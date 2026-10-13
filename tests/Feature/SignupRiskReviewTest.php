<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use App\Models\UserStatus;
use App\Services\Signup\SignupRiskScorer;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Signup risk scoring and the admin review list (#2312). A risky signup
 * stays Pending after it verifies; an admin approves it or bans it as spam.
 */
class SignupRiskReviewTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        Notification::fake();
        Validator::extend('captcha', fn () => true);
    }

    private function apiRegister(string $name, string $email): User
    {
        $this->postJson('/api/register', [
            'name' => $name,
            'email' => $email,
            'password' => 'password1234',
            'g-recaptcha-response' => 'valid-captcha-token',
        ])->assertStatus(201);

        return User::where('email', $email)->sole();
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $admin->assignGroup('admin');

        return $admin->fresh();
    }

    public function test_an_ordinary_signup_scores_low_and_activates_on_verification(): void
    {
        $user = $this->apiRegister('Jane Doe', 'jane-doe@example.com');

        $this->assertSame(0, $user->risk_score);
        $this->assertSame([], $user->risk_reasons);

        event(new Verified($user));
        $this->assertSame(UserStatus::ACTIVE, (int) $user->fresh()->user_status_id);
    }

    public function test_a_risky_signup_stays_pending_after_verification(): void
    {
        $user = $this->apiRegister('Kevinzzexods', 'kz@spammy-zz.ru');

        $this->assertEqualsCanonicalizing(['name_pattern', 'suspicious_tld'], $user->risk_reasons);
        $this->assertSame(3, $user->risk_score);

        $user->markEmailAsVerified();
        event(new Verified($user));

        $this->assertSame(UserStatus::PENDING, (int) $user->fresh()->user_status_id);
    }

    public function test_verification_does_not_reactivate_a_banned_account(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::BANNED]);

        event(new Verified($user));

        $this->assertSame(UserStatus::BANNED, (int) $user->fresh()->user_status_id);
    }

    public function test_a_burst_of_signups_from_one_ip_is_flagged(): void
    {
        config(['signup.ip_burst_threshold' => 2]);

        $this->apiRegister('Burst One', 'burst-1@example.com');
        $second = $this->apiRegister('Burst Two', 'burst-2@example.com');
        $third = $this->apiRegister('Burst Three', 'burst-3@example.com');

        $this->assertNotContains('ip_burst', $second->risk_reasons);
        $this->assertContains('ip_burst', $third->risk_reasons);
    }

    public function test_stopforumspam_flags_a_listed_email_when_enabled(): void
    {
        config(['signup.stopforumspam.enabled' => true]);
        Http::fake(['api.stopforumspam.org/*' => Http::response([
            'success' => 1,
            'emailhash' => ['appears' => 1, 'frequency' => 12],
            'ip' => ['appears' => 0],
        ])]);

        $user = $this->apiRegister('Listed Person', 'listed@example.com');

        $this->assertContains('stopforumspam', $user->risk_reasons);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'emailhash='.md5('listed@example.com'))
            && !str_contains($request->url(), 'listed%40example.com'));
    }

    public function test_a_failing_stopforumspam_lookup_does_not_block_signup(): void
    {
        config(['signup.stopforumspam.enabled' => true]);
        Http::fake(fn () => throw new ConnectionException('timed out'));

        $user = $this->apiRegister('Lookup Down', 'lookup-down@example.com');

        $this->assertNotContains('stopforumspam', $user->risk_reasons);
    }

    public function test_a_new_account_turning_its_bio_into_spam_goes_back_to_pending(): void
    {
        $user = User::factory()->create(['name' => 'Jessicazzstync', 'user_status_id' => UserStatus::ACTIVE]);
        $profile = new Profile();
        $profile->user_id = $user->id;
        $profile->save();

        $this->actingAs($user)->put("/users/{$user->slug}", [
            'name' => $user->name,
            'email' => $user->email,
            'bio' => 'Best casino bonuses, message me',
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertEqualsCanonicalizing(['name_pattern', 'spam_keywords'], $user->risk_reasons);
        $this->assertSame(UserStatus::PENDING, (int) $user->user_status_id);
    }

    public function test_a_trusted_account_is_not_rescored(): void
    {
        $user = User::factory()->create(['name' => 'Jessicazzstync', 'user_status_id' => UserStatus::ACTIVE, 'trusted_at' => now()]);

        $this->actingAs($user)->put("/users/{$user->slug}", [
            'name' => $user->name, 'email' => $user->email, 'bio' => 'I write about casino architecture',
        ])->assertSessionHasNoErrors();

        $this->assertSame(UserStatus::ACTIVE, (int) $user->fresh()->user_status_id);
        $this->assertNull($user->fresh()->risk_score);
    }

    public function test_only_admins_can_open_the_review_list(): void
    {
        $member = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->get(route('users.signups'))->assertRedirect(route('login'));
        $this->actingAs($member)->get(route('users.signups'))->assertForbidden();
        $this->actingAs($member)->post(route('users.signups.update'), ['action' => 'approve', 'ids' => [$member->id]])
            ->assertForbidden();
    }

    public function test_the_review_list_shows_flagged_signups_with_their_reasons(): void
    {
        $risky = $this->apiRegister('Kevinzzexods', 'kz@spammy-zz.ru');
        $fine = $this->apiRegister('Jane Doe', 'jane-doe@example.com');

        $this->actingAs($this->admin())->get(route('users.signups', ['show' => 'flagged']))
            ->assertOk()
            ->assertSee($risky->name)
            ->assertSee(SignupRiskScorer::LABELS['suspicious_tld'])
            ->assertDontSee($fine->email);
    }

    public function test_approving_a_signup_trusts_and_activates_it(): void
    {
        $user = $this->apiRegister('Kevinzzexods', 'kz@spammy-zz.ru');

        $this->actingAs($this->admin())
            ->post(route('users.signups.update'), ['action' => 'approve', 'ids' => [$user->id]])
            ->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->trusted_at);
        $this->assertSame(UserStatus::ACTIVE, (int) $user->user_status_id);
        $this->assertTrue($user->isTrusted());
    }

    public function test_banning_a_signup_bans_it_and_clears_its_profile(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::PENDING]);
        $profile = new Profile();
        $profile->user_id = $user->id;
        $profile->bio = 'ZZ spam bio';
        $profile->instagram_username = 'zz_spam';
        $profile->save();

        $this->actingAs($this->admin())
            ->post(route('users.signups.update'), ['action' => 'ban', 'ids' => [$user->id]])
            ->assertRedirect();

        $user->refresh();
        $this->assertSame(UserStatus::BANNED, (int) $user->user_status_id);
        $this->assertNull($user->profile->bio);
        $this->assertNull($user->profile->instagram_username);
        // the row stays, so the address can't register again
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_admins_cannot_be_banned_from_the_review_list(): void
    {
        $admin = $this->admin();
        $other = $this->admin();

        $this->actingAs($admin)
            ->post(route('users.signups.update'), ['action' => 'ban', 'ids' => [$admin->id, $other->id]]);

        $this->assertSame(UserStatus::ACTIVE, (int) $admin->fresh()->user_status_id);
        $this->assertSame(UserStatus::ACTIVE, (int) $other->fresh()->user_status_id);
    }
}
