<?php

namespace Tests\Feature\Notifications;

use App\Models\JobStatus;
use App\Models\User;
use App\Notifications\EventPublished;
use App\Notifications\JobCompleted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\Twitter\TwitterChannel;
use stdClass;
use Tests\TestCase;

/**
 * Dedicated coverage for the notification classes themselves — channels,
 * mail content, and array payload. (Dispatch of JobCompleted is exercised by
 * QueuedInstagramPostTest; this asserts what the notification actually contains.)
 */
class NotificationContentTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function jobStatus(User $user, string $label = 'Account data export'): JobStatus
    {
        return JobStatus::create([
            'user_id' => $user->id,
            'type' => 'data_export',
            'label' => $label,
            'status' => JobStatus::STATUS_SUCCEEDED,
        ]);
    }

    public function test_event_published_uses_twitter_channel(): void
    {
        $notification = new EventPublished();

        $this->assertSame([TwitterChannel::class], $notification->via(new stdClass()));
        $this->assertSame([], $notification->toArray(new stdClass()));
    }

    public function test_event_published_mail_has_intro_lines(): void
    {
        $mail = (new EventPublished())->toMail(new stdClass());

        $this->assertInstanceOf(MailMessage::class, $mail);
        $this->assertContains('The introduction to the notification.', $mail->introLines);
        $this->assertSame('Notification Action', $mail->actionText);
    }

    public function test_job_completed_sends_via_database_only_by_default(): void
    {
        config()->set('app.queue_notify_email', false);

        $user = User::factory()->create();
        $notification = new JobCompleted($this->jobStatus($user), true, 'done');

        $this->assertSame(['database'], $notification->via(new stdClass()));
    }

    public function test_job_completed_adds_mail_channel_when_enabled(): void
    {
        config()->set('app.queue_notify_email', true);

        $user = User::factory()->create();
        $notification = new JobCompleted($this->jobStatus($user), true, 'done');

        $this->assertSame(['database', 'mail'], $notification->via(new stdClass()));
    }

    public function test_job_completed_mail_subject_reflects_outcome(): void
    {
        $user = User::factory()->create();
        $status = $this->jobStatus($user, 'Account data export');

        $success = (new JobCompleted($status, true, 'all good'))->toMail(new stdClass());
        $this->assertSame('Completed: Account data export', $success->subject);

        $failure = (new JobCompleted($status, false, 'it broke'))->toMail(new stdClass());
        $this->assertSame('Failed: Account data export', $failure->subject);
    }

    public function test_job_completed_to_array_payload(): void
    {
        $user = User::factory()->create();
        $status = $this->jobStatus($user);

        $payload = (new JobCompleted($status, true, 'done'))->toArray(new stdClass());

        $this->assertSame($status->id, $payload['job_status_id']);
        $this->assertSame('data_export', $payload['type']);
        $this->assertTrue($payload['succeeded']);
        $this->assertSame('done', $payload['message']);
    }
}
