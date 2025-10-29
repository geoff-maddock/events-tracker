<?php

namespace App\Http\Controllers\Api;

use App\Filters\EventFilters;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Event;
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
     * Endpoint to post a single event to Instagram.
     */
    public function postToInstagram(int $id, Instagram $instagram): RedirectResponse
    {
        // load the event
        if (!$event = Event::find($id)) {
            flash()->error('Error', 'No such event');

            return back();
        }

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

        // get the image URL
        $photo = $event->getPrimaryPhoto();

        if (!$photo) {
            flash()->error('Error', 'You must have an photo to extract the image to post to Instagram');

            return back();
        }

        $imageUrl = Storage::disk('external')->url($photo->getStoragePath());

        if (!$imageUrl) {
            flash()->error('Error', 'You must have an image url to post to Instagram');

            return back();
        }

        // get the instagram caption
        $caption = urlEncode($event->getInstagramFormat());

        if (!$caption) {
            flash()->error('Error', 'You must have an Instagram caption linked to post to Instagram.');

            return back();
        }

        // make the instagram api calls

        // upload the image
        try {
            $igContainerId = $instagram->uploadPhoto($imageUrl, $caption);
        } catch (Exception $e) {
            flash()->error('Error', 'There was an error posting to Instagram.  Please try again.');

            return back();
        }

        // check the container status every 5 seconds until status_code is FINISHED
        if ($instagram->checkStatus($igContainerId) === false) {
            flash()->error('Error', 'There was an error posting to Instagram.  Please try again.');

            return back();
        }

        // pubish the image
        $result = $instagram->publishMedia($igContainerId);
        if ($result === false) {
            flash()->error('Error', 'There was an error posting to Instagram.  Please try again.');

            return back();
        }

        // log the post to instagram
        Activity::log($event, $this->user, 16);

        // post was successful
        flash()->success('Success', 'Successfully published to Instagram, returned id: '.$result);

        return back();
    }


        
    /**
     * Endpoint to post a single event as a carousel to Instagram.
     */
    public function postCarouselToInstagram(int $id, Instagram $instagram): RedirectResponse
    {
        // load the event
        if (!$event = Event::find($id)) {
            flash()->error('Error', 'No such event');

            return back();
        }

        $errorMessage = '';
        $result = $this->publishCarousel($event, $instagram, $errorMessage);

        if ($result === null) {
            flash()->error('Error', $errorMessage);

            return back();
        }

        Activity::log($event, $this->user, 16);

        flash()->success('Success', 'Successfully published to Instagram, returned id: '.$result);

        return back();
    }

    public function postCarouselToInstagramApi(int $id, Instagram $instagram, Request $request): JsonResponse
    {
        if (!$event = Event::find($id)) {
            return response()->json(['success' => false, 'message' => 'No such event'], 404);
        }

        // Authorization checks
        $user = $this->user;

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

        $errorMessage = '';
        $result = $this->publishCarousel($event, $instagram, $errorMessage);

        if ($result === null) {
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], 400);
        }

        Activity::log($event, $request->user(), 16);

        return response()->json([
            'success' => true,
            'id' => $result,
        ]);
    }

    private function publishCarousel(Event $event, Instagram $instagram, string &$errorMessage): ?int
    {
        // get the instagram account
        if (!$instagram->getIgUserId()) {
            $errorMessage = 'You must have an Instagram user account linked to post to Instagram.';

            return null;
        }

        // get the instagram page access token
        if (!$instagram->getPageAccessToken()) {
            $errorMessage = 'You must have an Instagram page linked to post to Instagram.';

            return null;
        }

        // get the image URL
        $photo = $event->getPrimaryPhoto();

        if (!$photo) {
            $errorMessage = 'You must have an photo to extract the image to post to Instagram';

            return null;
        }

        $imageUrl = Storage::disk('external')->url($photo->getStoragePath());

        if (!$imageUrl) {
            $errorMessage = 'You must have an image url to post to Instagram';

            return null;
        }

        // get the instagram caption
        $caption = $event->getInstagramFormat();

        // make the instagram api calls
        $igContainerIds = [];

        // upload the image
        try {
            $id = $instagram->uploadCarouselPhoto($imageUrl);
            $igContainerIds[] = $id;
        } catch (Exception $e) {
            Log::info('Carousel photo error: '. $e->getMessage());
            $errorMessage = 'There was an error posting to Instagram.  Please try again.';

            return null;
        }

        Log::info('Carousel photo uploaded: '.$id);

        // add other photos related to the event to the carousel
        foreach ($event->getOtherPhotos() as $otherPhotos) {
            $imageUrl = Storage::disk('external')->url($otherPhotos->getStoragePath());

            if (!$imageUrl) {
                Log::info('No image url found for event: '.$event->id);
                continue;
            }

            try {
                $igContainerId = $instagram->uploadCarouselPhoto($imageUrl);
            } catch (Exception $e) {
                Log::info('Carousel photo error: '. $e->getMessage());
                $errorMessage = 'There was an error posting to Instagram.  Please try again.';

                return null;
            }

            // check the container status every 5 seconds until status_code is FINISHED
            if ($instagram->checkStatus($igContainerId) === false) {
                Log::info('Error checking status of carousel photo');
                $errorMessage = 'There was an error posting to Instagram.  Please try again.';

                return null;
            }

            $igContainerIds[] = $igContainerId;
            Log::info('Added container id: '.$igContainerId);

            Log::info('Carousel photo uploaded: '.$id);
        }

        // only do this if there are any other related photos
        foreach ($event->entities as $entity) {
            foreach ($entity->photos as $photo) {
                if ($photo->is_primary) {
                    // process the photo
                    $imageUrl = Storage::disk('external')->url($photo->getStoragePath());

                    if (!$imageUrl) {
                        $errorMessage = 'You must have an image url to post to Instagram';

                        return null;
                    }

                    // make the instagram api calls
                    // upload the image
                    try {
                        $igContainerId = $instagram->uploadCarouselPhoto($imageUrl);
                    } catch (Exception $e) {
                        Log::info('Error uploading carousel photo');
                        $errorMessage = 'There was an error posting to Instagram.  Please try again.';

                        return null;
                    }

                    // check the container status every 5 seconds until status_code is FINISHED
                    if ($instagram->checkStatus($igContainerId) === false) {
                        Log::info('Error checking status of carousel photo');
                        $errorMessage = 'There was an error posting to Instagram.  Please try again.';

                        return null;
                    }

                    $igContainerIds[] = $igContainerId;
                    Log::info('Added container id: '.$igContainerId);
                }
            }
        }

        // create a carousel container
        try {
            $igCarouselId = $instagram->createCarousel($igContainerIds, $caption);
        } catch (Exception $e) {
            Log::info('Error creating carousel');
            $errorMessage = 'There was an error posting carousel to Instagram.  Please try again.';

            return null;
        }

        // check the container status every 5 seconds until status_code is FINISHED
        if ($instagram->checkStatus($igCarouselId) === false) {
            $errorMessage = 'There was an error posting to Instagram.  Please try again.';

            return null;
        }

        // pubish the image
        $result = $instagram->publishMedia($igCarouselId);
        if ($result === false) {
            $errorMessage = 'There was an error posting to Instagram.  Please try again.';

            return null;
        }
        Log::info('Carousel published: '.$igCarouselId);

        return $result;
    }


    /**
     * Endpoint to post an event as a STORY to Instagram.
     */
    public function postStoryToInstagram(int $id, Instagram $instagram): RedirectResponse
    {
        // load the event
        if (!$event = Event::find($id)) {
            flash()->error('Error', 'No such event');

            return back();
        }

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

        // get the image URL
        $photo = $event->getPrimaryPhoto();

        if (!$photo) {
            flash()->error('Error', 'You must have an photo to extract the image to post to Instagram');

            return back();
        }

        $imageUrl = Storage::disk('external')->url($photo->getStoragePath());

        if (!$imageUrl) {
            flash()->error('Error', 'You must have an image url to post to Instagram');

            return back();
        }

        // get the instagram caption
        $caption = urlEncode($event->getInstagramFormat());
        $caption = $event->getInstagramFormat();

        // make the instagram api calls
        $igContainerIds = [];

        // upload the image
        try {
            $id = $instagram->uploadCarouselPhoto($imageUrl);
            $igContainerIds[] = $id;
        } catch (Exception $e) {
            flash()->error('Error', 'There was an error posting to Instagram.  Please try again.');
            Log::info('Carousel photo error: '. $e->getMessage());
            return back();
        }

        Log::info('Carousel photo uploaded: '.$id);

        // add other photos related to the event to the carousel
        foreach ($event->getOtherPhotos() as $otherPhotos) {
            $imageUrl = Storage::disk('external')->url($otherPhotos->getStoragePath());

            if (!$imageUrl) {
                flash()->error('Error', 'You must have an image url to post to Instagram');
                Log::info('No image url found for event: '.$event->id);
                continue;
            }

            try {
                $igContainerId = $instagram->uploadCarouselPhoto($imageUrl);
            } catch (Exception $e) {
                flash()->error('Error', 'There was an error posting to Instagram.  Please try again.');
                Log::info('Carousel photo error: '. $e->getMessage());
                return back();
            }

            // check the container status every 5 seconds until status_code is FINISHED
            if ($instagram->checkStatus($igContainerId) === false) {
                flash()->error('Error', 'There was an error posting to Instagram.  Please try again.');
                Log::info('Error checking status of carousel photo');
                return back();
            }

            $igContainerIds[] = $igContainerId;
            Log::info('Added container id: '.$igContainerId);

            Log::info('Carousel photo uploaded: '.$id);
        }

        // only do this if there are any other related photos
        foreach ($event->entities as $entity) {
            foreach ($entity->photos as $photo) {
                if ($photo->is_primary) {
                    // process the photo
                    $imageUrl = Storage::disk('external')->url($photo->getStoragePath());
                    $images[] = $imageUrl;

                    if (!$imageUrl) {
                        flash()->error('Error', 'You must have an image url to post to Instagram');
            
                        return back();
                    }

                    // make the instagram api calls
                    // upload the image
                    try {
                        $igContainerId = $instagram->uploadCarouselPhoto($imageUrl);
                    } catch (Exception $e) {
                        flash()->error('Error', 'There was an error posting to Instagram.  Please try again.');
                        Log::info('Error uploading carousel photo');
                        return back();
                    }

                    // check the container status every 5 seconds until status_code is FINISHED
                    if ($instagram->checkStatus($igContainerId) === false) {
                        flash()->error('Error', 'There was an error posting to Instagram.  Please try again.');
                        Log::info('Error checking status of carousel photo');
                        return back();
                    }

                    $igContainerIds[] = $igContainerId;
                    Log::info('Added container id: '.$igContainerId);
                }
            }
        }

        // create a carousel container
        try {
            $igCarouselId = $instagram->createCarousel($igContainerIds, $caption);
        } catch (Exception $e) {
            flash()->error('Error', 'There was an error posting carousel to Instagram.  Please try again.');
            Log::info('Error creating carousel');
            return back();
        }

        // check the container status every 5 seconds until status_code is FINISHED
        if ($instagram->checkStatus($igCarouselId) === false) {
            flash()->error('Error', 'There was an error posting to Instagram.  Please try again.');

            return back();
        }

        // pubish the image
        $result = $instagram->publishStoryMedia($igCarouselId);
        if ($result === false) {
            flash()->error('Error', 'There was an error posting to Instagram.  Please try again.');

            return back();
        }
        Log::info('Carousel published: '.$igCarouselId);

        // log the post to instagram
        Activity::log($event, $this->user, 16);

        // post was successful
        flash()->success('Success', 'Successfully published to Instagram, returned id: '.$result);

        return back();
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

        // get info from the events
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
            } catch (Exception $e) {
                flash()->error('Error', 'There was an error posting to Instagram.  Please try again.');
                Log::info('Error uploading carousel photo');
                return back();
            }

            // check the container status every 5 seconds until status_code is FINISHED
            if ($instagram->checkStatus($igContainerId) === false) {
                flash()->error('Error', 'There was an error posting to Instagram.  Please try again.');
                Log::info('Error checking status of carousel photo');
                return back();
            }

            $igContainerIds[] = $igContainerId;
            Log::info('Added container id: '.$igContainerId);
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

        // check the container status every 5 seconds until status_code is FINISHED
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

        // post was successful
        flash()->success('Success', 'Successfully published to Instagram, returned id: '.$result);

        return back();
    }



}
