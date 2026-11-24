### Finished one-off commands removed (#2412)

Three artisan commands that had done their job were removed:

- `file:publicize` (2022) set every stored photo to public after the move to external storage.
- `events:fix-numeric-slugs` renamed event slugs that began with a digit. None are left, and `Event` no longer lets one be saved.
- `spaces:backfill-cache-control` stamped a `Cache-Control` header on the photos uploaded before the storage disk sent one. Old photos now carry it, and every new upload gets it from the disk options.

`tags:cleanup` and `instagram:initialize-shares` stay: junk tag names can still be typed in, and a new install needs the second one before it turns on Instagram posting.
