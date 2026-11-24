/**
 * The validation error summary of a form (#2411), rendered by
 * partials/form-errors.blade.php as #form-error-summary with one
 * <li data-error-field="key"> per message.
 *
 * When the page loads with errors, focus moves to the summary, so a keyboard or
 * screen reader user starts at the list of problems and not at the top of the
 * page. Each message whose field can be found becomes a link that focuses it.
 */

// "tag_list.0" -> the field named tag_list[] (or tag_list[0]); "include.tag" -> include[tag][]
function findField(key) {
    const parts = key.split('.');
    const bracketed = parts[0] + parts.slice(1).map((part) => `[${part}]`).join('');
    const names = [key, bracketed, `${bracketed}[]`, parts[0], `${parts[0]}[]`];

    const byId = document.getElementById(key) || document.getElementById(parts[0]);
    if (byId && byId.matches('input, select, textarea')) return byId;

    for (const name of names) {
        const field = document.querySelector(
            `input[name="${CSS.escape(name)}"]:not([type="hidden"]), select[name="${CSS.escape(name)}"], textarea[name="${CSS.escape(name)}"]`
        );
        if (field) return field;
    }
    return null;
}

function focusField(field) {
    // a select2 field hides the real <select>; its visible box is the next element
    const select2Box = field.classList.contains('select2-hidden-accessible')
        ? field.nextElementSibling?.querySelector('.select2-selection, input')
        : null;
    const target = select2Box || field;
    target.scrollIntoView({ block: 'center' });
    target.focus({ preventScroll: true });
}

document.addEventListener('DOMContentLoaded', () => {
    const summary = document.getElementById('form-error-summary');
    if (!summary) return;

    summary.querySelectorAll('li[data-error-field]').forEach((item) => {
        const field = findField(item.dataset.errorField);
        if (!field) return;

        const link = document.createElement('a');
        link.href = field.id ? `#${field.id}` : '#';
        link.className = 'underline hover:no-underline';
        link.textContent = item.textContent;
        link.addEventListener('click', (event) => {
            event.preventDefault();
            focusField(field);
        });
        item.replaceChildren(link);
    });

    summary.focus();
});
