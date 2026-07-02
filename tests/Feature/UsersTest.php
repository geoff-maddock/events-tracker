<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Models\UserStatus;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UsersTest extends TestCase
{
    // refresh database and run migrations before test
    use RefreshDatabase;

    // reseed the database
    protected $seed = true;

    /** @test */
    public function a_user_can_fetch_their_most_recent_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['created_by' => $user->id]);

        $post->created_by = $user->id;
        $this->assertEquals($post->id, $user->lastPost->id);
    }

    // Disabled - needs fixing
    // /** @test */
    public function an_admin_can_access_password_reset_form()
    {
        // Create an admin user with grant_access permission
        $admin = User::factory()->create();
        $admin->assignGroup('admin');
        $admin->refresh(); // Reload the user to get the groups relationship
        
        // Create a regular user
        $user = User::factory()->create();
        
        $this->actingAs($admin);
        
        $response = $this->get(route('users.showResetPassword', ['id' => $user->id]));
        
        $response->assertStatus(200);
        $response->assertSee('Reset Password');
    }

    /** @test */
    public function a_non_admin_cannot_access_password_reset_form()
    {
        $this->withExceptionHandling();

        // Create a regular user without admin permissions
        $regularUser = User::factory()->create();

        // Create another user whose password would be reset
        $targetUser = User::factory()->create();

        $this->actingAs($regularUser);

        $response = $this->get(route('users.showResetPassword', ['id' => $targetUser->id]));

        // Should be redirected or receive 403 when authorization fails
        $this->assertContains($response->status(), [302, 301, 403]);
    }

    /** @test */
    public function an_invalid_sort_param_redirects_instead_of_500(): void
    {
        // /users?sort=start_at reaches orderBy() on a column that does not exist
        // on the users table, throwing a QueryException. Rather than 500, the
        // exception handler should strip the sort params, flash a notice, and
        // redirect back to the list (#1942).
        $this->withExceptionHandling();

        $admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $admin->assignGroup('admin');
        $this->actingAs($admin);

        $response = $this->get('/users?limit=25&sort=start_at&direction=desc');

        // Redirects back to the list with the bad sort params stripped but other
        // params (e.g. limit) preserved.
        $response->assertStatus(302);
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith(route('users.index'), $location);
        $this->assertStringNotContainsString('sort', $location);
        $this->assertStringNotContainsString('direction', $location);
        $response->assertSessionHas('flash_message');
    }

    /** @test */
    public function following_the_invalid_sort_redirect_renders_the_list(): void
    {
        $this->withExceptionHandling();

        $admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $admin->assignGroup('admin');
        $this->actingAs($admin);

        // The redirect target carries no sort params, so the list falls back to
        // the default order and renders normally.
        $response = $this->get(route('users.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function a_valid_sort_param_still_works(): void
    {
        $this->withExceptionHandling();

        $admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $admin->assignGroup('admin');
        $this->actingAs($admin);

        // A real column and a computed/aliased column (last_active, added via
        // addSelect) must both continue to sort without error.
        $this->get('/users?sort=name&direction=asc')->assertStatus(200);
        $this->get('/users?sort=last_active&direction=desc')->assertStatus(200);
    }

    /** @test */
    public function updating_a_user_with_no_profile_row_does_not_error(): void
    {
        // UserFactory doesn't create a profile row (EVENTREPO-VW regression).
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->assertNull($user->profile);

        $this->actingAs($user);

        $response = $this->patch(route('users.update', ['user' => $user]), [
            'name' => $user->name,
            'email' => $user->email,
        ]);

        $response->assertRedirect(route('users.show', ['user' => $user]));
        $this->assertNotNull($user->profile()->first());
    }
}
