<?php

namespace Modules\Subscription\App\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Modules\Addon\App\Models\Addon;
use Modules\Subscription\App\Models\SubscriptionAddon;
use Modules\Subscription\App\Models\SubscriptionPackage;

class SubscriptionService
{
    public function updatePackage($data)
    {
        $data = SubscriptionPackage::create([
            'code' => Str::upper(Str::random(5)),
            'customer_id' => $data['customer_id'],
            'company_id' => $data['company_id'],
            'package_id' => $data['package_id'],
            'termin_duration' => $data['termin_duration'],
            'termin' => $data['termin'],
            'started_at' => $data['started_at'],
            'expired_at' => $data['expired_at'],
            'is_active' => true,
            'is_trial' => $data['is_trial'] ?? 'trial',
            'is_grace' => $data['is_grace'] ?? 'active',
            'grace_started_at' => $data['grace_started_at'] ?? null,
        ]);

        // Bekukan snapshot aturan fitur bawaan paket untuk cycle baru ini (grandfathering).
        app(SubscriptionFeatureRuleService::class)->snapshot($data);

        $data->load('package');
        return $data;
    }

    /**
     * Upsert addon subscription per (customer, company, addon).
     *
     * @param bool $isRenewal true bila dari settlement invoice type='renew'. Untuk addon RECURRING
     *   saat perpanjangan, kapasitas DIPERTAHANKAN (set = charge invoice, tidak digandakan) — hanya
     *   periode diperpanjang. Addon ONETIME (AI Credit) tetap akumulasi/topup (reset bila lapse
     *   penuh) baik saat beli maupun renew. Pembelian biasa (non-renewal) selalu akumulasi.
     */
    public function updateAddon($data, bool $isRenewal = false)
    {
        $existAddon = SubscriptionAddon::where([
            'customer_id' => $data['customer_id'],
            'company_id' => $data['company_id'],
            'addon_id' => $data['addon_id']
        ])->first();

        if (!$existAddon) {
            $existAddon = SubscriptionAddon::create([
                'code' => Str::upper(Str::random(5)),
                'customer_id' => $data['customer_id'],
                'company_id' => $data['company_id'],
                'addon_id' => $data['addon_id'],
                'charge' => $data['charge'],
                'started_at' => $data['started_at'],
                'expired_at' => $data['expired_at'],
                'is_active' => true,
            ]);
        } else {
            $isOneTime = $this->isOneTime($data['addon_id']);

            // ONETIME (prepaid, mis. AI Credit): jika saldo sudah lapse PENUH (expired melewati jendela
            // grace), saldo lama hangus — perlakukan sebagai saldo baru (RESET). Selama kontinu →
            // akumulasi. Ambang selaras extendOneTimeAddonExpiry.
            $graceDays = \App\Services\GracePeriod\GraceLifecycleService::GRACE_DURATION_DAYS;
            $lapsed = $isOneTime
                && $existAddon->expired_at
                && Carbon::parse($existAddon->expired_at)->endOfDay()->lt(Carbon::now()->subDays($graceDays));

            if ($isRenewal && !$isOneTime) {
                // Perpanjangan addon RECURRING: PERTAHANKAN kapasitas (charge invoice renewal =
                // kapasitas saat ini), JANGAN gandakan. Hanya periode yang diperpanjang.
                $charge = $data['charge'];
            } elseif ($lapsed) {
                $charge = $data['charge']; // ONETIME lapse penuh → reset saldo
            } else {
                $charge = $existAddon->charge + $data['charge']; // beli tambah / topup onetime
            }

            $existAddon->update([
                'charge' => $charge,
                'started_at' => $data['started_at'],
                'expired_at' => $data['expired_at'],
                'is_active' => true,
            ]);
        }

        return $existAddon;
    }

    /**
     * Perpanjang expired_at addon ONETIME (prepaid, mis. AI Credit) aktif milik company ke akhir cycle
     * langganan yang baru, agar saldo tetap valid (carry-over) di cycle berikutnya. Dipanggil saat
     * settlement paket (new/renew) — bila tanpa ini, addon akan expired di tengah cycle baru dan
     * saldo hilang meski user memperpanjang.
     *
     * HANYA memperpanjang addon yang masih kontinu: belum expired, ATAU expired tetapi masih dalam
     * jendela grace. Addon yang sudah lapse penuh (expired > grace) TIDAK diperpanjang → saldo hangus
     * dan tidak "resurrect" saat company berlangganan lagi dari nol.
     */
    public function extendOneTimeAddonExpiry(string $companyId, $expiredAt): void
    {
        $oneTimeAddonIds = Addon::where('billing_type', Addon::BILLING_ONETIME)->pluck('id');

        if ($oneTimeAddonIds->isEmpty()) {
            return;
        }

        $graceDays = \App\Services\GracePeriod\GraceLifecycleService::GRACE_DURATION_DAYS;
        $continuityFloor = Carbon::now()->subDays($graceDays);

        SubscriptionAddon::where('company_id', $companyId)
            ->whereIn('addon_id', $oneTimeAddonIds)
            ->where('is_active', true)
            ->where('expired_at', '>=', $continuityFloor)
            ->update(['expired_at' => $expiredAt]);
    }

    private function isOneTime($addonId): bool
    {
        return Addon::where('id', $addonId)
            ->where('billing_type', Addon::BILLING_ONETIME)
            ->exists();
    }
}
