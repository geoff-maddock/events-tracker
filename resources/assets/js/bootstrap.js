import axios from 'axios';

/**
 * SweetAlert2 is a third of this bundle but only used for dialogs, so it is
 * fetched on demand (#2302): window.Swal starts as a stand-in whose fire()
 * loads the library and passes the call on, returning the same result promise.
 * The real library replaces window.Swal once loaded. Loading starts early on
 * the first pointer, key or touch event, so a confirm dialog opened by that
 * click is usually ready, and 5 s after page load at the latest.
 *
 * If the chunk can't be fetched (offline, or a tab left open across a deploy
 * that changed its hash), dialogs fall back to the browser's own confirm() or
 * alert() rather than failing silently. The browser caches a failed import,
 * so the fallback stays in use until the page is reloaded.
 */
let swalLoading = null;
const loadSwal = () => {
    swalLoading ??= import('sweetalert2').then((m) => (window.Swal = m.default));
    return swalLoading;
};
const nativeFire = (options) => {
    const o = typeof options === 'object' && options !== null ? options : { title: String(options ?? '') };
    const message = [o.title, o.text].filter(Boolean).join('\n\n') || 'Are you sure?';
    if (o.showCancelButton) {
        const confirmed = window.confirm(message);
        return { isConfirmed: confirmed, isDenied: false, isDismissed: !confirmed, value: confirmed || undefined };
    }
    window.alert(message);
    return { isConfirmed: true, isDenied: false, isDismissed: false, value: true };
};
const Swal = {
    fire: (...args) => loadSwal().then(
        (swal) => swal.fire(...args),
        () => nativeFire(args[0]),
    ),
};
window.Swal = Swal;

const swalTriggers = ['pointerdown', 'keydown', 'touchstart'];
const preloadSwal = () => {
    swalTriggers.forEach((e) => window.removeEventListener(e, preloadSwal));
    loadSwal();
};
swalTriggers.forEach((e) => window.addEventListener(e, preloadSwal, { once: true, passive: true }));
window.addEventListener('load', () => setTimeout(loadSwal, 5000));

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Next we will register the CSRF Token as a common header with Axios so that
 * all outgoing HTTP requests automatically have it attached. This is just
 * a simple convenience so we don't have to attach every token manually.
 */

let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

/**
 * Global confirm-modal handler: the one place a SweetAlert2 confirmation is
 * attached to a destructive or state-changing action (#2269).
 *
 * - <form data-confirm="message">: confirms before the form submits.
 * - <button type="submit" data-confirm="message">: the same, for that button only.
 * - <a href data-confirm="message">: confirms, then follows the link, or POSTs to
 *   it when the link also has data-method (the state-changing links, #2166).
 *
 * The message may be empty for a plain "Are you sure?". Optional attributes:
 * data-confirm-title, data-confirm-button.
 */
const confirmAction = function (source) {
    const message = source.getAttribute('data-confirm');
    return Swal.fire({
        title: source.dataset.confirmTitle || 'Are you sure?',
        text: message || undefined,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DD6B55',
        confirmButtonText: source.dataset.confirmButton || 'Yes, delete it!',
        cancelButtonText: 'Cancel',
    }).then((result) => result.isConfirmed === true);
};

const postTo = function (url) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.style.display = 'none';
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = '_token';
    const meta = document.head.querySelector('meta[name="csrf-token"]');
    input.value = meta ? meta.content : '';
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
};

document.addEventListener('submit', function (e) {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    const submitter = e.submitter;
    const source = submitter && submitter.hasAttribute('data-confirm') ? submitter : form;
    if (!source.hasAttribute('data-confirm')) return;

    e.preventDefault();
    confirmAction(source).then((confirmed) => {
        if (!confirmed) return;
        // form.submit() drops the clicked button's name/value, so carry it over
        if (submitter && submitter.name) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = submitter.name;
            input.value = submitter.value;
            form.appendChild(input);
        }
        form.submit();
    });
}, true);

document.addEventListener('click', function (e) {
    const link = e.target instanceof Element ? e.target.closest('a[data-confirm]') : null;
    if (!link) return;

    e.preventDefault();
    confirmAction(link).then((confirmed) => {
        if (!confirmed) return;
        if (link.dataset.method) {
            postTo(link.href);
        } else {
            window.location.href = link.href;
        }
    });
}, true);

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY;

window.Echo = null;

// Pusher and Echo are only fetched when broadcasting is configured (#2175)
if (pusherKey) {
    Promise.all([import('laravel-echo'), import('pusher-js')]).then(([{ default: Echo }, { default: Pusher }]) => {
        window.Pusher = Pusher;
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: pusherKey,
            cluster: 'us2',
            encrypted: true,
        });

        window.Echo.channel('events')
            .listen('EventUpdated', e => {
                const message = 'Event #' + e.event.id + ' "' + e.event.name + '" was updated.';
                Swal.fire({
                    title: 'Event Updated',
                    text: message,
                    icon: 'info',
                    timer: 2500,
                    showConfirmButton: false,
                    preConfirm: function () {
                        return new Promise(function (resolve) {
                            setTimeout(function () {
                                resolve();
                            }, 2000);
                        });
                    }
                });
            })
            // .listen('EventCreated', e => {
            //     const message = 'Event #' + e.event.id + ' "' + e.event.name + '" was created.';
            //     Swal.fire({
            //         title: "New Event Created",
            //         text: message,
            //         type: "info",
            //         timer: 2500,
            //         showConfirmButton: false,
            //         preConfirm: function() {
            //             return new Promise(function(resolve) {
            //                 setTimeout(function() {
            //                     resolve()
            //                 }, 2000)
            //             })
            //         }
            //     });
            //     console.log('Event created.');
            //     console.log(e);
            // })
            ;
    });
}
