<?php

namespace App\Services\Embeds;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Series;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Cache;

/**
 * Extracts embed data using oEmbed APIs
 */
class OembedExtractor
{
    const CONTAINER_LIMIT = 4;

    const MAX_REDIRECTS = 3;

    // 7 days — matches the browser-side embed cache TTL in public/js/embed-cache.js.
    const CACHE_TTL_SECONDS = 604800;

    // A link that resolved to nothing is retried after an hour rather than a week.
    const FAILED_SOURCES_TTL_SECONDS = 3600;

    protected Provider $provider;

    protected array $config = [];
    protected string $size = "medium";

    public function __construct(Provider $provider)
    {
        $this->provider = $provider;
    }

    public function setLayout(string $size = "medium"): void
    {
        $this->size = $size;
        $this->config = $this->getLayoutConfig();
    }

    public function getLayoutConfig(?string $size = null): array
    {
        $config = [];

        $css = 'bgcol=333333/linkcol=0f91ff';

        switch ($size ?? $this->size) {
            case "large":
                $config["bandcamp"] = sprintf('/size=large/%s/tracklist=false/transparent=true/', $css);
                $config["bandcamp_layout"] = '<iframe style="border: 0; width: 100%%; height: 300px;" src="%s" allowfullscreen seamless title="Bandcamp audio player"></iframe>';
                $config["soundcloud_layout"] = '<iframe style="border: 0; width: 100%%; height: 300px;" src="%s" allowfullscreen seamless title="SoundCloud audio player"></iframe>';
                break;
            case "small":
                $config["bandcamp"] = sprintf('/size=small/%s/transparent=true/', $css);
                $config["bandcamp_layout"] = '<iframe style="border: 0; width: 100%%; height: 42px; margin-bottom: -7px;" src="%s" allowfullscreen seamless title="Bandcamp audio player"></iframe>';
                $config["soundcloud_layout"] = '<iframe style="border: 0; width: 100%%; height: 24px; margin-bottom: -7px; padding: 2px; background-color: #333333; color: #cccccc;" src="%s" allowfullscreen seamless title="SoundCloud audio player"></iframe>';
                break;
            default:
                $config["bandcamp"] = sprintf('/size=large/%s/tracklist=false/artwork=small/transparent=true/', $css);
                $config["bandcamp_layout"] = '<iframe style="border: 0; width: 100%%; height: 120px;" src="%s" allowfullscreen seamless title="Bandcamp audio player"></iframe>';
                $config["soundcloud_layout"] = '<iframe style="border: 0; width: 100%%; height: 120px;" src="%s" allowfullscreen seamless title="SoundCloud audio player"></iframe>';
        }

        return $config;
    }

    /**
     * Returns an array of embeds for an entity
     */
    public function getEmbedsForEntity(Entity $entity, string $size = "medium"): array
    {
        return Cache::remember(
            $this->embedsCacheKey('entity', $entity->slug ?? (string) $entity->id, $this->size, $entity->updated_at?->timestamp),
            self::CACHE_TTL_SECONDS,
            function () use ($entity, $size): array {
                $urls = [];

                foreach ($entity->links as $link) {
                    if (in_array($link->url, $urls)) {
                        continue;
                    }
                    $urls[] = $link->url;
                }

                return $this->extractEmbedsFromUrls($urls, $size);
            }
        );
    }

    /**
     * Returns an array of embeds for an event
     */
    public function getEmbedsForEvent(Event $event, string $size = "medium"): array
    {
        return Cache::remember(
            $this->embedsCacheKey('event', $event->slug ?? (string) $event->id, $this->size, $event->updated_at?->timestamp),
            self::CACHE_TTL_SECONDS,
            function () use ($event, $size): array {
                $body = $event->description ?? '';

                $regex = "/\b(?:(?:https|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i";
                preg_match_all($regex, $body, $result, PREG_PATTERN_ORDER);
                $urls = $result[0];

                foreach ($event->entities as $entity) {
                    foreach ($entity->links as $link) {
                        if (in_array($link->url, $urls)) {
                            continue;
                        }
                        $urls[] = $link->url;
                    }
                }

                return $this->extractEmbedsFromUrls($urls, $size);
            }
        );
    }

