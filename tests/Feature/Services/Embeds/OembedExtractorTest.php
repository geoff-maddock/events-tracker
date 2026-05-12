<?php

namespace Tests\Feature\Services\Embeds;

use App\Services\Embeds\Provider;
use App\Services\Embeds\OembedExtractor;
use PHPUnit\Framework\TestCase;

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
    public function default_config_has_medium_layouts()
    {
        $provider = new Provider();
        $extractor = new OembedExtractor($provider);
        $extractor->setLayout("medium");
        $results = $extractor->getLayoutConfig();

        $this->assertArrayHasKey('bandcamp_layout', $results);
        $this->assertArrayHasKey('soundcloud_layout', $results);
        $this->assertStringContainsString('height: 120px', $results['soundcloud_layout']);
    }

    /** @test */
    public function config_can_be_set_to_large()
    {
        $provider = new Provider();
        $extractor = new OembedExtractor($provider);
        $extractor->setLayout("large");
        $results = $extractor->getLayoutConfig();

        $this->assertArrayHasKey('bandcamp_layout', $results);
        $this->assertArrayHasKey('soundcloud_layout', $results);
        $this->assertStringContainsString('height: 300px', $results['soundcloud_layout']);
        $this->assertStringContainsString('height: 300px', $results['bandcamp_layout']);
    }

    /** @test */
    public function config_can_be_set_to_small()
    {
        $provider = new Provider();
        $extractor = new OembedExtractor($provider);
        $extractor->setLayout("small");
        $results = $extractor->getLayoutConfig();

        $this->assertArrayHasKey('bandcamp_layout', $results);
        $this->assertArrayHasKey('soundcloud_layout', $results);
        $this->assertStringContainsString('height: 24px', $results['soundcloud_layout']);
        $this->assertStringContainsString('height: 42px', $results['bandcamp_layout']);
    }

    /** @test */
    public function soundcloud_layout_is_set_for_each_size()
    {
        $provider = new Provider();
        $extractor = new OembedExtractor($provider);

        foreach (['large', 'medium', 'small'] as $size) {
            $extractor->setLayout($size);
            $results = $extractor->getLayoutConfig();
            $this->assertArrayHasKey('soundcloud_layout', $results, "soundcloud_layout missing for size: $size");
            $this->assertStringContainsString('<iframe', $results['soundcloud_layout']);
            $this->assertStringContainsString('SoundCloud audio player', $results['soundcloud_layout']);
        }
    }

    /** @test */
    public function extract_embeds_from_urls_returns_array()
    {
        $provider = new Provider();
        $extractor = new OembedExtractor($provider);
        $results = $extractor->extractEmbedsFromUrls([], "medium");

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

        $results = $extractor->extractEmbedsFromUrls($urls, "medium");
        $this->assertIsArray($results);
    }
}
