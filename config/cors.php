<?php
return [
    'paths' => ['api/*'],
    'allowed_origins' => [env('APP_URL', 'https://arcane.city'),'https://dev.arcane.city','http://beta.arcane.city','https://beta.arcane.city','http://localhost:3000','http://localhost:5173','https://arcane-city-frontend.vercel.app','https://*geoffmaddocks-projects.vercel.app', 'http://api.arcane.city', 'https://api.arcane.city'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'allowed_headers' => ['Content-Type', 'Authorization'],
    'exposed_headers' => [],
    'max_age' => 3600,
    'supports_credentials' => false,
];
