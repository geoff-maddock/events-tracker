<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The desktop sidebar and the mobile drawer take their links from one partial,
 * partials/nav-items-tw (#2410).
 */
class SidebarNavTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    /**
     * @return array{0: list<string>, 1: list<string>} the link targets of the desktop and mobile nav
     */
    private function navLinks(string $html): array
    {
        preg_match_all('/<nav aria-label="Main"[^>]*>(.*?)<\/nav>/s', $html, $navs);
        $this->assertCount(2, $navs[1]);

        return array_map(function (string $nav): array {
            preg_match_all('/<a href="([^"]+)"/', $nav, $links);

            return $links[1];
        }, $navs[1]);
    }

    public function test_both_sidebars_offer_the_same_links_to_a_guest(): void
    {
        [$desktop, $mobile] = $this->navLinks($this->get('/tags')->assertOk()->getContent());

        $this->assertSame($desktop, $mobile);
        $this->assertContains(url('/events/tonight'), $desktop);
        $this->assertContains(url('/threads/all'), $desktop);
        $this->assertNotContains(url('/users'), $desktop);
        $this->assertNotContains(route('radar'), $desktop);
    }

    public function test_both_sidebars_offer_the_same_links_when_signed_in(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        [$desktop, $mobile] = $this->navLinks($this->actingAs($user)->get('/tags')->assertOk()->getContent());

        $this->assertSame($desktop, $mobile);
        $this->assertContains(url('/users'), $desktop);
        $this->assertContains(route('radar'), $desktop);
    }

    public function test_the_current_page_is_marked_in_both_sidebars(): void
    {
        $html = $this->get('/threads')->assertOk()->getContent();

        $marked = '/href="'.preg_quote(url('/threads/all'), '/').'" class="nav-item-tw nav-item-active-tw"\s+aria-current="page"/';

        $this->assertSame(2, preg_match_all($marked, $html));
        $this->assertSame(2, substr_count($html, 'aria-current="page"'));
    }
}
