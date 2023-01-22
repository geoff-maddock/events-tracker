<?php

namespace App\Services\Embeds;

use App\Models\Entity;
use App\Models\Event;
use DOMDocument;
use DOMXPath;
use Exception;
use Jamband\Ripple\Ripple;

/**
 * Extracts embed data from objects and strings
 */
class EmbedExtractor
{
    const CONTAINER_LIMIT = 4;

    protected Provider $provider;
    protected array $config = [];
    protected string $size = "medium";

    /**
     * @param Provider $provider
     */
    public function __construct(Provider $provider)
    {
        $this->provider = $provider;
    }

    public function setLayout(string $size = "medium"): void
    {
        $this->size = $size;
        $this->config = $this->getLayoutConfig($size);
    }

    private function getLayoutConfig(string $size = "medium"): array 
    {
        $config = [];

        // set up the variables;
        switch ($this->size) {
            case "large":
                $config["bandcamp"] = '/size=large/bgcol=ffffff/linkcol=0687f5/tracklist=false/transparent=true/';
                $config["soundcloud"] = '&color=%23ff5500&inverse=false&auto_play=true&show_user=true';
                $config["bandcamp_layout"] = '<iframe style="border: 0; width: 100%%; height: 300px;" src="%s" allowfullscreen seamless></iframe>';
                $config["soundcloud_layout"] = '<iframe style="border: 0; width: 100%%; height: 300px;" src="%s" allowfullscreen seamless></iframe>';
                break;
            case "small":
                $config["bandcamp"] = '/size=small/bgcol=333333/linkcol=0687f5/transparent=true/';
                $config["soundcloud"] = '&color=%23232863&inverse=true&auto_play=true&show_user=true';
                $config["bandcamp_layout"] = '<iframe style="border: 0; width: 100%%; height: 42px; margin-bottom: -7px;" src="%s" allowfullscreen seamless></iframe>';
                $config["soundcloud_layout"] = '<iframe style="border: 0; width: 100%%; height: 20px; margin-bottom: -7px; color: #cccccc;" src="%s" allowfullscreen seamless></iframe>';
               break;
            default:
                $config["bandcamp"] = '/size=large/bgcol=ffffff/linkcol=0687f5/tracklist=false/artwork=small/transparent=true/';
                $config["soundcloud"] = '&color=%23ff5500&auto_play=true&hide_related=true&show_comments=false&show_user=true&show_reposts=false&show_teaser=false';
                $config["bandcamp_layout"] = '<iframe style="border: 0; width: 100%%; height: 120px;" src="%s" allowfullscreen seamless></iframe>';
                $config["soundcloud_layout"] = '<iframe style="border: 0; width: 100%%; height: 120px;" src="%s" allowfullscreen seamless></iframe>';
        }

        return $config;
    }

    /**
     * Returns an array of embeds for an entity
     */
    public function getEmbedsForEntity(Entity $entity, string $size = "medium"): array
    {
        $embeds = [];
        $links = [];

        // check if the config is set, if not, set it
        if (empty($this->config)) {
            $this->config = $this->getLayoutConfig($size);
        };

        // ripple extracts data from audio provider links
        $ripple = new Ripple;

        // set up ripple
        $ripple->options([
            'curl' => [],
            'embed' => [
                'Bandcamp' => $this->config["bandcamp"],
                'Soundcloud' => $this->config["soundcloud"]
            ],
            'response' => []
        ]);

        // get some data about the entities bandcamp links
        $collectionLinks = $entity->links;

        // handle any URLs that are only containers
        foreach ($collectionLinks as $collectionLink) {
            $url = $collectionLink->url;

            // if it's a bandcamp link
            if (strpos($url, "bandcamp.com")) {
                $temp =  $this->getEmbedsFromBandcampUrl($url);
                $embeds = array_merge($embeds, $temp);
            } else {
                $links[] = $url;
            }
        }

        // convert the entity's links into embeds when they contain embeddable audio
        foreach ($links as $link) {
            // soundcloud
            if (strpos($link, "soundcloud.com") && substr_count($link, '/') > 3) {
                // it's a soundcloud link, so request info
                $ripple->request($link);
                $embeds[] = sprintf($this->config["soundcloud_layout"], $ripple->embed().$this->config["soundcloud"]);
            }
        }

        return array_unique($embeds);
    }

