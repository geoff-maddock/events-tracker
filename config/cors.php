<?php

/*
 | CORS origins.
 |
 | Production origins are always allowed. Development / preview origins
 | (localhost, plain-http, and Vercel preview deployments) are only added
 | outside production, so a permissive policy never ships to prod. Additional
 | origins can be supplied per-environment via CORS_EXTRA_ORIGINS
 | (comma-separated). supports_credentials stays false, so these origins
 | cannot be used to ride a session cookie.
 */

$prodOrigins = [
    env('APP_URL', 'https://arcane.city'),
    'https://dev.arcane.city',
    'https://beta.arcane.city',
    'https://arcane-city-frontend.vercel.app',
    'https://api.arcane.city',
];

$devOrigins = [
    'http://beta.arcane.city',
    'http://api.arcane.city',
    'http://localhost:3000',
    'http://localhost:5173',
];

// Note: a literal '*' in allowed_origins is matched verbatim and never fires;
// wildcards must be regexes in allowed_origins_patterns.
$devOriginPatterns = [
    '#^https://.*geoffmaddocks-projects\.vercel\.app$#',
];

$extraOrigins = array_values(array_filter(array_map('trim', explode(',', (string) env('CORS_EXTRA_ORIGINS', '')))));

$isProduction = env('APP_ENV', 'production') === 'production';

return [
    'paths' => ['api/*'],
    'allowed_origins' => array_values(array_unique(array_merge(
        $prodOrigins,
        $isProduction ? [] : $devOrigins,
        $extraOrigins,
    ))),
    'allowed_origins_patterns' => $isProduction ? [] : $devOriginPatterns,
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'allowed_headers' => ['Content-Type', 'Authorization'],
    'exposed_headers' => [],
    'max_age' => 3600,
    'supports_credentials' => false,
];
