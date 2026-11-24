<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * Screen reader names on list pages (#2411): the sort selects and the buttons
 * that are only an icon.
 */
class ListAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_every_list_control_select_in_the_views_has_a_name(): void
    {
        $found = 0;

        foreach (Finder::create()->files()->in(resource_path('views'))->name('*.blade.php') as $file) {
            preg_match_all('/<select\b[^>]*\bname="(?:limit|sort|direction)"[^>]*>/', $file->getContents(), $selects);

            foreach ($selects[0] as $select) {
                $found++;
                $this->assertMatchesRegularExpression('/aria-label="[^"]+"/', $select, $file->getRelativePathname());
            }
        }

        $this->assertGreaterThan(40, $found);
    }

    public function test_the_actions_menu_button_of_the_event_list_has_a_name(): void
    {
        $html = $this->get('/events')->assertOk()->getContent();

        preg_match('/<button id="actions-menu-btn"[^>]*>/', $html, $button);

        $this->assertStringContainsString('aria-label="More actions"', $button[0]);
        $this->assertStringContainsString('aria-controls="actions-menu-dropdown"', $button[0]);
        $this->assertStringContainsString('id="actions-menu-dropdown"', $html);
    }

    public function test_an_event_without_a_photo_names_its_placeholder_link(): void
    {
        $event = Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC, 'start_at' => now()->addDays(2), 'name' => 'Zz Nameless Thumb']);

        $html = view('events.single-tw', ['event' => $event, 'signedIn' => false, 'user' => null])->render();
        $this->assertMatchesRegularExpression('/<a href="\/images\/event-placeholder\.png"[^>]*aria-label="Zz Nameless Thumb"/s', $html);
    }

    public function test_the_section_toggles_of_the_search_page_say_what_they_control(): void
    {
        Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC, 'start_at' => now()->addDays(2), 'name' => 'Zq Night']);

        // two letters: a name match, not the fulltext index (which can't see this test's transaction)
        $html = $this->get('/search?keyword=Zq')->assertOk()->getContent();
        preg_match_all('/<button\b[^>]*onclick="toggleSection\(\'([^\']+)\'\)"[^>]*>/', $html, $toggles, PREG_SET_ORDER);
        $this->assertNotEmpty($toggles);

        foreach ($toggles as [$button, $section]) {
            $this->assertStringContainsString('aria-controls="' . $section . '"', $button);
            $this->assertStringContainsString('aria-expanded="true"', $button);
            $this->assertMatchesRegularExpression('/aria-label="Show or hide \w+"/', $button);
            $this->assertStringContainsString('id="' . $section . '"', $html);
        }
    }
}