    /**
     * Returns an array of embeds for an event
     */
    public function getEmbedsForEvent(Event $event, string $size = "medium"): array
    {
        $embeds = [];
        $links = [];

        // check if the config is set, if not, set it
        if (empty($this->config)) {
            $this->config = $this->getLayoutConfig($size);
        };

        // ripple extracts data from audio provider links
        $ripple = new Ripple;

        // set up ripple
        $ripple->options([
            'curl' => [],
            'embed' => [
                'Bandcamp' => $this->config["bandcamp"],
                'Soundcloud' => $this->config["soundcloud"]
            ],
            'response' => []
        ]);

        // get the body of the event and extract any relevant links
        $body = $event->description;

        // regex match all URLs
        $regex = "/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i";
        preg_match_all($regex, $body, $result, PREG_PATTERN_ORDER);
        $urls = $result[0];

        // collect any URLs from related entities
        foreach ($event->entities as $entity) {
            foreach ($entity->links as $link) {
                if (in_array($link->url, $urls)) {
                    continue;
                };
                $urls[] = $link->url;
            }
        };

        // handle any URLs
        foreach ($urls as $url) {
            // if it's a bandcamp link
            if (strpos($url, "bandcamp.com")) {
                $temp =  $this->getEmbedsFromBandcampUrl($url);
                $embeds = array_merge($embeds, $temp);
            } else {
                $links[] = $url;
            }
        }

        // convert the event's links into embeds when they contain embeddable audio
        foreach ($links as $link) {
            // soundcloud
            if (strpos($link, "soundcloud.com") && substr_count($link, '/') > 3) {
                // it's a soundcloud link, so request info
                $ripple->request($link);

                $embeds[] = sprintf($this->config["soundcloud_layout"], $ripple->embed().$this->config["soundcloud"]);
            }
        }

        return $embeds;
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
            $this->config = $this->getLayoutConfig($size);
        };

        // if it's a bandcamp link
        if (strpos($url, "bandcamp.com")) {
            // send a request to the URL and look for a meta tag
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

    /**
     * Returns an array of tracks for an entity - call this with ajax so it's not blocking
     */
    public function getTracksFromUrl(string $url): array
    {
        // now collect tracks from all root bandcamp links
        $trackUrls = [];

        $httpClient = new \GuzzleHttp\Client();

        try {
            $response = $httpClient->get($url);
        } catch (Exception $e) {
            // if there was an exception, don't process further
            return [];
        }
        $htmlString = (string) $response->getBody();

        libxml_use_internal_errors(true);

        $doc = new DOMDocument();
        $doc->loadHTML($htmlString);
        $xpath = new DOMXPath($doc);

        $albumLinks = $xpath->evaluate("//a[contains(@href,'album')]");

        // build a list of links
        $albumUrls = [];
        foreach ($albumLinks as $albumLink) {
            if (strpos($albumLink->getAttribute("href"), 'https') === 0) {
                if (!in_array($albumLink->getAttribute("href"), $albumUrls)) {
                    $albumUrls[] = $albumLink->getAttribute("href");
                }
            }
        }

        $trackLinks = $xpath->evaluate("//a[contains(@href,'track')]");

        // build a list of links
        $trackUrls = [];
        foreach ($trackLinks as $trackLink) {
            if (strpos($trackLink->getAttribute("href"), 'https') === 0) {
                if (!in_array($trackLink->getAttribute("href"), $trackUrls)) {
                    $trackUrls[] = $trackLink->getAttribute("href");
                }
            }
        }

        // spider the album urls to get the rest of the tracks
        foreach ($albumUrls as $albumUrl) {
            $parsedUrl = parse_url($albumUrl);
            $baseUrl = $parsedUrl["scheme"]."://".$parsedUrl["host"];

            $trackResponse = $httpClient->get($albumUrl);
            $htmlString = (string) $trackResponse->getBody();

            // may instead be able to use header meta data on album pages
            $doc = new DOMDocument();
            $doc->loadHTML($htmlString);
            $xpath = new DOMXPath($doc);
    
            $trackLinks = $xpath->evaluate("//a[contains(@href,'track')]");

            foreach ($trackLinks as $trackLink) {
                $trackFullUrl = $baseUrl.$trackLink->getAttribute("href");
                if (!strpos($trackLink->getAttribute("href"), '?')) {
                    if (!in_array($trackFullUrl, $trackUrls)) {
                        $trackUrls[] = $trackFullUrl;
                    }
                }
            }
        }

        $embedUrls = $this->getEmbedsFromTracks($trackUrls);

        return $embedUrls;
    }

    /**
     * Get the embed URLs by querying buymusic API
     */
    protected function getEmbedsFromTracks(array $trackUrls): array
    {
        $baseUrl = "https://buymusic.club/api/bandcamp/";
        $embedUrls = [];

        $httpClient = new \GuzzleHttp\Client();

        foreach ($trackUrls as $trackUrl) {
            $url = sprintf("%s?url=%s", $baseUrl, $trackUrl);
            $response = $httpClient->get($url);
            $jsonString = (string) $response->getBody();
            $obj = json_decode($jsonString);
            $embedUrls[] = $obj->streamURL;
        }

        return $embedUrls;
    }
}
