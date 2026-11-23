### Unused page scripts removed (#2410)
- The jQuery throttle/debounce plugin was loaded on every page and used by nothing. It is no
  longer loaded and the file is deleted: one request fewer per page.
- `custom.js` no longer carries the Bootstrap tooltip set-up. Bootstrap's script has not been
  on the site since the Tailwind move, so that code never ran.
- Ten list pages each had their own "submit the form when this select changes" handler on top
  of the site-wide one for `.auto-submit` fields, so changing a sort or page size submitted the
  form twice. The page copies are gone; the site-wide handler does it once.

