<?php

namespace Modules\Subscription\App\Console;

use Illuminate\Console\Command;
use Modules\Subscription\App\Models\SubscriptionPackage;
use Modules\Subscription\App\Services\SubscriptionFeatureRuleService;

/**
 * Rekonsiliasi keanggotaan fitur snapshot (tambah/hapus) terhadap `package_feature` terkini,
 * untuk subscription aktif. Idempotent. Versi sinkron dari ReconcilePackageFeatureRulesJob —
 * berguna untuk ops manual & verifikasi. Nilai limit fitur existing TIDAK diubah (grandfathering).
 */
class ReconcileFeatureRulesCommand extends Command
{
    protected $signature = 'subscription:reconcile-feature-rules
        {--package= : Batasi ke satu package_id}
        {--company= : Batasi ke satu company_id}
        {--chunk=500 : Ukuran chunk iterasi}';

    protected $description = 'Rekonsiliasi keanggotaan fitur snapshot (tambah/hapus) untuk subscription aktif';

    public function handle(SubscriptionFeatureRuleService $service): int
    {
        $chunk = (int) $this->option('chunk') ?: 500;

        $query = SubscriptionPackage::query()
            ->where('is_active', true)
            ->when($this->option('package'), fn ($q, $p) => $q->where('package_id', $p))
            ->when($this->option('company'), fn ($q, $c) => $q->where('company_id', $c));

        $subs = 0;
        $added = 0;
        $removed = 0;

        $query->orderBy('id')->chunkById($chunk, function ($batch) use ($service, &$subs, &$added, &$removed) {
            foreach ($batch as $subscription) {
                $result = $service->reconcile($subscription);
                $added += $result['added'];
                $removed += $result['removed'];
                $subs++;
            }
        });

        $this->info("Rekonsiliasi selesai: {$subs} subscription, +{$added} fitur ditambah, -{$removed} fitur dihapus.");

        return self::SUCCESS;
    }
}
