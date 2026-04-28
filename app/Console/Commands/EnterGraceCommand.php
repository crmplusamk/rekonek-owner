<?php

namespace App\Console\Commands;

use App\Jobs\DisconnectCompanyWhatsappChannelsJob;
use App\Services\GracePeriod\GraceDripService;
use App\Services\GracePeriod\GraceLifecycleService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * app:enter-grace
 *
 * Dijadwalkan harian. Mencari subscription_packages yang expired_at = kemarin
 * dan is_grace = 'active', lalu:
 *   1. Transisi state: is_grace → 'grace', grace_started_at = hari ini, is_active → false
 *   2. Dispatch DisconnectCompanyWhatsappChannelsJob per company (preserve
 *      behavior existing dari SendExpiredSubscriptionNotificationCommand)
 *   3. Dispatch touchpoint H+1 notification (WA + Email) via GraceDripService
 *
 * Menggantikan SendExpiredSubscriptionNotificationCommand (retired di schedule).
 */
class EnterGraceCommand extends Command
{
    protected $signature = 'app:enter-grace
                            {--dry-run : Tampilkan kandidat tanpa dispatch}
                            {--company-id= : Filter satu company saja}
                            {--force : Dispatch ulang walaupun grace_logs sudah ada}';

    protected $description = 'Transisi subscription yang expired kemarin ke grace state + dispatch H+1 notification';

    public function handle(
        GraceLifecycleService $lifecycle,
        GraceDripService $drip
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $companyIdFilter = $this->option('company-id') ?: null;

        $today = Carbon::today();
        $touchpointKey = 'H+1';
        $config = config("grace-touchpoints.touchpoints.$touchpointKey");

        if (empty($config)) {
            $this->error("Touchpoint config not found: $touchpointKey");

            return self::FAILURE;
        }

        if (! ($config['enabled'] ?? true)) {
            $this->warn("Touchpoint $touchpointKey disabled in config — skipping dispatch, tapi tetap lanjut transisi state.");
        }

        $candidates = $lifecycle->findEnterGraceCandidates($today, $companyIdFilter);

        if ($candidates->isEmpty()) {
            $this->info('No subscriptions to enter grace (expired yesterday AND is_grace=active).');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '[%s] Found %d subscription(s) to enter grace period.%s',
            $today->toDateString(),
            $candidates->count(),
            $dryRun ? ' [DRY RUN]' : ''
        ));

        $transitioned = 0;
        $dispatchedPerChannel = [];
        $waDisconnectQueued = [];

        foreach ($candidates as $row) {

            $this->line(sprintf(
                '  → company=%s  sub_pkg=%s  expired=%s  email=%s  phone=%s',
                $row->company_id,
                $row->subscription_package_id,
                $row->expired_at,
                $row->email ?: '(none)',
                $row->phone ?: '(none)'
            ));

            if ($dryRun) {
                $dispatched = $drip->dispatchForTarget(
                    (object) array_merge((array) $row, ['grace_started_at' => $today->toDateString()]),
                    $touchpointKey,
                    $config,
                    ['dry_run' => true]
                );
                $this->line('     would dispatch: '.implode(', ', $dispatched ?: ['(none)']));

                continue;
            }

            $ok = $lifecycle->transitionToGrace((string) $row->subscription_package_id, $today);

            if (! $ok) {
                $this->warn(sprintf('     skip: transition failed (concurrent update?) — sub_pkg=%s', $row->subscription_package_id));

                continue;
            }

            $transitioned++;

            $companyId = (string) $row->company_id;
            if ($companyId !== '' && ! isset($waDisconnectQueued[$companyId])) {
                DisconnectCompanyWhatsappChannelsJob::dispatch($companyId)->onQueue('default');
                $waDisconnectQueued[$companyId] = true;
            }

            if ($config['enabled'] ?? true) {
                $row->grace_started_at = $today->toDateString();

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
        }

        $this->info('');
        $this->info(sprintf(
            'Summary: transitioned=%d  wa_disconnect_queued=%d  %s',
            $transitioned,
            count($waDisconnectQueued),
            collect($dispatchedPerChannel)
                ->map(fn ($count, $ch) => "$ch=$count")
                ->values()
                ->implode('  ')
        ));

        return self::SUCCESS;
    }
}
