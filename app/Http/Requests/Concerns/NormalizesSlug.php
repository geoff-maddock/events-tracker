<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Support\Str;

/**
 * The writer stores a slug through Str::slug(), which drops leading, trailing
 * and doubled dashes: `-karma-` is saved as `karma`. Doing the same before
 * validation makes the unique rule check the value that will be stored, so a
 * taken slug is a form error and not a failed insert against the unique index.
 */
trait NormalizesSlug
{
    protected function prepareForValidation(): void
    {
        $slug = $this->input('slug');

        // anything else is left for the format rule to refuse as it is
        if (is_string($slug) && 1 === preg_match('/^[a-z0-9-]+$/', $slug)) {
            $this->merge(['slug' => Str::slug($slug)]);
        }
    }
}
