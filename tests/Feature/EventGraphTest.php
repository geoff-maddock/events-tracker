<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventGraphTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function makeAdmin(): User
    {
        $admin = User::factory()->create(['user_status_id' => 1]);
        $admin->assignGroup('admin');

        return $admin;
    }

    public function test_event_graph_is_admin_only(): void
    {
        $this->withExceptionHandling();

        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user);

        $this->get(route('events.graph'))->assertStatus(403);
    }

    public function test_admin_can_view_event_graph_by_event_type(): void
    {
        $this->actingAs($this->makeAdmin());

        $concertType = EventType::factory()->create(['name' => 'Zz Concert Type', 'slug' => 'zz-concert-type']);
        $clubType = EventType::factory()->create(['name' => 'Zz Club Night Type', 'slug' => 'zz-club-night-type']);

        Event::factory()->create([
            'event_type_id' => $concertType->id,
            'start_at' => Carbon::today()->subDay(),
        ]);
        Event::factory()->create([
            'event_type_id' => $clubType->id,
            'start_at' => Carbon::today(),
        ]);

        $response = $this->get(route('events.graph', ['days' => 7, 'group_by' => 'day']));

        $response->assertOk();
        $response->assertSee('Event Graph');
        $response->assertSee('Zz Concert Type');
        $response->assertSee('Zz Club Night Type');
    }

    public function test_admin_can_view_event_graph_by_tag(): void
    {
        $this->actingAs($this->makeAdmin());

        $tag = Tag::factory()->create(['name' => 'Zz Techno Tag']);
        $event = Event::factory()->create(['start_at' => Carbon::today()]);
        $event->tags()->attach($tag->id);

        $response = $this->get(route('events.graph', ['days' => 7, 'series_by' => 'tag']));

        $response->assertOk();
        $response->assertSee('Zz Techno Tag');
    }

    public function test_admin_can_view_event_graph_by_related_entity(): void
    {
        $this->actingAs($this->makeAdmin());

        $artist = Entity::factory()->create(['name' => 'Zz Artist Entity']);
        $event = Event::factory()->create(['start_at' => Carbon::today()]);
        $event->entities()->attach($artist->id);

        $response = $this->get(route('events.graph', ['days' => 7, 'series_by' => 'entity']));

        $response->assertOk();
        $response->assertSee('Zz Artist Entity');
    }

    public function test_admin_can_export_event_graph_csv_grouped_by_week(): void
    {
        $this->actingAs($this->makeAdmin());

        $eventType = EventType::factory()->create(['name' => 'Zz Export Type', 'slug' => 'zz-export-type']);
        $firstDate = Carbon::parse('2026-01-14 20:00:00');
        $secondDate = Carbon::parse('2026-01-15 21:00:00');
        $weekStart = $firstDate->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');

        Event::factory()->create(['event_type_id' => $eventType->id, 'start_at' => $firstDate]);
        Event::factory()->create(['event_type_id' => $eventType->id, 'start_at' => $secondDate]);

        $response = $this->get(route('events.graph.export', [
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'group_by' => 'week',
            'series_by' => 'event_type',
            'line_limit' => 50,
        ]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $content = $response->streamedContent();
        $this->assertStringContainsString('Period,Series,Count', $content);
        $this->assertStringContainsString($weekStart.',"Zz Export Type",2', $content);
        $this->assertStringContainsString('Grouping,week', $content);
        $this->assertStringContainsString('Breakdown,event_type', $content);
    }

    public function test_event_graph_total_breakdown_counts_events(): void
    {
        $this->actingAs($this->makeAdmin());

        Event::factory()->count(2)->create(['start_at' => Carbon::today()]);

        $response = $this->get(route('events.graph.export', [
            'days' => 7,
            'group_by' => 'day',
            'series_by' => 'total',
        ]));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString(Carbon::today()->toDateString().',Events,2', $content);
    }
}
