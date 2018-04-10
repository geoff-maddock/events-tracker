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
		'App\Console\Commands\Notify',
	];

	/**
	 * Define the application's command schedule.
	 *
	 * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
	 * @return void
	 */
	protected function schedule(Schedule $schedule)
	{
	    // test schedule
		/*
		$schedule->call(function() {
			Log::info('test.');
		})->everyMinute();
		*/

		// schedule weekly email of events each user is attending
		$schedule->call(function () {

			$reply_email = config('app.noreplyemail');
			$site = config('app.app_name');
			$url = config('app.url');
            $admin_email = config('app.admin');

			// get each user
			$users = User::orderBy('name','ASC')->get();
			$show_count = 12;

			// cycle through all the users
			foreach ($users as $user)
			{
                $interests = array();

                // build an array of events that are in the future based on what the user follows
                if ($entities = $user->getEntitiesFollowing())
                {
                    foreach ($entities as $entity)
                    {
                        if (count($entity->todaysEvents()) > 0)
                        {
                            $interests[$entity->name] = $entity->todaysEvents();
                        }
                    }
                }
                // build an array of future events based on tags the user follows
                if ($tags = $user->getTagsFollowing())
                {
                    foreach ($tags as $tag)
                    {
                        if (count($tag->futureEvents()) > 0)
                        {
                            $interests[$tag->name] = $tag->futureEvents();
                        }
                    }
                }

				// get the next x events they are attending
				if ($events = $user->getAttendingFuture()->take($show_count) )
				{
					// if there are more than 0 events
					if ($events->count() > 0)
					{
						// send an email containing that list
						Mail::send('emails.weekly-events',
                            ['user' => $user, 'interests' => $interests, 'events' => $events, 'url' => $url, 'site' => $site],
                            function ($m) use ($user, $admin_email, $reply_email, $site) {
							$m->from($reply_email, $site);

							$dt = Carbon::now();
							$m->to($user->email, $user->name)
                                ->bcc($admin_email)
                                ->subject($site.': Weekly Reminder - '.$dt->format('l F jS Y'));
						});

						// log that the weekly email was sent
						Log::info('Weekly events email was sent to '.$user->name.' at '.$user->email.'.');
					} else {
						// log that no email was sent
						Log::info('No weekly events email was sent to '.$user->name.' at '.$user->email.'.');
					};
				} else {
					// log that no email was sent
					Log::info('No weekly events email was sent to '.$user->name.' at '.$user->email.'.');
				};
			
			};
		})->weekly()->mondays()->timezone('America/New_York')->at('5:00');

		// schedule daily email of events each user is attending today
		$schedule->command('notify')->daily()->timezone('America/New_York')->at('9:00');

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
