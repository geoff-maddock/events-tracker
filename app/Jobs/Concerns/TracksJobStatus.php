<?php

namespace App\Jobs\Concerns;

use App\Models\JobStatus;
use App\Models\User;
use App\Notifications\JobCompleted;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Gives a queued job a user-facing JobStatus row plus completion notifications.
 *
 * Call initJobStatus() from the job constructor (so the row exists the moment
 * the job is dispatched), then markRunning()/markSucceeded()/markFailed()
 * from handle()/failed().
 */
trait TracksJobStatus
{
    /** The JobStatus row id created for this job. */
    public ?int $jobStatusId = null;

    protected ?int $trackedUserId = null;

    /**
     * Create the tracking row. Returns the id so a controller can hand it to the UI.
     */
    protected function initJobStatus(string $type, string $label, ?Model $subject, ?int $userId): int
    {
        $this->trackedUserId = $userId;

        $status = new JobStatus();
        $status->user_id = $userId;
        $status->type = $type;
        $status->label = $label;
        $status->status = JobStatus::STATUS_QUEUED;

        if ($subject !== null) {
            $status->subject_type = $subject->getMorphClass();
            $status->subject_id = $subject->getKey();
        }

        $status->save();

        $this->jobStatusId = $status->id;

        return $status->id;
    }

    protected function jobStatus(): ?JobStatus
    {
        return $this->jobStatusId ? JobStatus::find($this->jobStatusId) : null;
    }

    protected function markRunning(): void
    {
        $this->jobStatus()?->update([
            'status' => JobStatus::STATUS_RUNNING,
            'started_at' => Carbon::now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    protected function markSucceeded(string $message, array $result = []): void
    {
        $this->jobStatus()?->update([
            'status' => JobStatus::STATUS_SUCCEEDED,
            'message' => $message,
            'result' => $result ?: null,
            'finished_at' => Carbon::now(),
        ]);

        $this->notifyTrackedUser(true, $message);
    }

    protected function markFailed(string $message): void
    {
        $this->jobStatus()?->update([
            'status' => JobStatus::STATUS_FAILED,
            'message' => $message,
            'finished_at' => Carbon::now(),
        ]);

        $this->notifyTrackedUser(false, $message);
    }

    private function notifyTrackedUser(bool $succeeded, string $message): void
    {
        if (!$this->trackedUserId) {
            return;
        }

        $user = User::find($this->trackedUserId);
        $status = $this->jobStatus();

        if ($user && $status) {
            $user->notify(new JobCompleted($status, $succeeded, $message));
        }
    }
}
