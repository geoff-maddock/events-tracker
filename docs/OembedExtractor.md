# OembedExtractor Service

## Overview

The `OembedExtractor` service provides a way to extract embed HTML codes from audio platform URLs (SoundCloud and Bandcamp) using their oEmbed APIs.

## Features

- Extract embed codes from SoundCloud URLs using the SoundCloud oEmbed API
- Extract embed codes from Bandcamp URLs using the Bandcamp oEmbed API
- Support for multiple size configurations (small, medium, large)
- Methods to extract embeds from Entity, Event, and Series models

## Usage

### Basic Usage

```php
use App\Services\Embeds\OembedExtractor;

$extractor = new OembedExtractor();
$extractor->setLayout('medium'); // Options: 'small', 'medium', 'large'

// Extract embeds from URLs
$urls = [
    'https://soundcloud.com/user/track',
    'https://artist.bandcamp.com/track/song'
];

$embeds = $extractor->extractEmbedsFromUrls($urls);
```

### With Entity, Event, or Series

```php
use App\Services\Embeds\OembedExtractor;

$extractor = new OembedExtractor();

// For an entity
$embeds = $extractor->getEmbedsForEntity($entity, 'medium');

// For an event
$embeds = $extractor->getEmbedsForEvent($event, 'large');

// For a series
$embeds = $extractor->getEmbedsForSeries($series, 'small');
```

## Size Options

- **small**: Height of 42px
- **medium** (default): Height of 120px
- **large**: Height of 300px

## API Endpoints Used

### SoundCloud oEmbed API
- Endpoint: `https://soundcloud.com/oembed`
- Method: POST
- Parameters:
  - `format`: 'json'
  - `url`: The SoundCloud URL

### Bandcamp oEmbed API
- Endpoint: `https://bandcamp.com/EmbeddedPlayer/oembed`
- Method: POST
- Parameters:
  - `format`: 'json'
  - `url`: The Bandcamp URL

## Response Format

Both APIs return a JSON response with an `html` key containing the embed code. The service extracts this HTML and returns it in an array.

## Error Handling

- Returns `null` for individual URLs that fail to retrieve embed codes
- Returns an empty array if no valid embeds are found
- Times out after 10 seconds for each request
- Only processes HTTP 200 responses

## Differences from EmbedExtractor

The `OembedExtractor` service differs from the existing `EmbedExtractor` service in the following ways:

1. **API-based**: Uses official oEmbed APIs instead of scraping and the Ripple library
2. **Simpler**: No container/playlist detection - focuses on individual tracks/albums
3. **Direct embed codes**: Returns the embed HTML directly from the provider's API
4. **More reliable**: Uses standardized oEmbed protocol supported by the platforms
