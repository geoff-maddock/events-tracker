<?php

namespace Tests\Unit\Services;

use App\Exceptions\RemoteImageException;
use App\Services\RemoteImageFetcher;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * RemoteImageFetcher (issue #2123) is an SSRF sink: the caller picks the
 * URL and the server fetches it. These tests pin down the guards — scheme,
 * address space (per redirect hop), redirect cap, size cap, content
 * sniffing — and the UploadedFile it hands back.
 */
class RemoteImageFetcherTest extends TestCase
{
    private const PUBLIC_IP = '93.184.216.34';

    /** Hostnames the fake resolver knows; anything else resolves to nothing. */
    private array $hosts = [
        'cdn.example.com' => [self::PUBLIC_IP],
        'other.example.com' => ['198.51.100.7', self::PUBLIC_IP], // first address is TEST-NET-2, non-global
        'internal.example.com' => ['127.0.0.1'],
        'metadata.example.com' => ['169.254.169.254'],
        'v6.example.com' => ['2606:2800:220:1:248:1893:25c8:1946'],
    ];

    private function fetcher(): RemoteImageFetcher
    {
        return new RemoteImageFetcher(fn (string $host) => $this->hosts[$host] ?? []);
    }

    private function png(): string
    {
        $img = imagecreatetruecolor(4, 4);
        ob_start();
        imagepng($img);

        return (string) ob_get_clean();
    }

    private function jpeg(): string
    {
        $img = imagecreatetruecolor(4, 4);
        ob_start();
        imagejpeg($img);

        return (string) ob_get_clean();
    }

    private function image(string $bytes, string $contentType = 'image/png', array $headers = []): \GuzzleHttp\Promise\PromiseInterface
    {
        return Http::response($bytes, 200, ['Content-Type' => $contentType] + $headers);
    }

    /** Run fetch() and return [originalName, mime, size, pathAtCallbackTime]. */
    private function fetchInfo(string $url): array
    {
        return $this->fetcher()->fetch($url, fn (UploadedFile $file) => [
            $file->getClientOriginalName(),
            $file->getMimeType(),
            $file->getSize(),
            $file->getPathname(),
            $file->isValid(),
        ]);
    }

    private function assertRejected(string $url, string $messageFragment): void
    {
        try {
            $this->fetcher()->fetch($url, fn () => null);
        } catch (RemoteImageException $e) {
            $this->assertStringContainsString($messageFragment, $e->getMessage());

            return;
        }

        $this->fail('Expected RemoteImageException for '.$url);
    }

    /** @test */
    public function it_downloads_a_public_image_and_hands_back_an_uploaded_file(): void
    {
        Http::fake(['https://cdn.example.com/*' => $this->image($this->png())]);

        [$name, $mime, $size, $path, $valid] = $this->fetchInfo('https://cdn.example.com/uploads/Flyer%20Final.png?w=800');

        $this->assertSame('Flyer-Final.png', $name);
        $this->assertSame('image/png', $mime);
        $this->assertSame(strlen($this->png()), $size);
        $this->assertTrue($valid);
        $this->assertFileDoesNotExist($path, 'The temp file must be removed after the callback runs.');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://cdn.example.com/uploads/Flyer%20Final.png?w=800');
    }

    /** @test */
    public function it_returns_whatever_the_callback_returns(): void
    {
        Http::fake(['https://cdn.example.com/*' => $this->image($this->png())]);

        $this->assertSame('done', $this->fetcher()->fetch('https://cdn.example.com/a.png', fn () => 'done'));
    }

    /** @test */
    public function the_extension_follows_the_sniffed_type_not_the_url(): void
    {
        Http::fake(['https://cdn.example.com/*' => $this->image($this->jpeg(), 'image/png')]);

        [$name, $mime] = $this->fetchInfo('https://cdn.example.com/flyer.png');

        $this->assertSame('flyer.jpg', $name);
        $this->assertSame('image/jpeg', $mime);
    }

    /** @test */
    public function it_generates_a_name_when_the_url_has_no_usable_path(): void
    {
        Http::fake(['https://cdn.example.com/*' => $this->image($this->png())]);

        [$name] = $this->fetchInfo('https://cdn.example.com/');

        $this->assertMatchesRegularExpression('/^remote-[A-Za-z0-9]{12}\.png$/', $name);
    }

    /** @test */
    public function it_cleans_up_the_temp_file_when_the_callback_throws(): void
    {
        Http::fake(['https://cdn.example.com/*' => $this->image($this->png())]);

        $path = null;

        try {
            $this->fetcher()->fetch('https://cdn.example.com/a.png', function (UploadedFile $file) use (&$path) {
                $path = $file->getPathname();
                throw new \RuntimeException('boom');
            });
        } catch (\RuntimeException $e) {
        }

        $this->assertNotNull($path);
        $this->assertFileDoesNotExist($path);
    }

    /** @test */
    public function it_only_allows_https(): void
    {
        Http::fake();

        $this->assertRejected('http://cdn.example.com/a.png', 'Only https');
        $this->assertRejected('ftp://cdn.example.com/a.png', 'Only https');
        $this->assertRejected('file:///etc/passwd', 'not valid');
        $this->assertRejected('data:image/png;base64,AAAA', 'not valid');
        $this->assertRejected('https://user:pw@cdn.example.com/a.png', 'credentials');
        $this->assertRejected('https://cdn.example.com:8443/a.png', 'standard https port');

        Http::assertNothingSent();
    }

    /** @test */
    public function it_rejects_hosts_in_private_or_reserved_address_space(): void
    {
        Http::fake();

        $this->assertRejected('https://127.0.0.1/a.png', 'public host');
        $this->assertRejected('https://[::1]/a.png', 'public host');
        $this->assertRejected('https://10.1.2.3/a.png', 'public host');
        $this->assertRejected('https://169.254.169.254/latest/meta-data', 'public host');
        $this->assertRejected('https://internal.example.com/a.png', 'public host');
        $this->assertRejected('https://metadata.example.com/a.png', 'public host');
        // one non-global address among several is enough to reject
        $this->assertRejected('https://other.example.com/a.png', 'public host');
        $this->assertRejected('https://does-not-resolve.example.com/a.png', 'could not be resolved');

        Http::assertNothingSent();
    }

    /** @test */
    public function is_global_address_classifies_the_edge_cases(): void
    {
        $fetcher = $this->fetcher();

        foreach (['127.0.0.1', '10.0.0.1', '172.16.5.5', '192.168.1.1', '169.254.169.254', '100.64.0.1', '0.0.0.0',
            '224.0.0.1', '255.255.255.255', '::1', '::', 'fe80::1', 'fc00::1', 'fd12::1', '::ffff:127.0.0.1', '::ffff:10.0.0.1', 'ff02::1'] as $ip) {
            $this->assertFalse($fetcher->isGlobalAddress($ip), "$ip should not be treated as global");
        }

        foreach (['93.184.216.34', '8.8.8.8', '2606:2800:220:1:248:1893:25c8:1946', '2001:4860:4860::8888'] as $ip) {
            $this->assertTrue($fetcher->isGlobalAddress($ip), "$ip should be global");
        }
    }

    /** @test */
    public function it_follows_redirects_but_revalidates_every_hop(): void
    {
        Http::fake([
            'https://cdn.example.com/start' => Http::response('', 302, ['Location' => '/moved']),
            'https://cdn.example.com/moved' => Http::response('', 301, ['Location' => 'https://v6.example.com/final.png']),
            'https://v6.example.com/final.png' => $this->image($this->png()),
        ]);

        [$name] = $this->fetchInfo('https://cdn.example.com/start');

        $this->assertSame('final.png', $name);
        Http::assertSentCount(3);
    }

    /** @test */
    public function it_rejects_a_redirect_chain_that_lands_on_a_private_address(): void
    {
        Http::fake([
            'https://cdn.example.com/start' => Http::response('', 302, ['Location' => 'https://internal.example.com/secret.png']),
            'https://internal.example.com/*' => $this->image($this->png()),
        ]);

        $this->assertRejected('https://cdn.example.com/start', 'public host');

        Http::assertSentCount(1);
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'internal.example.com'));
    }

    /** @test */
    public function it_rejects_a_redirect_to_a_non_https_scheme(): void
    {
        Http::fake([
            'https://cdn.example.com/start' => Http::response('', 302, ['Location' => 'http://cdn.example.com/plain.png']),
        ]);

        $this->assertRejected('https://cdn.example.com/start', 'Only https');
        Http::assertSentCount(1);
    }

    /** @test */
    public function it_caps_the_redirect_chain(): void
    {
        Http::fake([
            'https://cdn.example.com/1' => Http::response('', 302, ['Location' => '/2']),
            'https://cdn.example.com/2' => Http::response('', 302, ['Location' => '/3']),
            'https://cdn.example.com/3' => Http::response('', 302, ['Location' => '/4']),
            'https://cdn.example.com/4' => Http::response('', 302, ['Location' => '/5']),
            'https://cdn.example.com/5' => $this->image($this->png()),
        ]);

        $this->assertRejected('https://cdn.example.com/1', 'too many times');
        Http::assertSentCount(RemoteImageFetcher::MAX_REDIRECTS + 1);
    }

    /** @test */
    public function it_rejects_a_body_over_the_cap_even_without_content_length(): void
    {
        Http::fake(['https://cdn.example.com/*' => $this->image(str_repeat('x', RemoteImageFetcher::MAX_BYTES + 1), 'image/jpeg')]);

        $this->assertRejected('https://cdn.example.com/huge.jpg', 'larger than the 5 MB limit');
    }

    /** @test */
    public function it_rejects_an_announced_size_over_the_cap(): void
    {
        Http::fake(['https://cdn.example.com/*' => $this->image($this->png(), 'image/png', ['Content-Length' => (string) (RemoteImageFetcher::MAX_BYTES + 1)])]);

        $this->assertRejected('https://cdn.example.com/huge.png', 'larger than the 5 MB limit');
    }

    /** @test */
    public function it_accepts_a_body_exactly_at_the_cap(): void
    {
        // a real PNG padded to exactly MAX_BYTES; trailing bytes are ignored by decoders
        $png = $this->png();
        $bytes = $png.str_repeat("\0", RemoteImageFetcher::MAX_BYTES - strlen($png));
        Http::fake(['https://cdn.example.com/*' => $this->image($bytes)]);

        [, , $size] = $this->fetchInfo('https://cdn.example.com/big.png');

        $this->assertSame(RemoteImageFetcher::MAX_BYTES, $size);
    }

    /** @test */
    public function it_rejects_bodies_that_are_not_images_whatever_the_headers_say(): void
    {
        Http::fake([
            'https://cdn.example.com/html.jpg' => $this->image('<html><body>not a flyer</body></html>', 'image/jpeg'),
            'https://cdn.example.com/photo.heic' => $this->image("\x00\x00\x00\x18ftypheic\x00\x00\x00\x00mif1heic".str_repeat("\0", 64), 'image/heic'),
            'https://cdn.example.com/doc.svg' => $this->image('<svg xmlns="http://www.w3.org/2000/svg"><script>1</script></svg>', 'image/svg+xml'),
            'https://cdn.example.com/empty.png' => $this->image('', 'image/png'),
        ]);

        $this->assertRejected('https://cdn.example.com/html.jpg', 'supported image');
        $this->assertRejected('https://cdn.example.com/photo.heic', 'supported image');
        $this->assertRejected('https://cdn.example.com/doc.svg', 'supported image');
        $this->assertRejected('https://cdn.example.com/empty.png', 'empty');
    }

    /** @test */
    public function it_reports_non_success_statuses(): void
    {
        Http::fake(['https://cdn.example.com/*' => Http::response('gone', 404)]);

        $this->assertRejected('https://cdn.example.com/missing.png', 'HTTP 404');
    }

    /** @test */
    public function it_reports_connection_failures_without_leaking_details(): void
    {
        Http::fake(['https://cdn.example.com/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('cURL error 28: timed out at 10.0.0.1')]);

        try {
            $this->fetcher()->fetch('https://cdn.example.com/slow.png', fn () => null);
            $this->fail('Expected RemoteImageException');
        } catch (RemoteImageException $e) {
            $this->assertSame('The image could not be downloaded from that URL.', $e->getMessage());
        }
    }
}
