<?php

namespace Tests\Feature;

use App\Mail\UserSuspended;
use App\Models\Action;
use App\Models\Activity;
use App\Models\Event;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The admin's suspend and delete actions on a user, which run through
 * App\Services\UserAccounts (#2407). Activate, purge and the signup review
 * have their own tests.
 */
class UserAccountsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        Mail::fake();

        $this->admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->admin->assignGroup('admin');
    }

    public function test_an_admin_suspends_an_account_and_the_user_is_emailed(): void
    {
        $target = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($this->admin)->post('/users/'.$target->id.'/suspend')
            ->assertRedirect()
            ->assertSessionHas('flash_message.level', 'success');

        $this->assertSame(UserStatus::SUSPENDED, $target->fresh()->user_status_id);
        Mail::assertSent(UserSuspended::class, fn (UserSuspended $mail) => $mail->hasTo($target->email));
        $this->assertTrue(Activity::where('object_table', 'User')->where('object_id', $target->id)
            ->where('action_id', Action::SUSPEND)->where('user_id', $this->admin->id)->exists());
    }

    public function test_an_admin_deletes_an_account_and_its_events_pass_to_the_site_admin(): void
    {
        $target = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $event = Event::factory()->create(['created_by' => $target->id]);

        $this->actingAs($this->admin)->post('/users/'.$target->id.'/delete')
            ->assertRedirect()
            ->assertSessionHas('flash_message.message', 'User '.$target->name.' is now deleted.');

        $this->assertNull(User::find($target->id));
        $this->assertNotSame($target->id, $event->fresh()->created_by);
        $this->assertTrue(Activity::where('object_table', 'User')->where('object_id', $target->id)
            ->where('action_id', Action::DELETE)->exists());
    }

    public function test_an_action_on_a_missing_user_says_so(): void
    {
        $this->actingAs($this->admin)->post('/users/999999/suspend')
            ->assertRedirect()
            ->assertSessionHas('flash_message.message', 'No such user');
    }
}
