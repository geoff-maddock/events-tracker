<?php

namespace App\Rules;

use App\Models\Link;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A link URL must be an http(s) URL once normalized: scheme-less input such as
 * "www.example.com" is accepted (Link stores it as https://…), any other
 * scheme is rejected (#2220).
 */
class LinkUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $url = Link::normalizeUrl(is_string($value) ? $value : null);

        if (!Link::isWebUrl($url) || mb_strlen((string) $url) > 255) {
            $fail('The :attribute must be a web address starting with http:// or https://.');
        }
    }
}
