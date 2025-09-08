<?php
/*
 * File name: Kernel.php
 * Last modified: 2024.04.18 at 17:21:43
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

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
        //
        Commands\ProcessMissedRemindersCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        // $schedule->command('inspire')->everyMinute();
        $schedule->command('bookings:process-missed-reminders')
                //  ->hourly()
                //  ->withoutOverlapping()
                ->everyMinute()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/reminders.log'));

        // Nettoyage des jobs échoués quotidiennement (optionnel)
        $schedule->command('queue:flush')
                 ->daily()
                 ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
