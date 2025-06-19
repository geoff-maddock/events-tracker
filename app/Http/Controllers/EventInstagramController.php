<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Event;
use App\Services\ImageHandler;
use App\Services\Integrations\Instagram;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\File as HttpFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EventInstagramController extends Controller
{
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
