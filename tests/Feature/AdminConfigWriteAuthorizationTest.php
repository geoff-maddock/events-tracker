<?php

namespace Tests\Feature;

use App\Models\EntityType;
use App\Models\Forum;
use App\Models\Menu;
use App\Models\ThreadCategory;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menus, thread categories and entity types are site configuration: creating
 * or editing one is admin-only, like deleting one (#2163, follow-up to #2138).
 */
class AdminConfigWriteAuthorizationTest extends TestCase
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
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        if ($group) {
            $user->assignGroup($group);
        }

        return $user->fresh();
    }

    public function test_member_cannot_open_or_submit_config_forms(): void
    {
        $member = $this->makeUser();
        $menu = Menu::factory()->create();
        $category = ThreadCategory::factory()->create();
        $entityType = EntityType::query()->firstOrFail();

        foreach ([
            route('menus.create'), route('menus.edit', $menu),
            route('categories.create'), route('categories.edit', $category),
            route('entity-types.create'), route('entity-types.edit', $entityType),
        ] as $url) {
            $this->actingAs($member)->get($url)->assertForbidden();
        }

        $this->actingAs($member)->post(route('menus.store'), ['name' => 'ZZ Menu', 'slug' => 'zz-menu', 'body' => 'x', 'visibility_id' => Visibility::VISIBILITY_PUBLIC])->assertForbidden();
        $this->actingAs($member)->put(route('menus.update', $menu), ['name' => 'ZZ Renamed', 'slug' => 'zz-renamed', 'body' => 'x', 'visibility_id' => Visibility::VISIBILITY_PUBLIC])->assertForbidden();
        $this->actingAs($member)->post(route('categories.store'), ['name' => 'ZZ Category', 'forum_id' => Forum::factory()->create()->id])->assertForbidden();
        $this->actingAs($member)->put(route('categories.update', $category), ['name' => 'ZZ Renamed', 'forum_id' => $category->forum_id])->assertForbidden();
        $this->actingAs($member)->post(route('entity-types.store'), ['name' => 'ZZ Type', 'slug' => 'zz-type', 'short' => 'ZZ short'])->assertForbidden();
        $this->actingAs($member)->put(route('entity-types.update', $entityType), ['name' => 'ZZ Renamed', 'slug' => $entityType->slug, 'short' => 'ZZ short'])->assertForbidden();

        $this->assertDatabaseMissing('menus', ['slug' => 'zz-menu']);
        $this->assertDatabaseMissing('menus', ['name' => 'ZZ Renamed']);
        $this->assertDatabaseMissing('thread_categories', ['name' => 'ZZ Category']);
        $this->assertDatabaseMissing('thread_categories', ['name' => 'ZZ Renamed']);
        $this->assertDatabaseMissing('entity_types', ['slug' => 'zz-type']);
        $this->assertNotSame('ZZ Renamed', $entityType->fresh()->name);
    }

    public function test_member_does_not_see_the_add_buttons(): void
    {
        $member = $this->makeUser();

        $this->actingAs($member)->get(route('menus.index'))->assertOk()->assertDontSee(route('menus.create'));
        $this->actingAs($member)->get(route('categories.index'))->assertOk()->assertDontSee(route('categories.create'));
        $this->actingAs($member)->get(route('entity-types.index'))->assertOk()->assertDontSee(route('entity-types.create'));
    }

    public function test_admin_can_create_and_update_config(): void
    {
        $admin = $this->makeUser('admin');
        $menu = Menu::factory()->create();

        $this->actingAs($admin)->get(route('menus.index'))->assertOk()->assertSee(route('menus.create'));
        $this->actingAs($admin)->post(route('menus.store'), ['name' => 'ZZ Menu', 'slug' => 'zz-menu', 'body' => 'x', 'visibility_id' => Visibility::VISIBILITY_PUBLIC])->assertRedirect();
        $this->actingAs($admin)->put(route('menus.update', $menu), ['name' => 'ZZ Renamed', 'slug' => 'zz-renamed', 'body' => 'x', 'visibility_id' => Visibility::VISIBILITY_PUBLIC])->assertRedirect();

        $this->assertDatabaseHas('menus', ['slug' => 'zz-menu']);
        $this->assertSame('ZZ Renamed', $menu->fresh()->name);
    }
}
