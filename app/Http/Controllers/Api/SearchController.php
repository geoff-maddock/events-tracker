<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    public function __construct(private readonly SearchService $searchService)
    {
    }

    /**
     * Lightweight typeahead endpoint. Public — visibility scoping is applied
     * based on the optionally-authenticated user.
     *
     * GET /api/search?q=keyword&types=events,entities,series,tags&limit=6
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q'     => 'nullable|string|max:120',
            'types' => 'nullable|string|max:120',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $keyword = (string) ($validated['q'] ?? '');
        $limit = (int) ($validated['limit'] ?? 6);

        $types = null;
        if (! empty($validated['types'])) {
            $allowed = ['events', 'entities', 'series', 'tags', 'modules'];
            $requested = array_map('trim', explode(',', $validated['types']));
            $types = array_values(array_intersect($allowed, $requested)) ?: null;
        }

        return response()->json($this->searchService->lite($keyword, Auth::user(), $limit, $types));
    }
}
