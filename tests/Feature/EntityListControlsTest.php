<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\EntityType;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The filter, sort and reset controls of the entity lists, which run on the
 * shared list-page scaffold (#2406).
 */
class EntityListControlsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const FILTERS = 'internal_entity_index/index/filters';

    private const SORT = 'internal_entity_index/index/sort';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        $venue = Role::where('slug', 'venue')->firstOrFail();
        foreach (['Alpha Listed Entity', 'Beta Listed Entity'] as $name) {
            Entity::factory()->create([
                'name' => $name,
                'entity_type_id' => EntityType::SPACE,
                'entity_status_id' => EntityStatus::ACTIVE,
            ])->roles()->attach($venue->id);
        }
    }

    private function filterByName(): void
    {
        $this->post('/entities/filter', [
            'filters' => ['name' => 'Alpha Listed'],
            'sort' => 'entities.name',
            'direction' => 'asc',
        ])->assertOk()->assertSee('Alpha Listed Entity')->assertDontSee('Beta Listed Entity');
    }

    public function test_a_filter_is_remembered_for_the_next_visit(): void
    {
        $this->filterByName();

        $this->get('/entities')->assertOk()->assertSee('Alpha Listed Entity')->assertDontSee('Beta Listed Entity');
        $this->assertSame(['name' => 'Alpha Listed'], session(self::FILTERS));
    }

    public function test_reset_clears_the_filter_and_the_sort(): void
    {
        $this->filterByName();

        $this->get('/entities/reset')->assertRedirect(route('entities.index'));

        $this->assertEmpty(session(self::FILTERS));
        $this->assertNull(session(self::SORT));
    }

    public function test_rpp_reset_clears_the_sort_and_keeps_the_filter(): void
    {
        $this->filterByName();

        $this->get('/entities/rpp-reset')->assertRedirect(route('entities.index'));

        $this->assertSame(['name' => 'Alpha Listed'], session(self::FILTERS));
        $this->assertNull(session(self::SORT));
    }

    public function test_the_role_list_sorts_by_popularity_under_either_name(): void
    {
        // "popularity" is the name the sort links use; the column is popularity_score
        foreach (['popularity', 'popularity_score'] as $sort) {
            $this->get('/entities/role/venue?sort='.$sort.'&direction=desc')
                ->assertOk()
                ->assertSee('Alpha Listed Entity')
                ->assertSee('Beta Listed Entity');
        }
    }

    public function test_a_shared_filter_url_is_applied(): void
    {
        $this->get('/entities/apply-filter?filters[name]=Beta+Listed')->assertRedirect(route('entities.filter'));

        $this->get('/entities/filter')->assertOk()->assertSee('Beta Listed Entity')->assertDontSee('Alpha Listed Entity');
    }
}
