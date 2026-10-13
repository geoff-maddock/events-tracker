<?php

namespace App\Rules;

use App\Services\Signup\SignupFormToken;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The register form's timing token must be genuine and old enough (#2312).
 * The message is deliberately vague so a bot learns nothing from it.
 */
class SignupFormSubmission implements ValidationRule
{
    public const MESSAGE = 'Something went wrong with your sign-up. Please try again.';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!SignupFormToken::isAcceptable($value)) {
            $fail(self::MESSAGE);
        }
    }
}
