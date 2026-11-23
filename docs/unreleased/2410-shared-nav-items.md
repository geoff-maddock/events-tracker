### One set of navigation links for both sidebars (#2410)
- The desktop sidebar and the mobile drawer each had their own hand-kept copy of the navigation
  links. Both now include `partials/nav-items-tw`, so a link is added or changed once.
- The copies had drifted: in the mobile drawer the Forum link was not highlighted on `/threads`,
  and the spacing between links was uneven. Both now match the desktop sidebar. The drawer keeps
  its larger sub-links for touch, and still leaves the calendar out on the smallest screens.
- The link for the current page is marked with `aria-current="page"`, and the icons are hidden
  from screen readers.
- Removed a second "Forum" link that depended on a variable nothing sets.

