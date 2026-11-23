/**
 * The "Show Filters" toggle on list pages (#2410). One handler for every page
 * that has the filter markup:
 *
 *   #filters-toggle-btn [data-has-filter="1|0"]   the button
 *   #filter-panel                                 the filter form, shown when open
 *   #filters-toggle-text, #filters-chevron        optional label and arrow in the button
 *   #active-filters-badges                        optional summary, shown when closed
 *   #filters-reset-closed / #filters-reset-open   optional reset links for each state
 *
 * The page renders its own default (open or closed). The visitor's last choice
 * for that path is remembered and wins, except that a page with a filter applied
 * and no badges to show it stays open.
 */
document.addEventListener('DOMContentLoaded', () => {
    const button = document.getElementById('filters-toggle-btn');
    const panel = document.getElementById('filter-panel');
    if (!button || !panel) return;

    const text = document.getElementById('filters-toggle-text');
    const chevron = document.getElementById('filters-chevron');
    const badges = document.getElementById('active-filters-badges');
    const resetClosed = document.getElementById('filters-reset-closed');
    const resetOpen = document.getElementById('filters-reset-open');
    const hasFilter = button.dataset.hasFilter === '1';
    const storageKey = 'filter-open:' + window.location.pathname;

    if (panel.id) button.setAttribute('aria-controls', panel.id);

    function applyState(open) {
        panel.classList.toggle('hidden', !open);
        button.setAttribute('aria-expanded', String(open));
        if (text) text.textContent = open ? 'Hide Filters' : 'Show Filters';
        if (chevron) chevron.classList.toggle('rotate-180', open);
        if (badges) badges.classList.toggle('hidden', open);
        if (resetClosed) resetClosed.classList.toggle('hidden', open || !hasFilter);
        if (resetOpen) resetOpen.classList.toggle('hidden', !open || !hasFilter);
    }

    // storage can be blocked (private windows); the toggle still works without it
    let saved = null;
    try {
        saved = localStorage.getItem(storageKey);
    } catch {
        // nothing remembered
    }

    let open = !panel.classList.contains('hidden');
    if (saved !== null) open = saved === '1';
    // a filter is applied and nothing else on the page says so
    if (hasFilter && !badges) open = true;
    applyState(open);

    button.addEventListener('click', () => {
        const next = panel.classList.contains('hidden');
        applyState(next);
        try {
            localStorage.setItem(storageKey, next ? '1' : '0');
        } catch {
            // not remembered, nothing else to do
        }
    });
});
