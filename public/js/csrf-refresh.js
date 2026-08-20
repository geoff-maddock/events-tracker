// Refresh the CSRF token when a stale tab becomes visible again, so a form
// rendered before the session expired can still post successfully.
(function () {
    'use strict';

    function refreshToken() {
        fetch('/csrf-token', {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        })
            .then(function (response) {
                return response.ok ? response.json() : null;
            })
            .then(function (data) {
                if (!data || !data.token) {
                    return;
                }
                document.querySelectorAll('input[name="_token"]').forEach(function (input) {
                    input.value = data.token;
                });
                var meta = document.querySelector('meta[name="csrf-token"]');
                if (meta) {
                    meta.setAttribute('content', data.token);
                }
                if (window.axios) {
                    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = data.token;
                }
            })
            .catch(function () {
                // Ignore network errors — the exception handler's 419 redirect
                // is the fallback path.
            });
    }

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            refreshToken();
        }
    });

    // bfcache restore (common on mobile Safari) skips normal page load events.
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            refreshToken();
        }
    });
})();
