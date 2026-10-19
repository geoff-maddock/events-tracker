<?php

namespace Tests\Feature;

use App\Mail\EmailChanged;
use App\Models\Forum;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use App\Services\EmailChangeVerifier;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Users who change their own email must verify the new address before
 * content writes resume; an admin's change, including to their own email,
 * keeps them verified (#2321).
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

    /**
     * @param array<string, mixed> $attributes
     */
    private function makeUser(string $email, ?string $group = null, array $attributes = []): User
    {
        $user = User::factory()->create($attributes + [
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

    public function test_an_admin_changing_their_own_email_stays_verified(): void
    {
        $admin = $this->makeUser('self-admin@example.com', 'admin');

        $this->actingAs($admin)
            ->patch(route('users.update', $admin->id), ['name' => $admin->name, 'email' => 'self-admin-new@example.com'])
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('show_verification_notice');

        $admin->refresh();
        $this->assertSame('self-admin-new@example.com', $admin->email);
        $this->assertTrue($admin->hasVerifiedEmail());
        $this->assertNothingSent();
    }

    public function test_an_unverified_account_changing_its_email_does_not_notify_the_unconfirmed_address(): void
    {
        $user = $this->makeUser('typo@gmial.com', null, ['user_status_id' => UserStatus::PENDING, 'email_verified_at' => null]);

        $this->actingAs($user)
            ->patch(route('users.update', $user->id), ['name' => $user->name, 'email' => 'fixed@gmail.com'])
            ->assertSessionHasNoErrors();

        $user->refresh();
        Notification::assertSentTo($user, VerifyEmail::class);
        Mail::assertNotSent(EmailChanged::class);
        // still a never-verified signup, so cleanup may remove it as before
        $this->assertNull($user->email_change_pending_at);
    }

    public function test_cleanup_and_purge_keep_an_account_unverified_only_by_an_email_change(): void
    {
        $admin = $this->makeUser('purge-admin@example.com', 'admin');
        $changed = $this->makeUser('old-member@example.com', null, ['created_at' => now()->subYear()]);
        // a verified signup held at Pending for review (#2312)
        $held = $this->makeUser('held@example.com', null, ['user_status_id' => UserStatus::PENDING, 'created_at' => now()->subYear()]);
        $stale = $this->makeUser('stale@example.com', null, ['user_status_id' => UserStatus::PENDING, 'email_verified_at' => null, 'created_at' => now()->subYear()]);

        foreach ([$changed, $held] as $user) {
            $this->actingAs($user)
                ->patch(route('users.update', $user->id), ['name' => $user->name, 'email' => 'new-'.$user->email])
                ->assertSessionHasNoErrors();
            $this->assertNotNull($user->fresh()->email_change_pending_at);
        }

        $this->actingAs($admin)->post(route('users.purge'))->assertRedirect();
        $this->artisan('userCleanup')->assertSuccessful();

        $this->assertNotNull($changed->fresh());
        $this->assertNotNull($held->fresh());
        $this->assertNull($stale->fresh());
    }

    public function test_verifying_the_new_address_ends_the_pending_change(): void
    {
        $user = $this->makeUser('verify-old@example.com');

        $this->actingAs($user)
            ->patch(route('users.update', $user->id), ['name' => $user->name, 'email' => 'verify-new@example.com'])
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertNotNull($user->email_change_pending_at);

        $user->markEmailAsVerified();

        $user->refresh();
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertNull($user->email_change_pending_at);
    }

    public function test_email_changes_are_rate_limited(): void
    {
        $user = $this->makeUser('limit-0@example.com');
        $headers = $this->tokenHeaders($user);

        for ($i = 1; $i <= EmailChangeVerifier::MAX_CHANGES_PER_HOUR; ++$i) {
            $this->putJson('/api/users/'.$user->id, ['email' => "limit-{$i}@example.com"], $headers)->assertOk();
        }

        $this->putJson('/api/users/'.$user->id, ['email' => 'limit-over@example.com'], $headers)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertSame('limit-'.EmailChangeVerifier::MAX_CHANGES_PER_HOUR.'@example.com', $user->fresh()->email);
    }

    public function test_an_email_change_link_verifies_without_signing_in_whoever_clicks_it(): void
    {
        $user = $this->makeUser('link-old@example.com');

        $this->actingAs($user)
            ->patch(route('users.update', $user->id), ['name' => $user->name, 'email' => 'link-typo@example.com'])
            ->assertSessionHasNoErrors();

        // the new inbox may be a stranger's, opening the link signed out
        auth()->logout();
        $this->app['auth']->forgetGuards();

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $user->id, 'hash' => sha1('link-typo@example.com')], false);

        $this->get($url)->assertRedirect(route('login'));

        $this->assertGuest();
        $user->refresh();
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertNull($user->email_change_pending_at);
    }

    public function test_a_disposable_address_is_refused_on_change_but_an_existing_one_can_be_resaved(): void
    {
        $user = $this->makeUser('keeper@example.com');

        $this->actingAs($user)
            ->patch(route('users.update', $user->id), ['name' => $user->name, 'email' => 'throwaway@mailinator.com'])
            ->assertSessionHasErrors('email');
        $this->putJson('/api/users/'.$user->id, ['email' => 'throwaway@mailinator.com'], $this->tokenHeaders($user))
            ->assertJsonValidationErrors('email');
        $this->assertSame('keeper@example.com', $user->fresh()->email);

        $legacy = $this->makeUser('legacy@mailinator.com');

        $this->actingAs($legacy)
            ->patch(route('users.update', $legacy->id), ['name' => $legacy->name, 'email' => 'legacy@mailinator.com'])
            ->assertSessionHasNoErrors();
    }

    public function test_an_admin_correcting_an_unverified_users_email_sends_a_new_link(): void
    {
        $admin = $this->makeUser('fix-admin@example.com', 'admin');
        $user = $this->makeUser('fix-old@example.com');

        $this->actingAs($user)
            ->patch(route('users.update', $user->id), ['name' => $user->name, 'email' => 'fix-typo@exmaple.com'])
            ->assertSessionHasNoErrors();
        Notification::fake();
        Mail::fake();

        $this->actingAs($admin)
            ->patch(route('users.update', $user->id), ['name' => $user->name, 'email' => 'fix-right@example.com'])
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('show_verification_notice');

        $user->refresh();
        $this->assertSame('fix-right@example.com', $user->email);
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);
        Mail::assertNotSent(EmailChanged::class);
    }

    public function test_cleanup_reminds_a_pending_email_change_once(): void
    {
        $due = $this->makeUser('remind-due@example.com', null, ['email_verified_at' => null, 'email_change_pending_at' => now()->subDays(3)->subHours(12)]);
        $recent = $this->makeUser('remind-recent@example.com', null, ['email_verified_at' => null, 'email_change_pending_at' => now()->subDay()]);
        $reminded = $this->makeUser('remind-done@example.com', null, ['email_verified_at' => null, 'email_change_pending_at' => now()->subDays(5)]);

        $this->artisan('userCleanup')->assertSuccessful();

        Notification::assertSentTo($due, VerifyEmail::class);
        Notification::assertNotSentTo($recent, VerifyEmail::class);
        Notification::assertNotSentTo($reminded, VerifyEmail::class);
        $this->assertNotNull($reminded->fresh());
    }
}
