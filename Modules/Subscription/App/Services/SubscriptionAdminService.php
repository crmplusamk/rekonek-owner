<?php

namespace Modules\Subscription\App\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Modules\Addon\App\Models\Addon;
use Modules\Logs\App\Services\LogService;
use Modules\Subscription\App\Models\SubscriptionAddon;
use Modules\Subscription\App\Models\SubscriptionPackage;

/**
 * Manipulasi MANUAL langganan oleh admin owner (ubah paket, kelola addon) langsung pada record
 * subscription — di LUAR alur checkout/billing (tanpa invoice). Ini koreksi/override admin,
 * pelengkap override snapshot aturan fitur (SubscriptionFeatureRuleService::setManualRule).
 *
 * Catatan arsitektur penting (lihat peta owner→app):
 * - rekonek-app membaca tabel ini LIVE lintas-DB (DB::connection('owner')) — perubahan langsung
 *   terlihat pada query baru. Namun app menyimpan hasil di session (refresh saat login / hit
 *   endpoint billing) dan AI Credit di Redis (TTL ~300s), jadi efek bisa tertunda sampai sesi
 *   user disegarkan. Tidak ada mekanisme push/bust dari owner (keterbatasan arsitektur saat ini).
 * - Addon NON-AI-Credit di app dijumlahkan ke limit TANPA cek is_active (rekonek-app
 *   SubscriptionService::getAuthData). Karena itu "hapus addon" harus benar-benar MENGHAPUS row
 *   (hard delete), bukan sekadar is_active=false — kalau tidak, entitlement tetap menempel di app.
 */
class SubscriptionAdminService
{
    public function __construct(
        private SubscriptionFeatureRuleService $ruleService,
        private SubscriptionService $subscriptionService,
    ) {}

    /**
     * Ubah paket langganan (upgrade/downgrade) IN-PLACE pada row yang sama:
     * - set paket & periode baru, reset status grace ke aktif (silent resume dari grace),
     * - rebuild snapshot aturan fitur dari paket baru (override manual pada subscription ini
     *   ikut ter-reset ke default paket baru — perilaku yang benar saat paket berganti),
     * - samakan expired addon AI Credit ke akhir cycle baru (carry-over saldo prepaid),
     * - matikan row langganan aktif lain milik company agar tetap single-active (higiene).
     */
    public function changePackage(SubscriptionPackage $subscription, array $data): SubscriptionPackage
    {
        $started = Carbon::parse($data['started_at'])->startOfDay();
        $expired = $started->copy()->add($data['termin'] . 's', (int) $data['termin_duration']);
        $wasGrace = $subscription->is_grace !== 'active';

        // Higiene single-active: pastikan hanya row ini yang aktif untuk company tsb.
        SubscriptionPackage::where('company_id', $subscription->company_id)
            ->where('id', '!=', $subscription->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $subscription->update([
            'package_id' => $data['package_id'],
            'termin' => $data['termin'],
            'termin_duration' => (int) $data['termin_duration'],
            'started_at' => $started,
            'expired_at' => $expired,
            'is_trial' => $data['is_trial'],
            'is_active' => true,
            'is_grace' => 'active',
            'grace_started_at' => null,
        ]);

        $subscription->refresh();

        // Snapshot fitur = sumber kebenaran limit yang dibaca app. Wajib rebuild saat paket ganti.
        $this->ruleService->snapshot($subscription);

        // Carry-over saldo AI Credit ke akhir cycle baru (samakan expired_at).
        $this->subscriptionService->extendOneTimeAddonExpiry($subscription->company_id, $subscription->expired_at);

        $subscription->load('package');
        LogService::create([
            'fid' => $subscription->id,
            'category' => 'subscription',
            'title' => 'Admin Ubah Paket',
            'note' => "Paket diubah admin menjadi {$subscription->package?->name}"
                . ($wasGrace ? ' — status grace direset ke aktif, data grace di-clear' : ''),
            'company_id' => $subscription->company_id,
        ]);

        return $subscription;
    }

    /**
     * Tambah addon untuk company (SET eksak, bukan akumulasi seperti alur beli). Upsert per
     * (customer, company, addon) — bila row addon tsb sudah ada (mis. sisa nonaktif) di-reuse & di-set.
     */
    public function addAddon(SubscriptionPackage $subscription, array $data): SubscriptionAddon
    {
        $master = Addon::findOrFail($data['addon_id']);
        $blockSize = max(1, (int) $master->charge);
        $charge = $blockSize * max(1, (int) $data['quantity']);

        $addon = SubscriptionAddon::firstOrNew([
            'customer_id' => $subscription->customer_id,
            'company_id' => $subscription->company_id,
            'addon_id' => $master->id,
        ]);

        $addon->fill([
            'code' => $addon->code ?: Str::upper(Str::random(5)),
            'charge' => $charge,
            'started_at' => Carbon::parse($data['started_at'])->startOfDay(),
            'expired_at' => Carbon::parse($data['expired_at'])->endOfDay(),
            'is_active' => true,
        ])->save();

        LogService::create([
            'fid' => $addon->id,
            'category' => 'subscription',
            'title' => 'Admin Tambah Addon',
            'note' => "Addon {$master->name} ditambahkan admin (charge {$charge})",
            'company_id' => $subscription->company_id,
        ]);

        return $addon;
    }

    /** Ubah addon aktif — SET nilai eksak (charge dihitung dari jumlah blok × ukuran blok master). */
    public function updateAddon(SubscriptionAddon $addon, array $data): SubscriptionAddon
    {
        $master = $addon->addon()->first();
        $blockSize = max(1, (int) ($master->charge ?? 1));
        $charge = $blockSize * max(1, (int) $data['quantity']);

        $addon->update([
            'charge' => $charge,
            'started_at' => Carbon::parse($data['started_at'])->startOfDay(),
            'expired_at' => Carbon::parse($data['expired_at'])->endOfDay(),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        LogService::create([
            'fid' => $addon->id,
            'category' => 'subscription',
            'title' => 'Admin Ubah Addon',
            'note' => "Addon {$master?->name} diubah admin (charge {$charge})",
            'company_id' => $addon->company_id,
        ]);

        return $addon;
    }

    /**
     * Hapus addon — HARD DELETE. Wajib menghapus row (bukan is_active=false) agar entitlement
     * benar-benar hilang di app: resolver app menjumlahkan charge addon non-AI-Credit ke limit
     * tanpa memeriksa is_active. Hard delete juga menghindari "resurrect + akumulasi" saat company
     * membeli addon yang sama lagi lewat checkout (updateAddon upsert per customer+company+addon).
     */
    public function removeAddon(SubscriptionAddon $addon): void
    {
        $master = $addon->addon()->first();
        $companyId = $addon->company_id;
        $addonId = $addon->id;

        $addon->delete();

        LogService::create([
            'fid' => $addonId,
            'category' => 'subscription',
            'title' => 'Admin Hapus Addon',
            'note' => "Addon {$master?->name} dihapus admin",
            'company_id' => $companyId,
        ]);
    }
}
