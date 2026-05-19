<?php

namespace App\Services\Embeds;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Series;
use DOMDocument;
use DOMXPath;
use Exception;
use Illuminate\Support\Facades\Cache;

/**
 * Extracts embed data using oEmbed APIs
 */
class OembedExtractor
{
    const CONTAINER_LIMIT = 4;

    // 7 days — matches the browser-side embed cache TTL in public/js/embed-cache.js.
    const CACHE_TTL_SECONDS = 604800;

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

    public function getLayoutConfig(): array
    {
        $config = [];

        $css = 'bgcol=333333/linkcol=0f91ff';

        switch ($this->size) {
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
            if (str_contains($url, "soundcloud.com")) {
                $embed = $this->getEmbedsFromSoundcloudUrl($url);
                if ($embed !== null) {
                    $embeds[] = $embed;
                }
            }

            if (str_contains($url, "bandcamp.com")) {
                $temp = $this->getEmbedsFromBandcampUrl($url);
                if ($temp !== null) {
                    $embeds = array_merge($embeds, $temp);
                }
            }
        }

        return $embeds;
    }

    /**
     * Get embed HTML from SoundCloud using oEmbed API
     */
    protected function getEmbedsFromSoundcloudUrl(string $url): ?string
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

        if (!isset($data['html'])) {
            return null;
        }

        // Extract the src URL from the returned iframe and apply our size-aware template
        if (!preg_match('/src="([^"]+)"/', $data['html'], $matches)) {
            return null;
        }

        $src = $this->applySoundcloudSizeParams($matches[1]);

        return sprintf($this->config['soundcloud_layout'], $src);
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

    protected function getEmbedsFromBandcampUrl(string $url, int $depth = 1): ?array
    {
        // prevent infinite recursion through container pages
        if ($depth > 2) {
            return [];
        }

        $this->provider->setResponse(null);

        $embeds = [];
        $containerCount = 1;

        if (empty($this->config)) {
            $this->config = $this->getLayoutConfig();
        }

        if (str_contains($url, "bandcamp.com")) {
            $this->provider->request($url);
            $content = $this->provider->query('//meta[@property="og:video"]/@content');

            if (null !== $content) {
                $content = $this->convertBandcampMetaOgVideo($content);
                $embeds[] = sprintf($this->config['bandcamp_layout'], $content);
            } else {
                $containerUrls = $this->getUrlsFromContainer($url);

                foreach ($containerUrls as $containerUrl) {
                    if ($containerCount > $this::CONTAINER_LIMIT) {
                        break;
                    }
                    $temp = $this->getEmbedsFromBandcampUrl($containerUrl, $depth + 1);
                    if (count($temp) > 0) {
                        $embeds = array_merge($embeds, $temp);
                        $containerCount++;
                    }
                }
            }

            $this->provider->setResponse(null);
        }

        return array_unique($embeds);
    }

    protected function getUrlsFromContainer(string $containerUrl): array
    {
        $urls = [];

        $httpClient = new \GuzzleHttp\Client();

        try {
            $response = $httpClient->get($containerUrl);
        } catch (Exception $e) {
            return [];
        }

        $htmlString = (string) $response->getBody();

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
