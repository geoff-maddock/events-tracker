<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Entity;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Group;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The web form and the API write an event through the same service (#2407),
 * so what a write records does not depend on which one was used.
 */
class EventWriterTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $owner;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->owner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        // an admin, so the write is by someone other than the owner
        $this->editor = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->editor->groups()->attach(Group::where('name', 'admin')->firstOrFail()->id);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'ZZ Writer Event',
            'slug' => 'zz-writer-event',
            'short' => 'A short blurb.',
            'event_type_id' => EventType::first()->id,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => now()->addDays(10)->setTime(20, 0)->toDateTimeString(),
        ], $overrides);
    }

    private function event(array $attributes = []): Event
    {
        return Event::factory()->create(array_merge([
            'slug' => 'zz-writer-event', 'created_by' => $this->owner->id, 'updated_by' => null,
            'venue_id' => null, 'promoter_id' => null,
        ], $attributes));
    }

    private function write(string $via, Event $event, array $changes): void
    {
        match ($via) {
            'web' => $this->actingAs($this->editor)->put('/events/'.$event->slug, $this->payload($changes))->assertSessionHasNoErrors(),
            'put' => $this->actingAs($this->editor, 'sanctum')->putJson('/api/events/'.$event->slug, $this->payload($changes))->assertOk(),
            'patch' => $this->actingAs($this->editor, 'sanctum')->patchJson('/api/events/'.$event->slug, $changes)->assertOk(),
            default => $this->fail('unknown write path '.$via),
        };
    }

    /** @return array<string, array{string}> */
    public static function writes(): array
    {
        return ['web form' => ['web'], 'API put' => ['put'], 'API patch' => ['patch']];
    }

    #[DataProvider('writes')]
    public function test_an_update_records_who_made_it_and_keeps_the_owner(string $via): void
    {
        $event = $this->event();
        $logged = Activity::count();

        $this->write($via, $event, ['name' => 'ZZ Renamed']);

        $event->refresh();
        $this->assertSame('ZZ Renamed', $event->name);
        $this->assertSame($this->editor->id, $event->updated_by, 'who last changed it');
        $this->assertSame($this->owner->id, $event->created_by, 'the owner is unchanged');
        $this->assertSame($logged + 1, Activity::count(), 'one activity row per write');
    }

    #[DataProvider('writes')]
    public function test_an_update_keeps_the_venue_and_promoter_among_the_related_entities(string $via): void
    {
        $venue = Entity::factory()->create();
        $promoter = Entity::factory()->create();
        $other = Entity::factory()->create();
        $event = $this->event();

        // the related list names neither the venue nor the promoter
        $this->write($via, $event, ['venue_id' => $venue->id, 'promoter_id' => $promoter->id, 'entity_list' => [$other->id]]);

        $related = $event->entities()->pluck('entities.id')->sort()->values()->all();
        $this->assertSame([$venue->id, $promoter->id, $other->id], $related);
    }

    public function test_a_new_event_belongs_to_its_creator_on_both_paths(): void
    {
        $member = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $venue = Entity::factory()->create();

        $this->actingAs($member)->post('/events', $this->payload(['slug' => 'zz-web-new', 'created_by' => $this->owner->id, 'venue_id' => $venue->id]))
            ->assertSessionHasNoErrors();
        $this->actingAs($member, 'sanctum')->postJson('/api/events', $this->payload(['slug' => 'zz-api-new', 'created_by' => $this->owner->id, 'venue_id' => $venue->id]))
            ->assertOk();

        foreach (['zz-web-new', 'zz-api-new'] as $slug) {
            $event = Event::where('slug', $slug)->firstOrFail();
            $this->assertSame($member->id, $event->created_by, $slug);
            $this->assertSame($member->id, $event->updated_by, $slug);
            $this->assertSame([$venue->id], $event->entities()->pluck('entities.id')->all(), $slug);
        }
    }
}
