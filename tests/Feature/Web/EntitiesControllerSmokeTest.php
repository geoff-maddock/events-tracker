<?php

namespace Tests\Feature\Web;

use App\Models\Entity;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntitiesControllerSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['user_status_id' => UserStatus::ACTIVE]));
    }

    public function test_entities_index_renders(): void
    {
        Entity::factory()->count(2)->create();

        $this->get('/entities')->assertOk();
    }

    public function test_entities_show_renders(): void
    {
        $entity = Entity::factory()->create();

        $this->get('/entities/'.$entity->slug)->assertOk();
    }

    public function test_entities_create_renders(): void
    {
        $this->get('/entities/create')->assertOk();
    }

    public function test_entities_tag_index_renders(): void
    {
        $tag = Tag::factory()->create(['slug' => 'zz-entity-tag']);
        $entity = Entity::factory()->create();
        $entity->tags()->attach($tag->id);

        $this->get('/entities/tag/zz-entity-tag')->assertOk();
    }

    public function test_entities_role_index_renders(): void
    {
        $role = Role::first();
        $this->assertNotNull($role);

        $this->get('/entities/role/'.$role->name)->assertOk();
    }

    public function test_entities_reset_redirects(): void
    {
        $this->get('/entities/reset')->assertRedirect('/entities');
    }
}
