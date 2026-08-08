<?php

namespace App\Http\Controllers;

use App\Exceptions\DiscordWebhookException;
use App\Filters\DiscordTargetFilters;
use App\Models\DiscordPost;
use App\Models\DiscordTarget;
use App\Models\DiscordTargetCriterion;
use App\Models\Entity;
use App\Models\EventType;
use App\Models\Series;
use App\Models\Tag;
use App\Http\Requests\DiscordTargetRequest;
use App\Services\Integrations\Discord\DiscordEventPoster;
use App\Services\Integrations\Discord\DiscordTargetMatcher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Admin management of Discord repost targets (issue #2058).
 *
 * Follows the newer FeedbackAdminController shape — validated request filters
 * and a plain paginator — rather than the ListParameterSessionStore ceremony,
 * because there are a few dozen admin-only rows here and nothing needs filters
 * persisted across navigations.
 *
 * SECURITY. webhook_url is a bearer credential. It is never rendered back into
 * the edit form, never included in a response, and never logged. Anything
 * user-visible goes through DiscordTarget::maskedUrl().
 */
class DiscordTargetsController extends Controller
{
    protected const PER_PAGE = 25;

    public function __construct()
    {
        $this->middleware(['auth', 'can:admin']);
        parent::__construct();
    }

    public function index(Request $request, DiscordTargetFilters $filters): View
    {
        $applied = array_filter(
            $request->only(['name', 'enabled', 'mode', 'tag']),
            fn ($value): bool => null !== $value && '' !== $value
        );

        $query = DiscordTarget::with('criteria')->orderBy('name');
        $filters->applyFilters($query, $applied);

        /** @var LengthAwarePaginator $targets */
        $targets = $query->paginate(self::PER_PAGE)->appends($request->query());

        return view('discord-targets.index-tw')
            ->with('targets', $targets)
            ->with('filters', $applied)
            ->with('enabled', config('discord.enabled'));
    }

    public function create(): View
    {
        return view('discord-targets.create-tw')
            ->with('target', new DiscordTarget)
            ->with($this->criteriaOptions());
    }

    public function store(DiscordTargetRequest $request): RedirectResponse
    {
        $target = new DiscordTarget;
        $this->fill($target, $request);
        $target->save();

        $this->syncCriteria($target, $request);

        flash()->success('Success', 'Discord target "'.$target->name.'" was created. Send a test message to confirm the webhook works.');

        return redirect()->route('discord-targets.show', ['discordTarget' => $target->id]);
    }

    public function show(DiscordTarget $discordTarget, DiscordTargetMatcher $matcher): View
    {
        // A preview of what this target would receive is the fastest way for an
        // admin to see that their filters do what they think.
        $upcoming = $matcher->eventsFor($discordTarget)
            ->with(['venue', 'eventType'])
            ->limit(10)
            ->get();

        $posts = DiscordPost::where('discord_target_id', $discordTarget->id)
            ->with('event')
            ->orderByDesc('created_at')
            ->limit(25)
            ->get();

        return view('discord-targets.show-tw')
            ->with('target', $discordTarget->load('criteria'))
            ->with('upcoming', $upcoming)
            ->with('posts', $posts)
            ->with('enabled', config('discord.enabled'));
    }

    public function edit(DiscordTarget $discordTarget): View
    {
        return view('discord-targets.edit-tw')
            ->with('target', $discordTarget->load('criteria'))
            ->with($this->criteriaOptions());
    }

    public function update(DiscordTarget $discordTarget, DiscordTargetRequest $request): RedirectResponse
    {
        $this->fill($discordTarget, $request);
        $discordTarget->save();

        $this->syncCriteria($discordTarget, $request);

        flash()->success('Success', 'Discord target "'.$discordTarget->name.'" was updated.');

        return redirect()->route('discord-targets.show', ['discordTarget' => $discordTarget->id]);
    }

    public function destroy(DiscordTarget $discordTarget): RedirectResponse
    {
        $name = $discordTarget->name;
        $discordTarget->delete();

        flash()->success('Success', 'Discord target "'.$name.'" was deleted.');

        return redirect()->route('discord-targets.index');
    }

    /**
     * Send a test message. Runs synchronously — an admin clicking "test" is
     * asking a question and should get the answer on the page, not eventually.
     */
    public function test(DiscordTarget $discordTarget, DiscordEventPoster $poster): RedirectResponse
    {
        if (! config('discord.enabled')) {
            flash()->error('Discord is disabled', 'Set DISCORD_ENABLED=true to send messages.');

            return redirect()->route('discord-targets.show', ['discordTarget' => $discordTarget->id]);
        }

        try {
            $poster->sendTest($discordTarget, $this->user?->id);
            flash()->success('Sent', 'A test message was posted to "'.$discordTarget->name.'".');
        } catch (DiscordWebhookException $e) {
            // The exception message is already scrubbed of the webhook URL.
            flash()->error('Failed', 'Discord rejected the test: '.$e->getMessage());
        }

        return redirect()->route('discord-targets.show', ['discordTarget' => $discordTarget->id]);
    }

    /**
     * Flip a target on or off, including re-enabling one that auto-disabled.
     */
    public function toggle(DiscordTarget $discordTarget): RedirectResponse
    {
        $enabling = ! $discordTarget->is_enabled;

        $discordTarget->forceFill([
            'is_enabled' => $enabling,
            'disabled_at' => $enabling ? null : now(),
            'disabled_reason' => $enabling ? null : 'Disabled by an administrator',
            'consecutive_failures' => $enabling ? 0 : $discordTarget->consecutive_failures,
        ])->save();

        flash()->success('Success', '"'.$discordTarget->name.'" is now '.($enabling ? 'enabled' : 'disabled').'.');

        return redirect()->route('discord-targets.show', ['discordTarget' => $discordTarget->id]);
    }

    /**
     * Copy validated input onto the model, leaving the stored webhook alone
     * when the field came back blank (see DiscordTargetRequest).
     */
    private function fill(DiscordTarget $target, DiscordTargetRequest $request): void
    {
        $target->fill([
            'name' => $request->input('name'),
            'slug' => $request->input('slug') ?: null,
            'is_enabled' => $request->boolean('is_enabled'),
            'match_all' => $request->boolean('match_all'),
            'requires_photo' => $request->boolean('requires_photo'),
            'post_on_create' => $request->boolean('post_on_create'),
            'post_reminders' => $request->boolean('post_reminders'),
            'post_digest' => $request->boolean('post_digest'),
            'allow_manual' => $request->boolean('allow_manual'),
            'reminder_offsets' => $request->reminderOffsets(),
            'digest_day' => (int) $request->input('digest_day', config('discord.digest.default_day')),
            'digest_hour' => (int) $request->input('digest_hour', config('discord.digest.default_hour')),
            'digest_window_days' => (int) $request->input('digest_window_days', config('discord.digest.window_days')),
            'mention' => $request->input('mention') ?: null,
            'username' => $request->input('username') ?: null,
            'avatar_url' => $request->input('avatar_url') ?: null,
            'embed_color' => $request->filled('embed_color') ? (int) $request->input('embed_color') : null,
        ]);

        if ($request->filled('webhook_url')) {
            $target->webhook_url = (string) $request->input('webhook_url');
        }
    }

    /**
     * Replace the target's criteria wholesale. Simpler than a diff, and the
     * rows carry nothing worth preserving beyond their identity.
     */
    private function syncCriteria(DiscordTarget $target, DiscordTargetRequest $request): void
    {
        $rows = $request->criteriaRows();

        DB::transaction(function () use ($target, $rows): void {
            $target->criteria()->delete();

            $seen = [];

            foreach ($rows as $row) {
                // (type, id) is unique per target, so the same entry listed as
                // both include and exclude would violate the index. Drop the
                // repeat quietly rather than 500 — first one listed wins.
                $key = $row['criteria_type'].':'.$row['criteria_id'];

                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                $target->criteria()->create($row);
            }
        });
    }

    /**
     * Option lists for the criteria editor.
     *
     * @return array<string, mixed>
     */
    private function criteriaOptions(): array
    {
        return [
            'tagOptions' => Tag::orderBy('name')->pluck('name', 'id'),
            'eventTypeOptions' => EventType::orderBy('name')->pluck('name', 'id'),
            'seriesOptions' => Series::orderBy('name')->pluck('name', 'id'),
            'venueOptions' => Entity::ofType('Space')->orderBy('name')->pluck('name', 'id'),
            'entityOptions' => Entity::orderBy('name')->limit(500)->pluck('name', 'id'),
            'criteriaTypes' => DiscordTargetCriterion::TYPES,
        ];
    }
}
