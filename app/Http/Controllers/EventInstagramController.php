<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Concerns\ChecksInstagramPosting;
use App\Jobs\Instagram\PostEventStoryToInstagram;
use App\Jobs\Instagram\PostEventToInstagram;
use App\Jobs\Instagram\PostTodaysPreviewToInstagram;
use App\Jobs\Instagram\PostWeekendPreviewToInstagram;
use App\Models\Event;
use App\Services\Integrations\Instagram;
use App\Services\Integrations\InstagramEventPoster;
use App\Services\ImageHandler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Instagram posting from the site: these actions sit on web routes, flash a
 * message and redirect (or answer an AJAX caller with JSON). The API's
 * token-authenticated endpoint is Api\EventInstagramController (#2179).
 */
class EventInstagramController extends Controller
{
    use ChecksInstagramPosting;

    /**
     * Queue a single event photo to be posted to Instagram.
     */
    public function postToInstagram(int $id, Instagram $instagram): RedirectResponse
    {
        if (!$event = Event::find($id)) {
            flash()->error('Error', 'No such event');

            return back();
        }

        if ($error = $this->eventShareError($event, $this->user)) {
            flash()->error('Error', $error);

            return back();
        }

        if ($error = $this->instagramCredentialError($instagram)) {
            flash()->error('Error', $error);

            return back();
        }

        if ($error = $this->eventPhotoError($event)) {
            flash()->error('Error', $error);

            return back();
        }

        if ($error = $this->recentRepostError($event, $this->user)) {
            flash()->error('Already posted', $error);

            return back();
        }

        PostEventToInstagram::dispatch($event, false, $this->user?->id);

        flash()->success('Queued', 'This event is being posted to Instagram in the background. You will be notified when it finishes.');

        return back();
    }

    /**
     * Queue an event to be posted to Instagram as a carousel.
     */
    public function postCarouselToInstagram(int $id, Instagram $instagram): RedirectResponse|JsonResponse
    {
        if (!$event = Event::find($id)) {
            return $this->instagramActionResponse(false, 'Error', 'No such event');
        }

        if ($error = $this->eventShareError($event, $this->user)) {
            return $this->instagramActionResponse(false, 'Error', $error);
        }

        if ($error = $this->instagramCredentialError($instagram)) {
            return $this->instagramActionResponse(false, 'Error', $error);
        }

        if ($error = $this->eventPhotoError($event)) {
            return $this->instagramActionResponse(false, 'Error', $error);
        }

        if ($error = $this->recentRepostError($event, $this->user)) {
            return $this->instagramActionResponse(false, 'Already posted', $error);
        }

        PostEventToInstagram::dispatch($event, true, $this->user?->id);

        return $this->instagramActionResponse(true, 'Queued', 'This event is being posted to Instagram in the background. You will be notified when it finishes.');
    }

    /**
     * Respond to an Instagram post action. AJAX callers (the event page) get JSON
     * so the request never navigates and stays out of the browser history;
     * regular requests fall back to a flash message and redirect.
     */
    private function instagramActionResponse(bool $success, string $title, string $message): RedirectResponse|JsonResponse
    {
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(
                ['success' => $success, 'title' => $title, 'message' => $message],
                $success ? 200 : 422
            );
        }

        flash()->{$success ? 'success' : 'error'}($title, $message);

        return back();
    }

    /**
     * Queue an event to be posted to Instagram as a STORY.
     */
    public function postStoryToInstagram(int $id, Instagram $instagram): RedirectResponse|JsonResponse
    {
        // admin-only action
        $user = Auth::user();
        if (!$user || !$user->hasGroup('super_admin')) {
            return $this->instagramActionResponse(false, 'Error', 'You are not authorized to post stories to Instagram.');
        }

        if (!$event = Event::find($id)) {
            return $this->instagramActionResponse(false, 'Error', 'No such event');
        }

        if ($error = $this->instagramCredentialError($instagram)) {
            return $this->instagramActionResponse(false, 'Error', $error);
        }

        PostEventStoryToInstagram::dispatch($event, $user->id);

        return $this->instagramActionResponse(true, 'Queued', 'This story is being posted to Instagram in the background. You will be notified when it finishes.');
    }

    /**
     * Post this week's events to Instagram as one carousel. Admin only.
     */
    public function postWeekToInstagram(InstagramEventPoster $poster, ImageHandler $imageHandler): RedirectResponse
    {
        if (!$this->user || !$this->user->hasGroup('super_admin')) {
            flash()->error('Error', 'You must be an admin to post the week to Instagram.');

            return back();
        }

        try {
            $result = $poster->postWeek($imageHandler, $this->user->id);
        } catch (RuntimeException $e) {
            flash()->error('Error', $e->getMessage());

            return back();
        }

        flash()->success('Success', 'Successfully published to Instagram, returned id: '.$result);

        return back();
    }

    /**
     * Queue the weekend preview to be posted to Instagram Stories.
     * The queued job picks every public event of the upcoming weekend and posts
     * them in batches of ten (#2159). Only accessible by admins.
     */
    public function postWeekendPreviewToInstagram(Instagram $instagram): RedirectResponse|JsonResponse
    {
        // Admin-only guard
        if (!$this->user || !$this->user->hasGroup('super_admin')) {
            return $this->instagramActionResponse(false, 'Error', 'You must be an admin to post the weekend preview to Instagram.');
        }

        // Fail fast when Instagram is not linked; the job re-checks at run time.
        if ($error = $this->instagramCredentialError($instagram)) {
            return $this->instagramActionResponse(false, 'Error', $error);
        }

        PostWeekendPreviewToInstagram::dispatch($this->user->id);

        return $this->instagramActionResponse(true, 'Queued', 'The weekend preview is being posted to Instagram in the background. You will be notified when it finishes.');
    }

    /**
     * Queue today's preview to be posted to Instagram Stories. Event selection
     * happens inside the queued job. Admin only.
     */
    public function postTodaysPreviewToInstagram(Instagram $instagram): RedirectResponse|JsonResponse
    {
        // Admin-only guard
        if (!$this->user || !$this->user->hasGroup('super_admin')) {
            return $this->instagramActionResponse(false, 'Error', "You must be an admin to post today's preview to Instagram.");
        }

        // Fail fast when Instagram is not linked; the job re-checks at run time.
        if ($error = $this->instagramCredentialError($instagram)) {
            return $this->instagramActionResponse(false, 'Error', $error);
        }

        PostTodaysPreviewToInstagram::dispatch($this->user->id);

        return $this->instagramActionResponse(true, 'Queued', "Today's preview is being posted to Instagram in the background. You will be notified when it finishes.");
    }
}
