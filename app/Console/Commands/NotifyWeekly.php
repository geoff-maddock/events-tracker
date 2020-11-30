<?php

namespace App\Console\Commands;

use App\User;
use Carbon\Carbon;
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
    public function handle()
    {
        $reply_email = config('app.noreplyemail');
        $admin_email = config('app.admin');
        $site = config('app.app_name');
        $url = config('app.url');

        // get each user
        $users = User::orderBy('name', 'ASC')->get();
        $show_count = 12;

        // cycle through all the users
        foreach ($users as $user) {
            // get the next x events they are attending
            if ($events = $user->getAttendingToday()->take($show_count)) {
                // if there are more than 0 events
                if ($events->count() > 0) {
                    // send an email containing that list
                    Mail::send('emails.daily-events', ['user' => $user, 'events' => $events, 'admin_email' => $admin_email, 'url' => $url], function ($m) use ($user, $admin_email, $reply_email, $site) {
                        $m->from($reply_email, $site);

                        $dt = Carbon::now();
                        $m->to($user->email, $user->name)
                                                    ->bcc($admin_email)
                                                    ->subject($site . ': Daily Reminder - ' . $dt->format('l F jS Y'));
                    });

                    // log that the weekly email was sent
                    Log::info('Daily events email was sent to ' . $user->name . ' at ' . $user->email . '.');
                } else {
                    // log that no email was sent
                    Log::info('No daily events email was sent to ' . $user->name . ' at ' . $user->email . '.');
                }
            } else {
                // log that no email was sent
                Log::info('No daily events email was sent to ' . $user->name . ' at ' . $user->email . '.');
            }
        }
    }
}
