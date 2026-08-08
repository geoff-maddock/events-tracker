<?php

namespace App\Http\Controllers;

use App\Jobs\Discord\PostEventToDiscord;
use App\Models\DiscordPost;
use App\Models\DiscordTarget;
use App\Models\Event;
use App\Services\Integrations\Discord\DiscordTargetMatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Manual "Post to Discord" action on an event (issue #2058).
 *
 * Deliberately POST rather than GET: this has side effects that leave the
 * server. The existing Instagram equivalents are GET routes bound to an Api\
 * controller while serving web pages; neither choice is repeated here.
 */
class EventDiscordController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'can:admin']);
        parent::__construct();
    }

    /**
     * Queue a post of this event to the chosen channels, or to every channel
     * that matches it when none are named.
     */
    public function store(int $id, Request $request, DiscordTargetMatcher $matcher): RedirectResponse
    {
        $event = Event::findOrFail($id);

        if (! config('discord.enabled')) {
            flash()->error('Discord is disabled', 'Set DISCORD_ENABLED=true to post events.');

            return redirect()->route('events.show', ['event' => $event->slug]);
        }

        $validated = $request->validate([
            'target_id' => 'nullable|array',
            'target_id.*' => 'integer|exists:discord_targets,id',
            'force' => 'nullable|boolean',
        ]);

        $targetIds = array_map('intval', $validated['target_id'] ?? []);
        $force = (bool) ($validated['force'] ?? false);

        $targets = [] === $targetIds
            ? $matcher->targetsFor($event, DiscordTarget::MODE_MANUAL)
            : DiscordTarget::forMode(DiscordTarget::MODE_MANUAL)->whereIn('id', $targetIds)->get();

        if ($targets->isEmpty()) {
            flash()->error('Nothing to post', 'No enabled Discord channel accepts manual posts for this event.');

            return redirect()->route('events.show', ['event' => $event->slug]);
        }

        // A soft block on an accidental double-click. reason_key carries the
        // user and minute, so a deliberate repost with force still gets its
        // own ledger row rather than being swallowed as a duplicate.
        if (! $force && $this->postedRecently($event, $targets->pluck('id')->all())) {
            flash()->error(
                'Already posted',
                'This event went to Discord within the last hour. Use "Force repost" if you meant to send it again.'
            );

            return redirect()->route('events.show', ['event' => $event->slug]);
        }

        PostEventToDiscord::dispatch(
            $event->id,
            DiscordPost::REASON_MANUAL,
            $targets->pluck('id')->all(),
            $this->user?->id.':'.now()->format('YmdHi'),
            $this->user?->id,
        );

        flash()->success('Queued', 'Posting "'.$event->name.'" to '.$targets->count().' Discord channel(s).');

        return redirect()->route('events.show', ['event' => $event->slug]);
    }

    /**
     * @param  array<int, int>  $targetIds
     */
    private function postedRecently(Event $event, array $targetIds): bool
    {
        return DiscordPost::where('event_id', $event->id)
            ->whereIn('discord_target_id', $targetIds)
            ->sent()
            ->where('posted_at', '>=', now()->subHour())
            ->exists();
    }
}
