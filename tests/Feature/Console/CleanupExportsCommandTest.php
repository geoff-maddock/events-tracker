<?php

namespace Tests\Feature\Console;

use App\Services\DataExportService;
use Mockery\MockInterface;
use Tests\TestCase;

class CleanupExportsCommandTest extends TestCase
{
    public function test_command_invokes_service_with_default_days(): void
    {
        $this->mock(DataExportService::class, function (MockInterface $mock) {
            $mock->shouldReceive('cleanupOldExports')->once()->with(7);
        });

        $this->artisan('cleanup:exports')->assertExitCode(0);
    }

    public function test_command_passes_days_option(): void
    {
        $this->mock(DataExportService::class, function (MockInterface $mock) {
            $mock->shouldReceive('cleanupOldExports')->once()->with(30);
        });

        $this->artisan('cleanup:exports', ['--days' => 30])->assertExitCode(0);
    }
}
