<?php

namespace App\Services;

use App\Exceptions\RemoteImageException;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Downloads a publicly reachable image so it can be attached as a Photo
 * exactly like a multipart upload would be.
 *
 * This is an SSRF sink (the caller picks the URL, the server makes the
 * request), so every step is defensive:
 *
 *  - https only, on every hop of a redirect chain
 *  - the host is resolved up front and rejected if any address is
 *    loopback / link-local / private / otherwise non-global; the request
 *    is then pinned to the validated address so a DNS rebind between the
 *    check and the connect cannot redirect it
 *  - redirects are followed manually (max 3) and re-validated per hop
 *  - connect and total timeouts
 *  - a hard download cap, enforced while streaming (and via the curl
 *    progress callback in production) — not by trusting Content-Length
 *  - the stored bytes are sniffed and must be a jpg/png/gif/webp image
 */
class RemoteImageFetcher
{
    public const MAX_BYTES = 5 * 1024 * 1024;   // matches the Dropzone maxFilesize: 5
    public const MAX_REDIRECTS = 3;
    public const CONNECT_TIMEOUT = 10;
    public const TIMEOUT = 20;

    /** Sniffed mime type => extension used for the stored file name. */
    public const ALLOWED_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    /** @var callable(string): array<int, string> */
    private $resolver;

    /**
     * @param  (callable(string): array<int, string>)|null  $resolver  maps a hostname to its
     *         IP addresses; injectable so tests do not need DNS
     */
    public function __construct(?callable $resolver = null)
    {
        $this->resolver = $resolver ?? [$this, 'resolveHost'];
    }

