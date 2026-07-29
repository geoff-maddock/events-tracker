<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventResponse;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAttendingEventsCardTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function makeUser(array $profileAttributes = []): User
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        Profile::factory()->create(array_merge(['user_id' => $user->id], $profileAttributes));

        return $user;
    }

    private function attendFutureEvent(User $user, int $daysAhead = 1, array $overrides = []): Event
    {
        $event = Event::factory()->create(array_merge([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => Carbon::now()->addDays($daysAhead),
            'end_at' => Carbon::now()->addDays($daysAhead)->addHour(),
        ], $overrides));

        EventResponse::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'response_type_id' => 1,
        ]);

        return $event;
    }

    private function extractCard(string $html, int $userId): string
    {
        $matched = preg_match(
            '/<article[^>]*id="user-card-'.$userId.'".*?<\/article>/s',
            $html,
            $m
        );
        $this->assertSame(1, $matched, "Card for user {$userId} not found on index");

        return $m[0];
    }

    /** @test */
    public function public_profile_shows_attending_event_thumbnails(): void
    {
        $this->withExceptionHandling();

        $user = $this->makeUser();
        $event = $this->attendFutureEvent($user);

        $response = $this->actingAs($user)->get('/users?limit=1000');
        $response->assertStatus(200);

        $card = $this->extractCard($response->getContent(), $user->id);
        $this->assertStringContainsString('data-attending-events', $card);
        $this->assertStringContainsString(route('events.show', ['event' => $event->slug]), $card);
        $this->assertStringContainsString('title="'.e($event->name).'"', $card);
    }

    /** @test */
    public function row_caps_at_four_soonest_events_with_overflow_chip(): void
    {
        $this->withExceptionHandling();

        $user = $this->makeUser();
        $events = [];
        foreach (range(1, 6) as $daysAhead) {
            $events[$daysAhead] = $this->attendFutureEvent($user, $daysAhead);
        }

        $response = $this->actingAs($user)->get('/users?limit=1000');
        $response->assertStatus(200);

        $card = $this->extractCard($response->getContent(), $user->id);
        foreach (range(1, 4) as $daysAhead) {
            $this->assertStringContainsString($events[$daysAhead]->slug, $card);
        }
        foreach (range(5, 6) as $daysAhead) {
            $this->assertStringNotContainsString($events[$daysAhead]->slug, $card);
        }
        $this->assertStringContainsString('+2', $card);
    }

    /** @test */
    public function no_future_events_renders_no_row(): void
    {
        $this->withExceptionHandling();

        $user = $this->makeUser();
        $this->attendFutureEvent($user, 1, [
            'start_at' => Carbon::now()->subDays(3),
            'end_at' => Carbon::now()->subDays(3)->addHour(),
        ]);

        $response = $this->actingAs($user)->get('/users?limit=1000');
        $response->assertStatus(200);

        $card = $this->extractCard($response->getContent(), $user->id);
        $this->assertStringNotContainsString('data-attending-events', $card);
    }

    /** @test */
    public function private_profile_hides_row(): void
    {
        $this->withExceptionHandling();

        $user = $this->makeUser(['setting_public_profile' => 0]);
        $this->attendFutureEvent($user);

        $viewer = $this->makeUser();
        $response = $this->actingAs($viewer)->get('/users?limit=1000');
        $response->assertStatus(200);

        $card = $this->extractCard($response->getContent(), $user->id);
        $this->assertStringNotContainsString('data-attending-events', $card);
    }

    /** @test */
    public function user_without_profile_row_shows_no_row(): void
    {
        $this->withExceptionHandling();

        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->attendFutureEvent($user);

        $viewer = $this->makeUser();
        $response = $this->actingAs($viewer)->get('/users?limit=1000');
        $response->assertStatus(200);

        $card = $this->extractCard($response->getContent(), $user->id);
        $this->assertStringNotContainsString('data-attending-events', $card);
    }

    /** @test */
    public function private_events_of_others_are_not_shown(): void
    {
        $this->withExceptionHandling();

        $creator = $this->makeUser();
        $user = $this->makeUser();
        $this->attendFutureEvent($user, 1, [
            'visibility_id' => Visibility::VISIBILITY_PRIVATE,
            'created_by' => $creator->id,
        ]);

        $viewer = $this->makeUser();
        $response = $this->actingAs($viewer)->get('/users?limit=1000');
        $response->assertStatus(200);

        $card = $this->extractCard($response->getContent(), $user->id);
        $this->assertStringNotContainsString('data-attending-events', $card);
    }

    /** @test */
    public function filtered_index_keeps_the_row(): void
    {
        $this->withExceptionHandling();

        $user = $this->makeUser();
        $user->update(['name' => 'Filterable Person']);
        $event = $this->attendFutureEvent($user);

        $response = $this->actingAs($user)->post(route('users.filter'), [
            'filters' => ['name' => 'Filterable'],
        ]);
        $response->assertStatus(200);

        $card = $this->extractCard($response->getContent(), $user->id);
        $this->assertStringContainsString('data-attending-events', $card);
        $this->assertStringContainsString($event->slug, $card);
    }
}
