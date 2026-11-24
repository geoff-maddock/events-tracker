/**
 * Keyboard focus for every modal dialog on the site (#2411).
 *
 * A dialog is any element with role="dialog" aria-modal="true", however it is
 * shown and hidden (a `hidden` class, Alpine's x-show, or the `inert` attribute
 * on the mobile navigation drawer). When one opens, focus moves into it; Tab and
 * Shift+Tab stay inside it; when it closes, focus goes back to where it was
 * (unless the dialog has data-focus-return="false").
 */

// SweetAlert popups are dialogs too, and already manage their own focus
const DIALOG = '[role="dialog"][aria-modal="true"]:not(.swal2-popup)';
const FOCUSABLE = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

// open dialogs, oldest first, each with the element focus returns to
const opened = new Map();

const isShown = (el) => el.getClientRects().length > 0;
const isOpen = (dialog) => dialog.isConnected && !dialog.inert && isShown(dialog);
const focusableIn = (dialog) => [...dialog.querySelectorAll(FOCUSABLE)].filter(isShown);

function enter(dialog) {
    opened.set(dialog, document.activeElement);

    // a dialog that places focus itself (the search overlay) keeps its choice
    if (dialog.contains(document.activeElement)) {
        return;
    }

    const target = dialog.querySelector('[autofocus]') || focusableIn(dialog)[0];

    if (target) {
        target.focus();
    } else {
        dialog.tabIndex = -1;
        dialog.focus();
    }
}

function leave(dialog) {
    const cameFrom = opened.get(dialog);
    opened.delete(dialog);

    // the search overlay opens when its field gets focus, so it must not get it back
    if (dialog.dataset.focusReturn === 'false') {
        return;
    }

    // only when focus was left behind in the dialog, not when the user has moved on
    const stranded = document.activeElement === document.body || dialog.contains(document.activeElement);

    if (stranded && cameFrom instanceof HTMLElement && cameFrom.isConnected) {
        cameFrom.focus();
    }
}

function sync() {
    [...opened.keys()].filter((dialog) => !isOpen(dialog)).forEach(leave);
    document.querySelectorAll(DIALOG).forEach((dialog) => {
        if (isOpen(dialog) && !opened.has(dialog)) {
            enter(dialog);
        }
    });
}

let queued = false;

function queueSync() {
    if (!queued) {
        queued = true;
        requestAnimationFrame(() => {
            queued = false;
            sync();
        });
    }
}

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Tab' || opened.size === 0) {
        return;
    }

    const dialog = [...opened.keys()].pop();
    const items = focusableIn(dialog);

    if (items.length === 0) {
        event.preventDefault();
        return;
    }

    const first = items[0];
    const last = items[items.length - 1];
    const outside = !dialog.contains(document.activeElement);

    if (event.shiftKey && (outside || document.activeElement === first)) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && (outside || document.activeElement === last)) {
        event.preventDefault();
        first.focus();
    }
});

new MutationObserver(queueSync).observe(document.documentElement, {
    subtree: true,
    childList: true,
    attributes: true,
    attributeFilter: ['class', 'style', 'hidden', 'inert'],
});

queueSync();
