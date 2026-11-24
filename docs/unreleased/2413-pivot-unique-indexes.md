### Link tables hold each pair once (#2413)

The tables that link events, entities, series, threads, blogs and posts to their tags, entities, roles, links, contacts and photos (and users to groups) had nothing to stop the same pair being stored twice. A migration removes the repeats and adds a unique index to each of the 23 tables.

In production only `event_tag` had repeats: 10 event and tag pairs from 2016 to 2020, 28 extra rows. Nothing changes on any page.

The forms that create an event, series, entity, thread, group or permission now ignore an id sent twice, which would otherwise fail against the new index.

**Deploy:** run `php artisan migrate`. It took about 5 seconds on the dev database, which had the same repeats.
