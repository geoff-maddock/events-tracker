<?php

namespace Tests\Feature\Web;

use App\Models\Event;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_index_loads(): void
    {
        $this->get('/events')->assertOk();
    }

    public function test_index_future_loads(): void
    {
        $this->get('/events/future')->assertOk();
    }

    public function test_index_today_loads(): void
    {
        $this->get('/events/today')->assertOk();
    }

    public function test_index_week_loads(): void
    {
        $this->get('/events/week')->assertOk();
    }

    public function test_index_past_loads(): void
    {
        $this->get('/events/past')->assertOk();
    }

    public function test_index_upcoming_loads(): void
    {
        $this->get('/events/upcoming')->assertOk();
    }

    public function test_show_loads_for_existing_event(): void
    {
        $event = Event::factory()->create();

        $this->get('/events/'.$event->slug)->assertOk();
    }

    public function test_create_form_requires_auth(): void
    {
        // Routes may use `verified` middleware which sends guests to
        // email/verify rather than /login; either is acceptable.
        $response = $this->get('/events/create');

        $response->assertStatus(302);
        $this->assertTrue(
            str_contains($response->headers->get('Location'), '/login')
            || str_contains($response->headers->get('Location'), '/email/verify')
        );
    }

    public function test_create_form_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($user)->get('/events/create')->assertOk();
    }

    public function test_edit_form_requires_auth(): void
    {
        $event = Event::factory()->create();

        $response = $this->get('/events/'.$event->slug.'/edit');

        $response->assertStatus(302);
        $this->assertTrue(
            str_contains($response->headers->get('Location'), '/login')
            || str_contains($response->headers->get('Location'), '/email/verify')
        );
    }
}
