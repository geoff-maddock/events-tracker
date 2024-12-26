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
use DateTimeZone as PhpDateTimeZone;
use DateTime as PhpDateTime;
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

        // set the default php time zone
        date_default_timezone_set('America/New_York');

        // specify local time zone
        $phpDateTimeZone = new PhpDateTimeZone('America/New_York');
        $utcTimeZone = new PhpDateTimeZone('UTC');

        $oldestTime = new DateTimeImmutable('now', $phpDateTimeZone);
        $latestTime = new DateTimeImmutable('now', $phpDateTimeZone);

        // create an empty array to store the events
        $vEvents = [];

        // loop over events
        foreach ($events as $event) {
            // use the route for the event as the unique id
            $uniqueId = route('events.show', ['event' => $event]);
            
            // set up unique ID
            $uniqueIdentifier = new UniqueIdentifier($uniqueId);

            $vEvent = new iCalEvent($uniqueIdentifier);

            // set up occurrence           
            $phpStart = PhpDateTime::createFromFormat('Y-m-d H:i:s', $event->start_at, $phpDateTimeZone);
            $phpStart->setTimezone($phpDateTimeZone);
            $start = new DateTime($phpStart, true);


            if ($event->end_at) {
                $phpEnd = PhpDateTime::createFromFormat('Y-m-d H:i:s', $event->end_at, $phpDateTimeZone);
                $phpEnd->setTimezone($phpDateTimeZone);
                $end = new DateTime($phpEnd, true);
            } else {
                $phpEnd = PhpDateTime::createFromFormat('Y-m-d H:i:s', $event->start_at, $phpDateTimeZone);
                $phpEnd->setTimezone($phpDateTimeZone);

                // add 4 hours to the php end time  
                $phpEnd->add(new DateInterval('PT4H'));
                $end = new DateTime($phpEnd, true);

            }

            $occurrence = new TimeSpan($start, $end);

            // update oldest and latest times
            if ($event->updated_at < $oldestTime) {
                $oldestTime = $event->updated_at;
            }
            if ($start->getDateTime() > $latestTime) {
                $latestTime = $end->getDateTime();
            }

            $vEvent->setOccurrence($occurrence)
                ->setSummary($event->name)
                ->setDescription($event->description);

            // convert $event->updated_at to timestamp
            $updated = new DateTime($event->updated_at, true);
            $vEvent->touch($updated);

            // set the url
            $url = $event->primary_link ? $event->primary_link : $uniqueId;
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
                                new Uri($uniqueId),
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

            $vEvents[] = $vEvent;
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
}
