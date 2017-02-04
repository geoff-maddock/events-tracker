<?php namespace App\Console;

use DB;
use Log;
use Mail;
use App\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel {

	/**
	 * The Artisan commands provided by your application.
	 *
	 * @var array
	 */
	protected $commands = [
		'App\Console\Commands\Inspire',
	];

	/**
	 * Define the application's command schedule.
	 *
	 * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
	 * @return void
	 */
	protected function schedule(Schedule $schedule)
	{
		$schedule->command('inspire')
				 ->hourly();

		// schedule daily email of events each user is attending
		$schedule->call(function () {
		// get each user
		// get the events they are attending
		// send an email containing that list
			$users = User::orderBy('name','ASC')->get();

			// cycle through all the users
			foreach ($users as $user)
			{
				$events = $user->getAttendingFuture()->take(100);

				Mail::send('emails.daily-events', ['user' => $user, 'events' => $events], function ($m) use ($user, $events) {
					$m->from('admin@events.cutupsmethod.com','Event Repo');

					$m->to($user->email, $user->name)->subject('Event Repo: Daily Events Reminder');
				});
			
			};
		})->dailyAt('6:00');

	}

}
