<?php

namespace Tests\Feature;

use App\Jobs\Instagram\PostTagStoriesToInstagram;
use App\Jobs\Instagram\PostTagToInstagram;
use App\Models\Event;
use App\Models\EventShare;
use App\Models\Group;
use App\Models\Photo;
use App\Models\Tag;
use App\Models\User;
use App\Models\Visibility;
use App\Services\Integrations\Instagram;
use App\Services\Integrations\InstagramEventPoster;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * Admins can post a tag's upcoming events to Instagram from the tag page: the
 * next ones as a feed carousel, or all of them as stories (#2287).
 */
class InstagramTagPostTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['user_status_id' => 1]);
        $admin->groups()->attach(Group::firstOrCreate(['name' => 'super_admin'])->id);

        return $admin;
    }

    private function taggedEvent(Tag $tag, int $daysAhead, bool $withPhoto = true, array $attributes = []): Event
    {
        $event = Event::factory()->create($attributes + [
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => Carbon::now()->addDays($daysAhead)->setTime(20, 0),
            'cancelled_at' => null,
        ]);
        $event->tags()->attach($tag->id);

        if ($withPhoto) {
            $photo = Photo::factory()->create(['is_primary' => 1, 'path' => 'test.jpg', 'thumbnail' => 'test_thumb.jpg']);
            $event->photos()->attach($photo->id);
        }

        return $event;
    }

    private function mockInstagram(): Instagram|Mockery\MockInterface
    {
        Storage::shouldReceive('disk')->with('external')->andReturnSelf()->byDefault();
        Storage::shouldReceive('url')->andReturn('http://example.com/test.jpg')->byDefault();

        $instagram = Mockery::mock(Instagram::class);
        $instagram->shouldReceive('getIgUserId')->andReturn(123)->byDefault();
        $instagram->shouldReceive('getPageAccessToken')->andReturn('token')->byDefault();
        $instagram->shouldReceive('uploadStoryPhoto')->andReturn(111)->byDefault();
        $instagram->shouldReceive('uploadCarouselPhoto')->andReturn(222)->byDefault();
        $instagram->shouldReceive('uploadPhoto')->andReturn(333)->byDefault();
        $instagram->shouldReceive('checkStatus')->andReturn(true)->byDefault();
        $instagram->shouldReceive('checkBatchStatus')->andReturn(true)->byDefault();
        $instagram->shouldReceive('createCarousel')->andReturn(444)->byDefault();
        $instagram->shouldReceive('publishMedia')->andReturn(777)->byDefault();
        $instagram->shouldReceive('publishStoryMedia')->andReturn(555)->byDefault();

        return $instagram;
    }

    public function test_the_tag_menu_shows_the_instagram_actions_to_admins_only(): void
    {
        $tag = Tag::factory()->create();

        $this->actingAs($this->admin())->get(route('tags.show', $tag->slug))->assertOk()
            ->assertSee(route('tags.instagramPost', $tag->slug), false)
            ->assertSee(route('tags.instagramStories', $tag->slug), false);

        $creator = User::factory()->create(['user_status_id' => 1]);
        $tag->update(['created_by' => $creator->id]);
        $this->actingAs($creator)->get(route('tags.show', $tag->slug))->assertOk()
            ->assertDontSee(route('tags.instagramPost', $tag->slug), false);
    }

    public function test_admins_queue_the_feed_post_and_the_stories(): void
    {
        Queue::fake();
        $this->app->instance(Instagram::class, $this->mockInstagram());
        $tag = Tag::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('tags.instagramPost', $tag->slug))->assertRedirect();
        Queue::assertPushed(PostTagToInstagram::class, fn ($job) => $job->tag->is($tag) && $job->userId === $admin->id);

        $this->actingAs($admin)->postJson(route('tags.instagramStories', $tag->slug))->assertOk();
        Queue::assertPushed(PostTagStoriesToInstagram::class, fn ($job) => $job->tag->is($tag) && $job->eventIds === null);
    }

    public function test_non_admins_cannot_post_a_tag(): void
    {
        Queue::fake();
        $this->app->instance(Instagram::class, $this->mockInstagram());
        $tag = Tag::factory()->create();
        $user = User::factory()->create(['user_status_id' => 1]);

        $this->post(route('tags.instagramPost', $tag->slug))->assertRedirect('/login');
        $this->actingAs($user)->post(route('tags.instagramPost', $tag->slug))->assertRedirect();
        $this->actingAs($user)->post(route('tags.instagramStories', $tag->slug))->assertRedirect();

        Queue::assertNothingPushed();
    }

    public function test_nothing_is_queued_when_instagram_is_not_linked(): void
    {
        Queue::fake();
        $instagram = Mockery::mock(Instagram::class);
        $instagram->shouldReceive('getIgUserId')->andReturn(0);
        $instagram->shouldReceive('getPageAccessToken')->andReturn('');
        $this->app->instance(Instagram::class, $instagram);
        $tag = Tag::factory()->create();

        $this->actingAs($this->admin())->postJson(route('tags.instagramPost', $tag->slug))->assertStatus(422);

        Queue::assertNothingPushed();
    }

    public function test_an_unknown_tag_is_a_404(): void
    {
        Queue::fake();
        $this->app->instance(Instagram::class, $this->mockInstagram());

        $this->actingAs($this->admin())->post(route('tags.instagramPost', 'no-such-tag'))->assertNotFound();

        Queue::assertNothingPushed();
    }

    public function test_the_tag_selection_is_upcoming_public_events_with_photos_in_start_order(): void
    {
        $tag = Tag::factory()->create();
        $later = $this->taggedEvent($tag, 5);
        $sooner = $this->taggedEvent($tag, 2);
        $this->taggedEvent($tag, 3, false);                                                  // no photo
        $this->taggedEvent($tag, -3);                                                        // past
        $this->taggedEvent($tag, 4, true, ['visibility_id' => Visibility::VISIBILITY_PRIVATE]);
        $this->taggedEvent($tag, 4, true, ['cancelled_at' => Carbon::now()]);
        $this->taggedEvent(Tag::factory()->create(), 1);                                     // other tag

        $poster = new InstagramEventPoster($this->mockInstagram());

        $this->assertSame([$sooner->id, $later->id], $poster->tagEventIds($tag));
    }

    public function test_a_tag_without_upcoming_photo_events_cannot_be_posted(): void
    {
        $tag = Tag::factory()->create();
        $this->taggedEvent($tag, 2, false);

        $poster = new InstagramEventPoster($this->mockInstagram());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No upcoming events with a photo are tagged');

        $poster->postTagCarousel($tag, null);
    }

    public function test_the_feed_post_is_a_carousel_of_the_next_ten_events(): void
    {
        $tag = Tag::factory()->create(['name' => 'Techno']);
        $events = collect(range(1, 12))->map(fn ($day) => $this->taggedEvent($tag, $day, true, ['name' => 'Night '.$day]));

        $instagram = $this->mockInstagram();
        $instagram->shouldReceive('uploadCarouselPhoto')->times(10)->andReturn(222);
        $instagram->shouldReceive('createCarousel')->once()
            ->with(Mockery::on(fn ($ids) => count($ids) === 10), Mockery::on(function ($caption) {
                return str_starts_with($caption, 'Upcoming Techno events')
                    && str_contains($caption, 'Night 1 ')
                    && str_contains($caption, 'Night 10')
                    && !str_contains($caption, 'Night 11')
                    && strlen(urlencode($caption)) <= 2200;
            }))
            ->andReturn(444);

        $mediaId = (new InstagramEventPoster($instagram))->postTagCarousel($tag, null);

        $this->assertSame(777, $mediaId);
        $this->assertEqualsCanonicalizing(
            $events->take(10)->pluck('id')->all(),
            EventShare::where('platform_id', '777')->pluck('event_id')->all()
        );
    }

    public function test_a_single_event_is_posted_as_a_photo(): void
    {
        $tag = Tag::factory()->create();
        $event = $this->taggedEvent($tag, 1);

        $instagram = $this->mockInstagram();
        $instagram->shouldNotReceive('createCarousel');
        $instagram->shouldNotReceive('uploadCarouselPhoto');
        $instagram->shouldReceive('uploadPhoto')->once()->with('http://example.com/test.jpg', Mockery::any())->andReturn(333);

        (new InstagramEventPoster($instagram))->postTagCarousel($tag, null);

        $this->assertDatabaseHas('event_shares', ['event_id' => $event->id, 'platform_id' => '777']);
    }

    public function test_the_caption_drops_lines_that_would_not_fit_instagrams_limit(): void
    {
        $tag = Tag::factory()->create();
        foreach (range(1, 10) as $day) {
            $this->taggedEvent($tag, $day, true, ['name' => 'Event '.$day.' '.str_repeat('x', 230)]);
        }

        $instagram = $this->mockInstagram();
        $instagram->shouldReceive('createCarousel')->once()
            ->with(Mockery::any(), Mockery::on(function ($caption) {
                return strlen(urlencode($caption)) <= 2200
                    && str_contains($caption, 'Event 1 ')
                    && !str_contains($caption, 'Event 10 ')
                    && str_contains($caption, 'More at ');
            }))
            ->andReturn(444);

        $this->assertSame(777, (new InstagramEventPoster($instagram))->postTagCarousel($tag, null));
    }

    public function test_stories_post_every_upcoming_event_across_batches(): void
    {
        Notification::fake();
        $tag = Tag::factory()->create();
        $events = collect(range(1, 12))->map(fn ($day) => $this->taggedEvent($tag, $day));
        $this->taggedEvent($tag, 13, false); // no photo: left out

        $instagram = $this->mockInstagram();
        $instagram->shouldReceive('publishStoryMedia')->times(12)->andReturn(555);
        $this->app->instance(Instagram::class, $instagram);

        // the sync queue runs the first batch and the one it queues
        PostTagStoriesToInstagram::dispatch($tag, $this->admin()->id);

        $this->assertSame($events->pluck('id')->all(), EventShare::orderBy('id')->pluck('event_id')->all());
        $this->assertDatabaseHas('job_statuses', ['message' => 'Batch 2 of 2: '.$tag->name.' stories posted: 2 stories published.']);
    }
}
