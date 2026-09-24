<?php

namespace Tests\Unit\Services;

use App\Services\Embeds\OembedExtractor;
use App\Services\Embeds\Provider;
use Tests\TestCase;

/**
 * Bandcamp embeds only ever fetch https pages on bandcamp.com (or a subdomain),
 * re-check every redirect hop, and escape the og:video src they embed.
 */
class BandcampEmbedSafetyTest extends TestCase
{
    public function test_bandcamp_url_accepts_bandcamp_hosts(): void
    {
        $this->assertSame('https://artist.bandcamp.com/album/x', OembedExtractor::bandcampUrl('https://artist.bandcamp.com/album/x'));
        $this->assertSame('https://artist.bandcamp.com/track/y?a=1', OembedExtractor::bandcampUrl('http://Artist.Bandcamp.com/track/y?a=1'));
        $this->assertSame('https://bandcamp.com/EmbeddedPlayer/album=1/', OembedExtractor::bandcampUrl('https://bandcamp.com/EmbeddedPlayer/album=1/'));
    }

    public function test_bandcamp_url_rejects_everything_else(): void
    {
        foreach ([
            'http://169.254.169.254/latest/meta-data/?bandcamp.com',
            'http://10.0.0.5/bandcamp.com',
            'https://evil.example/bandcamp.com',
            'https://bandcamp.com.evil.example/album/x',
            'https://evilbandcamp.com/album/x',
            'https://artist.bandcamp.com@evil.example/',
            'https://user:pw@artist.bandcamp.com/',
            'https://artist.bandcamp.com:8080/',
            'javascript:alert(1)//bandcamp.com',
            'ftp://artist.bandcamp.com/',
            '//artist.bandcamp.com/',
        ] as $url) {
            $this->assertNull(OembedExtractor::bandcampUrl($url), $url);
        }
    }

    public function test_non_bandcamp_urls_are_never_fetched(): void
    {
        $provider = new RecordingProvider();
        $extractor = new OembedExtractor($provider);

        $extractor->extractEmbedsFromUrls([
            'http://169.254.169.254/latest/meta-data/?bandcamp.com',
            'https://evil.example/bandcamp.com',
        ]);

        $this->assertSame([], $provider->fetched);
    }

    public function test_redirects_off_bandcamp_are_not_followed(): void
    {
        $provider = new RecordingProvider();
        $provider->redirects['https://artist.bandcamp.com/album/x'] = 'http://169.254.169.254/latest/meta-data/';
        $extractor = new OembedExtractor($provider);

        $this->assertSame([], $extractor->extractEmbedsFromUrls(['https://artist.bandcamp.com/album/x']));
        $this->assertSame(['https://artist.bandcamp.com/album/x'], $provider->fetched);
    }

    public function test_redirects_within_bandcamp_are_followed(): void
    {
        $provider = new RecordingProvider();
        $provider->redirects['https://artist.bandcamp.com/'] = 'https://artist.bandcamp.com/album/x';
        $provider->pages['https://artist.bandcamp.com/album/x'] = '<meta property="og:video" content="https://bandcamp.com/EmbeddedPlayer/v=2/album=1/">';
        $extractor = new OembedExtractor($provider);

        $embeds = $extractor->extractEmbedsFromUrls(['https://artist.bandcamp.com/']);

        $this->assertCount(1, $embeds);
        $this->assertStringContainsString('src="https://bandcamp.com/EmbeddedPlayer/v=2/album=1/', $embeds[0]);
    }

    public function test_og_video_off_bandcamp_is_not_embedded(): void
    {
        $provider = new RecordingProvider();
        $provider->pages['https://artist.bandcamp.com/album/x'] = '<meta property="og:video" content="https://evil.example/x&quot; onload=&quot;alert(1)">';
        $extractor = new OembedExtractor($provider);

        $this->assertSame([], $extractor->extractEmbedsFromUrls(['https://artist.bandcamp.com/album/x']));
    }

    public function test_og_video_src_is_escaped(): void
    {
        $provider = new RecordingProvider();
        $provider->pages['https://artist.bandcamp.com/album/x'] = '<meta property="og:video" content="https://bandcamp.com/EmbeddedPlayer/album=1/&quot;onload=&quot;alert(1)">';
        $extractor = new OembedExtractor($provider);

        $embeds = $extractor->extractEmbedsFromUrls(['https://artist.bandcamp.com/album/x']);

        $this->assertCount(1, $embeds);
        $this->assertStringNotContainsString('"onload="', $embeds[0]);
        $this->assertStringContainsString('&quot;onload=&quot;', $embeds[0]);
    }
}

/**
 * Provider double: serves canned pages/redirects and records every fetched URL.
 */
class RecordingProvider extends Provider
{
    /** @var array<int, string> */
    public array $fetched = [];

    /** @var array<string, string> */
    public array $pages = [];

    /** @var array<string, string> */
    public array $redirects = [];

    public function request(string $url): void
    {
        $this->fetched[] = $url;
    }

    public function requestWithoutRedirects(string $url): ?string
    {
        $this->fetched[] = $url;

        if (isset($this->redirects[$url])) {
            return $this->redirects[$url];
        }

        $this->setResponse($this->pages[$url] ?? null);

        return null;
    }
}
