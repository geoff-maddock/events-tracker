<?php

namespace Tests\Unit;

use App\Enums\EventTimeWindow;
use Carbon\CarbonImmutable;
use Tests\TestCase;

/**
 * Boundary matrix for the /events/tonight, /this-weekend, /this-week
 * landing pages. All times are America/New_York wall-clock, expressed as
 * naive 'Y-m-d H:i:s' strings compared against events.start_at.
 */
class EventTimeWindowTest extends TestCase
{
    protected function now(string $dateTime): CarbonImmutable
    {
        return CarbonImmutable::parse($dateTime, 'America/New_York');
    }

    public function test_this_week_window_rolls_six_days_forward(): void
    {
        $range = EventTimeWindow::ThisWeek->range($this->now('2026-08-04 12:00:00')); // Tuesday

        $this->assertSame('2026-08-04 00:00:00', $range['start']);
        $this->assertSame('2026-08-10 23:59:59', $range['end']);
    }

    public function test_this_week_window_crosses_a_month_boundary(): void
    {
        $range = EventTimeWindow::ThisWeek->range($this->now('2026-08-28 09:00:00')); // Friday

        $this->assertSame('2026-08-28 00:00:00', $range['start']);
        $this->assertSame('2026-09-03 23:59:59', $range['end']);
    }

    // --- Tuesday noon: plain day, weekend is the upcoming Friday ---

    public function test_tuesday_noon_tonight_is_tuesday_evening(): void
    {
        $range = EventTimeWindow::Tonight->range($this->now('2026-08-04 12:00:00')); // Tuesday

        $this->assertSame('2026-08-04 17:00:00', $range['start']);
        $this->assertSame('2026-08-05 03:00:00', $range['end']);
    }

    public function test_tuesday_noon_weekend_is_upcoming_friday(): void
    {
        $range = EventTimeWindow::ThisWeekend->range($this->now('2026-08-04 12:00:00')); // Tuesday

        $this->assertSame('2026-08-07 17:00:00', $range['start']); // Friday
        $this->assertSame('2026-08-10 03:00:00', $range['end']); // Monday
    }

    // --- Friday 18:00: tonight and weekend both anchor to this Friday ---

    public function test_friday_evening_tonight_is_friday_night(): void
    {
        $range = EventTimeWindow::Tonight->range($this->now('2026-08-07 18:00:00')); // Friday

        $this->assertSame('2026-08-07 17:00:00', $range['start']);
        $this->assertSame('2026-08-08 03:00:00', $range['end']);
    }

    public function test_friday_evening_weekend_is_current_weekend(): void
    {
        $range = EventTimeWindow::ThisWeekend->range($this->now('2026-08-07 18:00:00')); // Friday

        $this->assertSame('2026-08-07 17:00:00', $range['start']);
        $this->assertSame('2026-08-10 03:00:00', $range['end']);
    }

    // --- Saturday 01:00: still "Friday night" per the <3am hangover rule ---

    public function test_saturday_1am_tonight_is_still_fridays_window(): void
    {
        $range = EventTimeWindow::Tonight->range($this->now('2026-08-08 01:00:00')); // Saturday

        $this->assertSame('2026-08-07 17:00:00', $range['start']);
        $this->assertSame('2026-08-08 03:00:00', $range['end']);
    }

    public function test_saturday_1am_weekend_is_still_current(): void
    {
        $range = EventTimeWindow::ThisWeekend->range($this->now('2026-08-08 01:00:00')); // Saturday

        $this->assertSame('2026-08-07 17:00:00', $range['start']);
        $this->assertSame('2026-08-10 03:00:00', $range['end']);
    }

    // --- Sunday 20:00: still within the Fri->Mon weekend window ---

    public function test_sunday_evening_weekend_is_still_current(): void
    {
        $range = EventTimeWindow::ThisWeekend->range($this->now('2026-08-09 20:00:00')); // Sunday

        $this->assertSame('2026-08-07 17:00:00', $range['start']);
        $this->assertSame('2026-08-10 03:00:00', $range['end']);
    }

    public function test_sunday_evening_tonight_is_sunday_night(): void
    {
        $range = EventTimeWindow::Tonight->range($this->now('2026-08-09 20:00:00')); // Sunday

        $this->assertSame('2026-08-09 17:00:00', $range['start']);
        $this->assertSame('2026-08-10 03:00:00', $range['end']);
    }

    // --- Monday 02:00: hangover from Sunday night; weekend is the one that just ended ---

    public function test_monday_2am_tonight_is_sundays_window(): void
    {
        $range = EventTimeWindow::Tonight->range($this->now('2026-08-10 02:00:00')); // Monday

        $this->assertSame('2026-08-09 17:00:00', $range['start']);
        $this->assertSame('2026-08-10 03:00:00', $range['end']);
    }

