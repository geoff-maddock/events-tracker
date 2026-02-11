<?php

namespace App\Services;

use App\Models\User;
use App\Http\Resources\EventResource;
use App\Http\Resources\SeriesResource;
use App\Http\Resources\EntityResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\CommentResource;
use App\Http\Resources\PhotoResource;
use App\Http\Resources\BlogResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class DataExportService
{
    /**
     * Generate a complete data export for a user
     *
     * @param User $user
     * @return array Returns array with 'zipPath' and 'filename'
     */
    public function generateExport(User $user): array
    {
        $exportData = $this->aggregateUserData($user);
        
        // Create a unique directory for this export
        $exportId = uniqid('export_' . $user->id . '_', true);
        $exportDir = storage_path('app/exports/' . $exportId);
        
        if (!file_exists($exportDir)) {
            mkdir($exportDir, 0755, true);
        }
        
        // Write JSON files
        $this->writeJsonFile($exportDir . '/events.json', $exportData['events']);
        $this->writeJsonFile($exportDir . '/series.json', $exportData['series']);
        $this->writeJsonFile($exportDir . '/entities.json', $exportData['entities']);
        $this->writeJsonFile($exportDir . '/posts.json', $exportData['posts']);
        $this->writeJsonFile($exportDir . '/comments.json', $exportData['comments']);
        $this->writeJsonFile($exportDir . '/blogs.json', $exportData['blogs']);
        $this->writeJsonFile($exportDir . '/follows.json', $exportData['follows']);
        $this->writeJsonFile($exportDir . '/event_responses.json', $exportData['event_responses']);
        $this->writeJsonFile($exportDir . '/profile.json', $exportData['profile']);
        $this->writeJsonFile($exportDir . '/photos_metadata.json', $exportData['photos']);
        $this->writeJsonFile($exportDir . '/constants.json', $exportData['constants']);
        
        // Download and save photos
        $photosDir = $exportDir . '/photos';
        if (!empty($exportData['photo_files'])) {
            if (!file_exists($photosDir)) {
                mkdir($photosDir, 0755, true);
            }
            $this->downloadPhotos($exportData['photo_files'], $photosDir);
        }
        
        // Create ZIP file
        $zipFilename = 'user_data_export_' . uniqid('', true) . '.zip';
        $zipPath = storage_path('app/exports/' . $zipFilename);
        
        $this->createZipArchive($exportDir, $zipPath);
        
        // Clean up temporary directory
        $this->deleteDirectory($exportDir);
        
        return [
            'zipPath' => $zipPath,
            'filename' => $zipFilename,
        ];
    }
    
    /**
     * Aggregate all user data into arrays
     *
     * @param User $user
     * @return array
     */
    protected function aggregateUserData(User $user): array
    {
        // Events created by user
        $events = $user->events()->with(['eventType', 'eventStatus', 'visibility', 'venue', 'entities', 'tags', 'series'])->get();
        $eventsData = EventResource::collection($events)->resolve();
        
        // Series created by user
        $series = $user->series()->with(['occurrenceType', 'venue', 'entities', 'tags'])->get();
        $seriesData = SeriesResource::collection($series)->resolve();
        
        // Entities created by user (need to check if there's a created_by field)
        $entities = \App\Models\Entity::where('created_by', $user->id)
            ->with(['entityType', 'entityStatus', 'tags', 'roles', 'aliases'])
            ->get();
        $entitiesData = EntityResource::collection($entities)->resolve();
        
        // Posts created by user
        $posts = \App\Models\Post::where('created_by', $user->id)
            ->with(['thread', 'user'])
            ->get();
        $postsData = PostResource::collection($posts)->resolve();
        
        // Comments created by user
        $comments = $user->comments()->with(['commentable'])->get();
        $commentsData = CommentResource::collection($comments)->resolve();
        
        // Blogs created by user (if exists)
        $blogs = [];
        if (class_exists('\App\Models\Blog')) {
            $blogsQuery = \App\Models\Blog::where('created_by', $user->id)->get();
            if (class_exists('\App\Http\Resources\BlogResource')) {
                $blogs = BlogResource::collection($blogsQuery)->resolve();
            }
        }
        
        // User follows (entities, tags, series, threads)
        $follows = [
            'entities' => $user->followedEntities()->get()->map(function ($entity) {
                return [
                    'id' => $entity->id,
                    'name' => $entity->name,
                    'slug' => $entity->slug,
                    'type' => $entity->entityType ? $entity->entityType->name : null,
                ];
            }),
            'tags' => $user->followedTags()->get()->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                ];
            }),
            'series' => $user->followedSeries()->get()->map(function ($series) {
                return [
                    'id' => $series->id,
                    'name' => $series->name,
                    'slug' => $series->slug,
                ];
            }),
            'threads' => $user->followedThreads()->get()->map(function ($thread) {
                return [
                    'id' => $thread->id,
                    'name' => $thread->name,
                    'slug' => $thread->slug ?? null,
                ];
            }),
        ];
        
        // Event responses (attending, interested, etc.)
        $eventResponses = $user->eventResponses()->with(['event', 'responseType'])->get()->map(function ($response) {
            return [
                'event_id' => $response->event_id,
                'event_name' => $response->event ? $response->event->name : null,
                'response_type' => $response->responseType ? $response->responseType->name : null,
                'created_at' => $response->created_at,
            ];
        });
        
        // User profile
        $profile = [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'full_name' => $user->full_name,
            'created_at' => $user->created_at,
            'profile' => $user->profile ? [
                'first_name' => $user->profile->first_name,
                'last_name' => $user->profile->last_name,
                'bio' => $user->profile->bio,
                'location' => $user->profile->location,
                'setting_weekly_update' => $user->profile->setting_weekly_update,
                'setting_daily_update' => $user->profile->setting_daily_update,
                'setting_instant_update' => $user->profile->setting_instant_update,
                'setting_forum_update' => $user->profile->setting_forum_update,
                'setting_public_profile' => $user->profile->setting_public_profile,
            ] : null,
        ];
        
        // Photos associated with user
        $photos = $user->photos()->get();
        $photosData = PhotoResource::collection($photos)->resolve();
        
        // Photo files to download
        $photoFiles = $photos->map(function ($photo) {
            return [
                'id' => $photo->id,
                'name' => $photo->name,
                'path' => $photo->path,
                'thumbnail' => $photo->thumbnail,
            ];
        })->toArray();
        
        // Constants/lookup data
        $constants = [
            'event_types' => \App\Models\EventType::all()->map(fn($et) => ['id' => $et->id, 'name' => $et->name]),
            'event_statuses' => \App\Models\EventStatus::all()->map(fn($es) => ['id' => $es->id, 'name' => $es->name]),
            'entity_types' => \App\Models\EntityType::all()->map(fn($et) => ['id' => $et->id, 'name' => $et->name]),
            'visibility_options' => \App\Models\Visibility::all()->map(fn($v) => ['id' => $v->id, 'name' => $v->name]),
            'response_types' => \App\Models\ResponseType::all()->map(fn($rt) => ['id' => $rt->id, 'name' => $rt->name]),
        ];
        
        return [
            'events' => $eventsData,
            'series' => $seriesData,
            'entities' => $entitiesData,
            'posts' => $postsData,
            'comments' => $commentsData,
            'blogs' => $blogs,
            'follows' => $follows,
            'event_responses' => $eventResponses,
            'profile' => $profile,
            'photos' => $photosData,
            'photo_files' => $photoFiles,
            'constants' => $constants,
        ];
    }
    
    /**
     * Write data to JSON file
     *
     * @param string $filepath
     * @param mixed $data
     */
    protected function writeJsonFile(string $filepath, $data): void
    {
        file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    
    /**
     * Download photos from storage to local directory
     *
     * @param array $photoFiles
     * @param string $destinationDir
     */
    protected function downloadPhotos(array $photoFiles, string $destinationDir): void
    {
        foreach ($photoFiles as $photo) {
            if (!empty($photo['path'])) {
                try {
                    // Try to get file from storage
                    if (Storage::exists($photo['path'])) {
                        $contents = Storage::get($photo['path']);
                        $filename = basename($photo['path']);
                        file_put_contents($destinationDir . '/' . $filename, $contents);
                    }
                    
                    // Also try thumbnail if exists
                    if (!empty($photo['thumbnail']) && Storage::exists($photo['thumbnail'])) {
                        $contents = Storage::get($photo['thumbnail']);
                        $filename = basename($photo['thumbnail']);
                        file_put_contents($destinationDir . '/thumb_' . $filename, $contents);
                    }
                } catch (\Exception $e) {
                    // Log error but continue with other photos
                    Log::warning('Failed to download photo: ' . $photo['path'], ['error' => $e->getMessage()]);
                }
            }
        }
    }
    
    /**
     * Create a ZIP archive from directory
     *
     * @param string $sourceDir
     * @param string $zipPath
     */
    protected function createZipArchive(string $sourceDir, string $zipPath): void
    {
        $zip = new ZipArchive();
        
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Cannot create ZIP file');
        }
        
        // Add all files from source directory
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($sourceDir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
        
        $zip->close();
    }
    
    /**
     * Recursively delete a directory
     *
     * @param string $dir
     */
    protected function deleteDirectory(string $dir): void
    {
        if (!file_exists($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        
        rmdir($dir);
    }
    
    /**
     * Get a signed URL for downloading the export file with expiration
     *
     * @param string $filename
     * @return string
     */
    public function getDownloadUrl(string $filename): string
    {
        // Store in public storage temporarily
        $publicPath = 'exports/' . $filename;
        
        // Copy from storage/app/exports to storage/app/public/exports
        if (file_exists(storage_path('app/exports/' . $filename))) {
            if (!file_exists(storage_path('app/public/exports'))) {
                mkdir(storage_path('app/public/exports'), 0755, true);
            }
            copy(
                storage_path('app/exports/' . $filename),
                storage_path('app/public/' . $publicPath)
            );
        }
        
        // Note: Using public URL for now. For production with S3/DigitalOcean Spaces,
        // this should be replaced with Storage::temporaryUrl($publicPath, now()->addDays(7))
        // which will generate a signed URL with automatic expiration
        return url('storage/' . $publicPath);
    }
    
    /**
     * Clean up old export files (called by scheduled task)
     *
     * @param int $daysOld
     */
    public function cleanupOldExports(int $daysOld = 7): void
    {
        $exportDir = storage_path('app/exports');
        $publicExportDir = storage_path('app/public/exports');
        
        $this->cleanupDirectory($exportDir, $daysOld);
        $this->cleanupDirectory($publicExportDir, $daysOld);
    }
    
    /**
     * Clean up files in a directory older than specified days
     *
     * @param string $directory
     * @param int $daysOld
     */
    protected function cleanupDirectory(string $directory, int $daysOld): void
    {
        if (!file_exists($directory)) {
            return;
        }
        
        $files = glob($directory . '/*');
        $now = time();
        
        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= 60 * 60 * 24 * $daysOld) {
                    unlink($file);
                }
            }
        }
    }
}
