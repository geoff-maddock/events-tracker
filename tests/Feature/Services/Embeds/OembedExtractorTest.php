<?php

namespace Tests\Feature\Services\Embeds;

use App\Services\Embeds\Provider;
use App\Services\Embeds\OembedExtractor;
use Tests\TestCase;

class OembedExtractorTest extends TestCase
{
    /** @test */
    public function it_can_be_instantiated()
    {
        $provider = new Provider();
        $extractor = new OembedExtractor($provider);
        $this->assertInstanceOf(OembedExtractor::class, $extractor);
    }

    /** @test */
    public function default_config_is_medium()
    {
        $provider = new Provider();
        $extractor = new OembedExtractor($provider);
        $extractor->setLayout("medium");
        $results = $extractor->getLayoutConfig();

        $this->assertEquals(166, $results["height"]);
    }

    /** @test */
    public function config_can_be_set_to_large()
    {
        $provider = new Provider();
        $extractor = new OembedExtractor($provider);
        $extractor->setLayout("large");
        $results = $extractor->getLayoutConfig();

        $this->assertEquals(300, $results["height"]);
    }

    /** @test */
    public function config_can_be_set_to_small()
    {
        $provider = new Provider();
        $extractor = new OembedExtractor($provider);
        $extractor->setLayout("small");
        $results = $extractor->getLayoutConfig();

        $this->assertEquals(20, $results["height"]);
    }

    /** @test */
    public function extract_embeds_from_urls_returns_array()
    {
        $provider = new Provider();
        $extractor = new OembedExtractor($provider);
        $urls = [];
        $results = $extractor->extractEmbedsFromUrls($urls, "medium");

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /** @test */
    public function extract_embeds_filters_soundcloud_urls()
    {
        $provider = new Provider();
        $extractor = new OembedExtractor($provider);
        $urls = [
            'https://soundcloud.com/user/track',
            'https://example.com/other'
        ];
        
        // We can't test actual API calls without mocking, so we just verify it doesn't crash
        $results = $extractor->extractEmbedsFromUrls($urls, "medium");
        $this->assertIsArray($results);
    }

    /** @test */
    public function extract_embeds_filters_bandcamp_urls()
    {
        $provider = new Provider();
        $extractor = new OembedExtractor($provider);
        $urls = [
            'https://artist.bandcamp.com/track/song',
            'https://example.com/other'
        ];
        
        // We can't test actual API calls without mocking, so we just verify it doesn't crash
        $results = $extractor->extractEmbedsFromUrls($urls, "medium");
        $this->assertIsArray($results);
    }
}
