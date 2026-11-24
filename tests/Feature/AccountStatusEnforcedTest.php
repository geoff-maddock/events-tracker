<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Auth\SessionGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A suspended or banned account is refused however it signs in.
 *
 * Each test makes one request and signs in the way a browser or client does:
 * the session holds the user id, or the request carries a token or basic
 * credentials. `actingAs()` would put the user on the guard before any
 * middleware runs, which hides whether the check runs late enough to see them.
 */
class AccountStatusEnforcedTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const PASSWORD = 'zz-Secret-pass-19';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    /**
     * @return array<string, array{int}>
     */
    public static function refusedStatuses(): array
    {
        return ['suspended' => [UserStatus::SUSPENDED], 'banned' => [UserStatus::BANNED]];
    }

    private function user(int $status): User
    {
        return User::factory()->create([
            'user_status_id' => $status,
            'password' => bcrypt(self::PASSWORD),
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function basic(User $user): array
    {
        return ['Authorization' => 'Basic '.base64_encode($user->email.':'.self::PASSWORD)];
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionOf(User $user): array
    {
        return [$this->sessionKey() => $user->id];
    }

    /**
     * Where the session guard keeps the signed-in user's id.
     */
    private function sessionKey(): string
    {
        $guard = Auth::guard('web');
        $this->assertInstanceOf(SessionGuard::class, $guard);

        return $guard->getName();
    }

    #[Test]
    #[DataProvider('refusedStatuses')]
    public function a_signed_in_session_is_ended(int $status): void
    {
        $user = $this->user($status);

        $this->withSession($this->sessionOf($user))->get('/events/create')
            ->assertRedirect(route('login'))
            ->assertSessionMissing($this->sessionKey());
    }

    #[Test]
    #[DataProvider('refusedStatuses')]
    public function a_session_is_ended_on_a_page_that_needs_no_sign_in(int $status): void
    {
        $user = $this->user($status);

        $this->withSession($this->sessionOf($user))->get('/events')
            ->assertRedirect(route('login'));
    }

    #[Test]
    #[DataProvider('refusedStatuses')]
    public function signing_in_does_not_get_past_the_next_page(int $status): void
    {
        $user = $this->user($status);

        $this->post('/login', ['email' => $user->email, 'password' => self::PASSWORD]);
        $this->app['auth']->forgetGuards();

        $this->get('/events/create')->assertRedirect(route('login'));
    }

    #[Test]
    #[DataProvider('refusedStatuses')]
    public function an_api_token_is_refused_and_deleted(int $status): void
    {
        $user = $this->user(UserStatus::ACTIVE);
        $token = $user->createToken('client')->plainTextToken;
        // changed underneath, as when the status hook did not run
        User::whereKey($user->id)->toBase()->update(['user_status_id' => $status]);

        $this->getJson('/api/events?limit=1', ['Authorization' => 'Bearer '.$token])->assertStatus(401);

        $this->assertSame(0, $user->tokens()->count());
    }

    #[Test]
    #[DataProvider('refusedStatuses')]
    public function a_token_is_refused_on_a_token_only_route(int $status): void
    {
        $user = $this->user(UserStatus::ACTIVE);
        $token = $user->createToken('client')->plainTextToken;
        User::whereKey($user->id)->toBase()->update(['user_status_id' => $status]);

        $this->getJson('/api/auth/me', ['Authorization' => 'Bearer '.$token])->assertStatus(401);
    }

    #[Test]
    #[DataProvider('refusedStatuses')]
    public function basic_auth_is_refused(int $status): void
    {
        $user = $this->user($status);

        $this->getJson('/api/events?limit=1', $this->basic($user))->assertStatus(401);
    }

    #[Test]
    #[DataProvider('refusedStatuses')]
    public function a_new_token_cannot_be_created(int $status): void
    {
        $user = $this->user($status);

        $this->postJson('/api/tokens/create', ['token_name' => 'client'], $this->basic($user))->assertStatus(401);

        $this->assertSame(0, $user->tokens()->count());
    }

    #[Test]
    #[DataProvider('refusedStatuses')]
    public function changing_the_status_deletes_the_tokens(int $status): void
    {
        $user = $this->user(UserStatus::ACTIVE);
        $user->createToken('client');

        $user->update(['user_status_id' => $status]);

        $this->assertSame(0, $user->tokens()->count());
    }

    #[Test]
    public function an_active_session_is_let_through(): void
    {
        $user = $this->user(UserStatus::ACTIVE);

        $this->withSession($this->sessionOf($user))->get('/events/create')->assertOk();
    }

    #[Test]
    public function an_active_token_is_let_through(): void
    {
        $user = $this->user(UserStatus::ACTIVE);
        $token = $user->createToken('client')->plainTextToken;

        $this->getJson('/api/auth/me', ['Authorization' => 'Bearer '.$token])->assertOk();
        $this->assertSame(1, $user->tokens()->count());
    }

    #[Test]
    public function active_basic_auth_is_let_through(): void
    {
        $user = $this->user(UserStatus::ACTIVE);

        $this->getJson('/api/events?limit=1', $this->basic($user))->assertOk();
    }

    #[Test]
    public function an_active_account_can_create_a_token(): void
    {
        $user = $this->user(UserStatus::ACTIVE);

        $this->postJson('/api/tokens/create', ['token_name' => 'client'], $this->basic($user))->assertOk();
        $this->assertSame(1, $user->tokens()->count());
    }

    #[Test]
    public function a_guest_is_let_through(): void
    {
        $this->get('/events')->assertOk();
        $this->getJson('/api/search?q=zz')->assertOk();
    }
}
