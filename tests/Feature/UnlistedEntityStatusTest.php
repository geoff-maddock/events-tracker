<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\Group;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Unlisted entity status exists on fresh installs too (#2255): a migration
 * and the seeder now create it, so an unlisted entity's page works for the
 * admins allowed to see it.
 */
class UnlistedEntityStatusTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_the_unlisted_status_exists(): void
    {
        $this->assertSame('Unlisted', EntityStatus::find(EntityStatus::UNLISTED)?->name);
    }

    public function test_an_unlisted_entity_page_is_for_super_admins_only(): void
    {
        $this->withExceptionHandling();
        $entity = Entity::factory()->create(['slug' => 'zz-unlisted', 'name' => 'Zz Unlisted', 'entity_status_id' => EntityStatus::UNLISTED]);

        $this->get('/entities/zz-unlisted')->assertNotFound();

        $admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $admin->groups()->attach(Group::firstOrCreate(['name' => 'super_admin'])->id);
        $this->actingAs($admin)->get('/entities/zz-unlisted')->assertOk()->assertSee('Unlisted');
    }

    public function test_guests_do_not_see_unlisted_entities_in_the_list(): void
    {
        $this->withExceptionHandling();
        Entity::factory()->create(['name' => 'Zz Unlisted Listing', 'entity_status_id' => EntityStatus::UNLISTED]);

        $this->get('/entities?filters[name]=Zz+Unlisted')->assertOk()->assertDontSee('Zz Unlisted Listing');
    }
}
