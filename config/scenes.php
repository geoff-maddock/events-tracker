<?php

/*
|--------------------------------------------------------------------------
| Scene hub pages (/scenes/{slug})
|--------------------------------------------------------------------------
|
| Config-driven v1 for issue #2002 §4 — no database model, no migration.
| Each entry powers one curated hub page combining upcoming events, key
| entities, and active series for a genre "scene" so it can rank for
| genre-demand searches that a bare tag listing doesn't.
|
| Keys:
|   icon        Bootstrap Icons class used as the scene's visual fallback
|               when no upcoming event photo is available for the hero image.
|   name        Display name (nav/footer/cards), e.g. "Raves & EDM"
|   title       SEO <title> (layout appends " | " . config('app.app_name'))
|   description Meta description base sentence; the controller appends
|               live public event/venue counts.
|   tags        Real tag slugs (App\Models\Tag.slug) that define the scene.
|               Verified against database/initialize/*.sql — the dev/test
|               DB's TagsTableSeeder only carries 3 rows, so these were
|               checked against the production tag export, not tinker.
|
| ScenesController::show() 404s for any slug not present here. The sitemap
| generator loops these keys — never hardcode a scene slug outside this file.
|
*/

return [

    'rave-edm' => [
        'icon' => 'bi-music-note-beamed',
        'name' => 'Raves & EDM',
        'title' => 'Raves, EDM & Club Nights in Pittsburgh — Upcoming Events & Parties',
        'description' => 'Upcoming raves, techno and house parties, and EDM club nights in Pittsburgh.',
        'tags' => ['rave', 'techno', 'house', 'edm', 'electronic'],
    ],

    'punk-hardcore' => [
        'icon' => 'bi-lightning-fill',
        'name' => 'Punk & Hardcore',
        'title' => 'Punk & Hardcore Shows in Pittsburgh — Upcoming Events',
        'description' => 'Upcoming punk and hardcore shows in Pittsburgh, from DIY basement gigs to club bills.',
        'tags' => ['punk', 'hardcore'],
    ],

    'goth-industrial' => [
        'icon' => 'bi-moon-stars-fill',
        'name' => 'Goth & Industrial',
        'title' => 'Goth & Industrial Nights in Pittsburgh — Upcoming Events',
        'description' => 'Upcoming goth, industrial and darkwave nights in Pittsburgh.',
        'tags' => ['goth', 'industrial', 'darkwave'],
    ],

    'drum-and-bass' => [
        'icon' => 'bi-vinyl-fill',
        'name' => 'Drum & Bass',
        'title' => 'Drum & Bass & Jungle Nights in Pittsburgh — Upcoming Events',
        'description' => 'Upcoming drum and bass and jungle nights in Pittsburgh.',
        'tags' => ['drum-and-bass', 'jungle'],
    ],

    'experimental-noise' => [
        'icon' => 'bi-soundwave',
        'name' => 'Experimental & Noise',
        'title' => 'Experimental & Noise Shows in Pittsburgh — Upcoming Events',
        'description' => 'Upcoming experimental, noise and ambient shows in Pittsburgh.',
        'tags' => ['experimental', 'noise', 'ambient'],
    ],

];
