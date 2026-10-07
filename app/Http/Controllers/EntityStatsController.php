<?php

namespace App\Http\Controllers;

use App\Models\ClickTrack;
use App\Models\Entity;
use App\Services\EntityStats;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Owner analytics for an entity page (#2149).
 */
class EntityStatsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        parent::__construct();
    }

    public const PERIODS = [30, 90];

    public function show(Entity $entity, Request $request, EntityStats $stats): View
    {
        $this->authorize('update', $entity);

        $period = (int) $request->query('period', '30');
        if (!in_array($period, self::PERIODS, true)) {
            $period = self::PERIODS[0];
        }

        return view('entities.stats-tw', [
            'entity' => $entity,
            'period' => $period,
            'periods' => self::PERIODS,
            'stats' => $stats->dashboard($entity, self::PERIODS),
        ]);
    }

    /**
     * Every ticket-link click credited to the entity over the period, with
     * whether it counts in the stats and why not. Admin only (#2293).
     */
    public function clicks(Entity $entity, Request $request, EntityStats $stats): View
    {
        $period = (int) $request->query('period', '30');
        if (!in_array($period, self::PERIODS, true)) {
            $period = self::PERIODS[0];
        }

        // same window as the stats page, but read live, so it includes today
        $start = Carbon::today()->subDays($period - 1);
        $end = Carbon::now();

        $all = $stats->creditedClicks($start, $end, countableOnly: false, entityId: $entity->id);
        $counted = $stats->creditedClicks($start, $end, countableOnly: true, entityId: $entity->id);

        $clicks = ClickTrack::whereIn('id', DB::query()->fromSub($all, 'pairs')->select('source_id'))
            ->with(['event', 'user'])
            ->orderByDesc('clicked_at')
            ->orderByDesc('id')
            ->paginate(100)
            ->withQueryString();

        return view('entities.stats-clicks-tw', [
            'entity' => $entity,
            'period' => $period,
            'periods' => self::PERIODS,
            'clicks' => $clicks,
            'total' => DB::query()->fromSub($all, 'pairs')->count(),
            'counted' => DB::query()->fromSub($counted, 'pairs')->count(),
        ]);
    }
}
