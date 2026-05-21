<?php

namespace App\Http\Controllers;

use App\Models\JobStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * User-facing view of queued background work and its completion notifications.
 */
class JobStatusController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('auth');
    }

    /**
     * List the current user's recent background jobs and notifications.
     */
    public function index(): View
    {
        $jobStatuses = JobStatus::forUser($this->user?->id)
            ->latest()
            ->limit(50)
            ->get();

        $notifications = $this->user
            ? $this->user->notifications()->limit(50)->get()
            : collect();

        return view('job-statuses.index', [
            'jobStatuses' => $jobStatuses,
            'notifications' => $notifications,
        ]);
    }

    /**
     * Return the JSON status of a single job for UI polling.
     */
    public function show(int $id): JsonResponse
    {
        $jobStatus = JobStatus::findOrFail($id);

        if ($jobStatus->user_id !== $this->user?->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'id' => $jobStatus->id,
            'type' => $jobStatus->type,
            'label' => $jobStatus->label,
            'status' => $jobStatus->status,
            'message' => $jobStatus->message,
            'result' => $jobStatus->result,
            'finished' => $jobStatus->isFinished(),
            'started_at' => $jobStatus->started_at,
            'finished_at' => $jobStatus->finished_at,
        ]);
    }

    /**
     * Mark a single notification, or all notifications, as read.
     */
    public function markNotificationsRead(Request $request): RedirectResponse
    {
        if (!$this->user) {
            return back();
        }

        $id = $request->input('notification_id');

        if ($id) {
            $this->user->notifications()->where('id', $id)->update(['read_at' => now()]);
        } else {
            $this->user->unreadNotifications()->update(['read_at' => now()]);
        }

        return back();
    }
}
