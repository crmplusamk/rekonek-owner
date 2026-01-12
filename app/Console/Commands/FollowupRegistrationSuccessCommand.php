<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Traits\AccessLogFollowupTrait;

class FollowupRegistrationSuccessCommand extends Command
{
    use AccessLogFollowupTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'followup:registration-success';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create followup tasks for customers stuck at registration_success stage for 24+ hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing customers stuck at registration_success stage...');

        $nextStages = [
            'email_verified_success',
            'first_login_success',
            'onboarding_completed',
            'trial_activated'
        ];

        $processedCount = $this->processStuckCustomers(
            'registration_success',
            $nextStages,
            'Follow up Verifikasi Email',
            'Customer sudah berhasil registrasi namun belum melakukan verifikasi email. Silakan hubungi customer untuk memandu proses verifikasi email.'
        );

        $this->info("Processed {$processedCount} customers stuck at registration_success stage.");
        return Command::SUCCESS;
    }
}