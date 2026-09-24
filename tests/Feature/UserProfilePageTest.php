<?php

namespace Tests\Feature;

use App\Models\Action;
use App\Models\Activity;
use App\Models\Event;
use App\Models\EventResponse;
use App\Models\Follow;
use App\Models\ResponseType;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * User profile page (#2169): counts come from SQL (and count the right rows),
 * the event lists are capped and eager loaded, and viewing a profile has no
 * side effect on the user's password reset token.
 */
class UserProfilePageTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function makeUser(): User
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $user->profile()->create(['setting_public_profile' => 1]);

        return $user->fresh();
    }

    public function test_login_count_counts_only_logins(): void
    {
        $user = $this->makeUser();
        Activity::factory()->count(3)->create(['user_id' => $user->id, 'action_id' => Action::LOGIN]);
        Activity::factory()->count(2)->create(['user_id' => $user->id, 'action_id' => Action::CREATE]);

        $this->assertSame(3, $user->login_count);
    }

    public function test_attending_count_counts_only_attending_responses(): void
    {
        $user = $this->makeUser();
        foreach ([ResponseType::ATTENDING, ResponseType::ATTENDING, ResponseType::INTERESTED] as $type) {
            EventResponse::create([
                'event_id' => Event::factory()->create()->id,
                'user_id' => $user->id,
                'response_type_id' => $type,
            ]);
        }

        $this->assertSame(2, $user->attending_count);
    }

    public function test_following_counts_are_per_type(): void
    {
        $user = $this->makeUser();
        foreach (['tag', 'tag', 'entity', 'series', 'thread', 'thread', 'thread'] as $i => $type) {
            Follow::create(['user_id' => $user->id, 'object_type' => $type, 'object_id' => $i + 1]);
        }

        $this->assertSame(2, $user->tags_following_count);
        $this->assertSame(1, $user->entities_following_count);
        $this->assertSame(1, $user->series_following_count);
        $this->assertSame(3, $user->threads_following_count);
    }

    public function test_viewing_a_profile_leaves_the_reset_token_alone(): void
    {
        $user = $this->makeUser();
        DB::table('password_resets')->insert([
            'email' => $user->email,
            'token' => 'existing-token-hash',
            'created_at' => now(),
        ]);

        $this->get(route('users.show', $user))->assertOk();
        $this->actingAs($this->makeUser())->get(route('users.show', $user))->assertOk();

        $this->assertSame(1, DB::table('password_resets')->where('email', $user->email)->count());
        $this->assertSame('existing-token-hash', DB::table('password_resets')->where('email', $user->email)->value('token'));
    }

    public function test_created_events_tab_shows_at_most_ten_events(): void
    {
        $user = $this->makeUser();
        Event::factory()->count(12)->create([
            'created_by' => $user->id,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $response = $this->actingAs($user)->get(route('users.show', $user).'?tabs[events]=created');

        $response->assertOk();
        $this->assertCount(10, $response->viewData('profileEvents'));
    }

    public function test_attending_tab_shows_attending_events(): void
    {
        $user = $this->makeUser();
        // fixed name: a random name with an apostrophe renders escaped and would miss assertSee
        $event = Event::factory()->create(['name' => 'ZZ Attending Event', 'visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        EventResponse::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'response_type_id' => ResponseType::ATTENDING,
        ]);

        $response = $this->actingAs($user)->get(route('users.show', $user).'?tabs[events]=attending');

        $response->assertOk()->assertSee($event->name, false);
        $this->assertTrue($response->viewData('profileEvents')->contains('id', $event->id));
    }

    public function test_query_count_does_not_grow_with_created_events(): void
    {
        $user = $this->makeUser();
        $viewer = $this->makeUser();
        Event::factory()->count(2)->create(['created_by' => $user->id, 'visibility_id' => Visibility::VISIBILITY_PUBLIC]);

        $small = $this->countQueries(fn () => $this->actingAs($viewer)->get(route('users.show', $user))->assertOk());

        Event::factory()->count(8)->create(['created_by' => $user->id, 'visibility_id' => Visibility::VISIBILITY_PUBLIC]);

        $large = $this->countQueries(fn () => $this->actingAs($viewer)->get(route('users.show', $user))->assertOk());

        // allow a little slack for incidental lookups; before #2169 this grew by ~7 queries per event
        $this->assertLessThanOrEqual($small + 3, $large, "queries: 2 events = {$small}, 10 events = {$large}");
    }

    private function countQueries(callable $fn): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $fn();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }
}
