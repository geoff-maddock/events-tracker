<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventResponse;
use App\Models\EventReview;
use App\Models\Group;
use App\Models\ResponseType;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Deleting an event soft-deletes it: it disappears everywhere public but its
 * RSVPs and reviews are kept, and an admin can restore it (#2192).
 */
class EventSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $owner;

    private User $admin;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        $this->owner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->admin->groups()->attach(Group::firstOrCreate(['name' => 'admin'])->id);

        $this->event = Event::factory()->create([
            'created_by' => $this->owner->id,
            'name' => 'ZZ Soft Deleted Show',
            'slug' => 'zz-soft-deleted-show',
            'start_at' => now()->addDays(3),
        ]);
    }

    private function attend(User $user): EventResponse
    {
        return EventResponse::create([
            'event_id' => $this->event->id,
            'user_id' => $user->id,
            'response_type_id' => ResponseType::ATTENDING,
        ]);
    }

    public function test_deleting_an_event_keeps_its_rsvps_and_reviews_and_hides_it(): void
    {
        $fan = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $response = $this->attend($fan);
        $review = EventReview::create([
            'event_id' => $this->event->id, 'user_id' => $fan->id, 'review_type_id' => 1,
            'review' => 'A ZZ review', 'attended' => 1,
        ]);
        $this->assertSame(1, $fan->fresh()->attending_count);

        $this->actingAs($this->owner)->delete(route('events.destroy', $this->event))->assertRedirect('/events');

        $this->assertSoftDeleted('events', ['id' => $this->event->id]);
        $this->assertNotNull(EventResponse::find($response->id));
        $this->assertNotNull(EventReview::find($review->id));

        // hidden from public pages and the API, and from counts
        $this->get("/events/{$this->event->slug}")->assertNotFound();
        $this->actingAs($fan, 'sanctum')->getJson("/api/events/{$this->event->slug}")->assertNotFound();
        $this->assertSame(0, $fan->fresh()->attending_count);

        // the reviews list skips a deleted event's review instead of failing on it
        $this->get('/reviews')->assertOk()->assertDontSee('A ZZ review');
    }

    public function test_related_tags_ignore_deleted_events(): void
    {
        $tag = Tag::factory()->create(['name' => 'Zzsoftmain', 'slug' => 'zzsoftmain']);
        $other = Tag::factory()->create(['name' => 'Zzsoftother', 'slug' => 'zzsoftother']);
        $this->event->tags()->attach([$tag->id, $other->id]);
        $this->assertSame(['Zzsoftother' => 1], $tag->relatedTags());

        $this->event->delete();

        $this->assertSame([], $tag->fresh()->relatedTags());
    }

    public function test_only_admins_see_the_deleted_events_page(): void
    {
        $this->actingAs($this->owner)->delete(route('events.destroy', $this->event));

        $this->actingAs($this->owner)->get('/events/deleted')->assertForbidden();

        $this->actingAs($this->admin)->get('/events/deleted')
            ->assertOk()
            ->assertSee('ZZ Soft Deleted Show')
            ->assertSee('by '.e($this->owner->name), false);
    }

    public function test_an_admin_can_restore_a_deleted_event_with_its_rsvps(): void
    {
        $fan = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->attend($fan);
        $this->event->delete();

        $this->actingAs($this->owner)->post("/events/{$this->event->id}/restore")->assertForbidden();
        $this->assertSoftDeleted('events', ['id' => $this->event->id]);

        $this->actingAs($this->admin)->post("/events/{$this->event->id}/restore")
            ->assertRedirect(route('events.deleted'));

        $this->assertNotSoftDeleted('events', ['id' => $this->event->id]);
        $this->get("/events/{$this->event->slug}")->assertOk();
        $this->assertSame(1, $fan->fresh()->attending_count);
    }

    public function test_the_api_restore_endpoint_is_admin_only(): void
    {
        $this->event->delete();

        $this->actingAs($this->owner, 'sanctum')->postJson("/api/events/{$this->event->id}/restore")->assertForbidden();

        $this->actingAs($this->admin, 'sanctum')->postJson("/api/events/{$this->event->id}/restore")
            ->assertOk()
            ->assertJsonPath('slug', 'zz-soft-deleted-show');
        $this->assertNotSoftDeleted('events', ['id' => $this->event->id]);

        // nothing to restore now
        $this->postJson("/api/events/{$this->event->id}/restore")->assertNotFound();
    }
}
