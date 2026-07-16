<?php

namespace Modules\Subscription\App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Subscription\App\Models\SubscriptionFeatureRule;
use Modules\Subscription\App\Models\SubscriptionPackage;

/**
 * Membekukan (snapshot) aturan fitur bawaan paket ke `subscription_feature_rules` saat sebuah
 * subscription dibuat. Menjadi sumber kebenaran limit yang dibaca resolver (rekonek-app
 * SubscriptionService::getAuthData) menggantikan pembacaan live `package_feature`.
 *
 * Addon TIDAK di-snapshot — tetap dinamis lewat subscription_addons.
 */
class SubscriptionFeatureRuleService
{
    /**
     * Rebuild snapshot untuk satu subscription dari aturan `package_feature` paketnya SAAT INI.
     * Idempotent: hapus baris lama subscription tsb lalu isi ulang. Aman dipanggil berulang.
     *
     * @return int jumlah baris aturan yang ditulis
     */
    public function snapshot(SubscriptionPackage $subscription): int
    {
        if (! $subscription->package_id) {
            return 0;
        }

        return DB::transaction(function () use ($subscription) {
            // Ambil aturan template dari master paket. Dedupe per feature_id (pivot tidak punya
            // composite unique constraint) — ambil baris pertama per fitur.
            $rules = DB::table('package_feature')
                ->where('package_id', $subscription->package_id)
                ->get()
                ->unique('feature_id');

            SubscriptionFeatureRule::where('subscription_id', $subscription->id)->delete();

            if ($rules->isEmpty()) {
                return 0;
            }

            $now = Carbon::now();
            $rows = $rules->map(fn ($pivot) => [
                'id' => (string) Str::uuid(),
                'subscription_id' => $subscription->id,
                'feature_id' => $pivot->feature_id,
                'company_id' => $subscription->company_id,
                'limit' => $pivot->limit,
                'limit_type' => $pivot->limit_type,
                'included' => $pivot->included,
                'visiblity' => $pivot->visiblity,
                'source' => 'package',
                'created_at' => $now,
                'updated_at' => $now,
            ])->values()->all();

            SubscriptionFeatureRule::insert($rows);

            return count($rows);
        });
    }

