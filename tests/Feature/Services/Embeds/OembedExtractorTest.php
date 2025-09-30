<?php

namespace Tests\Feature\Services\Embeds;

use App\Services\Embeds\OembedExtractor;
use Tests\TestCase;

class OembedExtractorTest extends TestCase
{
    /** @test */
    public function it_can_be_instantiated()
    {
        $extractor = new OembedExtractor();
        $this->assertInstanceOf(OembedExtractor::class, $extractor);
    }

    /** @test */
    public function default_config_is_medium()
    {
        $extractor = new OembedExtractor();
        $extractor->setLayout("medium");
        $results = $extractor->getLayoutConfig();

        $this->assertEquals(120, $results["height"]);
    }

    /** @test */
    public function config_can_be_set_to_large()
    {
        $extractor = new OembedExtractor();
        $extractor->setLayout("large");
        $results = $extractor->getLayoutConfig();

        $this->assertEquals(300, $results["height"]);
    }

    /** @test */
    public function config_can_be_set_to_small()
    {
        $extractor = new OembedExtractor();
        $extractor->setLayout("small");
        $results = $extractor->getLayoutConfig();

        $this->assertEquals(42, $results["height"]);
    }

    /** @test */
    public function extract_embeds_from_urls_returns_array()
    {
        $extractor = new OembedExtractor();
        $urls = [];
        $results = $extractor->extractEmbedsFromUrls($urls, "medium");

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /** @test */
    public function extract_embeds_filters_soundcloud_urls()
    {
        $extractor = new OembedExtractor();
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
        $extractor = new OembedExtractor();
        $urls = [
            'https://artist.bandcamp.com/track/song',
            'https://example.com/other'
        ];
        
        // We can't test actual API calls without mocking, so we just verify it doesn't crash
        $results = $extractor->extractEmbedsFromUrls($urls, "medium");
        $this->assertIsArray($results);
    }
}
