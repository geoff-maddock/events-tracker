<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Group and permission pages, and the group badges on users, are admin-only:
 * they show who the site's admins are (#2233).
 */
class GroupsAdminOnlyTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $admin;

    private User $member;

    private Group $group;

    private Permission $permission;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        $this->admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->admin->groups()->attach(Group::firstOrCreate(['name' => 'admin'])->id);

        $this->group = Group::create(['name' => 'zzsecret', 'label' => 'Zz Secret Group', 'level' => 1, 'description' => '']);
        $this->permission = Permission::create(['name' => 'zz_secret', 'label' => 'Zz Secret Permission', 'level' => 1, 'description' => '']);

        // a member with a public profile, so guests get the full profile view
        $this->member = User::factory()->create(['user_status_id' => UserStatus::ACTIVE, 'name' => 'Zz Member']);
        $this->member->profile()->firstOrCreate([])->forceFill(['setting_public_profile' => 1])->save();
        $this->member->groups()->attach($this->group->id);
    }

    /**
     * @return array<int, string>
     */
    private function readUrls(): array
    {
        return [
            '/groups', "/groups/{$this->group->id}", '/groups/filter', '/groups/reset', '/groups/rpp-reset',
            '/permissions', "/permissions/{$this->permission->id}", '/permissions/filter', '/permissions/reset', '/permissions/rpp-reset',
        ];
    }

    public function test_guests_are_sent_to_login(): void
    {
        foreach ($this->readUrls() as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_signed_in_non_admins_are_refused(): void
    {
        $this->actingAs(User::factory()->create(['user_status_id' => UserStatus::ACTIVE]));

        foreach ($this->readUrls() as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_admins_can_read_groups_and_permissions(): void
    {
        $this->actingAs($this->admin);

        $this->get('/groups')->assertOk()->assertSee('Zz Secret Group');
        $this->get("/groups/{$this->group->id}")->assertOk()->assertSee('Zz Member');
        $this->get('/groups/filter')->assertOk();
        $this->get('/permissions')->assertOk()->assertSee('Zz Secret Permission');
        $this->get("/permissions/{$this->permission->id}")->assertOk();
        $this->get('/permissions/filter')->assertOk();
    }

    public function test_profile_group_badges_are_shown_only_to_admins_and_the_user(): void
    {
        $url = "/users/{$this->member->slug}";

        $this->get($url)->assertOk()->assertSee('Zz Member')->assertDontSee('Zz Secret Group');

        $this->actingAs(User::factory()->create(['user_status_id' => UserStatus::ACTIVE]))
            ->get($url)->assertOk()->assertDontSee('Zz Secret Group');

        // the user sees their own groups, but not a link to the admin-only page
        $this->actingAs($this->member)->get($url)->assertOk()
            ->assertSee('Zz Secret Group')
            ->assertDontSee("/groups/{$this->group->id}", false);

        $this->actingAs($this->admin)->get($url)->assertOk()
            ->assertSee('Zz Secret Group')
            ->assertSee("/groups/{$this->group->id}", false);
    }

    public function test_user_list_cards_show_group_badges_only_to_admins(): void
    {
        $url = '/users?filters[name]=Zz+Member';

        // the user list itself needs a sign-in
        $this->get($url)->assertRedirect('/login');

        $this->actingAs(User::factory()->create(['user_status_id' => UserStatus::ACTIVE]))
            ->get($url)->assertOk()->assertSee('Zz Member')->assertDontSee('Zz Secret Group');

        $this->actingAs($this->admin)->get($url)->assertOk()->assertSee('Zz Member')->assertSee('Zz Secret Group');
    }
}
