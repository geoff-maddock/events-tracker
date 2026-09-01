<?php

/*
|--------------------------------------------------------------------------
| Shared prompt fragments
|--------------------------------------------------------------------------
|
| Built as variables first so the per-context prompts below can compose them
| and so the legacy `flyer_*_prompt` aliases can point at the event pair.
|
*/

// NOTE: do not put a date or year in these prompts. Config is cached at deploy
// time, so anything computed here freezes until the next deploy. The current
// date is prefixed to the system prompt per request by
// ImageAnalysisService::datedPrompt().

// The event types actually seeded by database/seeders/EventTypesTableSeeder.php.
// Offering the model anything outside this list guarantees a failed match.
$eventTypes = 'Art Opening, Concert, Festival, House Show, Club Night, Film Screening, '
    . 'Radio Show, Rave, Benefit, Renegade, Pop-up, Activism, Open Mic, Karaoke, '
    . 'Workshop, Live Stream, Comedy';

$localContext = 'Everything generally will be in Pittsburgh PA unless the image indicates otherwise. ';

$researchGuidance = 'After extracting what is visible, use your knowledge to research and supplement missing details. '
    . 'Make an educated guess, but no wild guesses or overreach. '
    . 'Where possible, research bandcamp or soundcloud URLs and include them in the description, '
    . 'but only add one if you can confidently verify it is correct. ';

$jsonGuidance = 'Return ONLY valid JSON with no markdown fences or extra text. '
    . 'Use null for any field you cannot confidently determine. ';

/*
|--------------------------------------------------------------------------
| Event
|--------------------------------------------------------------------------
*/

$eventSystemPrompt = 'You are an event data extraction assistant for an event calendar system focused on music and arts events. '
    . 'Your task is to analyse the provided event flyer image and extract all available information. '
    . $researchGuidance
    . 'Extract all of the info about the event from the flyer including date, location, performers, time, styles and any other relevant info. '
    . 'Unless the flyer explicitly states otherwise, assume the event is the next upcoming occurrence on or after today. '
    . $localContext
    . 'Once the info is collected, re-write the description in a clear, readable format suitable for an event listing, '
    . 'with accurate info and all relevant details included.';

$eventUserPrompt = 'Please analyse this event flyer and return a JSON object with the following fields. '
    . $jsonGuidance
    . 'Keys required: '
    . '"name" (string, event title, do not include the venue unless it is an essential part of the event title), '
    . '"slug" (string, lowercase hyphenated version of name), '
    . '"short" (string, one concise sentence describing the event), '
    . '"description" (string, full description including all relevant info), '
    . '"start_at" (string, ISO 8601 datetime YYYY-MM-DDTHH:MM or null), '
    . '"end_at" (string, ISO 8601 datetime YYYY-MM-DDTHH:MM or null), '
    . '"door_at" (string, ISO 8601 datetime for door open time YYYY-MM-DDTHH:MM or null) - only set door_at if it differs from start_at, '
    . '"venue_name" (string, venue name or null), '
    . '"promoter_name" (string, promoter or organiser name or null), '
    . '"event_type_name" (string, best matching event type from: ' . $eventTypes . ' — or null if unclear), '
    . '"presale_price" (number, presale ticket price without currency symbol or null), '
    . '"door_price" (number, door ticket price without currency symbol or null), '
    . '"min_age" (number, 0 for all ages / 18 / 21), '
    . '"primary_link" (string, URL to event page or null), '
    . '"ticket_link" (string, URL to purchase tickets or null), '
    . '"related_entities" (array of strings, names of performers/artists/DJs/bands/venues) - capitalize them, '
    . '"tag_list" (array of strings, genre and style tags such as "electronic", "hip-hop", "jazz") - capitalize them';

/*
|--------------------------------------------------------------------------
| Entity
|--------------------------------------------------------------------------
|
| An entity image is usually a logo, band photo, venue exterior or profile
| picture rather than a text-rich flyer, so identification comes first and
| an honest "I don't know" is preferred over an invented entity.
|
*/

$entitySystemPrompt = 'You are a music and arts scene research assistant for an event calendar system. '
    . 'The provided image depicts an entity: a venue, a band or artist, a promoter, a DJ, a producer, or a shop. '
    . 'It is usually a logo, a band photo, a venue exterior, a poster or a profile picture rather than an event flyer. '
    . 'First identify the subject, reading any text, logo or signage present in the image. '
    . 'Then use your knowledge to research the subject and fill in as much of the schema as you can. '
    . $localContext
    . $researchGuidance
    . 'IMPORTANT: if you cannot confidently identify the subject, return null for "name" and leave the other '
    . 'fields null rather than inventing an entity. A confident "unknown" is far more useful than a plausible guess.';

