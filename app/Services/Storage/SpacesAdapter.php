<?php

namespace App\Services\Storage;

use Illuminate\Filesystem\AwsS3V3Adapter;

/**
 * The S3 disk for DigitalOcean Spaces, building public URLs by hand.
 *
 * Without a configured base URL, Laravel asks the AWS SDK for every URL, which
 * costs about a millisecond each; a list page asks for dozens. With one,
 * Laravel joins the base and the path as they are, which leaves spaces and
 * other characters in older file names unencoded. This does the join with each
 * path segment encoded, which is exactly what the SDK returns.
 *
 * The base is the disk's `url` setting, or else the bucket's own host under the
 * endpoint (https://<bucket>.<region>.digitaloceanspaces.com). Any setup where
 * the SDK might not use that host is left to the SDK.
 */
class SpacesAdapter extends AwsS3V3Adapter
{
    private string|false|null $baseUrl = null;

    public function url($path)
    {
        $base = $this->baseUrl ??= $this->resolveBaseUrl();
        $key = $this->prefixer->prefixPath($path);

        // an empty path or one with empty or dot segments: let the SDK decide
        if ($base === false || preg_match('#(^|/)\.{0,2}(/|$)#', $key)) {
            return parent::url($path);
        }

        return $base.'/'.implode('/', array_map('rawurlencode', explode('/', $key)));
    }

    private function resolveBaseUrl(): string|false
    {
        if (!empty($this->config['url'])) {
            return rtrim($this->config['url'], '/');
        }

        $bucket = (string) ($this->config['bucket'] ?? '');
        $endpoint = parse_url(rtrim((string) ($this->config['endpoint'] ?? ''), '/'));
        $host = $endpoint['host'] ?? '';

        $plainEndpoint = is_array($endpoint)
            && in_array($endpoint['scheme'] ?? '', ['http', 'https'], true)
            && array_keys($endpoint) === ['scheme', 'host']
            && preg_match('/^[a-z0-9-]+(\.[a-z0-9-]+)+$/', $host)
            && !filter_var($host, FILTER_VALIDATE_IP)
            && !str_starts_with($host, $bucket.'.');

        if (!$plainEndpoint
            || !empty($this->config['use_path_style_endpoint'])
            || !preg_match('/^[a-z0-9][a-z0-9-]{1,61}[a-z0-9]$/', $bucket)) {
            return false;
        }

        return $endpoint['scheme'].'://'.$bucket.'.'.$host;
    }
}
