# Event Filter URL Examples

This document provides example URLs for testing the event filter sharing feature.

## Basic Filters

### Filter by Event Name
```
/events/apply-filter?filters[name]=Concert
```
Shows all events with "Concert" in the name.

### Filter by Venue
```
/events/apply-filter?filters[venue]=The Fillmore
```
Shows all events at "The Fillmore" venue.

### Filter by Single Tag
```
/events/apply-filter?filters[tag][]=music
```
Shows all events tagged with "music".

### Filter by Event Type
```
/events/apply-filter?filters[event_type]=live-music
```
Shows all events of type "live-music".

## Complex Filters

### Multiple Tags (OR)
```
/events/apply-filter?filters[tag][]=music&filters[tag][]=art
```
Shows events tagged with either "music" OR "art".

### Date Range
```
/events/apply-filter?filters[start_at][start]=2024-01-01&filters[start_at][end]=2024-12-31
```
Shows events starting between January 1, 2024 and December 31, 2024.

### Combined Filters
```
/events/apply-filter?filters[name]=Concert&filters[tag][]=music&filters[venue]=The Fillmore
```
Shows concerts at The Fillmore tagged with "music".

## With Sorting and Pagination

### Sort by Date Descending
```
/events/apply-filter?filters[tag][]=music&sort=start_at&direction=desc
```
Shows music events sorted by start date (newest first).

### Sort by Date Ascending
```
/events/apply-filter?filters[tag][]=music&sort=start_at&direction=asc
```
Shows music events sorted by start date (oldest first).

### With Custom Page Limit
```
/events/apply-filter?filters[tag][]=music&limit=50
```
Shows 50 music events per page instead of default.

### Complete Example
```
/events/apply-filter?filters[name]=Festival&filters[tag][]=music&filters[tag][]=outdoor&filters[start_at][start]=2024-06-01&filters[start_at][end]=2024-09-30&sort=start_at&direction=asc&limit=25
```
Shows outdoor music festivals from June to September 2024, sorted by date ascending, 25 per page.

## Testing Scenarios

### Scenario 1: Music Events This Month
1. Apply filters:
   - Tag: music
   - Start date: First day of current month
   - End date: Last day of current month
2. Click "Copy Filter URL"
3. Open URL in incognito window
4. Verify filters are applied correctly

### Scenario 2: Share Venue Events
1. Apply filters:
   - Venue: Select a specific venue
   - Sort: start_at
   - Direction: asc
2. Click "Copy Filter URL"
3. Send URL to another user
4. Verify they see the same filtered results

### Scenario 3: Complex Multi-Filter
1. Apply filters:
   - Name: "Show"
   - Tags: ["music", "comedy"]
   - Event type: "performance"
   - Date range: Next 30 days
   - Limit: 10
2. Click "Copy Filter URL"
3. Verify URL contains all parameters
4. Test URL in different browser

## Expected URL Format

The generated URLs should follow this pattern:
```
{base_url}/events/apply-filter?{query_parameters}
```

Where `{query_parameters}` includes:
- Simple filters: `filters[key]=value`
- Array filters: `filters[key][]=value1&filters[key][]=value2`
- Nested filters: `filters[key][subkey]=value`
- Sort: `sort=field_name`
- Direction: `direction=asc|desc`
- Limit: `limit=number`

## Browser Compatibility

The clipboard copy feature requires:
- Modern browser (Chrome, Firefox, Safari, Edge)
- HTTPS connection (required by Clipboard API)
- JavaScript enabled

Fallback behavior:
- If Clipboard API fails, an alert shows the URL for manual copy

## Notes

1. **Session Independence**: Each user's session is independent, so shared filters don't affect other users
2. **URL Encoding**: Special characters in filter values are automatically URL-encoded
3. **Empty Filters**: If no filters are provided in the URL, the user sees the default event listing
4. **Case Sensitivity**: Filter values are case-sensitive (as per existing EventFilters implementation)
5. **Persistence**: Filters applied via URL are saved to the user's session and persist across page refreshes
