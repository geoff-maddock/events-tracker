<?php

namespace Tests\Feature;

use App\Models\Action;
use App\Models\Activity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityGraphTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_activity_graph_is_admin_only(): void
    {
        $this->withExceptionHandling();

        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user);

        $this->get(route('activities.graph'))->assertStatus(403);
    }

    public function test_admin_can_view_activity_graph_with_series_data(): void
    {
        $admin = User::factory()->create(['user_status_id' => 1]);
        $admin->assignGroup('admin');

        $this->actingAs($admin);

        Activity::factory()->create([
            'user_id' => $admin->id,
            'object_table' => 'Event',
            'action_id' => Action::CREATE,
            'created_at' => Carbon::today()->subDay(),
            'updated_at' => Carbon::today()->subDay(),
        ]);

        Activity::factory()->create([
            'user_id' => $admin->id,
            'object_table' => 'Entity',
            'action_id' => Action::CREATE,
            'created_at' => Carbon::today(),
            'updated_at' => Carbon::today(),
        ]);

        $response = $this->get(route('activities.graph', ['days' => 7]));

        $response->assertOk();
        $response->assertSee('Activity Graph');
        $response->assertSee('Create Event');
        $response->assertSee('Create Entity');
    }

    public function test_admin_can_export_activity_graph_csv(): void
    {
        $admin = User::factory()->create(['user_status_id' => 1]);
        $admin->assignGroup('admin');
        $this->actingAs($admin);

        Activity::factory()->create([
            'user_id' => $admin->id,
            'object_table' => 'Event',
            'action_id' => Action::CREATE,
            'created_at' => Carbon::today(),
            'updated_at' => Carbon::today(),
        ]);

        $response = $this->get(route('activities.graph.export', ['days' => 7, 'line_limit' => 10]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $response->assertSee('Period,Activity Type,Count');
        $response->assertSee('Create Event');
    }

    public function test_admin_can_group_activity_graph_by_week(): void
    {
        $admin = User::factory()->create(['user_status_id' => 1]);
        $admin->assignGroup('admin');
        $this->actingAs($admin);

        $firstDate = Carbon::parse('2026-01-14 10:00:00');
        $secondDate = Carbon::parse('2026-01-15 11:00:00');
        $weekStart = $firstDate->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');

        Activity::factory()->create([
            'user_id' => $admin->id,
            'object_table' => 'Event',
            'action_id' => Action::CREATE,
            'created_at' => $firstDate,
            'updated_at' => $firstDate,
        ]);

        Activity::factory()->create([
            'user_id' => $admin->id,
            'object_table' => 'Event',
            'action_id' => Action::CREATE,
            'created_at' => $secondDate,
            'updated_at' => $secondDate,
        ]);

        $response = $this->get(route('activities.graph.export', [
            'group_by' => 'week',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'line_limit' => 10,
        ]));

        $response->assertOk();
        $response->assertSee($weekStart.',Create Event,2');
        $response->assertSee('Grouping,week');
    }
}
