<?php

namespace Tests\Feature\Web;

use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesControllerSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['user_status_id' => UserStatus::ACTIVE]));
    }

    public function test_all_modules_page_renders(): void
    {
        $this->get('/all-modules')->assertOk();
    }
}
