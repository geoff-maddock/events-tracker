<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use Illuminate\Http\JsonResponse;

class PhotosController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:sanctum')->only(['setPrimary', 'unsetPrimary', 'destroy']);

        parent::__construct();
    }

    /**
     * Set the photo as primary.
     */
    public function setPrimary(Photo $photo): JsonResponse
    {
        if (!$this->user || $photo->created_by !== $this->user->id) {
            return response()->json(['status' => 'Permission Denied'], 403);
        }

        // get anything linked to this photo and unset their primary flag
        // this includes users, entities, events, and series
        $users = $photo->users;

        foreach ($users as $user) {
            foreach ($user->photos as $p) {
                $p->is_primary = 0;
                $p->save();
            }
        }

        $entities = $photo->entities;
        foreach ($entities as $entity) {
            foreach ($entity->photos as $p) {
                $p->is_primary = 0;
                $p->save();
            }
        }

        $events = $photo->events;
        foreach ($events as $event) {
            foreach ($event->photos as $p) {
                $p->is_primary = 0;
                $p->save();
            }
        }

        $series = $photo->series;
        foreach ($series as $s) {
            foreach ($s->photos as $p) {
                $p->is_primary = 0;
                $p->save();
            }
        }

        $photo->is_primary = 1;
        $photo->save();

        return response()->json($photo);
    }

    /**
     * Unset the primary flag for the photo.
     */
    public function unsetPrimary(Photo $photo): JsonResponse
    {
        if (!$this->user || $photo->created_by !== $this->user->id) {
            return response()->json(['status' => 'Permission Denied'], 403);
        }

        $photo->is_primary = 0;
        $photo->save();

        return response()->json($photo);
    }

    /**
     * Delete the photo record and file.
     */
    public function destroy(Photo $photo): JsonResponse
    {
        if (!$this->user || $photo->created_by !== $this->user->id) {
            return response()->json(['status' => 'Permission Denied'], 403);
        }

        $photo->delete();

        return response()->json([], 204);
    }
}
