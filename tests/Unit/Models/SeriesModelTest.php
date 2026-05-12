<?php

namespace Tests\Unit\Models;

use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeriesModelTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_future_scope_returns_series_starting_today_or_later(): void
    {
        Series::factory()->create(['start_at' => Carbon::yesterday()]);
        Series::factory()->create(['start_at' => Carbon::tomorrow()]);

        $results = Series::future()->get();

        foreach ($results as $row) {
            $this->assertGreaterThanOrEqual(Carbon::today()->startOfDay(), $row->start_at);
        }
    }

    public function test_past_scope_returns_series_before_today(): void
    {
        Series::factory()->create(['start_at' => Carbon::yesterday()]);
        Series::factory()->create(['start_at' => Carbon::tomorrow()]);

        $results = Series::past()->get();

        foreach ($results as $row) {
            $this->assertLessThan(Carbon::today()->startOfDay(), $row->start_at);
        }
    }

    public function test_age_format_returns_all_ages_when_min_age_is_zero(): void
    {
        $series = Series::factory()->create(['min_age' => 0]);

        $this->assertEquals('All Ages', $series->age_format);
    }

    public function test_age_format_appends_plus_for_non_zero_min_age(): void
    {
        $series = Series::factory()->create(['min_age' => 18]);

        $this->assertEquals('18+', $series->age_format);
    }

    public function test_age_format_is_empty_when_min_age_is_null(): void
    {
        $series = Series::factory()->create(['min_age' => null]);

        $this->assertEquals('', $series->age_format);
    }

    public function test_end_time_returns_end_at_when_set(): void
    {
        $series = Series::factory()->create([
            'start_at' => Carbon::tomorrow()->setTime(20, 0),
            'end_at' => Carbon::tomorrow()->setTime(23, 0),
        ]);

        $this->assertNotNull($series->end_time);
    }

    public function test_end_time_falls_back_to_start_plus_one_day_when_unset(): void
    {
        $start = Carbon::tomorrow()->setTime(20, 0);
        $series = Series::factory()->create(['start_at' => $start, 'end_at' => null]);

        $this->assertNotNull($series->end_time);
        $this->assertEquals(
            $start->copy()->addDay()->startOfDay()->toDateTimeString(),
            Carbon::instance($series->end_time)->toDateTimeString()
        );
    }

    public function test_owned_by_returns_true_for_creator(): void
    {
        $user = User::factory()->create();
        $series = Series::factory()->create();
        $series->forceFill(['created_by' => $user->id])->save();

        $this->assertTrue($series->fresh()->ownedBy($user));
    }

    public function test_owned_by_returns_false_for_non_creator(): void
    {
        $creator = User::factory()->create();
        $other = User::factory()->create();
        $series = Series::factory()->create();
        $series->forceFill(['created_by' => $creator->id])->save();

        $this->assertFalse($series->fresh()->ownedBy($other));
    }

    public function test_get_by_tag_returns_series_with_matching_tag(): void
    {
        $tag = Tag::factory()->create();
        $series = Series::factory()->create();
        $series->tags()->attach($tag->id);

        $results = Series::getByTag($tag->slug)->get();

        $this->assertTrue($results->contains('id', $series->id));
    }
}
