<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterFormTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_registration_form_loads_for_guests(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_registration_form_redirects_authenticated_users(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($user)->get('/register')->assertStatus(302);
    }

    public function test_register_validates_required_fields(): void
    {
        $this->post('/register', [])
            ->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_register_validates_password_confirmation(): void
    {
        $this->post('/register', [
            'name' => 'Test',
            'email' => 'reg-zz-'.uniqid().'@example.com',
            'password' => 'short1234',
            'password_confirmation' => 'mismatch1',
        ])->assertSessionHasErrors('password');
    }
}
