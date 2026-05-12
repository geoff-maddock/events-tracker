<?php

namespace Tests\Unit\Filters;

use App\Filters\ActivityFilters;
use App\Models\Action;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ActivityFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function applyFilters(array $filters)
    {
        $request = Request::create('/', 'GET', $filters);
        $filter = new ActivityFilters($request);

        return $filter->apply(Activity::query());
    }

    public function test_message_filter_does_partial_match(): void
    {
        Activity::factory()->create(['message' => 'User logged in']);
        Activity::factory()->create(['message' => 'Page was viewed']);

        $results = $this->applyFilters(['message' => 'logged'])->get();

        $this->assertCount(1, $results);
    }

    public function test_user_id_filter_matches_exact_id(): void
    {
        $user = User::factory()->create();
        Activity::factory()->create(['user_id' => $user->id]);
        Activity::factory()->create(['user_id' => User::factory()->create()->id]);

        $results = $this->applyFilters(['user_id' => $user->id])->get();

        $this->assertCount(1, $results);
        $this->assertEquals($user->id, $results->first()->user_id);
    }

    public function test_object_id_filter_matches_exact_id(): void
    {
        Activity::factory()->create(['object_id' => 42]);
        Activity::factory()->create(['object_id' => 99]);

        $results = $this->applyFilters(['object_id' => 42])->get();

        $this->assertCount(1, $results);
    }

    public function test_object_table_filter_does_partial_match(): void
    {
        Activity::factory()->create(['object_table' => 'events']);
        Activity::factory()->create(['object_table' => 'users']);

        $results = $this->applyFilters(['object_table' => 'event'])->get();

        $this->assertCount(1, $results);
    }

    public function test_action_filter_matches_by_action_name(): void
    {
        $loginAction = Action::find(Action::LOGIN);
        $this->assertNotNull($loginAction);

        $activity = new Activity();
        $activity->forceFill([
            'user_id' => User::factory()->create()->id,
            'object_id' => 1,
            'object_table' => 'User',
            'object_name' => 'test',
            'action_id' => Action::LOGIN,
        ])->save();

        // Another activity with a different action
        $activity2 = new Activity();
        $activity2->forceFill([
            'user_id' => User::factory()->create()->id,
            'object_id' => 1,
            'object_table' => 'User',
            'object_name' => 'test',
            'action_id' => Action::CREATE,
        ])->save();

        $results = $this->applyFilters(['action' => $loginAction->name])->get();

        $this->assertGreaterThanOrEqual(1, $results->count());
        foreach ($results as $row) {
            $this->assertSame(Action::LOGIN, $row->action_id);
        }
    }

    public function test_user_filter_matches_username(): void
    {
        $user = User::factory()->create(['name' => 'ZZActivityActor']);

        $a = new Activity();
        $a->forceFill([
            'user_id' => $user->id,
            'object_id' => 1,
            'object_table' => 'User',
            'object_name' => 'test',
            'action_id' => Action::LOGIN,
        ])->save();

        $b = new Activity();
        $b->forceFill([
            'user_id' => User::factory()->create()->id,
            'object_id' => 2,
            'object_table' => 'User',
            'object_name' => 'test',
            'action_id' => Action::LOGIN,
        ])->save();

        $results = $this->applyFilters(['user' => 'ZZActivityActor'])->get();

        $this->assertCount(1, $results);
    }

    public function test_empty_filters_return_all_records(): void
    {
        Activity::factory()->count(3)->create();

        $results = $this->applyFilters([])->get();

        $this->assertGreaterThanOrEqual(3, $results->count());
    }
}
