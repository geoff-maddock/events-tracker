# OembedExtractor - Quick Start Guide

This guide will help you get started with the new OembedExtractor service.

## What is OembedExtractor?

OembedExtractor is a service that extracts embed HTML codes from SoundCloud and Bandcamp URLs using their official oEmbed APIs. It provides a cleaner, more reliable alternative to web scraping.

## Quick Start

### 1. Basic Usage

```php
use App\Services\Embeds\OembedExtractor;

// Create an instance
$extractor = new OembedExtractor();

// Set the size (optional, defaults to "medium")
$extractor->setLayout('medium'); // Options: 'small', 'medium', 'large'

// Extract embeds from a series
$embeds = $extractor->getEmbedsForSeries($series);

// Extract embeds from an event
$embeds = $extractor->getEmbedsForEvent($event);

// Extract embeds from an entity
$embeds = $extractor->getEmbedsForEntity($entity);

// Or extract from URLs directly
$urls = [
    'https://soundcloud.com/artist/track',
    'https://artist.bandcamp.com/album/name'
];
$embeds = $extractor->extractEmbedsFromUrls($urls);
```

### 2. Size Options

- **small**: 42px height - for minimal displays
- **medium**: 120px height - default, balanced view
- **large**: 300px height - full player experience

### 3. Integration Example

See `docs/examples/OembedControllerExample.php` for a complete controller implementation.

## How It Works

1. **Collects URLs**: Gathers URLs from entity links or extracts them from event/series descriptions
2. **Filters URLs**: Identifies SoundCloud and Bandcamp URLs
3. **Calls oEmbed APIs**: Makes POST requests to the respective oEmbed endpoints
4. **Extracts Embeds**: Retrieves the `html` key from JSON responses
5. **Returns Array**: Returns an array of embed HTML codes ready to display

## API Details

### SoundCloud
- **Endpoint**: `https://soundcloud.com/oembed`
- **Method**: POST
- **Parameters**: `format=json`, `url=<soundcloud-url>`

### Bandcamp
- **Endpoint**: `https://bandcamp.com/EmbeddedPlayer/oembed`
- **Method**: POST
- **Parameters**: `format=json`, `url=<bandcamp-url>`

## Documentation

- **Main Documentation**: `docs/OembedExtractor.md`
- **Implementation Summary**: `docs/OembedExtractor-Summary.md`
- **Controller Example**: `docs/examples/OembedControllerExample.php`
- **Testing Example**: `docs/examples/OembedExtractorIntegrationTest.php`

## Tests

Run the tests with:
```bash
php artisan test --filter OembedExtractorTest
```

## Differences from EmbedExtractor

| Feature | EmbedExtractor | OembedExtractor |
|---------|---------------|-----------------|
| Method | Web scraping + Ripple library | oEmbed API |
| Container Detection | Yes (playlists/albums) | No (individual tracks) |
| Complexity | High | Low |
| Reliability | Medium (depends on HTML structure) | High (uses official APIs) |
| Embed Source | Constructed URLs | Direct from provider |

## Next Steps

1. Integrate into your controllers (see example)
2. Update views to display oEmbed embeds
3. Consider adding caching for API responses
4. Add error logging for failed requests

## Support

For issues or questions, refer to the documentation files or check the implementation in `app/Services/Embeds/OembedExtractor.php`.