$entityUserPrompt = 'Please analyse this image of a music or arts entity and return a JSON object with the following fields. '
    . $jsonGuidance
    . 'Keys required: '
    . '"name" (string, the entity name, or null if you cannot confidently identify it), '
    . '"slug" (string, lowercase hyphenated version of name), '
    . '"short" (string, one concise sentence describing the entity, max 255 characters), '
    . '"description" (string, full description including history, style and anything notable), '
    . '"entity_type_name" (string, exactly one of: Space, Group, Individual, Interest — '
    . 'Space for a venue or physical place, Group for a band or collective, Individual for a solo artist or DJ, '
    . 'Interest for a genre, scene or topic — or null if unclear), '
    . '"started_at" (string, date the entity was founded or opened, format YYYY-MM-DD, or null. '
    . 'The date MUST fall between 1971-01-01 and 2037-12-31; return null rather than a date outside that range), '
    . '"facebook_username" (string, the facebook handle only, NOT a full URL, or null), '
    . '"instagram_username" (string, the instagram handle only, NOT a full URL, or null), '
    . '"role_list" (array of strings, any that apply from: Venue, Artist, Producer, DJ, Promoter, Shop, Band), '
    . '"tag_list" (array of strings, genre and style tags such as "Electronic", "Hip-Hop", "Jazz") - capitalize them, '
    . '"alias_list" (array of strings, other or former names and common alternate spellings for this entity), '
    . '"identified_as" (string, a short plain-language statement of who or what you concluded this is, or null), '
    . '"confidence" (string, exactly one of: high, medium, low — how confident you are in the identification)';

/*
|--------------------------------------------------------------------------
| Series
|--------------------------------------------------------------------------
|
| The event contract plus recurrence. The worked examples matter more than
| the field descriptions for getting the occurrence fields right.
|
*/

$seriesSystemPrompt = 'You are an event data extraction assistant for an event calendar system focused on music and arts events. '
    . 'The provided image advertises a RECURRING event series — a weekly club night, a monthly showcase, a residency — '
    . 'rather than a single one-off event. '
    . 'Your task is to extract both the details of the series and, crucially, its recurrence schedule. '
    . $researchGuidance
    . 'Unless the image explicitly states otherwise, assume the series is currently running. '
    . $localContext
    . 'Once the info is collected, re-write the description in a clear, readable format suitable for a series listing, '
    . 'with accurate info and all relevant details included.';

$seriesUserPrompt = 'Please analyse this recurring event series image and return a JSON object with the following fields. '
    . $jsonGuidance
    . 'Keys required: '
    . '"name" (string, the series title, do not include the venue unless it is an essential part of the title), '
    . '"slug" (string, lowercase hyphenated version of name), '
    . '"short" (string, one concise sentence describing the series), '
    . '"description" (string, full description including all relevant info), '
    . '"event_type_name" (string, best matching event type from: ' . $eventTypes . ' — or null if unclear), '
    . '"venue_name" (string, venue name or null), '
    . '"promoter_name" (string, promoter or organiser name or null), '
    . '"founded_at" (string, ISO 8601 datetime YYYY-MM-DDTHH:MM when the series started, or null), '
    . '"soundcheck_at" (string, ISO 8601 datetime YYYY-MM-DDTHH:MM or null), '
    . '"door_at" (string, ISO 8601 datetime YYYY-MM-DDTHH:MM or null), '
    . '"start_at" (string, ISO 8601 datetime YYYY-MM-DDTHH:MM or null), '
    . '"end_at" (string, ISO 8601 datetime YYYY-MM-DDTHH:MM or null), '
    . 'For soundcheck_at, door_at, start_at and end_at return the datetime of the next occurrence of the series '
    . 'that falls ON OR AFTER today\'s date, so the time of day is correct and the date is a sensible anchor. '
    . 'Work the recurrence forward from today; never return a date earlier than today. '
    . '"length" (number, typical length of one occurrence in hours, or null), '
    . '"presale_price" (number, presale ticket price without currency symbol or null), '
    . '"door_price" (number, door ticket price without currency symbol or null), '
    . '"min_age" (number, 0 for all ages / 18 / 21), '
    . '"primary_link" (string, URL to the series page or null), '
    . '"ticket_link" (string, URL to purchase tickets or null), '
    . '"facebook_username" (string, handle only, NOT a full URL, or null), '
    . '"instagram_username" (string, handle only, NOT a full URL, or null), '
    . '"twitter_username" (string, handle only, NOT a full URL, or null), '
    . '"occurrence_type_name" (string, exactly one of: No Schedule, Weekly, Biweekly, Monthly, Bimonthly, Yearly), '
    . '"occurrence_week_name" (string, exactly one of: First, Second, Third, Fourth, Last — '
    . 'required when occurrence_type_name is Monthly or Bimonthly, otherwise null), '
    . '"occurrence_day_name" (string, exactly one of: Sunday, Monday, Tuesday, Wednesday, Thursday, Friday, Saturday — '
    . 'required when occurrence_type_name is Weekly, Biweekly, Monthly or Bimonthly, otherwise null), '
    . '"related_entities" (array of strings, names of resident performers/artists/DJs/bands) - capitalize them, '
    . '"tag_list" (array of strings, genre and style tags) - capitalize them. '
    . 'Worked examples for the recurrence fields: '
    . '"Every second Friday of the month" => occurrence_type_name "Monthly", occurrence_week_name "Second", occurrence_day_name "Friday". '
    . '"Thursdays" or "every Thursday" => occurrence_type_name "Weekly", occurrence_week_name null, occurrence_day_name "Thursday". '
    . '"Every other Saturday" => occurrence_type_name "Biweekly", occurrence_week_name null, occurrence_day_name "Saturday". '
    . '"1st and 3rd Saturday" => occurrence_type_name "Monthly", occurrence_week_name "First", occurrence_day_name "Saturday" '
    . '(pick the closest representable schedule). '
    . 'If no schedule can be determined => occurrence_type_name "No Schedule", the other two null.';

