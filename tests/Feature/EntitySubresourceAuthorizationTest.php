<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Entity;
use App\Models\Link;
use App\Models\Location;
use App\Models\LocationType;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Web create/store/edit/update/destroy on an entity's links, locations and
 * contacts previously only required login, so any member could edit any
 * entity's sub-resources (#2145).
 */
class EntitySubresourceAuthorizationTest extends TestCase
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

    private function ownedEntity(User $owner): Entity
    {
        return Entity::factory()->create(['created_by' => $owner->id]);
    }

    private function linkOn(Entity $entity): Link
    {
        $link = Link::factory()->create(['text' => 'Original link']);
        $entity->links()->attach($link->id);

        return $link;
    }

    private function contactOn(Entity $entity): Contact
    {
        $contact = new Contact();
        $contact->forceFill([
            'name' => 'Original contact',
            'email' => 'contact-zz@example.com',
            'visibility_id' => Visibility::first()->id,
        ])->save();
        $entity->contacts()->attach($contact->id);

        return $contact;
    }

    private function locationOn(Entity $entity): Location
    {
        return Location::factory()->create(['entity_id' => $entity->id, 'name' => 'Original location']);
    }

    private function locationInput(string $name): array
    {
        return [
            'name' => $name,
            'slug' => 'location-zz',
            'city' => 'Pittsburgh',
            'visibility_id' => Visibility::first()->id,
            'location_type_id' => LocationType::first()->id,
        ];
    }

    // Links

    public function test_guest_is_sent_to_login_for_link_forms(): void
    {
        $entity = $this->ownedEntity($this->makeUser());

        $this->get(route('entities.links.create', $entity))->assertRedirect('/login');
        $this->post(route('entities.links.store', $entity), ['text' => 'Spam link', 'url' => 'https://spam.example'])
            ->assertRedirect('/login');

        $this->assertDatabaseMissing('links', ['text' => 'Spam link']);
    }

    public function test_non_owner_cannot_create_or_edit_links(): void
    {
        $entity = $this->ownedEntity($this->makeUser());
        $link = $this->linkOn($entity);
        $this->actingAs($this->makeUser());

        $this->get(route('entities.links.create', $entity))->assertForbidden();
        $this->post(route('entities.links.store', $entity), ['text' => 'Spam link', 'url' => 'https://spam.example'])
            ->assertForbidden();
        $this->get(route('entities.links.edit', [$entity, $link]))->assertForbidden();
        $this->put(route('entities.links.update', [$entity, $link]), ['text' => 'Hijacked', 'url' => 'https://spam.example'])
            ->assertForbidden();
        $this->delete(route('entities.links.destroy', [$entity, $link]))->assertForbidden();

        $this->assertDatabaseMissing('links', ['text' => 'Spam link']);
        $this->assertDatabaseHas('links', ['id' => $link->id, 'text' => 'Original link']);
    }

    public function test_owner_can_create_edit_and_delete_links(): void
    {
        $owner = $this->makeUser();
        $entity = $this->ownedEntity($owner);
        $link = $this->linkOn($entity);
        $this->actingAs($owner);

        $this->get(route('entities.links.create', $entity))->assertOk();
        $this->post(route('entities.links.store', $entity), ['text' => 'New link', 'url' => 'https://new.example'])
            ->assertRedirect();
        $this->get(route('entities.links.edit', [$entity, $link]))->assertOk();
        $this->put(route('entities.links.update', [$entity, $link]), ['text' => 'Updated link', 'url' => 'https://new.example'])
            ->assertRedirect();

        $this->assertDatabaseHas('links', ['text' => 'New link']);
        $this->assertDatabaseHas('links', ['id' => $link->id, 'text' => 'Updated link']);

        $this->delete(route('entities.links.destroy', [$entity, $link]))->assertRedirect();
        $this->assertDatabaseMissing('links', ['id' => $link->id]);
    }

    public function test_owner_cannot_edit_another_entitys_link_through_their_own_entity(): void
    {
        $owner = $this->makeUser();
        $ownEntity = $this->ownedEntity($owner);
        $otherLink = $this->linkOn($this->ownedEntity($this->makeUser()));

        $this->actingAs($owner)
            ->put(route('entities.links.update', [$ownEntity, $otherLink]), ['text' => 'Hijacked', 'url' => 'https://spam.example'])
            ->assertNotFound();

        $this->assertDatabaseHas('links', ['id' => $otherLink->id, 'text' => 'Original link']);
    }

    public function test_admin_can_edit_any_entitys_link(): void
    {
        $entity = $this->ownedEntity($this->makeUser());
        $link = $this->linkOn($entity);

        $this->actingAs($this->makeUser('admin'))
            ->put(route('entities.links.update', [$entity, $link]), ['text' => 'Admin link', 'url' => 'https://new.example'])
            ->assertRedirect();

        $this->assertDatabaseHas('links', ['id' => $link->id, 'text' => 'Admin link']);
    }

    // Locations

    public function test_non_owner_cannot_create_or_edit_locations(): void
    {
        $entity = $this->ownedEntity($this->makeUser());
        $location = $this->locationOn($entity);
        $this->actingAs($this->makeUser());

        $this->get(route('entities.locations.create', $entity))->assertForbidden();
        $this->post(route('entities.locations.store', $entity), $this->locationInput('Spam location'))->assertForbidden();
        $this->get(route('entities.locations.edit', [$entity, $location]))->assertForbidden();
        $this->put(route('entities.locations.update', [$entity, $location]), $this->locationInput('Hijacked'))->assertForbidden();
        $this->delete(route('entities.locations.destroy', [$entity, $location]))->assertForbidden();

        $this->assertDatabaseMissing('locations', ['name' => 'Spam location']);
        $this->assertDatabaseHas('locations', ['id' => $location->id, 'name' => 'Original location']);
    }

    public function test_owner_can_create_and_edit_locations(): void
    {
        $owner = $this->makeUser();
        $entity = $this->ownedEntity($owner);
        $location = $this->locationOn($entity);
        $this->actingAs($owner);

        $this->post(route('entities.locations.store', $entity), $this->locationInput('New location'))->assertRedirect();
        $this->put(route('entities.locations.update', [$entity, $location]), $this->locationInput('Updated location'))
            ->assertRedirect();

        $this->assertDatabaseHas('locations', ['entity_id' => $entity->id, 'name' => 'New location']);
        $this->assertDatabaseHas('locations', ['id' => $location->id, 'name' => 'Updated location']);
    }

    public function test_owner_cannot_edit_another_entitys_location_through_their_own_entity(): void
    {
        $owner = $this->makeUser();
        $ownEntity = $this->ownedEntity($owner);
        $otherLocation = $this->locationOn($this->ownedEntity($this->makeUser()));

        $this->actingAs($owner)
            ->put(route('entities.locations.update', [$ownEntity, $otherLocation]), $this->locationInput('Hijacked'))
            ->assertNotFound();

        $this->assertDatabaseHas('locations', ['id' => $otherLocation->id, 'name' => 'Original location']);
    }

    // Contacts

    public function test_non_owner_cannot_create_or_edit_contacts(): void
    {
        $entity = $this->ownedEntity($this->makeUser());
        $contact = $this->contactOn($entity);
        $input = ['name' => 'Hijacked', 'type' => 'booking', 'visibility_id' => Visibility::first()->id];
        $this->actingAs($this->makeUser());

        $this->post(route('entities.contacts.store', $entity), ['name' => 'Spam contact', 'visibility_id' => Visibility::first()->id])
            ->assertForbidden();
        $this->get(route('entities.contacts.edit', [$entity, $contact]))->assertForbidden();
        $this->put(route('entities.contacts.update', [$entity, $contact]), $input)->assertForbidden();
        $this->delete(route('entities.contacts.destroy', [$entity, $contact]))->assertForbidden();

        $this->assertDatabaseMissing('contacts', ['name' => 'Spam contact']);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'name' => 'Original contact']);
    }

    public function test_owner_can_create_and_edit_contacts(): void
    {
        $owner = $this->makeUser();
        $entity = $this->ownedEntity($owner);
        $contact = $this->contactOn($entity);
        $this->actingAs($owner);

        $this->post(route('entities.contacts.store', $entity), ['name' => 'New contact', 'visibility_id' => Visibility::first()->id])
            ->assertRedirect();
        $this->put(route('entities.contacts.update', [$entity, $contact]), ['name' => 'Updated contact', 'type' => 'booking', 'visibility_id' => Visibility::first()->id])
            ->assertRedirect();

        $this->assertDatabaseHas('contacts', ['name' => 'New contact']);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'name' => 'Updated contact']);
    }

    public function test_owner_cannot_edit_another_entitys_contact_through_their_own_entity(): void
    {
        $owner = $this->makeUser();
        $ownEntity = $this->ownedEntity($owner);
        $otherContact = $this->contactOn($this->ownedEntity($this->makeUser()));

        $this->actingAs($owner)
            ->put(route('entities.contacts.update', [$ownEntity, $otherContact]), ['name' => 'Hijacked', 'type' => 'booking', 'visibility_id' => Visibility::first()->id])
            ->assertNotFound();

        $this->assertDatabaseHas('contacts', ['id' => $otherContact->id, 'name' => 'Original contact']);
    }
}
