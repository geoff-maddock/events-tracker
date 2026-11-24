<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Keyboard and screen reader support in the page shell (#2411): skip link,
 * labelled navigation, the mobile drawer and modal dialogs.
 */
class ShellAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_the_first_link_on_a_page_skips_to_the_main_content(): void
    {
        $html = $this->get('/tags')->assertOk()->getContent();

        preg_match('/<body[^>]*>.*?<a ([^>]*)>([^<]*)<\/a>/s', $html, $firstLink);

        $this->assertStringContainsString('href="#main-content"', $firstLink[1]);
        $this->assertSame('Skip to main content', $firstLink[2]);
        $this->assertSame(1, preg_match_all('/<main id="main-content" tabindex="-1"/', $html));
    }

    public function test_every_navigation_landmark_is_labelled(): void
    {
        foreach (['/tags', '/help', '/tos', '/api-guide'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            preg_match_all('/<nav\b[^>]*>/', $html, $navs);
            $this->assertNotEmpty($navs[0], $url);

            foreach ($navs[0] as $nav) {
                $this->assertMatchesRegularExpression('/aria-label="[^"]+"/', $nav, $url);
            }
        }
    }

    public function test_the_closed_mobile_drawer_is_inert_and_a_dialog(): void
    {
        $html = $this->get('/tags')->assertOk()->getContent();

        $this->assertStringContainsString('<div id="mobile-sidebar" inert role="dialog" aria-modal="true" aria-label="Navigation menu"', $html);
        $this->assertStringContainsString('id="mobile-menu-toggle-top"', $html);
        $this->assertStringContainsString('aria-expanded="false" aria-controls="mobile-sidebar"', $html);
        $this->assertStringContainsString('id="mobile-sidebar-close"', $html);
        // one script owns the drawer; no inline handlers left behind
        $this->assertStringNotContainsString('closeMobileSidebar', $html);
        $this->assertStringNotContainsString('toggleMobileSidebar', $html);
    }

    public function test_dialogs_are_wired_for_focus_handling(): void
    {
        $this->assertStringContainsString("import './dialog-focus';", file_get_contents(resource_path('assets/js/app.js')));

        $modal = Blade::render('<x-ui.modal id="zz-modal" title="Zz">Body</x-ui.modal>');

        $this->assertStringContainsString('role="dialog"', $modal);
        $this->assertStringContainsString('aria-modal="true"', $modal);
        $this->assertStringContainsString('aria-labelledby="zz-modal-title"', $modal);
        $this->assertStringContainsString('aria-label="Close"', $modal);

        // the search overlay opens when its field is focused, so focus must not be handed back to it
        $this->get('/tags')->assertSee('data-focus-return="false"', false);
    }
}
