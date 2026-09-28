<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Give stored link URLs an http(s) scheme where the intent is clear (#2220):
 * a bare host ("www.example.com/x") gets https://, and a "https;//" typo is
 * repaired. Anything else without an http(s) scheme is left alone and logged
 * for review; Link::safeUrl() keeps it from rendering as an href.
 */
return new class extends Migration
{
    public function up(): void
    {
        $unfixed = [];

        DB::table('links')
            ->where('url', 'not like', 'http://%')
            ->where('url', 'not like', 'https://%')
            ->orderBy('id')
            ->get(['id', 'url'])
            ->each(function ($link) use (&$unfixed) {
                $url = trim((string) $link->url);
                $fixed = null;

                if (preg_match('#^(https?);//#i', $url, $m)) {
                    $fixed = strtolower($m[1]).'://'.substr($url, strlen($m[0]));
                } elseif (preg_match('#^(//)?[a-z0-9-]+(\.[a-z0-9-]+)*\.[a-z]{2,}(:\d+)?([/?\#]\S*)?$#i', $url)) {
                    $fixed = 'https://'.ltrim($url, '/');
                }

                if (null !== $fixed && false !== filter_var($fixed, FILTER_VALIDATE_URL)) {
                    DB::table('links')->where('id', $link->id)->update(['url' => $fixed]);
                } elseif ('' !== $url) {
                    $unfixed[] = ['id' => $link->id, 'url' => $url];
                }
            });

        if ($unfixed) {
            Log::warning('Links without an http(s) URL, left for review (#2220)', ['links' => $unfixed]);
        }
    }

    public function down(): void
    {
        // scheme fixes aren't reversible, and nothing needs undoing
    }
};
