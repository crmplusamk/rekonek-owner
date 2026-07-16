<?php

namespace Modules\Subscription\App\Console;

use Illuminate\Console\Command;
use Modules\Subscription\App\Models\SubscriptionPackage;
use Modules\Subscription\App\Services\SubscriptionFeatureRuleService;

/**
 * Isi snapshot `subscription_feature_rules` untuk subscriber existing dari aturan paket mereka
 * saat ini. Idempotent — aman dijalankan berulang. Bagian dari migrasi live-query → snapshot.
 */
class BackfillFeatureRulesCommand extends Command
{
    protected $signature = 'subscription:backfill-feature-rules
        {--company= : Batasi backfill ke satu company_id}
        {--chunk=500 : Ukuran chunk iterasi}';

    protected $description = 'Backfill snapshot aturan fitur (subscription_feature_rules) untuk subscription aktif';

    public function handle(SubscriptionFeatureRuleService $service): int
    {
        $company = $this->option('company');
        $chunk = (int) $this->option('chunk') ?: 500;

        $query = SubscriptionPackage::query()
            ->where('is_active', true)
            ->when($company, fn ($q) => $q->where('company_id', $company));

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->warn('Tidak ada subscription aktif yang cocok.');
            return self::SUCCESS;
        }

        $this->info("Backfill {$total} subscription (chunk {$chunk})...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $subs = 0;
        $rows = 0;
        $query->orderBy('id')->chunkById($chunk, function ($batch) use ($service, &$subs, &$rows, $bar) {
            foreach ($batch as $subscription) {
                $rows += $service->snapshot($subscription);
                $subs++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Selesai: {$subs} subscription, {$rows} baris aturan ditulis.");

        return self::SUCCESS;
    }
}
