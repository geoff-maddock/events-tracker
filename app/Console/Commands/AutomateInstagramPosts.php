<?php

namespace App\Console\Commands;

use App\Mail\InstagramPostFailure;
use App\Models\Event;
use App\Models\EventShare;
use App\Services\Integrations\Instagram;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Storage;

class AutomateInstagramPosts extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'instagram:autopost';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically post events to Instagram based on configured schedule';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Instagram $instagram): int
    {
        $this->info('Starting automated Instagram posting...');

        // Get events that need to be posted
        $events = $this->getEventsToPost();

        if ($events->isEmpty()) {
            $this->info('No events to post at this time.');
            Log::info('AutomateInstagramPosts: No events to post');
            return Command::SUCCESS;
        }

        $totalEvents = $events->count();
        $this->info("Found {$totalEvents} event(s) eligible for posting.");

        // Calculate how many to post: 3 or 1/16th of total, whichever is greater
        $batchSize = max(3, (int) ceil($totalEvents / 16));
        $this->info("Will attempt to post {$batchSize} event(s) in this batch.");

        // Take the batch to post
        $eventsToPost = $events->take($batchSize);
        
        $successCount = 0;
        $failureCount = 0;

        foreach ($eventsToPost as $event) {
            $result = $this->postEventToInstagram($event, $instagram);
            
            if ($result['success']) {
                $successCount++;
                $this->info("✓ Successfully posted event #{$event->id}: {$event->name}");
            } else {
                $failureCount++;
                $this->error("✗ Failed to post event #{$event->id}: {$event->name}");
            }
        }

        $this->info("\nCompleted: {$successCount} successful, {$failureCount} failed");
        Log::info("AutomateInstagramPosts: Posted {$successCount} events, {$failureCount} failures");

        return Command::SUCCESS;
    }

    /**
     * Get events that need to be posted to Instagram.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getEventsToPost()
    {
        $today = Carbon::today();

        // Get all public events that haven't been posted yet or need a reminder post
        $events = Event::where('visibility_id', \App\Models\Visibility::VISIBILITY_PUBLIC)
            ->where('start_at', '>=', $today) // Only future events
            ->whereHas('eventType') // Ensure event has a type
            ->orderBy('start_at', 'ASC')
            ->get()
            ->filter(function ($event) use ($today) {
                // Check if event has a primary photo (required for Instagram)
                if (!$event->getPrimaryPhoto()) {
                    return false;
                }

                // Get all shares for this event on Instagram
                $shares = EventShare::where('event_id', $event->id)
                    ->where('platform', 'instagram')
                    ->where('posted_at', '!=', null) // Only successful posts
                    ->orderBy('posted_at', 'DESC')
                    ->get();

                $shareCount = $shares->count();

                // Rule 1: Never posted before
                if ($shareCount === 0) {
                    Log::info("AutomateInstagramPosts: Event #{$event->id} has never been posted.");
                    return true;
                }

                // Rule 2: If it's been more than 7 days since the last share, 
                // and the event is less than 30 days away, share it again
                $lastShare = $shares->first(); // Most recent share (ordered by posted_at DESC)
                
                if ($lastShare && $lastShare->posted_at) {

                    // output the last posted date and shareCount for debugging in one log statement
                    Log::info("AutomateInstagramPosts: Last posted event #{$event->id} on {$lastShare->posted_at}, share count: {$shareCount}");

                    $daysSinceLastShare = $lastShare->posted_at->diffInDays($today, false);
                    $daysUntilEvent = $today->diffInDays($event->start_at, false);
                    
                    if ($daysSinceLastShare > 7 && $daysUntilEvent < 30) {
                        return true;
                    }
                }

                return false;
            });

        return $events;
    }

    /**
     * Post an event to Instagram.
     *
     * @param Event $event
     * @param Instagram $instagram
     * @return array
     */
    protected function postEventToInstagram(Event $event, Instagram $instagram): array
    {
        try {
            // Get the image URL
            $photo = $event->getPrimaryPhoto();

            if (!$photo) {
                throw new Exception('Event does not have a primary photo');
            }

            $imageUrl = Storage::disk('external')->url($photo->getStoragePath());

            if (!$imageUrl) {
                throw new Exception('Could not generate image URL');
            }

            // Get the Instagram caption
            $caption = $event->getInstagramFormat();

            if (!$caption) {
                throw new Exception('Could not generate Instagram caption');
            }

            // Upload the image
            $igContainerId = $instagram->uploadPhoto($imageUrl, urlEncode($caption));

            // Check the container status
            if ($instagram->checkStatus($igContainerId) === false) {
                throw new Exception('Instagram container status check failed');
            }

            // Publish the image
            $result = $instagram->publishMedia($igContainerId);
            
            if ($result === false) {
                throw new Exception('Failed to publish media to Instagram');
            }

            // Log the successful post
            $eventShare = EventShare::create([
                'event_id' => $event->id,
                'platform' => 'instagram',
                'platform_id' => (string) $result,
                'created_by' => null, // Automated post, no specific user
                'posted_at' => Carbon::now(),
            ]);

            Log::info("AutomateInstagramPosts: Successfully posted event #{$event->id}, Instagram ID: {$result}");

            return [
                'success' => true,
                'platform_id' => $result,
            ];

        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            Log::error("AutomateInstagramPosts: Failed to post event #{$event->id}: {$errorMessage}");

            // Record the failed attempt
            EventShare::create([
                'event_id' => $event->id,
                'platform' => 'instagram',
                'platform_id' => null,
                'created_by' => null,
                'posted_at' => null, // Null indicates failure
            ]);

            // Send failure email to admin
            $this->sendFailureEmail($event, $errorMessage);

            return [
                'success' => false,
                'error' => $errorMessage,
            ];
        }
    }

    /**
     * Send failure notification email to admin.
     *
     * @param Event $event
     * @param string $errorMessage
     * @return void
     */
    protected function sendFailureEmail(Event $event, string $errorMessage): void
    {
        try {
            $site = config('app.app_name');
            $admin_email = config('app.admin');
            $reply_email = config('app.noreplyemail');

            Mail::send(new InstagramPostFailure(
                $event->id,
                $event->name,
                $errorMessage,
                $site,
                $admin_email,
                $reply_email
            ));

            Log::info("AutomateInstagramPosts: Sent failure email for event #{$event->id}");
        } catch (Exception $e) {
            Log::error("AutomateInstagramPosts: Failed to send failure email: {$e->getMessage()}");
        }
    }
}

