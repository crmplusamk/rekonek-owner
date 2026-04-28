<?php

namespace App\Console\Commands;

use App\Services\GracePeriod\GraceDripService;
use App\Services\GracePeriod\GraceLifecycleService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * app:terminate-grace
 *
 * Dijadwalkan harian. Mencari subscription_packages yang is_grace='grace'
 * DAN grace_started_at + 30 hari <= hari ini, lalu:
 *   1. Transisi state: is_grace → 'end_grace'
 *   2. Dispatch touchpoint H+31 final notice (WA + Email)
 *
 * Hook untuk data deletion pipeline ada di sini — di comment; implementasi
 * actual deletion di luar scope dokumen desain 2026-04-19.
 */
class TerminateGraceCommand extends Command
{
    protected $signature = 'app:terminate-grace
                            {--dry-run : Tampilkan kandidat tanpa dispatch atau transisi}
                            {--company-id= : Filter satu company saja}
                            {--force : Dispatch ulang walaupun grace_logs sudah ada}';

    protected $description = 'Transisi grace → end_grace untuk subscription yang grace-nya 30 hari, dispatch H+31 final notice';

    public function handle(
        GraceLifecycleService $lifecycle,
        GraceDripService $drip
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $companyIdFilter = $this->option('company-id') ?: null;

        $today = Carbon::today();
        $touchpointKey = 'H+31';
        $config = config("grace-touchpoints.touchpoints.$touchpointKey");

        if (empty($config)) {
            $this->error("Touchpoint config not found: $touchpointKey");

            return self::FAILURE;
        }

        $candidates = $lifecycle->findTerminateCandidates($today, $companyIdFilter);

        if ($candidates->isEmpty()) {
            $this->info(sprintf(
                '[%s] No subscriptions to terminate (need is_grace=grace AND grace_started_at <= %s).',
                $today->toDateString(),
                $today->copy()->subDays(GraceLifecycleService::GRACE_DURATION_DAYS)->toDateString()
            ));

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '[%s] Found %d subscription(s) to terminate grace (→ end_grace).%s',
            $today->toDateString(),
            $candidates->count(),
            $dryRun ? ' [DRY RUN]' : ''
        ));

        $transitioned = 0;
        $dispatchedPerChannel = [];

        foreach ($candidates as $row) {

            $this->line(sprintf(
                '  → company=%s  sub_pkg=%s  grace_started=%s  email=%s  phone=%s',
                $row->company_id,
                $row->subscription_package_id,
                $row->grace_started_at,
                $row->email ?: '(none)',
                $row->phone ?: '(none)'
            ));

            if ($dryRun) {
                $dispatched = $drip->dispatchForTarget($row, $touchpointKey, $config, ['dry_run' => true]);
                $this->line('     would dispatch: '.implode(', ', $dispatched ?: ['(none)']));

                continue;
            }

            $ok = $lifecycle->transitionToEndGrace((string) $row->subscription_package_id);

            if (! $ok) {
                $this->warn(sprintf('     skip: transition failed (concurrent update?) — sub_pkg=%s', $row->subscription_package_id));

                continue;
            }

            $transitioned++;

            if ($config['enabled'] ?? true) {
                $dispatched = $drip->dispatchForTarget(
                    $row,
                    $touchpointKey,
                    $config,
                    ['force' => $force]
                );

                foreach ($dispatched as $channel) {
                    $dispatchedPerChannel[$channel] = ($dispatchedPerChannel[$channel] ?? 0) + 1;
                }

                $this->line('     dispatched: '.implode(', ', $dispatched ?: ['(none)']));
            }

            // Data deletion TIDAK di-dispatch di sini. Sesuai desain grace
            // lifecycle, eksekusi delete dilakukan pada touchpoint H+29
            // (lewat flag `triggers_deletion` di config/grace-touchpoints.php,
            // diproses oleh DripGraceCommand). H+31 murni farewell + transisi
            // state `grace → end_grace` — akun dianggap sudah dihapus di titik
            // ini, sehingga copy pesan H+31 bisa berbunyi "akun telah resmi
            // kami nonaktifkan".
        }

        $this->info('');
        $this->info(sprintf(
            'Summary: transitioned=%d  %s',
            $transitioned,
            collect($dispatchedPerChannel)
                ->map(fn ($count, $ch) => "$ch=$count")
                ->values()
                ->implode('  ')
        ));

        return self::SUCCESS;
    }
}
