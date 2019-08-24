<?php namespace App\Console\Commands;

use DB;
use Log;
use Mail;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class Notify extends Command {

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
        public function handle()
        {
                $reply_email = config('app.noreplyemail');
                $admin_email = config('app.admin');
                $site = config('app.app_name');
                $url = config('app.url');

                // get each user
                $users = User::orderBy('name','ASC')->get();
                $show_count = 12;

                // cycle through all the users
                foreach ($users as $user)
                {
                        $interests = array();
                        $seriesList = array();

                        // build an array of events that are today based on what the user follows
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
                                if (count($tag->todaysEvents()) > 0)
                                {
                                    $interests[$tag->name] = $tag->todaysEvents();
                                }
                            }
                        }

                        // build an array of series that the user is following
                        if ($series = $user->getSeriesFollowing())
                        {
                            foreach ($series as $s)
                            {
                                // if the series does not have NO SCHEDULE AND CANCELLED AT IS NULL
                                if ($s->occurrenceType->name !== 'No Schedule' && (NULL === $s->cancelled_at)) {
                                    // add matches to list
                                    $next_date = $s->nextOccurrenceDate()->format('Y-m-d');

                                    // today's date is the next series date
                                    if ($next_date === Carbon::now()->format('Y-m-d'))
                                    {
                                        $seriesList[] = $s;
                                    }
                                }

                            }
                        }

                        // get the next x events they are attending
                        $events = $user->getAttendingToday()->take($show_count);

                        // if there are more than 0 events
                        if ((NULL !== $events && $events->count() > 0) || (NULL !== $seriesList && count($seriesList) > 0) || (NULL !== $interests && count($interests) > 0))
                        {
                                // send an email containing that list
                                Mail::send('emails.daily-events', ['user' => $user, 'events' => $events, 'seriesList' => $seriesList, 'interests' => $interests, 'admin_email' => $admin_email, 'url' => $url, 'site' => $site], function ($m) use ($user,  $admin_email, $reply_email, $site) {
                                        $m->from($reply_email, $site);

                                        $dt = Carbon::now();
                                        $m->to($user->email, $user->name)
                                            ->bcc($admin_email)
                                            ->subject($site.': Daily Reminder - '.$dt->format('l F jS Y'));
                                });

                                // log that the weekly email was sent
                                Log::info('Daily events email was sent to '.$user->name.' at '.$user->email.'.');
                        } else {
                                // log that no email was sent
                                Log::info('No daily events email was sent to '.$user->name.' at '.$user->email.'.');
                        }


                };


        }

}
