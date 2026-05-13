<?php

namespace Tests\Feature\Web;

use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersControllerSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->actingAs($this->user);
    }

    private User $user;

    public function test_users_index_renders(): void
    {
        $this->get('/users')->assertOk();
    }

    public function test_users_create_renders(): void
    {
        $this->get('/users/create')->assertOk();
    }

    public function test_users_edit_renders_for_self(): void
    {
        $this->get('/users/'.$this->user->id.'/edit')->assertOk();
    }
}
