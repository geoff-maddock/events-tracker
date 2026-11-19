<?php

namespace Tests\Unit\Services;

use App\Services\Storage\SpacesAdapter;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The external disk builds public URLs itself (#2387). They have to be the
 * URLs the AWS SDK produced before, encoded the same way, because 1 in 10
 * stored photo paths has spaces, brackets or other characters in it.
 */
class SpacesAdapterTest extends TestCase
{
    /** @return array<string, mixed> */
    private function diskConfig(?string $url): array
    {
        return [
            'driver' => 'spaces',
            'key' => 'key',
            'secret' => 'secret',
            'endpoint' => 'https://nyc3.digitaloceanspaces.com',
            'region' => 'nyc3',
            'bucket' => 'a-bucket',
            'root' => 'prod',
            'url' => $url,
        ];
    }

    /** @return array<string, array{string}> */
    public static function paths(): array
    {
        return [
            'plain' => ['photos/1700000000-flyer.webp'],
            'spaces and brackets' => ['photos/1456453201-12716295_o (1).jpg'],
            'plus, apostrophe, at' => ["photos/1-B+Z's @ Bar.jpg"],
            'hash, ampersand, comma' => ['photos/2-a#b&c,d=e!f~g:h.png'],
            'unicode' => ['photos/3-café · night’s.jpg'],
            'nested' => ['photos/2024/11/large one.webp'],
        ];
    }

    #[DataProvider('paths')]
    public function test_urls_match_what_the_sdk_builds(string $path): void
    {
        config([
            'filesystems.disks.spaces_fast' => $this->diskConfig('https://a-bucket.nyc3.digitaloceanspaces.com'),
            'filesystems.disks.spaces_sdk' => $this->diskConfig(null),
        ]);

        $fast = Storage::disk('spaces_fast');
        $this->assertInstanceOf(SpacesAdapter::class, $fast);

        $this->assertSame(Storage::disk('spaces_sdk')->url($path), $fast->url($path));
    }

    public function test_a_configured_base_url_is_used_as_given(): void
    {
        config(['filesystems.disks.spaces_cdn' => $this->diskConfig('https://cdn.example.test/')]);

        $this->assertSame(
            'https://cdn.example.test/prod/photos/a%20b.webp',
            Storage::disk('spaces_cdn')->url('photos/a b.webp')
        );
    }

    public function test_the_external_disk_uses_the_adapter(): void
    {
        $this->assertSame('spaces', config('filesystems.disks.external.driver'));
    }
}
