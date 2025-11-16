# Advanced API Filter Examples

This document provides practical examples of using the new advanced filter syntax for API endpoints.

## Basic Usage

### Simple Equality
```bash
# Find events with exact name match
curl "https://your-domain.com/api/events?filters=events.name%20EQ%20%22Rock%20Concert%22"

# URL decoded: events.name EQ "Rock Concert"
```

### Comparison Operators

#### Greater Than
```bash
# Events for ages 21 and up
curl "https://your-domain.com/api/events?filters=events.min_age%20GT%2018"

# URL decoded: events.min_age GT 18
```

#### Less Than or Equal
```bash
# Events with door price $20 or less
curl "https://your-domain.com/api/events?filters=events.door_price%20LTE%2020"

# URL decoded: events.door_price LTE 20
```

#### Not Equal
```bash
# All events except archived ones
curl "https://your-domain.com/api/events?filters=events.status%20NEQ%20archived"

# URL decoded: events.status NEQ archived
```

### Pattern Matching with LIKE

```bash
# Events with "concert" anywhere in the name
curl "https://your-domain.com/api/events?filters=events.name%20LIKE%20%25concert%25"

# URL decoded: events.name LIKE %concert%
```

### IN and NOT IN Operators

#### IN - Match any value in list
```bash
# Events that are either "Concert" or "Festival"
curl "https://your-domain.com/api/events?filters=event_types.name%20IN%20(Concert,%20Festival)"

# URL decoded: event_types.name IN (Concert, Festival)
```

#### NOT IN - Exclude values
```bash
# All events except archived or deleted
curl "https://your-domain.com/api/events?filters=events.status%20NOT%20IN%20(archived,%20deleted)"

# URL decoded: events.status NOT IN (archived, deleted)
```

## Combining Conditions

### AND - Both conditions must be true
```bash
# Rock concerts for 21+
curl "https://your-domain.com/api/events?filters=events.name%20LIKE%20%25Rock%25%20AND%20events.min_age%20GTE%2021"

# URL decoded: events.name LIKE %Rock% AND events.min_age GTE 21
```

### OR - Either condition must be true
```bash
# Events that are either jazz or blues
curl "https://your-domain.com/api/events?filters=events.name%20LIKE%20%25Jazz%25%20OR%20events.name%20LIKE%20%25Blues%25"

# URL decoded: events.name LIKE %Jazz% OR events.name LIKE %Blues%
```

### Grouped Conditions with Parentheses

```bash
# (Rock OR Jazz) concerts that are 18+
curl "https://your-domain.com/api/events?filters=(events.name%20LIKE%20%25Rock%25%20OR%20events.name%20LIKE%20%25Jazz%25)%20AND%20events.min_age%20EQ%2018"

# URL decoded: (events.name LIKE %Rock% OR events.name LIKE %Jazz%) AND events.min_age EQ 18
```

### Complex Nested Conditions
```bash
# (Concerts for 21+) OR (Festivals under $50)
curl "https://your-domain.com/api/events?filters=(event_types.name%20EQ%20Concert%20AND%20events.min_age%20GT%2018)%20OR%20(event_types.name%20EQ%20Festival%20AND%20events.door_price%20LT%2050)"

# URL decoded: (event_types.name EQ Concert AND events.min_age GT 18) OR (event_types.name EQ Festival AND events.door_price LT 50)
```

## Real-World Use Cases

### Find Upcoming Events by Venue
```bash
curl "https://your-domain.com/api/events?filters=venue.name%20EQ%20%22The%20Venue%22%20AND%20events.start_at%20GT%202024-01-01"

# URL decoded: venue.name EQ "The Venue" AND events.start_at GT 2024-01-01
```

### Find Events by Multiple Tags (OR logic)
```bash
curl "https://your-domain.com/api/events?filters=tags.name%20IN%20(music,%20art,%20culture)"

# URL decoded: tags.name IN (music, art, culture)
```

### Find Free or Cheap Events
```bash
curl "https://your-domain.com/api/events?filters=events.door_price%20EQ%200%20OR%20events.door_price%20LTE%2010"

# URL decoded: events.door_price EQ 0 OR events.door_price LTE 10
```

### Find All-Ages Events of Specific Types
```bash
curl "https://your-domain.com/api/events?filters=events.min_age%20LTE%2018%20AND%20event_types.name%20IN%20(Concert,%20Festival,%20Workshop)"

# URL decoded: events.min_age LTE 18 AND event_types.name IN (Concert, Festival, Workshop)
```

### Exclude Specific Venues
```bash
curl "https://your-domain.com/api/events?filters=venue.name%20NOT%20IN%20(%22Closed%20Venue%22,%20%22Under%20Renovation%22)"

# URL decoded: venue.name NOT IN ("Closed Venue", "Under Renovation")
```

## Tips and Best Practices

1. **URL Encoding**: Always URL-encode the filter string. Spaces become `%20`, parentheses become `%28` and `%29`.

2. **Quotes for Strings**: Use quotes (single or double) for string values that contain spaces:
   - Good: `name EQ "Rock Concert"`
   - Good: `name EQ 'Rock Concert'`
   - Bad: `name EQ Rock Concert` (will fail parsing)

3. **Table Prefixes**: Use table prefixes when joining related tables to avoid ambiguity:
   - `events.name` vs `venue.name` vs `event_types.name`

4. **Numeric Values**: Don't quote numeric values:
   - Good: `age GT 18`
   - Bad: `age GT "18"`

5. **Boolean Values**: Use lowercase `true` or `false`:
   - `is_benefit EQ true`

6. **NULL Values**: Use lowercase `null`:
   - `description EQ null`

7. **LIKE Wildcards**: Use `%` for wildcards in LIKE queries:
   - `%concert%` matches "Rock Concert", "Jazz Concert", etc.
   - `concert%` matches strings starting with "concert"
   - `%concert` matches strings ending with "concert"

8. **Complex Queries**: Break complex queries into smaller parts and test incrementally.

9. **Error Handling**: Invalid queries are logged and ignored, returning all results. Check logs if filters aren't working.

10. **Backward Compatibility**: The legacy array-based filter format still works:
    ```bash
    # Still supported
    curl "https://your-domain.com/api/events?filters[name]=Concert"
    ```

## Combining with Other Parameters

You can combine filters with sorting and pagination:

```bash
# Filter, sort, and paginate
curl "https://your-domain.com/api/events?filters=events.min_age%20GTE%2021&sort=events.start_at&direction=asc&limit=25&page=1"
```

## JavaScript Example

```javascript
// Using fetch API with advanced filters
const filters = encodeURIComponent('(events.name LIKE %Rock% OR events.name LIKE %Jazz%) AND events.min_age EQ 18');
const url = `https://your-domain.com/api/events?filters=${filters}`;

fetch(url, {
  headers: {
    'Authorization': 'Bearer your-token-here',
    'Accept': 'application/json'
  }
})
  .then(response => response.json())
  .then(data => console.log(data));
```

## Python Example

```python
import requests
from urllib.parse import urlencode

# Build filter query
filter_query = '(events.name LIKE %Rock% OR events.name LIKE %Jazz%) AND events.min_age EQ 18'

# URL encode parameters
params = urlencode({'filters': filter_query})

# Make request
url = f'https://your-domain.com/api/events?{params}'
headers = {
    'Authorization': 'Bearer your-token-here',
    'Accept': 'application/json'
}

response = requests.get(url, headers=headers)
data = response.json()
print(data)
```
