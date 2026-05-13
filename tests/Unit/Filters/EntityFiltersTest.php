<?php

namespace Tests\Unit\Filters;

use App\Filters\EntityFilters;
use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\EntityType;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EntityFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function applyFilters(array $filters, ?string $uri = '/')
    {
        $request = Request::create($uri, 'GET', $filters);
        $filter = new EntityFilters($request);

        return $filter->apply(Entity::query());
    }

    public function test_id_filter_matches_exact(): void
    {
        $entity = Entity::factory()->create();
        Entity::factory()->create();

        $results = $this->applyFilters(['id' => $entity->id])->get();

        $this->assertCount(1, $results);
    }

    public function test_id_filter_supports_comma_separated_list(): void
    {
        $a = Entity::factory()->create();
        $b = Entity::factory()->create();
        Entity::factory()->create();

        $results = $this->applyFilters(['id' => $a->id.','.$b->id])->get();

        $this->assertCount(2, $results);
    }

    public function test_name_filter_does_partial_match(): void
    {
        Entity::factory()->create(['name' => 'ZZ-The-Special-Band']);
        Entity::factory()->create(['name' => 'Other']);

        $results = $this->applyFilters(['name' => 'ZZ-The'])->get();

        $this->assertCount(1, $results);
    }

    public function test_description_filter_does_partial_match(): void
    {
        Entity::factory()->create(['description' => 'ZZUnique pattern in description']);
        Entity::factory()->create(['description' => 'unrelated']);

        $results = $this->applyFilters(['description' => 'ZZUnique'])->get();

        $this->assertCount(1, $results);
    }

    public function test_entity_status_filter_matches_by_name(): void
    {
        $status = EntityStatus::first();
        Entity::factory()->create(['entity_status_id' => $status->id]);

        $results = $this->applyFilters(['entity_status' => strtolower($status->name)])->get();

        $this->assertGreaterThanOrEqual(1, $results->count());
    }

    public function test_entity_type_filter_matches_by_slug(): void
    {
        $type = EntityType::first();
        Entity::factory()->create(['entity_type_id' => $type->id]);

        $results = $this->applyFilters(['entity_type' => $type->slug])->get();

        $this->assertGreaterThanOrEqual(1, $results->count());
    }

    public function test_tag_filter_matches_by_slug(): void
    {
        $tag = Tag::factory()->create(['slug' => 'zz-ent-tag']);
        $entity = Entity::factory()->create();
        $entity->tags()->attach($tag->id);
        Entity::factory()->create();

        $results = $this->applyFilters(['tag' => 'zz-ent-tag'])->get();

        $this->assertCount(1, $results);
    }

    public function test_tag_all_requires_every_tag(): void
    {
        $a = Tag::factory()->create(['slug' => 'zz-ea']);
        $b = Tag::factory()->create(['slug' => 'zz-eb']);
        $both = Entity::factory()->create();
        $both->tags()->attach([$a->id, $b->id]);
        $only = Entity::factory()->create();
        $only->tags()->attach($a->id);

        $results = $this->applyFilters(['tag_all' => 'zz-ea,zz-eb'])->get();

        $this->assertCount(1, $results);
    }

    public function test_role_filter_matches_role_name_case_insensitive(): void
    {
        $role = Role::first();
        $this->assertNotNull($role);
        $entity = Entity::factory()->create();
        $entity->roles()->attach($role->id);
        Entity::factory()->create();

        $results = $this->applyFilters(['role' => strtolower($role->name)])->get();

        $this->assertGreaterThanOrEqual(1, $results->count());
    }

    public function test_created_at_range_filter(): void
    {
        $within = Entity::factory()->create();
        DB::table('entities')->where('id', $within->id)
            ->update(['created_at' => Carbon::parse('2026-04-15')]);
        $outside = Entity::factory()->create();
        DB::table('entities')->where('id', $outside->id)
            ->update(['created_at' => Carbon::parse('2026-06-15')]);

        $results = $this->applyFilters([
            'created_at' => ['start' => '2026-04-01', 'end' => '2026-04-30'],
        ])->get();

        $this->assertTrue($results->pluck('id')->contains($within->id));
        $this->assertFalse($results->pluck('id')->contains($outside->id));
    }

    public function test_started_at_range_filter(): void
    {
        Entity::factory()->create(['started_at' => Carbon::parse('2026-04-01')]);
        Entity::factory()->create(['started_at' => Carbon::parse('2026-06-01')]);

        $results = $this->applyFilters([
            'started_at' => ['start' => '2026-04-01', 'end' => '2026-04-30'],
        ])->get();

        $this->assertCount(1, $results);
    }

    public function test_active_range_filter_is_no_op_on_web_requests(): void
    {
        Entity::factory()->count(2)->create();

        // Non-API URI — filter should be a passthrough.
        $results = $this->applyFilters(['active_range' => '1-month'], '/entities')->get();

        $this->assertGreaterThanOrEqual(2, $results->count());
    }

    public function test_active_range_filter_invalid_value_is_passthrough(): void
    {
        Entity::factory()->count(2)->create();

        $results = $this->applyFilters(['active_range' => 'not-a-real-period'], '/api/entities')->get();

        $this->assertGreaterThanOrEqual(2, $results->count());
    }

    public function test_display_type_all_is_passthrough(): void
    {
        Entity::factory()->count(2)->create();

        $results = $this->applyFilters(['display_type' => 'all'])->get();

        $this->assertGreaterThanOrEqual(2, $results->count());
    }

    public function test_display_type_created_matches_auth_user_entities(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $owned = Entity::factory()->create(['created_by' => $user->id]);
        Entity::factory()->create();

        $results = $this->applyFilters(['display_type' => 'created'])->get();

        $this->assertTrue($results->pluck('id')->contains($owned->id));
    }
}
