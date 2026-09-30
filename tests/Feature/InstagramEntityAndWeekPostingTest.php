<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Entity;
use App\Models\Event;
use App\Models\EventShare;
use App\Models\Group;
use App\Models\Photo;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use App\Services\ImageHandler;
use App\Services\Integrations\Instagram;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * What posting an entity (feed and story) and the week's events to Instagram
 * does, with the Instagram API mocked (#2182: the logic moved out of the
 * controllers into services, behaviour unchanged).
 */
class InstagramEntityAndWeekPostingTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->owner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        Storage::shouldReceive('disk')->with('external')->andReturnSelf()->byDefault();
        Storage::shouldReceive('url')->andReturn('https://example.com/photo.jpg')->byDefault();
        Storage::shouldReceive('putFileAs')->andReturn('photos/week-image.jpg')->byDefault();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function instagram(): MockInterface
    {
        $instagram = Mockery::mock(Instagram::class);
        $instagram->shouldReceive('getIgUserId')->andReturn(123)->byDefault();
        $instagram->shouldReceive('getPageAccessToken')->andReturn('token')->byDefault();
        $instagram->shouldReceive('getLastError')->andReturn(null)->byDefault();
        $this->app->instance(Instagram::class, $instagram);

        return $instagram;
    }

    private function withPhoto(Entity|Event $model): void
    {
        $photo = Photo::factory()->create([
            'is_primary' => 1, 'path' => 'zz.jpg', 'thumbnail' => 'zz_tn.jpg',
            'created_by' => $this->owner->id, 'updated_by' => $this->owner->id,
        ]);
        $model->photos()->attach($photo->id);
    }

    private function ownedEntity(): Entity
    {
        $entity = Entity::factory()->create(['created_by' => $this->owner->id, 'name' => 'Zz Posted Entity']);
        $this->withPhoto($entity);

        return $entity;
    }

    private function instagramLogCount(Entity $entity): int
    {
        return Activity::where('object_table', 'Entity')->where('object_id', $entity->id)->where('action_id', 16)->count();
    }

    public function test_an_entity_without_upcoming_events_is_posted_as_a_single_photo(): void
    {
        $entity = $this->ownedEntity();
        $instagram = $this->instagram();
        $instagram->shouldReceive('uploadCarouselPhoto')->once()->andReturn(11);
        $instagram->shouldReceive('uploadPhoto')->once()->andReturn(21);
        $instagram->shouldReceive('checkStatus')->with(21)->once()->andReturn(true);
        $instagram->shouldReceive('publishMedia')->with(21)->once()->andReturn(555);
        $instagram->shouldNotReceive('createCarousel');

        $this->actingAs($this->owner)->post("/entities/{$entity->id}/instagram-post")
            ->assertRedirect()
            ->assertSessionHas('flash_message.level', 'success')
            ->assertSessionHas('flash_message.message', fn ($m) => str_contains($m, '555'));

        $this->assertSame(1, $this->instagramLogCount($entity));
    }

    public function test_an_entity_with_upcoming_events_is_posted_as_a_carousel(): void
    {
        $entity = $this->ownedEntity();
        foreach ([2, 5] as $days) {
            $event = Event::factory()->create(['start_at' => Carbon::now()->addDays($days), 'visibility_id' => Visibility::VISIBILITY_PUBLIC]);
            $this->withPhoto($event);
            $event->entities()->attach($entity->id);
        }
        // an upcoming event without a photo is left out of the carousel
        Event::factory()->create(['start_at' => Carbon::now()->addDays(3)])->entities()->attach($entity->id);
        // so is someone else's private event, photo or not (#2244)
        $private = Event::factory()->create(['start_at' => Carbon::now()->addDays(4), 'visibility_id' => Visibility::VISIBILITY_PRIVATE]);
        $this->withPhoto($private);
        $private->entities()->attach($entity->id);

        $instagram = $this->instagram();
        $instagram->shouldReceive('uploadCarouselPhoto')->times(3)->andReturn(11, 12, 13);
        $instagram->shouldReceive('checkBatchStatus')->with([11, 12, 13])->once()->andReturn(true);
        $instagram->shouldReceive('createCarousel')->with([11, 12, 13], Mockery::type('string'))->once()->andReturn(31);
        $instagram->shouldReceive('checkStatus')->with(31)->once()->andReturn(true);
        $instagram->shouldReceive('publishMedia')->with(31)->once()->andReturn(556);
        $instagram->shouldNotReceive('uploadPhoto');

        $this->actingAs($this->owner)->post("/entities/{$entity->id}/instagram-post")
            ->assertSessionHas('flash_message.level', 'success')
            ->assertSessionHas('flash_message.message', fn ($m) => str_contains($m, '556'));

        $this->assertSame(1, $this->instagramLogCount($entity));
    }

    public function test_an_entity_post_fails_cleanly(): void
    {
        $entity = $this->ownedEntity();
        $instagram = $this->instagram();
        $instagram->shouldReceive('uploadCarouselPhoto')->andReturn(11);
        $instagram->shouldReceive('uploadPhoto')->andReturn(21);
        $instagram->shouldReceive('checkStatus')->andReturn(true);
        $instagram->shouldReceive('publishMedia')->andReturn(false);

        $this->actingAs($this->owner)->post("/entities/{$entity->id}/instagram-post")
            ->assertSessionHas('flash_message.level', 'error')
            ->assertSessionHas('flash_message.message', fn ($m) => str_contains($m, 'error posting to Instagram'));

        $this->assertSame(0, $this->instagramLogCount($entity));
    }

    public function test_an_entity_post_needs_a_linked_account_and_a_photo(): void
    {
        $entity = $this->ownedEntity();
        $instagram = $this->instagram();
        $instagram->shouldReceive('getIgUserId')->andReturn(0);
        $instagram->shouldNotReceive('uploadCarouselPhoto');

        $this->actingAs($this->owner)->post("/entities/{$entity->id}/instagram-post")
            ->assertSessionHas('flash_message.message', 'You must have an Instagram user account linked to post to Instagram.');

        $bare = Entity::factory()->create(['created_by' => $this->owner->id]);
        $this->instagram()->shouldNotReceive('uploadCarouselPhoto');

        foreach (['instagram-post', 'instagram-story-post'] as $action) {
            $this->actingAs($this->owner)->post("/entities/{$bare->id}/{$action}")
                ->assertSessionHas('flash_message.level', 'error')
                ->assertSessionHas('flash_message.message', fn ($m) => str_contains($m, 'photo'));
        }
    }

    public function test_an_entity_is_posted_as_a_story(): void
    {
        $entity = $this->ownedEntity();
        $instagram = $this->instagram();
        $instagram->shouldReceive('uploadStoryPhoto')->once()->andReturn(41);
        $instagram->shouldReceive('checkStatus')->with(41)->once()->andReturn(true);
        // both hit the same media_publish endpoint
        $instagram->shouldReceive('publishMedia', 'publishStoryMedia')->with(41)->andReturn(557);

        $this->actingAs($this->owner)->post("/entities/{$entity->id}/instagram-story-post")
            ->assertSessionHas('flash_message.level', 'success')
            ->assertSessionHas('flash_message.message', fn ($m) => str_contains($m, '557'));

        $this->assertSame(1, $this->instagramLogCount($entity));
    }

    public function test_the_week_is_posted_as_a_cover_plus_event_carousel(): void
    {
        $admin = $this->superAdmin();

        $events = [];
        foreach (['Zz Week One', 'Zz Week Two'] as $i => $name) {
            $events[] = $event = Event::factory()->create([
                'name' => $name, 'visibility_id' => Visibility::VISIBILITY_PUBLIC,
                'start_at' => Carbon::now()->startOfWeek()->addDays($i + 1)->setTime(20, 0),
            ]);
            $this->withPhoto($event);
        }

        $cover = $this->fakeCoverImage();

        $instagram = $this->instagram();
        $instagram->shouldReceive('uploadCarouselPhoto')->times(3)->andReturn(10, 11, 12);
        $instagram->shouldReceive('checkBatchStatus')->with([10, 11, 12])->once()->andReturn(true);
        $instagram->shouldReceive('createCarousel')
            ->with([10, 11, 12], Mockery::on(fn ($caption) => str_contains($caption, 'Zz Week One') && str_contains($caption, 'Zz Week Two')))
            ->once()->andReturn(30);
        $instagram->shouldReceive('checkStatus')->with(30)->once()->andReturn(true);
        $instagram->shouldReceive('publishMedia')->with(30)->once()->andReturn(558);

        $this->actingAs($admin)->post('/events/instagram-post-week')
            ->assertSessionHas('flash_message.level', 'success')
            ->assertSessionHas('flash_message.message', fn ($m) => str_contains($m, '558'));

        foreach ($events as $event) {
            $this->assertSame(1, EventShare::where('event_id', $event->id)->where('platform_id', '558')->count());
        }
        @unlink($cover);
    }

    public function test_the_week_post_leaves_out_private_cancelled_and_photo_less_events(): void
    {
        $day = Carbon::now()->startOfWeek()->addDays(2)->setTime(20, 0);
        $public = Event::factory()->create(['name' => 'Zz Public', 'visibility_id' => Visibility::VISIBILITY_PUBLIC, 'start_at' => $day]);
        $private = Event::factory()->create(['name' => 'Zz Private', 'visibility_id' => Visibility::VISIBILITY_PRIVATE, 'start_at' => $day]);
        $cancelled = Event::factory()->create(['name' => 'Zz Cancelled', 'visibility_id' => Visibility::VISIBILITY_PUBLIC, 'start_at' => $day, 'cancelled_at' => Carbon::now()]);
        $noPhoto = Event::factory()->create(['name' => 'Zz No Photo', 'visibility_id' => Visibility::VISIBILITY_PUBLIC, 'start_at' => $day]);
        foreach ([$public, $private, $cancelled] as $event) {
            $this->withPhoto($event);
        }
        $cover = $this->fakeCoverImage();

        $instagram = $this->instagram();
        $instagram->shouldReceive('uploadCarouselPhoto')->twice()->andReturn(10, 11);
        $instagram->shouldReceive('checkBatchStatus')->with([10, 11])->andReturn(true);
        $instagram->shouldReceive('createCarousel')
            ->with([10, 11], Mockery::on(fn ($caption) => str_contains($caption, 'Zz Public')
                && !str_contains($caption, 'Zz Private') && !str_contains($caption, 'Zz Cancelled') && !str_contains($caption, 'Zz No Photo')))
            ->once()->andReturn(30);
        $instagram->shouldReceive('checkStatus')->andReturn(true);
        $instagram->shouldReceive('publishMedia')->andReturn(559);

        $this->actingAs($this->superAdmin())->post('/events/instagram-post-week')
            ->assertSessionHas('flash_message.level', 'success');

        $this->assertSame(1, EventShare::where('event_id', $public->id)->count());
        foreach ([$private, $cancelled, $noPhoto] as $event) {
            $this->assertSame(0, EventShare::where('event_id', $event->id)->count());
        }
        @unlink($cover);
    }

    public function test_a_week_without_photos_is_not_posted(): void
    {
        Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC, 'start_at' => Carbon::now()->startOfWeek()->addDay()]);
        // nothing is generated, stored or uploaded
        $this->app->instance(ImageHandler::class, Mockery::mock(ImageHandler::class, function ($m) {
            $m->shouldNotReceive('generateCoverImage');
        }));
        Storage::shouldReceive('putFileAs')->never();

        $instagram = $this->instagram();
        $instagram->shouldNotReceive('uploadCarouselPhoto');

        $this->actingAs($this->superAdmin())->post('/events/instagram-post-week')
            ->assertSessionHas('flash_message.level', 'error')
            ->assertSessionHas('flash_message.message', "None of this week's events have a photo to post to Instagram.");
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $admin->groups()->attach(Group::firstOrCreate(['name' => 'super_admin'])->id);

        return $admin;
    }

    private function fakeCoverImage(): string
    {
        $cover = tempnam(sys_get_temp_dir(), 'zzcover');
        $this->app->instance(ImageHandler::class, Mockery::mock(ImageHandler::class, function ($m) use ($cover) {
            $m->shouldReceive('generateCoverImage')->andReturn($cover);
        }));

        return $cover;
    }
}
