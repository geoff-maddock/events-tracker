<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Event;
use App\Models\Follow;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Follow, attend, like, lock, impersonate and the admin user actions change
 * state, so they are POST-only (a GET can be triggered cross-site); and
 * session API calls are no longer exempt from CSRF (#2166).
 */
class StateChangingRoutesArePostTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public static function postOnlyPaths(): array
    {
        return [
            'event attend' => ['events/1/attend'], 'event unattend' => ['events/1/unattend'],
            'entity follow' => ['entities/1/follow'], 'entity unfollow' => ['entities/1/unfollow'],
            'series follow' => ['series/1/follow'], 'series unfollow' => ['series/1/unfollow'],
            'tag follow' => ['tags/1/follow'], 'tag unfollow' => ['tags/1/unfollow'],
            'thread follow' => ['threads/1/follow'], 'thread unfollow' => ['threads/1/unfollow'],
            'thread like' => ['threads/1/like'], 'thread unlike' => ['threads/1/unlike'],
            'thread lock' => ['threads/1/lock'], 'thread unlock' => ['threads/1/unlock'],
            'post like' => ['posts/1/like'], 'post unlike' => ['posts/1/unlike'],
            'impersonate' => ['impersonate/1'],
            'user activate' => ['users/1/activate'], 'user suspend' => ['users/1/suspend'],
            'user delete' => ['users/1/delete'], 'user reminder' => ['users/1/reminder'],
            'user weekly' => ['users/1/weekly'],
        ];
    }

    #[DataProvider('postOnlyPaths')]
    public function test_state_changing_route_rejects_get(string $path): void
    {
        $this->actingAs(User::factory()->create(['user_status_id' => UserStatus::ACTIVE]))
            ->get('/'.$path)
            ->assertStatus(405);
    }

    public function test_following_a_tag_works_by_post_and_not_by_get(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $tag = Tag::factory()->create();

        $this->actingAs($user)->get('/tags/'.$tag->id.'/follow')->assertStatus(405);
        $this->assertFalse(Follow::where(['user_id' => $user->id, 'object_type' => 'tag', 'object_id' => $tag->id])->exists());

        $this->actingAs($user)->post('/tags/'.$tag->id.'/follow')->assertRedirect();
        $this->assertTrue(Follow::where(['user_id' => $user->id, 'object_type' => 'tag', 'object_id' => $tag->id])->exists());
    }

    public function test_ajax_attend_still_returns_json_over_post(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $event = Event::factory()->create();

        $this->actingAs($user)
            ->post('/events/'.$event->id.'/attend', [], ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonStructure(['Message']);
    }

    public function test_api_routes_are_not_exempt_from_csrf(): void
    {
        // PHPUnit bypasses CSRF checks entirely, so check the exemption list itself
        $middleware = $this->app->make(VerifyCsrfToken::class);
        $inExcept = (new \ReflectionMethod($middleware, 'inExceptArray'))->getClosure($middleware);

        $this->assertFalse($inExcept(Request::create('/api/tags/1/follow', 'POST')));
        $this->assertFalse($inExcept(Request::create('/api/events', 'POST')));
    }
}
