### Text colours that follow the theme (#2411)
- About 75 text and icon colours were fixed palette shades (`text-green-500`, `text-yellow-500`,
  `text-gray-400`, …) with no dark-theme variant, so they were faint on one theme or the other:
  the follow ticks, like and favourite stars, the "/" crumbs, status icons, the stat icons on a
  profile. They now use the theme's tokens, which have a value per theme: `text-success`,
  `text-destructive`, `text-muted-foreground`, and a new `text-warning` (`--warning-text`).
- Slightly noticeable: on the light theme the greens and ambers are darker than before, and
  greys match the rest of the muted text.
- The "Copied!" state of the share buttons and the search highlight used light-only fills;
  they are now tints that work on both themes.
- Coloured fills with their own text colour (status dots, count badges, the photo buttons) are
  unchanged.
- A test fails on a new one-theme text colour in a view. Deploy needs `npm run build`.