    /**
     * Download the image at $url, hand it to $callback as an UploadedFile
     * (test mode, so ImageHandler::makePhoto() works unchanged) and remove
     * the temp file afterwards whatever happens.
     *
     * @template T
     *
     * @param  callable(UploadedFile): T  $callback
     * @return T
     *
     * @throws RemoteImageException
     */
    public function fetch(string $url, callable $callback): mixed
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'remote-image-');

        if ($tempPath === false) {
            throw new RemoteImageException('Could not create a temporary file for the download.');
        }

        try {
            $finalUrl = $this->download($url, $tempPath);
            $mime = $this->sniffMime($tempPath);
            $name = $this->originalName($finalUrl, self::ALLOWED_MIMES[$mime]);

            return $callback(new UploadedFile($tempPath, $name, $mime, null, true));
        } finally {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    /**
     * Follow the (validated) redirect chain and stream the final response
     * into $tempPath. Returns the URL the bytes actually came from.
     */
    private function download(string $url, string $tempPath): string
    {
        $current = $url;

        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            $response = $this->request($current);

            if ($response->redirect()) {
                $location = $response->header('Location');

                if ($location === '') {
                    throw new RemoteImageException('The remote server sent a redirect without a location.');
                }

                try {
                    $current = (string) UriResolver::resolve(new Uri($current), new Uri($location));
                } catch (\InvalidArgumentException $e) {
                    throw new RemoteImageException('The remote server redirected to an invalid URL.');
                }

                continue;
            }

            if (!$response->successful()) {
                throw new RemoteImageException(sprintf('The remote server responded with HTTP %d.', $response->status()));
            }

            $this->streamToFile($response, $tempPath);

            return $current;
        }

        throw new RemoteImageException('The URL redirected too many times.');
    }

    /**
     * Validate one URL and issue the request for it, pinned to the address
     * that passed validation.
     */
    private function request(string $url): Response
    {
        [$host, $port, $ip] = $this->assertSafeUrl($url);

        $aborted = false;

        $curl = [
            // pin the connection to the address we validated (DNS rebinding guard);
            // the Host header / SNI still use the hostname so TLS validates normally
            CURLOPT_RESOLVE => [sprintf('%s:%d:%s', $host, $port, $ip)],
            // abort the transfer once it passes the cap; curl 8.4+ also stops
            // in-flight transfers on MAXFILESIZE but older builds only check
            // the announced size, hence the progress callback as well
            CURLOPT_MAXFILESIZE => self::MAX_BYTES,
            CURLOPT_NOPROGRESS => false,
            CURLOPT_PROGRESSFUNCTION => static function ($resource, int $downloadSize, int $downloaded) use (&$aborted): int {
                if ($downloaded > self::MAX_BYTES || $downloadSize > self::MAX_BYTES) {
                    $aborted = true;

                    return 1;
                }

                return 0;
            },
        ];

        try {
            return Http::withOptions([
                    'allow_redirects' => false,
                    'stream' => true,
                    'curl' => $curl,
                ])
                ->connectTimeout(self::CONNECT_TIMEOUT)
                ->timeout(self::TIMEOUT)
                ->withUserAgent('EventsTracker/1.0 (+photo-from-url)')
                ->accept('image/*,*/*;q=0.5')
                ->get($url);
        } catch (ConnectionException|RequestException $e) {
            if ($aborted) {
                throw new RemoteImageException($this->tooLargeMessage());
            }

            throw new RemoteImageException('The image could not be downloaded from that URL.');
        }
    }

    /**
     * Enforce scheme, host and address-space rules for one URL.
     *
     * @return array{0: string, 1: int, 2: string} host, port, validated IP
     */
    private function assertSafeUrl(string $url): array
    {
        $parts = parse_url($url);

        if ($parts === false || empty($parts['host'])) {
            throw new RemoteImageException('The URL is not valid.');
        }

        if (strtolower($parts['scheme'] ?? '') !== 'https') {
            throw new RemoteImageException('Only https URLs are allowed.');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new RemoteImageException('URLs with credentials are not allowed.');
        }

        $host = strtolower(trim($parts['host'], '[]'));
        $port = (int) ($parts['port'] ?? 443);

        if ($port !== 443) {
            throw new RemoteImageException('Only the standard https port is allowed.');
        }

        $addresses = filter_var($host, FILTER_VALIDATE_IP) !== false
            ? [$host]
            : ($this->resolver)($host);

        if ($addresses === []) {
            throw new RemoteImageException('The host in the URL could not be resolved.');
        }

        foreach ($addresses as $address) {
            if (!$this->isGlobalAddress($address)) {
                throw new RemoteImageException('The URL must point to a public host.');
            }
        }

        return [$host, $port, $addresses[0]];
    }

    /**
     * True when $ip is a globally routable unicast address — i.e. not
     * loopback, link-local (incl. the 169.254.169.254 metadata endpoint),
     * RFC1918 / ULA, CGNAT, multicast, unspecified or otherwise reserved.
     */
    public function isGlobalAddress(string $ip): bool
    {
        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_GLOBAL_RANGE;

        if (filter_var($ip, FILTER_VALIDATE_IP, $flags) === false) {
            return false;
        }

        $packed = inet_pton($ip);

        if ($packed === false) {
            return false;
        }

        if (strlen($packed) === 4) {
            $first = ord($packed[0]);
            $second = ord($packed[1]);

            // 100.64.0.0/10 (CGNAT, used for cloud metadata by some providers), 224.0.0.0/4 multicast
            return !(($first === 100 && $second >= 64 && $second <= 127) || $first >= 224);
        }

        // IPv4-mapped (::ffff:a.b.c.d) and IPv4-compatible addresses: judge the embedded IPv4
        if (substr($packed, 0, 10) === str_repeat("\0", 10) && in_array(substr($packed, 10, 2), ["\xff\xff", "\0\0"], true)) {
            return $this->isGlobalAddress(inet_ntop(substr($packed, 12)));
        }

        // ff00::/8 multicast
        return ord($packed[0]) !== 0xff;
    }

    /**
     * Copy the response body into $tempPath in chunks, giving up as soon as
     * the cap is passed. This is the layer that catches oversized bodies
     * with no Content-Length (and is what Http::fake() exercises).
     */
    private function streamToFile(Response $response, string $tempPath): void
    {
        $announced = $response->header('Content-Length');

        if ($announced !== '' && (int) $announced > self::MAX_BYTES) {
            throw new RemoteImageException($this->tooLargeMessage());
        }

        $body = $response->toPsrResponse()->getBody();

        if ($body->isSeekable()) {
            $body->rewind();
        }

        $handle = fopen($tempPath, 'wb');

        if ($handle === false) {
            throw new RemoteImageException('Could not write the downloaded image.');
        }

        $total = 0;

        try {
            while (!$body->eof()) {
                $chunk = $body->read(65536);

                if ($chunk === '') {
                    break;
                }

                $total += strlen($chunk);

                if ($total > self::MAX_BYTES) {
                    throw new RemoteImageException($this->tooLargeMessage());
                }

                fwrite($handle, $chunk);
            }
        } finally {
            fclose($handle);
        }

        if ($total === 0) {
            throw new RemoteImageException('The URL returned an empty response.');
        }
    }

    /**
     * Determine the real image type from the downloaded bytes — never from
     * the URL extension or the Content-Type header.
     */
    private function sniffMime(string $path): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($path) ?: '';

        // getimagesize() must agree, which rules out e.g. an HTML page or a
        // polyglot that only carries an image signature
        $info = @getimagesize($path);
        $imageMime = $info['mime'] ?? '';

        if (!isset(self::ALLOWED_MIMES[$mime]) || $imageMime !== $mime) {
            throw new RemoteImageException('The URL did not return a supported image (jpg, jpeg, png, gif, webp).');
        }

        return $mime;
    }

    /**
     * Build a client-style original file name from the last path segment of
     * the URL, sanitized and forced to the sniffed extension; falls back to a
     * generated name when the path gives nothing usable.
     */
    private function originalName(string $url, string $extension): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $segment = rawurldecode(basename($path));
        $base = pathinfo($segment, PATHINFO_FILENAME);

        $slug = Str::of($base)->ascii()->replaceMatches('/[^A-Za-z0-9._-]+/', '-')->trim('-._')->limit(80, '')->toString();

        if ($slug === '') {
            $slug = 'remote-'.Str::random(12);
        }

        return $slug.'.'.$extension;
    }

    /** Default resolver: A and AAAA records for the host. */
    private function resolveHost(string $host): array
    {
        $addresses = [];

        foreach (@dns_get_record($host, DNS_A | DNS_AAAA) ?: [] as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;

            if ($ip !== null) {
                $addresses[] = $ip;
            }
        }

        // dns_get_record ignores /etc/hosts; gethostbynamel covers that case
        if ($addresses === []) {
            $addresses = gethostbynamel($host) ?: [];
        }

        return array_values(array_unique($addresses));
    }

    private function tooLargeMessage(): string
    {
        return sprintf('The image is larger than the %d MB limit.', self::MAX_BYTES / 1024 / 1024);
    }
}
