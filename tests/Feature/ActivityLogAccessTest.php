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

    public function test_the_user_api_reports_when_a_user_was_last_active_not_the_activity_itself(): void
    {
        $activity = Activity::factory()->create([
            'user_id' => $this->admin->id, 'ip_address' => '203.0.113.9', 'object_name' => 'zz-private@example.com',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/users/{$this->admin->id}")->assertOk();

        $this->assertIsString($response->json('last_active'));
        $this->assertSame($activity->created_at->toJSON(), $response->json('last_active'));
        $response->assertDontSee('203.0.113.9')->assertDontSee('zz-private@example.com');
    }
}
