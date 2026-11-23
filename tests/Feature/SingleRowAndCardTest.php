<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Follow;
use App\Models\Series;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * An event or series in a list is one view, events.single-tw / series.single-tw,
 * drawn as a row or (with 'card' => true) as a card (#2410).
 */
class SingleRowAndCardTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function renderFor(?User $user, string $view, array $data): string
    {
        return view($view, $data + ['signedIn' => $user !== null, 'user' => $user])->render();
    }

    public function test_an_event_renders_as_a_row_and_as_a_card(): void
    {
        $event = Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC, 'start_at' => now()->addDays(2), 'primary_link' => 'https://example.com/zz']);

        $row = $this->renderFor(null, 'events.single-tw', ['event' => $event]);
        $card = $this->renderFor(null, 'events.single-tw', ['event' => $event, 'card' => true]);

        foreach ([$row, $card] as $html) {
            $this->assertStringContainsString('id="event-'.$event->id.'"', $html);
            $this->assertStringContainsString(e($event->name), $html);
            $this->assertSame(1, substr_count($html, 'href="https://example.com/zz"'));
            $this->assertSame(substr_count($html, '<div'), substr_count($html, '</div>'));
        }

        $this->assertStringContainsString('sm:flex-row', $row);
        $this->assertStringNotContainsString('Sign in to mark as attending', $row);
        $this->assertStringContainsString('h-full', $card);
        $this->assertStringContainsString('Sign in to mark as attending', $card);
    }

    public function test_a_signed_in_user_gets_the_attend_control_of_each_layout(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $event = Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC, 'start_at' => now()->addDays(2), 'created_by' => $user->id]);

        $row = $this->renderFor($user, 'events.single-tw', ['event' => $event]);
        $card = $this->renderFor($user, 'events.single-tw', ['event' => $event, 'card' => true]);

        $this->assertStringContainsString('data-method="post" href="'.route('events.attend', ['id' => $event->id]).'"', $row);
        $this->assertStringContainsString('class="ajax-action', $card);
        $this->assertStringNotContainsString('data-method="post"', $card);

        foreach ([$row, $card] as $html) {
            $this->assertSame(1, substr_count($html, 'title="Edit this event"'));
        }
    }

    public function test_a_series_renders_as_a_row_and_as_a_card(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $series = Series::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC, 'cancelled_at' => null]);
        Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC, 'start_at' => now()->addDays(3), 'series_id' => $series->id, 'name' => 'Zz Next Edition']);
        Follow::create(['user_id' => $user->id, 'object_type' => 'series', 'object_id' => $series->id]);

        $row = $this->renderFor($user, 'series.single-tw', ['series' => $series]);
        $card = $this->renderFor($user, 'series.single-tw', ['series' => $series, 'card' => true]);

        foreach ([$row, $card] as $html) {
            $this->assertStringContainsString('id="series-'.$series->id.'"', $html);
            $this->assertStringContainsString(e($series->name), $html);
            $this->assertSame(1, substr_count($html, 'title="Unfollow"'));
            $this->assertStringNotContainsString('title="Follow"', $html);
            $this->assertSame(substr_count($html, '<div'), substr_count($html, '</div>'));
        }

        // the next edition is shown on the card only
        $this->assertStringNotContainsString('Zz Next Edition', $row);
        $this->assertStringContainsString('Zz Next Edition', $card);

        $guest = $this->renderFor(null, 'series.single-tw', ['series' => $series]);
        $this->assertStringNotContainsString('follow', strtolower(strip_tags($guest, '<a>')));
    }

    public function test_the_home_page_and_a_profile_use_the_one_view(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $event = Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC, 'start_at' => now()->addHours(2), 'created_by' => $user->id]);

        $this->get('/')->assertOk()->assertSee('id="event-'.$event->id.'"', false);
        $this->actingAs($user)->followingRedirects()->get('/users/'.$user->id)->assertOk()->assertSee('id="event-'.$event->id.'"', false);

        $this->assertFileDoesNotExist(resource_path('views/events/single-card-tw.blade.php'));
        $this->assertFileDoesNotExist(resource_path('views/series/single-card-tw.blade.php'));
    }
}
