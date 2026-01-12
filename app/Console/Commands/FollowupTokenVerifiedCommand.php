<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Traits\AccessLogFollowupTrait;

class FollowupTokenVerifiedCommand extends Command
{
    use AccessLogFollowupTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'followup:token-verified';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create followup tasks for customers stuck at token_verified stage for 24+ hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing customers stuck at token_verified stage...');

        $nextStages = [
            'registration_success', 
            'email_verified_success',
            'first_login_success',
            'onboarding_completed',
            'trial_activated'
        ];

        $processedCount = $this->processStuckCustomers(
            'token_verified',
            $nextStages,
            'Follow up Registrasi',
            'Token customer sudah terverifikasi namun belum melanjutkan ke registrasi. Silakan hubungi customer untuk memandu proses registrasi akun.'
        );

        $this->info("Processed {$processedCount} customers stuck at token_verified stage.");
        return Command::SUCCESS;
    }
}