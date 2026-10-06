<?php

namespace Tests\Feature;

use App\Models\EntityType;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The entity type page was the last one on the legacy Bootstrap layout (#2184).
 */
class EntityTypeShowTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_the_page_uses_the_tailwind_layout(): void
    {
        $type = EntityType::create(['name' => 'Zz Collective', 'slug' => 'zz-collective', 'short' => 'A group of artists']);

        $this->get("/entity-types/{$type->id}")->assertOk()
            ->assertViewIs('entityTypes.show-tw')
            ->assertSee('Zz Collective')
            ->assertSee('A group of artists')
            ->assertDontSee('Delete Entity Type');
    }

    public function test_an_admin_gets_a_confirmed_delete(): void
    {
        $type = EntityType::create(['name' => 'Zz Collective', 'slug' => 'zz-collective', 'short' => 'Zz']);
        $admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $admin->assignGroup('admin');

        $this->actingAs($admin->fresh())->get("/entity-types/{$type->id}")->assertOk()
            ->assertSee('Delete Entity Type')
            ->assertSee('data-confirm="Are you sure you want to delete this entity type?"', false);
    }
}
