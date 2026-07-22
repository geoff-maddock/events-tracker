<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\EventsController;
use App\Mail\FollowingUpdate;
use App\Models\Entity;
use App\Models\Event;
use App\Models\Follow;
use App\Models\Photo;
use App\Models\Profile;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

/**
 * EventsController::notifyFollowing dedup (issue #1991): the tag loop keyed
 * its notified-users map on $user->user_id, an attribute that doesn't exist
 * on followers() rows (only users.* is selected). Every follower collapsed
 * onto the null key, so only the FIRST tag follower ever received an email,
 * and the entity pass could double-mail users the tag pass had covered.
 */
class EventNotifyFollowingTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function follower(string $email, int $instantUpdate = 1): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'user_status_id' => UserStatus::ACTIVE,
        ]);
        Profile::factory()->create([
            'user_id' => $user->id,
            'setting_instant_update' => $instantUpdate,
        ]);

        return $user;
    }

    private function follow(User $user, string $type, int $objectId): void
    {
        Follow::create([
            'user_id' => $user->id,
            'object_type' => $type,
            'object_id' => $objectId,
        ]);
    }

    private function notify(Event $event): void
    {
        $controller = app(EventsController::class);
        $method = new ReflectionMethod($controller, 'notifyFollowing');
        $method->invoke($controller, $event);
    }

    private function sentTo(string $email): int
    {
        return Mail::sent(FollowingUpdate::class)
            ->filter(fn (FollowingUpdate $mail) => $mail->hasTo($email))
            ->count();
    }

    /** @test */
    public function every_tag_follower_receives_the_notification(): void
    {
        $tag = Tag::factory()->create();
        $event = Event::factory()->create();
        $event->tags()->attach($tag->id);

        $first = $this->follower('first-follower@example.com');
        $second = $this->follower('second-follower@example.com');
        $this->follow($first, 'tag', $tag->id);
        $this->follow($second, 'tag', $tag->id);

        $this->notify($event);

        // Pre-fix only the first follower was mailed (the null dedup key
        // marked everyone else as already notified).
        $this->assertSame(1, $this->sentTo('first-follower@example.com'));
        $this->assertSame(1, $this->sentTo('second-follower@example.com'));
    }

    /** @test */
    public function follower_of_two_tags_receives_exactly_one_email(): void
    {
        $tagOne = Tag::factory()->create();
        $tagTwo = Tag::factory()->create();
        $event = Event::factory()->create();
        $event->tags()->attach([$tagOne->id, $tagTwo->id]);

        $user = $this->follower('two-tags@example.com');
        $this->follow($user, 'tag', $tagOne->id);
        $this->follow($user, 'tag', $tagTwo->id);

        $this->notify($event);

        $this->assertSame(1, $this->sentTo('two-tags@example.com'));
    }

    /** @test */
    public function follower_of_a_tag_and_a_linked_entity_receives_exactly_one_email(): void
    {
        $tag = Tag::factory()->create();
        $entity = Entity::factory()->create();
        $event = Event::factory()->create();
        $event->tags()->attach($tag->id);
        $event->entities()->attach($entity->id);

        $user = $this->follower('tag-and-entity@example.com');
        $this->follow($user, 'tag', $tag->id);
        $this->follow($user, 'entity', $entity->id);

        $this->notify($event);

        // Pre-fix the entity pass could not see tag-pass notifications
        // (they were filed under the null key) and double-mailed.
        $this->assertSame(1, $this->sentTo('tag-and-entity@example.com'));
    }

    /**
     * Stub Intervention's Image facade (same pattern as ImageHandlerTest) so
     * the real upload endpoint runs against the fake external disk.
     */
    private function stubImageFacade(): void
    {
        $mock = Mockery::mock();
        $mock->shouldReceive('fit')->andReturnSelf();
        $mock->shouldReceive('encode')->andReturnSelf();
        $mock->shouldReceive('save')->andReturnSelf();
        $mock->shouldReceive('destroy')->andReturnNull();
        $mock->shouldReceive('basePath')->andReturnUsing(function () {
            return tempnam(sys_get_temp_dir(), 'notifytest-');
        });

        Image::shouldReceive('make')->andReturn($mock);
    }

    /**
     * Upload-trigger regression: isset($event->photos) cached the relation
     * pre-attach, so `1 === count($event->photos)` read stale data — the
     * notification fired on the SECOND photo upload instead of the first.
     */
    private function uploadPhoto(Event $event, User $owner): void
    {
        $this->actingAs($owner, 'sanctum');
        $this->post('/api/events/'.$event->id.'/photos',
            ['file' => UploadedFile::fake()->image('flyer.jpg')],
            ['Accept' => 'application/json'])
            ->assertStatus(201);
    }

    /** @test */
    public function first_photo_upload_notifies_followers(): void
    {
        Storage::fake('external');
        $this->stubImageFacade();

        $owner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $tag = Tag::factory()->create();
        $event = Event::factory()->create([
            'created_by' => $owner->id,
            'start_at' => now()->addDays(2),
        ]);
        $event->tags()->attach($tag->id);

        $follower = $this->follower('first-photo@example.com');
        $this->follow($follower, 'tag', $tag->id);

        $this->uploadPhoto($event, $owner);

        $this->assertSame(1, $this->sentTo('first-photo@example.com'));
        $this->assertSame(1, $event->photos()->first()->is_primary);
    }

    /** @test */
    public function second_photo_upload_does_not_renotify(): void
    {
        Storage::fake('external');
        $this->stubImageFacade();

        $owner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $tag = Tag::factory()->create();
        $event = Event::factory()->create([
            'created_by' => $owner->id,
            'start_at' => now()->addDays(2),
        ]);
        $event->tags()->attach($tag->id);

        // The event already has a photo before this upload.
        $existing = Photo::factory()->create([
            'name' => 'existing.webp',
            'path' => 'photos/existing.webp',
            'thumbnail' => 'photos/tn-existing.webp',
            'is_primary' => 1,
            'created_by' => $owner->id,
            'updated_by' => null,
        ]);
        $event->addPhoto($existing);

        $follower = $this->follower('second-photo@example.com');
        $this->follow($follower, 'tag', $tag->id);

        $this->uploadPhoto($event, $owner);

        // Pre-fix this was the (only) case that sent mail.
        $this->assertSame(0, $this->sentTo('second-photo@example.com'));
    }

    /** @test */
    public function followers_with_instant_updates_disabled_are_not_notified(): void
    {
        $tag = Tag::factory()->create();
        $event = Event::factory()->create();
        $event->tags()->attach($tag->id);

        $muted = $this->follower('muted@example.com', 0);
        $active = $this->follower('active@example.com');
        $this->follow($muted, 'tag', $tag->id);
        $this->follow($active, 'tag', $tag->id);

        $this->notify($event);

        $this->assertSame(0, $this->sentTo('muted@example.com'));
        $this->assertSame(1, $this->sentTo('active@example.com'));
    }
}
