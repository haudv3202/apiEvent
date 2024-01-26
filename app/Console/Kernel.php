<?php

namespace App\Console;

use App\Console\Commands\UpdateEventStatus;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        UpdateEventStatus::class
    ];
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
//        everyThirtyMinutes
        $schedule->command('auto:send-mail')->everyMinute();
//        $schedule->command('app:calendar-notifition-event')->hourly();
        $schedule->command('app:update-event-status')->everyFiveMinutes();
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
