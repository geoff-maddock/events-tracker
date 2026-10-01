<?php

namespace App\Console\Commands;

use App\Mail\WeeklyUpdate;
use App\Models\Activity;
use App\Models\User;
use App\Services\DigestBuilder;
use App\Services\EntityStats;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyWeekly extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'notifyWeekly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and send specified weekly notification(s).';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(EntityStats $stats, DigestBuilder $digests)
    {
        $reply_email = config('app.noreplyemail');
        $admin_email = config('app.admin');
        $site = config('app.app_name');
        $url = config('app.url');

        // get each user
        $users = User::orderBy('name', 'ASC')->get();

        foreach ($users as $user) {
            // if the user does not have this setting, continue
            if ($user->profile == null || $user->profile->setting_weekly_update !== 1) {
                continue;
            }

            $digest = $digests->weekly($user);

            if ($digest->isEmpty()) {
                Log::info('No weekly update email was sent to '.$user->name.' at '.$user->email.'.');

                continue;
            }

            Mail::to($user->email)
                ->send(new WeeklyUpdate($url, $site, $admin_email, $reply_email, $user, $digest->attending, $digest->series, $digest->interests));

            // count this recipient toward each event's digest reach for the owner dashboard
            $stats->recordEventReach($digest->eventIds());

            // add login to log
            Activity::log($user, $user, 15, "Sent weekly notification email");

            Log::info('Weekly update email was sent to '.$user->name.' at '.$user->email.'.');
        }
    }
}
