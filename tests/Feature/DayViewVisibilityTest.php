<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The day views (home, /events/upcoming, /events/day, the "load next events"
 * fragment, /events/week) list what the main event list would: the visible()
 * rule, not a rule of their own (#2408).
 */
class DayViewVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const AJAX = ['X-Requested-With' => 'XMLHttpRequest'];

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        $this->member = User::factory()->create();
        $other = User::factory()->create();

        Event::query()->delete();
        foreach ([
            'Zzpublic show' => [Visibility::VISIBILITY_PUBLIC, $other],
            'Zzguarded show' => [Visibility::VISIBILITY_GUARDED, $other],
            'Zzothers private' => [Visibility::VISIBILITY_PRIVATE, $other],
            'Zzmy private' => [Visibility::VISIBILITY_PRIVATE, $this->member],
            'Zzmy proposal' => [Visibility::VISIBILITY_PROPOSAL, $this->member],
            'Zzmy cancelled' => [Visibility::VISIBILITY_CANCELLED, $this->member],
        ] as $name => [$visibility, $owner]) {
            Event::factory()->create([
                'name' => $name, 'visibility_id' => $visibility, 'created_by' => $owner->id,
                'start_at' => now()->addDay()->setTime(20, 0), 'end_at' => null, 'series_id' => null,
            ]);
        }
    }

    /**
     * Every page and fragment that shows tomorrow's events.
     *
     * @return array<int, array{0: string, 1: array<string, string>}>
     */
    private function dayViews(): array
    {
        $tomorrow = now()->addDay();

        return [
            ['/', []],
            ['/', self::AJAX],
            ['/events/upcoming', []],
            ['/events/upcoming', self::AJAX],
            ['/events/upcoming/'.$tomorrow->format('Y-m-d'), []],
            ['/events/add/'.$tomorrow->format('Ymd'), self::AJAX],
            ['/events/day/'.$tomorrow->format('Y-m-d'), []],
            ['/events/week', []],
        ];
    }

    public function test_a_guest_sees_only_public_events(): void
    {
        foreach ($this->dayViews() as [$url, $headers]) {
            $response = $this->get($url, $headers)->assertOk()->assertSee('Zzpublic show');
            foreach (['Zzguarded show', 'Zzothers private', 'Zzmy private', 'Zzmy proposal', 'Zzmy cancelled'] as $hidden) {
                $response->assertDontSee($hidden);
            }
        }
    }

    public function test_a_member_also_sees_guarded_events_and_their_own_private_ones(): void
    {
        $this->actingAs($this->member);

        foreach ($this->dayViews() as [$url, $headers]) {
            $response = $this->get($url, $headers)->assertOk();
            foreach (['Zzpublic show', 'Zzguarded show', 'Zzmy private', 'Zzmy proposal'] as $shown) {
                $response->assertSee($shown);
            }
            // as on /events: nobody else's private event, and no cancelled ones
            $response->assertDontSee('Zzothers private')->assertDontSee('Zzmy cancelled');
        }
    }

    public function test_the_day_views_match_the_event_list(): void
    {
        $this->actingAs($this->member);

        $listed = Event::visible($this->member)->pluck('name')->sort()->values()->all();
        $shown = Event::cardsByDay(now(), 4, $this->member)->flatten()->pluck('name')->sort()->values()->all();

        $this->assertSame($listed, $shown);
    }
}
