<?php

/*
|--------------------------------------------------------------------------
| Site Modules
|--------------------------------------------------------------------------
|
| Authoritative list of the site's top-level pages ("modules"), grouped by
| the access level required to see them. Consumed by the All Modules page
| (resources/views/pages/all-modules-tw.blade.php) and by search
| (App\Services\ModuleRegistry / App\Services\SearchService) so the two
| never drift.
|
| Access groups:
|   public  - visible to everyone
|   auth    - visible to any authenticated user
|   policy  - informational pages, visible to everyone
|   admin   - visible only to users in the "admin" group
|
| Each module: name, url, icon (Bootstrap Icons class), description.
|
*/

return [
    'public' => [
        ['name' => 'Activity', 'url' => '/activity', 'icon' => 'bi-activity', 'description' => 'View recent site activity'],
        ['name' => 'Calendar', 'url' => '/calendar', 'icon' => 'bi-calendar3', 'description' => 'Browse events by calendar'],
        ['name' => 'Entities', 'url' => '/entities', 'icon' => 'bi-people', 'description' => 'Artists, venues, promoters'],
        ['name' => 'Events', 'url' => '/events', 'icon' => 'bi-calendar-event', 'description' => 'Concerts and club nights'],
        ['name' => 'Photos', 'url' => '/photos', 'icon' => 'bi-images', 'description' => 'Browse event photos'],
        ['name' => 'Popular', 'url' => '/popular', 'icon' => 'bi-graph-up-arrow', 'description' => 'Popular events, entities, and tags'],
        ['name' => 'Posts', 'url' => '/posts', 'icon' => 'bi-file-text', 'description' => 'Community posts'],
        ['name' => 'Reviews', 'url' => '/reviews', 'icon' => 'bi-star', 'description' => 'Event reviews'],
        ['name' => 'Search', 'url' => '/search', 'icon' => 'bi-search', 'description' => 'Search events, entities, series, and more'],
        ['name' => 'Series', 'url' => '/series', 'icon' => 'bi-collection', 'description' => 'Recurring event series'],
        ['name' => 'Tags', 'url' => '/tags', 'icon' => 'bi-tags', 'description' => 'Browse by tags'],
        ['name' => 'Threads', 'url' => '/threads', 'icon' => 'bi-chat-dots', 'description' => 'Forum discussions'],
        ['name' => 'Users', 'url' => '/users', 'icon' => 'bi-person', 'description' => 'Community members'],
    ],

    'auth' => [
        ['name' => 'Notifications', 'url' => '/job-status', 'icon' => 'bi-bell', 'description' => 'Background jobs and notifications'],
    ],

    'policy' => [
        ['name' => 'Privacy Policy', 'url' => '/privacy', 'icon' => 'bi-shield-check', 'description' => 'How we handle your data'],
        ['name' => 'Terms of Service', 'url' => '/tos', 'icon' => 'bi-file-earmark-text', 'description' => 'Terms and conditions of use'],
        ['name' => 'About', 'url' => '/about', 'icon' => 'bi-info-circle', 'description' => 'About this site'],
        ['name' => 'Help', 'url' => '/help', 'icon' => 'bi-question-circle', 'description' => 'How to use the site'],
    ],

    'admin' => [
        ['name' => 'Activity Graph', 'url' => '/activity/graph', 'icon' => 'bi-activity', 'description' => 'Visualize and export activity trends'],
        ['name' => 'Blogs', 'url' => '/blogs', 'icon' => 'bi-journal-text', 'description' => 'Manage blog posts'],
        ['name' => 'Categories', 'url' => '/categories', 'icon' => 'bi-folder', 'description' => 'Manage forum categories'],
        ['name' => 'Entity Types', 'url' => '/entity-types', 'icon' => 'bi-diagram-3', 'description' => 'Manage entity types'],
        ['name' => 'Forums', 'url' => '/forums', 'icon' => 'bi-chat-square-text', 'description' => 'Manage forum sections'],
        ['name' => 'Groups', 'url' => '/groups', 'icon' => 'bi-people-fill', 'description' => 'Manage user groups'],
        ['name' => 'Menus', 'url' => '/menus', 'icon' => 'bi-menu-button-wide', 'description' => 'Manage navigation menus'],
        ['name' => 'Permissions', 'url' => '/permissions', 'icon' => 'bi-key', 'description' => 'Manage permissions'],
        ['name' => 'Roles', 'url' => '/roles', 'icon' => 'bi-person-badge', 'description' => 'Manage entity roles'],
    ],
];
