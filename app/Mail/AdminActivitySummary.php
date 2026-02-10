<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminActivitySummary extends Mailable
{
    use Queueable;
    use SerializesModels;

    public string $url;
    public string $site;
    public string $admin_email;
    public string $reply_email;
    public int $days;
    public Carbon $startDate;
    public Carbon $endDate;
    public array $summary;
    public array $counts;
    public array $userCounts;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
        string $url,
        string $site,
        string $admin_email,
        string $reply_email,
        int $days,
        Carbon $startDate,
        Carbon $endDate,
        array $summary,
        array $counts,
        array $userCounts
    ) {
        $this->url = $url;
        $this->site = $site;
        $this->admin_email = $admin_email;
        $this->reply_email = $reply_email;
        $this->days = $days;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->summary = $summary;
        $this->counts = $counts;
        $this->userCounts = $userCounts;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): AdminActivitySummary
    {
        return $this->markdown('emails.admin-activity-summary')
            ->from($this->reply_email, $this->site)
            ->subject($this->site . ': Activity Summary - ' . $this->startDate->format('M j') . ' to ' . $this->endDate->format('M j, Y'));
    }
}
