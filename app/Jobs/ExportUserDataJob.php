<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\DataExportService;
use App\Mail\UserDataExportReady;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ExportUserDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected User $user;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(DataExportService $exportService): void
    {
        try {
            Log::info('Starting data export for user: ' . $this->user->id);
            
            // Generate the export
            $exportResult = $exportService->generateExport($this->user);
            
            // Get download URL
            $downloadUrl = $exportService->getDownloadUrl($exportResult['filename']);
            
            Log::info('Data export completed for user: ' . $this->user->id, [
                'filename' => $exportResult['filename'],
                'url' => $downloadUrl
            ]);
            
            // Send email with download link
            $reply_email = config('app.noreplyemail');
            $admin_email = config('app.admin');
            $site = config('app.app_name');
            $url = config('app.url');
            
            Mail::to($this->user->email)->send(
                new UserDataExportReady($url, $site, $admin_email, $reply_email, $this->user, $downloadUrl, $exportResult['filename'])
            );
            
            Log::info('Export email sent to user: ' . $this->user->email);
            
        } catch (\Exception $e) {
            Log::error('Failed to export user data', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-throw to mark job as failed
            throw $e;
        }
    }
    
    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('User data export job failed', [
            'user_id' => $this->user->id,
            'error' => $exception->getMessage()
        ]);
        
        // Optionally notify user of failure
        // Could send a "export failed" email here
    }
}
