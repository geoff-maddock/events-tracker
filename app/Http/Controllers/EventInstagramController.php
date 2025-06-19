<?php

namespace App\Http\Controllers;

use App\Events\EventCreated;
use App\Events\EventUpdated;
use App\Filters\EventFilters;
use App\Http\Requests\EventRequest;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Mail\FollowingUpdate;
use App\Models\Activity;
use App\Models\Entity;
use App\Models\Event;
use App\Models\EventResponse;
use App\Models\EventReview;
use App\Models\EventType;
use App\Models\Follow;
use App\Models\OccurrenceDay;
use App\Models\OccurrenceType;
use App\Models\OccurrenceWeek;
use App\Models\ResponseType;
use App\Models\Series;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use App\Models\Visibility;
use App\Notifications\EventPublished;
use App\Services\Embeds\EmbedExtractor;
use App\Services\Integrations\Instagram;
use App\Services\ImageHandler;
use App\Services\RssFeed;
use App\Services\SessionStore\ListParameterSessionStore;
use App\Services\StringHelper;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\File as HttpFile;
use Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Services\Calendar\ICalBuilder;


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
        $result = $instagram->publishMedia($igCarouselId);
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
