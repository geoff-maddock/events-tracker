<?php

namespace App\Services;

use Illuminate\Support\Str;

class StringHelper
{
    /**
     * Converts a slug into a name.
     */
    public function SlugToName(string $slug): string
    {
        return Str::title(str_replace('-', ' ', $slug));
    }
}
