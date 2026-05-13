<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventType;
use App\Models\Series;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for the auth-bypass bug previously present in the
 * Api\EventsController and Api\SeriesController update/patch paths: a
 * missing `return` on $this->unauthorized() allowed non-owners to mutate
 * other users' resources. The strongest signal is that data does not
 * change after a non-owner request — so each test asserts that, rather
 * than depending on the exact 4xx code returned.
 */
class ApiEventsAuthBypassTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $owner;
    private User $attacker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->owner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->attacker = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
    }

    private function fullEventPayload(Event $event, string $name): array
    {
        return [
            'name' => $name,
            'slug' => $event->slug,
            'start_at' => Carbon::parse('2026-09-15 20:00')->toDateTimeString(),
            'event_type_id' => EventType::first()->id,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ];
    }

    public function test_event_update_does_not_mutate_data_for_non_owner(): void
    {
        $event = Event::factory()->create([
            'created_by' => $this->owner->id,
            'name' => 'Original Name ZZ',
        ]);

        $this->actingAs($this->attacker, 'sanctum');
        $this->putJson('/api/events/'.$event->slug, $this->fullEventPayload($event, 'Hijacked'));

        $this->assertSame('Original Name ZZ', $event->fresh()->name);
    }

    public function test_event_patch_does_not_mutate_data_for_non_owner(): void
    {
        $event = Event::factory()->create([
            'created_by' => $this->owner->id,
            'name' => 'Original Name ZZ',
        ]);

        $this->actingAs($this->attacker, 'sanctum');
        $this->patchJson('/api/events/'.$event->slug, ['name' => 'Hijacked']);

        $this->assertSame('Original Name ZZ', $event->fresh()->name);
    }

    public function test_series_update_does_not_mutate_data_for_non_owner(): void
    {
        $series = Series::factory()->create([
            'created_by' => $this->owner->id,
            'name' => 'Original Series ZZ',
        ]);

        $this->actingAs($this->attacker, 'sanctum');
        $this->putJson('/api/series/'.$series->slug, ['name' => 'Hijacked']);

        $this->assertSame('Original Series ZZ', $series->fresh()->name);
    }

    public function test_series_patch_does_not_mutate_data_for_non_owner(): void
    {
        $series = Series::factory()->create([
            'created_by' => $this->owner->id,
            'name' => 'Original Series ZZ',
        ]);

        $this->actingAs($this->attacker, 'sanctum');
        $this->patchJson('/api/series/'.$series->slug, ['name' => 'Hijacked']);

        $this->assertSame('Original Series ZZ', $series->fresh()->name);
    }

    public function test_event_destroy_rejects_non_owner(): void
    {
        $event = Event::factory()->create(['created_by' => $this->owner->id]);

        $this->actingAs($this->attacker, 'sanctum');
        $this->deleteJson('/api/events/'.$event->slug);

        $this->assertNotNull(Event::find($event->id), 'Event should still exist.');
    }

    public function test_event_destroy_succeeds_for_owner(): void
    {
        $event = Event::factory()->create(['created_by' => $this->owner->id]);

        $this->actingAs($this->owner, 'sanctum');
        $this->deleteJson('/api/events/'.$event->slug)->assertStatus(204);

        $this->assertNull(Event::find($event->id));
    }

    public function test_series_destroy_rejects_non_owner(): void
    {
        $series = Series::factory()->create(['created_by' => $this->owner->id]);

        $this->actingAs($this->attacker, 'sanctum');
        $this->deleteJson('/api/series/'.$series->slug);

        $this->assertNotNull(Series::find($series->id), 'Series should still exist.');
    }

    public function test_entity_destroy_rejects_non_owner(): void
    {
        $entity = \App\Models\Entity::factory()->create(['created_by' => $this->owner->id]);

        $this->actingAs($this->attacker, 'sanctum');
        $this->deleteJson('/api/entities/'.$entity->slug);

        $this->assertNotNull(\App\Models\Entity::find($entity->id), 'Entity should still exist.');
    }
}
