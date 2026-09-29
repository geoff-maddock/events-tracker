<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * User and profile writes save validated input only (#2180).
 */
class UserValidatedWritesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
    }

    public function test_creating_a_user_takes_only_the_account_fields_and_starts_pending(): void
    {
        $this->actingAs($this->user)->post('/users', [
            'name' => 'ZZ New Person',
            'email' => 'zz-new@example.com',
            'password' => 'a-long-password',
            'user_status_id' => UserStatus::ACTIVE,
            'slug' => 'zz-chosen-slug',
        ])->assertRedirect();

        $created = User::where('email', 'zz-new@example.com')->sole();
        $this->assertSame(UserStatus::PENDING, (int) $created->user_status_id);
        $this->assertNotSame('zz-chosen-slug', $created->slug);
        $this->assertSame(1, Profile::where('user_id', $created->id)->count());
    }

    public function test_updating_a_profile_saves_every_form_field_and_ignores_admin_fields(): void
    {
        $this->actingAs($this->user)->put("/users/{$this->user->slug}", [
            'name' => 'ZZ Updated Name',
            'email' => $this->user->email,
            'first_name' => 'Zz',
            'last_name' => 'Tester',
            'alias' => 'zztest',
            'location' => 'Pittsburgh',
            'bio' => 'A ZZ bio',
            'default_theme' => 'light',
            'instagram_username' => 'zzinsta',
            'setting_weekly_update' => 'on',
            'user_status_id' => UserStatus::BANNED,
            'onboarding_completed_at' => '2026-01-01 00:00:00',
        ])->assertSessionHasNoErrors();

        $user = $this->user->fresh();
        $profile = $user->profile;
        $this->assertSame('ZZ Updated Name', $user->name);
        $this->assertSame(UserStatus::ACTIVE, (int) $user->user_status_id);
        $this->assertSame('Zz', $profile->first_name);
        $this->assertSame('A ZZ bio', $profile->bio);
        $this->assertSame('light', $profile->default_theme);
        $this->assertSame('zzinsta', $profile->instagram_username);
        $this->assertSame(1, (int) $profile->setting_weekly_update);
        $this->assertSame(0, (int) $profile->setting_daily_update);
        $this->assertNull($profile->onboarding_completed_at);
    }

    public function test_profile_fields_are_validated(): void
    {
        $this->actingAs($this->user)
            ->put("/users/{$this->user->slug}", [
                'name' => 'ZZ Name', 'email' => $this->user->email, 'default_theme' => 'neon',
            ])
            ->assertSessionHasErrors('default_theme');
    }

    public function test_api_slug_changes_must_be_unique_and_url_safe(): void
    {
        $other = User::factory()->create(['user_status_id' => UserStatus::ACTIVE, 'slug' => 'zz-taken']);
        $this->actingAs($this->user, 'sanctum');

        $this->patchJson("/api/users/{$this->user->id}", ['slug' => $other->slug])
            ->assertStatus(422)->assertJsonValidationErrors('slug');
        $this->patchJson("/api/users/{$this->user->id}", ['slug' => 'Not A Slug'])
            ->assertStatus(422)->assertJsonValidationErrors('slug');

        $this->patchJson("/api/users/{$this->user->id}", ['slug' => 'zz-mine'])->assertOk();
        $this->assertSame('zz-mine', $this->user->fresh()->slug);
    }

    public function test_api_profile_fields_are_validated_and_limited_to_documented_ones(): void
    {
        $this->actingAs($this->user, 'sanctum');

        // NOT NULL setting: null is a 422, not a database error
        $this->patchJson("/api/users/{$this->user->id}", ['profile' => ['setting_feedback_requests' => null]])
            ->assertStatus(422)->assertJsonValidationErrors('profile.setting_feedback_requests');

        $this->patchJson("/api/users/{$this->user->id}", ['profile' => [
            'bio' => 'API bio ZZ',
            'setting_public_profile' => true,
            'onboarding_completed_at' => '2026-01-01 00:00:00',
        ]])->assertOk();

        $profile = $this->user->fresh()->profile;
        $this->assertSame('API bio ZZ', $profile->bio);
        $this->assertSame(1, (int) $profile->setting_public_profile);
        $this->assertNull($profile->onboarding_completed_at);
    }

    public function test_user_controllers_do_not_pass_raw_request_input_to_model_writes(): void
    {
        foreach (['UsersController', 'Api/UsersController'] as $controller) {
            $source = file_get_contents(app_path("Http/Controllers/{$controller}.php"));
            $this->assertDoesNotMatchRegularExpression(
                '/(create|fill|update)\(\$request->(all|input)\(\)\)|\$input = \$request->(all|input)\(\);|\$request->input\(\'profile\'/',
                $source,
                "{$controller} passes raw request input to a model write"
            );
        }
    }
}
