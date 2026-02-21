<?php

namespace App\Auth;

use Illuminate\Auth\SessionGuard;
use Symfony\Component\HttpFoundation\Cookie;

class RememberMeSessionGuard extends SessionGuard
{
    // 1 year in minutes
    const REMEMBER_DURATION_MINUTES = 365 * 24 * 60;

    protected function createRecallerCookie($user): Cookie
    {
        return $this->cookieJar->make(
            $this->getRecallerName(),
            $user->getAuthIdentifier() . '|' . $user->getRememberToken() . '|' . $user->getAuthPassword(),
            self::REMEMBER_DURATION_MINUTES
        );
    }
}
