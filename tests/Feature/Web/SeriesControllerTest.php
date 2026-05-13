<?php

namespace Tests\Feature\Web;

use App\Models\Series;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeriesControllerTest extends TestCase
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
        $this->get('/series')->assertOk();
    }

    public function test_show_loads_for_existing_series(): void
    {
        $series = Series::factory()->create();

        $this->get('/series/'.$series->slug)->assertOk();
    }

    public function test_create_form_requires_auth(): void
    {
        $this->get('/series/create')->assertStatus(302);
    }

    public function test_create_form_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($user)->get('/series/create')->assertOk();
    }
}
