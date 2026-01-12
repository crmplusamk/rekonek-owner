<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Traits\AccessLogFollowupTrait;

class FollowupEmailVerifiedCommand extends Command
{
    use AccessLogFollowupTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'followup:email-verified';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create followup tasks for customers stuck at email_verified_success stage for 24+ hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing customers stuck at email_verified_success stage...');

        $nextStages = [
            'first_login_success',
            'onboarding_completed',
            'trial_activated'
        ];

        $processedCount = $this->processStuckCustomers(
            'email_verified_success',
            $nextStages,
            'Follow up Login Pertama',
            'Email customer sudah terverifikasi namun belum melakukan login pertama. Silakan hubungi customer untuk memandu proses login.'
        );

        $this->info("Processed {$processedCount} customers stuck at email_verified_success stage.");
        return Command::SUCCESS;
    }
}