    /**
     * Returns an array of embeds for a series
     */
    public function getEmbedsForSeries(Series $series, string $size = "medium"): array
    {
        return Cache::remember(
            $this->embedsCacheKey('series', $series->slug ?? (string) $series->id, $this->size, $series->updated_at?->timestamp),
            self::CACHE_TTL_SECONDS,
            function () use ($series, $size): array {
                $body = $series->description ?? '';

                $regex = "/\b(?:(?:https|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i";
                preg_match_all($regex, $body, $result, PREG_PATTERN_ORDER);
                $urls = $result[0];

                foreach ($series->entities as $entity) {
                    foreach ($entity->links as $link) {
                        if (in_array($link->url, $urls)) {
                            continue;
                        }
                        $urls[] = $link->url;
                    }
                }

                return $this->extractEmbedsFromUrls($urls, $size);
            }
        );
    }

    /**
     * Build a cache key keyed by resource + slug + size + updated_at so saves auto-invalidate.
     */
    protected function embedsCacheKey(string $type, string $identifier, string $size, ?int $version): string
    {
        return sprintf('embeds:%s:%s:%s:%s', $type, $identifier, $size, $version ?? '0');
    }

    /**
     * Returns an array of audio embeds based on URLs
     */
    public function extractEmbedsFromUrls(array $urls, string $size = "medium"): array
    {
        $embeds = [];

        if (empty($this->config)) {
            $this->config = $this->getLayoutConfig();
        }

        foreach ($urls as $url) {
            foreach ($this->getRawSourcesForUrl($url) as $source) {
                $embeds[] = $source['provider'] === 'soundcloud'
                    ? sprintf($this->config['soundcloud_layout'], $this->applySoundcloudSizeParams($source['raw']))
                    : sprintf($this->config['bandcamp_layout'], e($this->convertBandcampMetaOgVideo($source['raw'])));
            }
        }

        return $embeds;
    }

    /**
     * Playable sources for one SoundCloud or Bandcamp URL, for the audio player (#2366).
     *
     * Each source is ['provider' => 'soundcloud'|'bandcamp', 'url' => page URL,
     * 'src' => iframe src at the medium layout, 'title' => ?string].
     *
     * @return list<array{provider: string, url: string, src: string, title: ?string}>
     */
    public function getSourcesForUrl(string $url): array
    {
        $config = $this->getLayoutConfig('medium');

        return array_map(fn (array $source) => [
            'provider' => $source['provider'],
            'url' => $source['url'],
            'src' => $source['provider'] === 'soundcloud' ? $source['raw'] : $source['raw'].$config['bandcamp'],
            'title' => $source['title'],
        ], $this->getRawSourcesForUrl($url));
    }

    /**
     * resolveRawSources(), cached per URL and shared by the page embeds and the
     * player, so a Bandcamp container is resolved once for both. A URL that
     * resolves to nothing is cached for a shorter time so a broken link isn't
     * fetched on every view.
     *
     * @return list<array{provider: string, url: string, raw: string, title: ?string}>
     */
    protected function getRawSourcesForUrl(string $url): array
    {
        $key = 'audio-sources:'.sha1($url);
        $cached = Cache::get($key);

        if (is_array($cached)) {
            return $cached;
        }

        $sources = $this->resolveRawSources($url);

        Cache::put($key, $sources, $sources === [] ? self::FAILED_SOURCES_TTL_SECONDS : self::CACHE_TTL_SECONDS);

        return $sources;
    }

    /**
     * Whether $url is a link the player and embeds can resolve.
     */
    public static function isAudioUrl(string $url): bool
    {
        return self::soundcloudUrl($url) !== null || self::bandcampUrl($url) !== null;
    }

