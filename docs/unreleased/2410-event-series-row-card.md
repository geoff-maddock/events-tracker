### One view each for an event and a series in a list (#2410)
- `events/single-tw` and `events/single-card-tw` were two copies of the same event, one drawn
  as a row and one as a card; the series pair was the same. Each pair is now one view,
  `events.single-tw` / `series.single-tw`, that takes `'card' => true` for the card. The edit,
  thread, link and follow icons are one small partial per type.
- Event rows (a profile's events, the attending list) now load the thumbnail instead of the
  full-size image, and load it lazily, as the cards already did.
- Series rows check "do I follow this" from one list of followed ids, as the cards already did,
  instead of one query per row.
- An event card with exactly four tags no longer shows a stray "+1" after them.

