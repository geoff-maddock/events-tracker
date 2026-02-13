import _ from 'lodash';
import $ from 'jquery';
import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import Swal from 'sweetalert2';
import Visibility from './utilities/visibility';

window._ = _;

/**
 * We'll load jQuery and the Bootstrap jQuery plugin which provides support
 * for JavaScript based Bootstrap features such as modals and tabs. This
 * code may be modified to fit the specific needs of your application.
 */

window.$ = window.jQuery = $;

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
console.log('assets js bootstrap.js visibility init')

window.Pusher = Pusher;

const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY;

if (pusherKey) {
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
                preConfirm: function() {
                    return new Promise(function(resolve) {
                        setTimeout(function() {
                            resolve();
                        }, 2000);
                    });
                }
            });
            console.log('Event updated.');
            console.log(e);
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
} else {
    console.warn('VITE_PUSHER_APP_KEY is not set; realtime event listening is disabled.');
}