    public function test_monday_2am_weekend_is_the_just_ended_one(): void
    {
        $range = EventTimeWindow::ThisWeekend->range($this->now('2026-08-10 02:00:00')); // Monday

        $this->assertSame('2026-08-07 17:00:00', $range['start']);
        $this->assertSame('2026-08-10 03:00:00', $range['end']);
    }

    // --- Monday 12:00: fully into the new week; weekend is next Friday ---

    public function test_monday_noon_tonight_is_monday_night(): void
    {
        $range = EventTimeWindow::Tonight->range($this->now('2026-08-10 12:00:00')); // Monday

        $this->assertSame('2026-08-10 17:00:00', $range['start']);
        $this->assertSame('2026-08-11 03:00:00', $range['end']);
    }

    public function test_monday_noon_weekend_is_next_friday(): void
    {
        $range = EventTimeWindow::ThisWeekend->range($this->now('2026-08-10 12:00:00')); // Monday

        $this->assertSame('2026-08-14 17:00:00', $range['start']); // Friday
        $this->assertSame('2026-08-17 03:00:00', $range['end']); // Monday
    }

    // --- default $now (no argument) resolves without throwing ---

    public function test_range_defaults_to_current_time_when_now_is_not_injected(): void
    {
        $range = EventTimeWindow::ThisWeek->range();

        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} 00:00:00$/', $range['start']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} 23:59:59$/', $range['end']);
    }

    // --- copy / label / path / title / h1 ---

    public function test_paths_and_labels(): void
    {
        $this->assertSame('/events/tonight', EventTimeWindow::Tonight->path());
        $this->assertSame('Tonight', EventTimeWindow::Tonight->label());
        $this->assertSame('/events/this-weekend', EventTimeWindow::ThisWeekend->path());
        $this->assertSame('This Weekend', EventTimeWindow::ThisWeekend->label());
        $this->assertSame('/events/this-week', EventTimeWindow::ThisWeek->path());
        $this->assertSame('This Week', EventTimeWindow::ThisWeek->label());
    }

    public function test_title_has_no_brand_suffix(): void
    {
        $this->assertSame('Events in Pittsburgh Tonight — Live Music, Shows & More', EventTimeWindow::Tonight->title());
        $this->assertSame('Events in Pittsburgh This Weekend — Live Music, Shows & More', EventTimeWindow::ThisWeekend->title());
        $this->assertSame('Events in Pittsburgh This Week — Live Music, Shows & More', EventTimeWindow::ThisWeek->title());

        foreach (EventTimeWindow::cases() as $window) {
            $this->assertStringNotContainsString('Arcane City', $window->title());
        }
    }

    public function test_h1(): void
    {
        $this->assertSame('Events in Pittsburgh Tonight', EventTimeWindow::Tonight->h1());
        $this->assertSame('Events in Pittsburgh This Weekend', EventTimeWindow::ThisWeekend->h1());
        $this->assertSame('Events in Pittsburgh This Week', EventTimeWindow::ThisWeek->h1());
    }

    // --- meta description grammar ---

    public function test_meta_description_plural_counts(): void
    {
        $desc = EventTimeWindow::Tonight->metaDescription([
            'events' => 5,
            'venues' => 3,
            'tags' => ['Techno', 'Punk', 'DIY'],
        ]);

        $this->assertSame('5 shows tonight across 3 Pittsburgh venues — Techno, Punk, DIY & more.', $desc);
    }

    public function test_meta_description_singular_counts(): void
    {
        $desc = EventTimeWindow::Tonight->metaDescription([
            'events' => 1,
            'venues' => 1,
            'tags' => ['Jazz'],
        ]);

        $this->assertSame('1 show tonight across 1 Pittsburgh venue — Jazz & more.', $desc);
    }

    public function test_meta_description_weekend_and_week_phrases(): void
    {
        $this->assertSame(
            '2 shows this weekend across 2 Pittsburgh venues — Rock & more.',
            EventTimeWindow::ThisWeekend->metaDescription(['events' => 2, 'venues' => 2, 'tags' => ['Rock']])
        );

        $this->assertSame(
            '10 shows this week across 6 Pittsburgh venues — Rock & more.',
            EventTimeWindow::ThisWeek->metaDescription(['events' => 10, 'venues' => 6, 'tags' => ['Rock']])
        );
    }

    public function test_meta_description_falls_back_when_no_events(): void
    {
        $desc = EventTimeWindow::Tonight->metaDescription(['events' => 0, 'venues' => 0, 'tags' => []]);

        $this->assertSame('Live music, club nights and DIY shows in Pittsburgh — updated daily.', $desc);
    }

    public function test_meta_description_omits_venue_clause_when_no_venues(): void
    {
        $desc = EventTimeWindow::Tonight->metaDescription(['events' => 5, 'venues' => 0, 'tags' => []]);

        $this->assertSame('5 shows tonight.', $desc);
        $this->assertStringNotContainsString('Pittsburgh venues', $desc);
        $this->assertStringNotContainsString('across 0', $desc);
    }
}
