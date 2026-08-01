<?php

namespace App\Enums;

use Carbon\CarbonImmutable;

/**
 * The four time-window landing pages: /events/tonight, /events/today,
 * /events/this-weekend, /events/this-week. Pure date/copy logic only —
 * no queries, no HTTP. See app/Services/EventTimeWindowStats.php for
 * counts and app/Http/Controllers/EventTimeWindowController.php for glue.
 *
 * All window math happens in America/New_York wall-clock time. Never use
 * config('app.timezone') here — it's a fixed-offset 'EST' zone and will
 * silently miscompute DST transitions.
 */
enum EventTimeWindow: string
{
    case Tonight = 'tonight';
    case Today = 'today';
    case ThisWeekend = 'this-weekend';
    case ThisWeek = 'this-week';

    protected const TIMEZONE = 'America/New_York';

    /**
     * Inclusive start/end naive 'Y-m-d H:i:s' strings, compared against
     * events.start_at via Event::scopeBetween().
     *
     * @return array{start: string, end: string}
     */
    public function range(?CarbonImmutable $now = null): array
    {
        $now = $now ?? CarbonImmutable::now(self::TIMEZONE);

        [$start, $end] = match ($this) {
            self::Today => $this->todayBounds($now),
            self::ThisWeek => $this->thisWeekBounds($now),
            self::Tonight => $this->tonightBounds($now),
            self::ThisWeekend => $this->thisWeekendBounds($now),
        };

        return [
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function todayBounds(CarbonImmutable $now): array
    {
        $date = $now->startOfDay();

        return [$date, $date->setTime(23, 59, 59)];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function thisWeekBounds(CarbonImmutable $now): array
    {
        $start = $now->startOfDay();

        return [$start, $start->addDays(6)->setTime(23, 59, 59)];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function tonightBounds(CarbonImmutable $now): array
    {
        $anchor = $this->anchorDay($now);

        return [$anchor->setTime(17, 0, 0), $anchor->addDay()->setTime(3, 0, 0)];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function thisWeekendBounds(CarbonImmutable $now): array
    {
        $friday = $this->weekendFriday($now);

        return [$friday->setTime(17, 0, 0), $friday->addDays(3)->setTime(3, 0, 0)];
    }

    /**
     * The "anchor day" used by tonight/this-weekend: before 3am, we're still
     * talking about last night, so the anchor rolls back to yesterday.
     */
    private function anchorDay(CarbonImmutable $now): CarbonImmutable
    {
        $day = $now->startOfDay();

        return $now->hour < 3 ? $day->subDay() : $day;
    }

    /**
     * Friday of the relevant weekend: if the anchor day already falls on
     * Fri/Sat/Sun, it's that weekend's Friday; otherwise it's the next one.
     */
    private function weekendFriday(CarbonImmutable $now): CarbonImmutable
    {
        $anchor = $this->anchorDay($now);
        $isoDow = $anchor->dayOfWeekIso; // 1 = Monday ... 7 = Sunday

        if ($isoDow >= 5) {
            // Fri (5), Sat (6), Sun (7) -> back up to that week's Friday.
            return $anchor->subDays($isoDow - 5);
        }

        // Mon..Thu -> forward to the upcoming Friday.
        return $anchor->addDays(5 - $isoDow);
    }

    /**
     * SEO title, no brand suffix — the layout appends " | Arcane City".
     */
    public function title(): string
    {
        return match ($this) {
            self::Tonight => 'Events in Pittsburgh Tonight — Live Music, Shows & More',
            self::Today => 'Events in Pittsburgh Today — Live Music, Shows & More',
            self::ThisWeekend => 'Events in Pittsburgh This Weekend — Live Music, Shows & More',
            self::ThisWeek => 'Events in Pittsburgh This Week — Live Music, Shows & More',
        };
    }

    public function h1(): string
    {
        return match ($this) {
            self::Tonight => 'Events in Pittsburgh Tonight',
            self::Today => 'Events in Pittsburgh Today',
            self::ThisWeekend => 'Events in Pittsburgh This Weekend',
            self::ThisWeek => 'Events in Pittsburgh This Week',
        };
    }

    public function path(): string
    {
        return '/events/'.$this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::Tonight => 'Tonight',
            self::Today => 'Today',
            self::ThisWeekend => 'This Weekend',
            self::ThisWeek => 'This Week',
        };
    }

    /**
     * Window-appropriate adverbial phrase used mid-sentence, e.g.
     * "5 shows {phrase} across 3 Pittsburgh venues".
     */
    private function phrase(): string
    {
        return match ($this) {
            self::Tonight => 'tonight',
            self::Today => 'today',
            self::ThisWeekend => 'this weekend',
            self::ThisWeek => 'this week',
        };
    }

    /**
     * @param array{events?: int, venues?: int, tags?: string[]} $stats
     */
    public function metaDescription(array $stats): string
    {
        $events = $stats['events'] ?? 0;
        $venues = $stats['venues'] ?? 0;
        $tags = $stats['tags'] ?? [];

        if ($events === 0) {
            return 'Live music, club nights and DIY shows in Pittsburgh — updated daily.';
        }

        $showWord = $events === 1 ? 'show' : 'shows';

        $desc = sprintf('%d %s %s', $events, $showWord, $this->phrase());

        if ($venues > 0) {
            $venueWord = $venues === 1 ? 'venue' : 'venues';
            $desc .= sprintf(' across %d Pittsburgh %s', $venues, $venueWord);
        }

        if (! empty($tags)) {
            $desc .= ' — '.implode(', ', $tags).' & more.';
        } else {
            $desc .= '.';
        }

        return $desc;
    }
}
