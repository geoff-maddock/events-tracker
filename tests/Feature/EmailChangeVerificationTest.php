<?php

namespace Tests\Feature;

use App\Mail\EmailChanged;
use App\Models\Forum;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Users who change their own email must verify the new address before
 * content writes resume; an admin's change keeps them verified (#2321).
 */
class EmailChangeVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        Notification::fake();
        Mail::fake();
    }

    private function makeUser(string $email, ?string $group = null): User
    {
        $user = User::factory()->create([
            'name' => 'Person '.strtok($email, '@'),
            'email' => $email,
            'user_status_id' => UserStatus::ACTIVE,
        ]);
        $user->profile()->create([]);
        if ($group) {
            $user->assignGroup($group);
        }

        return $user->fresh();
    }

    /**
     * @return array<string, string>
     */
    private function tokenHeaders(User $user): array
    {
        return ['Accept' => 'application/json', 'Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken];
    }

    private function assertReverificationSent(User $user, string $oldEmail): void
    {
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);
        Mail::assertSent(EmailChanged::class, fn (EmailChanged $mail) => $mail->hasTo($oldEmail));
    }

    private function assertNothingSent(): void
    {
        Notification::assertNothingSent();
        Mail::assertNotSent(EmailChanged::class);
    }

    public function test_changing_your_own_email_on_the_web_requires_verifying_it(): void
    {
        $user = $this->makeUser('before@example.com');

        $this->actingAs($user)
            ->patch(route('users.update', $user->id), ['name' => $user->name, 'email' => 'after@example.com'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('show_verification_notice', 'after@example.com')
            ->assertSessionHas('verification_notice_reason', 'email-changed');

        $user->refresh();
        $this->assertSame('after@example.com', $user->email);
        $this->assertReverificationSent($user, 'before@example.com');

        $this->actingAs($user)->get(route('events.create'))->assertRedirect(route('verification.notice'));
    }

    public function test_resaving_the_same_email_on_the_web_keeps_verification(): void
    {
        $user = $this->makeUser('same@example.com');

        $this->actingAs($user)
            ->patch(route('users.update', $user->id), ['name' => $user->name, 'email' => 'same@example.com'])
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('show_verification_notice');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertNothingSent();
    }

    public function test_changing_only_the_case_of_your_email_keeps_verification(): void
    {
        $user = $this->makeUser('casey@example.com');

        $this->actingAs($user)
            ->patch(route('users.update', $user->id), ['name' => $user->name, 'email' => 'Casey@Example.com'])
            ->assertSessionHasNoErrors();

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertNothingSent();
    }

    public function test_an_admin_changing_another_users_email_on_the_web_keeps_them_verified(): void
    {
        $admin = $this->makeUser('admin@example.com', 'admin');
        $target = $this->makeUser('target@example.com');

        $this->actingAs($admin)
            ->patch(route('users.update', $target->id), ['name' => $target->name, 'email' => 'moved@example.com'])
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('show_verification_notice');

        $target->refresh();
        $this->assertSame('moved@example.com', $target->email);
        $this->assertTrue($target->hasVerifiedEmail());
        $this->assertNothingSent();
    }

    public function test_changing_your_own_email_through_the_api_requires_verifying_it(): void
    {
        $user = $this->makeUser('api-before@example.com');
        $headers = $this->tokenHeaders($user);

        $this->putJson('/api/users/'.$user->id, ['email' => 'api-after@example.com'], $headers)
            ->assertOk()
            ->assertJson(['email' => 'api-after@example.com', 'email_verified_at' => null]);

        $user->refresh();
        $this->assertReverificationSent($user, 'api-before@example.com');

        // a new request resolves the token's user afresh, as in production
        $this->app['auth']->forgetGuards();

        $this->postJson('/api/threads', [
            'name' => 'ZZ after email change',
            'body' => 'Thread body',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'forum_id' => Forum::factory()->create()->id,
        ], $headers)
            ->assertForbidden()
            ->assertJson(['message' => 'Your email address is not verified.']);

        $this->assertDatabaseMissing('threads', ['name' => 'ZZ after email change']);
    }

    public function test_resaving_the_same_email_through_the_api_keeps_verification(): void
    {
        $user = $this->makeUser('api-same@example.com');

        $this->putJson('/api/users/'.$user->id, ['email' => 'api-same@example.com'], $this->tokenHeaders($user))
            ->assertOk();

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertNothingSent();
    }

    public function test_an_admin_changing_another_users_email_through_the_api_keeps_them_verified(): void
    {
        $admin = $this->makeUser('api-admin@example.com', 'admin');
        $target = $this->makeUser('api-target@example.com');

        $this->putJson('/api/users/'.$target->id, ['email' => 'api-moved@example.com'], $this->tokenHeaders($admin))
            ->assertOk();

        $target->refresh();
        $this->assertSame('api-moved@example.com', $target->email);
        $this->assertTrue($target->hasVerifiedEmail());
        $this->assertNothingSent();
    }
}