    /**
     * Unsized sources for one URL: the SoundCloud widget src from oEmbed, or the
     * Bandcamp og:video embed URL(s), each with its page URL and title.
     *
     * @return list<array{provider: string, url: string, raw: string, title: ?string}>
     */
    protected function resolveRawSources(string $url): array
    {
        $sources = [];

        if (str_contains($url, "soundcloud.com")) {
            $source = $this->resolveSoundcloudSource($url);
            if ($source !== null) {
                $sources[] = $source;
            }
        }

        if ($bandcampUrl = self::bandcampUrl($url)) {
            $sources = array_merge($sources, $this->resolveBandcampSources($bandcampUrl));
        }

        return $sources;
    }

    /**
     * Get the widget src for a SoundCloud URL using the oEmbed API
     *
     * @return array{provider: string, url: string, raw: string, title: ?string}|null
     */
    protected function resolveSoundcloudSource(string $url): ?array
    {
        $oembedUrl = 'https://soundcloud.com/oembed?' . http_build_query([
            'format'        => 'json',
            'url'           => $url,
            'maxheight'     => 300,
            'show_comments' => 'false',
            'show_user'     => 'true',
            'hide_related'  => 'true',
            'show_teaser'   => 'false',
        ]);

        $this->provider->setResponse(null);
        $this->provider->request($oembedUrl);
        $response = $this->provider->getResponse();

        if (empty($response)) {
            return null;
        }

        $data = json_decode($response, true);

        if (!is_array($data) || !isset($data['html']) || !is_string($data['html'])) {
            return null;
        }

        // Extract the src URL from the returned iframe; only SoundCloud's own widget is embedded
        if (!preg_match('/src="([^"]+)"/', $data['html'], $matches)
            || !str_starts_with($matches[1], 'https://w.soundcloud.com/player/')
        ) {
            return null;
        }

        return [
            'provider' => 'soundcloud',
            'url' => $url,
            'raw' => $matches[1],
            'title' => isset($data['title']) && is_string($data['title']) ? $data['title'] : null,
        ];
    }

    /**
     * SoundCloud's oembed always returns the visual (~300px) player. The "small"
     * layout iframe is only ~24px tall, so the URL must be rewritten to the mini
     * (non-visual) player or it renders squashed/wrong-format. Medium and large
     * iframes are tall enough for the visual player as-is.
     */
    protected function applySoundcloudSizeParams(string $src): string
    {
        if ($this->size !== 'small') {
            return $src;
        }

        return str_replace(
            'visual=true',
            'visual=false&show_artwork=false&inverse=true&color=%23333333',
            $src
        );
    }

    /**
     * Converts the Bandcamp og:video src URL for the target size
     */
    protected function convertBandcampMetaOgVideo(string $content): string
    {
        if ($this->size === "small") {
            $content = str_replace("large", "small", $content);
            $content = str_replace("artwork=small/", "", $content);
        }

        return $content . $this->config["bandcamp"];
    }

    /**
     * The og:video embed URL(s) for a Bandcamp page; an artist/label page with no
     * og:video is treated as a container and up to CONTAINER_LIMIT of its albums
     * and tracks are resolved instead.
     *
     * @return list<array{provider: string, url: string, raw: string, title: ?string}>
     */
    protected function resolveBandcampSources(string $url, int $depth = 1): array
    {
        // prevent infinite recursion through container pages
        if ($depth > 2) {
            return [];
        }

        $this->provider->setResponse(null);

        $sources = [];
        $containerCount = 1;

        // only https pages on bandcamp.com or a subdomain are fetched, and every redirect hop is re-checked
        if (($url = self::bandcampUrl($url)) && $this->requestBandcampPage($url)) {
            $content = $this->provider->query('//meta[@property="og:video"]/@content');

            if (null !== $content && self::bandcampUrl($content) !== null) {
                $sources[] = [
                    'provider' => 'bandcamp',
                    'url' => $url,
                    'raw' => $content,
                    'title' => $this->provider->query('//meta[@property="og:title"]/@content'),
                ];
            } elseif (null === $content) {
                $containerUrls = $this->getUrlsFromContainer($url, (string) $this->provider->getResponse());

                foreach ($containerUrls as $containerUrl) {
                    if ($containerCount > $this::CONTAINER_LIMIT) {
                        break;
                    }
                    $temp = $this->resolveBandcampSources($containerUrl, $depth + 1);
                    if (count($temp) > 0) {
                        $sources = array_merge($sources, $temp);
                        $containerCount++;
                    }
                }
            }

            $this->provider->setResponse(null);
        }

        // one source per embed URL, first occurrence wins
        $seen = [];

        return array_values(array_filter($sources, function (array $source) use (&$seen): bool {
            if (isset($seen[$source['raw']])) {
                return false;
            }

            return $seen[$source['raw']] = true;
        }));
    }

