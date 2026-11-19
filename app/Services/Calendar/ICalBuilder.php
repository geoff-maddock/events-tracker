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

            // what every date of the event shares, worked out once
            $details = $this->details($event, $uniqueId);

            // One VEVENT per date: a short run's showings each get their own
            // (#2375). The first date keeps the event's UID, so the entry a
            // subscribed calendar already has stays the same item; each later
            // showing's UID adds its start, which is stable across edits.
            foreach ($event->occurrences() as $occurrence) {
                $uid = $occurrence->start_at->equalTo($event->start_at)
                    ? $uniqueId
                    : $uniqueId.'#'.$occurrence->start_at->format('Ymd\THis');

                // EventTime reinterprets the naive stored wall-clock value in
                // America/New_York, and falls back to a 4-hour duration when the
                // date has no end_at.
                $start = new DateTime(EventTime::occurrenceStartsAt($occurrence), true);
                $end = new DateTime(EventTime::occurrenceEndsAt($occurrence), true);

                // update latest time
                if ($start->getDateTime() > $latestTime) {
                    $latestTime = $end->getDateTime();
                }

                $vEvents[] = $this->vEvent(new UniqueIdentifier($uid), new TimeSpan($start, $end), $details);
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
     * The parts of a VEVENT that are the same for every date of $event.
     *
     * @return array{summary: string, description: string, updated: DateTime, url: Uri, location: Location|null, organizer: Organizer|null, attachment: Attachment|null}
     */
    private function details(Event $event, string $eventUrl): array
    {
        // set up the venue location
        $location = $event->venueLabel() ? new Location($event->venueLabel()) : null;

        // the promoter's first contact with an email address is the organizer
        $organizer = null;
        foreach ($event->promoter->contacts ?? [] as $contact) {
            if ($contact->email) {
                $organizer = new Organizer(
                    new EmailAddress($contact->email),
                    $event->promoter->name,
                    new Uri($eventUrl),
                    new EmailAddress($contact->email)
                );

                break;
            }
        }

        // the primary image as a url attachment
        $attachment = null;
        $photo = $event->getPrimaryPhoto();
        if ($photo) {
            $attachment = new Attachment(
                new Uri(Storage::disk('external')->url($photo->getStoragePath())),
                'image/jpeg'
            );
        }

        return [
            'summary' => (string) $event->name,
            'description' => $event->description ? $event->description : '',
            'updated' => new DateTime($event->updated_at, true),
            'url' => new Uri($event->primary_link ? $event->primary_link : $eventUrl),
            'location' => $location,
            'organizer' => $organizer,
            'attachment' => $attachment,
        ];
    }

    /**
     * One VEVENT: a date of an event, with the details from details().
     *
     * @param array{summary: string, description: string, updated: DateTime, url: Uri, location: Location|null, organizer: Organizer|null, attachment: Attachment|null} $details
     */
    private function vEvent(UniqueIdentifier $uniqueIdentifier, TimeSpan $occurrence, array $details): iCalEvent
    {
        $vEvent = new iCalEvent($uniqueIdentifier);

        $vEvent->setOccurrence($occurrence)
            ->setSummary($details['summary'])
            ->setDescription($details['description'])
            ->setUrl($details['url']);
        $vEvent->touch($details['updated']);

        if ($details['location']) {
            $vEvent->setLocation($details['location']);
        }

        if ($details['organizer']) {
            $vEvent->setOrganizer($details['organizer']);
        }

        if ($details['attachment']) {
            $vEvent->addAttachment($details['attachment']);
        }

        return $vEvent;
    }
}
