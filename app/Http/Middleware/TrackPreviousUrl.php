<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tracks a filtered "previous URL" in the session for use by Back buttons.
 *
 * Create and edit pages are excluded so that Back buttons on show pages
 * never return the user to a form they have already submitted.
 * Revisiting the same URL (e.g. page reload) also does not overwrite the
 * stored value.
 */
class TrackPreviousUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only track full-page GET requests (skip AJAX, API, etc.)
        if ($request->isMethod('GET') && ! $request->ajax()) {
            $currentUrl = $request->fullUrl();

            if (! $this->shouldSkip($request)) {
                $trackedUrl = session('tracked_url');

                // Only advance the pointer when the user navigates to a new page
                if ($trackedUrl !== $currentUrl) {
                    if ($trackedUrl !== null) {
                        session(['previous_url' => $trackedUrl]);
                    }
                    session(['tracked_url' => $currentUrl]);
                }
            }
        }

        return $next($request);
    }

    /**
     * Determine whether this URL should be excluded from the back-navigation
     * history (create and edit pages).
     */
    private function shouldSkip(Request $request): bool
    {
        // Check the named route first (most reliable)
        $routeName = $request->route()?->getName() ?? '';
        if (str_ends_with($routeName, '.create') || str_ends_with($routeName, '.edit')) {
            return true;
        }

        // Fall back to inspecting the last path segment for routes that use
        // non-standard names (e.g. events.createSeries, series.createOccurrence)
        $lastSegment = basename(parse_url($request->path(), PHP_URL_PATH) ?? '');

        return str_starts_with($lastSegment, 'create') || $lastSegment === 'edit';
    }
}
