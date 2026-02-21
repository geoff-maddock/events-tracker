<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\OccurrenceDay;
use App\Models\OccurrenceType;
use App\Models\OccurrenceWeek;
use App\Models\Series;
use App\Models\Tag;
use App\Models\Entity;
use App\Models\Photo;
use App\Models\User;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CreateSeriesEventsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    /**
     * Test that the command can be executed successfully.
     *
     * @return void
     */
    public function test_command_executes_successfully()
    {
        $exitCode = Artisan::call('series:create-events');

        $this->assertEquals(0, $exitCode);
    }

    /**
     * Test that the command creates an event for a series without a future event.
     *
     * @return void
     */
    public function test_command_creates_event_for_series_without_future_event()
    {
        // Create a user to own the series
        $user = User::factory()->create();

        // Get occurrence type
        $occurrenceType = OccurrenceType::where('name', 'Weekly')->first();
        $occurrenceDay = OccurrenceDay::where('name', 'Monday')->first();

        // Create a series with a weekly occurrence
        $series = Series::factory()->create([
            'name' => 'Test Weekly Series',
            'created_by' => $user->id,
            'occurrence_type_id' => $occurrenceType->id,
            'occurrence_day_id' => $occurrenceDay->id,
            'founded_at' => Carbon::now()->subWeeks(2),
            'start_at' => Carbon::now()->addWeeks(1)->setTime(20, 0, 0),
            'length' => 3,
            'cancelled_at' => null,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        // Count events before
        $eventCountBefore = Event::where('series_id', $series->id)->count();

        // Run the command
        $exitCode = Artisan::call('series:create-events');

        // Count events after
        $eventCountAfter = Event::where('series_id', $series->id)->count();

        // Assert command succeeded
        $this->assertEquals(0, $exitCode);

        // Assert an event was created
        $this->assertEquals($eventCountBefore + 1, $eventCountAfter);

        // Get the created event
        $event = Event::where('series_id', $series->id)->latest()->first();

        // Assert event has correct attributes
        $this->assertEquals($series->name, $event->name);
        $this->assertEquals($series->created_by, $event->created_by);
        $this->assertEquals($series->visibility_id, $event->visibility_id);
    }

    /**
     * Test that the command skips series that already have a future event.
     *
     * @return void
     */
    public function test_command_skips_series_with_existing_future_event()
    {
        // Create a user to own the series
        $user = User::factory()->create();

        // Get occurrence type
        $occurrenceType = OccurrenceType::where('name', 'Weekly')->first();
        $occurrenceDay = OccurrenceDay::where('name', 'Monday')->first();

        // Create a series
        $series = Series::factory()->create([
            'name' => 'Test Series With Event',
            'created_by' => $user->id,
            'occurrence_type_id' => $occurrenceType->id,
            'occurrence_day_id' => $occurrenceDay->id,
            'founded_at' => Carbon::now()->subWeeks(2),
            'start_at' => Carbon::now()->addWeeks(1)->setTime(20, 0, 0),
            'cancelled_at' => null,
        ]);

        // Create an existing future event for this series
        $existingEvent = Event::factory()->create([
            'series_id' => $series->id,
            'start_at' => Carbon::now()->addWeeks(1),
            'created_by' => $user->id,
        ]);

        // Count events before
        $eventCountBefore = Event::where('series_id', $series->id)->count();

        // Run the command
        $exitCode = Artisan::call('series:create-events');

        // Count events after
        $eventCountAfter = Event::where('series_id', $series->id)->count();

        // Assert command succeeded
        $this->assertEquals(0, $exitCode);

        // Assert no new event was created
        $this->assertEquals($eventCountBefore, $eventCountAfter);
    }

    /**
     * Test that the command copies entities from series to event.
     *
     * @return void
     */
    public function test_command_copies_entities_to_event()
    {
        // Create a user and entities
        $user = User::factory()->create();
        $venue = Entity::factory()->create(['name' => 'Test Venue']);
        $artist = Entity::factory()->create(['name' => 'Test Artist']);

        // Get occurrence type
        $occurrenceType = OccurrenceType::where('name', 'Weekly')->first();
        $occurrenceDay = OccurrenceDay::where('name', 'Monday')->first();

        // Create a series with entities
        $series = Series::factory()->create([
            'name' => 'Test Series With Entities',
            'created_by' => $user->id,
            'venue_id' => $venue->id,
            'occurrence_type_id' => $occurrenceType->id,
            'occurrence_day_id' => $occurrenceDay->id,
            'founded_at' => Carbon::now()->subWeeks(2),
            'start_at' => Carbon::now()->addWeeks(1)->setTime(20, 0, 0),
            'cancelled_at' => null,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        // Attach entities to series
        $series->entities()->attach([$venue->id, $artist->id]);

        // Run the command
        Artisan::call('series:create-events');

        // Get the created event
        $event = Event::where('series_id', $series->id)->latest()->first();

        // Assert entities were copied
        $this->assertNotNull($event);
        $this->assertTrue($event->entities->contains($venue));
        $this->assertTrue($event->entities->contains($artist));
    }

    /**
     * Test that the command copies tags from series to event.
     *
     * @return void
     */
    public function test_command_copies_tags_to_event()
    {
        // Create a user and tags
        $user = User::factory()->create();
        $tag1 = Tag::factory()->create(['name' => 'Music']);
        $tag2 = Tag::factory()->create(['name' => 'Live']);

        // Get occurrence type
        $occurrenceType = OccurrenceType::where('name', 'Weekly')->first();
        $occurrenceDay = OccurrenceDay::where('name', 'Monday')->first();

        // Create a series with tags
        $series = Series::factory()->create([
            'name' => 'Test Series With Tags',
            'created_by' => $user->id,
            'occurrence_type_id' => $occurrenceType->id,
            'occurrence_day_id' => $occurrenceDay->id,
            'founded_at' => Carbon::now()->subWeeks(2),
            'start_at' => Carbon::now()->addWeeks(1)->setTime(20, 0, 0),
            'cancelled_at' => null,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        // Attach tags to series
        $series->tags()->attach([$tag1->id, $tag2->id]);

        // Run the command
        Artisan::call('series:create-events');

        // Get the created event
        $event = Event::where('series_id', $series->id)->latest()->first();

        // Assert tags were copied
        $this->assertNotNull($event);
        $this->assertTrue($event->tags->contains($tag1));
        $this->assertTrue($event->tags->contains($tag2));
    }

    /**
     * Test that the command skips cancelled series.
     *
     * @return void
     */
    public function test_command_skips_cancelled_series()
    {
        // Create a user
        $user = User::factory()->create();

        // Get occurrence type
        $occurrenceType = OccurrenceType::where('name', 'Weekly')->first();
        $occurrenceDay = OccurrenceDay::where('name', 'Monday')->first();

        // Create a cancelled series
        $series = Series::factory()->create([
            'name' => 'Cancelled Series',
            'created_by' => $user->id,
            'occurrence_type_id' => $occurrenceType->id,
            'occurrence_day_id' => $occurrenceDay->id,
            'founded_at' => Carbon::now()->subWeeks(2),
            'start_at' => Carbon::now()->addWeeks(1)->setTime(20, 0, 0),
            'cancelled_at' => Carbon::now()->subDays(1),
            'visibility_id' => Visibility::VISIBILITY_CANCELLED,
        ]);

        // Count events before
        $eventCountBefore = Event::where('series_id', $series->id)->count();

        // Run the command
        Artisan::call('series:create-events');

        // Count events after
        $eventCountAfter = Event::where('series_id', $series->id)->count();

        // Assert no event was created
        $this->assertEquals($eventCountBefore, $eventCountAfter);
    }

    /**
     * Test that the command skips series with "No Schedule" occurrence type.
     *
     * @return void
     */
    public function test_command_skips_no_schedule_series()
    {
        // Create a user
        $user = User::factory()->create();

        // Get "No Schedule" occurrence type
        $noScheduleType = OccurrenceType::where('name', 'No Schedule')->first();

        // Create a series with no schedule
        $series = Series::factory()->create([
            'name' => 'No Schedule Series',
            'created_by' => $user->id,
            'occurrence_type_id' => $noScheduleType->id,
            'founded_at' => Carbon::now()->subWeeks(2),
            'start_at' => Carbon::now()->addWeeks(1)->setTime(20, 0, 0),
            'cancelled_at' => null,
        ]);

        // Count events before
        $eventCountBefore = Event::where('series_id', $series->id)->count();

        // Run the command
        Artisan::call('series:create-events');

        // Count events after
        $eventCountAfter = Event::where('series_id', $series->id)->count();

        // Assert no event was created
        $this->assertEquals($eventCountBefore, $eventCountAfter);
    }
}
