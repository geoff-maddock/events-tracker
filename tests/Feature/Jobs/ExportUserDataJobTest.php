<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ExportUserDataJob;
use App\Mail\UserDataExportReady;
use App\Models\JobStatus;
use App\Models\User;
use App\Notifications\JobCompleted;
use App\Services\DataExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * Covers ExportUserDataJob — the user-triggered data export job. (The Instagram
 * jobs and the shared TracksJobStatus concern are already covered by
 * QueuedInstagramPostTest.)
 */
class ExportUserDataJobTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_constructing_the_job_creates_a_queued_job_status(): void
    {
        $user = User::factory()->create();

        $job = new ExportUserDataJob($user);

        $this->assertNotNull($job->jobStatusId);
        $this->assertDatabaseHas('job_statuses', [
            'id' => $job->jobStatusId,
            'user_id' => $user->id,
            'type' => 'data_export',
            'status' => JobStatus::STATUS_QUEUED,
        ]);
    }

    public function test_handle_generates_export_emails_user_and_marks_succeeded(): void
    {
        Mail::fake();
        Notification::fake();

        $user = User::factory()->create();

        $service = Mockery::mock(DataExportService::class);
        $service->shouldReceive('generateExport')
            ->once()
            ->with(Mockery::on(fn ($u) => $u instanceof User && $u->id === $user->id))
            ->andReturn(['filename' => 'user-export.zip']);
        $service->shouldReceive('getDownloadUrl')
            ->once()
            ->with('user-export.zip')
            ->andReturn('https://example.test/download/user-export.zip');

        $job = new ExportUserDataJob($user);
        $job->handle($service);

        $this->assertDatabaseHas('job_statuses', [
            'id' => $job->jobStatusId,
            'status' => JobStatus::STATUS_SUCCEEDED,
        ]);

        Mail::assertSent(UserDataExportReady::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email) && $mail->filename === 'user-export.zip';
        });

        Notification::assertSentTo($user, JobCompleted::class);
    }

    public function test_handle_rethrows_when_export_fails(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $service = Mockery::mock(DataExportService::class);
        $service->shouldReceive('generateExport')
            ->once()
            ->andThrow(new RuntimeException('disk full'));

        $job = new ExportUserDataJob($user);

        $this->expectException(RuntimeException::class);
        $job->handle($service);
    }

    public function test_failed_marks_status_failed_and_notifies_user(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $job = new ExportUserDataJob($user);
        $job->failed(new RuntimeException('boom'));

        $this->assertDatabaseHas('job_statuses', [
            'id' => $job->jobStatusId,
            'status' => JobStatus::STATUS_FAILED,
        ]);

        Notification::assertSentTo($user, JobCompleted::class);
    }
}
