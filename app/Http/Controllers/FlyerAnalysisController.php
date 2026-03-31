<?php

namespace App\Http\Controllers;

use App\Services\FlyerAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FlyerAnalysisController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Analyse an uploaded event flyer image using an LLM and return
     * extracted event data as JSON so the create form can be pre-filled.
     */
    public function analyze(Request $request, FlyerAnalysisService $service): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'max:10240', 'mimes:jpeg,png,gif,webp'],
        ]);

        try {
            $data = $service->analyze($request->file('image'));

            return response()->json([
                'success' => true,
                'data' => $data,
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
