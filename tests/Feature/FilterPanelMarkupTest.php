<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * The filter section of a list page is the <x-filter-panel> component, opened
 * and closed by resources/assets/js/filter-panel.js (#2410).
 */
class FilterPanelMarkupTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_only_the_component_carries_the_filter_toggle_markup(): void
    {
        $using = 0;

        foreach (Finder::create()->files()->in(resource_path('views'))->name('*.blade.php') as $file) {
            $source = $file->getContents();
            $view = $file->getRelativePathname();
            $using += substr_count($source, '<x-filter-panel ');

            if ($view === 'components/filter-panel.blade.php') {
                continue;
            }

            $this->assertStringNotContainsString('id="filters-toggle-btn"', $source, "$view: use <x-filter-panel>");
            $this->assertStringNotContainsString("getElementById('filters-toggle-btn')", $source, "$view: has its own toggle handler");
        }

        $this->assertGreaterThan(10, $using);
        $this->assertStringContainsString("import './filter-panel';", file_get_contents(resource_path('assets/js/app.js')));
    }

    public function test_an_unfiltered_list_starts_with_its_filters_closed(): void
    {
        $html = $this->get('/events')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/id="filters-toggle-btn" data-has-filter="0" aria-expanded="false"/', $html);
        $this->assertMatchesRegularExpression('/id="filter-panel" class="hidden /', $html);
        $this->assertStringContainsString('action="'.route('events.filter').'"', $html);
        $this->assertStringNotContainsString('id="active-filters-badges"', $html);
        $this->assertStringNotContainsString('id="filters-reset-closed"', $html);
    }

    public function test_a_filtered_list_shows_badges_and_both_reset_links(): void
    {
        $response = $this->followingRedirects()->post(route('events.filter'), ['filters' => ['name' => 'Zzfiltered']])->assertOk();
        $reset = e(route('events.reset', ['redirect' => 'events.index', 'key' => 'internal_event_index']));

        $response->assertSee('data-has-filter="1"', false)
            ->assertSee('id="active-filters-badges"', false)
            ->assertSee('Name: Zzfiltered')
            ->assertSee('id="filters-reset-closed" href="'.$reset.'"', false)
            ->assertSee('id="filters-reset-open" href="'.$reset.'"', false)
            ->assertSee('id="copy-filter-url-btn"', false);
    }

    public function test_a_page_without_badges_opens_when_filtered(): void
    {
        $html = $this->get('/tags?search=zz')->assertOk()->getContent();

        $this->assertStringContainsString('data-has-filter="1" aria-expanded="true"', $html);
        $this->assertMatchesRegularExpression('/id="filter-panel" class=" bg-card/', $html);
        $this->assertStringContainsString('<form method="GET" action="'.url('/tags').'"', $html);
        // a GET filter form carries no CSRF token
        $this->assertSame(0, substr_count(explode('id="filter-panel"', $html)[1], 'name="_token"'));
    }

    public function test_a_reset_that_needs_a_post_is_a_button_for_its_own_form(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $html = $this->actingAs($user)->get(route('users.attending', $user->id))->assertOk()->getContent();

        $this->assertStringContainsString('<form id="filters-reset-form" method="POST" action="'.route('users.resetUserAttending', $user->id).'"', $html);
        $this->assertStringContainsString('form="filters-reset-form" id="filters-reset-open"', $html);
    }
}