return [

    /*
    |--------------------------------------------------------------------------
    | AI Provider
    |--------------------------------------------------------------------------
    |
    | The AI provider to use for features like image analysis.
    | Supported: "anthropic"
    |
    */

    'provider' => env('AI_PROVIDER', 'anthropic'),

    /*
    |--------------------------------------------------------------------------
    | Anthropic (Claude) Configuration
    |--------------------------------------------------------------------------
    */

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY', ''),
        'api_url' => 'https://api.anthropic.com/v1/messages',
        'api_version' => '2023-06-01',
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-5'),
        // Adaptive thinking (Sonnet 5 / Opus 5) counts against max_tokens,
        // so leave headroom above the expected JSON payload size.
        'max_tokens' => 8192,
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Analysis Prompts
    |--------------------------------------------------------------------------
    |
    | One entry per analysis context. `keys` is the allowlist applied to the
    | model's JSON response — anything outside it is discarded before the
    | payload reaches the form, so prompt drift cannot introduce new fields.
    |
    | Keep these arrays string-keyed only. A stray comma instead of a "." in
    | the concatenations below would append a numerically-keyed element and
    | silently drop that text from the prompt (see ImageAnalysisServiceTest).
    |
    */

    'prompts' => [

        'event' => [
            'system' => $eventSystemPrompt,
            'user' => $eventUserPrompt,
            'keys' => [
                'name', 'slug', 'short', 'description',
                'start_at', 'end_at', 'door_at',
                'venue_name', 'promoter_name', 'event_type_name',
                'presale_price', 'door_price', 'min_age',
                'primary_link', 'ticket_link',
                'related_entities', 'tag_list',
            ],
        ],

        'entity' => [
            'system' => $entitySystemPrompt,
            'user' => $entityUserPrompt,
            'keys' => [
                'name', 'slug', 'short', 'description',
                'entity_type_name', 'started_at',
                'facebook_username', 'instagram_username',
                'role_list', 'tag_list', 'alias_list',
                'identified_as', 'confidence',
            ],
        ],

        'series' => [
            'system' => $seriesSystemPrompt,
            'user' => $seriesUserPrompt,
            'keys' => [
                'name', 'slug', 'short', 'description',
                'event_type_name', 'venue_name', 'promoter_name',
                'founded_at', 'soundcheck_at', 'door_at', 'start_at', 'end_at', 'length',
                'presale_price', 'door_price', 'min_age',
                'primary_link', 'ticket_link',
                'facebook_username', 'instagram_username', 'twitter_username',
                'occurrence_type_name', 'occurrence_week_name', 'occurrence_day_name',
                'related_entities', 'tag_list',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Deprecated prompt aliases
    |--------------------------------------------------------------------------
    |
    | Retained for one release so anything still reading the old flat keys
    | keeps working. Remove alongside the events.analyzeFlyer route alias.
    |
    */

    'flyer_system_prompt' => $eventSystemPrompt,
    'flyer_user_prompt' => $eventUserPrompt,

];
