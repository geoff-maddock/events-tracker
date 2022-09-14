<?php

namespace App\Services;

use App\Models\Entity;
use App\Models\Event;
use Jamband\Ripple\Ripple;

/**
 * Extracts embed data from objects and strings
 */
class EmbedExtractor
{
    /**
     * Returns an array of embeds for an entity
     */
    public function getEmbedsForEntity(Entity $entity): array
    {
        $embeds = [];

        // ripple extracts data from audio provider links
        $ripple = new Ripple;

        // LARGE - Set up a large bandcamp player
        // $ripple->options(['curl' => [], 'embed' => ['Bandcamp' => 'size=large/bgcol=ffffff/linkcol=0687f5/tracklist=false/transparent=true/'], 'response' => []]);

        // MEDIUM - Set up a medium bandcamp player
        $ripple->options([
            'curl' => [],
            'embed' => [
                'Bandcamp' => '/size=large/bgcol=ffffff/linkcol=0687f5/tracklist=false/artwork=small/transparent=true/',
                'Soundcloud' => '&color=%23ff5500&auto_play=true&hide_related=true&show_comments=false&show_user=true&show_reposts=false&show_teaser=false',
 
            ],
            'response' => []
        ]);
       
        // SMALL - Set up a small bandcamp player
        //$ripple->options(['curl' => [], 'embed' => ['Bandcamp' => '/size=small/bgcol=333333/linkcol=0687f5/transparent=true/'], 'response' => []]);
        
        // get some data about the entities bandcamp links
        $links = $entity->links;

        // convert the entitie's links into embeds when they contain embeddable audio
        foreach ($links as $link) {
            // bandcamp
            if (strpos($link->url, "bandcamp.com") && (strpos($link->url, "album") || strpos($link->url, "track"))) {
                // it's a bandcamp link, so request info
                $ripple->request($link->url);
                $embeds[] = sprintf('<iframe style="border: 0; width: 100%%; height: 120px;"  src="%s" allowfullscreen seamless></iframe>', $ripple->embed());
            }
            // soundcloud
            if (strpos($link->url, "soundcloud.com") && substr_count($link->url, '/') > 3) {
                // it's a soundcloud link, so request info

                $ripple->request($link->url);
                $embeds[] = sprintf('<iframe style="border: 0; width: 100%%; height: 120px;"  src="%s" allowfullscreen seamless></iframe>', $ripple->embed());
            }
        }

        return $embeds;
    }

    /**
     * Returns an array of embeds for an event
     */
    public function getEmbedsForEvent(Event $event): array
    {
        $embeds = [];

        // ripple extracts data from audio provider links
        $ripple = new Ripple;

        // LARGE - Set up a large bandcamp player
        // $ripple->options(['curl' => [], 'embed' => ['Bandcamp' => 'size=large/bgcol=ffffff/linkcol=0687f5/tracklist=false/transparent=true/'], 'response' => []]);

        // MEDIUM - Set up a medium bandcamp player
        $ripple->options([
            'curl' => [],
            'embed' => [
                'Bandcamp' => '/size=large/bgcol=ffffff/linkcol=0687f5/tracklist=false/artwork=small/transparent=true/',
                'Soundcloud' => '&color=%23ff5500&auto_play=true&hide_related=true&show_comments=false&show_user=true&show_reposts=false&show_teaser=false',
 
            ],
            'response' => []
        ]);
       
        // SMALL - Set up a small bandcamp player
        //$ripple->options(['curl' => [], 'embed' => ['Bandcamp' => '/size=small/bgcol=333333/linkcol=0687f5/transparent=true/'], 'response' => []]);
        
        // get the body of the event and extract any relevant links
        $body = $event->description;

        $regex = "/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i";
        preg_match_all($regex, $body, $result, PREG_PATTERN_ORDER);
        $links = $result[0];

        // convert the entity's links into embeds when they contain embeddable audio
        foreach ($links as $link) {
            // bandcamp
            if (strpos($link, "bandcamp.com") && (strpos($link, "album") || strpos($link, "track"))) {
                // it's a bandcamp link, so request info
                // ripple requires that the link contain album or track, but any bandcamp page that has meta property og:video should have an embedded player
                // and if the page is a container (artist page), we should be able to get the first album or track link and get the embed from there.
                $ripple->request($link);
                $embeds[] = sprintf('<iframe style="border: 0; width: 100%%; height: 120px;"  src="%s" allowfullscreen seamless></iframe>', $ripple->embed());
            }
            // soundcloud
            if (strpos($link, "soundcloud.com") && substr_count($link, '/') > 3) {
                // it's a soundcloud link, so request info

                $ripple->request($link);
                $embeds[] = sprintf('<iframe style="border: 0; width: 100%%; height: 120px;"  src="%s" allowfullscreen seamless></iframe>', $ripple->embed());
            }
        }

        // dump($embeds);
        return $embeds;
    }
}
