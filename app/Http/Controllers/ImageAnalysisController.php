<?php

namespace App\Http\Controllers;

use App\Services\ImageAnalysisService;
use App\Services\TempImageStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ImageAnalysisController extends Controller
{
    public function __construct(private readonly TempImageStore $tempImages)
    {
        // Note: `auth`, not `verified`. EventsController and SeriesController
        // gate create on `verified` but EntitiesController gates on `auth`, so
        // requiring verification here would leave users able to reach entity
        // create but unable to use the panel on it. Throttling covers abuse.
        $this->middleware('auth');
    }

    /**
     * Stash an uploaded image without analysing it.
     *
     * This is what makes the image attach even when the user never presses
     * Analyze: the file is held server-side as soon as it is chosen, and the
     * create form carries only the returned token.
     */
    public function stash(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'max:10240', 'mimes:jpeg,png,gif,webp'],
            'image_temp_token' => ['nullable', 'string', 'max:64'],
        ]);

        try {
            $token = $this->tempImages->stash(
                $request->file('image'),
                $request->input('image_temp_token')
            );

            return response()->json($this->tokenPayload(['success' => true], $token));
        } catch (\Throwable $e) {
            Log::error('ImageAnalysisController: failed to stash image', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'The image could not be uploaded. Please try again.',
            ], 500);
        }
    }

    /**
     * Analyse an uploaded image using an LLM and return the extracted data as
     * JSON so a create form can be pre-filled.
     *
     * The image is stashed as well, so it can be attached as the record's
     * photo on save without the user uploading it a second time.
     */
    public function analyze(Request $request, ImageAnalysisService $service): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'max:10240', 'mimes:jpeg,png,gif,webp'],
            'context' => ['nullable', Rule::in(ImageAnalysisService::CONTEXTS)],
            'image_temp_token' => ['nullable', 'string', 'max:64'],
        ]);

        $context = $request->input('context', ImageAnalysisService::DEFAULT_CONTEXT);

        try {
            $imageFile = $request->file('image');

            $data = $service->analyze($imageFile, $context);

            $token = $this->tempImages->stash($imageFile, $request->input('image_temp_token'));

            return response()->json($this->tokenPayload([
                'success' => true,
                'data' => $data,
            ], $token));
        } catch (\RuntimeException $e) {
            Log::warning('ImageAnalysisController: analysis failed', [
                'context' => $context,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('ImageAnalysisController: unexpected error', [
                'context' => $context,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while analysing the image.',
            ], 500);
        }
    }

    /**
     * Both the current and the legacy token field are returned so that a page
     * loaded before this shipped keeps working until the alias is removed.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function tokenPayload(array $payload, string $token): array
    {
        return $payload + [
            'image_temp_token' => $token,
            'flyer_temp_token' => $token,
        ];
    }
}
