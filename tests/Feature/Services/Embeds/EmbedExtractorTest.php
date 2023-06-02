<?php

namespace Tests\Feature\Services\Embeds;

use App\Services\Embeds\Provider;
use App\Services\Embeds\EmbedExtractor;
use Tests\TestCase;

class EmbedExtractorTest extends TestCase
{

    /** @test */
    public function default_config_is_medium()
    {
        $provider = new Provider();
        $extractor = new EmbedExtractor($provider);
        $extractor->setLayout("medium");
        $results = $extractor->getLayoutConfig();

        $config["bandcamp"] = sprintf('/size=large/%s/tracklist=false/artwork=small/transparent=true/','bgcol=333333/linkcol=0f91ff');
        $config["soundcloud"] = '&color=%23ff5500&inverse=true&auto_play=true&show_user=true';
        $config["bandcamp_layout"] = '<iframe style="border: 0; width: 100%%; height: 120px;" src="%s" allowfullscreen seamless></iframe>';
        $config["soundcloud_layout"] = '<iframe style="border: 0; width: 100%%; height: 120px;" src="%s" allowfullscreen seamless></iframe>';
        
        $this->assertEquals($results, $config);
    }
}

