### The personal data export is only downloadable through its signed link

The zip of a user's data was also copied to public storage, where anyone with the address could fetch it for up to seven days without signing in; only the file's name kept it private. The copy is no longer made. The link in the "export ready" email is now always the signed download route, which works for seven days and for that one file. The link is no longer written to the application log.

Checked on the dev site: a link generated the way the queue worker does it downloads the zip; the same address without its signature, or altered, is refused. The signature is built from `APP_URL`, which must match the address people use.

Copies left in `storage/app/public/exports` by earlier versions are still removed by `cleanup:exports` (production has none).

Signed links in general (this one, unsubscribe, email verification): they still work when a mail client or link scanner adds a tracking tag such as `utm_source` or `fbclid`, and they are no longer remembered in the session as the page a Back button returns to. The download is marked `private, no-store`.
