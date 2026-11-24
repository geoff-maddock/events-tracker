### Form errors that are announced and easy to find (#2411)
- **Error summary.** A form that fails validation now shows one summary of the problems at the
  top of the page, on every form (it was only on login, register and confirm-password). Keyboard
  focus moves to it when the page loads, and each message is a link that takes you to its field,
  so an error far down the event form can't go unnoticed. `partials/form-errors.blade.php`,
  `resources/assets/js/form-errors.js`.
- **Fields.** A field inside `<x-ui.form-group>` is tied to its error message or help text
  (`aria-describedby`), marked invalid when it has an error, and marked required when the group
  is (`aria-required`; the browser's own "fill in this field" popup is not switched on). The
  `x-ui` input, select, textarea and checkbox do this by themselves from the group around them.
- **Labels** that pointed at nothing now point at their field: confirm password on register and
  reset, the quick-add fields on the event form, three on the Discord target form. A group whose
  control has a different id than its name takes `for="the-id"`.
- Deploy needs `npm run build`.
