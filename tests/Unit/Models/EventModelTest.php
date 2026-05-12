<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventModelTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_future_scope_returns_only_events_starting_today_or_later(): void
    {
        Event::factory()->create(['start_at' => Carbon::yesterday()]);
        Event::factory()->create(['start_at' => Carbon::tomorrow()]);

        $results = Event::future()->get();

        foreach ($results as $event) {
            $this->assertGreaterThanOrEqual(Carbon::today()->startOfDay(), $event->start_at);
        }
    }

    public function test_past_scope_returns_only_events_starting_before_today(): void
    {
        Event::factory()->create(['start_at' => Carbon::yesterday()]);
        Event::factory()->create(['start_at' => Carbon::tomorrow()]);

        $results = Event::past()->get();

        foreach ($results as $event) {
            $this->assertLessThan(Carbon::today()->startOfDay(), $event->start_at);
        }
    }

    public function test_today_scope_returns_only_events_starting_today(): void
    {
        $today = Event::factory()->create(['start_at' => Carbon::today()->setTime(20, 0)]);
        Event::factory()->create(['start_at' => Carbon::tomorrow()]);

        $results = Event::today()->get();

        $this->assertTrue($results->contains('id', $today->id));
        foreach ($results as $event) {
            $this->assertEquals(Carbon::today()->toDateString(), $event->start_at->toDateString());
        }
    }

    public function test_owned_by_returns_true_for_creator(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->forceFill(['created_by' => $user->id])->save();

        $this->assertTrue($event->fresh()->ownedBy($user));
    }

    public function test_owned_by_returns_false_for_non_creator(): void
    {
        $creator = User::factory()->create();
        $other = User::factory()->create();
        $event = Event::factory()->create();
        $event->forceFill(['created_by' => $creator->id])->save();

        $this->assertFalse($event->fresh()->ownedBy($other));
    }

    public function test_past_or_future_returns_event_past_for_past_event(): void
    {
        $event = Event::factory()->create(['start_at' => Carbon::yesterday()]);

        $this->assertEquals('event-past', $event->past_or_future);
    }

    public function test_past_or_future_returns_event_future_for_future_event(): void
    {
        $event = Event::factory()->create(['start_at' => Carbon::tomorrow()]);

        $this->assertEquals('event-future', $event->past_or_future);
    }

    public function test_age_format_returns_all_ages_when_min_age_is_zero(): void
    {
        $event = Event::factory()->create(['min_age' => 0]);

        $this->assertEquals('All Ages', $event->age_format);
    }

    public function test_age_format_appends_plus_for_non_zero_min_age(): void
    {
        $event = Event::factory()->create(['min_age' => 21]);

        $this->assertEquals('21+', $event->age_format);
    }

    public function test_age_format_is_empty_when_min_age_is_null(): void
    {
        $event = Event::factory()->create(['min_age' => null]);

        $this->assertEquals('', $event->age_format);
    }

    public function test_end_time_returns_end_at_when_set(): void
    {
        $event = Event::factory()->create([
            'start_at' => Carbon::tomorrow()->setTime(20, 0),
            'end_at' => Carbon::tomorrow()->setTime(23, 0),
        ]);

        $this->assertNotNull($event->end_time);
        $this->assertEquals($event->end_at->toDateTimeString(), $event->end_time->toDateTimeString());
    }

    public function test_default_end_time_falls_back_to_start_plus_default_when_unset(): void
    {
        $event = Event::factory()->create([
            'start_at' => Carbon::tomorrow()->setTime(20, 0),
            'end_at' => null,
        ]);

        $this->assertNotNull($event->default_end_time);
        $this->assertTrue($event->default_end_time->greaterThan($event->start_at));
    }
}
