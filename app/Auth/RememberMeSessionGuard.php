<?php

namespace App\Auth;

use Illuminate\Auth\SessionGuard;

class RememberMeSessionGuard extends SessionGuard
{
    // 1 year in minutes
    const REMEMBER_DURATION_MINUTES = 365 * 24 * 60;

    protected function getRememberDuration(): int
    {
        return self::REMEMBER_DURATION_MINUTES;
    }
}
