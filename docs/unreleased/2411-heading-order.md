### Heading order (#2411)
- **Page titles.** On the event, series, entity, photo and review lists, the event grid, a
  thread and a user profile, the `<h1>` held the tag / venue crumbs with their links and follow
  buttons. The title is now the heading and the crumbs sit beside it, looking the same.
- **No skipped levels.** The home and upcoming pages jumped from the title to `<h3>`, `<h5>`
  and `<h6>`; the week page and the list pages jumped from the title to `<h3>`. Headings now go
  down one level at a time, so a screen reader's heading list reads as an outline. Lists whose
  cards followed the title directly get a hidden "Events" / "Series" / … heading
  (`<x-list-toolbar heading="…">` on the first toolbar).
- An event or series row takes `'heading' => 'h4'` when it sits under a deeper heading (the day
  cards on the home page).
- Nothing changes on screen.
