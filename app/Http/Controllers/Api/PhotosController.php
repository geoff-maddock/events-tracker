<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use Illuminate\Http\JsonResponse;

class PhotosController extends Controller
{
    /**
     * Set the photo as primary.
     */
    public function setPrimary(Photo $photo): JsonResponse
    {
        if (!$this->user || $photo->created_by !== $this->user->id) {
            return response()->json(['status' => 'Permission Denied'], 403);
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
