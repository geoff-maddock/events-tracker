<?php

namespace Tests\Feature\Services\Calendar;

use App\Models\Entity;
use App\Models\Event;
use App\Services\Calendar\ICalBuilder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ICalBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_builder_renders_basic_calendar_envelope(): void
    {
        $event = Event::factory()->create([
            'name' => 'Sample Concert ZZ',
            'description' => 'A description for the show.',
            'start_at' => Carbon::parse('2026-06-15 20:00:00'),
            'end_at' => Carbon::parse('2026-06-15 23:00:00'),
        ]);

        $ics = @(new ICalBuilder())->buildCalendar('test.ics', collect([$event]));

        $this->assertStringContainsString('BEGIN:VCALENDAR', $ics);
        $this->assertStringContainsString('END:VCALENDAR', $ics);
        $this->assertStringContainsString('BEGIN:VEVENT', $ics);
        $this->assertStringContainsString('SUMMARY:Sample Concert ZZ', $ics);
        $this->assertStringContainsString('DESCRIPTION:A description for the show.', $ics);
    }

    public function test_event_with_venue_emits_location(): void
    {
        $venue = Entity::factory()->venue()->create(['name' => 'The ZZ Venue']);
        $event = Event::factory()->create([
            'name' => 'Show With Venue',
            'venue_id' => $venue->id,
            'start_at' => Carbon::parse('2026-07-10 19:00:00'),
            'end_at' => Carbon::parse('2026-07-10 22:00:00'),
        ]);

        $ics = @(new ICalBuilder())->buildCalendar('venue.ics', collect([$event]));

        $this->assertStringContainsString('LOCATION:The ZZ Venue', $ics);
    }

    public function test_event_without_end_at_falls_back_to_four_hour_duration(): void
    {
        $event = Event::factory()->create([
            'name' => 'Open-ended ZZ Show',
            'start_at' => Carbon::parse('2026-08-01 18:00:00'),
            'end_at' => null,
        ]);

        $ics = @(new ICalBuilder())->buildCalendar('open.ics', collect([$event]));

        $this->assertStringContainsString('BEGIN:VEVENT', $ics);
        $this->assertStringContainsString('SUMMARY:Open-ended ZZ Show', $ics);
        // Expect a DTEND emitted by the builder even with no end_at set.
        $this->assertStringContainsString('DTEND', $ics);
    }
}
