<?php

namespace App\Console\Commands;

use App\Jobs\DeleteCompanyDataJob;
use App\Jobs\DeleteCompanyMongoDataJob;
use App\Services\GracePeriod\GraceDripService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * app:drip-grace
 *
 * Dijadwalkan harian. Iterate semua touchpoint config('grace-touchpoints.touchpoints')
 * dengan handler='drip' (middle touchpoints antara H+1 dan H+31), query kandidat
 * per touchpoint, dan dispatch notifikasi WA + Email sesuai channel config.
 *
 * Handler 'enter' (H+1) dan 'terminate' (H+31) tidak diproses di sini — mereka
 * ditangani oleh EnterGraceCommand dan TerminateGraceCommand secara terpisah
 * karena butuh transisi state.
 */
class DripGraceCommand extends Command
{
    protected $signature = 'app:drip-grace
                            {--dry-run : Tampilkan kandidat tanpa dispatch}
                            {--touchpoint= : Proses hanya satu touchpoint (misal H+7)}
                            {--company-id= : Filter satu company saja}
                            {--force : Dispatch ulang walaupun grace_logs sudah ada}';

    protected $description = 'Dispatch drip campaign notifications untuk touchpoint tengah (H+3 s/d H+29) pada users is_grace=grace';

    public function handle(GraceDripService $drip): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $companyIdFilter = $this->option('company-id') ?: null;
        $touchpointFilter = $this->option('touchpoint') ?: null;

        $today = Carbon::today();
        $touchpoints = config('grace-touchpoints.touchpoints', []);

        if (empty($touchpoints)) {
            $this->error('No touchpoints configured in config/grace-touchpoints.php');

            return self::FAILURE;
        }

        $this->info(sprintf(
            '[%s] DripGraceCommand start%s',
            $today->toDateString(),
            $dryRun ? ' [DRY RUN]' : ''
        ));

        $totalDispatched = [];
        $processedTouchpoints = 0;

        foreach ($touchpoints as $key => $config) {

            if (($config['handler'] ?? null) !== 'drip') {
                continue;
            }

            if ($touchpointFilter !== null && $key !== $touchpointFilter) {
                continue;
            }

            if (! ($config['enabled'] ?? true)) {
                $this->line("  — skip $key: disabled in config");

                continue;
            }

            $this->info(sprintf('  Touchpoint %s (phase %s, channels: %s):',
                $key,
                $config['phase'] ?? '?',
                implode('+', $config['channels'] ?? [])
            ));

            $candidates = $drip->findDripCandidates($config, $today, $companyIdFilter);
            $processedTouchpoints++;

            if ($candidates->isEmpty()) {
                $this->line('     no candidates');

                continue;
            }

            foreach ($candidates as $row) {
                $dispatched = $drip->dispatchForTarget(
                    $row,
                    $key,
                    $config,
                    [
                        'dry_run' => $dryRun,
                        'force' => $force,
                    ]
                );

                $this->line(sprintf(
                    '     → company=%s email=%s phone=%s → [%s]',
                    $row->company_id,
                    $row->email ?: '(none)',
                    $row->phone ?: '(none)',
                    implode(', ', $dispatched ?: ['skip'])
                ));

                foreach ($dispatched as $channelLabel) {
                    $key2 = "$key:$channelLabel";
                    $totalDispatched[$key2] = ($totalDispatched[$key2] ?? 0) + 1;
                }

                if (! $dryRun && ! empty($config['triggers_deletion'])) {
                    $this->dispatchDeletionJobs($row->company_id, (int) ($config['deletion_delay_hours'] ?? 0));
                    $this->line('     → deletion queued: postgres + mongo');
                }
            }
        }

        $this->info('');
        $this->info(sprintf(
            'Summary: touchpoints_processed=%d  dispatched=%s',
            $processedTouchpoints,
            collect($totalDispatched)
                ->map(fn ($count, $label) => "$label=$count")
                ->values()
                ->implode('  ') ?: '(none)'
        ));

        return self::SUCCESS;
    }

    /**
     * Dispatch kedua delete job (PostgreSQL client DB + MongoDB) untuk satu company.
     * Dipakai saat touchpoint dengan flag `triggers_deletion` diproses.
     */
    private function dispatchDeletionJobs(string $companyId, int $delayHours): void
    {
        $pgJob    = DeleteCompanyDataJob::dispatch($companyId);
        $mongoJob = DeleteCompanyMongoDataJob::dispatch($companyId);

        if ($delayHours > 0) {
            $runAt = now()->addHours($delayHours);
            $pgJob->delay($runAt);
            $mongoJob->delay($runAt);
        }
    }
}
