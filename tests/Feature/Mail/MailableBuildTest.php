<?php

namespace Tests\Feature\Mail;

use App\Mail\AdminActivitySummary;
use App\Mail\AdminMailer;
use App\Mail\DailyReminder;
use App\Mail\EntityOutreachAdminSummary;
use App\Mail\EntityReminder;
use App\Mail\EntityUpdateSummary;
use App\Mail\FollowingPostUpdate;
use App\Mail\FollowingThreadUpdate;
use App\Mail\FollowingUpdate;
use App\Mail\InstagramPostFailure;
use App\Mail\LoginFailure;
use App\Mail\UserActivation;
use App\Mail\UserDataExportReady;
use App\Mail\UserRegistration;
use App\Mail\UserSuspended;
use App\Mail\UserUpdate;
use App\Mail\WeeklyUpdate;
use App\Models\Entity;
use App\Models\Event;
use App\Models\Post;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Mail\Mailable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dedicated coverage for the app/Mail mailables. The existing suite exercises
 * mail through the flows that dispatch it; these tests assert the mailable
 * classes themselves build correctly — view selection, sender, and subject —
 * without coupling to the rendered Blade output.
 */
class MailableBuildTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private const URL = 'https://example.test';
    private const SITE = 'TestSite';
    private const ADMIN = 'admin@example.test';
    private const REPLY = 'noreply@example.test';
    private const FEEDBACK = 'feedback@example.test';

    /**
     * Build the mailable and assert it points at the expected (existing) markdown
     * view, sends from the reply address, and has a non-empty subject prefixed
     * with the site name (the convention every mailable in this app follows).
     */
    private function assertBuilt(Mailable $mailable, string $view): void
    {
        $mailable->build();

        $this->assertSame($view, $mailable->markdown, "Mailable should use the {$view} markdown view");
        $this->assertTrue(view()->exists($view), "Markdown view {$view} should exist");
        $this->assertNotEmpty($mailable->subject, 'Mailable subject should be set');
        $this->assertStringStartsWith(self::SITE, $mailable->subject, 'Subject should be prefixed with the site name');
        $this->assertNotEmpty($mailable->from, 'Mailable should have a from address');
        $this->assertSame(self::REPLY, $mailable->from[0]['address'], 'Mailable should send from the reply address');
        $this->assertSame(self::SITE, $mailable->from[0]['name'], 'From name should be the site name');
    }

    private function assertBccsAdmin(Mailable $mailable): void
    {
        $this->assertNotEmpty($mailable->bcc, 'Mailable should bcc the admin address');
        $this->assertSame(self::ADMIN, $mailable->bcc[0]['address'], 'Mailable should bcc the admin address');
    }

    public function test_user_registration_builds(): void
    {
        $user = User::factory()->create();
        $mailable = new UserRegistration(self::URL, self::SITE, self::ADMIN, self::REPLY, $user);

        $this->assertBuilt($mailable, 'emails.user-registration-markdown');
        $this->assertStringContainsString($user->name, $mailable->subject);
        $this->assertBccsAdmin($mailable);
    }

    public function test_user_activation_builds(): void
    {
        $user = User::factory()->create();
        $mailable = new UserActivation(self::URL, self::SITE, self::ADMIN, self::REPLY, $user);

        $this->assertBuilt($mailable, 'emails.user-activation-markdown');
        $this->assertStringContainsString($user->name, $mailable->subject);
    }

    public function test_user_suspended_builds(): void
    {
        $user = User::factory()->create();
        $mailable = new UserSuspended(self::URL, self::SITE, self::ADMIN, self::REPLY, $user);

        $this->assertBuilt($mailable, 'emails.user-suspended-markdown');
        $this->assertStringContainsString($user->name, $mailable->subject);
    }

    public function test_login_failure_builds(): void
    {
        $user = User::factory()->create();
        $mailable = new LoginFailure(self::URL, self::SITE, self::ADMIN, self::REPLY, $user, 3);

        $this->assertBuilt($mailable, 'emails.user-login-failed-markdown');
        $this->assertStringContainsString($user->name, $mailable->subject);
    }

    public function test_user_data_export_ready_builds(): void
    {
        $user = User::factory()->create();
        $mailable = new UserDataExportReady(
            self::URL,
            self::SITE,
            self::ADMIN,
            self::REPLY,
            $user,
            'https://example.test/download/export.zip',
            'export.zip'
        );

        $this->assertBuilt($mailable, 'emails.user-data-export-ready');
        $this->assertSame(self::SITE . ': Your Data Export is Ready', $mailable->subject);
        $this->assertBccsAdmin($mailable);
    }

    public function test_user_update_builds(): void
    {
        $user = User::factory()->create();
        $mailable = new UserUpdate(self::URL, self::SITE, self::ADMIN, self::REPLY, $user, new EloquentCollection(), [], []);

        $this->assertBuilt($mailable, 'emails.user-update-markdown');
        $this->assertStringContainsString($user->name, $mailable->subject);
    }

    public function test_weekly_update_builds(): void
    {
        $user = User::factory()->create();
        $mailable = new WeeklyUpdate(self::URL, self::SITE, self::ADMIN, self::REPLY, $user, new EloquentCollection(), [], []);

        $this->assertBuilt($mailable, 'emails.weekly-update-markdown');
    }

    public function test_daily_reminder_builds(): void
    {
        $user = User::factory()->create();
        $mailable = new DailyReminder(self::URL, self::SITE, self::ADMIN, self::REPLY, $user, new EloquentCollection(), [], []);

        $this->assertBuilt($mailable, 'emails.daily-reminder-markdown');
    }

    public function test_following_update_builds(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['start_at' => Carbon::parse('2026-01-15 20:00:00')]);
        $tag = Tag::factory()->create();

        $mailable = new FollowingUpdate(self::URL, self::SITE, self::ADMIN, self::REPLY, $user, $event, $tag);

        $this->assertBuilt($mailable, 'emails.following-update-markdown');
        $this->assertStringContainsString($tag->name, $mailable->subject);
    }

    public function test_following_thread_update_builds(): void
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create();
        $tag = Tag::factory()->create();

        $mailable = new FollowingThreadUpdate(self::URL, self::SITE, self::ADMIN, self::REPLY, $user, $thread, $tag);

        $this->assertBuilt($mailable, 'emails.following-thread-update-markdown');
        $this->assertStringContainsString($tag->name, $mailable->subject);
    }

    public function test_following_post_update_builds(): void
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create();
        $post = Post::factory()->create();
        $tag = Tag::factory()->create();

        $mailable = new FollowingPostUpdate(self::URL, self::SITE, self::ADMIN, self::REPLY, $user, $thread, $post, $tag);

        $this->assertBuilt($mailable, 'emails.following-post-update-markdown');
        $this->assertStringContainsString($thread->name, $mailable->subject);
    }

    public function test_admin_activity_summary_builds(): void
    {
        $mailable = new AdminActivitySummary(
            self::URL,
            self::SITE,
            self::ADMIN,
            self::REPLY,
            7,
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-01-07'),
            [],
            [],
            []
        );

        $this->assertBuilt($mailable, 'emails.admin-activity-summary');
    }

    public function test_admin_mailer_builds(): void
    {
        $mailable = new AdminMailer(self::URL, self::SITE, self::ADMIN, self::REPLY);

        $this->assertBuilt($mailable, 'emails.admin-test-markdown');
    }

    public function test_entity_reminder_builds(): void
    {
        $entity = Entity::factory()->create();
        $mailable = new EntityReminder(
            self::URL,
            self::SITE,
            self::ADMIN,
            self::REPLY,
            self::FEEDBACK,
            $entity,
            new EloquentCollection(),
            new EloquentCollection(),
            new EloquentCollection()
        );

        $this->assertBuilt($mailable, 'emails.entity-reminder-markdown');
        $this->assertStringContainsString($entity->name, $mailable->subject);
    }

    public function test_entity_outreach_admin_summary_builds(): void
    {
        $mailable = new EntityOutreachAdminSummary(
            self::URL,
            self::SITE,
            self::ADMIN,
            self::REPLY,
            self::FEEDBACK,
            new EloquentCollection(),
            0
        );

        $this->assertBuilt($mailable, 'emails.entity-outreach-admin-markdown');
    }

    public function test_entity_update_summary_builds(): void
    {
        $entity = Entity::factory()->create();
        $mailable = new EntityUpdateSummary(
            self::URL,
            self::SITE,
            self::ADMIN,
            self::REPLY,
            $entity,
            new EloquentCollection(),
            new EloquentCollection(),
            new EloquentCollection(),
            new EloquentCollection()
        );

        $this->assertBuilt($mailable, 'emails.entity-update-summary-markdown');
        $this->assertStringContainsString($entity->name, $mailable->subject);
    }

    public function test_instagram_post_failure_builds(): void
    {
        $mailable = new InstagramPostFailure(
            42,
            'some-event-slug',
            'Some Event',
            'API timeout',
            self::SITE,
            self::ADMIN,
            self::REPLY
        );

        $this->assertBuilt($mailable, 'emails.instagram-post-failure');
    }
}
