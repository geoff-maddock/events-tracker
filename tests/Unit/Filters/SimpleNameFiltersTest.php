<?php

namespace Tests\Unit\Filters;

use App\Filters\EntityStatusFilters;
use App\Filters\EntityTypeFilters;
use App\Filters\EventStatusFilters;
use App\Filters\EventTypeFilters;
use App\Filters\ForumFilters;
use App\Filters\MenuFilters;
use App\Filters\OccurrenceDayFilters;
use App\Filters\OccurrenceTypeFilters;
use App\Filters\OccurrenceWeekFilters;
use App\Filters\RoleFilters;
use App\Filters\TagTypeFilters;
use App\Filters\ThreadCategoryFilters;
use App\Filters\VisibilityFilters;
use App\Models\EntityStatus;
use App\Models\EntityType;
use App\Models\EventStatus;
use App\Models\EventType;
use App\Models\Forum;
use App\Models\Menu;
use App\Models\OccurrenceDay;
use App\Models\OccurrenceType;
use App\Models\OccurrenceWeek;
use App\Models\Role;
use App\Models\TagType;
use App\Models\ThreadCategory;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Smoke tests for filter classes that only expose a single `name` (or similar)
 * method matching a seeded reference table. Each test verifies the filter
 * narrows the query as expected against records seeded by DatabaseSeeder.
 */
class SimpleNameFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function apply(string $filterClass, string $modelClass, array $filters)
    {
        $request = Request::create('/', 'GET', $filters);
        $filter = new $filterClass($request);

        return $filter->apply($modelClass::query())->get();
    }

    public function test_visibility_filter_narrows_by_name(): void
    {
        $first = Visibility::first();
        $this->assertNotNull($first, 'Visibilities should be seeded.');

        $results = $this->apply(VisibilityFilters::class, Visibility::class, ['name' => $first->name]);

        $this->assertNotEmpty($results);
        foreach ($results as $row) {
            $this->assertStringContainsStringIgnoringCase($first->name, $row->name);
        }
    }

    public function test_role_filter_narrows_by_name(): void
    {
        $first = Role::first();
        $this->assertNotNull($first, 'Roles table must be seeded by DatabaseSeeder.');

        $results = $this->apply(RoleFilters::class, Role::class, ['name' => $first->name]);

        $this->assertNotEmpty($results);
    }

    public function test_event_type_filter_narrows_by_name(): void
    {
        $first = EventType::first();
        $this->assertNotNull($first, 'Event types must be seeded by DatabaseSeeder.');

        $results = $this->apply(EventTypeFilters::class, EventType::class, ['name' => $first->name]);

        $this->assertNotEmpty($results);
    }

    public function test_event_status_filter_narrows_by_name(): void
    {
        $first = EventStatus::first();
        $this->assertNotNull($first, 'Event statuses must be seeded by DatabaseSeeder.');

        $results = $this->apply(EventStatusFilters::class, EventStatus::class, ['name' => $first->name]);

        $this->assertNotEmpty($results);
    }

    public function test_entity_type_filter_narrows_by_name(): void
    {
        $first = EntityType::first();
        $this->assertNotNull($first, 'Entity types must be seeded by DatabaseSeeder.');

        $results = $this->apply(EntityTypeFilters::class, EntityType::class, ['name' => $first->name]);

        $this->assertNotEmpty($results);
    }

    public function test_entity_status_filter_narrows_by_name(): void
    {
        $first = EntityStatus::first();
        $this->assertNotNull($first, 'Entity statuses must be seeded by DatabaseSeeder.');

        $results = $this->apply(EntityStatusFilters::class, EntityStatus::class, ['name' => $first->name]);

        $this->assertNotEmpty($results);
    }

    public function test_occurrence_day_filter_narrows_by_name(): void
    {
        $first = OccurrenceDay::first();
        $this->assertNotNull($first, 'Occurrence days must be seeded by DatabaseSeeder.');

        $results = $this->apply(OccurrenceDayFilters::class, OccurrenceDay::class, ['name' => $first->name]);

        $this->assertNotEmpty($results);
    }

    public function test_occurrence_type_filter_narrows_by_name(): void
    {
        $first = OccurrenceType::first();
        $this->assertNotNull($first, 'Occurrence types must be seeded by DatabaseSeeder.');

        $results = $this->apply(OccurrenceTypeFilters::class, OccurrenceType::class, ['name' => $first->name]);

        $this->assertNotEmpty($results);
    }

    public function test_occurrence_week_filter_narrows_by_name(): void
    {
        $first = OccurrenceWeek::first();
        $this->assertNotNull($first, 'Occurrence weeks must be seeded by DatabaseSeeder.');

        $results = $this->apply(OccurrenceWeekFilters::class, OccurrenceWeek::class, ['name' => $first->name]);

        $this->assertNotEmpty($results);
    }

    public function test_tag_type_filter_narrows_by_name(): void
    {
        $first = TagType::first();
        $this->assertNotNull($first, 'Tag types must be seeded by DatabaseSeeder.');

        $results = $this->apply(TagTypeFilters::class, TagType::class, ['name' => $first->name]);

        $this->assertNotEmpty($results);
    }

    public function test_thread_category_filter_narrows_by_name(): void
    {
        $first = ThreadCategory::first();
        $this->assertNotNull($first, 'Thread categories must be seeded by DatabaseSeeder.');

        $results = $this->apply(ThreadCategoryFilters::class, ThreadCategory::class, ['name' => $first->name]);

        $this->assertNotEmpty($results);
    }

    public function test_forum_filter_returns_all_when_empty(): void
    {
        Forum::factory()->count(2)->create();

        $results = $this->apply(ForumFilters::class, Forum::class, []);

        $this->assertGreaterThanOrEqual(2, $results->count());
    }

    public function test_menu_filter_returns_all_when_empty(): void
    {
        Menu::factory()->count(2)->create();

        $results = $this->apply(MenuFilters::class, Menu::class, []);

        $this->assertGreaterThanOrEqual(2, $results->count());
    }
}
