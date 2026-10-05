import axios from 'axios';
import Swal from 'sweetalert2';
import Visibility from './utilities/visibility';

window.Swal = Swal;

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
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

Visibility.init('body');

/**
 * Global confirm-modal handler.
 *
 * Any <form data-confirm="message"> or <button data-confirm="message"> will
 * show a SweetAlert2 modal in place of the native window.confirm() dialog.
 * Optional attributes: data-confirm-title, data-confirm-button.
 */
document.addEventListener('submit', function (e) {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.dataset.confirmed === 'true') return;
    const message = form.dataset.confirm;
    if (!message) return;

    e.preventDefault();
    Swal.fire({
        title: form.dataset.confirmTitle || 'Are you sure?',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DD6B55',
        confirmButtonText: form.dataset.confirmButton || 'Yes, delete it!',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (result.value) {
            form.dataset.confirmed = 'true';
            form.submit();
        }
    });
}, true);

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
