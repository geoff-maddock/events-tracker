/**
 * Toast messages (#2397): window.notify({ title, message, level }) shows a
 * message in the corner of the page without taking keyboard focus.
 *
 *   level 'success' | 'info'    announced politely, goes away after a few seconds
 *   level 'error' | 'warning'   announced at once, stays until it is closed
 *
 * The messages go into the two live regions the layout renders
 * (partials/toasts.blade.php). A flash() message from the last request arrives
 * as JSON in #flash-data and is shown when the page loads.
 */
const DISMISS_AFTER = 6000;

const styles = {
    success: { icon: 'bi-check-circle-fill', colour: 'text-success', border: 'border-l-green-600' },
    info: { icon: 'bi-info-circle-fill', colour: 'text-primary', border: 'border-l-primary' },
    warning: { icon: 'bi-exclamation-triangle-fill', colour: 'text-warning', border: 'border-l-amber-500' },
    error: { icon: 'bi-x-circle-fill', colour: 'text-destructive', border: 'border-l-red-600' },
};

function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text) node.textContent = text;
    return node;
}

function notify({ title = '', message = '', level = 'info', persistent = null } = {}) {
    const style = styles[level] ?? styles.info;
    const urgent = level === 'error' || level === 'warning';
    const region = document.getElementById(urgent ? 'toast-alerts' : 'toast-status');
    if (!region || (!title && !message)) return null;

    // the same message twice (several failed requests at once) is shown once
    const key = `${level}|${title}|${message}`;
    const existing = [...region.children].find((toast) => toast.dataset.key === key);
    if (existing) return existing;

    const toast = el('div', `pointer-events-auto flex items-start gap-3 w-full rounded-lg border border-border border-l-4 ${style.border} bg-card text-foreground shadow-lg p-4`);
    toast.dataset.key = key;
    toast.dataset.level = level;

    const icon = el('i', `bi ${style.icon} ${style.colour} text-lg leading-6`);
    icon.setAttribute('aria-hidden', 'true');

    const body = el('div', 'flex-1 min-w-0 break-words');
    if (title) body.append(el('p', 'font-semibold', title));
    if (message) body.append(el('p', 'text-sm text-muted-foreground', message));

    const close = el('button', 'flex-shrink-0 text-muted-foreground hover:text-foreground transition-colors');
    close.type = 'button';
    close.setAttribute('aria-label', 'Dismiss message');
    const closeIcon = el('i', 'bi bi-x-lg');
    closeIcon.setAttribute('aria-hidden', 'true');
    close.append(closeIcon);
    close.addEventListener('click', () => toast.remove());

    toast.append(icon, body, close);
    region.append(toast);

    if (!(persistent ?? urgent)) {
        // not while someone is reading it or has tabbed to its close button
        let timer = setTimeout(() => toast.remove(), DISMISS_AFTER);
        const hold = () => clearTimeout(timer);
        const release = () => {
            clearTimeout(timer);
            timer = setTimeout(() => toast.remove(), DISMISS_AFTER);
        };
        toast.addEventListener('mouseenter', hold);
        toast.addEventListener('focusin', hold);
        toast.addEventListener('mouseleave', release);
        toast.addEventListener('focusout', release);
    }

    return toast;
}

window.notify = notify;

document.addEventListener('DOMContentLoaded', () => {
    const data = document.getElementById('flash-data');
    if (!data) return;
    try {
        notify(JSON.parse(data.textContent));
    } catch {
        // a malformed payload is not worth breaking the page for
    }
});
