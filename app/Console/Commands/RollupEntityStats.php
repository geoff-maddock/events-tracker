<?php

namespace App\Console\Commands;

use App\Services\EntityStats;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RollupEntityStats extends Command
{
    /**
     * @var string
     */
    protected $signature = 'entities:rollup-stats
        {--date= : Day to roll up (Y-m-d); defaults to yesterday}
        {--days=1 : Number of days to roll up, ending on --date, for backfills}';

    /**
     * @var string
     */
    protected $description = 'Rebuild daily follows, ticket clicks and event responses per entity for the owner dashboard.';

    public function handle(EntityStats $stats): int
    {
        $end = $this->option('date')
            ? Carbon::createFromFormat('Y-m-d', (string) $this->option('date'))->startOfDay()
            : Carbon::yesterday();
        $days = max(1, (int) $this->option('days'));

        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $day = $end->copy()->subDays($offset);
            $count = $stats->rollupDay($day);
            $this->line(sprintf('%s: %d entities', $day->toDateString(), $count));
        }

        return self::SUCCESS;
    }
}
