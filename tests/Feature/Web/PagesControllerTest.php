<?php

namespace Tests\Feature\Web;

use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_home_page_loads(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_home_alias_loads(): void
    {
        $this->get('/home')->assertOk();
    }

    public function test_about_page_loads(): void
    {
        $this->get('/about')->assertOk();
    }

    public function test_privacy_page_loads(): void
    {
        $this->get('/privacy')->assertOk();
    }

    public function test_tos_page_loads(): void
    {
        $this->get('/tos')->assertOk();
    }

    public function test_help_page_loads(): void
    {
        $this->get('/help')->assertOk();
    }

    public function test_popular_page_loads(): void
    {
        $this->get('/popular')->assertOk();
    }

    public function test_radar_page_requires_auth(): void
    {
        $this->get('/radar')->assertRedirect('/login');
    }

    public function test_radar_page_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($user)->get('/radar')->assertOk();
    }

    public function test_all_modules_page_loads(): void
    {
        $this->get('/all-modules')->assertOk();
    }

    public function test_search_page_loads(): void
    {
        $this->get('/search')->assertOk();
    }

    public function test_tools_page_requires_auth(): void
    {
        $this->get('/tools')->assertRedirect('/login');
    }

    public function test_tools_page_redirects_non_admin_user(): void
    {
        // /tools requires the show_admin permission. A vanilla active user
        // does not have it, so they should be redirected home with a flash
        // (previously this branch called exit() and killed the response).
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($user)->get('/tools')->assertRedirect(route('home'));
    }
}
