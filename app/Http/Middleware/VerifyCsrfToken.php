<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // browsers send CSP violation reports without a token
        'csp-report',
        // api/* is no longer excluded (#2166): token and basic-auth API calls aren't
        // session requests, so Sanctum never applies this to them; only session calls
        // from the site's own (stateful) domains are checked.
    ];
}
