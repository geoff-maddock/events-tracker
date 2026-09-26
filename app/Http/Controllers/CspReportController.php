<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Receives Content-Security-Policy-Report-Only violation reports (#2166).
 *
 * Browsers POST these without a session or CSRF token. The endpoint is
 * throttled and only logs a few trimmed fields, so it can't be used to write
 * arbitrary data anywhere but the log.
 */
class CspReportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true);
        $report = is_array($payload) ? ($payload['csp-report'] ?? $payload) : [];

        if (is_array($report) && $report !== []) {
            $field = fn (string $key) => Str::limit((string) ($report[$key] ?? ''), 300);

            Log::warning('CSP violation', [
                'directive' => $field('violated-directive') ?: $field('effective-directive'),
                'blocked' => $field('blocked-uri'),
                'document' => $field('document-uri'),
                'source' => $field('source-file'),
                'line' => $report['line-number'] ?? null,
            ]);
        }

        return response()->noContent();
    }
}
