<?php

namespace App\Console\Commands;

use App\Mail\EssentialEventsDigest;
use App\Models\Event;
use App\Models\NewsletterSubscriber;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NewsletterSendEssential extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'newsletter:send-essential {days=10 : How many days ahead to include events}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send the weekly Essential Events digest to all confirmed newsletter subscribers.';

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

        // public, future events flagged essential within the window
        $events = Event::future()
            ->visible(null)
            ->where('is_essential', true)
            ->where('start_at', '<=', Carbon::now()->addDays($days))
            ->with(['venue', 'series', 'eventType', 'tags', 'entities', 'user'])
            ->get();

        if ($events->isEmpty()) {
            $this->info('No essential events in the next '.$days.' days - no digest sent.');
            Log::info('Essential events digest skipped - no essential events in the next '.$days.' days.');

            return Command::SUCCESS;
        }

        $subscribers = NewsletterSubscriber::confirmed()->get();
        $sent = 0;

        foreach ($subscribers as $subscriber) {
            try {
                Mail::to($subscriber->email)
                    ->send(new EssentialEventsDigest($url, $site, $admin_email, $reply_email, $subscriber, $events));
                $sent++;
            } catch (\Exception $e) {
                // one bad address must not abort the whole run
                Log::error('Failed to send essential events digest to '.$subscriber->email.': '.$e->getMessage());
            }
        }

        $this->info('Essential events digest sent to '.$sent.' of '.$subscribers->count().' subscribers ('.$events->count().' events).');
        Log::info('Essential events digest sent to '.$sent.' subscribers with '.$events->count().' events.');

        return Command::SUCCESS;
    }
}
