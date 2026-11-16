<?php

namespace App\Services\Calendar;

use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\Entity\Event as iCalEvent;
use Eluceo\iCal\Domain\ValueObject\Organizer;
use Eluceo\iCal\Domain\ValueObject\Uri;
use Eluceo\iCal\Domain\ValueObject\EmailAddress;
use Eluceo\iCal\Domain\ValueObject\Location;
use Eluceo\iCal\Domain\ValueObject\UniqueIdentifier;
use Eluceo\iCal\Presentation\Factory\CalendarFactory;
use Eluceo\iCal\Domain\ValueObject\TimeSpan;
use Eluceo\iCal\Domain\ValueObject\DateTime;
use Eluceo\iCal\Domain\ValueObject\Attachment;
use Eluceo\iCal\Domain\Entity\TimeZone;
use App\Models\Event;
use App\Services\EventTime;
use DateTimeZone as PhpDateTimeZone;
use Storage;
use DateTimeImmutable;
use DateInterval;


/**
 * Builds and exports ical calendar
 */
class ICalBuilder
{

    /**
     * Build iCal calendar from events
     * @param string $calendarName Name of the calendar file
     * @param $events Array of Event models
     * @return string The iCal formatted calendar
     */
    public function buildCalendar(string $calendarName, $events): string
    {
        // create a calendar object
        $vCalendar = new Calendar([]);

        // Deliberately no date_default_timezone_set() here. It used to set the
        // process default for the rest of the request, so unrelated code read
        // dates differently depending on whether anyone had downloaded a
        // calendar. EventTime carries the zone explicitly instead.

        // specify local time zone
        $phpDateTimeZone = new PhpDateTimeZone(EventTime::ZONE);
        $utcTimeZone = new PhpDateTimeZone('UTC');

        $oldestTime = new DateTimeImmutable('now', $phpDateTimeZone);
        $latestTime = new DateTimeImmutable('now', $phpDateTimeZone);

        // create an empty array to store the events
        $vEvents = [];

        // loop over events
        foreach ($events as $event) {
            // use the route for the event as the unique id
            $uniqueId = route('events.show', ['event' => $event]);

            // update oldest time
            if ($event->updated_at < $oldestTime) {
                $oldestTime = $event->updated_at;
            }

            // One VEVENT per date: a short run's showings each get their own
            // (#2375). The first date keeps the event's UID, so the entry a
            // subscribed calendar already has stays the same item; each later
            // showing's UID adds its start, which is stable across edits.
            foreach ($event->occurrences() as $occurrence) {
                $uid = $occurrence->start_at->equalTo($event->start_at)
                    ? $uniqueId
                    : $uniqueId.'#'.$occurrence->start_at->format('Ymd\THi');

                // EventTime reinterprets the naive stored wall-clock value in
                // America/New_York, and falls back to a 4-hour duration when the
                // date has no end_at.
                $start = new DateTime(EventTime::occurrenceStartsAt($occurrence), true);
                $end = new DateTime(EventTime::occurrenceEndsAt($occurrence), true);

                // update latest time
                if ($start->getDateTime() > $latestTime) {
                    $latestTime = $end->getDateTime();
                }

                $vEvents[] = $this->vEvent($event, new UniqueIdentifier($uid), new TimeSpan($start, $end), $uniqueId);
            }
        }

        // move the oldest time back 1 month
        $oldestTime->sub(new DateInterval('P1M'));

        $timeZone = TimeZone::createFromPhpDateTimeZone(
            $phpDateTimeZone,
            $oldestTime,
            $latestTime,
        );

        $vCalendar->addTimeZone($timeZone);

        // add all events to the calendar
        foreach ($vEvents as $vEvent) {
            $vCalendar->addEvent($vEvent);
        }

        $componentFactory = new CalendarFactory();
        $calendarComponent = $componentFactory->createCalendar($vCalendar);

        // Set the headers
        header('Content-type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$calendarName.'"');

        return $calendarComponent;
    }

    /**
     * One VEVENT: a date of $event, with the event's details.
     */
    private function vEvent(Event $event, UniqueIdentifier $uniqueIdentifier, TimeSpan $occurrence, string $eventUrl): iCalEvent
    {
        $vEvent = new iCalEvent($uniqueIdentifier);

        $vEvent->setOccurrence($occurrence)
            ->setSummary($event->name)
            ->setDescription($event->description ? $event->description : '');

        // convert $event->updated_at to timestamp
        $updated = new DateTime($event->updated_at, true);
        $vEvent->touch($updated);

        // set the url
        $url = $event->primary_link ? $event->primary_link : $eventUrl;
        $url = new Uri($url);
        $vEvent->setUrl($url);

        // set up the venue location
        // get the name for the venue or set to empty
        $venue = $event->venue ? $event->venue->name : '';

        // set the location
        if ($venue) {
            $vEvent->setLocation(new Location($venue));
        }

        // get the promoter to set organizer
        if ($event->promoter) {
            // check for contacts on the promoter
            if ($event->promoter->contacts->count() > 0) {

                // cycle through all contacts to find one with an email address
                foreach ($event->promoter->contacts as $contact) {
                    if ($contact->email) {

                        $organizer = new Organizer(
                            new EmailAddress($contact->email),
                            $event->promoter->name,
                            new Uri($eventUrl),
                            new EmailAddress($contact->email)
                        );

                        $vEvent->setOrganizer($organizer);

                        break;
                    }
                }
            }
        }

        // add the primary image as a url attachment
        $photo = $event->getPrimaryPhoto();
        if ($photo) {
            $imageUrl = Storage::disk('external')->url($photo->getStoragePath());

            $urlAttachment = new Attachment(
                new Uri($imageUrl),
                'image/jpeg'
            );

            $vEvent->addAttachment($urlAttachment);
        }

        return $vEvent;
    }
}
