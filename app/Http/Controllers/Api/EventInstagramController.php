<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Auth;
use App\Filters\EventFilters;
use App\Http\Controllers\Controller;
use App\Jobs\Instagram\PostEventStoryToInstagram;
use App\Jobs\Instagram\PostEventToInstagram;
use App\Jobs\Instagram\PostWeekendPreviewToInstagram;
use App\Models\Event;
use App\Models\EventShare;
use App\Services\Integrations\Instagram;
use App\Services\ImageHandler;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\File as HttpFile;
use Storage;

use Symfony\Component\HttpFoundation\BinaryFileResponse;


class EventInstagramController extends Controller
{
    protected string $prefix;

    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected array $defaultSortCriteria;

    protected int $limit;

    protected string $sort;

    protected string $sortDirection;

    protected int $defaultGridLimit;

    protected int $gridLimit;

    // this should be an array of filter values
    protected array $filters;

    // this is the class specifying the filters methods for each field
    protected EventFilters $filter;

    protected bool $hasFilter;

    protected int $defaultWindow;

    public function __construct(EventFilters $filter)
    {
        $this->middleware('verified', ['only' => ['create', 'edit', 'duplicate','store', 'update', 'indexAttending']]);

        $this->filter = $filter;

        // prefix for session storage
        $this->prefix = 'app.events.';

        // default list variables
        $this->defaultLimit = 10;
        $this->defaultGridLimit = 24;
        $this->defaultSort = 'start_at';
        $this->defaultSortDirection = 'desc';
        $this->defaultWindow = 4;

        $this->limit = $this->defaultLimit;
        $this->sort = $this->defaultSort;
        $this->sortDirection = $this->defaultSortDirection;
        $this->gridLimit = 24;

        $this->defaultSortCriteria = ['events.start_at' => 'desc'];

        $this->hasFilter = false;
        parent::__construct();
    }


    /**
     * Use code to generate an image
     *
     * @param int $id
     */
    public function generateImage($id, ImageHandler $imageHandler): BinaryFileResponse
    {
        $event = Event::findOrFail($id);

        return response()->download($imageHandler->generateCoverImage());
    }


    /**
     * Curl API call.
     */
    private function makeApiCall(string $endpoint, string $type, array $params): array
    {
        $ch = curl_init();

        // create endpoint with params
        if (empty($params)) {
            $apiEndpoint = $endpoint;
        } else {
            $apiEndpoint = $endpoint.'?'.http_build_query($params);
        }

        // set other curl options
        curl_setopt($ch, CURLOPT_URL, $apiEndpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // set values based on type
        if ($type == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        } elseif ($type == 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        } elseif ($type == 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        // get response
        $response = curl_exec($ch);

        curl_close($ch);

        return [
            'type' => $type,
            'endpoint' => $endpoint,
            'params' => $params,
            'api_endpoint' => $apiEndpoint,
            'data' => json_decode($response, true),
        ];
    }

    /**
     * Queue a single event photo to be posted to Instagram.
     */
    public function postToInstagram(int $id, Instagram $instagram): RedirectResponse
    {
        if (!$event = Event::find($id)) {
            flash()->error('Error', 'No such event');

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

        if ($error = $this->instagramCredentialError($instagram)) {
            return $this->instagramActionResponse(false, 'Error', $error);
        }

        if ($error = $this->eventPhotoError($event)) {
            return $this->instagramActionResponse(false, 'Error', $error);
        }

        PostEventToInstagram::dispatch($event, true, $this->user?->id);

        return $this->instagramActionResponse(true, 'Queued', 'This event is being posted to Instagram in the background. You will be notified when it finishes.');
    }

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
        if ($event->visibility_id !== \App\Models\Visibility::VISIBILITY_PUBLIC) {
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

        // Build the job so we can hand its tracking id back to the caller, then dispatch.
        $job = new PostEventToInstagram($event, true, $user->id);
        dispatch($job);

        return response()->json([
            'success' => true,
            'queued' => true,
            'job_status_id' => $job->jobStatusId,
        ]);
    }

    /**
     * Quick pre-flight check so callers fail fast when Instagram is not linked.
     * Returns a user-facing error string, or null when credentials are present.
     */
    private function instagramCredentialError(Instagram $instagram): ?string
    {
        if (!$instagram->getIgUserId()) {
            return 'You must have an Instagram user account linked to post to Instagram.';
        }

        if (!$instagram->getPageAccessToken()) {
            return 'You must have an Instagram page linked to post to Instagram.';
        }

        return null;
    }

    /**
     * Instagram posts are built from the event's primary photo. Fail fast at
     * dispatch time when it is missing so the caller gets an immediate,
     * actionable message, instead of queuing a job that throws "You must have
     * a photo…" and burns all three retries before giving up (EVENTREPO-VA).
     */
    private function eventPhotoError(Event $event): ?string
    {
        if (!$event->getPrimaryPhoto()) {
            return 'This event must have a primary photo before it can be posted to Instagram.';
        }

        return null;
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


}
