<?php

namespace Tests\Feature;

use App\Services\Integrations\InstagramCaption;
use App\Console\Commands\AutomateInstagramPosts;
use App\Mail\InstagramPostFailure;
use App\Models\Event;
use App\Models\EventShare;
use App\Models\EventType;
use App\Models\Photo;
use App\Models\User;
use App\Models\Visibility;
use App\Services\Integrations\Instagram;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Mockery;
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

    private function eligibleEvent(): Event
    {
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'created_by' => $user->id,
            'event_type_id' => EventType::first()->id,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => Carbon::now()->addDays(5),
        ]);
        $photo = Photo::factory()->create(['is_primary' => 1, 'path' => 'test.jpg', 'thumbnail' => 'test_thumb.jpg', 'created_by' => $user->id]);
        $event->photos()->attach($photo->id);

        return $event;
    }

    private function failedAttempt(Event $event, Carbon $at): void
    {
        (new EventShare())->forceFill([
            'event_id' => $event->id,
            'platform' => 'instagram',
            'posted_at' => null,
            'created_at' => $at,
            'updated_at' => $at,
        ])->save();
    }

    private function eventsToPost()
    {
        $method = (new \ReflectionClass(AutomateInstagramPosts::class))->getMethod('getEventsToPost');
        $method->setAccessible(true);

        return $method->invoke(new AutomateInstagramPosts());
    }

    // a failing event used to stay "never posted" and take a batch slot every run (#2339)
    public function test_an_event_that_failed_recently_waits_before_a_retry(): void
    {
        $event = $this->eligibleEvent();
        $this->failedAttempt($event, Carbon::now()->subHour());

        $this->assertFalse($this->eventsToPost()->contains($event));
    }

    public function test_an_event_is_retried_once_the_wait_has_passed(): void
    {
        $event = $this->eligibleEvent();
        $this->failedAttempt($event, Carbon::now()->subHours(AutomateInstagramPosts::RETRY_AFTER_HOURS + 1));

        $this->assertTrue($this->eventsToPost()->contains($event));
    }

    public function test_an_event_is_given_up_on_after_repeated_failures(): void
    {
        $event = $this->eligibleEvent();
        foreach (range(1, AutomateInstagramPosts::MAX_FAILED_ATTEMPTS) as $daysAgo) {
            $this->failedAttempt($event, Carbon::now()->subDays($daysAgo));
        }

        $this->assertFalse($this->eventsToPost()->contains($event));
    }

    public function test_the_failure_email_goes_out_once_a_day_per_event(): void
    {
        Mail::fake();
        $event = $this->eligibleEvent();
        $method = (new \ReflectionClass(AutomateInstagramPosts::class))->getMethod('sendFailureEmail');
        $method->setAccessible(true);
        $command = new AutomateInstagramPosts();

        $method->invoke($command, $event, 'first failure');
        $method->invoke($command, $event, 'second failure');

        Mail::assertSent(InstagramPostFailure::class, 1);
    }

    /**
     * The Instagram client with every call succeeding, bound in the container;
     * a test overrides the call it wants to fail (#2404).
     */
    private function mockInstagram(): Instagram|Mockery\MockInterface
    {
        Storage::shouldReceive('disk')->with('external')->andReturnSelf()->byDefault();
        Storage::shouldReceive('url')->andReturn('http://example.com/test.jpg')->byDefault();

        $instagram = Mockery::mock(Instagram::class);
        $instagram->shouldReceive('uploadCarouselPhoto')->andReturn(222)->byDefault();
        $instagram->shouldReceive('uploadPhoto')->andReturn(333)->byDefault();
        $instagram->shouldReceive('checkBatchStatus')->andReturn(true)->byDefault();
        $instagram->shouldReceive('createCarousel')->andReturn(444)->byDefault();
        $instagram->shouldReceive('checkStatus')->andReturn(true)->byDefault();
        $instagram->shouldReceive('publishMedia')->andReturn(777)->byDefault();
        $this->app->instance(Instagram::class, $instagram);

        return $instagram;
    }

    public function test_the_command_posts_an_eligible_event_as_a_carousel(): void
    {
        Mail::fake();
        $event = $this->eligibleEvent();
        $second = Photo::factory()->create(['is_primary' => 0, 'path' => 'second.jpg', 'thumbnail' => 'second_thumb.jpg']);
        $event->photos()->attach($second->id);

        $instagram = $this->mockInstagram();
        $instagram->shouldReceive('uploadCarouselPhoto')->twice()->andReturn(222, 223);
        $instagram->shouldReceive('checkBatchStatus')->once()->with([222, 223])->andReturn(true);
        $instagram->shouldReceive('createCarousel')->once()->with([222, 223], InstagramCaption::forEvent($event))->andReturn(444);
        $instagram->shouldReceive('checkStatus')->once()->with(444)->andReturn(true);
        $instagram->shouldReceive('publishMedia')->once()->with(444)->andReturn(777);

        $this->artisan('instagram:autopost')
            ->expectsOutputToContain('Completed: 1 successful, 0 failed')
            ->assertSuccessful();

        $share = EventShare::where('event_id', $event->id)->sole();
        $this->assertSame('instagram', $share->platform);
        $this->assertSame('777', $share->platform_id);
        $this->assertNotNull($share->posted_at);
        Mail::assertNothingSent();

        // posted, so the next run leaves it alone
        $this->assertFalse($this->eventsToPost()->contains($event));
    }

    public function test_an_event_with_one_photo_is_posted_as_a_single_photo(): void
    {
        Mail::fake();
        $event = $this->eligibleEvent();

        $instagram = $this->mockInstagram();
        $instagram->shouldNotReceive('checkBatchStatus');
        $instagram->shouldNotReceive('createCarousel');
        $instagram->shouldReceive('uploadPhoto')->once()
            ->with('http://example.com/test.jpg', urlencode(InstagramCaption::forEvent($event)))->andReturn(333);
        $instagram->shouldReceive('checkStatus')->once()->with(333)->andReturn(true);
        $instagram->shouldReceive('publishMedia')->once()->with(333)->andReturn(777);

        $this->artisan('instagram:autopost')
            ->expectsOutputToContain('Completed: 1 successful, 0 failed')
            ->assertSuccessful();

        $this->assertSame('777', EventShare::where('event_id', $event->id)->sole()->platform_id);
        Mail::assertNothingSent();
    }

    public function test_a_carousel_stops_at_ten_images(): void
    {
        Mail::fake();
        $event = $this->eligibleEvent();
        foreach (range(1, 12) as $n) {
            $event->photos()->attach(Photo::factory()->create(['is_primary' => 0, 'path' => "extra{$n}.jpg", 'thumbnail' => "extra{$n}_thumb.jpg"])->id);
        }

        $instagram = $this->mockInstagram();
        $instagram->shouldReceive('uploadCarouselPhoto')->times(10)->andReturn(222);
        $instagram->shouldReceive('createCarousel')->once()->andReturn(444);

        $this->artisan('instagram:autopost')
            ->expectsOutputToContain('Completed: 1 successful, 0 failed')
            ->assertSuccessful();
    }

    public function test_the_command_does_nothing_when_no_event_is_eligible(): void
    {
        $instagram = $this->mockInstagram();
        $instagram->shouldNotReceive('uploadCarouselPhoto');

        $this->artisan('instagram:autopost')
            ->expectsOutputToContain('No events to post at this time.')
            ->assertSuccessful();

        $this->assertSame(0, EventShare::count());
    }

    public function test_a_failed_publish_is_recorded_and_mailed_to_the_admin(): void
    {
        Mail::fake();
        $event = $this->eligibleEvent();

        $instagram = $this->mockInstagram();
        $instagram->shouldReceive('publishMedia')->once()->andReturn(false);

        $this->artisan('instagram:autopost')
            ->expectsOutputToContain('Completed: 0 successful, 1 failed')
            ->assertSuccessful();

        $share = EventShare::where('event_id', $event->id)->sole();
        $this->assertNull($share->posted_at);
        $this->assertNull($share->platform_id);
        Mail::assertSent(InstagramPostFailure::class, 1);

        // and it waits before the next attempt
        $this->assertFalse($this->eventsToPost()->contains($event));
    }

    public function test_a_failed_primary_upload_stops_the_post(): void
    {
        Mail::fake();
        $event = $this->eligibleEvent();

        $instagram = $this->mockInstagram();
        $instagram->shouldReceive('uploadCarouselPhoto')->once()->andThrow(new \Exception('bad image'));
        $instagram->shouldNotReceive('createCarousel');
        $instagram->shouldNotReceive('publishMedia');

        $this->artisan('instagram:autopost')->assertSuccessful();

        $this->assertNull(EventShare::where('event_id', $event->id)->sole()->posted_at);
        Mail::assertSent(InstagramPostFailure::class, 1);
    }

    public function test_a_failed_extra_photo_is_left_out_and_the_post_goes_ahead(): void
    {
        Mail::fake();
        $event = $this->eligibleEvent();
        $second = Photo::factory()->create(['is_primary' => 0, 'path' => 'second.jpg', 'thumbnail' => 'second_thumb.jpg']);
        $event->photos()->attach($second->id);

        $instagram = $this->mockInstagram();
        $instagram->shouldReceive('uploadCarouselPhoto')->twice()->andReturnUsing(
            fn () => 222,
            fn () => throw new \Exception('bad image')
        );
        // one image left: a single photo, since Instagram refuses a carousel of one
        $instagram->shouldNotReceive('createCarousel');
        $instagram->shouldReceive('uploadPhoto')->once()->andReturn(333);
        $instagram->shouldReceive('publishMedia')->once()->with(333)->andReturn(777);

        $this->artisan('instagram:autopost')
            ->expectsOutputToContain('Completed: 1 successful, 0 failed')
            ->assertSuccessful();

        $this->assertNotNull(EventShare::where('event_id', $event->id)->sole()->posted_at);
    }

    public function test_a_failed_status_check_stops_the_post(): void
    {
        Mail::fake();
        $batch = $this->eligibleEvent();
        // a second photo, so the post is a carousel and its items are checked as a batch
        $batch->photos()->attach(Photo::factory()->create(['is_primary' => 0, 'path' => 'second.jpg', 'thumbnail' => 'second_thumb.jpg'])->id);

        $instagram = $this->mockInstagram();
        $instagram->shouldReceive('checkBatchStatus')->once()->andReturn(false);
        $instagram->shouldNotReceive('createCarousel');

        $this->artisan('instagram:autopost')->assertSuccessful();
        $this->assertNull(EventShare::where('event_id', $batch->id)->sole()->posted_at);

        // the container's own status check
        $carousel = $this->eligibleEvent();
        $instagram = $this->mockInstagram();
        $instagram->shouldReceive('checkStatus')->once()->andReturn(false);
        $instagram->shouldNotReceive('publishMedia');

        $this->artisan('instagram:autopost')->assertSuccessful();
        $this->assertNull(EventShare::where('event_id', $carousel->id)->sole()->posted_at);
    }

    public function test_a_run_posts_three_events_at_most_when_few_are_waiting(): void
    {
        Mail::fake();
        foreach (range(1, 5) as $i) {
            $this->eligibleEvent();
        }

        $instagram = $this->mockInstagram();
        $instagram->shouldReceive('publishMedia')->times(3)->andReturn(777);

        $this->artisan('instagram:autopost')
            ->expectsOutputToContain('Will attempt to post 3 event(s) in this batch.')
            ->assertSuccessful();

        $this->assertSame(3, EventShare::whereNotNull('posted_at')->count());
    }
}
