<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserStatus;
use App\Services\ModuleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchModulesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function activeUser(): User
    {
        /** @var User $user */
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        return $user;
    }

    private function admin(): User
    {
        $admin = $this->activeUser();
        $admin->assignGroup('admin');

        return $admin;
    }

    /** @test */
    public function module_registry_matches_on_name_and_description(): void
    {
        $registry = new ModuleRegistry;

        // Name match.
        $byName = collect($registry->search('calendar', null))->pluck('name');
        $this->assertContains('Calendar', $byName);

        // Description match ("Concerts and club nights" -> Events).
        $byDescription = collect($registry->search('concerts', null))->pluck('name');
        $this->assertContains('Events', $byDescription);

        // Empty keyword returns nothing.
        $this->assertSame([], $registry->search('   ', null));
    }

    /** @test */
    public function guests_only_see_public_and_policy_modules(): void
    {
        $registry = new ModuleRegistry;

        $this->assertContains('Calendar', collect($registry->search('calendar', null))->pluck('name'));
        $this->assertContains('About', collect($registry->search('about', null))->pluck('name'));

        // Admin-only and auth-only pages are hidden from guests.
        $this->assertEmpty($registry->search('permissions', null));
        $this->assertEmpty($registry->search('notifications', null));
    }

    /** @test */
    public function authenticated_users_see_notifications_but_not_admin_modules(): void
    {
        $registry = new ModuleRegistry;
        $user = $this->activeUser();

        $this->assertContains('Notifications', collect($registry->search('notifications', $user))->pluck('name'));
        $this->assertEmpty($registry->search('permissions', $user));
    }

    /** @test */
    public function admins_see_admin_modules(): void
    {
        $registry = new ModuleRegistry;
        $admin = $this->admin();

        $this->assertContains('Permissions', collect($registry->search('permissions', $admin))->pluck('name'));
    }

    /** @test */
    public function search_page_shows_matching_public_module_to_guests(): void
    {
        $response = $this->get('/search?keyword=calendar');

        $response->assertStatus(200);
        $response->assertSee('id="modules-results"', false);
        $response->assertSee('Browse events by calendar');
    }

    /** @test */
    public function api_search_returns_modules_scoped_by_permission(): void
    {
        // Guest: gets public module, no admin module.
        $guest = $this->getJson('/api/search?q=calendar');
        $guest->assertStatus(200);
        $this->assertContains('Calendar', collect($guest->json('results.modules'))->pluck('name'));

        $guestAdminTerm = $this->getJson('/api/search?q=permissions');
        $this->assertSame([], $guestAdminTerm->json('results.modules'));

        // Admin: gets the admin module.
        $this->actingAs($this->admin());
        $adminResponse = $this->getJson('/api/search?q=permissions');
        $adminResponse->assertStatus(200);
        $this->assertContains('Permissions', collect($adminResponse->json('results.modules'))->pluck('name'));
    }
}
