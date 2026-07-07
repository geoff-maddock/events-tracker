<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
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
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($user)
            ->get('/events/create')
            ->assertOk()
            ->assertSee('No age limit specified')
            ->assertSeeInOrder(['id="min_age"', 'option value="" selected'], false);
    }

    public function testUpdateDoesNotNullCreatedByFromEmptyInput()
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $event = Event::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->put('/events/'.$event->id, [
            'name' => 'Updated Event Name',
            'slug' => 'updated-event-name-'.$event->id,
            'start_at' => '2026-07-01 20:00:00',
            'event_type_id' => $event->event_type_id,
            'visibility_id' => $event->visibility_id,
            // An empty hidden field becomes null via ConvertEmptyStringsToNull and
            // must not overwrite created_by and trip the NOT NULL constraint
            // (EVENTREPO-VM).
            'created_by' => '',
        ]);

        $response->assertRedirect();
        $this->assertSame($user->id, (int) $event->fresh()->created_by);
    }

    public function testShowResolvesEventBySlugAndById()
    {
        $event = Event::factory()->create([
            'slug' => 'zz-binding-test',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $this->get('/events/'.$event->slug)->assertOk();
        $this->get('/events/'.$event->id)->assertOk();
    }

    public function testShowReturns404ForIdPrefixedNonexistentSlug()
    {
        $this->withExceptionHandling();
        $event = Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);

        // The old orWhere('id', ...) binding let MySQL cast '<id>-no-such-slug'
        // to the id and wrongly serve that event (issue #1964).
        $this->get('/events/'.$event->id.'-no-such-slug')->assertNotFound();
    }

    public function testStorePrependsDashToDigitLeadingSlug()
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($user)->post('/events', [
            'name' => '1984 Test Party',
            'slug' => '1984-test-party',
            'start_at' => '2026-08-01 20:00:00',
            'event_type_id' => 1,
            'visibility_id' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('events', ['slug' => '-1984-test-party']);
        $this->assertDatabaseMissing('events', ['slug' => '1984-test-party']);
    }

    public function testStoreRejectsDigitLeadingSlugCollidingWithDashPrefixedSlug()
    {
        $this->withExceptionHandling();
        Event::factory()->create(['slug' => '-1984-test-party']);
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($user)->post('/events', [
            'name' => '1984 Test Party',
            'slug' => '1984-test-party',
            'start_at' => '2026-08-01 20:00:00',
            'event_type_id' => 1,
            'visibility_id' => 1,
        ])->assertSessionHasErrors('slug');
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
