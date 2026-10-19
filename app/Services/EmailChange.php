<?php

namespace App\Services;

/**
 * An email change that EmailChangeVerifier::prepare() found needs a new
 * verification, carried to notify() once the update is saved (#2321).
 */
final readonly class EmailChange
{
    /**
     * @param list<string> $limiterKeys rate limiter keys to record once the change is saved
     */
    public function __construct(
        public string $oldEmail,
        public bool $oldEmailWasVerified,
        public array $limiterKeys = [],
    ) {
    }
}
