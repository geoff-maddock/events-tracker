<?php

namespace App\Services\Embeds;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Series;
use DOMDocument;
use DOMXPath;
use Exception;

/**
 * Extracts embed data using oEmbed APIs
 */
class OembedExtractor
{
    const CONTAINER_LIMIT = 4;

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

        // set up the layout configuration based on size
        switch ($this->size) {
            case "large":
                $config["height"] = 300;
                $config["bandcamp"] = sprintf('/size=large/%s/tracklist=false/transparent=true/', $css);
                $config["bandcamp_layout"] = '<iframe style="border: 0; width: 100%%; height: 300px;" src="%s" allowfullscreen seamless></iframe>';
                break;
            case "small":
                $config["height"] = 20;
                $config["bandcamp"] = sprintf('/size=small/%s/transparent=true/', $css);
                $config["bandcamp_layout"] = '<iframe style="border: 0; width: 100%%; height: 42px; margin-bottom: -7px;" src="%s" allowfullscreen seamless></iframe>';
 
                break;
            default:
                $config["height"] = 166;
                $config["bandcamp"] = sprintf('/size=large/%s/tracklist=false/artwork=small/transparent=true/',$css);
                $config["bandcamp_layout"] = '<iframe style="border: 0; width: 100%%; height: 120px;" src="%s" allowfullscreen seamless></iframe>';
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

        // check if the config is set, if not, set it
        if (empty($this->config)) {
            $this->config = $this->getLayoutConfig();
        }

        // process each URL
        foreach ($urls as $url) {
            // if it's a soundcloud link
            if (strpos($url, "soundcloud.com") !== false) {
                $embed = $this->getEmbedsFromSoundcloudUrl($url);

                if ($embed !== null) {

                    // process the embed based on the size
                    // for small, we need to change visual=true to visual=false
                    if ($this->size === "small") {
                        $embed = str_replace("visual=true", "visual=false&color=%160d18&inverse=true", $embed);
                        $embed = str_replace("frameborder=\"no\"", "style=\"border: 0; width: 100%; height: 24px; margin-bottom: -7px; padding: 2px; background-color: #333333; color: #cccccc;\"", $embed);
                    }

                    $embeds[] = $embed;
                }
            }

            // if it's a bandcamp link
            if (strpos($url, "bandcamp.com") !== false) {
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
        $oembedUrl = 'https://soundcloud.com/oembed';
        
        // build the POST data
        $postData = http_build_query([
            'format' => 'json',
            'url' => $url,
            'maxheight' => $this->config['height'] ?? 120,
            'autoplay' => 'false',  // valid param
            'show_comments' => 'false',  // valid param
            'show_user' => 'true',  
            'hide_related' => 'true',
            'show_teaser' => 'false',
            'inverse' => 'false',
            'visual' => 'false',  // invalid, but needs to be false for small
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
     * Converts the Bandcamp Meta OG Video format based on size
     */
    protected function convertBandcampMetaOgVideo(string $content): string
    {
        switch ($this->size) {
            case "small":
                $content = str_replace("large", "small", $content);
                $content = str_replace("artwork=small/", "", $content);
        }

        $content = $content.$this->config["bandcamp"];

        return $content;
    }


    protected function getEmbedsFromBandcampUrl(string $url, int $depth = 1, string $size = 'medium'): ?array
    {
        // prevent an infinite loop
        if ($depth > 2) {
            return [];
        }
        // reset the response
        $this->provider->setResponse(null);

        $embeds = [];
        $containerCount = 1;

        // set up the layout config
        if (empty($this->config)) {
            $this->config = $this->getLayoutConfig();
        };

        // if it's a bandcamp link
        if (strpos($url, "bandcamp.com")) {

            // send a request to the URL and look for a meta tag that contains the embed link directly
            $this->provider->request($url);
            $content = $this->provider->query('//meta[@property="og:video"]/@content');
                
            // if there is a matching meta tag on the page
            if (null !== $content) {

                // convert content based on size
                $content = $this->convertBandcampMetaOgVideo($content);
                $embeds[] = sprintf($this->config['bandcamp_layout'], $content);
            } else {
                // no embed in meta, so might be container
                $containerUrls = $this->getUrlsFromContainer($url);

                // for each URL on the page
                foreach ($containerUrls as $containerUrl) {
                    if ($containerCount > $this::CONTAINER_LIMIT) {
                        break;
                    }
                    // if there is an embed, add it to the array
                    $temp = $this->getEmbedsFromBandcampUrl($containerUrl, $depth + 1, $size);
                    if (count($temp) > 0) {
                        $embeds = array_merge($embeds, $temp);
                        $containerCount++;
                    }
                }
            }
        
            // reset the response
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
            // if there was an exception, don't process further
            return [];
        }

        $htmlString = (string) $response->getBody();

        libxml_use_internal_errors(true);

        $doc = new DOMDocument();
        $doc->loadHTML($htmlString);
        $xpath = new DOMXPath($doc);

        // parse the url to get the base
        $parsedUrl = parse_url($containerUrl);

        // if there is no scheme, default to https
        $scheme = isset($parsedUrl["scheme"]) ? $parsedUrl["scheme"] : 'https';
        $host = isset($parsedUrl["host"]) ? $parsedUrl["host"] : '';

        $baseUrl = $scheme."://".$host;

        $albumLinks = $xpath->evaluate("//a[contains(@href,'/album')]");
        
        // add album links to the url array
        foreach ($albumLinks as $albumLink) {
            if (strpos($albumLink->getAttribute("href"), 'https') === 0) {
                if (!in_array($albumLink->getAttribute("href"), $urls)
                     && strpos($parsedUrl["host"], $albumLink->getAttribute("href"))
                     && $albumLink->getAttribute("href") !== $containerUrl
                ) {
                    $urls[] = $albumLink->getAttribute("href");
                }
            } else {
                // handle the case where the links are just partial
                if (substr($albumLink->getAttribute("href"), 4) !== 'http') {
                    if (!in_array($baseUrl.$albumLink->getAttribute("href"), $urls)
                        && $baseUrl.$albumLink->getAttribute("href") !== $containerUrl
                    ) {
                        $urls[] = $baseUrl.$albumLink->getAttribute("href");
                    }
                }
            }
        }

        $trackLinks = $xpath->evaluate("//a[contains(@href,'/track')]");

        // add track links to the url array
        foreach ($trackLinks as $trackLink) {
            if (strpos($trackLink->getAttribute("href"), 'https') === 0) {
                if (!in_array($trackLink->getAttribute("href"), $urls)
                    && strpos($parsedUrl["host"], $trackLink->getAttribute("href"))
                    && $trackLink->getAttribute("href") !== $containerUrl
                ) {
                    $urls[] = $trackLink->getAttribute("href");
                }
            } else {
                // handle the case where the links are just partial
                if (substr($trackLink->getAttribute("href"), 4) !== 'http') {
                    if (!in_array($baseUrl.$trackLink->getAttribute("href"), $urls)
                        && $baseUrl.$trackLink->getAttribute("href") !== $containerUrl
                    ) {
                        $urls[] = $baseUrl.$trackLink->getAttribute("href");
                    }
                }
            }
        }

        return array_unique($urls);
    }


}
