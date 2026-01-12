<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Traits\AccessLogFollowupTrait;

class FollowupOnboardingCommand extends Command
{
    use AccessLogFollowupTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'followup:onboarding';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create followup tasks for customers stuck at onboarding_completed stage for 24+ hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing customers stuck at onboarding_completed stage...');

        $nextStages = [
            'trial_activated'
        ];

        $processedCount = $this->processStuckCustomers(
            'onboarding_completed',
            $nextStages,
            'Follow up Aktivasi Trial',
            'Customer sudah menyelesaikan onboarding namun belum mengaktifkan trial. Silakan hubungi customer untuk memandu aktivasi trial gratis.'
        );

        $this->info("Processed {$processedCount} customers stuck at onboarding_completed stage.");
        return Command::SUCCESS;
    }
}