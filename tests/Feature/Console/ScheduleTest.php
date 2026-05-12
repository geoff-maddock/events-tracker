<?php

namespace Tests\Feature\Console;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    private function commands(): array
    {
        $events = app(Schedule::class)->events();

        return array_map(fn ($e) => $e->command, $events);
    }

    public function test_userCleanup_is_scheduled(): void
    {
        $this->assertTrue($this->hasCommandLike('userCleanup'));
    }

    public function test_cleanup_exports_is_scheduled(): void
    {
        $this->assertTrue($this->hasCommandLike('cleanup:exports'));
    }

    public function test_notifyWeekly_is_scheduled(): void
    {
        $this->assertTrue($this->hasCommandLike('notifyWeekly'));
    }

    public function test_admin_activity_summary_is_scheduled(): void
    {
        $this->assertTrue($this->hasCommandLike('admin:activity-summary'));
    }

    public function test_notifyEntities_is_scheduled(): void
    {
        $this->assertTrue($this->hasCommandLike('notifyEntities'));
    }

    public function test_notify_is_scheduled(): void
    {
        // Match 'notify' as a whole word (don't confuse with notifyWeekly).
        $events = app(Schedule::class)->events();
        $found = false;
        foreach ($events as $event) {
            if (preg_match("/'artisan'\s+notify(\s|$)/", (string) $event->command)) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, "Expected 'notify' command to be scheduled.");
    }

    private function hasCommandLike(string $needle): bool
    {
        foreach ($this->commands() as $command) {
            if (str_contains((string) $command, $needle)) {
                return true;
            }
        }

        return false;
    }
}
