<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Group;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * When a user is deleted their events pass to the site admin (APP_SUPERUSER)
 * instead of pointing at a user that no longer exists.
 */
class DeletedUserEventOwnershipTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        $this->admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->admin->groups()->attach(Group::firstOrCreate(['name' => 'admin'])->id);
        config(['app.superuser' => (string) $this->admin->id]);
    }

    public function test_deleting_a_user_passes_their_events_to_the_admin(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $other = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $theirs = Event::factory()->count(2)->create(['created_by' => $user->id]);
        $untouched = Event::factory()->create(['created_by' => $other->id]);

        $user->delete();

        foreach ($theirs as $event) {
            $this->assertSame($this->admin->id, (int) $event->fresh()->created_by);
        }
        $this->assertSame($other->id, (int) $untouched->fresh()->created_by);
    }

    public function test_the_admin_delete_route_reassigns_events(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $event = Event::factory()->create(['created_by' => $user->id]);

        $this->actingAs($this->admin)->post("/users/{$user->id}/delete")->assertRedirect();

        $this->assertNull(User::find($user->id));
        $this->assertSame($this->admin->id, (int) $event->fresh()->created_by);
    }

    public function test_events_stay_put_when_the_admin_itself_is_deleted_or_no_admin_is_configured(): void
    {
        $adminEvent = Event::factory()->create(['created_by' => $this->admin->id]);
        $this->admin->delete();
        $this->assertSame($this->admin->id, (int) $adminEvent->fresh()->created_by);

        config(['app.superuser' => null]);
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $event = Event::factory()->create(['created_by' => $user->id]);
        $user->delete();
        $this->assertSame($user->id, (int) $event->fresh()->created_by);
    }

    public function test_the_backfill_reassigns_events_of_already_deleted_users(): void
    {
        $gone = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $orphan = Event::factory()->create(['created_by' => $gone->id]);
        // removed without the model hook, as older deletions were
        DB::table('users')->where('id', $gone->id)->delete();
        $owned = Event::factory()->create();

        ob_start();
        (require database_path('migrations/2026_09_29_000001_reassign_events_of_deleted_users.php'))->up();
        $output = (string) ob_get_clean();

        $this->assertSame($this->admin->id, (int) $orphan->fresh()->created_by);
        $this->assertNotSame($this->admin->id, (int) $owned->fresh()->created_by);
        $this->assertStringContainsString("to user #{$this->admin->id}", $output);
    }
}
