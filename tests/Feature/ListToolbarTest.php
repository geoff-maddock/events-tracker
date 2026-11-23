<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Series;
use App\Models\Tag;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * The result count, sort selects and page links above a list are the
 * <x-list-toolbar> component (#2410).
 */
class ListToolbarTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_the_list_pages_render_the_toolbar_above_and_below_the_list(): void
    {
        // the events grid and the series list only show the toolbar when they have rows
        Series::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC, 'cancelled_at' => null]);
        Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC, 'start_at' => now()->addDays(2)]);

        foreach (['/events', '/events/grid', '/entities', '/series', '/tags'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertSame(2, substr_count($html, 'name="limit" aria-label="Results per page"'), $url);
            $this->assertSame(2, substr_count($html, 'name="sort" aria-label="Sort by"'), $url);
            $this->assertSame(2, substr_count($html, 'name="direction" aria-label="Sort direction"'), $url);
            $this->assertStringContainsString('Showing ', $html, $url);
        }

        $this->get('/threads')->assertOk()->assertSee('name="sort" aria-label="Sort by"', false);
    }

    public function test_the_toolbar_marks_the_chosen_sort_and_the_current_page(): void
    {
        Tag::factory()->count(30)->create();

        $html = $this->get('/tags?limit=10&sort=created_at&direction=desc&page=2')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<option value="10" selected>/', $html);
        $this->assertMatchesRegularExpression('/<option value="desc" selected>/', $html);
        $this->assertMatchesRegularExpression('/<nav aria-label="Pages"/', $html);
        $this->assertMatchesRegularExpression('/aria-current="page"[^>]*>2<\/a>/', $html);
    }

    public function test_the_tag_toolbar_carries_the_search_with_a_sort_change(): void
    {
        $html = $this->get('/tags?search=zz')->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, '<input type="hidden" name="search" value="zz">'));
    }

    public function test_no_view_keeps_its_own_copy_of_the_toolbar(): void
    {
        foreach (Finder::create()->files()->in(resource_path('views'))->name('index-sort-pagination*') as $file) {
            $this->fail($file->getRelativePathname().' should be <x-list-toolbar>');
        }

        $this->assertFileExists(resource_path('views/components/list-toolbar.blade.php'));
    }
}
