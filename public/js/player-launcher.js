/**
 * Opens the pop-out audio player (#2366) from any [data-player-launch] link.
 *
 * The player lives in one named window ("arcane-player") so it keeps playing
 * while the site navigates. If that window already shows the player, the link
 * is sent to it over BroadcastChannel ('load' replaces the queue, 'enqueue'
 * adds to it) instead of reloading it; otherwise the window is pointed at the
 * link's URL. If popups are blocked, the player opens in this tab.
 */
(function () {
    'use strict';

    var WINDOW_NAME = 'arcane-player';
    var FEATURES = 'popup,width=440,height=780';
    var channel = 'BroadcastChannel' in window ? new BroadcastChannel('arcane-player') : null;

    // brief confirmation: the label (read by screen readers, visible on text
    // buttons) and the icon (all that icon-only buttons show) change for 2s
    function flash(link, text) {
        var label = link.querySelector('[data-player-label]');
        var icon = link.querySelector('.bi');
        if (!label || link.dataset.playerBusy) {
            return;
        }
        var original = label.textContent;
        var originalIcon = icon ? icon.className : '';
        link.dataset.playerBusy = '1';
        label.textContent = text;
        if (link.hasAttribute('aria-label')) {
            link.setAttribute('aria-label', text);
        }
        if (icon) {
            icon.className = originalIcon.replace('bi-headphones', 'bi-check2');
        }
        window.setTimeout(function () {
            label.textContent = original;
            if (link.hasAttribute('aria-label')) {
                link.setAttribute('aria-label', original);
            }
            if (icon) {
                icon.className = originalIcon;
            }
            delete link.dataset.playerBusy;
        }, 2000);
    }

    document.addEventListener('click', function (event) {
        var link = event.target.closest('[data-player-launch]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }
        event.preventDefault();

        var url = link.href;
        var query = link.dataset.playerQuery || '';
        var mode = link.dataset.playerMode === 'enqueue' ? 'enqueue' : 'load';
        var playerPath = new URL(url, window.location.href).pathname;

        // an empty URL returns the existing named window untouched, or opens a blank one
        var player = window.open('', WINDOW_NAME, FEATURES);
        if (!player) {
            window.location.href = url;
            return;
        }

        var isOpen = false;
        try {
            isOpen = player.location.pathname === playerPath && !!player.document.getElementById('player');
        } catch {
            // not readable (still opening, or navigated elsewhere): treat as closed
        }

        if (isOpen && channel) {
            channel.postMessage({ type: mode, query: query });
            if (mode === 'enqueue') {
                flash(link, 'Added to player');
            }
        } else {
            player.location.href = url;
        }
        player.focus();
    });
})();
