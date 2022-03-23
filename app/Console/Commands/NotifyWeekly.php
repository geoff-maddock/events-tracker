<?php

namespace App\Console\Commands;

use App\Mail\WeeklyUpdate;
use App\Models\User;
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
        $show_count = 21;

        // cycle through all the users
        foreach ($users as $user) {
            // if the user does not have a profile, continue
            if ($user->profile == null) {
                continue;
            }

            // if the user does not have this setting, continue
            if ($user->profile->setting_weekly_update !== 1) {
                continue;
            }

            $interests = [];
            $seriesList = [];
            $entityEvents = [];
            $tagEvents = [];
            $attendingIdList = [];

            // get the events they are attending in the next two weeks
            $attendingEvents = $user->getAttendingFuture()->where('start_at', '<=', Carbon::now()->addDays(14));
            foreach ($attendingEvents as $event) {
                $attendingIdList[] = $event->id;
            }

            // build an array of events that are upcoming based on what the user follows
            $entities = $user->getEntitiesFollowing();
            if (count($entities) > 0) {
                foreach ($entities as $entity) {
                    $entityEvents = [];
                    // get the future events for each followed entity
                    if (count($entity->futureEvents()) > 0) {
                        foreach ($entity->futureEvents() as $futureEvent) {
                            if (!in_array($futureEvent->id, $attendingIdList)) {
                                $entityEvents[] = $futureEvent;
                                $attendingIdList[] = $futureEvent->id;
                            }
                        }
                        if (count($entityEvents) > 0) {
                            $interests[$entity->name] = $entityEvents;
                        }
                    }
                }
            }
            // build an array of future events based on tags the user follows
            $tags = $user->getTagsFollowing();
            if (count($tags) > 0) {
                foreach ($tags as $tag) {
                    $tagEvents = [];
                    // get the future events for each followed tag
                    if (count($tag->futureEvents()) > 0) {
                        foreach ($tag->futureEvents() as $futureEvent) {
                            if (!in_array($futureEvent->id, $attendingIdList)) {
                                $tagEvents[] = $futureEvent;
                                $attendingIdList[] = $futureEvent->id;
                            }
                        }
                        if (count($tagEvents) > 0) {
                            $interests[$tag->name] = $tagEvents;
                        }
                    }
                }
            }

            // build an array of series that the user is following
            $series = $user->getSeriesFollowing();
            if (count($series) > 0) {
                foreach ($series as $s) {
                    // if the series does not have NO SCHEDULE AND CANCELLED AT IS NULL
                    if ($s->occurrenceType->name !== 'No Schedule' && (null === $s->cancelled_at)) {
                        // add matches to list
                        $seriesList[] = $s;
                    }
                }
            }

            // if there are more than 0 events
            if ((null !== $attendingEvents && $attendingEvents->count() > 0) || (null !== $seriesList && count($seriesList) > 0) || (null !== $interests && count($interests) > 0)) {
                // send an email containing that list
                Mail::to($user->email)
                    ->send(new WeeklyUpdate($url, $site, $admin_email, $reply_email, $user, $attendingEvents, $seriesList, $interests));

                // log that the weekly email was sent
                Log::info('Weekly update email was sent to '.$user->name.' at '.$user->email.'.');
            } else {
                // log that no email was sent
                Log::info('No weekly update email was sent to '.$user->name.' at '.$user->email.'.');
            }
        }
    }
}
