<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventResponse;
use App\Models\Profile;
use App\Models\ResponseType;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What an account may show before it is trusted (#2312): its bio and social
 * handles are private to itself and admins, and its bio can't hold links.
 */
class UserTrustTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function member(array $attributes = [], string $bio = 'ZZ spam bio text'): User
    {
        $user = User::factory()->create(array_merge(['user_status_id' => UserStatus::ACTIVE], $attributes));
        Profile::where('user_id', $user->id)->delete();
        $profile = new Profile();
        $profile->user_id = $user->id;
        $profile->bio = $bio;
        $profile->instagram_username = 'zz_handle';
        $profile->setting_public_profile = 1;
        $profile->save();

        return $user->fresh();
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $admin->assignGroup('admin');

        return $admin->fresh();
    }

    public function test_a_new_account_is_not_trusted(): void
    {
        $this->assertFalse($this->member()->isTrusted());
    }

    public function test_an_approved_account_is_trusted(): void
    {
        $this->assertTrue($this->member(['trusted_at' => now()])->isTrusted());
    }

    public function test_an_established_account_that_has_done_something_is_trusted(): void
    {
        $old = $this->member(['created_at' => now()->subDays(30)]);
        $this->assertFalse($old->isTrusted(), 'age alone is not enough');

        EventResponse::create([
            'user_id' => $old->id,
            'event_id' => Event::factory()->create()->id,
            'response_type_id' => ResponseType::ATTENDING,
        ]);

        $this->assertTrue($old->fresh()->isTrusted());
    }

    public function test_pending_unverified_or_banned_accounts_are_never_trusted(): void
    {
        $this->assertFalse($this->member(['trusted_at' => now(), 'user_status_id' => UserStatus::PENDING])->isTrusted());
        $this->assertFalse($this->member(['trusted_at' => now(), 'user_status_id' => UserStatus::BANNED])->isTrusted());
        $this->assertFalse($this->member(['trusted_at' => now(), 'email_verified_at' => null])->isTrusted());
    }

    public function test_guests_and_members_do_not_see_an_untrusted_bio(): void
    {
        $user = $this->member();

        $this->get(route('users.show', $user))->assertOk()
            ->assertDontSee('ZZ spam bio text')
            ->assertDontSee('zz_handle');

        $other = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->actingAs($other)->get(route('users.show', $user))->assertOk()
            ->assertDontSee('ZZ spam bio text');
    }

    public function test_the_owner_and_admins_see_an_untrusted_bio(): void
    {
        $user = $this->member();

        $this->actingAs($user)->get(route('users.show', $user))->assertOk()
            ->assertSee('ZZ spam bio text')
            ->assertSee('Only you can see your bio');

        $this->actingAs($this->admin())->get(route('users.show', $user))->assertOk()
            ->assertSee('ZZ spam bio text');
    }

    public function test_everyone_sees_a_trusted_bio(): void
    {
        $user = $this->member(['trusted_at' => now()]);

        $this->get(route('users.show', $user))->assertOk()
            ->assertSee('ZZ spam bio text')
            ->assertSee('zz_handle');
    }

    public function test_the_api_hides_an_untrusted_bio_from_others(): void
    {
        $user = $this->member();
        $other = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($other, 'sanctum')->getJson("/api/users/{$user->id}")->assertOk()
            ->assertJsonPath('profile.bio', null)
            ->assertJsonPath('profile.instagram_username', null);

        $this->actingAs($user, 'sanctum')->getJson("/api/users/{$user->id}")->assertOk()
            ->assertJsonPath('profile.bio', 'ZZ spam bio text');
    }

    public function test_an_untrusted_account_cannot_put_links_in_its_bio(): void
    {
        $user = $this->member([], '');

        $this->actingAs($user)->put("/users/{$user->slug}", [
            'name' => $user->name, 'email' => $user->email, 'bio' => 'Visit https://spam.example now',
        ])->assertSessionHasErrors('bio');

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/users/{$user->id}", ['profile' => ['bio' => 'see www.spam.example']])
            ->assertStatus(422)->assertJsonValidationErrors('profile.bio');

        $this->assertSame('', (string) $user->fresh()->profile->bio);
    }

    public function test_a_trusted_account_or_an_admin_can_add_links(): void
    {
        $trusted = $this->member(['trusted_at' => now()], '');
        $this->actingAs($trusted)->put("/users/{$trusted->slug}", [
            'name' => $trusted->name, 'email' => $trusted->email, 'bio' => 'My label: https://label.example',
        ])->assertSessionHasNoErrors();

        $untrusted = $this->member([], '');
        $this->actingAs($this->admin())->put("/users/{$untrusted->slug}", [
            'name' => $untrusted->name, 'email' => $untrusted->email, 'bio' => 'Their site: https://site.example',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Their site: https://site.example', $untrusted->fresh()->profile->bio);
    }

    public function test_the_bio_has_a_length_cap(): void
    {
        $user = $this->member(['trusted_at' => now()], '');

        $this->actingAs($user)->put("/users/{$user->slug}", [
            'name' => $user->name, 'email' => $user->email,
            'bio' => str_repeat('a', (int) config('signup.bio_max_length') + 1),
        ])->assertSessionHasErrors('bio');
    }

    public function test_an_unverified_account_cannot_comment(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::PENDING, 'email_verified_at' => null]);
        $event = Event::factory()->create();

        $this->actingAs($user)
            ->post("/events/{$event->id}/comments", ['message' => 'ZZ unverified comment'])
            ->assertRedirect(route('verification.notice'));

        $this->assertDatabaseMissing('comments', ['message' => 'ZZ unverified comment']);
    }
}
