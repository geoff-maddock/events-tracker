<?php
return [
    'paths' => ['api/*'],
    'allowed_origins' => ['https://dev.arcane.city','https://arcane.city','http://localhost:3000'], // Replace with your React app's URL
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'allowed_headers' => ['Content-Type', 'Authorization'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];