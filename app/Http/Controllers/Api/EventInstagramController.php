<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ChecksInstagramPosting;
use App\Http\Controllers\Controller;
use App\Jobs\Instagram\PostEventToInstagram;
use App\Models\Event;
use App\Models\Visibility;
use App\Services\Integrations\Instagram;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Token-authenticated Instagram posting. The site's own posting actions live
 * in the web App\Http\Controllers\EventInstagramController (#2179).
 */
class EventInstagramController extends Controller
{
    use ChecksInstagramPosting;

    public function postCarouselToInstagramApi(int $id, Instagram $instagram, Request $request): JsonResponse
    {
        $user = Auth::guard('sanctum')->user() ?? Auth::user();

        if (!$event = Event::find($id)) {
            return response()->json(['success' => false, 'message' => 'No such event'], 404);
        }

        // if the user does not exist, unauthorized
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Check if event is public
        if ($event->visibility_id !== Visibility::VISIBILITY_PUBLIC) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Check if user owns the event OR has admin permission
        $isOwner = $event->created_by === $user->id;
        $isAdmin = $user->hasGroup('admin') || $user->hasGroup('super_admin');

        if (!$isOwner && !$isAdmin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if ($error = $this->instagramCredentialError($instagram)) {
            return response()->json(['success' => false, 'message' => $error], 400);
        }

        if ($error = $this->eventPhotoError($event)) {
            return response()->json(['success' => false, 'message' => $error], 422);
        }

        if ($error = $this->recentRepostError($event, $user)) {
            return response()->json(['success' => false, 'message' => $error], 429);
        }

        // Build the job so we can hand its tracking id back to the caller, then dispatch.
        $job = new PostEventToInstagram($event, true, $user->id);
        dispatch($job);

        return response()->json([
            'success' => true,
            'queued' => true,
            'job_status_id' => $job->jobStatusId,
        ]);
    }
}
