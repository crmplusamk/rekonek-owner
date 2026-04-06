<?php

namespace App\Console\Commands;

use App\Mail\SubscriberExpiryReminderMail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendSubscriberExpiryReminderCommand extends Command
{
    protected $signature = 'app:send-subscriber-expiry-reminder';

    protected $description = 'Queue email reminder H-7 for non-Free subscriptions';

    public function handle(): int
    {
        $targetDate = Carbon::today()->addDays(7)->toDateString();
        $targetDateLabel = Carbon::today()->addDays(7)->locale('id')->translatedFormat('d F Y');

        $rows = DB::table('subscription_packages as sp')
            ->join('packages as p', 'p.id', '=', 'sp.package_id')
            ->join('contacts as c', 'c.id', '=', 'sp.customer_id')
            ->whereNotNull('sp.expired_at')
            ->where('sp.is_active', true)
            ->where('p.name', '!=', 'Free')
            ->whereDate('sp.expired_at', $targetDate)
            ->select([
                'sp.id as subscription_id',
                'c.email',
                'c.name',
            ])
            ->get();

        if ($rows->isEmpty()) {
            $this->info('No non-Free subscriptions due in 7 days ('.$targetDate.').');

            return Command::SUCCESS;
        }

        $queued = 0;
        foreach ($rows as $row) {
            if (! is_object($row) || empty($row->email)) {
                continue;
            }

            Mail::to($row->email)->queue(
                (new SubscriberExpiryReminderMail(
                    $row->name ?? 'Customer',
                    $targetDateLabel
                ))->onQueue('emails')
            );
            $queued++;
        }

        $this->info("Queued {$queued} subscriber expiry reminder email(s) (H-7).");

        return Command::SUCCESS;
    }
}

