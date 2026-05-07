<?php

namespace App\Http\Controllers;

use App\Models\ShortUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ShortUrlController extends Controller
{
    /**
     * Shorten a URL and return the short URL.
     *
     * Accepts a full URL and returns a JSON response containing a short code
     * and the fully-qualified short URL. If the same URL has been shortened
     * before, the existing record is returned.
     */
    public function shorten(Request $request): JsonResponse
    {
        $request->validate([
            'url' => ['required', 'string', 'url', 'max:2048'],
        ]);

        $url = $request->input('url');
        $shortUrl = $this->findOrCreateShortUrl($url);

        return response()->json([
            'code' => $shortUrl->code,
            'short_url' => route('short-url.redirect', ['code' => $shortUrl->code]),
        ]);
    }

    /**
     * Resolve a short code and redirect to the original URL.
     */
    public function redirect(string $code): RedirectResponse
    {
        $shortUrl = ShortUrl::where('code', $code)->firstOrFail();

        $shortUrl->increment('visit_count');

        return redirect()->away($shortUrl->url);
    }

    /**
     * Find an existing short URL record for the given URL, or create one,
     * retrying up to five times when a hash collision with a different URL
     * is encountered.
     */
    private function findOrCreateShortUrl(string $url): ShortUrl
    {
        // Return the existing record if one already maps to this URL.
        $existing = ShortUrl::where('url', $url)->first();
        if ($existing) {
            return $existing;
        }

        $seed = $url;
        $maxAttempts = 5;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $code = $this->generateCode($seed);

            // Skip this code if it already belongs to a different URL.
            if (ShortUrl::where('code', $code)->where('url', '!=', $url)->exists()) {
                $seed = $url . uniqid('', true);
                continue;
            }

            return ShortUrl::firstOrCreate(['code' => $code], ['url' => $url]);
        }

        // Extremely unlikely last resort: use a 10-char code from microtime.
        $code = substr(md5($url . microtime(true)), 0, 10);

        return ShortUrl::firstOrCreate(['code' => $code], ['url' => $url]);
    }

    /**
     * Derive an 8-character alphanumeric code from a URL.
     */
    private function generateCode(string $url): string
    {
        return substr(md5($url), 0, 8);
    }
}
