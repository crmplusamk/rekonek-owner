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
        $schedule->command('app:send-subscriber-expiry-reminder')->dailyAt('08:10');

        // Grace period pipeline. Pre-expiry (H-3) + post-expiry (H+1..H+31).
        // Staggered timings: pre-expiry → enter → drip → terminate, pagi hari,
        // tidak berebut DB/queue antar command.
        $schedule->command('app:send-pre-expiry-reminder')->dailyAt('05:00');
        $schedule->command('app:enter-grace')->dailyAt('06:00');
        $schedule->command('app:drip-grace')->dailyAt('09:00');
        $schedule->command('app:terminate-grace')->dailyAt('11:00');
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
