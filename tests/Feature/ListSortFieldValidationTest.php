<?php

namespace Tests\Feature;

use App\Filters\EntityFilters;
use App\Filters\UserFilters;
use App\Http\Requests\ListQueryParameters;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\Follow;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

/**
 * Lists without a sort allowlist used to pass any identifier-shaped sort
 * straight to orderBy(): an unknown column 500'd every list it was stored for
 * (EVENTREPO-YT, `popularity` on /entities). The builder now only orders by
 * something the query can actually sort on, and lists can name friendly aliases.
 */
class ListSortFieldValidationTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @param array<string, string> $aliases
     */
    private function build(Builder $query, $filter, ?string $sortField, array $defaultSort, array $aliases = [])
    {
        $params = Mockery::mock(ListQueryParameters::class);
        $params->shouldReceive('getFilters')->andReturn([]);
        $params->shouldReceive('getIsEmptyFilter')->andReturn(false);
        $params->shouldReceive('getSortFieldName')->andReturn($sortField);
        $params->shouldReceive('getSortDirection')->andReturn('desc');
        $params->shouldReceive('getLimit')->andReturn(25);
        $params->shouldReceive('getPage')->andReturn(1);

        $builder = new ListEntityResultBuilder($params);
        $builder->setQueryBuilder($query)
            ->setFilter($filter)
            ->setDefaultSort($defaultSort)
            ->setSortAliases($aliases);
        $builder->setMultiSort([]);
        $builder->setDefaultLimit(25);

        return $builder->listResultSetFactory();
    }

    private function entities(?string $sortField, array $aliases = [])
    {
        $query = Entity::query()
            ->leftJoin('entity_types', 'entities.entity_type_id', '=', 'entity_types.id')
            ->select('entities.*')
            ->addSelect(DB::raw('(select count(*) from follows where follows.object_id = entities.id and follows.object_type = \'entity\') as popularity_score'));

        return $this->build($query, app(EntityFilters::class), $sortField, ['entities.name' => 'asc'], $aliases);
    }

    public function test_an_unknown_column_falls_back_to_the_default(): void
    {
        foreach (['namexh3probe9', 'popularity', 'entities.namexh3probe9', 'entity_types.bogus', 'nottable.name'] as $field) {
            $result = $this->entities($field);
            $this->assertSame('entities.name', $result->getSort(), $field);
            $result->getList()->get();
        }
    }

    public function test_real_joined_and_computed_columns_are_kept(): void
    {
        foreach (['name', 'entities.created_at', 'entity_types.name', 'popularity_score'] as $field) {
            $result = $this->entities($field);
            $this->assertSame($field, $result->getSort());
            $result->getList()->get();
        }
    }

    public function test_an_alias_sorts_by_the_field_it_names(): void
    {
        $result = $this->entities('popularity', ['popularity' => 'popularity_score']);

        $this->assertSame('popularity_score', $result->getSort());
        $result->getList()->get();
    }

    public function test_relation_counts_need_a_declared_relation(): void
    {
        $entity = Entity::factory()->create();

        $result = $this->entities('follows_count');
        $this->assertSame('follows_count', $result->getSort());
        $result->getList()->get();

        // not relations: withCount() would call these methods by name
        foreach (['foo_count', 'delete_count', 'getTable_count'] as $field) {
            $result = $this->entities($field);
            $this->assertSame('entities.name', $result->getSort(), $field);
            $result->getList()->get();
        }
        $this->assertNotNull($entity->fresh());
    }

    public function test_hidden_columns_cannot_be_sorted_on(): void
    {
        $result = $this->build(User::query(), app(UserFilters::class), 'password', ['users.name' => 'asc']);

        $this->assertSame('users.name', $result->getSort());
    }

    public function test_the_entities_page_sorts_by_popularity(): void
    {
        $this->withExceptionHandling();
        $followers = User::factory()->count(3)->create(['user_status_id' => UserStatus::ACTIVE]);
        $popular = Entity::factory()->create(['name' => 'Zz Popular Collective', 'entity_status_id' => EntityStatus::ACTIVE]);
        $quiet = Entity::factory()->create(['name' => 'Aa Quiet Collective', 'entity_status_id' => EntityStatus::ACTIVE]);
        foreach ($followers as $user) {
            Follow::create(['user_id' => $user->id, 'object_type' => 'entity', 'object_id' => $popular->id]);
        }

        $html = $this->get('/entities?sort=popularity&direction=desc&limit=100')->assertOk()->getContent();

        $this->assertNotFalse(strpos($html, $popular->name));
        $this->assertNotFalse(strpos($html, $quiet->name));
        $this->assertLessThan(strpos($html, $quiet->name), strpos($html, $popular->name), 'the followed entity should list first');
    }
}
