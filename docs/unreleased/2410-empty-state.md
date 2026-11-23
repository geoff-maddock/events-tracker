### One "nothing to show" message for every list (#2410)
- The message a list shows when it has no rows is now the `<x-empty-state>` Blade component
  (an optional icon, the message, optional links below). 35 hand-written blocks in 29 views use it.
- They now look the same: one icon size and colour, the same text colour, and a full stop at the
  end. Before, the icon was anywhere from large to very large, some messages were italic, some
  small, and some pages boxed the message in a card while others did not (that part is kept as
  it was, page by page).
- Not converted: the "No … listed" rows inside the admin tables, and one-line notes inside a
  detail page section ("No contacts found." on an entity).

