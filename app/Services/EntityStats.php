<?php

namespace App\Services;

use App\Helpers\BotDetector;
use App\Models\Entity;
use App\Models\EntityStatDaily;
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
     * Everything the owner dashboard shows for an entity (#2149).
     *
     * @return array{
     *     trackingSince: ?Carbon,
     *     followers: int,
     *     upcomingEvents: int,
     *     upcomingResponses: int,
     *     periods: array<int, array<string, array{current: int, previous: int}>>,
     *     reach: array<int, array{digest: int, instagram: int, discord: int}>,
     *     chart: array{labels: array<int, string>, views: array<int, int>, follows: array<int, int>, clicks: array<int, int>}
     * }
     */
    public function dashboard(Entity $entity, array $periods = [30, 90]): array
    {
        $today = Carbon::today();
        $eventIds = $this->entityEventIds($entity);

        $firstTracked = EntityStatDaily::min('date');

        $summary = [
            'trackingSince' => $firstTracked ? Carbon::parse($firstTracked) : null,
            'followers' => DB::table('follows')->where('object_type', 'entity')->where('object_id', $entity->id)->count(),
            'upcomingEvents' => DB::table('events')->whereIn('id', $eventIds)->where('start_at', '>=', $today)->count(),
            'upcomingResponses' => DB::table('event_responses')
                ->whereIn('event_id', DB::table('events')->whereIn('id', $eventIds)->where('start_at', '>=', $today)->select('id'))
                ->count(),
            'periods' => [],
            'reach' => [],
        ];

        foreach ($periods as $days) {
            // the current window includes today, so partial-day live views show up
            $start = $today->copy()->subDays($days - 1);
            $previousStart = $start->copy()->subDays($days);

            $summary['periods'][$days] = [
                'views' => $this->compare($this->dailySum($entity, 'views', $start, $today), $this->dailySum($entity, 'views', $previousStart, $start->copy()->subDay())),
                'clicks' => $this->compare($this->dailySum($entity, 'clicks', $start, $today), $this->dailySum($entity, 'clicks', $previousStart, $start->copy()->subDay())),
                'responses' => $this->compare($this->dailySum($entity, 'responses', $start, $today), $this->dailySum($entity, 'responses', $previousStart, $start->copy()->subDay())),
                // new follows are read live rather than from the rollup, so they are never a day behind
                'follows' => $this->compare($this->newFollows($entity, $start, $today->copy()->endOfDay()), $this->newFollows($entity, $previousStart, $start->copy()->subSecond())),
            ];

            $summary['reach'][$days] = [
                'digest' => (int) DB::table('event_reach_daily')
                    ->whereIn('event_id', $eventIds)
                    ->where('channel', EventReachDaily::CHANNEL_DIGEST)
                    ->whereBetween('date', [$start->toDateString(), $today->toDateString()])
                    ->sum('count'),
                'instagram' => DB::table('event_shares')
                    ->whereIn('event_id', $eventIds)
                    ->where('platform', 'instagram')
                    ->where('created_at', '>=', $start)
                    ->count(),
                'discord' => DB::table('discord_posts')
                    ->whereIn('event_id', $eventIds)
                    ->where('status', 'sent')
                    ->where('created_at', '>=', $start)
                    ->count(),
            ];
        }

        $summary['chart'] = $this->chartSeries($entity, max($periods));

        return $summary;
    }

    /**
     * Ids of every event the entity is the venue, promoter, or billed on.
     */
    protected function entityEventIds(Entity $entity): \Illuminate\Database\Query\Builder
    {
        return DB::table('entity_event')->where('entity_id', $entity->id)->select('event_id as id')
            ->union(DB::table('events')->where('venue_id', $entity->id)->select('id'))
            ->union(DB::table('events')->where('promoter_id', $entity->id)->select('id'));
    }

    protected function dailySum(Entity $entity, string $column, CarbonInterface $from, CarbonInterface $to): int
    {
        return (int) DB::table('entity_stats_daily')
            ->where('entity_id', $entity->id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->sum($column);
    }

    protected function newFollows(Entity $entity, CarbonInterface $from, CarbonInterface $to): int
    {
        return DB::table('follows')
            ->where('object_type', 'entity')
            ->where('object_id', $entity->id)
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }

    /**
     * @return array{current: int, previous: int}
     */
    protected function compare(int $current, int $previous): array
    {
        return ['current' => $current, 'previous' => $previous];
    }

    /**
     * One point per day, with missing days filled with zero.
     *
     * @return array{labels: array<int, string>, views: array<int, int>, follows: array<int, int>, clicks: array<int, int>}
     */
    protected function chartSeries(Entity $entity, int $days): array
    {
        $end = Carbon::today();
        $start = $end->copy()->subDays($days - 1);

        $rows = DB::table('entity_stats_daily')
            ->where('entity_id', $entity->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['date', 'views', 'clicks'])
            ->keyBy(fn ($row) => Carbon::parse($row->date)->toDateString());

        $follows = DB::table('follows')
            ->where('object_type', 'entity')
            ->where('object_id', $entity->id)
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $series = ['labels' => [], 'views' => [], 'follows' => [], 'clicks' => []];
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $key = $day->toDateString();
            $series['labels'][] = $day->format('M j');
            $series['views'][] = (int) ($rows[$key]->views ?? 0);
            $series['clicks'][] = (int) ($rows[$key]->clicks ?? 0);
            $series['follows'][] = (int) ($follows[$key] ?? 0);
        }

        return $series;
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
