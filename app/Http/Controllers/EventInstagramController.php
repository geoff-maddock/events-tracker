<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Concerns\ChecksInstagramPosting;
use App\Jobs\Instagram\PostEventStoryToInstagram;
use App\Jobs\Instagram\PostEventToInstagram;
use App\Jobs\Instagram\PostTodaysPreviewToInstagram;
use App\Jobs\Instagram\PostWeekendPreviewToInstagram;
use App\Models\Event;
use App\Models\EventShare;
use App\Services\Integrations\Instagram;
use App\Services\ImageHandler;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\File as HttpFile;
use Storage;

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
     * Log a share to the event_shares table.
     *
     * @param Event $event The event that was shared
     * @param int $platformId The ID returned from Instagram (must be a valid integer, not false)
     * @param int|null $userId The ID of the user who created the share
     */
    private function logEventShare(Event $event, int $platformId, ?int $userId): void
    {
        EventShare::create([
            'event_id' => $event->id,
            'platform' => 'instagram',
            'platform_id' => (string) $platformId,
            'created_by' => $userId,
            'posted_at' => Carbon::now(),
        ]);
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
     * Endpoint to post a week's events event to Instagram.
     */
    public function postWeekToInstagram(Instagram $instagram, ImageHandler $imageHandler): RedirectResponse
    {
        // Admin-only guard
        if (!$this->user || !$this->user->hasGroup('super_admin')) {
            flash()->error('Error', 'You must be an admin to post the week to Instagram.');

            return back();
        }

        // load the first 9 events of the week
        $events = Event::where('start_at', '>=', Carbon::now()->startOfWeek())
            ->where('start_at', '<=', Carbon::now()->endOfWeek())
            ->orderBy('start_at', 'ASC')
            ->limit(9)
            ->get();

        // get the first image to post
        $coverFileName = 'week-image.jpg';
        $coverImagePath = $imageHandler->generateCoverImage($coverFileName);

        if (!is_file($coverImagePath)) {
            flash()->error('Error', 'You must have a base image to extract the image to make a week post to Instagram');

            return back();
        }

        // save the file in Storage
        $coverPath = Storage::disk('external')->putFileAs('photos', new HttpFile($coverImagePath), $coverFileName, 'public');
        $coverImageUrl = Storage::disk('external')->url($coverPath);

        // create an array of images to post
        $images[] = $coverImageUrl;
        $igContainerIds = [];

        Log::info('Cover image URL: '.$coverImageUrl);

        // create a string of event data for the caption
        $caption = "Events for the upcoming week...\n";

        // get the instagram account
        if (!$instagram->getIgUserId()) {
            flash()->error('Error', 'You must have an Instagram user account linked to post to Instagram.');

            return back();
        }

        // get the instagram page access token
        if (!$instagram->getPageAccessToken()) {
            flash()->error('Error', 'You must have an Instagram page linked to post to Instagram.');

            return back();
        }

        // upload the cover image
        try {
            $id = $instagram->uploadCarouselPhoto($coverImageUrl);
            $igContainerIds[] = $id;
        } catch (Exception $e) {
            flash()->error('Error', 'There was an error posting to Instagram.  Please try again.');
            Log::info('Carousel photo error: '. $e->getMessage());
            return back();
        }

        Log::info('Carousel photo uploaded: '.$id);

        // get info from the events and upload all photos
        foreach ($events as $event) {
            // get the image URL
            $photo = $event->getPrimaryPhoto();

            if (!$photo) {
                flash()->error('Error', 'You must have an photo to extract the image to post to Instagram');
                Log::info('No photo found for event: '.$event->id);
                continue;
            }

            $imageUrl = Storage::disk('external')->url($photo->getStoragePath());
            $images[] = $imageUrl;

            if (!$imageUrl) {
                flash()->error('Error', 'You must have an image url to post to Instagram');
                Log::info('No image url found for event: '.$event->id);
                continue;
            }
    
            // get the instagram caption
            $caption .= $event->getInstagramFormat()."\n\n";

            if (!$caption) {
                flash()->error('Error', 'You must have an Instagram caption linked to post to Instagram.');
                Log::info('No caption found for event: '.$event->id);
                continue;
            }

            // make the instagram api calls
            // upload the image
            try {
                $igContainerId = $instagram->uploadCarouselPhoto($imageUrl);
                $igContainerIds[] = $igContainerId;
                Log::info('Added container id: '.$igContainerId);
            } catch (Exception $e) {
                flash()->error('Error', 'There was an error posting to Instagram.  Please try again.');
                Log::info('Error uploading carousel photo');
                return back();
            }
        }

        // Check status of all uploaded photos in batch (more efficient than individual checks)
        if ($instagram->checkBatchStatus($igContainerIds) === false) {
            flash()->error('Error', 'There was an error posting to Instagram.  Please try again.');
            Log::info('Error checking batch status');
            return back();
        }

        Log::info('Looped through events');

        Log::info('Caption:'.$caption);
        Log::info('IG Container Ids:'.json_encode($igContainerIds));

        // create a carousel container
        try {
            $igContainerId = $instagram->createCarousel($igContainerIds, $caption);
        } catch (Exception $e) {
            flash()->error('Error', 'There was an error posting carousel to Instagram.  Please try again.');
            Log::info('Error creating carousel');
            return back();
        }

        // check the carousel container status
        if ($instagram->checkStatus($igContainerId) === false) {
            flash()->error('Error', 'There was an error posting carousel to Instagram.  Please try again.');
            Log::info('Error checking status');
            return back();
        }

        // publish the carousel
        $result = $instagram->publishMedia($igContainerId);
        if ($result === false) {
            flash()->error('Error', 'There was an error posting to Instagram.  Please try again.');
            Log::info('Error publishing media');
            return back();
        }

        // log the share to event_shares table for each event in the week post
        foreach ($events as $event) {
            $this->logEventShare($event, $result, $this->user?->id);
        }

        // post was successful
        flash()->success('Success', 'Successfully published to Instagram, returned id: '.$result);

        return back();
    }


    /**
     * Queue the weekend preview to be posted to Instagram Stories.
     * Event selection (top weekend events by attending count) happens inside
     * the queued job. Only accessible by admins.
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
