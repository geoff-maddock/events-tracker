<?php

namespace App\Console;

use App\Console\Commands\AdminActivitySummary;
use App\Console\Commands\AdminTest;
use App\Console\Commands\AutomateInstagramPosts;
use App\Console\Commands\CleanupExports;
use App\Console\Commands\CreateSeriesEvents;
use App\Console\Commands\InitializeEventShares;
use App\Console\Commands\Notify;
use App\Console\Commands\NotifyWeekly;
use App\Console\Commands\UserCleanup;
use App\Models\User;
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
        Notify::class,
        AdminTest::class,
        AdminActivitySummary::class,
        NotifyWeekly::class,
        UserCleanup::class,
        AutomateInstagramPosts::class,
        InitializeEventShares::class,
        CleanupExports::class,
        CreateSeriesEvents::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // WEEKLY
        // schedule weekly update email of events each user is attending or interested in
        $schedule->command('notifyWeekly')->weekly()->mondays()->timezone('America/New_York')->at('5:00');

        // schedule weekly admin activity summary (7 days)
        $schedule->command('admin:activity-summary 7')->weekly()->mondays()->timezone('America/New_York')->at('6:00');

        // MONTHLY
        // schedule monthly admin activity summary (30 days) on the first of each month
        $schedule->command('admin:activity-summary 30')->monthly()->timezone('America/New_York')->at('6:00');

        // DAILY
        // schedule daily user cleanup process
        $schedule->command('userCleanup')->daily()->timezone('America/New_York')->at('07:00');

        // schedule daily cleanup of old export files
        $schedule->command('cleanup:exports')->daily()->timezone('America/New_York')->at('03:00');

        // schedule daily creation of next series events
        // DISABLED - need to reconsider if we want this
        // $schedule->command('series:create-events')->daily()->timezone('America/New_York')->at('04:00');

        // schedule daily email of events each user is attending today
        $schedule->command('notify')->daily()->timezone('America/New_York')->at('09:00');

        // send a test email every day at noon
        $schedule->command('adminTest')->daily()->timezone('America/New_York')->at('12:00');

        // send event tweets every day at 8AM
        if (config('app.twitter_consumer_key') !== '999') {
            $schedule->command('dailyTweet')->daily()->timezone('America/New_York')->at('08:00');
        }

        // update the sitemap once per week
        $schedule->command('sitemap:generate')->weekly()->sundays()->timezone('America/New_York')->at('5:00');

        // EVERY 30 MINUTES
        // automate Instagram posts for events
        // runs every 30 minutes, Monday through Friday, from 9 AM to 5 PM (Eastern Time)
        $schedule->command('instagram:autopost')
            ->everyThirtyMinutes()
            ->timezone('America/New_York')
            ->between('9:00', '17:00')
            ->weekdays();
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
