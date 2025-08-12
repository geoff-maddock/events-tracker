<?php

namespace Tests\Feature;

use App\Models\Action;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /** @test */
    public function it_records_activity_when_api_password_reset_requested()
    {
        config(['app.password_reset_secret' => 'test-secret']);

        $user = User::factory()->create([
            'email' => 'jane@example.com',
        ]);

        $response = $this->postJson('/api/user/send-password-reset-email', [
            'email' => $user->email,
            'secret' => 'test-secret',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('activities', [
            'object_table' => 'User',
            'user_id' => $user->id,
            'object_id' => $user->id,
            'object_name' => $user->email,
            'action_id' => Action::PASSWORD_RESET_REQUEST,
            'message' => 'Password reset link requested for ' . $user->email,
        ]);
    }
}

