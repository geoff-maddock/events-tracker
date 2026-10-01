<?php

namespace App\Console\Commands;

use App\Mail\DailyReminder;
use App\Models\User;
use App\Services\DigestBuilder;
use Illuminate\Console\Command;
use Log;
use Mail;

class Notify extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'notify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and send specified notification(s).';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(DigestBuilder $digests)
    {
        $reply_email = config('app.noreplyemail');
        $admin_email = config('app.admin');
        $site = config('app.app_name');
        $url = config('app.url');

        // get each user
        $users = User::orderBy('name', 'ASC')->get();

        foreach ($users as $user) {
            // if the user does not have this setting, continue
            if ($user->profile == null || $user->profile->setting_daily_update !== 1) {
                continue;
            }

            $digest = $digests->daily($user);

            if ($digest->isEmpty()) {
                Log::info('No daily events email was sent to '.$user->name.' at '.$user->email.'.');

                continue;
            }

            Mail::to($user->email)
                ->send(new DailyReminder($url, $site, $admin_email, $reply_email, $user, $digest->attending, $digest->series, $digest->interests));

            Log::info('Daily events email was sent to '.$user->name.' at '.$user->email.'.');
        }
    }
}
