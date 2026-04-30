<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Provider
    |--------------------------------------------------------------------------
    |
    | The AI provider to use for features like flyer analysis.
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
        'model' => env('ANTHROPIC_MODEL', 'claude-opus-4-5'),
        'max_tokens' => 2048,
    ],

    /*
    |--------------------------------------------------------------------------
    | Flyer Analysis System Prompt
    |--------------------------------------------------------------------------
    |
    | The system prompt used when analysing an event flyer image.
    |
    */

    'flyer_system_prompt' => 'You are an event data extraction assistant for an event calendar system focused on music and arts events. '
        . 'Your task is to analyse the provided event flyer image and extract all available information. '
        . 'After extracting from the flyer, use your knowledge to research and supplement any missing details. '
        . 'Extract all of the info about the event from the flyer including date, location, performers, time, styles and any other relevant info. '
        . 'Unless the flyer explicitly states otherwise, assume the event is happening in the current year, which is 2026.'
        . 'Use that to research more info on the event and then look at the schema for the event and attempt to fill in as much info as possible. '
        . 'Make an educated guess, but no wild guess or overreach. '
        . 'Everything generally will be in Pittsburgh PA unless the flyer indicates otherwise.'
        . 'Where possible, research bandcamp or soundcloud URLs for any performers and include in the description.  only add if you can confidently verify it is correct.'
        , 'Once the info is collected, re-write the description in a clear, readable format suitable for an event listing with accurate info, all relevant details included.',

    'flyer_user_prompt' => 'Please analyse this event flyer and return a JSON object with the following fields. '
        . 'Return ONLY valid JSON with no markdown fences or extra text. '
        . 'Use null for any field you cannot confidently determine. '
        . 'Keys required: '
        . '"name" (string, event title, do not include the venue unless it is an essential part of the event title), '
        . '"slug" (string, lowercase hyphenated version of name), '
        . '"short" (string, one concise sentence describing the event), '
        . '"description" (string, full description including all relevant info), '
        . '"start_at" (string, ISO 8601 datetime YYYY-MM-DDTHH:MM or null), '
        . '"end_at" (string, ISO 8601 datetime YYYY-MM-DDTHH:MM or null), '
        . '"door_at" (string, ISO 8601 datetime for door open time YYYY-MM-DDTHH:MM or null) - only set the door_at if it is not the same as start_at, '
        . '"venue_name" (string, venue name or null), '
        . '"promoter_name" (string, promoter or organiser name or null), '
        . '"event_type_name" (string, best matching event type from: Concert, Club Night, Festival, DJ Set, Art Show, Comedy, Theater, Workshop, Film, Other — or null if unclear), '
        . '"presale_price" (number, presale ticket price without currency symbol or null), '
        . '"door_price" (number, door ticket price without currency symbol or null), '
        . '"min_age" (number, 0 for all ages / 18 / 21), '
        . '"primary_link" (string, URL to event page or null), '
        . '"ticket_link" (string, URL to purchase tickets or null), '
        . '"related_entities" (array of strings, names of performers/artists/DJs/bands/venues) - capitalize them, '
        . '"tag_list" (array of strings, genre and style tags such as "electronic", "hip-hop", "jazz") - capitalize them',

];
