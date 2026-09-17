<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use App\Services\EntityStats;
use Illuminate\Http\Request;
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
}
