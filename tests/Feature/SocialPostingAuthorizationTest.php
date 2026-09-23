<?php

namespace Tests\Feature;

use App\Jobs\Instagram\PostEventToInstagram;
use App\Models\Entity;
use App\Models\Event;
use App\Models\Photo;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use App\Notifications\EventPublished;
use App\Services\Integrations\Instagram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

/**
 * Posting to the site's Instagram/Twitter accounts: POST + auth, and only for
 * content the user may manage (public events for Instagram).
 */
class SocialPostingAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        $instagram = Mockery::mock(Instagram::class);
        $instagram->shouldReceive('getIgUserId')->andReturn(123)->byDefault();
        $instagram->shouldReceive('getPageAccessToken')->andReturn('token')->byDefault();
        $this->app->instance(Instagram::class, $instagram);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeUser(?string $group = null): User
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        if ($group) {
            $user->assignGroup($group);
        }

        return $user->fresh();
    }

    private function eventWithPhoto(User $owner, int $visibility = Visibility::VISIBILITY_PUBLIC): Event
    {
        $event = Event::factory()->create(['visibility_id' => $visibility, 'created_by' => $owner->id]);
        $photo = Photo::factory()->create([
            'is_primary' => 1,
            'path' => 'test.jpg',
            'thumbnail' => 'test_thumb.jpg',
            'created_by' => $owner->id,
        ]);
        $event->photos()->attach($photo->id);

        return $event;
    }

    public function test_instagram_routes_reject_get(): void
    {
        $event = $this->eventWithPhoto($this->makeUser());
        $entity = Entity::factory()->create();

        foreach ([
            '/events/'.$event->id.'/instagram-post',
            '/events/'.$event->id.'/instagram-post-single',
            '/events/'.$event->id.'/instagram-story-post',
            '/events/instagram-post-week',
            '/events/instagram-weekend-preview',
            '/events/instagram-todays-preview',
            '/events/'.$event->id.'/tweet',
            '/entities/'.$entity->id.'/instagram-post',
            '/entities/'.$entity->id.'/instagram-story-post',
            '/entities/'.$entity->id.'/tweet',
        ] as $url) {
            $this->assertContains($this->get($url)->status(), [404, 405], $url);
        }
    }

    public function test_guest_cannot_post_an_event_to_instagram(): void
    {
        Queue::fake();
        $event = $this->eventWithPhoto($this->makeUser());

        $this->post('/events/'.$event->id.'/instagram-post')->assertRedirect(route('login'));
        $this->post('/events/'.$event->id.'/instagram-post-single')->assertRedirect(route('login'));

        Queue::assertNotPushed(PostEventToInstagram::class);
    }

    public function test_non_owner_cannot_post_an_event_to_instagram(): void
    {
        Queue::fake();
        $event = $this->eventWithPhoto($this->makeUser());

        $this->actingAs($this->makeUser())
            ->postJson('/events/'.$event->id.'/instagram-post')
            ->assertStatus(422)
            ->assertJson(['success' => false]);
        $this->actingAs($this->makeUser())->post('/events/'.$event->id.'/instagram-post-single');

        Queue::assertNotPushed(PostEventToInstagram::class);
    }

    public function test_owner_cannot_post_a_private_event_to_instagram(): void
    {
        Queue::fake();
        $owner = $this->makeUser();
        $event = $this->eventWithPhoto($owner, Visibility::VISIBILITY_PRIVATE);

        $this->actingAs($owner)->postJson('/events/'.$event->id.'/instagram-post')->assertStatus(422);

        Queue::assertNotPushed(PostEventToInstagram::class);
    }

    public function test_owner_can_post_a_public_event_to_instagram(): void
    {
        Queue::fake();
        $owner = $this->makeUser();
        $event = $this->eventWithPhoto($owner);

        $this->actingAs($owner)->postJson('/events/'.$event->id.'/instagram-post')->assertOk();

        Queue::assertPushed(PostEventToInstagram::class);
    }

    public function test_week_post_is_super_admin_only(): void
    {
        $this->actingAs($this->makeUser())
            ->post('/events/instagram-post-week')
            ->assertRedirect()
            ->assertSessionHas('flash_message.level', 'error');
    }

    public function test_non_owner_cannot_post_an_entity_to_instagram(): void
    {
        $entity = Entity::factory()->create();

        foreach (['instagram-post', 'instagram-story-post'] as $action) {
            $this->post('/entities/'.$entity->id.'/'.$action)->assertRedirect(route('login'));
        }

        $member = $this->makeUser();
        foreach (['instagram-post', 'instagram-story-post'] as $action) {
            $this->actingAs($member)
                ->post('/entities/'.$entity->id.'/'.$action)
                ->assertRedirect()
                ->assertSessionHas('flash_message.message', 'You are not authorized to post this entity to Instagram.');
        }
    }

    public function test_non_owner_cannot_tweet_an_event_or_entity(): void
    {
        Notification::fake();
        $event = $this->eventWithPhoto($this->makeUser());
        $entity = Entity::factory()->create();
        $member = $this->makeUser();

        $this->post('/events/'.$event->id.'/tweet')->assertRedirect(route('login'));
        $this->actingAs($member)->post('/events/'.$event->id.'/tweet')->assertRedirect();
        $this->actingAs($member)->post('/entities/'.$entity->id.'/tweet')->assertRedirect();

        Notification::assertNothingSent();
    }
}
