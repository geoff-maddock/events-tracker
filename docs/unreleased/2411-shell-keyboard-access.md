### Keyboard and screen reader access in the page shell (#2411)
- **Skip link.** The first Tab on any page shows "Skip to main content", which jumps past the
  sidebar's links to the page itself.
- **Modal dialogs.** When a dialog opens (quick-add entity on the event form, onboarding, the
  verify-email prompt, the feedback prompt), keyboard focus moves into it, Tab stays inside it,
  and focus goes back to where it was when it closes. One script,
  `resources/assets/js/dialog-focus.js`, does this for anything with `role="dialog"
  aria-modal="true"`. The close buttons, which are only an icon, now have a name.
- **Mobile menu.** While closed, the drawer's links were still reachable with Tab and read by
  screen readers, because it is only moved off-screen. It is now `inert` until opened. Escape
  closes it, and the menu button reports open/closed correctly however it was closed.
- **Navigation landmarks** have names ("Main", "On this page", "Table of contents").
- Deploy needs `npm run build`.
