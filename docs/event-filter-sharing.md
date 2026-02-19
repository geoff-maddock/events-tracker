# Event Filter Sharing Feature

## Overview
The Event Filter Sharing feature allows users to share their filtered event views by generating a shareable URL with encoded filter parameters. This enables users to bookmark specific filter combinations and share them with others.

## User Flow

### Applying and Sharing Filters
1. User navigates to the events listing page
2. User applies desired filters (name, venue, tags, event type, date range, etc.)
3. After filters are applied, a "Copy Filter URL" button appears next to the Apply/Reset buttons
4. User clicks the "Copy Filter URL" button
5. A shareable URL with all current filters encoded as query parameters is copied to the clipboard
6. Visual feedback is shown (button changes to "Copied!" with a checkmark)
7. User can paste and share the URL with others

### Using a Shared Filter URL
1. User receives a filter URL from someone else
2. User opens the URL in their browser
3. The system automatically:
   - Extracts filter parameters from the URL
   - Saves them to the user's session
   - Redirects to the filtered events view
4. Events are displayed with the shared filters applied

## Technical Implementation

### Routes
- **GET `/events/apply-filter`** - Accepts filter parameters and sets them in session
  - Route name: `events.applyFilterFromUrl`
  - Controller method: `EventsController@applyFilterFromUrl`

### URL Format
```
https://example.com/events/apply-filter?filters[name]=Concert&filters[tag][]=music&filters[tag][]=live&sort=start_at&direction=desc&limit=25
```

### Supported Parameters

#### Filter Parameters
All filters are passed under the `filters` array in the query string:

- **filters[name]** - Text search in event names
- **filters[venue]** - Filter by venue name
- **filters[tag][]** - Filter by one or more tags (array)
- **filters[tag_all][]** - Filter by events matching ALL specified tags (array)
- **filters[related]** - Filter by related entity name
- **filters[event_type]** - Filter by event type slug
- **filters[start_at][start]** - Start date for date range (YYYY-MM-DD)
- **filters[start_at][end]** - End date for date range (YYYY-MM-DD)

#### Sort Parameters
- **sort** - Field name to sort by (e.g., 'start_at', 'name')
- **direction** - Sort direction ('asc' or 'desc')
- **limit** - Number of results per page

### Controller Method
The `applyFilterFromUrl()` method in `EventsController`:
1. Initializes the session store with the appropriate key prefix
2. Extracts filter parameters from the request
3. Sets filters, sorting, and pagination parameters in the session
4. Saves the session state
5. Redirects to the events filter route which applies the filters

### Frontend Components

#### Button (index-tw.blade.php)
- Only visible when filters are active (`@if($hasFilter)`)
- Located in the filter panel next to Apply/Reset buttons
- Includes icon (bi-link-45deg) and text

#### JavaScript Functionality
The JavaScript code:
1. Reads current filter state from blade-injected JSON
2. Constructs a URLSearchParams object with all active filters
3. Handles different filter types:
   - Simple string values
   - Array values (tags)
   - Nested objects (date ranges)
4. Generates the complete shareable URL
5. Uses the Clipboard API to copy the URL
6. Provides visual feedback with temporary button state change

### Example Usage

#### Creating a Shareable URL for Music Events in January
```javascript
// After applying filters for:
// - Tags: music, live
// - Date: January 1-31, 2024
// The generated URL will be:
https://example.com/events/apply-filter?filters[tag][]=music&filters[tag][]=live&filters[start_at][start]=2024-01-01&filters[start_at][end]=2024-01-31
```

## Testing
Tests are located in `tests/Feature/EventFilterUrlTest.php` and cover:
- Route existence
- Simple filter application
- Complex filter combinations
- Date range filters
- Sort and pagination parameters

## Browser Compatibility
The feature uses the modern Clipboard API (`navigator.clipboard.writeText()`):
- Supported in all modern browsers
- Falls back to alert with manual copy for unsupported browsers
- Requires HTTPS in production

## Security Considerations
- All filter values are validated through Laravel's existing filter validation
- No user input is executed as code
- URL parameters are sanitized by Laravel's request handling
- Session-based storage prevents URL tampering from affecting other users

## Future Enhancements
Potential improvements:
1. URL shortening service integration for cleaner sharing
2. QR code generation for mobile sharing
3. Social media share buttons
4. "Recent filters" history feature
5. Named filter presets that users can save
