<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The simple admin lists on the shared list-page scaffold (#2406): roles
 * stand in for groups, permissions, entity types and reviews, which are the
 * same code; menus differ in listing only public ones.
 */
class AdminListControlsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const FILTERS = 'internal_role_index/index/filters';

    private const SORT = 'internal_role_index/index/sort';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignGroup('admin');

        return $admin;
    }

    public function test_the_role_list_is_for_admins(): void
    {
        $this->actingAs(User::factory()->create())->get('/roles')->assertForbidden();
        $this->get('/roles/filter')->assertForbidden();
        $this->get('/roles/reset')->assertForbidden();
    }

    public function test_a_role_filter_is_remembered_and_reset(): void
    {
        $this->actingAs($this->admin());
        Role::create(['name' => 'Zzlistedrole', 'slug' => 'zzlistedrole', 'short' => 'x']);

        $this->post('/roles/filter', ['filters' => ['name' => 'Zzlisted'], 'sort' => 'roles.name', 'direction' => 'desc'])
            ->assertOk()->assertSee('Zzlistedrole')->assertDontSee('Promoter');
        $this->get('/roles')->assertOk()->assertSee('Zzlistedrole')->assertDontSee('Promoter');
        $this->assertSame('roles.name', session(self::SORT));

        $this->get('/roles/rpp-reset')->assertRedirect(route('roles.index'));
        $this->assertSame(['name' => 'Zzlisted'], session(self::FILTERS));
        $this->assertNull(session(self::SORT));

        $this->get('/roles/reset')->assertRedirect(route('roles.index'));
        $this->assertEmpty(session(self::FILTERS));
        $this->get('/roles')->assertSee('Zzlistedrole')->assertSee('Promoter');
    }

    public function test_the_menu_list_shows_public_menus_only(): void
    {
        Menu::factory()->create(['name' => 'Zzpublicmenu', 'visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        Menu::factory()->create(['name' => 'Zzprivatemenu', 'visibility_id' => Visibility::VISIBILITY_PRIVATE]);

        $this->get('/menus')->assertOk()->assertSee('Zzpublicmenu')->assertDontSee('Zzprivatemenu');
    }
}
