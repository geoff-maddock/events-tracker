### Search results follow who is asking

- The search page no longer finds an event through a related entity that is unlisted, unless an admin is searching. Searching the exact slug of an unlisted entity used to list the public events it is attached to.
- `GET /api/search` now reads an API token. It used to recognise only the site's own session, so an API client always got the results of a signed-out visitor.
