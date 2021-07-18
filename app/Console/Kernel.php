<?php

namespace App\Console;

use App\Console\Commands\AdminTest;
use App\Console\Commands\Notify;
use App\Console\Commands\NotifyWeekly;
use App\Console\Commands\UserCleanup;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Notify::class,
        AdminTest::class,
        NotifyWeekly::class,
        UserCleanup::class
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // WEEKLY
        // schedule weekly update email of events each user is attending or interested in
        $schedule->command('notifyWeekly')->weekly()->mondays()->timezone('America/New_York')->at('5:00');

        // DAILY
        // schedule daily user cleanup process
        $schedule->command('userCleanup')->daily()->timezone('America/New_York')->at('07:00');

        // schedule daily email of events each user is attending today
        $schedule->command('notify')->daily()->timezone('America/New_York')->at('09:00');

        // send a test email every day at noon
        $schedule->command('adminTest')->daily()->timezone('America/New_York')->at('12:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
