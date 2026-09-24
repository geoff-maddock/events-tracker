<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Entity;
use App\Models\Event;
use App\Models\EventReview;
use App\Models\Forum;
use App\Models\Link;
use App\Models\Location;
use App\Models\ReviewType;
use App\Models\Thread;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Update paths are authorized per record before anything is saved: threads,
 * forums (API), comments, links and locations (API), and event reviews.
 */
class UpdateAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function makeUser(?string $group = null): User
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE, 'email_verified_at' => now()]);
        if ($group) {
            $user->assignGroup($group);
        }

        return $user->fresh();
    }

    private function entityOwnedBy(User $user): Entity
    {
        $entity = Entity::factory()->create();
        $entity->owners()->attach($user->id);

        return $entity;
    }

    private function threadBy(User $user): Thread
    {
        $thread = Thread::factory()->create(['body' => 'original body', 'visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        // Thread's creating hook stamps the signed-in user (or 1), so set the owner afterwards
        $thread->forceFill(['created_by' => $user->id])->saveQuietly();

        return $thread->fresh();
    }

    private function threadPayload(Thread $thread, array $overrides = []): array
    {
        return array_merge([
            'name' => $thread->name,
            'body' => 'changed body',
            'visibility_id' => $thread->visibility_id,
            'forum_id' => $thread->forum_id,
        ], $overrides);
    }

    // Threads

    public function test_other_user_cannot_edit_a_thread(): void
    {
        $thread = $this->threadBy($this->makeUser());
        $other = $this->makeUser();

        $this->actingAs($other)->get(route('threads.edit', $thread))->assertForbidden();
        $this->actingAs($other)->put(route('threads.update', $thread), $this->threadPayload($thread))->assertForbidden();

        $this->assertSame('original body', $thread->fresh()->body);
    }

    public function test_author_can_edit_their_thread(): void
    {
        $author = $this->makeUser();
        $thread = $this->threadBy($author);

        $this->actingAs($author)->put(route('threads.update', $thread), $this->threadPayload($thread))->assertRedirect();

        $this->assertSame('changed body', $thread->fresh()->body);
    }

    // Forums (API): admin only

    public function test_non_admin_cannot_create_or_update_forums_via_api(): void
    {
        $member = $this->makeUser();
        // fixed name: the factory's random word can be under ForumRequest's 3-char minimum,
        // and validation runs before the controller's admin check
        $forum = Forum::factory()->create(['name' => 'ZZ Forum Name', 'created_by' => $member->id, 'description' => 'original']);

        $this->actingAs($member, 'sanctum')
            ->postJson('/api/forums', ['name' => 'ZZ Forum', 'slug' => 'zz-forum', 'visibility_id' => Visibility::VISIBILITY_PUBLIC])
            ->assertForbidden();
        $this->actingAs($member, 'sanctum')
            ->putJson('/api/forums/'.$forum->id, ['name' => $forum->name, 'slug' => 'renamed', 'visibility_id' => Visibility::VISIBILITY_PUBLIC, 'description' => 'changed'])
            ->assertForbidden();
        $this->actingAs($member, 'sanctum')
            ->patchJson('/api/forums/'.$forum->id, ['description' => 'changed'])
            ->assertForbidden();

        $this->assertDatabaseMissing('forums', ['slug' => 'zz-forum']);
        $this->assertSame('original', $forum->fresh()->description);
    }

    public function test_admin_can_patch_a_forum_via_api(): void
    {
        $forum = Forum::factory()->create(['description' => 'original']);

        $this->actingAs($this->makeUser('admin'), 'sanctum')
            ->patchJson('/api/forums/'.$forum->id, ['description' => 'changed'])
            ->assertOk();

        $this->assertSame('changed', $forum->fresh()->description);
    }

    // Comments

    public function test_new_comments_are_attributed_to_their_author(): void
    {
        $author = $this->makeUser();
        $entity = Entity::factory()->create();

        $this->actingAs($author)
            ->post(route('entities.comments.store', $entity), ['message' => 'first comment'])
            ->assertRedirect();

        $this->assertSame($author->id, (int) Comment::where('message', 'first comment')->value('created_by'));
    }

    public function test_other_user_cannot_edit_a_comment(): void
    {
        $author = $this->makeUser();
        $entity = Entity::factory()->create();
        $comment = $this->actingAs($author)->commentOn($entity, 'original message');
        $other = $this->makeUser();

        $this->actingAs($other)
            ->put(route('entities.comments.update', [$entity, $comment]), ['message' => 'defaced'])
            ->assertForbidden();

        $this->assertSame('original message', $comment->fresh()->message);
    }

    public function test_author_can_edit_only_the_comment_text(): void
    {
        $author = $this->makeUser();
        $entity = Entity::factory()->create();
        $elsewhere = Entity::factory()->create();
        $comment = $this->actingAs($author)->commentOn($entity, 'original message');

        $this->actingAs($author)
            ->put(route('entities.comments.update', [$entity, $comment]), [
                'message' => 'edited message',
                'commentable_id' => $elsewhere->id,
            ])
            ->assertRedirect(route('entities.show', $entity->getRouteKey()));

        $comment->refresh();
        $this->assertSame('edited message', $comment->message);
        $this->assertSame($entity->id, (int) $comment->commentable_id);
    }

    private function commentOn(Entity $entity, string $message): Comment
    {
        // created while signed in so the creating hook attributes it
        return Comment::create(['message' => $message, 'commentable_type' => 'entity', 'commentable_id' => $entity->id]);
    }

    // Links (API)

    public function test_link_store_requires_edit_rights_on_the_entity(): void
    {
        $owner = $this->makeUser();
        $entity = $this->entityOwnedBy($owner);
        $payload = ['text' => 'Bandcamp', 'url' => 'https://example.com/music', 'entity_id' => $entity->id];

        $this->actingAs($this->makeUser(), 'sanctum')->postJson('/api/links', $payload)->assertForbidden();
        $this->assertCount(0, $entity->links()->get());

        $this->actingAs($owner, 'sanctum')->postJson('/api/links', $payload)->assertCreated();
        $this->assertCount(1, $entity->links()->get());
    }

    public function test_other_user_cannot_update_a_link(): void
    {
        $owner = $this->makeUser();
        $entity = $this->entityOwnedBy($owner);
        $link = Link::factory()->create(['url' => 'https://example.com/original', 'text' => 'Original']);
        $entity->links()->attach($link->id);

        $this->actingAs($this->makeUser(), 'sanctum')
            ->putJson('/api/links/'.$link->id, ['text' => 'Phish', 'url' => 'https://evil.example/'])
            ->assertForbidden();
        $this->assertSame('https://example.com/original', $link->fresh()->url);

        $this->actingAs($owner, 'sanctum')
            ->putJson('/api/links/'.$link->id, ['text' => 'Updated', 'url' => 'https://example.com/updated'])
            ->assertOk();
        $this->assertSame('https://example.com/updated', $link->fresh()->url);
    }

    // Locations (API)

    public function test_other_user_cannot_change_a_location(): void
    {
        $owner = $this->makeUser();
        $entity = $this->entityOwnedBy($owner);
        $location = Location::factory()->create(['entity_id' => $entity->id, 'city' => 'Pittsburgh']);
        $other = $this->makeUser();
        // a past location of their own used to be enough to pass LocationRequest
        Location::factory()->create(['created_by' => $other->id]);

        $this->actingAs($other, 'sanctum')
            ->patchJson('/api/locations/'.$location->id, ['city' => 'Elsewhere'])
            ->assertForbidden();

        $this->assertSame('Pittsburgh', $location->fresh()->city);
    }

    public function test_owner_cannot_move_a_location_to_an_entity_they_do_not_own(): void
    {
        $owner = $this->makeUser();
        $entity = $this->entityOwnedBy($owner);
        $location = Location::factory()->create(['entity_id' => $entity->id]);
        $someoneElses = Entity::factory()->create();

        $this->actingAs($owner, 'sanctum')
            ->patchJson('/api/locations/'.$location->id, ['entity_id' => $someoneElses->id])
            ->assertForbidden();
        $this->assertSame($entity->id, (int) $location->fresh()->entity_id);

        $this->actingAs($owner, 'sanctum')
            ->patchJson('/api/locations/'.$location->id, ['city' => 'Pittsburgh'])
            ->assertOk();
        $this->assertSame('Pittsburgh', $location->fresh()->city);
    }

    // Reviews

    public function test_reviews_resource_no_longer_creates_events(): void
    {
        $member = $this->makeUser();
        $before = Event::count();

        $this->actingAs($member)->post('/reviews', ['name' => 'Spoofed', 'created_by' => 1])->assertStatus(405);
        $this->actingAs($member)->get('/reviews/create')->assertNotFound();

        $this->assertSame($before, Event::count());
    }

    public function test_review_edit_updates_the_review_for_its_author_only(): void
    {
        $author = $this->makeUser();
        $event = Event::factory()->create();
        $review = EventReview::create([
            'event_id' => $event->id,
            'user_id' => $author->id,
            'review_type_id' => ReviewType::query()->value('id'),
            'review' => 'original review',
            'attended' => 0,
            'confirmed' => 0,
        ]);
        $payload = ['review' => 'updated review', 'review_type_id' => $review->review_type_id];

        $this->actingAs($this->makeUser())->get(route('reviews.edit', $review))->assertForbidden();
        $this->actingAs($this->makeUser())
            ->put(route('events.reviews.update', [$event->id, $review->id]), $payload)
            ->assertForbidden();
        $this->assertSame('original review', $review->fresh()->review);

        $this->actingAs($author)->get(route('reviews.edit', $review))->assertOk();
        $this->actingAs($author)
            ->put(route('events.reviews.update', [$event->id, $review->id]), $payload)
            ->assertRedirect();

        $this->assertSame('updated review', $review->fresh()->review);
        $this->assertSame(1, EventReview::where('event_id', $event->id)->count(), 'editing must not create a second review');
    }
}
