<?php
return [
    'name' => "Event Repo",
    'title' => "Event Repo",
    'subtitle' => 'A guide and calender of events, weekly and monthly series, promoters, artists, producers, djs, venues and other entities.',
    'description' => 'A guide and calender of events, weekly and monthly series, promoters, artists, producers, djs, venues and other entities.',
    'author' => 'Geoff Maddock',
    'page_image' => 'photos/tn-1485825995-icon-publicentities-big.png',
    'posts_per_page' => 10,
    'rss_size' => 25,
    'contact_email' => env('APP_FEEDBACK_EMAIL'),
    'uploads' => [
        'storage' => 'local',
        'webpath' => '/uploads/',
    ],
];