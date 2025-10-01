# OembedExtractor Implementation Summary

## Overview
This document summarizes the implementation of the OembedExtractor service for the events-tracker application.

## Requirements Met

✅ **Created a new service** that gathers correct embed HTML codes related to an entity, event or series

✅ **Service has methods similar to EmbedExtractor:**
- `getEmbedsForEntity(Entity $entity, string $size = "medium"): array`
- `getEmbedsForEvent(Event $event, string $size = "medium"): array`
- `getEmbedsForSeries(Series $series, string $size = "medium"): array`
- `extractEmbedsFromUrls(array $urls, string $size = "medium"): array`

✅ **Accepts entity and size parameters:**
- Size options: "small", "medium", "large"
- Each method accepts the model and optional size parameter

✅ **Collects related links and gathers embed HTML:**
- Extracts URLs from entity links
- Extracts URLs from event/series descriptions
- Processes SoundCloud and Bandcamp URLs

✅ **Uses SoundCloud oEmbed API with curl:**
```php
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://soundcloud.com/oembed',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => 'format=json&url=https://soundcloud.com/user/track',
    // ... other options
]);
```

✅ **Returns the html key from JSON response:**
```php
if (isset($data['html'])) {
    return $data['html'];
}
```

## Files Created

1. **app/Services/Embeds/OembedExtractor.php** - Main service class
2. **tests/Feature/Services/Embeds/OembedExtractorTest.php** - Unit tests
3. **docs/OembedExtractor.md** - Documentation
4. **docs/examples/OembedControllerExample.php** - Controller usage example
5. **docs/examples/OembedExtractorIntegrationTest.php** - Integration test example

## Technical Details

### SoundCloud oEmbed API
- **URL:** `https://soundcloud.com/oembed`
- **Method:** POST
- **Parameters:**
  - `format`: 'json'
  - `url`: The SoundCloud URL
- **Response:** JSON with `html` key containing embed code

### Bandcamp oEmbed API
- **URL:** `https://bandcamp.com/EmbeddedPlayer/oembed`
- **Method:** POST
- **Parameters:**
  - `format`: 'json'
  - `url`: The Bandcamp URL
- **Response:** JSON with `html` key containing embed code

### Size Configurations
- **Small:** Height of 42px
- **Medium:** Height of 120px (default)
- **Large:** Height of 300px

## Usage Example

```php
use App\Services\Embeds\OembedExtractor;

$extractor = new OembedExtractor();
$extractor->setLayout('medium');

// For an entity
$embeds = $extractor->getEmbedsForEntity($entity);

// For an event
$embeds = $extractor->getEmbedsForEvent($event);

// For a series
$embeds = $extractor->getEmbedsForSeries($series);

// From URLs directly
$urls = ['https://soundcloud.com/user/track'];
$embeds = $extractor->extractEmbedsFromUrls($urls);
```

## Testing

Basic unit tests are included in `tests/Feature/Services/Embeds/OembedExtractorTest.php`:
- Configuration tests
- Instantiation tests
- URL filtering tests

For integration testing with actual API calls, see the example in `docs/examples/OembedExtractorIntegrationTest.php`.

## Differences from EmbedExtractor

1. **API-based:** Uses official oEmbed APIs instead of web scraping
2. **Simpler:** No container/playlist detection
3. **Direct embed codes:** Returns embed HTML directly from provider APIs
4. **More reliable:** Uses standardized oEmbed protocol

## Next Steps

To use this service in production:

1. Add route handlers in controllers (see example in `docs/examples/OembedControllerExample.php`)
2. Update views to display oEmbed embeds
3. Consider caching API responses to reduce external requests
4. Add error logging for failed API requests
5. Consider implementing retry logic for transient failures
