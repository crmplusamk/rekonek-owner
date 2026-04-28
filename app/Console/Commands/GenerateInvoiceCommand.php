<?php

namespace App\Console\Commands;

use App\Jobs\GenerateRenewalInvoiceJob;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Subscription\App\Models\SubscriptionPackage;

class GenerateInvoiceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:invoice-generate-renew';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate renewal invoices 7 days before subscription expires';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting invoice generation for renewals...');

        $sevenDaysFromNow = Carbon::now()->addDays(7)->format('Y-m-d');

        $subscriptionPackages = SubscriptionPackage::where('is_active', true)
            ->where('is_grace', 'active')
            ->whereDate('started_at', '<=', Carbon::today()->toDateString())
            ->whereDate('expired_at', $sevenDaysFromNow)
            ->select(['id', 'company_id'])
            ->get();

        $this->info("Found {$subscriptionPackages->count()} subscription packages to renew.");

        if ($subscriptionPackages->isEmpty()) {
            $this->info('No packages to renew.');
            return;
        }

        foreach ($subscriptionPackages as $subsPackage) {
            GenerateRenewalInvoiceJob::dispatch($subsPackage->id, $subsPackage->company_id);
            $this->info("Dispatched job for company {$subsPackage->company_id} (subscription: {$subsPackage->id})");
        }

        $this->info('Invoice generation jobs dispatched.');
    }
}