    /**
     * Normalise $url to an https URL on soundcloud.com (or a subdomain), or null if it is not one.
     */
    public static function soundcloudUrl(string $url): ?string
    {
        $parts = parse_url(trim($url));

        if (!is_array($parts) || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return null;
        }

        $host = strtolower($parts['host'] ?? '');

        if ($host !== 'soundcloud.com' && !str_ends_with($host, '.soundcloud.com')) {
            return null;
        }

        return 'https://'.$host.($parts['path'] ?? '');
    }

    /**
     * Normalise $url to an https URL on bandcamp.com or one of its subdomains, or null if it is not one.
     * http links are upgraded; credentials and non-default ports are rejected.
     */
    public static function bandcampUrl(string $url): ?string
    {
        $parts = parse_url(trim($url));

        if (!is_array($parts) || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return null;
        }

        if (isset($parts['user']) || isset($parts['pass']) || (isset($parts['port']) && !in_array((int) $parts['port'], [80, 443], true))) {
            return null;
        }

        $host = strtolower($parts['host'] ?? '');

        if ($host !== 'bandcamp.com' && !str_ends_with($host, '.bandcamp.com')) {
            return null;
        }

        return 'https://'.$host.($parts['path'] ?? '').(isset($parts['query']) ? '?'.$parts['query'] : '');
    }

    /**
     * Load a Bandcamp page into the provider, following up to MAX_REDIRECTS redirects that stay on Bandcamp.
     */
    protected function requestBandcampPage(string $url): bool
    {
        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            $this->provider->setResponse(null);
            $next = $this->provider->requestWithoutRedirects($url);

            if ($next === null) {
                return !empty($this->provider->getResponse());
            }

            if (!$url = self::bandcampUrl($next)) {
                return false;
            }
        }

        return false;
    }

    /**
     * Album and track links on an already-fetched Bandcamp artist/label page.
     */
    protected function getUrlsFromContainer(string $containerUrl, string $htmlString): array
    {
        $urls = [];

        if ($htmlString === '') {
            return [];
        }

        libxml_use_internal_errors(true);

        $doc = new DOMDocument();
        $doc->loadHTML($htmlString);
        $xpath = new DOMXPath($doc);

        $parsedUrl = parse_url($containerUrl);
        $scheme = $parsedUrl["scheme"] ?? 'https';
        $host = $parsedUrl["host"] ?? '';
        $baseUrl = $scheme . "://" . $host;

        $albumLinks = $xpath->evaluate("//a[contains(@href,'/album')]");

        foreach ($albumLinks as $albumLink) {
            $href = $albumLink->getAttribute("href");
            if (str_starts_with($href, 'https')) {
                if (!in_array($href, $urls)
                    && str_contains($href, $host)
                    && $href !== $containerUrl
                ) {
                    $urls[] = $href;
                }
            } else {
                $full = $baseUrl . $href;
                if (!in_array($full, $urls) && $full !== $containerUrl) {
                    $urls[] = $full;
                }
            }
        }

        $trackLinks = $xpath->evaluate("//a[contains(@href,'/track')]");

        foreach ($trackLinks as $trackLink) {
            $href = $trackLink->getAttribute("href");
            if (str_starts_with($href, 'https')) {
                if (!in_array($href, $urls)
                    && str_contains($href, $host)
                    && $href !== $containerUrl
                ) {
                    $urls[] = $href;
                }
            } else {
                $full = $baseUrl . $href;
                if (!in_array($full, $urls) && $full !== $containerUrl) {
                    $urls[] = $full;
                }
            }
        }

        return array_unique($urls);
    }
}
