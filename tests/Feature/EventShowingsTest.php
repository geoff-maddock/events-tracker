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

/**
 * A short-run event keeps its first date in start_at and its other dates as
 * showings (#2375).
 */
class EventShowingsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * @param array<string, mixed> $extra
     *
     * @return array<string, mixed>
     */
    private function payload(string $slug, array $extra = []): array
    {
        return $extra + [
            'name' => 'ZZ Short Run Play',
            'slug' => $slug,
            'event_type_id' => EventType::query()->value('id'),
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => '2027-03-01 20:00:00',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function showingStarts(Event $event): array
    {
        return $event->showings()->get()->map(fn ($showing) => $showing->start_at->format('Y-m-d H:i'))->all();
    }

    public function test_creating_an_event_saves_its_showings_in_order(): void
    {
        $this->actingAs($this->user)->post('/events', $this->payload('zz-run-create', [
            'showings' => [
                ['start_at' => '2027-03-03T14:00', 'end_at' => '2027-03-03T16:00'],
                ['start_at' => '', 'end_at' => ''],
                ['start_at' => '2027-03-02T20:00', 'end_at' => ''],
            ],
        ]))->assertSessionHasNoErrors();

        $event = Event::where('slug', 'zz-run-create')->sole();

        $this->assertSame(['2027-03-02 20:00', '2027-03-03 14:00'], $this->showingStarts($event));
        $this->assertSame('2027-03-03 16:00', $event->showings->last()->end_at->format('Y-m-d H:i'));
        $this->assertSame('2027-03-03 14:00', $event->last_start_at->format('Y-m-d H:i'));
        $this->assertTrue($event->isMultiDate());
        $this->assertCount(3, $event->occurrences());
    }

    public function test_a_single_date_event_has_no_showings(): void
    {
        $this->actingAs($this->user)->post('/events', $this->payload('zz-run-single'))->assertSessionHasNoErrors();

        $event = Event::where('slug', 'zz-run-single')->sole();

        $this->assertFalse($event->isMultiDate());
        $this->assertSame('2027-03-01 20:00', $event->last_start_at->format('Y-m-d H:i'));
        $this->assertCount(1, $event->occurrences());
    }

    public function test_showings_must_follow_the_start_and_differ(): void
    {
        $this->actingAs($this->user)->post('/events', $this->payload('zz-run-early', [
            'showings' => [['start_at' => '2027-02-28T20:00']],
        ]))->assertSessionHasErrors(['showings.0.start_at']);

        $this->actingAs($this->user)->post('/events', $this->payload('zz-run-repeat', [
            'showings' => [['start_at' => '2027-03-02T20:00'], ['start_at' => '2027-03-02T20:00']],
        ]))->assertSessionHasErrors(['showings.0.start_at']);

        $this->actingAs($this->user)->post('/events', $this->payload('zz-run-backwards', [
            'showings' => [['start_at' => '2027-03-02T20:00', 'end_at' => '2027-03-02T19:00']],
        ]))->assertSessionHasErrors(['showings.0.end_at']);

        $this->assertSame(0, Event::where('slug', 'like', 'zz-run-%')->count());
    }

    public function test_updating_replaces_the_showings(): void
    {
        $event = Event::factory()->create(['created_by' => $this->user->id, 'start_at' => '2027-03-01 20:00:00']);
        $event->syncShowings([['start_at' => '2027-03-02 20:00:00'], ['start_at' => '2027-03-05 20:00:00']]);

        $this->actingAs($this->user)->put("/events/{$event->id}", $this->payload('zz-run-update', [
            'showings' => [['start_at' => '2027-03-04T20:00']],
        ]))->assertSessionHasNoErrors();

        $event = $event->fresh();
        $this->assertSame(['2027-03-04 20:00'], $this->showingStarts($event));
        $this->assertSame('2027-03-04 20:00', $event->last_start_at->format('Y-m-d H:i'));

        // the form sends every showing it has, so none means they were removed
        $this->actingAs($this->user)->put("/events/{$event->id}", $this->payload('zz-run-update'))
            ->assertSessionHasNoErrors();

        $event = $event->fresh();
        $this->assertSame([], $this->showingStarts($event));
        $this->assertFalse($event->isMultiDate());
    }

    public function test_api_patch_only_touches_showings_it_is_sent(): void
    {
        $event = Event::factory()->create(['created_by' => $this->user->id, 'start_at' => '2027-03-01 20:00:00']);
        $event->syncShowings([['start_at' => '2027-03-02 20:00:00'], ['start_at' => '2027-03-05 20:00:00']]);
        $this->actingAs($this->user, 'sanctum');

        $this->patchJson("/api/events/{$event->slug}", ['short' => 'ZZ still running'])
            ->assertOk()
            ->assertJsonCount(2, 'showings');

        // moving the first date past a showing drops that showing
        $this->patchJson("/api/events/{$event->slug}", ['start_at' => '2027-03-03 20:00:00'])->assertOk();
        $this->assertSame(['2027-03-05 20:00'], $this->showingStarts($event->fresh()));

        $this->patchJson("/api/events/{$event->slug}", ['showings' => [['start_at' => '2027-03-01 20:00:00']]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['showings.0.start_at']);

        $this->patchJson("/api/events/{$event->slug}", ['showings' => []])
            ->assertOk()
            ->assertJsonCount(0, 'showings');
        $this->assertFalse($event->fresh()->isMultiDate());
    }

    public function test_the_api_reports_the_next_showing(): void
    {
        $event = Event::factory()->create(['created_by' => $this->user->id, 'start_at' => '2027-03-01 20:00:00']);
        $event->syncShowings([['start_at' => '2027-03-02 20:00:00'], ['start_at' => '2027-03-05 20:00:00']]);
        Carbon::setTestNow('2027-03-03 12:00:00');

        $data = $this->actingAs($this->user, 'sanctum')->getJson("/api/events/{$event->slug}")->assertOk()->json();

        $this->assertCount(2, $data['showings']);
        // serialized like start_at, so compared with the showing's own value
        $this->assertSame($data['showings'][1]['start_at'], $data['next_start_at']);
        $this->assertSame($data['showings'][1]['start_at'], $data['last_start_at']);
        $this->assertNotSame($data['start_at'], $data['next_start_at']);
    }

    public function test_the_event_page_lists_every_showing(): void
    {
        $event = Event::factory()->create(['created_by' => $this->user->id, 'start_at' => '2027-03-01 20:00:00']);
        $event->syncShowings([['start_at' => '2027-03-02 14:00:00']]);

        $this->get("/events/{$event->slug}")
            ->assertOk()
            ->assertSee('2 showings')
            ->assertSee('Tue, Mar 2nd')
            ->assertSee('2:00 PM');

        $single = Event::factory()->create(['created_by' => $this->user->id]);
        $this->get("/events/{$single->slug}")->assertOk()->assertDontSee('data-showtimes', false);
    }

    public function test_the_edit_form_carries_the_showings(): void
    {
        $event = Event::factory()->create(['created_by' => $this->user->id, 'start_at' => '2027-03-01 20:00:00']);
        $event->syncShowings([['start_at' => '2027-03-02 14:00:00']]);

        $this->actingAs($this->user)->get("/events/{$event->slug}/edit")
            ->assertOk()
            ->assertSee('Additional Showings')
            ->assertSee('2027-03-02T14:00', false);
    }

    public function test_deleting_an_event_removes_its_showings(): void
    {
        $event = Event::factory()->create(['created_by' => $this->user->id, 'start_at' => '2027-03-01 20:00:00']);
        $event->syncShowings([['start_at' => '2027-03-02 14:00:00']]);

        $event->delete();

        $this->assertDatabaseCount('event_showings', 0);
    }
}
