<?php

namespace Tests\Feature;

use App\Filters\PhotoFilters;
use App\Http\Requests\ListQueryParameters;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * The list pages take a sort field that flows into orderBy() via
 * ListEntityResultBuilder. A malformed value (e.g. a stray quote from a fuzz
 * probe or a broken link) previously reached the query and produced a SQLSTATE
 * 500; it must now fall back to the default sort column.
 */
class ListSortHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function buildList(?string $sortField, ?string $sortDirection = 'desc', array $allowedSortFields = [])
    {
        $params = Mockery::mock(ListQueryParameters::class);
        $params->shouldReceive('getFilters')->andReturn([]);
        $params->shouldReceive('getIsEmptyFilter')->andReturn(false);
        $params->shouldReceive('getSortFieldName')->andReturn($sortField);
        $params->shouldReceive('getSortDirection')->andReturn($sortDirection);
        $params->shouldReceive('getLimit')->andReturn(25);
        $params->shouldReceive('getPage')->andReturn(1);

        $builder = new ListEntityResultBuilder($params);
        $builder->setQueryBuilder(Photo::query())
            ->setFilter(app(PhotoFilters::class))
            ->setAllowedSortFields($allowedSortFields)
            ->setDefaultSort(['photos.created_at' => 'desc']);
        $builder->setMultiSort([]);
        $builder->setDefaultLimit(25);

        return $builder->listResultSetFactory();
    }

    public function test_malformed_sort_field_falls_back_and_query_runs(): void
    {
        $result = $this->buildList("created_at'");

        // Fell back to the safe default rather than the malformed input...
        $this->assertSame('photos.created_at', $result->getSort());
        // ...and the built query executes without a bad-column SQLSTATE error.
        $result->getList()->get();
        $this->assertTrue(true);
    }

    public function test_malformed_sort_direction_falls_back_and_query_runs(): void
    {
        // A bad direction (e.g. from a stale session or fuzz probe) must normalize
        // to a safe value rather than throwing "Order direction must be 'asc' or
        // 'desc'" (EVENTREPO-V4 on /photos, EVENTREPO-TD on /series).
        $result = $this->buildList('photos.name', 'garbage');

        $this->assertSame('desc', $result->getSortDirection());
        $result->getList()->get();
        $this->assertTrue(true);
    }

    public function test_null_sort_field_does_not_break_set_sort(): void
    {
        // With no resolvable field and a multi-column default, appliedSortField is
        // null; setSort() must still receive a string (EVENTREPO-TB).
        $result = $this->buildList(null);

        $this->assertIsString($result->getSort());
        $result->getList()->get();
        $this->assertTrue(true);
    }

    public function test_valid_sort_field_is_preserved(): void
    {
        $result = $this->buildList('photos.name');

        $this->assertSame('photos.name', $result->getSort());
        $result->getList()->get();
        $this->assertTrue(true);
    }

    public function test_unknown_but_syntactically_valid_sort_field_falls_back_when_allowlisted(): void
    {
        // A value like ?sort=test passes the plain-identifier regex but is not a
        // real column, so without an allowlist it reached orderBy() and produced
        // "Unknown column 'test' in order clause" (EVENTREPO-YJ on /threads/all).
        // With the allowed sort fields configured it must fall back to the default.
        $result = $this->buildList('test', 'desc', ['photos.name', 'photos.created_at']);

        $this->assertSame('photos.created_at', $result->getSort());
        $result->getList()->get();
        $this->assertTrue(true);
    }

    public function test_allowlisted_sort_field_is_preserved(): void
    {
        $result = $this->buildList('photos.name', 'desc', ['photos.name', 'photos.created_at']);

        $this->assertSame('photos.name', $result->getSort());
        $result->getList()->get();
        $this->assertTrue(true);
    }
}
