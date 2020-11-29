<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AdminTest extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'adminTest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test notification to the admin user.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $reply_email = config('app.noreplyemail');
        $admin_email = config('app.admin');
        $site = config('app.app_name');
        $url = config('app.url');

        // send an email containing that list
        Mail::send('emails.admin-test', ['admin_email' => $admin_email, 'site' => $site, 'reply_email' => $reply_email, 'url' => $url], static function ($m) use ($admin_email, $site, $reply_email) {
            $m->from($reply_email, $site);

            $dt = Carbon::now();
            $m->to($admin_email, $site.' Admin User')
                ->bcc($admin_email)
                ->subject($site.': Admin Tester - '.$dt->format('l F jS Y'));
        });

        // log that the weekly email was sent
        Log::info('Admin test email was sent to '.$admin_email);
    }
}
