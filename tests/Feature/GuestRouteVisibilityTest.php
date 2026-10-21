<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Entity;
use App\Models\Event;
use App\Models\EventResponse;
use App\Models\EventReview;
use App\Models\Location;
use App\Models\Profile;
use App\Models\Series;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pages and endpoints that load an event, series, review, comment or location
 * by id apply the same visibility rule as the main pages.
 */
class GuestRouteVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $owner;

    private User $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        $this->owner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->other = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
    }

    private function privateEvent(): Event
    {
        return Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PRIVATE,
            'created_by' => $this->owner->id,
            'slug' => 'private-event-'.uniqid(),
        ]);
    }

    private function privateSeries(): Series
    {
        return Series::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PRIVATE,
            'created_by' => $this->owner->id,
            'slug' => 'private-series-'.uniqid(),
        ]);
    }

    public function test_create_series_from_an_event_needs_a_signed_in_user_who_can_see_it(): void
    {
        $event = $this->privateEvent();

        $this->get('/events/create-series?id='.$event->id)->assertRedirect('/login');
        $this->actingAs($this->other)->get('/events/create-series?id='.$event->id)->assertNotFound();
        $this->actingAs($this->other)->get('/events/create-series?id=999999')->assertNotFound();
    }

    public function test_the_owner_can_create_a_series_from_their_private_event(): void
    {
        $event = $this->privateEvent();

        $this->actingAs($this->owner)->get('/events/create-series?id='.$event->id)->assertOk();
    }

    public function test_duplicating_an_event_needs_one_the_user_can_see(): void
    {
        $event = $this->privateEvent();

        $this->actingAs($this->other)->get('/events/'.$event->id.'/duplicate')->assertNotFound();
        $this->actingAs($this->other)->get('/events/999999/duplicate')->assertNotFound();
    }

    public function test_create_occurrence_needs_a_signed_in_user_who_can_see_the_series(): void
    {
        $series = $this->privateSeries();

        $this->get('/series/createOccurrence?id='.$series->id)->assertRedirect('/login');
        $this->actingAs($this->other)->get('/series/createOccurrence?id='.$series->id)->assertNotFound();
        $this->actingAs($this->other)->get('/series/createOccurrence?id=999999')->assertNotFound();
    }

    public function test_event_embed_loaders_treat_a_private_event_as_missing(): void
    {
        $event = $this->privateEvent();
        $ajax = ['X-Requested-With' => 'XMLHttpRequest'];

        $this->getJson('/events/'.$event->slug.'/embeds')->assertNotFound();
        $this->getJson('/events/'.$event->slug.'/minimal-embeds')->assertNotFound();
        $this->get('/events/'.$event->id.'/load-embeds', $ajax)->assertRedirect();
        $this->get('/events/'.$event->id.'/load-minimal-embeds', $ajax)->assertRedirect();

        $this->actingAs($this->owner)->getJson('/events/'.$event->slug.'/embeds')->assertOk();
    }

    public function test_series_embed_loaders_treat_a_private_series_as_missing(): void
    {
        $series = $this->privateSeries();

        $this->getJson('/series/'.$series->slug.'/embeds')->assertNotFound();
        $this->getJson('/series/'.$series->slug.'/minimal-embeds')->assertNotFound();
    }

    public function test_attending_a_private_event_is_refused_without_rendering_it(): void
    {
        $event = $this->privateEvent();

        $response = $this->actingAs($this->other)
            ->post('/events/'.$event->id.'/attend', [], ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertRedirect();
        $this->assertStringNotContainsString($event->name, (string) $response->getContent());
        $this->assertFalse(EventResponse::where('event_id', $event->id)->exists());
    }

    public function test_a_review_of_a_private_event_is_not_found(): void
    {
        $review = EventReview::create([
            'event_id' => $this->privateEvent()->id,
            'user_id' => $this->owner->id,
            'review_type_id' => 1,
            'review' => 'A review',
            'attended' => 1,
        ]);

        $this->get('/reviews/'.$review->id)->assertNotFound();
        $this->actingAs($this->owner)->get('/reviews/'.$review->id)->assertOk();
    }

    public function test_a_comment_page_must_match_its_parent_and_respect_event_visibility(): void
    {
        $entity = Entity::factory()->create();
        $elsewhere = Entity::factory()->create();
        $entityComment = Comment::create(['message' => 'On an entity', 'commentable_id' => $entity->id, 'commentable_type' => 'entity']);

        $this->get('/entities/'.$entity->slug.'/comments/'.$entityComment->id)->assertOk();
        $this->get('/entities/'.$elsewhere->slug.'/comments/'.$entityComment->id)->assertNotFound();

        $event = $this->privateEvent();
        $eventComment = Comment::create(['message' => 'On an event', 'commentable_id' => $event->id, 'commentable_type' => 'event']);

        $this->get('/events/'.$event->id.'/comments/'.$eventComment->id)->assertNotFound();
        $this->actingAs($this->owner)->get('/events/'.$event->id.'/comments/'.$eventComment->id)
            ->assertRedirect(route('events.show', ['event' => $event->id]));
    }

    public function test_a_guarded_location_needs_a_signed_in_viewer_and_the_right_entity(): void
    {
        $entity = Entity::factory()->create();
        $elsewhere = Entity::factory()->create();
        $location = Location::factory()->create([
            'entity_id' => $entity->id,
            'visibility_id' => Visibility::VISIBILITY_GUARDED,
        ]);

        $this->get('/entities/'.$entity->slug.'/locations/'.$location->id)->assertNotFound();
        $this->actingAs($this->other)->get('/entities/'.$entity->slug.'/locations/'.$location->id)->assertOk();
        $this->actingAs($this->other)->get('/entities/'.$elsewhere->slug.'/locations/'.$location->id)->assertNotFound();
    }

    public function test_the_api_attending_list_is_refused_for_a_private_profile(): void
    {
        Profile::factory()->create(['user_id' => $this->owner->id, 'setting_public_profile' => 0]);
        $token = $this->other->createToken('test')->plainTextToken;

        $this->getJson('/api/users/'.$this->owner->id.'/events-attending', ['Authorization' => 'Bearer '.$token])
            ->assertForbidden();
    }

    public function test_the_api_attending_list_is_open_for_a_public_profile(): void
    {
        Profile::factory()->create(['user_id' => $this->owner->id, 'setting_public_profile' => 1]);
        $token = $this->other->createToken('test')->plainTextToken;

        $this->getJson('/api/users/'.$this->owner->id.'/events-attending', ['Authorization' => 'Bearer '.$token])
            ->assertOk();
    }
}
