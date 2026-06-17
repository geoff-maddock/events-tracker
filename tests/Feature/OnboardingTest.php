<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Follow;
use App\Models\Tag;
use App\Models\User;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Post-signup "Getting To Know You" onboarding (issue #901).
 */
class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        // The web onboarding endpoints sit behind CSRF protection; bypass it so
        // the JSON POST helpers can exercise the controller directly.
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    /** @test */
    public function verified_user_with_no_follows_should_see_onboarding(): void
    {
        $user = User::factory()->create(['user_status_id' => 1]);

        $this->assertTrue($user->shouldSeeOnboarding());
    }

    /** @test */
    public function unverified_user_should_not_see_onboarding(): void
    {
        $user = User::factory()->create(['user_status_id' => 1, 'email_verified_at' => null]);

        $this->assertFalse($user->shouldSeeOnboarding());
    }

    /** @test */
    public function user_who_already_follows_something_should_not_see_onboarding(): void
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $entity = Entity::factory()->create();

        Follow::create([
            'user_id' => $user->id,
            'object_type' => 'entity',
            'object_id' => $entity->id,
        ]);

        $this->assertFalse($user->fresh()->shouldSeeOnboarding());
    }

    /** @test */
    public function completed_or_dismissed_onboarding_is_not_shown_again(): void
    {
        $completed = User::factory()->create(['user_status_id' => 1]);
        $completed->profile()->create(['user_id' => $completed->id, 'onboarding_completed_at' => now()]);
        $this->assertFalse($completed->fresh()->shouldSeeOnboarding());

        $dismissed = User::factory()->create(['user_status_id' => 1]);
        $dismissed->profile()->create(['user_id' => $dismissed->id, 'onboarding_dismissed_at' => now()]);
        $this->assertFalse($dismissed->fresh()->shouldSeeOnboarding());
    }

    /** @test */
    public function store_creates_follows_and_marks_onboarding_complete(): void
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $entity = Entity::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->actingAs($user)->postJson(route('onboarding.store'), [
            'entities' => [$entity->id],
            'tags' => [$tag->id],
        ]);

        $response->assertOk()->assertJson(['success' => true, 'followed' => 2]);

        $this->assertDatabaseHas('follows', [
            'user_id' => $user->id,
            'object_type' => 'entity',
            'object_id' => $entity->id,
        ]);
        $this->assertDatabaseHas('follows', [
            'user_id' => $user->id,
            'object_type' => 'tag',
            'object_id' => $tag->id,
        ]);

        $this->assertNotNull($user->fresh()->profile->onboarding_completed_at);
        $this->assertFalse($user->fresh()->shouldSeeOnboarding());
    }

    /** @test */
    public function store_is_idempotent_and_does_not_duplicate_existing_follows(): void
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $entity = Entity::factory()->create();

        Follow::create([
            'user_id' => $user->id,
            'object_type' => 'entity',
            'object_id' => $entity->id,
        ]);

        $response = $this->actingAs($user)->postJson(route('onboarding.store'), [
            'entities' => [$entity->id],
        ]);

        $response->assertOk()->assertJson(['followed' => 0]);

        $this->assertSame(1, Follow::where('user_id', $user->id)
            ->where('object_type', 'entity')
            ->where('object_id', $entity->id)
            ->count());
    }

    /** @test */
    public function dismiss_marks_onboarding_dismissed(): void
    {
        $user = User::factory()->create(['user_status_id' => 1]);

        $response = $this->actingAs($user)->postJson(route('onboarding.dismiss'));

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertNotNull($user->fresh()->profile->onboarding_dismissed_at);
        $this->assertFalse($user->fresh()->shouldSeeOnboarding());
    }

    /** @test */
    public function restart_onboarding_clears_the_completed_and_dismissed_flags(): void
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $user->profile()->create([
            'user_id' => $user->id,
            'onboarding_completed_at' => now(),
            'onboarding_dismissed_at' => now(),
        ]);

        $this->assertFalse($user->fresh()->shouldSeeOnboarding());

        $response = $this->actingAs($user)
            ->post(route('users.restartOnboarding', ['id' => $user->id]));

        $response->assertRedirect('/');
        $response->assertSessionHas('show_onboarding', true);

        $profile = $user->fresh()->profile;
        $this->assertNull($profile->onboarding_completed_at);
        $this->assertNull($profile->onboarding_dismissed_at);
    }

    /** @test */
    public function a_user_cannot_restart_onboarding_for_another_user(): void
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $other = User::factory()->create(['user_status_id' => 1]);
        $other->profile()->create(['user_id' => $other->id, 'onboarding_completed_at' => now()]);

        $this->actingAs($user)
            ->post(route('users.restartOnboarding', ['id' => $other->id]));

        // The other user's completed flag is untouched.
        $this->assertNotNull($other->fresh()->profile->onboarding_completed_at);
    }

    /** @test */
    public function data_endpoint_returns_popular_lists(): void
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        Entity::factory()->create();
        Tag::factory()->create();
        Event::factory()->create(['start_at' => now()->addWeek()]);

        $response = $this->actingAs($user)->getJson(route('onboarding.data'));

        $response->assertOk()
            ->assertJsonStructure(['entities', 'tags', 'events']);
    }

    /** @test */
    public function onboarding_routes_require_authentication(): void
    {
        $this->withExceptionHandling();

        $this->getJson(route('onboarding.data'))->assertUnauthorized();
        $this->postJson(route('onboarding.dismiss'))->assertUnauthorized();
    }
}
