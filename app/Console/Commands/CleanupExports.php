<?php

namespace App\Console\Commands;

use App\Services\DataExportService;
use Illuminate\Console\Command;

/**
 * Handles cleanup of old data export files
 */
class CleanupExports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:exports {--days= : Number of days to keep exports (default: as long as a download link works)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old user data export files';

    /**
     * Execute the console command.
     */
    public function handle(DataExportService $exportService): int
    {
        $days = (int) ($this->option('days') ?? DataExportService::KEEP_DAYS);
        
        $this->info("Cleaning up exports older than {$days} days...");
        
        $exportService->cleanupOldExports($days);
        
        $this->info('Export cleanup completed.');
        
        return Command::SUCCESS;
    }
}
