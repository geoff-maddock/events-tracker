<?php

namespace App\View;

/**
 * The aria attributes of a form control inside an x-ui.form-group (#2411):
 * the error or help text that describes it, and whether it is invalid or
 * required. The group renders those texts with the ids used here.
 */
class FieldState
{
    /**
     * @return array<string, string>
     */
    public static function attributes(?string $id, bool $hasError, mixed $error, mixed $helpText, mixed $required): array
    {
        $attributes = [];

        if ($hasError || filled($error)) {
            $attributes['aria-invalid'] = 'true';
        }

        if ($id && filled($error)) {
            $attributes['aria-describedby'] = $id . '-error';
        } elseif ($id && filled($helpText)) {
            $attributes['aria-describedby'] = $id . '-help';
        }

        if ($required) {
            $attributes['aria-required'] = 'true';
        }

        return $attributes;
    }
}
