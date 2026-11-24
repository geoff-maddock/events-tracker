<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\Role;
use App\Models\Tag;
use App\Services\EntitySchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The JSON-LD of an entity page is built by App\Services\EntitySchema (#2409).
 */
class EntitySchemaTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function entityWithRole(?string $role, array $attrs = []): Entity
    {
        $entity = Entity::factory()->create($attrs + ['entity_status_id' => EntityStatus::ACTIVE, 'short' => 'A short line.']);
        if ($role !== null) {
            $entity->roles()->attach(Role::where('name', $role)->firstOrFail());
        }

        return $entity->fresh();
    }

    public function test_the_type_follows_the_role(): void
    {
        $this->assertSame('MusicVenue', EntitySchema::type($this->entityWithRole('Venue')));
        $this->assertSame('MusicGroup', EntitySchema::type($this->entityWithRole('Band')));
        $this->assertSame('MusicGroup', EntitySchema::type($this->entityWithRole('DJ')));
        $this->assertSame('Organization', EntitySchema::type($this->entityWithRole('Promoter')));
    }

    public function test_the_document_describes_the_entity(): void
    {
        $entity = $this->entityWithRole('Band', ['name' => 'Zz Test Band', 'slug' => 'zz-test-band', 'instagram_username' => 'zztestband']);
        $entity->tags()->attach(Tag::factory()->create(['name' => 'Zzgenre'])->id);
        $entity = $entity->fresh();

        $document = EntitySchema::document($entity);

        $this->assertSame('MusicGroup', $document['@type']);
        $this->assertSame('Zz Test Band', $document['name']);
        $this->assertSame(url('/entities/zz-test-band'), $document['url']);
        $this->assertSame(url('/entities/zz-test-band').'#entity', $document['@id']);
        $this->assertSame('A short line.', $document['description']);
        $this->assertSame(['Zzgenre'], $document['genre']);
        $this->assertContains('https://www.instagram.com/zztestband', $document['sameAs']);
        $this->assertSame($document['sameAs'], EntitySchema::sameAs($entity));
    }

    public function test_the_breadcrumbs_pass_through_the_first_role(): void
    {
        $band = $this->entityWithRole('Band', ['name' => 'Zz Crumb Band']);
        $items = EntitySchema::breadcrumbs($band)['itemListElement'];

        $this->assertSame(['Entities', 'Band', 'Zz Crumb Band'], array_column($items, 'name'));
        $this->assertSame([1, 2, 3], array_column($items, 'position'));

        $plain = $this->entityWithRole(null, ['name' => 'Zz Plain']);
        $this->assertSame(['Entities', 'Zz Plain'], array_column(EntitySchema::breadcrumbs($plain)['itemListElement'], 'name'));
    }

    public function test_the_entity_page_carries_the_document(): void
    {
        $band = $this->entityWithRole('Band', ['name' => 'Zz Page Band']);

        $this->get('/entities/'.$band->slug)
            ->assertOk()
            ->assertSee('"@type": "MusicGroup"', false)
            ->assertSee('"@type": "BreadcrumbList"', false);
    }
}
