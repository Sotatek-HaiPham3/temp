<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('game:order')->daily();
//        $schedule->command('videos-counter:run')->hourly();
        $schedule->command('delete-account:run')->daily();
        $schedule->command('delete-community:run')->everyMinute();
        $schedule->command('passport:purge')->hourly(); // Purge revoked and expired tokens and auth codes
        // $schedule->command('intro-task-reminder:run')->monthly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
