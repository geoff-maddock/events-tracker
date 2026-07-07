<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventType;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiEventsCrudTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->actingAs($this->user, 'sanctum');
    }

    private User $user;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'ZZ-Test-Event',
            'slug' => 'zz-test-event-'.uniqid(),
            'short' => 'A short blurb.',
            'description' => 'A longer description.',
            'event_type_id' => EventType::first()->id,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'is_benefit' => 0,
            'do_not_repost' => 0,
            'event_status_id' => 1,
            'start_at' => Carbon::parse('2026-09-15 20:00:00')->toDateTimeString(),
            'end_at' => Carbon::parse('2026-09-15 23:00:00')->toDateTimeString(),
        ], $overrides);
    }

    public function test_store_creates_event_with_creator_set_to_actor(): void
    {
        $response = $this->postJson('/api/events', $this->validPayload(['name' => 'ZZ-Store-Event']));

        $response->assertOk()->assertJsonFragment(['name' => 'ZZ-Store-Event']);

        $event = Event::where('name', 'ZZ-Store-Event')->first();
        $this->assertNotNull($event);
        $this->assertSame($this->user->id, $event->created_by);
    }

    public function test_store_prepends_dash_to_digit_leading_slug(): void
    {
        $response = $this->postJson('/api/events', $this->validPayload([
            'name' => 'ZZ-Numeric-Slug-Event',
            'slug' => '1984-zz-numeric-slug',
        ]));

        $response->assertOk();
        $this->assertDatabaseHas('events', ['slug' => '-1984-zz-numeric-slug']);
        $this->assertDatabaseMissing('events', ['slug' => '1984-zz-numeric-slug']);
    }

    public function test_store_rejects_invalid_payload(): void
    {
        $response = $this->postJson('/api/events', ['name' => 'x']);
        $response->assertStatus(422);
    }

    public function test_update_replaces_event_fields_for_creator(): void
    {
        $event = Event::factory()->create([
            'created_by' => $this->user->id,
            'slug' => 'zz-update-target-'.uniqid(),
        ]);

        $payload = $this->validPayload([
            'name' => 'ZZ-Updated-Event',
            'slug' => $event->slug,
        ]);

        $response = $this->putJson('/api/events/'.$event->slug, $payload);

        $response->assertOk();
        $this->assertSame('ZZ-Updated-Event', $event->fresh()->name);
    }

    public function test_update_refuses_when_not_creator(): void
    {
        $other = User::factory()->create();
        $event = Event::factory()->create([
            'created_by' => $other->id,
            'slug' => 'zz-other-event-'.uniqid(),
        ]);

        $payload = $this->validPayload(['slug' => $event->slug]);

        $response = $this->putJson('/api/events/'.$event->slug, $payload);

        $this->assertContains($response->status(), [302, 401, 403]);
    }

    public function test_patch_partial_update_for_creator(): void
    {
        $event = Event::factory()->create([
            'created_by' => $this->user->id,
            'name' => 'ZZ-Original',
            'slug' => 'zz-patch-event-'.uniqid(),
        ]);

        $response = $this->patchJson('/api/events/'.$event->slug, [
            'name' => 'ZZ-Patched-Only-Name',
        ]);

        $response->assertOk();
        $this->assertSame('ZZ-Patched-Only-Name', $event->fresh()->name);
    }

    public function test_patch_prepends_dash_to_digit_leading_slug(): void
    {
        $event = Event::factory()->create([
            'created_by' => $this->user->id,
            'slug' => 'zz-patch-slug-event-'.uniqid(),
        ]);

        $response = $this->patchJson('/api/events/'.$event->slug, [
            'slug' => '1984-zz-patched-slug',
        ]);

        $response->assertOk();
        $this->assertSame('-1984-zz-patched-slug', $event->fresh()->slug);
    }

    public function test_destroy_deletes_event_for_creator(): void
    {
        $event = Event::factory()->create([
            'created_by' => $this->user->id,
            'slug' => 'zz-destroy-mine-'.uniqid(),
        ]);

        $response = $this->deleteJson('/api/events/'.$event->slug);

        $response->assertStatus(204);
        $this->assertNull(Event::find($event->id));
    }

    public function test_destroy_refuses_when_not_creator(): void
    {
        $other = User::factory()->create();
        $event = Event::factory()->create([
            'created_by' => $other->id,
            'slug' => 'zz-destroy-other-'.uniqid(),
        ]);

        $response = $this->deleteJson('/api/events/'.$event->slug);

        $this->assertContains($response->status(), [302, 401, 403]);
        $this->assertNotNull(Event::find($event->id));
    }
}
