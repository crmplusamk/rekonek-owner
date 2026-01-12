<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Traits\AccessLogFollowupTrait;

class FollowupFirstLoginCommand extends Command
{
    use AccessLogFollowupTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'followup:first-login';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create followup tasks for customers stuck at first_login_success stage for 24+ hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing customers stuck at first_login_success stage...');

        $nextStages = [
            'onboarding_completed',
            'trial_activated'
        ];

        $processedCount = $this->processStuckCustomers(
            'first_login_success',
            $nextStages,
            'Follow up Onboarding',
            'Customer sudah berhasil login namun belum menyelesaikan onboarding. Silakan hubungi customer untuk memandu proses onboarding.'
        );

        $this->info("Processed {$processedCount} customers stuck at first_login_success stage.");
        return Command::SUCCESS;
    }
}