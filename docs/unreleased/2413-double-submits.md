### One attending response per person, and a quick-add that survives a double click (#2413)

- Two "attend" requests arriving together (a double click, a retry on a slow connection) could each store a response, and each extra row counted as one more person attending. A migration removes the repeats and makes the event, user and response type unique. In production 27 pairs were stored more than once, 113 extra rows, one of them 32 times; the newest is from October 2026. The API's attend action now adds the row the same way the site's does.
- Since entity slugs became unique (#2413), two "add a missing venue" requests for the same name arriving together made the second fail with an error. It now looks for a free slug again, as it would have a moment later.

**Deploy:** run `php artisan migrate`.
