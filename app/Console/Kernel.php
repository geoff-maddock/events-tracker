<?php namespace App\Console;

use DB;
use Log;
use Mail;
use App\User;
use Carbon\Carbon;
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

		// schedule weekly email of events each user is attending
		$schedule->call(function () {
			// get each user
			$users = User::orderBy('name','ASC')->get();
			$show_count = 12;

			// cycle through all the users
			foreach ($users as $user)
			{
				// get the next x events they are attending
				if ($events = $user->getAttendingFuture()->take($show_count) )
				{
					// if there are mroe than 0 events
					if ($events->count() > 0)
					{
						// send an email containing that list
						Mail::send('emails.weekly-events', ['user' => $user, 'events' => $events], function ($m) use ($user, $events) {
							$m->from('admin@events.cutupsmethod.com','Event Repo');

							$dt = Carbon::now();
							$m->to($user->email, $user->name)->subject('Event Repo: Weekly Reminder - '.$dt->format('l F jS Y'));
						});
					};
				};
			
			};
		})->weekly()->mondays()->timezone('America/New_York')->at('5:00');

		// schedule daily email of events each user is attending today
		$schedule->call(function () {
			// get each user
			$users = User::orderBy('name','ASC')->get();
			$show_count = 12;

			// cycle through all the users
			foreach ($users as $user)
			{
				// get the next x events they are attending
				if ($events = $user->getAttendingToday()->take($show_count))
				{
					// if there are mroe than 0 events
					if ($events->count() > 0)
					{
						// send an email containing that list
						Mail::send('emails.weekly-events', ['user' => $user, 'events' => $events], function ($m) use ($user, $events) {
							$m->from('admin@events.cutupsmethod.com','Event Repo');
							
							$dt = Carbon::now();
							$m->to($user->email, $user->name)->subject('Event Repo: Daily Reminder - '.$dt->format('l F jS Y'));
						});
					};
				};
			
			};
		})->daily()->timezone('America/New_York')->at('6:00');


	}

}
