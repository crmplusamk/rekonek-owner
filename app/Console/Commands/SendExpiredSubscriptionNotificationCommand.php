<?php

namespace App\Console\Commands;

use App\Jobs\DisconnectCompanyWhatsappChannelsJob;
use App\Mail\SubscriptionExpiredMail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendExpiredSubscriptionNotificationCommand extends Command
{
    protected $signature = 'app:send-expired-subscription-notification';

    protected $description = 'Queue email the day after subscription expiry (expired yesterday → notify today)';

    public function handle(): int
    {
        $yesterday = Carbon::yesterday()->startOfDay();
        $yesterdayDate = $yesterday->toDateString();

        $rows = DB::table('subscription_packages as sp')
            ->join('packages as p', 'p.id', '=', 'sp.package_id')
            ->join('contacts as c', 'c.id', '=', 'sp.customer_id')
            ->whereNotNull('sp.expired_at')
            ->whereDate('sp.expired_at', $yesterdayDate)
            ->select([
                'sp.id as subscription_id',
                'sp.company_id as subscription_company_id',
                'c.company_id as contact_company_id',
                'c.email',
                'c.name',
                'p.name as package_name',
                'sp.expired_at',
            ])
            ->orderBy('sp.expired_at')
            ->get();

        if ($rows->isEmpty()) {
            $this->info('No subscriptions expired on '.$yesterdayDate.'.');

            return Command::SUCCESS;
        }

        $expiredDateLabel = $yesterday->locale('id')->translatedFormat('d F Y');

        $companyIds = [];
        foreach ($rows as $row) {
            if (! is_object($row)) {
                continue;
            }
            $cid = $row->subscription_company_id ?? $row->contact_company_id ?? null;
            if (! empty($cid)) {
                $companyIds[(string) $cid] = true;
            }
        }

        foreach (array_keys($companyIds) as $companyId) {
            DisconnectCompanyWhatsappChannelsJob::dispatch($companyId)->onQueue('default');
        }

        $queued = 0;
        foreach ($rows as $row) {
            if (! is_object($row) || empty($row->email)) {
                continue;
            }

            Mail::to($row->email)->queue(
                (new SubscriptionExpiredMail(
                    $row->name ?? 'Customer',
                    $row->package_name ?? 'Paket',
                    $expiredDateLabel
                ))->onQueue('emails')
            );
            $queued++;
        }

        $this->info('Queued '.count($companyIds).' WhatsApp disconnect job(s) for '.$yesterdayDate.'.');
        $this->info("Queued {$queued} expired subscription email(s) for expiry date {$yesterdayDate}.");

        return Command::SUCCESS;
    }
}
