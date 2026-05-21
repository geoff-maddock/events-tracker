<?php

namespace App\Notifications;

use App\Models\JobStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a user that a background job they triggered has finished.
 *
 * Always stored via the `database` channel (drives the notifications bell).
 * Also sent via `mail` when config('app.queue_notify_email') is enabled.
 */
class JobCompleted extends Notification
{
    use Queueable;

    public function __construct(
        protected JobStatus $jobStatus,
        protected bool $succeeded,
        protected string $message
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (config('app.queue_notify_email')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage())
            ->subject(($this->succeeded ? 'Completed: ' : 'Failed: ') . $this->jobStatus->label)
            ->line($this->message);

        if ($this->jobStatus->subject_type && $this->jobStatus->subject_id) {
            $mail->action('View', url('/job-status'));
        }

        return $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'job_status_id' => $this->jobStatus->id,
            'type' => $this->jobStatus->type,
            'label' => $this->jobStatus->label,
            'status' => $this->jobStatus->status,
            'succeeded' => $this->succeeded,
            'message' => $this->message,
            'subject_type' => $this->jobStatus->subject_type,
            'subject_id' => $this->jobStatus->subject_id,
        ];
    }
}
