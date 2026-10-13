<?php

namespace Tests\Feature\Web;

use App\Models\Group;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The desktop sidebar is pinned to the viewport height (#2309). The account
 * links and theme toggle share one footer row, and logged-in account actions
 * sit in a popover there, so make sure they're all still reachable.
 */
class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_a_guest_sees_the_event_shortcuts_and_sign_in_links(): void
    {
        $sidebar = $this->desktopSidebar($this->get('/events')->assertOk()->getContent());

        foreach (['/events/tonight', '/events/this-weekend', '/events/this-week', '/events/grid', '/login', '/register'] as $path) {
            $this->assertStringContainsString('href="'.url($path).'"', $sidebar, $path);
        }
        $this->assertStringNotContainsString('All Modules', $sidebar);
        $this->assertStringNotContainsString('action="'.route('logout').'"', $sidebar);
    }

    public function test_an_admin_can_reach_profile_modules_and_log_out(): void
    {
        $admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $admin->groups()->attach(Group::firstOrCreate(['name' => 'admin'])->id);

        $sidebar = $this->desktopSidebar($this->actingAs($admin)->get('/events')->assertOk()->getContent());

        $this->assertStringContainsString('href="'.route('radar').'"', $sidebar);
        $this->assertStringContainsString('href="'.url('/users/'.$admin->id).'"', $sidebar);
        $this->assertStringContainsString('href="'.route('pages.allModules').'"', $sidebar);
        $this->assertStringContainsString('action="'.route('logout').'"', $sidebar);
        $this->assertStringNotContainsString('href="'.url('/login').'"', $sidebar);
    }

    /**
     * The mobile sidebar and topbar repeat most of these links, so look only at
     * the desktop <aside>.
     */
    private function desktopSidebar(string $html): string
    {
        $start = strpos($html, 'sidebar-desktop-tw');
        $this->assertNotFalse($start, 'desktop sidebar not rendered');
        $end = strpos($html, '</aside>', $start);
        $this->assertNotFalse($end);

        return substr($html, $start, $end - $start);
    }
}
