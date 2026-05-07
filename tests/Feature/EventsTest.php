<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Tag;
use App\Models\User;
use App\Models\Events;
use Carbon\Carbon;
use Laravel\Dusk\Dusk;
use Tests\TestCase;
use Laravel\Dusk\Chrome;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EventsTest extends TestCase
{
    // refresh database and run migrations before test
    use RefreshDatabase;

    // reseed the database
    protected $seed = true;

    /**
     * Test that events are browsable
     *
     * @test void
     */
    public function eventsBrowsable()
    {
        $this->get('/events')->assertSee('Events');
    }

    public function testCreateFormAllowsUnspecifiedAgeLimit()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/events/create')
            ->assertOk()
            ->assertSee('No age limit specified')
            ->assertSee('option value="" selected', false);
    }

    /**
     * A basic functional test example.
     *
     * @return void
     */
    public function testBasicExample()
    {
        $response = $this->call('GET', '/');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testEventsGridFilterRoutesAreRegistered()
    {
        $this->assertEquals('/events/grid/tag/test-tag', route('events.grid.tag', ['slug' => 'test-tag'], false));
        $this->assertEquals('/events/grid/by-date/2026/4/15', route('events.grid.byDate', ['year' => 2026, 'month' => 4, 'day' => 15], false));
        $this->assertEquals('/events/grid/related-to/test-entity', route('events.grid.relatedto', ['slug' => 'test-entity'], false));
        $this->assertEquals('/events/grid/type/test-type', route('events.grid.type', ['slug' => 'test-type'], false));
        $this->assertEquals('/events/grid/series/test-series', route('events.grid.series', ['slug' => 'test-series'], false));
    }

    public function testGridTagRouteDisplaysParentFilterBreadcrumb()
    {
        $tag = Tag::factory()->create([
            'name' => 'GridParentTag',
            'slug' => 'grid-parent-tag',
        ]);

        $this->get(route('events.grid.tag', ['slug' => $tag->slug]))
            ->assertOk()
            ->assertSee('GridParentTag');
    }

    public function testGridRelatedRouteDisplaysParentFilterBreadcrumb()
    {
        $related = Entity::factory()->create([
            'name' => 'Grid Parent Entity',
            'slug' => 'grid-parent-entity',
        ]);

        $this->get(route('events.grid.relatedto', ['slug' => $related->slug]))
            ->assertOk()
            ->assertSee('Grid Parent Entity');
    }

    public function testGridTypeRouteDisplaysParentFilterBreadcrumb()
    {
        $this->get(route('events.grid.type', ['slug' => 'grid-filter-type']))
            ->assertOk()
            ->assertSee('Grid-filter-type');
    }

    /**
     * A basic functional test example.
     *
     * @return void
     */
    public function calendarBrowsable()
    {
        $response = $this->call('GET', '/calendar');

        $this->assertEquals(200, $response->getStatusCode());

        $response->assertSee('Events Calendar');
    }
}
