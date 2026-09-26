<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Send HSTS unconditionally — browsers only act on it over HTTPS, but
        // sending it over HTTP is harmless and avoids missing it when SSL is
        // terminated upstream (reverse proxy / load balancer) before reaching Laravel.
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        // report-only: violations are reported to /csp-report, nothing is blocked (#2166)
        if (config('csp.enabled') && !$response->headers->has('Content-Security-Policy-Report-Only')) {
            $response->headers->set('Content-Security-Policy-Report-Only', $this->reportOnlyPolicy());
        }

        return $response;
    }

    private function reportOnlyPolicy(): string
    {
        $directives = (array) config('csp.directives', []);
        $parts = [];

        foreach ($directives as $name => $sources) {
            $parts[] = trim($name.' '.implode(' ', (array) $sources));
        }

        $parts[] = 'report-uri '.route('csp.report', [], false);

        return implode('; ', $parts);
    }
}
