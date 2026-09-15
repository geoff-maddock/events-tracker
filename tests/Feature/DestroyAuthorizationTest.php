<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\Event;
use App\Models\Link;
use App\Models\Location;
use App\Models\Photo;
use App\Models\Series;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Web destroy routes that previously carried no auth middleware and no
 * ownership check, so a guest could permanently delete content (#2101).
 *
 * The primary signal is that the row survives a denied request.
 */
class DestroyAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    /**
     * CheckBanned redirects anyone who is not active, which would mask the
     * guard under test, so acting users are pinned to ACTIVE.
     */
    private function makeUser(?string $group = null): User
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        if ($group) {
            $user->assignGroup($group);
        }

        return $user;
    }

    // Events

    public function test_guest_cannot_destroy_an_event(): void
    {
        $event = Event::factory()->create();

        $this->delete(route('events.destroy', $event))->assertRedirect();

        $this->assertDatabaseHas('events', ['id' => $event->id]);
    }

    public function test_non_owner_cannot_destroy_an_event(): void
    {
        $event = Event::factory()->create(['created_by' => $this->makeUser()->id]);

        $this->actingAs($this->makeUser())
            ->delete(route('events.destroy', $event))
            ->assertForbidden();

        $this->assertDatabaseHas('events', ['id' => $event->id]);
    }

    public function test_owner_can_destroy_their_event(): void
    {
        $owner = $this->makeUser();
        $event = Event::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($owner)
            ->delete(route('events.destroy', $event))
            ->assertRedirect('/events');

        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    public function test_admin_can_destroy_an_event_they_do_not_own(): void
    {
        $event = Event::factory()->create(['created_by' => $this->makeUser()->id]);

        $this->actingAs($this->makeUser('admin'))
            ->delete(route('events.destroy', $event))
            ->assertRedirect('/events');

        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    // Series

    public function test_guest_cannot_destroy_a_series(): void
    {
        $series = Series::factory()->create();

        $this->delete(route('series.destroy', $series))->assertRedirect();

        $this->assertDatabaseHas('series', ['id' => $series->id]);
    }

    public function test_non_owner_cannot_destroy_a_series(): void
    {
        $series = Series::factory()->create(['created_by' => $this->makeUser()->id]);

        $this->actingAs($this->makeUser())
            ->delete(route('series.destroy', $series))
            ->assertForbidden();

        $this->assertDatabaseHas('series', ['id' => $series->id]);
    }

    public function test_owner_can_destroy_their_series(): void
    {
        $owner = $this->makeUser();
        $series = Series::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($owner)
            ->delete(route('series.destroy', $series))
            ->assertRedirect('/series');

        $this->assertDatabaseMissing('series', ['id' => $series->id]);
    }

    // Entities

    public function test_guest_cannot_destroy_an_entity(): void
    {
        $entity = Entity::factory()->create();

        $this->delete(route('entities.destroy', $entity))->assertRedirect();

        $this->assertDatabaseHas('entities', ['id' => $entity->id]);
    }

    public function test_non_owner_cannot_destroy_an_entity(): void
    {
        $entity = Entity::factory()->create(['created_by' => $this->makeUser()->id]);

        $this->actingAs($this->makeUser())
            ->delete(route('entities.destroy', $entity))
            ->assertForbidden();

        $this->assertDatabaseHas('entities', ['id' => $entity->id]);
    }

    public function test_owner_can_destroy_their_entity(): void
    {
        $owner = $this->makeUser();
        $entity = Entity::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($owner)
            ->delete(route('entities.destroy', $entity))
            ->assertRedirect('/entities');

        $this->assertDatabaseMissing('entities', ['id' => $entity->id]);
    }

    // Photos

    public function test_guest_cannot_destroy_a_photo(): void
    {
        $photo = Photo::factory()->create();

        $this->delete('/photos/'.$photo->id)->assertRedirect();

        $this->assertDatabaseHas('photos', ['id' => $photo->id]);
    }

    public function test_unrelated_user_cannot_destroy_a_photo(): void
    {
        $photo = Photo::factory()->create(['created_by' => $this->makeUser()->id]);

        $this->actingAs($this->makeUser())
            ->delete('/photos/'.$photo->id)
            ->assertForbidden();

        $this->assertDatabaseHas('photos', ['id' => $photo->id]);
    }

    public function test_uploader_can_destroy_their_photo(): void
    {
        $uploader = $this->makeUser();
        $photo = Photo::factory()->create(['created_by' => $uploader->id]);

        $this->actingAs($uploader)->delete('/photos/'.$photo->id)->assertRedirect();

        $this->assertDatabaseMissing('photos', ['id' => $photo->id]);
    }

    public function test_event_owner_can_destroy_a_photo_on_their_event(): void
    {
        $owner = $this->makeUser();
        $event = Event::factory()->create(['created_by' => $owner->id]);
        $photo = Photo::factory()->create(['created_by' => $this->makeUser()->id]);
        $event->photos()->attach($photo->id);

        $this->actingAs($owner)->delete('/photos/'.$photo->id)->assertRedirect();

        $this->assertDatabaseMissing('photos', ['id' => $photo->id]);
    }

    // Entity sub-resources

    public function test_guest_cannot_destroy_an_entity_link(): void
    {
        $entity = Entity::factory()->create();
        $link = Link::factory()->create();
        $entity->links()->attach($link->id);

        $this->delete(route('entities.links.destroy', [$entity, $link]))->assertRedirect();

        $this->assertDatabaseHas('links', ['id' => $link->id]);
    }

    public function test_member_cannot_destroy_an_entity_link(): void
    {
        $entity = Entity::factory()->create();
        $link = Link::factory()->create();
        $entity->links()->attach($link->id);

        $this->actingAs($this->makeUser())
            ->delete(route('entities.links.destroy', [$entity, $link]))
            ->assertForbidden();

        $this->assertDatabaseHas('links', ['id' => $link->id]);
    }

    public function test_admin_can_destroy_an_entity_link(): void
    {
        $entity = Entity::factory()->create();
        $link = Link::factory()->create();
        $entity->links()->attach($link->id);

        $this->actingAs($this->makeUser('admin'))
            ->delete(route('entities.links.destroy', [$entity, $link]))
            ->assertRedirect();

        $this->assertDatabaseMissing('links', ['id' => $link->id]);
    }

    public function test_guest_and_member_cannot_destroy_an_entity_location(): void
    {
        $location = Location::factory()->create();
        $entity = Entity::find($location->entity_id);

        $this->delete(route('entities.locations.destroy', [$entity, $location]))->assertRedirect();
        $this->actingAs($this->makeUser())
            ->delete(route('entities.locations.destroy', [$entity, $location]))
            ->assertForbidden();

        $this->assertDatabaseHas('locations', ['id' => $location->id]);
    }

    public function test_guest_and_member_cannot_destroy_an_entity_contact(): void
    {
        $entity = Entity::factory()->create();
        $contact = new Contact();
        $contact->forceFill([
            'name' => 'Contact ZZ',
            'email' => 'contact-zz@example.com',
            'visibility_id' => Visibility::first()->id,
        ])->save();
        $entity->contacts()->attach($contact->id);

        $this->delete(route('entities.contacts.destroy', [$entity, $contact]))->assertRedirect();
        $this->actingAs($this->makeUser())
            ->delete(route('entities.contacts.destroy', [$entity, $contact]))
            ->assertForbidden();

        $this->assertDatabaseHas('contacts', ['id' => $contact->id]);
    }

    // Comments

    private function commentOn(Entity $entity, User $author): Comment
    {
        $comment = new Comment();
        $comment->forceFill([
            'message' => 'Comment ZZ',
            'commentable_id' => $entity->id,
            'commentable_type' => Entity::class,
            'created_by' => $author->id,
        ])->save();

        return $comment;
    }

    public function test_guest_cannot_destroy_a_comment(): void
    {
        $entity = Entity::factory()->create();
        $comment = $this->commentOn($entity, $this->makeUser());

        $this->delete(route('entities.comments.destroy', [$entity, $comment]))->assertRedirect();

        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }

    public function test_non_author_cannot_destroy_a_comment(): void
    {
        $entity = Entity::factory()->create();
        $comment = $this->commentOn($entity, $this->makeUser());

        $this->actingAs($this->makeUser())
            ->delete(route('entities.comments.destroy', [$entity, $comment]))
            ->assertForbidden();

        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }

    public function test_author_can_destroy_their_comment(): void
    {
        $entity = Entity::factory()->create();
        $author = $this->makeUser();
        $comment = $this->commentOn($entity, $author);

        $this->actingAs($author)
            ->delete(route('entities.comments.destroy', [$entity, $comment]))
            ->assertRedirect();

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }
}
