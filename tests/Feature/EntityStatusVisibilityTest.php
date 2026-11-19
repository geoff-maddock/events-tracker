<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\Event;
use App\Models\Group;
use App\Models\Location;
use App\Models\Role;
use App\Models\Series;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * An entity with the Unlisted status exists for admins only. Its name still
 * shows, as plain text, on events and series that use it as venue or promoter.
 */
class EntityStatusVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private Entity $unlisted;

    private Entity $listed;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        $venueRole = Role::where('slug', 'venue')->firstOrFail();

        $this->unlisted = Entity::factory()->create([
            'name' => 'Basement Number Nine',
            'slug' => 'basement-number-nine',
            'entity_status_id' => EntityStatus::UNLISTED,
        ]);
        $this->unlisted->roles()->attach($venueRole->id);

        $this->listed = Entity::factory()->create([
            'name' => 'Open Door Hall',
            'slug' => 'open-door-hall',
            'entity_status_id' => EntityStatus::ACTIVE,
        ]);
        $this->listed->roles()->attach($venueRole->id);

        Cache::flush();
    }

    private function member(): User
    {
        return User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
    }

    private function admin(): User
    {
        $user = $this->member();
        $user->groups()->attach(Group::firstOrCreate(['name' => 'admin'])->id);

        return $user;
    }

    /** @return array<string, string> */
    private function token(User $user): array
    {
        return ['Accept' => 'application/json', 'Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken];
    }

    private function eventAt(Entity $venue, ?User $owner = null): Event
    {
        return Event::factory()->create([
            'name' => 'Night At The Venue',
            'slug' => 'night-at-the-venue-'.$venue->id,
            'venue_id' => $venue->id,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => now()->addWeek(),
            'created_by' => $owner->id ?? 1,
        ]);
    }

    public function test_queries_hide_an_unlisted_entity_unless_an_admin_asks(): void
    {
        $this->assertNull(Entity::find($this->unlisted->id));
        $this->assertNotNull(Entity::withUnlisted()->find($this->unlisted->id));

        Auth::login($this->member());
        $this->assertNull(Entity::find($this->unlisted->id));

        Auth::login($this->admin());
        $this->assertNotNull(Entity::find($this->unlisted->id));
    }

    public function test_pages_for_an_unlisted_entity_are_not_found_for_guests_and_members(): void
    {
        $pages = ['/entities/basement-number-nine', '/venue/basement-number-nine'];

        foreach ($pages as $page) {
            $this->get($page)->assertNotFound();
        }

        $member = $this->member();
        foreach ($pages as $page) {
            $this->actingAs($member)->get($page)->assertNotFound();
        }

        // the listed venue is unaffected
        $this->get('/venue/open-door-hall')->assertOk();
    }

    public function test_an_admin_can_open_an_unlisted_entity(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/entities/basement-number-nine')->assertOk()->assertSee('Basement Number Nine');
        $this->actingAs($admin)->get('/venue/basement-number-nine')->assertOk();
    }

    public function test_lists_leave_out_unlisted_entities_even_when_the_filter_asks_for_them(): void
    {
        $lists = [
            '/entities?filters[entity_status]=Unlisted',
            '/entities?filters[name]=Basement',
            '/entities/role/venue?filters[entity_status]=Unlisted',
            '/venue',
        ];

        foreach ($lists as $list) {
            $this->get($list)->assertOk()->assertDontSee('Basement Number Nine');
        }

        $member = $this->member();
        foreach ($lists as $list) {
            $this->actingAs($member)->get($list)->assertOk()->assertDontSee('Basement Number Nine');
        }

        // an admin asking for them gets them, so the lists above were really filtered
        $this->actingAs($this->admin())->get('/entities?filters[entity_status]=Unlisted')
            ->assertOk()
            ->assertSee('Basement Number Nine');
    }

    public function test_the_api_hides_an_unlisted_entity_from_a_member_and_shows_it_to_an_admin(): void
    {
        $member = $this->token($this->member());

        $this->getJson('/api/entities/basement-number-nine', $member)->assertNotFound();
        $this->getJson('/api/entities/basement-number-nine/locations', $member)->assertNotFound();
        $this->getJson('/api/entities?filters[entity_status]=Unlisted', $member)
            ->assertOk()
            ->assertJsonMissing(['slug' => 'basement-number-nine']);

        $this->app['auth']->forgetGuards();

        $this->getJson('/api/entities/basement-number-nine', $this->token($this->admin()))
            ->assertOk()
            ->assertJsonFragment(['slug' => 'basement-number-nine']);
    }

    public function test_locations_of_an_unlisted_entity_are_hidden_with_it(): void
    {
        $location = Location::factory()->create(['entity_id' => $this->unlisted->id, 'address_one' => '9 Hidden Lane']);

        $this->assertNull(Location::find($location->id));
        $this->getJson('/api/locations/'.$location->id, $this->token($this->member()))->assertNotFound();

        $this->app['auth']->forgetGuards();
        Auth::shouldUse('web');
        Auth::login($this->admin());
        $this->assertNotNull(Location::find($location->id));
    }

    public function test_an_event_at_an_unlisted_venue_shows_the_name_without_a_link(): void
    {
        $event = $this->eventAt($this->unlisted);

        $this->assertNull($event->fresh()->venue);
        $this->assertSame('Basement Number Nine', $event->fresh()->venueLabel());

        $this->get('/events/'.$event->slug)
            ->assertOk()
            ->assertSee('Basement Number Nine')
            ->assertDontSee('/entities/basement-number-nine');

        $this->get('/events')
            ->assertOk()
            ->assertDontSee('/entities/basement-number-nine');

        // the API gives no venue object for it
        $this->getJson('/api/events/'.$event->slug, $this->token($this->member()))
            ->assertOk()
            ->assertJsonPath('venue', null);
    }

    public function test_an_admin_sees_the_venue_link_on_the_event(): void
    {
        $event = $this->eventAt($this->unlisted);

        $this->actingAs($this->admin())->get('/events/'.$event->slug)
            ->assertOk()
            ->assertSee('/entities/basement-number-nine');
    }

    public function test_editing_by_someone_who_cannot_see_the_venue_keeps_it(): void
    {
        $owner = $this->member();
        $event = $this->eventAt($this->unlisted, $owner);
        $event->entities()->attach([$this->unlisted->id, $this->listed->id]);

        // the form shows the hidden venue by name only
        $this->actingAs($owner)->get('/events/'.$event->slug.'/edit')
            ->assertOk()
            ->assertSee('Basement Number Nine');

        Auth::login($owner);

        // an update that leaves the venue empty and drops every related entity
        $event->fill($event->keepHiddenEntityRefs(['venue_id' => null]))->save();
        $event->syncEntities([]);

        $event = Event::find($event->id);
        $this->assertSame($this->unlisted->id, (int) $event->venue_id);
        $this->assertSame(
            [$this->unlisted->id],
            $event->entities()->withoutGlobalScopes()->pluck('entities.id')->map(fn ($id) => (int) $id)->all()
        );

        // choosing another venue replaces it
        $event->fill($event->keepHiddenEntityRefs(['venue_id' => $this->listed->id]))->save();
        $this->assertSame($this->listed->id, (int) $event->fresh()->venue_id);
    }

    public function test_an_api_replace_by_the_owner_keeps_the_hidden_venue(): void
    {
        $owner = $this->member();
        $event = $this->eventAt($this->unlisted, $owner);

        $this->putJson('/api/events/'.$event->slug, [
            'name' => $event->name,
            'slug' => $event->slug,
            'start_at' => $event->start_at->format('Y-m-d H:i:s'),
            'event_type_id' => $event->event_type_id,
            'visibility_id' => $event->visibility_id,
        ], $this->token($owner))->assertOk();

        $this->assertSame($this->unlisted->id, (int) Event::find($event->id)->venue_id);
    }

    public function test_an_admin_can_clear_an_unlisted_venue(): void
    {
        $event = $this->eventAt($this->unlisted);

        Auth::login($this->admin());
        $event->fill($event->keepHiddenEntityRefs(['venue_id' => null]))->save();

        $this->assertNull($event->fresh()->venue_id);
    }

    public function test_a_series_at_an_unlisted_venue_shows_the_name_only(): void
    {
        $series = Series::factory()->create([
            'venue_id' => $this->unlisted->id,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $this->assertNull($series->fresh()->venue);
        $this->assertSame('Basement Number Nine', $series->fresh()->venueLabel());

        $this->get('/series/'.$series->slug)
            ->assertOk()
            ->assertSee('Basement Number Nine')
            ->assertDontSee('/entities/basement-number-nine');
    }

    public function test_only_an_admin_may_make_an_entity_unlisted(): void
    {
        $owner = $this->member();
        $entity = Entity::factory()->create([
            'slug' => 'owned-by-a-member',
            'entity_status_id' => EntityStatus::ACTIVE,
            'created_by' => $owner->id,
        ]);

        $this->patchJson('/api/entities/owned-by-a-member', ['entity_status_id' => EntityStatus::UNLISTED], $this->token($owner))
            ->assertStatus(422)
            ->assertJsonValidationErrors('entity_status_id');
        $this->assertSame(EntityStatus::ACTIVE, (int) Entity::withUnlisted()->find($entity->id)->entity_status_id);

        // the form does not offer it to a member
        $this->assertArrayNotHasKey(EntityStatus::UNLISTED, EntityStatus::formOptionsFor($owner));
        $this->assertArrayHasKey(EntityStatus::UNLISTED, EntityStatus::formOptionsFor($this->admin()));

        $this->app['auth']->forgetGuards();

        $this->patchJson('/api/entities/owned-by-a-member', ['entity_status_id' => EntityStatus::UNLISTED], $this->token($this->admin()))
            ->assertOk();
        $this->assertSame(EntityStatus::UNLISTED, (int) Entity::withUnlisted()->find($entity->id)->entity_status_id);
    }

    public function test_the_event_form_venue_options_are_cached_separately_for_admins(): void
    {
        $member = $this->member();
        $this->actingAs($member)->get('/events/create')->assertOk()->assertDontSee('Basement Number Nine');

        $this->actingAs($this->admin())->get('/events/create')->assertOk()->assertSee('Basement Number Nine');

        // and the member's copy was not replaced by the admin's
        $this->actingAs($member)->get('/events/create')->assertOk()->assertDontSee('Basement Number Nine');
    }

    public function test_saving_an_entity_drops_the_cached_option_lists(): void
    {
        Cache::put('form-opts-venues', ['stale'], 3600);
        Cache::put('form-opts-venues:admin', ['stale'], 3600);

        $this->listed->update(['short' => 'Changed']);

        $this->assertFalse(Cache::has('form-opts-venues'));
        $this->assertFalse(Cache::has('form-opts-venues:admin'));
    }

    public function test_a_member_cannot_point_an_event_at_an_unlisted_entity_by_id(): void
    {
        $owner = $this->member();
        $event = $this->eventAt($this->listed, $owner);

        foreach (['venue_id' => $this->unlisted->id, 'promoter_id' => $this->unlisted->id, 'entity_list' => [$this->unlisted->id]] as $field => $value) {
            $this->patchJson('/api/events/'.$event->slug, [$field => $value], $this->token($owner))
                ->assertStatus(422);
            $this->app['auth']->forgetGuards();
        }

        $event = Event::find($event->id);
        $this->assertSame($this->listed->id, (int) $event->venue_id);
        $this->assertSame(0, $event->entities()->withoutGlobalScopes()->count());

        // an admin can, on an event of their own (the API lets only the owner edit, #2395)
        $admin = $this->admin();
        $adminEvent = Event::factory()->create(['slug' => 'the-admins-night', 'created_by' => $admin->id]);
        $this->patchJson('/api/events/the-admins-night', ['venue_id' => $this->unlisted->id], $this->token($admin))
            ->assertOk();
        $this->assertSame($this->unlisted->id, (int) $adminEvent->fresh()->venue_id);
    }

    public function test_the_venue_an_event_already_has_passes_validation_when_sent_back(): void
    {
        $owner = $this->member();
        $event = $this->eventAt($this->unlisted, $owner);

        $this->patchJson('/api/events/'.$event->slug, ['venue_id' => $this->unlisted->id, 'short' => 'Edited'], $this->token($owner))
            ->assertOk();

        $this->assertSame($this->unlisted->id, (int) Event::find($event->id)->venue_id);
    }

    public function test_editing_a_thread_keeps_an_unlisted_entity_attached(): void
    {
        $thread = \App\Models\Thread::factory()->create();
        $thread->entities()->attach([$this->unlisted->id, $this->listed->id]);

        Auth::login($this->member());
        $thread->syncEntities([]);

        $this->assertSame(
            [$this->unlisted->id],
            $thread->entities()->withoutGlobalScopes()->pluck('entities.id')->map(fn ($id) => (int) $id)->all()
        );
    }

    public function test_a_link_shared_with_a_listed_entity_stays_visible(): void
    {
        $shared = \App\Models\Link::factory()->create();
        $shared->entities()->attach([$this->unlisted->id, $this->listed->id]);

        $private = \App\Models\Link::factory()->create();
        $private->entities()->attach($this->unlisted->id);

        $this->assertNotNull(\App\Models\Link::find($shared->id));
        $this->assertNull(\App\Models\Link::find($private->id));

        Auth::login($this->admin());
        $this->assertNotNull(\App\Models\Link::find($private->id));
    }

    public function test_output_that_leaves_the_site_never_links_an_unlisted_venue(): void
    {
        $event = $this->eventAt($this->unlisted);

        // even when an admin's request builds it
        Auth::login($this->admin());
        $event = Event::find($event->id);

        $this->assertNotNull($event->venue);
        $this->assertNull($event->publicVenue());
        $this->assertSame('Basement Number Nine', $event->venueLabel());
    }

    public function test_a_quick_added_entity_does_not_reuse_an_unlisted_slug(): void
    {
        $this->actingAs($this->member())
            ->postJson('/entities/quick-store', ['name' => 'Basement Number Nine', 'role' => 'Venue'])
            ->assertSuccessful();

        $this->assertSame(1, Entity::withUnlisted()->where('slug', 'basement-number-nine')->count());
    }
}
