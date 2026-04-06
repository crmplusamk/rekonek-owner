<?php

namespace App\Console\Commands;

use App\Mail\FreePackageReminderMail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendFreePackageReminderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-free-package-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queue email reminder for Free package that expires in 10, 7, or 3 days';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $targets = [10, 7, 3];
        $targetDates = collect($targets)
            ->map(fn (int $day) => Carbon::today()->addDays($day)->toDateString())
            ->all();

        $rows = DB::table('subscription_packages as sp')
            ->join('packages as p', 'p.id', '=', 'sp.package_id')
            ->join('contacts as c', 'c.id', '=', 'sp.customer_id')
            ->where('p.name', 'Free')
            ->where('sp.is_active', true)
            ->whereNotNull('sp.expired_at')
            ->whereIn(DB::raw('DATE(sp.expired_at)'), $targetDates)
            ->select([
                'sp.id as subscription_id',
                'c.email',
                'c.name',
                'sp.expired_at',
            ])
            ->get();

        if ($rows->isEmpty()) {
            $this->info('No Free package subscriptions due in 10/7/3 days.');

            return Command::SUCCESS;
        }

        $queued = 0;
        foreach ($rows as $row) {
            if (! is_object($row)) {
                continue;
            }

            if (empty($row->email)) {
                continue;
            }

            $daysLeft = Carbon::today()->diffInDays(Carbon::parse($row->expired_at)->startOfDay(), false);
            if (! in_array($daysLeft, $targets, true)) {
                continue;
            }

            Mail::to($row->email)->queue(
                (new FreePackageReminderMail(
                    $row->name ?? 'Customer',
                    $daysLeft
                ))->onQueue('emails')
            );
            $queued++;
        }

        $this->info("Queued {$queued} reminder email job(s).");

        return Command::SUCCESS;
    }
}
