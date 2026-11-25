### Personal data export: survey responses included, old exports removed; more tests (#2404)

Two faults in the personal data export, found while writing its tests:

- **Survey responses were missing from the download.** The export gathered a user's feedback survey responses and then never wrote them into the zip. It now has a `survey_responses.json`.
- **Old exports were never deleted.** The zip is written to `storage/framework/exports`, and the daily clean-up only looked in `storage/app/exports` and the public copy. It now covers every folder an export can be written to. Production has four zips from May 2026 onwards in that folder; the next clean-up run removes them. A file the clean-up cannot delete is logged and skipped, not an error that stops the run.

Tests:

- Factories for `Comment`, `Follow`, `Like`, `EventShowing`, `EntityClaim`, `EventReview`, `ShortUrl`, `ClickTrack` and `EmailSuppression`. `EventResponse` and `TagType` had a factory file their model could not use.
- `DataExportService` is covered end to end (the zip, its contents, the download link, the clean-up): 51% to 93% of lines.
- `FollowerNotifier` is covered for new threads and replies (who is mailed, by which setting, once each): 63% to 92%.
