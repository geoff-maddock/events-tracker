<?php

namespace Tests\Unit\Services;

use App\Services\Embeds\OembedExtractor;
use App\Services\Embeds\Provider;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * OembedExtractor::getSourcesForUrl(), which feeds the audio player (#2366).
 */
class PlayerSourcesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_bandcamp_page_resolves_to_a_medium_embed_src_and_title(): void
    {
        $provider = new CannedPageProvider();
        $provider->pages['https://artist.bandcamp.com/album/x'] = '<meta property="og:title" content="Album X, by Artist">'
            .'<meta property="og:video" content="https://bandcamp.com/EmbeddedPlayer/v=2/album=1/">';

        $sources = (new OembedExtractor($provider))->getSourcesForUrl('https://artist.bandcamp.com/album/x');

        $this->assertSame([[
            'provider' => 'bandcamp',
            'url' => 'https://artist.bandcamp.com/album/x',
            'src' => 'https://bandcamp.com/EmbeddedPlayer/v=2/album=1//size=large/bgcol=333333/linkcol=0f91ff/tracklist=false/artwork=small/transparent=true/',
            'title' => 'Album X, by Artist',
        ]], $sources);
    }

    public function test_bandcamp_artist_page_resolves_its_releases(): void
    {
        $provider = new CannedPageProvider();
        $provider->pages['https://artist.bandcamp.com/'] = '<a href="/album/one">One</a><a href="/track/two">Two</a>';
        $provider->pages['https://artist.bandcamp.com/album/one'] = '<meta property="og:video" content="https://bandcamp.com/EmbeddedPlayer/v=2/album=1/">';
        $provider->pages['https://artist.bandcamp.com/track/two'] = '<meta property="og:video" content="https://bandcamp.com/EmbeddedPlayer/v=2/track=2/">';

        $sources = (new OembedExtractor($provider))->getSourcesForUrl('https://artist.bandcamp.com/');

        $this->assertSame(
            ['https://artist.bandcamp.com/album/one', 'https://artist.bandcamp.com/track/two'],
            array_column($sources, 'url')
        );
    }

    public function test_soundcloud_widget_from_another_host_is_rejected(): void
    {
        $provider = new CannedPageProvider();
        $provider->oembed = ['html' => '<iframe src="https://evil.example/player"></iframe>'];

        $this->assertSame([], (new OembedExtractor($provider))->getSourcesForUrl('https://soundcloud.com/artist'));
    }

    public function test_results_and_failures_are_cached_per_url(): void
    {
        $provider = new CannedPageProvider();
        $extractor = new OembedExtractor($provider);

        $this->assertSame([], $extractor->getSourcesForUrl('https://artist.bandcamp.com/album/missing'));
        $this->assertSame([], $extractor->getSourcesForUrl('https://artist.bandcamp.com/album/missing'));

        $this->assertSame(['https://artist.bandcamp.com/album/missing'], $provider->fetched);
    }

    public function test_page_embeds_and_the_player_share_one_lookup_per_url(): void
    {
        $provider = new CannedPageProvider();
        $provider->pages['https://artist.bandcamp.com/album/x'] = '<meta property="og:video" content="https://bandcamp.com/EmbeddedPlayer/v=2/album=1/">';
        $extractor = new OembedExtractor($provider);

        $this->assertCount(1, $extractor->extractEmbedsFromUrls(['https://artist.bandcamp.com/album/x']));
        $this->assertCount(1, $extractor->getSourcesForUrl('https://artist.bandcamp.com/album/x'));

        $this->assertSame(['https://artist.bandcamp.com/album/x'], $provider->fetched);
    }

    public function test_audio_urls_are_recognised(): void
    {
        $this->assertTrue(OembedExtractor::isAudioUrl('https://soundcloud.com/artist'));
        $this->assertTrue(OembedExtractor::isAudioUrl('http://m.soundcloud.com/artist/track'));
        $this->assertTrue(OembedExtractor::isAudioUrl('https://artist.bandcamp.com/'));
        $this->assertFalse(OembedExtractor::isAudioUrl('https://evil.example/soundcloud.com'));
        $this->assertFalse(OembedExtractor::isAudioUrl('https://soundcloud.com.evil.example/x'));
        $this->assertFalse(OembedExtractor::isAudioUrl('javascript:alert(1)//soundcloud.com'));
    }
}

/**
 * Provider double: canned Bandcamp pages and SoundCloud oEmbed JSON; records fetched pages.
 */
class CannedPageProvider extends Provider
{
    /** @var array<int, string> */
    public array $fetched = [];

    /** @var array<string, string> */
    public array $pages = [];

    /** @var array<string, mixed>|null */
    public ?array $oembed = null;

    public function request(string $url): void
    {
        $this->setResponse(str_contains($url, 'soundcloud.com/oembed') && $this->oembed !== null ? (string) json_encode($this->oembed) : null);
    }

    public function requestWithoutRedirects(string $url): ?string
    {
        $this->fetched[] = $url;
        $this->setResponse($this->pages[$url] ?? null);

        return null;
    }
}
