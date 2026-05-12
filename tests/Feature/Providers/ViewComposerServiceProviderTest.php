<?php

namespace Tests\Feature\Providers;

use App\Models\Forum;
use App\Models\Menu;
use App\Models\Role;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ViewComposerServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget('roles');
        Cache::forget('hasForum');
        Cache::forget('menus');
    }

    public function test_rendering_partials_nav_populates_navigation_cache(): void
    {
        Forum::factory()->create();
        Menu::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);

        view('partials.nav')->render();

        $this->assertTrue(Cache::has('roles'));
        $this->assertTrue(Cache::has('hasForum'));
        $this->assertTrue(Cache::has('menus'));

        $this->assertSame(Role::count(), Cache::get('roles')->count());
        $this->assertGreaterThanOrEqual(1, Cache::get('hasForum'));
        $this->assertGreaterThanOrEqual(1, Cache::get('menus')->count());
    }

    public function test_cached_navigation_data_is_reused_on_second_render(): void
    {
        view('partials.nav')->render();
        $firstRoles = Cache::get('roles');

        // Inserting a role after first render must NOT affect the cached value
        Role::create([
            'name' => 'cache-test-role-'.uniqid(),
            'slug' => 'cache-test-role-'.uniqid(),
            'short' => 'cache test',
        ]);

        view('partials.nav')->render();
        $secondRoles = Cache::get('roles');

        $this->assertEquals($firstRoles->pluck('id'), $secondRoles->pluck('id'));
    }
}
