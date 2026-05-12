<?php

namespace Tests\Feature\Console;

use App\Mail\EntityOutreachAdminSummary;
use App\Mail\EntityReminder;
use App\Models\Action;
use App\Models\Activity;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotifyEntitiesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        config()->set('app.admin', 'admin@example.com');
    }

    private function entityWithContact(string $email): Entity
    {
        $entity = Entity::factory()->create();
        $contact = Contact::create([
            'name' => 'Booking',
            'email' => $email,
        ]);
        $entity->contacts()->attach($contact->id);

        return $entity;
    }

    public function test_command_sends_reminder_to_entities_with_contact_emails(): void
    {
        Mail::fake();

        $this->entityWithContact('booker@example.com');

        $this->artisan('notifyEntities')->assertExitCode(0);

        Mail::assertSent(EntityReminder::class, fn ($mail) => $mail->hasTo('booker@example.com'));
    }

    public function test_command_skips_entities_whose_contact_user_logged_in_recently(): void
    {
        Mail::fake();

        $email = 'active@example.com';
        $entity = $this->entityWithContact($email);

        $user = User::factory()->create(['email' => $email]);

        // Recent login activity for that user
        (new Activity())->forceFill([
            'user_id' => $user->id,
            'object_id' => $user->id,
            'object_table' => 'User',
            'object_name' => $user->name,
            'action_id' => Action::LOGIN,
            'created_at' => Carbon::now()->subDays(3),
            'updated_at' => Carbon::now()->subDays(3),
        ])->save();

        $this->artisan('notifyEntities')->assertExitCode(0);

        Mail::assertNotSent(EntityReminder::class, fn ($mail) => $mail->hasTo($email));
    }

    public function test_dry_run_sends_no_mail(): void
    {
        Mail::fake();

        $this->entityWithContact('booker@example.com');

        $this->artisan('notifyEntities', ['--dry-run' => true])->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_command_sends_admin_summary_email(): void
    {
        Mail::fake();

        // An Instagram-only entity (no contact email) — should appear in admin summary
        Entity::factory()->create(['instagram_username' => 'venue_handle']);

        $this->artisan('notifyEntities')->assertExitCode(0);

        Mail::assertSent(EntityOutreachAdminSummary::class, fn ($mail) => $mail->hasTo('admin@example.com'));
    }

    public function test_single_mode_with_unknown_slug_fails(): void
    {
        Mail::fake();

        $exitCode = $this->artisan('notifyEntities', ['--single' => 'does-not-exist-zz'])->run();

        $this->assertSame(1, $exitCode);
        Mail::assertNothingSent();
    }
}
