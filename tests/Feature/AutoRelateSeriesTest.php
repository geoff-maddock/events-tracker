<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Series;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers PagesController::autoRelateSeries — the admin-only action that sets
 * the series on every event whose name, short or description contains the
 * exact keyword phrase (case-insensitive), skipping events already in a series.
 */
class AutoRelateSeriesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function activeUser(): User
    {
        /** @var User $user */
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        return $user;
    }

    private function admin(): User
    {
        $admin = $this->activeUser();
        $admin->assignGroup('admin');

        return $admin;
    }

    private function makeSeries(array $attributes = []): Series
    {
        return Series::factory()->create(array_merge([
            'name'          => 'Cutups',
            'slug'          => 'cutups',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ], $attributes));
    }

    private function makeEvent(array $attributes): Event
    {
        return Event::factory()->create(array_merge([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'series_id'     => null,
        ], $attributes));
    }

    /** @test */
    public function guest_cannot_auto_relate(): void
    {
        $series = $this->makeSeries();
        $event = $this->makeEvent(['name' => 'Cutups Vol. 12']);

        $this->get(route('pages.relateSeries', ['id' => $series->id, 'keyword' => 'cutups']));

        $this->assertNull($event->fresh()->series_id);
    }

    /** @test */
    public function non_admin_cannot_auto_relate(): void
    {
        $series = $this->makeSeries();
        $event = $this->makeEvent(['name' => 'Cutups Vol. 12']);

        $this->actingAs($this->activeUser())
            ->get(route('pages.relateSeries', ['id' => $series->id, 'keyword' => 'cutups']));

        $this->assertNull($event->fresh()->series_id);
    }

    /** @test */
    public function admin_sets_series_on_events_matching_exact_phrase_case_insensitively(): void
    {
        $series = $this->makeSeries();

        $byName = $this->makeEvent(['name' => 'CUTUPS Vol. 12']);
        $byDescription = $this->makeEvent([
            'name'        => 'Dance Night',
            'description' => 'Part of the cutups party',
        ]);
        // Contains both words but not the exact phrase — must NOT match.
        $splitWords = $this->makeEvent([
            'name'        => 'Cut Ups and Downs',
            'description' => 'Unrelated',
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('pages.relateSeries', ['id' => $series->id, 'keyword' => 'Cutups']));

        $response->assertRedirect(route('pages.search', ['keyword' => 'Cutups']));
        $this->assertSame($series->id, $byName->fresh()->series_id);
        $this->assertSame($series->id, $byDescription->fresh()->series_id);
        $this->assertNull($splitWords->fresh()->series_id);
    }

    /** @test */
    public function events_already_in_a_series_are_skipped(): void
    {
        $series = $this->makeSeries();
        $otherSeries = $this->makeSeries(['name' => 'Other Party', 'slug' => 'other-party']);
        $event = $this->makeEvent(['name' => 'Cutups Vol. 12', 'series_id' => $otherSeries->id]);

        $this->actingAs($this->admin())
            ->get(route('pages.relateSeries', ['id' => $series->id, 'keyword' => 'cutups']));

        $this->assertSame($otherSeries->id, $event->fresh()->series_id);
    }

    /** @test */
    public function other_users_private_events_are_not_related(): void
    {
        $series = $this->makeSeries();
        $owner = $this->activeUser();
        $privateEvent = $this->makeEvent([
            'name'          => 'Cutups Secret Show',
            'visibility_id' => Visibility::VISIBILITY_PRIVATE,
            'created_by'    => $owner->id,
        ]);

        $this->actingAs($this->admin())
            ->get(route('pages.relateSeries', ['id' => $series->id, 'keyword' => 'cutups']));

        $this->assertNull($privateEvent->fresh()->series_id);
    }

    /** @test */
    public function empty_keyword_relates_nothing(): void
    {
        $series = $this->makeSeries();
        $event = $this->makeEvent(['name' => 'Cutups Vol. 12']);

        $this->actingAs($this->admin())
            ->get(route('pages.relateSeries', ['id' => $series->id, 'keyword' => '']));

        $this->assertNull($event->fresh()->series_id);
    }

    /** @test */
    public function unknown_series_relates_nothing(): void
    {
        $event = $this->makeEvent(['name' => 'Cutups Vol. 12']);

        $this->actingAs($this->admin())
            ->get(route('pages.relateSeries', ['id' => 999999, 'keyword' => 'cutups']));

        $this->assertNull($event->fresh()->series_id);
    }
}
