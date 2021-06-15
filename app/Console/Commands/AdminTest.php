<?php

namespace App\Console\Commands;

use App\Mail\AdminMailer;
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

        // test using the admin mailer class
        Mail::to($admin_email)
            ->send(new AdminMailer($url, $site, $admin_email, $reply_email));

        // log that the weekly email was sent
        Log::info('Admin test email was sent to ' . $admin_email);
    }
}
