<?php

namespace App\Http\Controllers;

use App\Services\FlyerAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FlyerAnalysisController extends Controller
{
    /** Local storage directory used for temporary flyer copies. */
    public const FLYER_TEMP_DIR = 'flyer_temp';

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Analyse an uploaded event flyer image using an LLM and return
     * extracted event data as JSON so the create form can be pre-filled.
     *
     * The uploaded image is also saved to a temporary location so that it
     * can be automatically attached as the event photo when the event is
     * created.  The temporary token is returned alongside the extracted data.
     */
    public function analyze(Request $request, FlyerAnalysisService $service): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'max:10240', 'mimes:jpeg,png,gif,webp'],
        ]);

        try {
            $imageFile = $request->file('image');
            $data = $service->analyze($imageFile);

            // Use the MIME-validated extension from Laravel (not the client filename)
            // to prevent trusting user-supplied extension values.
            $extension = $imageFile->extension() ?: 'jpg';
            $token = Str::uuid()->toString() . '.' . $extension;
            Storage::disk('local')->putFileAs(self::FLYER_TEMP_DIR, $imageFile, $token);

            return response()->json([
                'success' => true,
                'data' => $data,
                'flyer_temp_token' => $token,
            ]);
        } catch (\RuntimeException $e) {
            Log::warning('FlyerAnalysisController: analysis failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('FlyerAnalysisController: unexpected error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while analysing the flyer.',
            ], 500);
        }
    }
}
