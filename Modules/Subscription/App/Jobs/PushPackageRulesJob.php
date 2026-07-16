<?php

namespace Modules\Subscription\App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Subscription\App\Models\SubscriptionPackage;
use Modules\Subscription\App\Services\SubscriptionFeatureRuleService;

/**
 * PUSH TERKONTROL: menerapkan aturan `package_feature` paket saat ini ke snapshot subscriber terpilih
 * (nilai limit dipaksa terbaru, ditandai source='admin_push', override manual dilewati kecuali diminta).
 *
 * $subscriptionIds null = semua subscription aktif paket; array = subset terpilih.
 */
class PushPackageRulesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600;

    public function __construct(
        public string $packageId,
        public ?array $subscriptionIds = null,
        public bool $overwriteManual = false
    ) {}

    public function handle(SubscriptionFeatureRuleService $service): void
    {
        $updated = $added = $removed = $skipped = $subs = 0;

        SubscriptionPackage::where('package_id', $this->packageId)
            ->where('is_active', true)
            ->when($this->subscriptionIds, fn ($q) => $q->whereIn('id', $this->subscriptionIds))
            ->orderBy('id')
            ->chunkById(500, function ($batch) use ($service, &$updated, &$added, &$removed, &$skipped, &$subs) {
                foreach ($batch as $subscription) {
                    $r = $service->pushOne($subscription, $this->overwriteManual);
                    $updated += $r['updated'];
                    $added += $r['added'];
                    $removed += $r['removed'];
                    $skipped += $r['skipped'];
                    $subs++;
                }
            });

        Log::info('[subscription] push package rules', [
            'package_id' => $this->packageId,
            'scope' => $this->subscriptionIds ? 'selected' : 'all',
            'overwrite_manual' => $this->overwriteManual,
            'subscriptions' => $subs,
            'updated' => $updated,
            'added' => $added,
            'removed' => $removed,
            'skipped_manual' => $skipped,
        ]);
    }
}
