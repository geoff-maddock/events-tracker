# Feature Notes

A more detailed description of new features and changes to the application.

## 2026.02.12

### Tailwind CSS 4 Upgrade (Mix-first)
The frontend build now uses Tailwind CSS v4 while staying on Laravel Mix for this phase.

**Build/config updates:**
- `tailwindcss` upgraded to `^4.1.12`
- `@tailwindcss/postcss` added and used in `postcss.config.js`
- `webpack.mix.js` now relies on PostCSS config for Tailwind processing
- `resources/css/tailwind.css` now uses:
	- `@config '../../tailwind.config.js';`
	- `@import 'tailwindcss';`

**Compatibility adjustment:**
- Replaced `@apply` composition of custom classes (`card-tw`, `card-hover-tw`) with direct utility lists for Tailwind v4 compatibility.

**Template cleanup (unused legacy files removed):**
- `resources/views/app-old.blade.php`
- `resources/views/pages/about-old.blade.php`
- `resources/views/pages/home-old.blade.php`
- `resources/views/pages/search-tw.blade.php`
- `resources/views/users/index-old.blade.php`
- `resources/views/threads/index-old.blade.php`
- `resources/views/events/home-tw.blade.php`

**Notes:**
- Active search remains `resources/views/pages/search.blade.php`.
- A Vite migration is intentionally deferred; this change keeps the existing Mix pipeline stable.

## 2025.01.15

### Semantic Entity Routes
You can now access entities using cleaner, semantic URLs based on their role.

**Index Routes** - List all entities of a specific type:
- `/venue` - List all venues
- `/artist` - List all artists  
- `/dj` - List all DJs
- `/producer` - List all music producers
- `/promoter` - List all promoters
- `/shop` - List all shops
- `/band` - List all bands

**Detail Routes** - View a specific entity by role and slug:
- `/venue/{slug}` - View a specific venue (e.g., `/venue/brillobox`)
- `/artist/{slug}` - View a specific artist (e.g., `/artist/andy-warhol`)
- `/dj/{slug}` - View a specific DJ (e.g., `/dj/cutups`)
- Similar patterns for producer, promoter, shop, and band

These routes work alongside the existing `/entities/role/{role}` and `/entities/{slug}` routes.

## 2024.12.26

### ICAL Calendar Integration
There are now a few different ical feeds that you can use to embed events in your own calendars such as google calendar or outlook.

Want to add all events to your calendar?
Use https://your-domain.com/events/ical

Want to just add events you are attending?
Use https://your-domain.com/users/{your-user-id}/attending-ical

Want to add events you are attending or are related to tags or entities you follow?
Use https://your-domain.com/users/{your-user-id}/interested-ical
