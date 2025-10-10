<?php

namespace App\Services\Embeds;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Series;

/**
 * Extracts embed data using oEmbed APIs
 */
class OembedExtractor
{
    protected array $config = [];
    protected string $size = "medium";

    public function __construct()
    {
    }

    public function setLayout(string $size = "medium"): void
    {
        $this->size = $size;
        $this->config = $this->getLayoutConfig();
    }

    public function getLayoutConfig(): array 
    {
        $config = [];

        // set up the layout configuration based on size
        switch ($this->size) {
            case "large":
                $config["height"] = 300;
                $config["width"] = 400;
                break;
            case "small":
                $config["height"] = 42;
                $config["width"] = 400;
                break;
            default: // medium
                $config["height"] = 120;
                $config["width"] = 400;
        }

        return $config;
    }

    /**
     * Returns an array of embeds for an entity
     */
    public function getEmbedsForEntity(Entity $entity, string $size = "medium"): array
    {
        $urls = [];

        // collect URLs from entity links
        foreach ($entity->links as $link) {
            if (in_array($link->url, $urls)) {
                continue;
            }
            $urls[] = $link->url;
        }
        
        // extract embeds from URLs
        return $this->extractEmbedsFromUrls($urls, $size);
    }

    /**
     * Returns an array of embeds for an event
     */
    public function getEmbedsForEvent(Event $event, string $size = "medium"): array
    {
        // get the body of the event and extract any relevant links
        $body = $event->description;

        // regex match all URLs
        $regex = "/\b(?:(?:https|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i";
        preg_match_all($regex, $body, $result, PREG_PATTERN_ORDER);
        $urls = $result[0];
        
        // collect any URLs from related entities
        foreach ($event->entities as $entity) {
            foreach ($entity->links as $link) {
                if (in_array($link->url, $urls)) {
                    continue;
                }
                $urls[] = $link->url;
            }
        }

        // extract embeds from URLs
        return $this->extractEmbedsFromUrls($urls, $size);
    }

    /**
     * Returns an array of embeds for a series
     */
    public function getEmbedsForSeries(Series $series, string $size = "medium"): array
    {
        // get the body of the series and extract any relevant links
        $body = $series->description;

        // regex match all URLs
        $regex = "/\b(?:(?:https|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i";
        preg_match_all($regex, $body, $result, PREG_PATTERN_ORDER);
        $urls = $result[0];

        // collect any URLs from related entities
        foreach ($series->entities as $entity) {
            foreach ($entity->links as $link) {
                if (in_array($link->url, $urls)) {
                    continue;
                }
                $urls[] = $link->url;
            }
        }

        // extract embeds from URLs
        return $this->extractEmbedsFromUrls($urls, $size);
    }

    /**
     * Returns an array of audio embeds based on URLs
     */
    public function extractEmbedsFromUrls(array $urls, string $size = "medium"): array
    {
        $embeds = [];

        // set the size first
        if ($this->size !== $size) {
            $this->setLayout($size);
        }

        // check if the config is set, if not, set it
        if (empty($this->config)) {
            $this->config = $this->getLayoutConfig();
        }

        // process each URL
        foreach ($urls as $url) {
            // if it's a soundcloud link
            if (strpos($url, "soundcloud.com") !== false) {
                $embed = $this->getEmbedFromSoundcloudUrl($url);
                if ($embed !== null) {
                    $embeds[] = $embed;
                }
            }

            // if it's a bandcamp link
            if (strpos($url, "bandcamp.com") !== false) {
                $embed = $this->getEmbedFromBandcampUrl($url);
                if ($embed !== null) {
                    $embeds[] = $embed;
                }
            }
        }

        return $embeds;
    }

    /**
     * Get embed HTML from SoundCloud using oEmbed API
     */
    protected function getEmbedFromSoundcloudUrl(string $url): ?string
    {
        $oembedUrl = 'https://soundcloud.com/oembed';
        
        // build the POST data using configured height and width
        $postData = http_build_query([
            'format' => 'json',
            'url' => $url,
            'maxheight' => $this->config['height'] ?? 120,
            'maxwidth' => $this->config['width'] ?? 400,
        ]);

        // make the curl request
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $oembedUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => 'Geoff-Maddock/Events-Tracker BrowserKit',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // if request was successful
        if ($response !== false && $httpCode === 200) {
            $data = json_decode($response, true);
        
            // if there's an html key in the response, return it
            if (isset($data['html'])) {
                return $data['html'];
            }
        }

        return null;
    }

    /**
     * Get embed HTML from Bandcamp using oEmbed API
     */
    protected function getEmbedFromBandcampUrl(string $url): ?string
    {
        $oembedUrl = 'https://bandcamp.com/EmbeddedPlayer/oembed';
        
        // build the POST data using configured height and width
        $postData = http_build_query([
            'format' => 'json',
            'url' => $url,
            'maxheight' => $this->config['height'] ?? 120,
            'maxwidth' => $this->config['width'] ?? 400,
        ]);

        // make the curl request
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $oembedUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => 'Geoff-Maddock/Events-Tracker BrowserKit',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // if request was successful
        if ($response !== false && $httpCode === 200) {
            $data = json_decode($response, true);
        
            // if there's an html key in the response, return it
            if (isset($data['html'])) {
                return $data['html'];
            }
        }

        return null;
    }
}
