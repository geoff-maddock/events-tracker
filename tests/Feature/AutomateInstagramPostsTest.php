<?php

namespace Tests\Feature;

use App\Console\Commands\AutomateInstagramPosts;
use App\Models\Event;
use App\Models\EventShare;
use App\Models\EventType;
use App\Models\Photo;
use App\Models\User;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutomateInstagramPostsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    /**
     * Test that newly created events (today) are identified for posting.
     *
     * @return void
     */
    public function test_identifies_new_events_created_today()
    {
        $user = User::factory()->create();
        $eventType = EventType::first();
        $visibility = Visibility::find(Visibility::VISIBILITY_PUBLIC);

        // Create an event today
        $event = Event::factory()->create([
            'name' => 'New Event Today',
            'slug' => 'new-event-today',
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'event_type_id' => $eventType->id,
            'visibility_id' => $visibility->id,
            'start_at' => Carbon::now()->addDays(10),
            'created_at' => Carbon::now(),
        ]);

        // Create a photo for the event
        $photo = Photo::factory()->create([
            'is_primary' => 1,
            'path' => 'test.jpg',
            'thumbnail' => 'test_thumb.jpg',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $event->photos()->attach($photo->id);

        $command = new AutomateInstagramPosts();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('getEventsToPost');
        $method->setAccessible(true);

        $events = $method->invoke($command);

        $this->assertTrue($events->contains($event));
    }

    /**
     * Test that events without photos are not identified for posting.
     *
     * @return void
     */
    public function test_excludes_events_without_photos()
    {
        $user = User::factory()->create();
        $eventType = EventType::first();
        $visibility = Visibility::find(Visibility::VISIBILITY_PUBLIC);

        // Create an event today without a photo
        $event = Event::factory()->create([
            'name' => 'Event Without Photo',
            'slug' => 'event-without-photo',
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'event_type_id' => $eventType->id,
            'visibility_id' => $visibility->id,
            'start_at' => Carbon::now()->addDays(10),
            'created_at' => Carbon::now(),
        ]);

        $command = new AutomateInstagramPosts();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('getEventsToPost');
        $method->setAccessible(true);

        $events = $method->invoke($command);

        $this->assertFalse($events->contains($event));
    }

    /**
     * Test that private events are not identified for posting.
     *
     * @return void
     */
    public function test_excludes_private_events()
    {
        $user = User::factory()->create();
        $eventType = EventType::first();
        $visibility = Visibility::find(Visibility::VISIBILITY_PRIVATE);

        // Create a private event today
        $event = Event::factory()->create([
            'name' => 'Private Event',
            'slug' => 'private-event',
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'event_type_id' => $eventType->id,
            'visibility_id' => $visibility->id,
            'start_at' => Carbon::now()->addDays(10),
            'created_at' => Carbon::now(),
        ]);

        // Create a photo for the event
        $photo = Photo::factory()->create([
            'is_primary' => 1,
            'path' => 'test.jpg',
            'thumbnail' => 'test_thumb.jpg',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $event->photos()->attach($photo->id);

        $command = new AutomateInstagramPosts();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('getEventsToPost');
        $method->setAccessible(true);

        $events = $method->invoke($command);

        $this->assertFalse($events->contains($event));
    }

    // /**
    //  * Test that events 5 days away with prior share are identified for reminder post.
    //  *
    //  * @return void
    //  */
    // public function test_identifies_events_five_days_away_for_reminder()
    // {
    //     $user = User::factory()->create();
    //     $eventType = EventType::first();
    //     $visibility = Visibility::find(Visibility::VISIBILITY_PUBLIC);

    //     // Create an event 7 days from now, created 14 days before the event
    //     $eventDate = Carbon::now()->addDays(7);
    //     $createdDate = Carbon::now()->subDays(7); // Total of 14 days before event

    //     $event = Event::factory()->create([
    //         'name' => 'Upcoming Event',
    //         'slug' => 'upcoming-event',
    //         'created_by' => $user->id,
    //         'updated_by' => $user->id,
    //         'event_type_id' => $eventType->id,
    //         'visibility_id' => $visibility->id,
    //         'start_at' => $eventDate,
    //         'created_at' => $createdDate,
    //     ]);

    //     // Create a photo for the event
    //     $photo = Photo::factory()->create([
    //         'is_primary' => 1,
    //         'path' => 'test.jpg',
    //         'thumbnail' => 'test_thumb.jpg',
    //         'created_by' => $user->id,
    //         'updated_by' => $user->id,
    //     ]);
    //     $event->photos()->attach($photo->id);

    //     // Create one prior successful share
    //     EventShare::create([
    //         'event_id' => $event->id,
    //         'platform' => 'instagram',
    //         'platform_id' => '12345',
    //         'created_by' => $user->id,
    //         'posted_at' => $createdDate,
    //     ]);

    //     $command = new AutomateInstagramPosts();
    //     $reflection = new \ReflectionClass($command);
    //     $method = $reflection->getMethod('getEventsToPost');
    //     $method->setAccessible(true);

    //     $events = $method->invoke($command);

    //     $this->assertTrue($events->contains($event));
    // }

    /**
     * Test that events with 2 shares are not posted again.
     *
     * @return void
     */
    public function test_excludes_events_already_posted_twice()
    {
        $user = User::factory()->create();
        $eventType = EventType::first();
        $visibility = Visibility::find(Visibility::VISIBILITY_PUBLIC);

        // Create an event 5 days from now
        $eventDate = Carbon::now()->addDays(5);
        $createdDate = Carbon::now()->subDays(10);

        $event = Event::factory()->create([
            'name' => 'Already Posted Event',
            'slug' => 'already-posted-event',
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'event_type_id' => $eventType->id,
            'visibility_id' => $visibility->id,
            'start_at' => $eventDate,
            'created_at' => $createdDate,
        ]);

        // Create a photo for the event
        $photo = Photo::factory()->create([
            'is_primary' => 1,
            'path' => 'test.jpg',
            'thumbnail' => 'test_thumb.jpg',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $event->photos()->attach($photo->id);

        // Create two prior successful shares
        EventShare::create([
            'event_id' => $event->id,
            'platform' => 'instagram',
            'platform_id' => '12345',
            'created_by' => $user->id,
            'posted_at' => $createdDate,
        ]);
        EventShare::create([
            'event_id' => $event->id,
            'platform' => 'instagram',
            'platform_id' => '67890',
            'created_by' => $user->id,
            'posted_at' => Carbon::now(),
        ]);

        $command = new AutomateInstagramPosts();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('getEventsToPost');
        $method->setAccessible(true);

        $events = $method->invoke($command);

        $this->assertFalse($events->contains($event));
    }

    /**
     * Test that past events are not identified for posting.
     *
     * @return void
     */
    public function test_excludes_past_events()
    {
        $user = User::factory()->create();
        $eventType = EventType::first();
        $visibility = Visibility::find(Visibility::VISIBILITY_PUBLIC);

        // Create a past event
        $event = Event::factory()->create([
            'name' => 'Past Event',
            'slug' => 'past-event',
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'event_type_id' => $eventType->id,
            'visibility_id' => $visibility->id,
            'start_at' => Carbon::now()->subDays(5),
            'created_at' => Carbon::now()->subDays(10),
        ]);

        // Create a photo for the event
        $photo = Photo::factory()->create([
            'is_primary' => 1,
            'path' => 'test.jpg',
            'thumbnail' => 'test_thumb.jpg',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $event->photos()->attach($photo->id);

        $command = new AutomateInstagramPosts();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('getEventsToPost');
        $method->setAccessible(true);

        $events = $method->invoke($command);

        $this->assertFalse($events->contains($event));
    }

    /**
     * Test that events with do_not_repost flag are not identified for posting.
     *
     * @return void
     */
    public function test_excludes_events_with_do_not_repost_flag()
    {
        $user = User::factory()->create();
        $eventType = EventType::first();
        $visibility = Visibility::find(Visibility::VISIBILITY_PUBLIC);

        // Create an event with do_not_repost flag set to true
        $event = Event::factory()->create([
            'name' => 'Do Not Repost Event',
            'slug' => 'do-not-repost-event',
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'event_type_id' => $eventType->id,
            'visibility_id' => $visibility->id,
            'start_at' => Carbon::now()->addDays(10),
            'created_at' => Carbon::now(),
            'do_not_repost' => true,
        ]);

        // Create a photo for the event
        $photo = Photo::factory()->create([
            'is_primary' => 1,
            'path' => 'test.jpg',
            'thumbnail' => 'test_thumb.jpg',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $event->photos()->attach($photo->id);

        $command = new AutomateInstagramPosts();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('getEventsToPost');
        $method->setAccessible(true);

        $events = $method->invoke($command);

        $this->assertFalse($events->contains($event));
    }
}
