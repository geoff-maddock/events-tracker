<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Auth;
use App\Filters\EventFilters;
use App\Http\Controllers\Controller;
use App\Jobs\Instagram\PostEventStoryToInstagram;
use App\Jobs\Instagram\PostEventToInstagram;
use App\Models\Activity;
use App\Models\Event;
use App\Models\EventShare;
use App\Models\Visibility;
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

        $img = $imageHandler->generateCoverImage();
 
        return response()->download($img->basePath());
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
        $coverImage = $imageHandler->generateCoverImage($coverFileName);

        if (!$coverImage) {
            flash()->error('Error', 'You must have a base image to extract the image to make a week post to Instagram');

            return back();
        }

        // save the file in Storage
        $coverPath = Storage::disk('external')->putFileAs('photos', new HttpFile($coverImage->basePath()), $coverFileName, 'public');
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
     * Endpoint to post a weekend preview to Instagram Stories.
     * Selects the top events for the upcoming weekend (Fri–Sun), ranked by follower count.
     * Only accessible by admins.
     *
     * Selection rules:
     *  - If <= 10 weekend events: post all of them.
     *  - If > 10: rank by follower count descending and take top 10.
     *  - If the 10th and 11th events are tied in follower count (can't distinguish): fall back to
     *    5 from Friday + 5 from Saturday, each sorted by follower count.
     *
     * Each selected event is posted as an individual Instagram Story.
     */
    public function postWeekendPreviewToInstagram(Instagram $instagram): RedirectResponse
    {
        // Admin-only guard
        if (!$this->user || !$this->user->hasGroup('super_admin')) {
            flash()->error('Error', 'You must be an admin to post the weekend preview to Instagram.');

            return back();
        }

        // Determine the upcoming weekend window (Friday 00:00 through Sunday 23:59)
        $today = Carbon::today();
        $dayOfWeek = $today->dayOfWeek; // 0 = Sunday, 5 = Friday, 6 = Saturday

        if ($dayOfWeek === Carbon::FRIDAY) {
            $fridayStart = $today->copy()->startOfDay();
        } elseif ($dayOfWeek === Carbon::SATURDAY) {
            $fridayStart = $today->copy()->previous(Carbon::FRIDAY)->startOfDay();
        } elseif ($dayOfWeek === Carbon::SUNDAY) {
            $fridayStart = $today->copy()->previous(Carbon::FRIDAY)->startOfDay();
        } else {
            // Mon–Thu: look to the coming Friday
            $fridayStart = $today->copy()->next(Carbon::FRIDAY)->startOfDay();
        }

        $sundayEnd = $fridayStart->copy()->next(Carbon::SUNDAY)->endOfDay();

        // Fetch all weekend events ranked by number of attending responses (EventResponse count)
        // Exclude cancelled events and non-public events
        $allWeekendEvents = Event::where('start_at', '>=', $fridayStart)
            ->where('start_at', '<=', $sundayEnd)
            ->where('visibility_id', '=', Visibility::VISIBILITY_PUBLIC)
            ->whereNull('cancelled_at')
            ->withCount(['eventResponses as response_count'])
            ->orderBy('response_count', 'desc')
            ->orderBy('start_at', 'asc')
            ->get();

        if ($allWeekendEvents->isEmpty()) {
            flash()->error('Error', 'No events found for the upcoming weekend.');

            return back();
        }

        // Apply selection rules
        if ($allWeekendEvents->count() <= 10) {
            $selectedEvents = $allWeekendEvents;
        } else {
            // Check if there is a clear attending-count cutoff between position 10 and 11.
            // If the 10th and 11th events have different response counts we can take a clean top 10.
            // When the counts are equal (tied), fall back to the day-based distribution.
            $tenth = $allWeekendEvents->get(9);
            $eleventh = $allWeekendEvents->get(10);

            if ($tenth && $eleventh && $tenth->response_count !== $eleventh->response_count) {
                // Clear cutoff at position 10 — take top 10 by attending count
                $selectedEvents = $allWeekendEvents->take(10);
            } else {
                // Tie at the cutoff — fall back to 5 Fri + 5 Sat (per issue spec; Sunday excluded)
                $fridayEvents = $allWeekendEvents
                    ->filter(fn ($e) => Carbon::parse($e->start_at)->isFriday())
                    ->take(5);
                $saturdayEvents = $allWeekendEvents
                    ->filter(fn ($e) => Carbon::parse($e->start_at)->isSaturday())
                    ->take(5);
                $selectedEvents = $fridayEvents->merge($saturdayEvents);
            }
        }

        if ($selectedEvents->isEmpty()) {
            flash()->error('Error', 'No events selected for the weekend preview.');

            return back();
        }

        // Validate Instagram credentials
        if (!$instagram->getIgUserId()) {
            flash()->error('Error', 'You must have an Instagram user account linked to post to Instagram.');

            return back();
        }

        if (!$instagram->getPageAccessToken()) {
            flash()->error('Error', 'You must have an Instagram page linked to post to Instagram.');

            return back();
        }

        $postedCount = 0;
        $skippedCount = 0;

        foreach ($selectedEvents as $event) {
            $photo = $event->getPrimaryPhoto();

            if (!$photo) {
                Log::info('Weekend preview: no photo for event '.$event->id.', skipping.');
                $skippedCount++;
                continue;
            }

            $imageUrl = Storage::disk('external')->url($photo->getStoragePath());

            if (!$imageUrl) {
                Log::info('Weekend preview: no image url for event '.$event->id.', skipping.');
                $skippedCount++;
                continue;
            }

            $caption = urlEncode($event->getInstagramFormat());
            $eventUrl = route('events.show', $event->id);

            try {
                $igContainerId = $instagram->uploadStoryPhoto($imageUrl, $caption, $eventUrl);
            } catch (Exception $e) {
                Log::info('Weekend preview: error uploading story for event '.$event->id.': '.$e->getMessage());
                $skippedCount++;
                continue;
            }

            if ($instagram->checkStatus($igContainerId) === false) {
                Log::info('Weekend preview: container status check failed for event '.$event->id);
                $skippedCount++;
                continue;
            }

            $result = $instagram->publishStoryMedia($igContainerId);

            if ($result === false) {
                Log::info('Weekend preview: failed to publish story for event '.$event->id);
                $skippedCount++;
                continue;
            }

            Log::info('Weekend preview: published story for event '.$event->id.', ig id: '.$result);

            Activity::log($event, $this->user, 16);
            $this->logEventShare($event, $result, $this->user?->id);
            $postedCount++;
        }

        if ($postedCount === 0) {
            flash()->error('Error', 'No stories could be posted. Ensure the selected events have photos.');

            return back();
        }

        flash()->success(
            'Success',
            "Weekend preview posted: {$postedCount} stor".($postedCount === 1 ? 'y' : 'ies').' published'
                .($skippedCount > 0 ? ", {$skippedCount} skipped (no photo)." : '.')
        );

        return back();
    }


}
