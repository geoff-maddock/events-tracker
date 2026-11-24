### Messages you have time to read (#2397)
- **Toasts instead of a popup.** The result of an action ("Event added", "You do not have
  permission…") used to be a popup in the middle of the page that took keyboard focus and closed
  itself after 2.5 seconds, errors included. It is now a message in the top-right corner (full
  width on a phone) that doesn't interrupt what you are doing. Success and info messages go
  after 6 seconds, or stay while the pointer or focus is on them; errors and warnings stay until
  closed. Screen readers announce them (`role="status"` / `role="alert"`).
- **Failed actions say so.** Following, attending or liking from a card did nothing visible when
  the request failed. It now shows an error, with the reason when there is one (signed out,
  session expired, no permission, offline). A signed-out session used to be reported as
  "Success". The home page's "load more events" reports a failure too.
- `window.notify({ title, message, level })` (`resources/assets/js/toast.js`) is the one way to
  show a message from a script; `flash()` in a controller is unchanged.
- Removed the unused `#loading` and `#flash` placeholders from the layout.
- Deploy needs `npm run build`.
