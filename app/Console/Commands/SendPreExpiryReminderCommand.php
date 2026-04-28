<?php

namespace App\Console\Commands;

use App\Jobs\Grace\SendGraceWhatsappJob;
use App\Mail\Grace\GraceTouchpointMail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * app:send-pre-expiry-reminder
 *
 * Touchpoint H-3 (grace period design — Fase 1: Salvage).
 *
 * Menggantikan SendFreePackageReminderCommand lama (yang hanya handle trial H-5 & H-3
 * email-only). Command baru ini mengirim reminder halus via WA + Email ke
 * SEMUA subscription aktif (trial maupun paid) yang expired dalam 3 hari.
 *
 * H-3 BERBEDA dari touchpoint H+N: ini PRE-expiry, jadi tidak di-track di tabel
 * grace_logs (idempotency cukup dijaga dari daily cron + filter tanggal exact).
 *
 * Narasi: "3 Hari lagi masa langganan berakhir. Jangan sampai alur kerja tim
 * Anda terganggu. Perpanjang sekarang untuk akses tanpa batas."
 */
class SendPreExpiryReminderCommand extends Command
{
    protected $signature = 'app:send-pre-expiry-reminder
                            {--dry-run : Tampilkan kandidat tanpa dispatch}
                            {--company-id= : Filter satu company saja}';

    protected $description = 'Send H-3 pre-expiry reminder (WA + Email) ke semua subscription aktif yang expired dalam 3 hari';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $companyIdFilter = $this->option('company-id') ?: null;

        $today = Carbon::today();
        $targetDate = $today->copy()->addDays(3)->toDateString();
        $targetDateLabel = $today->copy()->addDays(3)->locale('id')->translatedFormat('d F Y');

        $query = DB::table('subscription_packages as sp')
            ->join('contacts as c', 'c.id', '=', 'sp.customer_id')
            ->whereNotNull('sp.expired_at')
            ->where('sp.is_active', true)
            ->where('sp.is_grace', 'active')
            ->whereDate('sp.started_at', '<=', $today->toDateString())
            ->whereDate('sp.expired_at', $targetDate)
            ->select([
                'sp.id as subscription_package_id',
                'sp.company_id',
                'sp.expired_at',
                'sp.is_trial',
                'c.email',
                'c.name',
                'c.phone',
            ]);

        if ($companyIdFilter) {
            $query->where('sp.company_id', $companyIdFilter);
        }

        $rows = $query->get();

        if ($rows->isEmpty()) {
            $this->info("No subscriptions expiring in 3 days ({$targetDate}).");

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '[%s] Found %d subscription(s) expiring on %s (H-3).%s',
            $today->toDateString(),
            $rows->count(),
            $targetDateLabel,
            $dryRun ? ' [DRY RUN]' : ''
        ));

        $emailTemplate = 'emails.grace.h-minus-3';
        $emailSubject = '3 Hari Lagi — Masa Langganan Anda Akan Berakhir';
        $waTemplate = 'emails.grace.wa.h-minus-3';

        $emailQueued = 0;
        $waQueued = 0;

        foreach ($rows as $row) {
            if (! is_object($row)) {
                continue;
            }

            $payload = [
                'name' => $row->name ?? 'Customer',
                'company_id' => (string) $row->company_id,
                'expired_at' => $row->expired_at,
                'expired_at_label' => $targetDateLabel,
                'is_trial' => $row->is_trial ?? 'subs',
                'subscription_package_id' => (string) $row->subscription_package_id,
            ];

            $this->line(sprintf(
                '  → company=%s  type=%s  expired=%s  email=%s  phone=%s',
                $row->company_id,
                $row->is_trial ?? '?',
                $row->expired_at,
                $row->email ?: '(none)',
                $row->phone ?: '(none)'
            ));

            if ($dryRun) {
                $dispatched = [];
                if (! empty($row->email)) {
                    $dispatched[] = 'email (dry-run)';
                }
                if (! empty($row->phone)) {
                    $dispatched[] = 'wa (dry-run)';
                }
                $this->line('     would dispatch: '.implode(', ', $dispatched ?: ['(none)']));

                continue;
            }

            if (! empty($row->email)) {
                Mail::to($row->email)->queue(
                    (new GraceTouchpointMail($emailTemplate, $emailSubject, $payload))
                        ->onQueue('emails')
                );
                $emailQueued++;
            }

            if (! empty($row->phone)) {
                SendGraceWhatsappJob::dispatch(
                    null, // no grace_logs tracking untuk pre-expiry
                    (string) $row->phone,
                    $waTemplate,
                    $payload
                )->onQueue('default');
                $waQueued++;
            }
        }

        $this->info('');
        $this->info("Summary: email={$emailQueued}  wa={$waQueued}");

        return self::SUCCESS;
    }
}
