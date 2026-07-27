<?php

namespace App\Services;

use Illuminate\Http\Request;

/**
 * Central definition of the canonical URL and robots-meta rules used by the
 * layout head. Listing pages reached with list-state query params (sort,
 * filters, tabs, …) canonicalize to their clean path and are noindexed so
 * the QueryFilter permutations don't bleed duplicates into the index.
 */
class SeoMeta
{
    /**
     * Query params that represent list/navigation state rather than distinct
     * content. Anything else (e.g. utm_*) is left indexable and consolidates
     * through the canonical instead.
     */
    protected const NOINDEX_PARAMS = [
        'sort',
        'direction',
        'limit',
        'filters',
        'keyword',
        'tabs',
        'day_offset',
        'page',
    ];

    /**
     * Canonical URL for the current request: the path on the configured app
     * host, query string stripped. Built from config('app.url') rather than
     * the request host so it can't drift from the canonical host.
     */
    public static function canonicalUrl(Request $request): string
    {
        $base = rtrim(config('app.url'), '/');
        $path = trim($request->path(), '/');

        return $path === '' ? $base : $base.'/'.$path;
    }

    /**
     * Whether the current request carries list-state query params and should
     * be noindexed (with follow, so link equity still flows).
     */
    public static function shouldNoindex(Request $request): bool
    {
        return count(array_intersect(array_keys($request->query()), self::NOINDEX_PARAMS)) > 0;
    }
}
