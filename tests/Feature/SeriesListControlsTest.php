<?php

namespace Tests\Feature;

use App\Models\Series;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The filter, sort and reset controls of the series lists, which run on the
 * shared list-page scaffold (#2406).
 */
class SeriesListControlsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const FILTERS = 'internal_series_index/index/filters';

    private const SORT = 'internal_series_index/index/sort';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        foreach (['Alpha Listed Series', 'Beta Listed Series'] as $name) {
            Series::factory()->create(['name' => $name, 'visibility_id' => Visibility::VISIBILITY_PUBLIC, 'cancelled_at' => null]);
        }
    }

    private function filterByName(): void
    {
        $this->post('/series/filter', [
            'filters' => ['name' => 'Alpha Listed'],
            'sort' => 'series.name',
            'direction' => 'asc',
        ])->assertOk()->assertSee('Alpha Listed Series')->assertDontSee('Beta Listed Series');
    }

    public function test_a_filter_is_remembered_for_the_next_visit(): void
    {
        $this->filterByName();

        $this->get('/series')->assertOk()->assertSee('Alpha Listed Series')->assertDontSee('Beta Listed Series');
        $this->assertSame(['name' => 'Alpha Listed'], session(self::FILTERS));
        $this->assertSame('series.name', session(self::SORT));
    }

    public function test_reset_clears_the_filter_and_the_sort(): void
    {
        $this->filterByName();

        $this->get('/series/reset')->assertRedirect(route('series.index'));

        $this->assertEmpty(session(self::FILTERS));
        $this->assertNull(session(self::SORT));
        $this->get('/series')->assertSee('Alpha Listed Series')->assertSee('Beta Listed Series');
    }

    public function test_rpp_reset_clears_the_sort_and_keeps_the_filter(): void
    {
        $this->filterByName();

        $this->get('/series/rpp-reset')->assertRedirect(route('series.index'));

        $this->assertSame(['name' => 'Alpha Listed'], session(self::FILTERS));
        $this->assertNull(session(self::SORT));
    }

    public function test_reset_of_another_list_leaves_this_one_alone_and_follows_the_redirect(): void
    {
        $this->filterByName();

        $this->get('/series/reset?key=internal_series_cancelled&redirect=series.cancelled')
            ->assertRedirect(route('series.cancelled'));

        $this->assertSame(['name' => 'Alpha Listed'], session(self::FILTERS));
    }

    public function test_a_shared_filter_url_is_applied(): void
    {
        $this->get('/series/apply-filter?filters[name]=Beta+Listed')->assertRedirect(route('series.filter'));

        $this->get('/series/filter')->assertOk()->assertSee('Beta Listed Series')->assertDontSee('Alpha Listed Series');
    }
}
