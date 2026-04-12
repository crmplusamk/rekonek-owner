<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('app:invoice-generate-renew')->dailyAt('00:00');
        $schedule->command('app:send-free-package-reminder')->dailyAt('08:00');
        $schedule->command('app:send-subscriber-expiry-reminder')->dailyAt('08:10');
        $schedule->command('app:send-expired-subscription-notification')->dailyAt('04:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
