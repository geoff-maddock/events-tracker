<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Group;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The activity log records logins and failed logins (with the email address
 * typed), so every way of reading it is admin-only.
 */
class ActivityLogAccessTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $user;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        $this->user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->admin->groups()->attach(Group::firstOrCreate(['name' => 'admin'])->id);

        Activity::factory()->create(['object_table' => 'User', 'action_id' => 14, 'object_name' => 'zz-typed@example.com']);
    }

    public function test_guests_are_sent_to_login(): void
    {
        foreach (['/activity', '/activity/filter', '/activity/reset', '/activity/rpp-reset'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
        $this->post('/activity/filter')->assertRedirect('/login');
    }

    public function test_signed_in_non_admins_are_refused(): void
    {
        $this->actingAs($this->user);

        $this->get('/activity')->assertForbidden();
        $this->post('/activity/filter')->assertForbidden();

        $this->actingAs($this->user, 'sanctum');
        $this->getJson('/api/activities')->assertForbidden();
        $this->getJson('/api/activities/filter')->assertForbidden();
        $this->getJson('/api/activities/'.Activity::first()->id)->assertForbidden();
    }

    public function test_admins_can_read_the_log(): void
    {
        $this->actingAs($this->admin)->get('/activity')->assertOk()->assertSee('zz-typed@example.com');

        $this->actingAs($this->admin, 'sanctum')->getJson('/api/activities')->assertOk();
    }

    public function test_the_activity_module_is_listed_for_admins_only(): void
    {
        $this->assertNotContains('/activity', array_column(config('modules.public'), 'url'));
        $this->assertContains('/activity', array_column(config('modules.admin'), 'url'));
    }
}
