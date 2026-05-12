<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\AuthenticateEither;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthenticateEitherTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        Route::middleware(AuthenticateEither::class)
            ->get('/_test/auth-either', fn () => response()->json(['ok' => true]));
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        // Laravel's basic-auth path throws UnauthorizedHttpException for
        // requests with no Authorization header, so the middleware's own
        // JSON 'Unauthorized' branch is unreachable; we just assert 401 here.
        $this->getJson('/_test/auth-either')->assertStatus(401);
    }

    public function test_sanctum_authenticated_request_succeeds(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/_test/auth-either')
            ->assertStatus(200)
            ->assertJson(['ok' => true]);
    }
}