    /**
     * Rekonsiliasi KEANGGOTAAN fitur satu subscription terhadap `package_feature` paketnya SAAT INI.
     * Hanya menambah fitur baru & menghapus fitur yang sudah tak ada di master — TIDAK menyentuh
     * nilai limit fitur yang sudah ada (grandfathering; perubahan nilai lewat push terkontrol).
     *
     * - Fitur baru (ada di master, belum ada di snapshot apa pun source-nya) → INSERT source='package'.
     * - Fitur hilang (baris source='package' yang feature-nya tak ada lagi di master) → DELETE.
     * - Baris source='manual'/'admin_push' TIDAK dihapus otomatis (override sengaja dilindungi).
     *
     * @return array{added:int,removed:int}
     */
    public function reconcile(SubscriptionPackage $subscription): array
    {
        if (! $subscription->package_id) {
            return ['added' => 0, 'removed' => 0];
        }

        return DB::transaction(function () use ($subscription) {
            $masterFeatureIds = DB::table('package_feature')
                ->where('package_id', $subscription->package_id)
                ->pluck('feature_id')
                ->unique();

            $snapshotRows = SubscriptionFeatureRule::where('subscription_id', $subscription->id)->get();
            $existingIds = $snapshotRows->pluck('feature_id');                              // semua source
            // Baris system-managed (bukan override manual) → subjek rekonsiliasi hapus.
            $managedIds = $snapshotRows->where('source', '!=', 'manual')->pluck('feature_id');

            $toAdd = $masterFeatureIds->diff($existingIds);      // di master, belum ada di snapshot
            $toRemove = $managedIds->diff($masterFeatureIds);    // system-managed yg hilang dari master

            $added = 0;
            $removed = 0;

            if ($toAdd->isNotEmpty()) {
                $now = Carbon::now();
                $pivots = DB::table('package_feature')
                    ->where('package_id', $subscription->package_id)
                    ->whereIn('feature_id', $toAdd)
                    ->get()
                    ->unique('feature_id');

                $rows = $pivots->map(fn ($pivot) => [
                    'id' => (string) Str::uuid(),
                    'subscription_id' => $subscription->id,
                    'feature_id' => $pivot->feature_id,
                    'company_id' => $subscription->company_id,
                    'limit' => $pivot->limit,
                    'limit_type' => $pivot->limit_type,
                    'included' => $pivot->included,
                    'visiblity' => $pivot->visiblity,
                    'source' => 'package',
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->values()->all();

                SubscriptionFeatureRule::insert($rows);
                $added = count($rows);
            }

            if ($toRemove->isNotEmpty()) {
                $removed = SubscriptionFeatureRule::where('subscription_id', $subscription->id)
                    ->where('source', '!=', 'manual')
                    ->whereIn('feature_id', $toRemove)
                    ->delete();
            }

            return ['added' => $added, 'removed' => $removed];
        });
    }

    /**
     * PUSH TERKONTROL: sinkronkan snapshot satu subscription ke aturan `package_feature` paket SAAT
     * INI — memaksa nilai limit terbaru (bukan menunggu renew). Baris ditandai `source='admin_push'`.
     * Baris `source='manual'` DILEWATI kecuali $overwriteManual=true. Membership juga ikut disinkron
     * (fitur baru ditambah, fitur hilang dihapus untuk baris non-manual).
     *
     * @return array{updated:int,added:int,removed:int,skipped:int}
     */
    public function pushOne(SubscriptionPackage $subscription, bool $overwriteManual = false): array
    {
        if (! $subscription->package_id) {
            return ['updated' => 0, 'added' => 0, 'removed' => 0, 'skipped' => 0];
        }

        return DB::transaction(function () use ($subscription, $overwriteManual) {
            $master = DB::table('package_feature')
                ->where('package_id', $subscription->package_id)
                ->get()
                ->unique('feature_id')
                ->keyBy('feature_id');

            $rows = SubscriptionFeatureRule::where('subscription_id', $subscription->id)->get()->keyBy('feature_id');

            $now = Carbon::now();
            $updated = $added = $removed = $skipped = 0;

            foreach ($master as $featureId => $pivot) {
                $existing = $rows->get($featureId);

                if ($existing && $existing->source === 'manual' && ! $overwriteManual) {
                    $skipped++;
                    continue;
                }

                $values = [
                    'limit' => $pivot->limit,
                    'limit_type' => $pivot->limit_type,
                    'included' => $pivot->included,
                    'visiblity' => $pivot->visiblity,
                    'source' => 'admin_push',
                    'updated_at' => $now,
                ];

                if ($existing) {
                    $existing->update($values);
                    $updated++;
                } else {
                    SubscriptionFeatureRule::insert(array_merge($values, [
                        'id' => (string) Str::uuid(),
                        'subscription_id' => $subscription->id,
                        'feature_id' => $featureId,
                        'company_id' => $subscription->company_id,
                        'created_at' => $now,
                    ]));
                    $added++;
                }
            }

            // Hapus fitur yg tak ada lagi di master (baris non-manual, atau manual bila overwrite).
            $removable = $rows->filter(fn ($r) => ! $master->has($r->feature_id)
                && ($overwriteManual || $r->source !== 'manual'));
            if ($removable->isNotEmpty()) {
                $removed = SubscriptionFeatureRule::where('subscription_id', $subscription->id)
                    ->whereIn('feature_id', $removable->pluck('feature_id'))
                    ->delete();
            }

            return compact('updated', 'added', 'removed', 'skipped');
        });
    }

    /** Subscription aktif milik sebuah paket (untuk picker & hitung dampak push). */
    public function activeSubscriptions(string $packageId)
    {
        return SubscriptionPackage::with('customer')
            ->where('package_id', $packageId)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Override MANUAL satu baris aturan untuk sebuah subscription (deal khusus per-company).
     * Ditandai `source='manual'` → dilindungi dari rekonsiliasi & push (kecuali overwrite).
     * `$values` = ['limit'(nullable string), 'limit_type'(nullable), 'included'(bool), 'visiblity'(bool)].
     */
    public function setManualRule(SubscriptionPackage $subscription, string $featureId, array $values): void
    {
        SubscriptionFeatureRule::updateOrCreate(
            ['subscription_id' => $subscription->id, 'feature_id' => $featureId],
            [
                'company_id' => $subscription->company_id,
                'limit' => $values['limit'] ?? null,
                'limit_type' => $values['limit_type'] ?? null,
                'included' => $values['included'] ?? false,
                'visiblity' => $values['visiblity'] ?? true,
                'source' => 'manual',
            ]
        );
    }

    /**
     * Reset satu baris aturan ke nilai `package_feature` paket saat ini (source='package').
     * Bila fitur sudah tak ada di master, baris snapshot dihapus.
     */
    public function resetRule(SubscriptionPackage $subscription, string $featureId): void
    {
        $pivot = DB::table('package_feature')
            ->where('package_id', $subscription->package_id)
            ->where('feature_id', $featureId)
            ->first();

        if (! $pivot) {
            SubscriptionFeatureRule::where('subscription_id', $subscription->id)
                ->where('feature_id', $featureId)
                ->delete();
            return;
        }

        SubscriptionFeatureRule::updateOrCreate(
            ['subscription_id' => $subscription->id, 'feature_id' => $featureId],
            [
                'company_id' => $subscription->company_id,
                'limit' => $pivot->limit,
                'limit_type' => $pivot->limit_type,
                'included' => $pivot->included,
                'visiblity' => $pivot->visiblity,
                'source' => 'package',
            ]
        );
    }
}
