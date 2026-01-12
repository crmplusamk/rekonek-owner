<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FollowupMasterCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'followup:all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all access log followup commands to check all stages';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Running all access log followup commands...');
        
        $commands = [
            'followup:request-token',
            'followup:token-verified',
            'followup:registration-success',
            'followup:email-verified',
            'followup:first-login',
            'followup:onboarding',
            'followup:subscription-expired'
        ];

        foreach ($commands as $command) {
            $this->info("Executing: {$command}");
            $this->call($command);
            $this->newLine();
        }

        $this->info('All followup commands completed!');
        return Command::SUCCESS;
    }
}