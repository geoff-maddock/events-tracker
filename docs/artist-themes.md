# Artist theme lab (development only)

Thirteen original UI studies translate the October 19, 2026 artist reference
catalog into color tokens, heading families, card shapes, navigation treatments,
quiet textures and small original SVG illustrations. These are experiments
inspired by the catalog, not commissioned designs or artist collaborations.
The catalog's images, logos, captions and contact details are not bundled.

## Try it locally

Check out `experiment/artist-themes` and use your normal configured development
database and server. No migrations, seeding or account changes are needed.

```sh
npm ci
# In one terminal:
npm run dev
# In another, if you normally use artisan's local server:
php artisan serve
```

Set `APP_ENV=local` in your local `.env`. The lab defaults to enabled in local
and testing environments. If you use cached config, run `php artisan config:clear`.
Open any normal site page and expand **Artist themes · DEV** in the bottom-right.
Choose an artist, use Previous/Next to compare, and disable Decorative flourishes
to compare the palette and typography alone. Escape closes the panel.

Selection persists in this browser across page navigation and reloads. Use
**Restore original theme** to clear the artist selection. The ordinary light/dark
button also exits the artist study and switches back to the normal theme system.
Artist selection never calls the server or alters the normal saved light/dark
preference. The existing light/dark control retains its existing server behavior.

A direct preview URL can seed the selection, for example:

```
http://localhost:8000/?artist-theme=joe-taylor
http://localhost:8000/events?artist-theme=horriblparty
http://localhost:8000/entities?artist-theme=noirmatic
http://localhost:8000/series?artist-theme=dasha-plesen
```

The artist query parameter is consumed and removed without disturbing the other
query parameters or hash, so later selections survive reloads. Unknown values
restore the original appearance. Storage-disabled browsers can still preview a
selection for the current page.

To hide the lab in local development, set `ARTIST_THEMES_ENABLED=false` and clear
cached config. The layout requires **both** the enabled flag and an application
environment of `local` or `testing`. It does not load the artist script, stylesheet
or panel in production or staging, even when the flag is true. A URL parameter
cannot bypass that server-side gate. No deployment workflow is changed.

## Design studies

| Artist / URL value | Study | Palette and page details | Catalog references |
| --- | --- | --- | --- |
| Joe Taylor / `joe-taylor` | Living garden | Cream, sage, lilac; asymmetric organic cards, botanical familiar | JT-02, JT-04, JT-10 |
| Flesh from the Acid / `flesh-from-the-acid` | Electric relic | Charcoal, bone, rust; narrow display type, etched rules, electrical relic | FFA-01, FFA-04, FFA-05 |
| Tuukzs / `tuukzs` | Spirit room | Night violet, pink, mint; atmospheric pools, spectral eye and filament rings | TKZ-01, TKZ-03, TKZ-05 |
| Fuuurio / `fuuurio` | Chrome garden | Blue-black, pearl, pale green; iridescent trim, wireframe, chrome ornament | FUR-01, FUR-03, FUR-04 |
| Noirmatic / `noirmatic` | Ritual index | Olive-black and antique gold; codex frames, serif headings, geometric artifact | NMR-01–11 |
| Redaderr / `redaderr` | Feedback friend | Dusty rose, sage, paper; offset ink shadows, dashed labels, speaker creature | RED-01, RED-05 |
| Indecent Ink / `indecent-ink` | Bone architecture | Ink and ivory; double rules, concentric engraving, radial figure | INK-01–08 |
| Hollowed Graphix / `hollowed-graphix` | After-hours portal | Forest shadow, amethyst, fog; arched frames, distant portal | HGX-01–05 |
| Doomkitsch / `doomkitsch` | Candy dossier | Lilac, peach, plum; stippled hero, soft irregular labels, lumpy portrait | DMK-01–04 |
| Infiniteyay / `infiniteyay` | Recursive desktop | Lavender, jade, jewel pink; title bars, stacked window frames, nested worlds | INF-01, INF-04, INF-08 |
| The High Road Design / `high-road-design` | Midnight ephemera | Ochre, warm paper, forest; pulp serif, halftone, sound-system mascot | HRD-01, HRD-02, HRD-12 |
| Horriblparty / `horriblparty` | Friendly haunt | Lemon, hot pink, blue; bold outlines, flat shadows, sleepy building | HBP-02, HBP-11, HBP-13 |
| Dasha Plesen / `dasha-plesen` | Culture dish | Ivory, coral, turquoise; scalloped cards, rounded specimen labels, colony study | DPL-02, DPL-07, DPL-15 |

Each artist has a curated light or dark appearance. Body copy stays in the
existing system font; heading families use installed system fallbacks, without
additional font downloads. Existing posters, photographs, embedded players,
visibility badges, destructive actions and calendar event colors retain their
meaning. Color tokens flow through forms, menus, list views and shared cards.
Small artwork is concentrated in the homepage hero; detail headings get a quiet
accent rule. There is no canvas, animation loop or per-card JavaScript.

## Implementation and verification

- `resources/css/artist-themes.css`: scoped tokens, page treatments, panel styles.
- `resources/images/artist-themes/*.svg`: thirteen original decorative studies,
  fingerprinted by Vite and loaded as needed by CSS.
- `public/js/artist-themes.js`: early initialization, validated selection and
  browser-only state; included synchronously to avoid a palette flash.
- `resources/views/partials/artist-theme-lab.blade.php`: native disclosure,
  labeled select, compare controls, quiet mode and reset.
- `config/artist-themes.php` and `layouts/app-tw.blade.php`: development gate.

Run `npm run build` and `npm run lint`. In your configured Laravel development
environment, check home, event list/grid/detail, entity list/detail, series
list/detail and an edit form. Compare desktop and phone widths. Confirm the
normal theme reset, preserved filters, keyboard navigation and reduced-motion
settings. With `APP_ENV=production`, confirm no artist script, stylesheet or lab
markup is in the rendered HTML, including when a preview query is supplied.

The implementation workspace has no PHP/Composer runtime or development database;
full Laravel rendering and PHP tests must be run in a configured environment.

Local validation completed: Vite build, ESLint, and Chromium checks against a
representative markup fixture using the app's compiled CSS. All thirteen
selections, reload/navigation persistence, query validation, original reset,
flourish toggle, 390px layout, Escape and blocked-storage behavior passed. Token
contrast checks cover body, muted, primary, button and form text at 4.5:1 or more.
This does not substitute for rendering the application's Blade pages with real
data or running the Laravel suite.
