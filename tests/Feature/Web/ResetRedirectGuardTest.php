<?php

namespace Tests\Feature\Web;

use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResetRedirectGuardTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_events_reset_with_invalid_redirect_falls_back_to_index(): void
    {
        $this->withExceptionHandling();

        $this->get('/events/reset?redirect=nonexistent.route')
            ->assertRedirect(route('events.index'));
    }

    public function test_events_reset_with_valid_redirect_is_honored(): void
    {
        $this->get('/events/reset?redirect=events.grid')
            ->assertRedirect(route('events.grid'));
    }

    public function test_events_rpp_reset_with_invalid_redirect_falls_back_to_index(): void
    {
        $this->withExceptionHandling();

        $this->get('/events/rpp-reset?redirect=nonexistent.route')
            ->assertRedirect(route('events.index'));
    }

    public function test_blogs_reset_with_invalid_redirect_falls_back_to_index(): void
    {
        $this->withExceptionHandling();

        $this->get('/blogs/reset?redirect=nonexistent.route')
            ->assertRedirect(route('blogs.index'));
    }

    public function test_reset_user_attending_no_longer_accepts_get(): void
    {
        $this->withExceptionHandling();

        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($user)
            ->get('/users/'.$user->id.'/reset-user-attending')
            ->assertStatus(405);
    }

    public function test_reset_user_attending_accepts_post(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($user)
            ->post('/users/'.$user->id.'/reset-user-attending')
            ->assertRedirect(route('users.attending', ['id' => $user->id]));
    }
}
