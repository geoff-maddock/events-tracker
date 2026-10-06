<?php

namespace Tests\Feature;

use App\Filters\EntityFilters;
use App\Filters\EventFilters;
use App\Models\Entity;
use App\Models\Event;
use App\Models\Tag;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * filters[...] names and values come straight from the query string. Array
 * values hitting a ?string filter method, and names matching QueryFilter's own
 * methods, used to throw and 500 the list pages.
 */
class QueryFilterInputTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_array_values_on_string_filters_do_not_500(): void
    {
        foreach (['/entities', '/events', '/series', '/tags', '/threads', '/blogs', '/photos', '/forums', '/posts', '/users'] as $list) {
            foreach (['filters[id][]=1', 'filters[name][]=x', 'filters[name][a][b]=x'] as $query) {
                $status = $this->get("{$list}?{$query}")->status();
                $this->assertLessThan(500, $status, "{$list}?{$query} answered {$status}");
            }
        }
    }

    public function test_an_array_filter_kept_in_the_session_does_not_break_the_next_visit(): void
    {
        $this->get('/entities?filters[name][]=Zz')->assertOk();

        // no query string: the list reloads the stored filters
        $this->get('/entities')->assertOk();
    }

    public function test_filter_names_cannot_reach_the_filter_class_machinery(): void
    {
        foreach (['apply', 'applyFilters', 'filters', '__construct', 'applyFilter'] as $name) {
            $this->get("/entities?filters[{$name}]=x")->assertOk();
            $this->get("/events?filters[{$name}]=x")->assertOk();
        }
    }

    public function test_a_repeated_id_filter_works_as_an_id_list(): void
    {
        [$a, $b] = Entity::factory()->count(2)->create();
        Entity::factory()->create();

        $filters = new EntityFilters(Request::create('/', 'GET'));
        $ids = $filters->applyFilters(Entity::query(), ['id' => [$a->id, $b->id]])->pluck('entities.id')->sort()->values()->all();

        $this->assertSame(collect([$a->id, $b->id])->sort()->values()->all(), $ids);
    }

    public function test_array_filters_still_receive_arrays(): void
    {
        $tag = Tag::factory()->create(['name' => 'Zzfiltertag', 'slug' => 'zzfiltertag']);
        $tagged = Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        $tagged->tags()->attach($tag->id);
        Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);

        // tag() takes mixed, so the array reaches it untouched
        $filters = new EventFilters(Request::create('/', 'GET'));
        $ids = $filters->applyFilters(Event::query(), ['tag' => ['zzfiltertag']])->pluck('events.id')->all();

        $this->assertSame([$tagged->id], $ids);
    }
}
