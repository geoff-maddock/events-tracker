<?php

namespace App\Services\Integrations;

use App\Models\Photo;
use Exception;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Storage;

/**
 * The Instagram publishing steps shared by the event and entity posters.
 * Each step throws RuntimeException with a user-facing message on failure.
 */
abstract class InstagramPoster
{
    public function __construct(protected Instagram $instagram)
    {
    }

    protected function failureMessage(string $stage): string
    {
        $detail = $this->instagram->getLastError();
        $base = 'There was an error posting to Instagram during '.$stage.'.';
        if ($detail) {
            Log::error('Instagram '.$stage.' failed: '.$detail);
            return $base.' '.$detail;
        }
        return $base.' Please try again.';
    }

    /**
     * Wrap a thrown SDK/HTTP exception from an upload/create call.
     *
     * These call-sites previously threw a hardcoded "Please try again." message
     * and logged the real cause only at info level, so every distinct Instagram
     * failure (rate limit, rejected image, expired token, …) collapsed into one
     * opaque Sentry issue (EVENTREPO-VB). Surfacing the underlying message and
     * chaining the original exception lets Sentry group by actual cause and
     * preserves the full stack trace for triage.
     */
    protected function uploadFailure(string $stage, Exception $e): RuntimeException
    {
        Log::error('Instagram '.$stage.' failed: '.$e->getMessage());

        return new RuntimeException(
            'There was an error posting to Instagram during '.$stage.'. '.$e->getMessage(),
            0,
            $e
        );
    }

    /**
     * Verify the linked Instagram account is usable before any uploads happen.
     */
    protected function assertCredentials(): void
    {
        if (!$this->instagram->getIgUserId()) {
            throw new RuntimeException('You must have an Instagram user account linked to post to Instagram.');
        }

        if (!$this->instagram->getPageAccessToken()) {
            throw new RuntimeException('You must have an Instagram page linked to post to Instagram.');
        }
    }

    /**
     * The public URL of a (primary) photo, which Instagram fetches the image from.
     */
    protected function photoUrl(?Photo $photo): string
    {
        if (!$photo) {
            throw new RuntimeException('You must have a photo to extract the image to post to Instagram.');
        }

        $imageUrl = Storage::disk('external')->url($photo->getStoragePath());
        if (!$imageUrl) {
            throw new RuntimeException('You must have an image url to post to Instagram.');
        }

        return $imageUrl;
    }

    /**
     * Upload one image as a carousel item and return its container id.
     */
    protected function uploadCarouselItem(string $imageUrl): int
    {
        try {
            return $this->instagram->uploadCarouselPhoto($imageUrl);
        } catch (Exception $e) {
            throw $this->uploadFailure('carousel photo upload', $e);
        }
    }

    /**
     * Upload, wait for and publish a single feed photo. The caption must
     * already be URL-encoded.
     */
    protected function publishSinglePhoto(string $imageUrl, string $caption): int
    {
        try {
            $igContainerId = $this->instagram->uploadPhoto($imageUrl, $caption);
        } catch (Exception $e) {
            throw $this->uploadFailure('single photo upload', $e);
        }

        if ($this->instagram->checkStatus($igContainerId) === false) {
            throw new RuntimeException($this->failureMessage('single photo status check'));
        }

        $result = $this->instagram->publishMedia($igContainerId);
        if ($result === false) {
            throw new RuntimeException($this->failureMessage('single photo publish'));
        }

        return (int) $result;
    }

    /**
     * Wait for uploaded carousel items, then create and publish the carousel.
     *
     * @param array<int, int> $igContainerIds
     */
    protected function publishCarousel(array $igContainerIds, string $caption): int
    {
        if ($this->instagram->checkBatchStatus($igContainerIds) === false) {
            throw new RuntimeException($this->failureMessage('carousel batch status check'));
        }

        try {
            $igCarouselId = $this->instagram->createCarousel($igContainerIds, $caption);
        } catch (Exception $e) {
            throw $this->uploadFailure('carousel creation', $e);
        }

        if ($this->instagram->checkStatus($igCarouselId) === false) {
            throw new RuntimeException($this->failureMessage('carousel status check'));
        }

        $result = $this->instagram->publishMedia($igCarouselId);
        if ($result === false) {
            throw new RuntimeException($this->failureMessage('carousel publish'));
        }

        return (int) $result;
    }

    /**
     * Upload, wait for and publish a story photo. The caption must already be
     * URL-encoded.
     */
    protected function publishStoryPhoto(string $imageUrl, string $caption, ?string $linkUrl = null): int
    {
        try {
            $igContainerId = $this->instagram->uploadStoryPhoto($imageUrl, $caption, $linkUrl);
        } catch (Exception $e) {
            throw $this->uploadFailure('story photo upload', $e);
        }

        if ($this->instagram->checkStatus($igContainerId) === false) {
            throw new RuntimeException($this->failureMessage('story status check'));
        }

        $result = $this->instagram->publishStoryMedia($igContainerId);
        if ($result === false) {
            throw new RuntimeException($this->failureMessage('story publish'));
        }

        return (int) $result;
    }
}
