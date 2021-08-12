<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Handles cleanup of users who have not yet verified their email
 */

class UserCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'userCleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Handles cleanup of users who have not yet verified their email';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // number of days that users have to verify their email before their user is deleted
        $days_to_verify = 7;

        // get all the users who have not verified their email within the number of days granted and delete them
        $users = User::whereNull('email_verified_at')->where('created_at', '<', Carbon::now()->subDays($days_to_verify))->orderBy('name', 'ASC')->get();
        foreach ($users as $user) {
            $this->info('The user ' . $user->email . ' was deleted.');
            $user->delete();
        }

        $days_for_reminder = 3;

        // for all users who have not verified their email after the reminder days, resend the verification email
        $users = User::whereNull('email_verified_at')->where('created_at', '<', Carbon::now()->subDays($days_for_reminder))->orderBy('name', 'ASC')->get();
        foreach ($users as $user) {
            $user->sendEmailVerificationNotification();
            $this->info('The user ' . $user->email . ' was resent a verification email.');
        }
    }
}
