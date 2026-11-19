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
 */
class SpacesAdapter extends AwsS3V3Adapter
{
    public function url($path)
    {
        if (!isset($this->config['url'])) {
            return parent::url($path);
        }

        $segments = explode('/', ltrim($this->prefixer->prefixPath($path), '/'));

        return rtrim($this->config['url'], '/').'/'.implode('/', array_map('rawurlencode', $segments));
    }
}
