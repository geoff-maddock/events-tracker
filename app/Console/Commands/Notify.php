<?php

namespace App\Console\Commands;

use App\Mail\DailyReminder;
use App\Models\Activity;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
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
            $interests = [];
            $seriesList = [];
            $entityEvents = [];
            $tagEvents = [];
            $collectedIdList = [];

            // if the user does not have this setting, continue
            if ($user->profile == null || $user->profile->setting_daily_update !== 1) {
                continue;
            }

            // get the next x events they are attending
            $attendingEvents = $user->getAttendingToday()->take($show_count);
            foreach ($attendingEvents as $event) {
                /** @var \App\Models\Event $event */
                $collectedIdList[] = $event->id;
            }

            // build an array of events that are today based on what the user follows
            $entities = $user->getEntitiesFollowing();
            if (count($entities) > 0) {
                foreach ($entities as $entity) {
                    /** @var \App\Models\Entity $entity */
                    $entityEvents = [];
                    if (count($entity->todaysEvents()) > 0) {
                        foreach ($entity->todaysEvents() as $todaysEvent) {
                            /** @var \App\Models\Event $todaysEvent */
                            if (!in_array($todaysEvent->id, $collectedIdList)) {
                                $entityEvents[] = $todaysEvent;
                                $collectedIdList[] = $todaysEvent->id;
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
                    /** @var \App\Models\Tag $tag */
                    $tagEvents = [];
                    if (count($tag->todaysEvents()) > 0) {
                        foreach ($tag->todaysEvents() as $todaysEvent) {
                            /** @var \App\Models\Event $todaysEvent */
                            if (!in_array($todaysEvent->id, $collectedIdList)) {
                                $tagEvents[] = $todaysEvent;
                                $collectedIdList[] = $todaysEvent->id;
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
                    /** @var \App\Models\Series $s */
                    // if the series does not have NO SCHEDULE AND CANCELLED AT IS NULL
                    if ($s->occurrenceType->name !== 'No Schedule' && (null === $s->cancelled_at)) {
                        // add matches to list
                        $next_date = $s->nextOccurrenceDate()->format('Y-m-d');

                        // today's date is the next series date
                        if ($next_date === Carbon::now()->format('Y-m-d')) {
                            $seriesList[] = $s;
                        }
                    }
                }
            }

            // if there are more than 0 events
            if ((null !== $attendingEvents && $attendingEvents->count() > 0) || (null !== $seriesList && count($seriesList) > 0) || (null !== $interests && count($interests) > 0)) {
                // send an email containing that list
                Mail::to($user->email)
                    ->send(new DailyReminder($url, $site, $admin_email, $reply_email, $user, $attendingEvents, $seriesList, $interests));

                // log that the weekly email was sent
                Log::info('Daily events email was sent to '.$user->name.' at '.$user->email.'.');
            } else {
                // log that no email was sent
                Log::info('No daily events email was sent to '.$user->name.' at '.$user->email.'.');
            }
        }
    }
}
