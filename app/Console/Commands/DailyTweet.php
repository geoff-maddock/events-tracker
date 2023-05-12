<?php

namespace App\Console\Commands;

use App\Mail\DailyReminder;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Notifications\EventPublished;
use Log;
use Mail;

class DailyTweet extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'dailyTweet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tweet all events that happen today';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // get all the events happening today
        $events = Event::today()->leftJoin('event_types', 'events.event_type_id', '=', 'event_types.id')->select('events.*')->get();

        // cycle through all the events
        foreach ($events as $event) {

            // add a twitter notification
            $event->notify(new EventPublished());

            // unlink the temp file
            if ($photo = $event->getPrimaryPhoto()) {
                unlink(storage_path().'/app/public/photos/temp/'.$photo->name);
            };
        };
        
        // log that the daily events tweet were sent
        Log::info('Daily tweets were sent for '.count($events).' event(s).');
    }
}
