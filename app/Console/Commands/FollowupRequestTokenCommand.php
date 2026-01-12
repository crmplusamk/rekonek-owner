<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Traits\AccessLogFollowupTrait;

class FollowupRequestTokenCommand extends Command
{
    use AccessLogFollowupTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'followup:request-token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create followup tasks for customers stuck at request_token stage for 24+ hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing customers stuck at request_token stage...');

        $nextStages = [
            'token_verified',
            'registration_success', 
            'email_verified_success',
            'first_login_success',
            'onboarding_completed',
            'trial_activated'
        ];

        $processedCount = $this->processStuckCustomers(
            'request_token',
            $nextStages,
            'Follow up Token Request',
            'Customer memerlukan bantuan untuk melanjutkan verifikasi token setelah request token. Silakan hubungi customer untuk memberikan panduan verifikasi token.'
        );

        $this->info("Processed {$processedCount} customers stuck at request_token stage.");
        return Command::SUCCESS;
    }
}