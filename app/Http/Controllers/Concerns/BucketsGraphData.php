<?php

namespace App\Http\Controllers\Concerns;

use Carbon\Carbon;

/**
 * Shared time-bucketing helpers for graph endpoints (activity graph, event graph).
 */
trait BucketsGraphData
{
    protected const ALLOWED_GROUP_BY = ['day', 'week', 'month', 'year'];

    protected function buildBucketLabels(Carbon $startDate, Carbon $endDate, string $groupBy): array
    {
        $labels = [];
        $cursor = $this->getBucketStart($startDate, $groupBy);
        $endBucket = $this->getBucketStart($endDate, $groupBy);

        while ($cursor->lte($endBucket)) {
            $labels[] = $this->getBucketLabel($cursor, $groupBy);
            $this->advanceBucketCursor($cursor, $groupBy);
        }

        return $labels;
    }

    protected function getBucketLabel(Carbon $date, string $groupBy): string
    {
        return match ($groupBy) {
            'week' => $date->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d'),
            'month' => $date->copy()->startOfMonth()->format('Y-m'),
            'year' => $date->copy()->startOfYear()->format('Y'),
            default => $date->copy()->startOfDay()->format('Y-m-d'),
        };
    }

    protected function getBucketStart(Carbon $date, string $groupBy): Carbon
    {
        return match ($groupBy) {
            'week' => $date->copy()->startOfWeek(Carbon::MONDAY),
            'month' => $date->copy()->startOfMonth(),
            'year' => $date->copy()->startOfYear(),
            default => $date->copy()->startOfDay(),
        };
    }

    protected function advanceBucketCursor(Carbon $cursor, string $groupBy): void
    {
        match ($groupBy) {
            'week' => $cursor->addWeek(),
            'month' => $cursor->addMonth(),
            'year' => $cursor->addYear(),
            default => $cursor->addDay(),
        };
    }
}
