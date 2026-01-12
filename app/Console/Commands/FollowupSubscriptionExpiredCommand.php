<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\AccessLog;
use App\Traits\AccessLogFollowupTrait;

class FollowupSubscriptionExpiredCommand extends Command
{
    use AccessLogFollowupTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'followup:subscription-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create followup tasks for expired subscriptions and log access';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing expired subscriptions...');

        try {
            // Get expired subscriptions that are still marked as active
            $expiredSubscriptions = DB::table('subscription_packages as sp')
                ->join('contacts as c', 'sp.customer_id', '=', 'c.id')
                ->join('packages as p', 'sp.package_id', '=', 'p.id')
                ->where('sp.expired_at', '<', Carbon::now())
                ->where('sp.is_active', true)
                ->select(
                    'sp.id as subscription_id',
                    'sp.customer_id',
                    'sp.package_id',
                    'sp.code as subscription_code',
                    'sp.expired_at',
                    'sp.company_id',
                    'c.name as customer_name',
                    'c.email as customer_email',
                    'c.phone as customer_phone',
                    'p.name as package_name'
                )
                ->get();

            if ($expiredSubscriptions->isEmpty()) {
                $this->info('No expired subscriptions found.');
                return Command::SUCCESS;
            }

            $processedCount = 0;

            foreach ($expiredSubscriptions as $subscription) {
                DB::beginTransaction();
                
                try {
                    // Delete existing access log if exists
                    AccessLog::where('category', 'subscription')
                        ->where('progress', 'subscription_expired')
                        ->where('company_id', $subscription->company_id)
                        ->where('number', $subscription->customer_phone)
                        ->whereDate('created_at', Carbon::today())
                        ->delete();

                    // Create new Access Log
                    AccessLog::create([
                        'category' => 'subscription',
                        'email' => $subscription->customer_email ?? null,
                        'number' => $subscription->customer_phone ?? null,
                        'company_id' => $subscription->company_id ?? null,
                        'method' => 'CRON',
                        'endpoint' => 'followup:subscription-expired',
                        'status_code' => 200,
                        'request_data' => [
                            'subscription_id' => $subscription->subscription_id,
                            'subscription_code' => $subscription->subscription_code,
                            'package_name' => $subscription->package_name,
                            'expired_at' => $subscription->expired_at,
                        ],
                        'action' => 'subscription_check',
                        'activity_type' => 'subscription_expired',
                        'progress' => 'subscription_expired',
                    ]);

                    // Create task and followup in client database
                    $this->createSubscriptionFollowupTask($subscription);

                    // Update subscription status to inactive
                    DB::table('subscription_packages')
                        ->where('id', $subscription->subscription_id)
                        ->update(['is_active' => false]);

                    DB::commit();
                    $processedCount++;
                    
                    $this->info("Processed expired subscription: {$subscription->subscription_code} for {$subscription->customer_name}");

                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("Error processing subscription {$subscription->subscription_code}: " . $e->getMessage());
                }
            }

            $this->info("Processed {$processedCount} expired subscriptions.");
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Command error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Create followup task in client database for expired subscription
     */
    protected function createSubscriptionFollowupTask($subscription)
    {
        $salesCompanies = $this->getSalesCompanies();

        foreach ($salesCompanies as $companyId) {
            $contact = $this->getContactByNumber($subscription->customer_phone, $companyId);

            if (!$contact) {
                continue;
            }

            $admin = $this->getAdminUser($companyId);

            if (!$admin) {
                continue;
            }

            // Create task
            $now = Carbon::now();
            $expiredDate = Carbon::parse($subscription->expired_at)->format('d M Y');

            $taskId = $this->createTask([
                'name' => 'Follow up Langganan Expired',
                'description' => "Langganan customer dengan kode {$subscription->subscription_code} untuk paket {$subscription->package_name} telah expired pada tanggal {$expiredDate}. Silakan hubungi customer untuk renewal langganan. (Email: {$subscription->customer_email})",
                'priority' => 'high',
                'assign_to_id' => $admin->id,
                'contact_id' => $contact->id,
                'company_id' => $companyId,
                'created_by' => $admin->id
            ]);

            // Create followup with custom message
            $followupMessage = "Halo *{$subscription->customer_name}*! 👋\n\n".
                             "Kami ingin menginformasikan bahwa langganan Anda untuk paket *{$subscription->package_name}* telah berakhir pada *{$expiredDate}*.\n\n".
                             "Agar dapat terus menikmati layanan Retalk, silakan lakukan perpanjangan langganan. Ada penawaran menarik menunggu Anda! 🎉\n\n".
                             "Hubungi kami untuk informasi lebih lanjut. Terima kasih! 😊";

            $followupId = $this->createFollowup([
                'contact_id' => $contact->id,
                'task_id' => $taskId,
                'message' => $followupMessage,
                'created_by' => $admin->id
            ]);

            // Update task with followup ID
            $this->updateTaskWithFollowupId($taskId, $followupId);

            $this->info("Created followup task for {$subscription->customer_name}");
            break; // Exit after creating task in first matching company
        }
    }
}
