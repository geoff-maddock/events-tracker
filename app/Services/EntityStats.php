<?php

namespace App\Services;

use App\Helpers\BotDetector;
use App\Models\Entity;
use App\Models\EventReachDaily;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Records and rolls up the daily counters behind the entity owner dashboard (#2146).
 */
class EntityStats
{
    /**
     * Count one page view of an entity, unless it came from a bot, a browser
     * prefetch, or someone who manages the entity.
     */
    public function recordView(Entity $entity, Request $request, ?User $user): void
    {
        if (!$this->isCountableView($entity, $request, $user)) {
            return;
        }

        $now = Carbon::now();

        // a single statement so concurrent views never lose an increment
        DB::statement(
            'INSERT INTO entity_stats_daily (entity_id, date, views, created_at, updated_at)
             VALUES (?, ?, 1, ?, ?)
             ON DUPLICATE KEY UPDATE views = views + 1, updated_at = VALUES(updated_at)',
            [$entity->id, $now->toDateString(), $now, $now]
        );
    }

    public function isCountableView(Entity $entity, Request $request, ?User $user): bool
    {
        if (!$request->isMethod('GET')) {
            return false;
        }

        // real browsers always send a user agent; an empty one is a script
        $userAgent = $request->userAgent();
        if (empty($userAgent) || BotDetector::isBot($userAgent)) {
            return false;
        }

        // speculative prefetches are not views
        $purpose = strtolower((string) ($request->header('Sec-Purpose') ?? $request->header('Purpose')));
        if (str_contains($purpose, 'prefetch') || str_contains($purpose, 'prerender')) {
            return false;
        }

        // owners and admins checking their own page would inflate the numbers
        if ($user && $user->can('update', $entity)) {
            return false;
        }

        return true;
    }

    /**
     * Record that each event reached one more recipient on a channel today.
     *
     * @param iterable<int> $eventIds
     */
    public function recordEventReach(iterable $eventIds, string $channel = EventReachDaily::CHANNEL_DIGEST): void
    {
        $ids = collect($eventIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return;
        }

        $now = Carbon::now();
        $placeholders = $ids->map(fn () => '(?, ?, ?, 1, ?, ?)')->implode(', ');
        $bindings = $ids->flatMap(fn (int $id) => [$id, $now->toDateString(), $channel, $now, $now])->all();

        DB::statement(
            "INSERT INTO event_reach_daily (event_id, date, channel, count, created_at, updated_at)
             VALUES {$placeholders}
             ON DUPLICATE KEY UPDATE count = count + 1, updated_at = VALUES(updated_at)",
            $bindings
        );
    }

    /**
     * Rebuild follows, clicks and responses for one day. Views are left alone
     * because they are only ever recorded live. Safe to re-run.
     */
    public function rollupDay(CarbonInterface $day): int
    {
        $start = Carbon::parse($day)->startOfDay();
        $end = Carbon::parse($day)->endOfDay();
        $date = $start->toDateString();

        $follows = $this->followCounts($start, $end);
        $clicks = $this->clickCounts($start, $end);
        $responses = $this->responseCounts($start, $end);

        $entityIds = $follows->keys()->merge($clicks->keys())->merge($responses->keys())->unique();

        // only entities that still exist, so a stale click never breaks the foreign key
        $entityIds = DB::table('entities')->whereIn('id', $entityIds)->pluck('id');

        return DB::transaction(function () use ($date, $entityIds, $follows, $clicks, $responses) {
            // zero the day first so a re-run reflects rows deleted since the last run
            DB::table('entity_stats_daily')
                ->where('date', $date)
                ->update(['follows' => 0, 'clicks' => 0, 'responses' => 0]);

            $now = Carbon::now();
            $rows = $entityIds->map(fn ($id) => [
                'entity_id' => $id,
                'date' => $date,
                'views' => 0,
                'follows' => (int) $follows->get($id, 0),
                'clicks' => (int) $clicks->get($id, 0),
                'responses' => (int) $responses->get($id, 0),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($rows->chunk(500) as $chunk) {
                DB::table('entity_stats_daily')->upsert(
                    $chunk->values()->all(),
                    ['entity_id', 'date'],
                    ['follows', 'clicks', 'responses', 'updated_at']
                );
            }

            return $rows->count();
        });
    }

    /**
     * @return Collection<int, int> entity id => new follows
     */
    protected function followCounts(CarbonInterface $start, CarbonInterface $end): Collection
    {
        return DB::table('follows')
            ->where('object_type', 'entity')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('object_id')
            ->selectRaw('object_id, COUNT(*) as total')
            ->pluck('total', 'object_id');
    }

    /**
     * Ticket-link clicks credited to every entity on the event (venue,
     * promoter and billed entities), counting each click once per entity.
     *
     * @return Collection<int, int> entity id => clicks
     */
    protected function clickCounts(CarbonInterface $start, CarbonInterface $end): Collection
    {
        $window = [$start, $end];

        $billed = DB::table('click_tracks as c')
            ->join('entity_event as ee', 'ee.event_id', '=', 'c.event_id')
            ->whereBetween('c.clicked_at', $window)
            ->select('c.id as source_id', 'ee.entity_id');

        $venue = DB::table('click_tracks as c')
            ->whereNotNull('c.venue_id')
            ->whereBetween('c.clicked_at', $window)
            ->select('c.id as source_id', 'c.venue_id as entity_id');

        $promoter = DB::table('click_tracks as c')
            ->whereNotNull('c.promoter_id')
            ->whereBetween('c.clicked_at', $window)
            ->select('c.id as source_id', 'c.promoter_id as entity_id');

        return $this->countPerEntity($billed->union($venue)->union($promoter));
    }

    /**
     * Event responses (attending, interested, ...) created on the entity's events.
     *
     * @return Collection<int, int> entity id => responses
     */
    protected function responseCounts(CarbonInterface $start, CarbonInterface $end): Collection
    {
        $window = [$start, $end];

        $billed = DB::table('event_responses as r')
            ->join('entity_event as ee', 'ee.event_id', '=', 'r.event_id')
            ->whereBetween('r.created_at', $window)
            ->select('r.id as source_id', 'ee.entity_id');

        $venue = DB::table('event_responses as r')
            ->join('events as e', 'e.id', '=', 'r.event_id')
            ->whereNotNull('e.venue_id')
            ->whereBetween('r.created_at', $window)
            ->select('r.id as source_id', 'e.venue_id as entity_id');

        $promoter = DB::table('event_responses as r')
            ->join('events as e', 'e.id', '=', 'r.event_id')
            ->whereNotNull('e.promoter_id')
            ->whereBetween('r.created_at', $window)
            ->select('r.id as source_id', 'e.promoter_id as entity_id');

        return $this->countPerEntity($billed->union($venue)->union($promoter));
    }

    /**
     * UNION (not UNION ALL) drops a duplicate (source, entity) pair, so an
     * entity that is both the venue and billed on an event counts once.
     *
     * @return Collection<int, int>
     */
    protected function countPerEntity(\Illuminate\Database\Query\Builder $pairs): Collection
    {
        return DB::query()
            ->fromSub($pairs, 'pairs')
            ->groupBy('entity_id')
            ->selectRaw('entity_id, COUNT(*) as total')
            ->pluck('total', 'entity_id');
    }
}
