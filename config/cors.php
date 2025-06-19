<?php
return [
    'paths' => ['api/*'],
    'allowed_origins' => [env('FRONTEND_URL', 'https://arcane.city'),'https://dev.arcane.city','http://localhost:3000','http://localhost:5173','https://arcane-city-frontend.vercel.app','https://*geoffmaddocks-projects.vercel.app'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'allowed_headers' => ['Content-Type', 'Authorization'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];