<?php

namespace App\Console\Commands;

use App\Mail\AdminActivitySummary as AdminActivitySummaryMail;
use App\Models\Action;
use App\Models\Activity;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AdminActivitySummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:activity-summary {days=7 : Number of days to include in the summary}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send an activity summary email to the admin for the past X days';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->argument('days');
        
        if ($days < 1) {
            $this->error('Days parameter must be at least 1');
            return Command::FAILURE;
        }

        $reply_email = config('app.noreplyemail');
        $admin_email = config('app.admin');
        $site = config('app.app_name');
        $url = config('app.url');

        // Calculate the date range
        $startDate = Carbon::now()->subDays($days)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $this->info("Generating activity summary for the past {$days} days ({$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')})");

        // Get activities within the date range
        $activities = Activity::where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->with(['action', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Organize activities by type
        $summary = [
            'logins' => [],
            'deletions' => [],
            'new_users' => [],
            'new_events' => [],
            'new_entities' => [],
            'new_series' => [],
            'other' => [],
        ];

        $counts = [
            'logins' => 0,
            'deletions' => 0,
            'new_users' => 0,
            'new_events' => 0,
            'new_entities' => 0,
            'new_series' => 0,
            'other' => 0,
        ];

        foreach ($activities as $activity) {
            $actionName = $activity->action ? $activity->action->name : 'Unknown';
            $objectTable = $activity->object_table;

            // Categorize the activity
            if ($activity->action_id === Action::LOGIN) {
                $summary['logins'][] = $activity;
                $counts['logins']++;
            } elseif ($activity->action_id === Action::DELETE) {
                $summary['deletions'][] = $activity;
                $counts['deletions']++;
            } elseif ($activity->action_id === Action::CREATE) {
                if ($objectTable === 'User') {
                    $summary['new_users'][] = $activity;
                    $counts['new_users']++;
                } elseif ($objectTable === 'Event') {
                    $summary['new_events'][] = $activity;
                    $counts['new_events']++;
                } elseif ($objectTable === 'Entity') {
                    $summary['new_entities'][] = $activity;
                    $counts['new_entities']++;
                } elseif ($objectTable === 'Series') {
                    $summary['new_series'][] = $activity;
                    $counts['new_series']++;
                } else {
                    $summary['other'][] = $activity;
                    $counts['other']++;
                }
            } else {
                $summary['other'][] = $activity;
                $counts['other']++;
            }
        }

        // Display summary counts
        $this->info('Activity Summary:');
        $this->table(
            ['Category', 'Count'],
            [
                ['Logins', $counts['logins']],
                ['Deletions', $counts['deletions']],
                ['New Users', $counts['new_users']],
                ['New Events', $counts['new_events']],
                ['New Entities', $counts['new_entities']],
                ['New Series', $counts['new_series']],
                ['Other Activities', $counts['other']],
                ['Total', $activities->count()],
            ]
        );

        // Send email to admin
        try {
            Mail::to($admin_email)
                ->send(new AdminActivitySummaryMail(
                    $url,
                    $site,
                    $admin_email,
                    $reply_email,
                    $days,
                    $startDate,
                    $endDate,
                    $summary,
                    $counts
                ));

            Log::info("Admin activity summary email sent to {$admin_email} for the past {$days} days.");
            $this->info("Activity summary email sent successfully to {$admin_email}");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error("Failed to send admin activity summary email: {$e->getMessage()}");
            $this->error("Failed to send email: {$e->getMessage()}");
            
            return Command::FAILURE;
        }
    }
}
