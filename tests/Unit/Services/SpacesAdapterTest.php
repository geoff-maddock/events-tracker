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
    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function diskConfig(array $overrides = []): array
    {
        return $overrides + [
            'driver' => 'spaces',
            'key' => 'key',
            'secret' => 'secret',
            'endpoint' => 'https://nyc3.digitaloceanspaces.com',
            'region' => 'nyc3',
            'bucket' => 'a-bucket',
            'root' => 'prod',
            'url' => null,
        ];
    }

    /**
     * The URL from the spaces driver and from Laravel's plain s3 driver (the SDK).
     *
     * @param array<string, mixed> $overrides
     * @return array{string, string}
     */
    private function bothUrls(string $path, array $overrides = []): array
    {
        config([
            'filesystems.disks.spaces_fast' => $this->diskConfig($overrides),
            'filesystems.disks.spaces_sdk' => $this->diskConfig(['driver' => 's3'] + $overrides),
        ]);
        Storage::forgetDisk(['spaces_fast', 'spaces_sdk']);

        $fast = Storage::disk('spaces_fast');
        $this->assertInstanceOf(SpacesAdapter::class, $fast);

        return [Storage::disk('spaces_sdk')->url($path), $fast->url($path)];
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
            'empty' => [''],
            'leading slash' => ['/photos/a.jpg'],
            'double slash' => ['photos//a.jpg'],
            'dot segment' => ['photos/./a.jpg'],
            'parent segment' => ['photos/../a.jpg'],
            'trailing slash' => ['photos/'],
        ];
    }

    #[DataProvider('paths')]
    public function test_urls_match_what_the_sdk_builds(string $path): void
    {
        [$sdk, $fast] = $this->bothUrls($path);

        $this->assertSame($sdk, $fast);
    }

    public function test_the_default_base_is_the_buckets_own_host(): void
    {
        [$sdk, $fast] = $this->bothUrls('photos/a b.webp', ['bucket' => '123media']);

        $this->assertSame('https://123media.nyc3.digitaloceanspaces.com/prod/photos/a%20b.webp', $fast);
        $this->assertSame($sdk, $fast);
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function setupsLeftToTheSdk(): array
    {
        return [
            'dotted bucket' => [['bucket' => 'media.example.test']],
            'uppercase bucket' => [['bucket' => 'My_Bucket']],
            'ip endpoint' => [['endpoint' => 'http://127.0.0.1:9000']],
            'localhost endpoint' => [['endpoint' => 'http://localhost:9000']],
            'path style' => [['use_path_style_endpoint' => true]],
            'endpoint with a path' => [['endpoint' => 'https://nyc3.digitaloceanspaces.com/base']],
            'trailing slash endpoint' => [['endpoint' => 'https://nyc3.digitaloceanspaces.com/']],
        ];
    }

    /** @param array<string, mixed> $overrides */
    #[DataProvider('setupsLeftToTheSdk')]
    public function test_other_setups_match_the_sdk(array $overrides): void
    {
        [$sdk, $fast] = $this->bothUrls('photos/a b (1).webp', $overrides);

        $this->assertSame($sdk, $fast);
    }

    public function test_a_configured_base_url_is_used_as_given(): void
    {
        config(['filesystems.disks.spaces_cdn' => $this->diskConfig(['url' => 'https://cdn.example.test/'])]);

        $this->assertSame(
            'https://cdn.example.test/prod/photos/a%20b.webp',
            Storage::disk('spaces_cdn')->url('photos/a b.webp')
        );
    }

    public function test_the_external_disk_uses_the_adapter(): void
    {
        config(['filesystems.disks.external.bucket' => 'a-bucket', 'filesystems.disks.external.region' => 'nyc3']);
        Storage::forgetDisk('external');

        $this->assertInstanceOf(SpacesAdapter::class, Storage::disk('external'));
    }
}
