<?php

namespace Modules\Subscription\App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Subscription\App\Models\SubscriptionPackage;
use Modules\Subscription\App\Services\SubscriptionFeatureRuleService;

/**
 * Setelah aturan `package_feature` sebuah paket diubah admin, rekonsiliasi KEANGGOTAAN fitur
 * (tambah/hapus) ke snapshot semua subscription aktif paket tsb. Nilai limit fitur existing tidak
 * diubah (grandfathering). Unik per package_id agar tidak menumpuk saat admin menyimpan berulang.
 */
class ReconcilePackageFeatureRulesJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600;
    public int $uniqueFor = 300;

    public function __construct(public string $packageId) {}

    public function uniqueId(): string
    {
        return $this->packageId;
    }

    public function handle(SubscriptionFeatureRuleService $service): void
    {
        $added = 0;
        $removed = 0;
        $subs = 0;

        SubscriptionPackage::where('package_id', $this->packageId)
            ->where('is_active', true)
            ->orderBy('id')
            ->chunkById(500, function ($batch) use ($service, &$added, &$removed, &$subs) {
                foreach ($batch as $subscription) {
                    $result = $service->reconcile($subscription);
                    $added += $result['added'];
                    $removed += $result['removed'];
                    $subs++;
                }
            });

        if ($added || $removed) {
            Log::info('[subscription] reconcile feature rules', [
                'package_id' => $this->packageId,
                'subscriptions' => $subs,
                'added' => $added,
                'removed' => $removed,
            ]);
        }
    }
}
