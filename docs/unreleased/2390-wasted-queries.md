### Fewer queries on the popular, radar, tag and entity lists (#2390)

Timings are from the dev site, as a guest unless noted.

- **Popular** (`/popular`): 153 queries and about 2.0 s, now 35 queries and about 0.65 s when the tag block has to be built, less when it is cached. The event and entity cards load their relations up front, like the other lists. The tag block (the 24 tags on the most public events, with a count and one thumbnail each) is the same for everyone and is kept for 30 minutes (`Tag::mostUsed()`); a tag's thumbnail is now the same one the tag grid shows, so it can fall back to an entity's photo.
- **Radar** (`/radar`): 98 queries, now 48.
- **Tags** (`/tags`): the page computed the recent top tags and the series, events and entities of the viewer's followed tags, and showed none of them. Removed: 8 queries and 190 ms of SQL, now 2 queries and 30 ms.
- **Entities** (`/entities` and the role, type and tag lists): the popularity totals (two grouped joins over follows and events) were part of the page query, its count and the "play this list" check on every request. They now join only when the list is sorted by popularity: 200 ms of SQL, now about 45 ms. The list builder takes these as `computedSorts`.
- **Popular events API** (`/api/events/popular`): the attendees of each event were a query per event; now one query. The response is unchanged.
- **Follow buttons in lists**: tag, entity and thread rows asked for the viewer's follow one row at a time. They now use `isFollowedBy()`, which reads the viewer's followed ids once. A profile with 58 followed things: 108 queries, now 52.

Not done, from the issue: caching the column listing the list builder reads when it checks a sort name (one 3 ms query, on some lists only), and `Model::preventLazyLoading()` outside production (it would throw on the lazy loads that remain).
