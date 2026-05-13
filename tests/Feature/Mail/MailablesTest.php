<?php

namespace Tests\Feature\Mail;

use App\Mail\AdminActivitySummary;
use App\Mail\AdminMailer;
use App\Mail\DailyReminder;
use App\Mail\EntityOutreachAdminSummary;
use App\Mail\EntityReminder;
use App\Mail\WeeklyUpdate;
use App\Models\Entity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailablesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_admin_mailer_builds_with_expected_subject_and_recipients(): void
    {
        $mail = new AdminMailer('https://test.app', 'TestSite', 'admin@test.app', 'noreply@test.app');

        $built = $mail->build();

        $this->assertStringContainsString('Admin Mailer Test', $built->subject);
        $this->assertSame('noreply@test.app', $built->from[0]['address']);
        $this->assertSame('admin@test.app', $built->bcc[0]['address']);
    }

    public function test_daily_reminder_builds(): void
    {
        $user = User::factory()->create();
        $mail = new DailyReminder(
            'https://test.app',
            'TestSite',
            'admin@test.app',
            'noreply@test.app',
            $user,
            new Collection(),
            [],
            []
        );

        $built = $mail->build();

        $this->assertStringContainsString('Daily Reminder', $built->subject);
        $this->assertSame('noreply@test.app', $built->from[0]['address']);
    }

    public function test_weekly_update_builds(): void
    {
        $user = User::factory()->create();
        $mail = new WeeklyUpdate(
            'https://test.app',
            'TestSite',
            'admin@test.app',
            'noreply@test.app',
            $user,
            new Collection(),
            [],
            []
        );

        $built = $mail->build();

        $this->assertStringContainsString('Weekly Update', $built->subject);
        $this->assertSame('admin@test.app', $built->bcc[0]['address']);
    }

    public function test_entity_reminder_builds_with_entity_name_in_subject(): void
    {
        $entity = Entity::factory()->create(['name' => 'ZZ Reminder Band']);

        $mail = new EntityReminder(
            'https://test.app',
            'TestSite',
            'admin@test.app',
            'noreply@test.app',
            'feedback@test.app',
            $entity,
            new Collection(),
            new Collection(),
            new Collection()
        );

        $built = $mail->build();

        $this->assertStringContainsString('ZZ Reminder Band', $built->subject);
        $this->assertSame('feedback@test.app', $built->replyTo[0]['address']);
    }

    public function test_entity_outreach_admin_summary_builds(): void
    {
        $mail = new EntityOutreachAdminSummary(
            'https://test.app',
            'TestSite',
            'admin@test.app',
            'noreply@test.app',
            'feedback@test.app',
            new Collection(),
            5
        );

        $built = $mail->build();

        $this->assertStringContainsString('Entity Outreach Summary', $built->subject);
    }

    public function test_admin_activity_summary_builds_with_date_range_in_subject(): void
    {
        $mail = new AdminActivitySummary(
            'https://test.app',
            'TestSite',
            'admin@test.app',
            'noreply@test.app',
            7,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-08'),
            ['logins' => [], 'deletions' => [], 'new_users' => [], 'new_events' => [], 'new_entities' => [], 'new_series' => [], 'other' => []],
            ['logins' => 0, 'deletions' => 0, 'new_users' => 0, 'new_events' => 0, 'new_entities' => 0, 'new_series' => 0, 'other' => 0]
        );

        $built = $mail->build();

        $this->assertStringContainsString('Activity Summary', $built->subject);
        $this->assertStringContainsString('May 1', $built->subject);
        $this->assertStringContainsString('May 8', $built->subject);
    }
}
