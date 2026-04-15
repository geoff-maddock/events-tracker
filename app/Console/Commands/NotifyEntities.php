<?php

namespace App\Console\Commands;

use App\Mail\EntityOutreachAdminSummary;
use App\Mail\EntityReminder;
use App\Models\Action;
use App\Models\Activity;
use App\Models\Entity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyEntities extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'notifyEntities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder emails to entities with contact emails who have not logged in within the past 2 months, and send the admin a list of Instagram-only entities for manual outreach.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $reply_email = config('app.noreplyemail');
        $admin_email = config('app.admin');
        $site = config('app.app_name');
        $url = config('app.url');

        $cutoff = Carbon::now()->subMonths(2);
        $emailedCount = 0;

        // Gather all entities that have at least one contact with an email address
        /** @var \Illuminate\Database\Eloquent\Collection<int, Entity> $entities */
        $entities = Entity::whereHas('contacts', function ($q) {
            $q->whereNotNull('email')->where('email', '!=', '');
        })->with(['contacts', 'roles'])->get();

        $this->info("Found {$entities->count()} entities with contact emails.");

        foreach ($entities as $entity) {
            /** @var Entity $entity */

            // Collect contact emails for this entity
            $contactEmails = $entity->contacts
                ->filter(fn ($c) => !empty($c->email))
                ->pluck('email');

            if ($contactEmails->isEmpty()) {
                continue;
            }

            // Check whether any user matching a contact email has logged in recently.
            // If so, they are already engaged — skip this entity.
            $recentLogin = User::whereIn('email', $contactEmails)
                ->whereHas('activity', function ($q) use ($cutoff) {
                    $q->where('action_id', Action::LOGIN)
                        ->where('created_at', '>=', $cutoff);
                })->exists();

            if ($recentLogin) {
                Log::info("NotifyEntities: Skipping {$entity->name} — contact user logged in recently.");
                continue;
            }

            // Gather upcoming events for the entity (next 90 days, max 10)
            $upcomingEvents = $entity->events()
                ->where('start_at', '>=', Carbon::now())
                ->where('start_at', '<=', Carbon::now()->addDays(90))
                ->orderBy('start_at', 'ASC')
                ->limit(10) // show up to 10 upcoming events in the reminder email
                ->get();

            // Gather related entities (those that frequently perform with this entity), up to 5
            $relatedEntities = $entity->getFrequentlyPerformsWith(5);

            // Gather upcoming events for those related entities (next 90 days, max 5 per entity)
            $relatedEvents = new Collection();
            foreach ($relatedEntities as $relatedEntity) {
                /** @var Entity $relatedEntity */
                $entityEvents = $relatedEntity->events()
                    ->where('start_at', '>=', Carbon::now())
                    ->where('start_at', '<=', Carbon::now()->addDays(90))
                    ->orderBy('start_at', 'ASC')
                    ->limit(5)
                    ->get();

                $relatedEvents = $relatedEvents->merge($entityEvents);
            }

            // Deduplicate and sort by start date
            $relatedEvents = $relatedEvents->unique('id')->sortBy('start_at')->values();

            // Send a reminder email to each contact email for this entity
            foreach ($contactEmails as $contactEmail) {
                try {
                    Mail::to($contactEmail)
                        ->send(new EntityReminder(
                            $url,
                            $site,
                            $admin_email,
                            $reply_email,
                            $entity,
                            $upcomingEvents,
                            $relatedEntities,
                            $relatedEvents
                        ));

                    Log::info("NotifyEntities: Sent reminder to {$entity->name} at {$contactEmail}.");
                    $emailedCount++;
                } catch (\Exception $e) {
                    Log::error("NotifyEntities: Failed to send to {$entity->name} at {$contactEmail}: {$e->getMessage()}");
                    $this->error("Failed to send to {$entity->name} at {$contactEmail}: {$e->getMessage()}");
                }
            }
        }

        // Find entities that have an Instagram username but no contact email —
        // these are candidates for manual Instagram DM outreach by the admin.
        $instagramEntities = Entity::whereNotNull('instagram_username')
            ->where('instagram_username', '!=', '')
            ->whereDoesntHave('contacts', function ($q) {
                $q->whereNotNull('email')->where('email', '!=', '');
            })
            ->orderBy('name', 'ASC')
            ->get();

        $this->info("Found {$instagramEntities->count()} Instagram-only entities for admin outreach.");

        // Send the admin a summary email with the Instagram-only list and a template message
        try {
            Mail::to($admin_email)
                ->send(new EntityOutreachAdminSummary(
                    $url,
                    $site,
                    $admin_email,
                    $reply_email,
                    $instagramEntities,
                    $emailedCount
                ));

            Log::info("NotifyEntities: Admin outreach summary sent to {$admin_email}.");
        } catch (\Exception $e) {
            Log::error("NotifyEntities: Failed to send admin summary: {$e->getMessage()}");
            $this->error("Failed to send admin summary: {$e->getMessage()}");
        }

        $this->info("NotifyEntities complete. Sent {$emailedCount} entity reminder email(s).");

        return Command::SUCCESS;
    }
}
