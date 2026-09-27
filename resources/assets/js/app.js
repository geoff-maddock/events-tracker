/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import './bootstrap';

// Note: Alpine.js is loaded via CDN in the layout file
import Visibility from './utilities/visibility';

// sweet alert used for flash messages / alerts
import Swal from 'sweetalert2';
window.Swal = Swal;

// init visibility
Visibility.init('#event-repo');

/**
 * Heavy libraries are split into their own chunks and loaded only on the pages
 * that use them (#2175). window.loadLibrary(name) resolves with the library and
 * also sets it as the window global the inline page scripts expect.
 */
const libraries = {
    // chart.js: activity/event graphs and entity stats
    Chart: () => import('chart.js/auto').then((m) => m.default),
    // dropzone: photo upload forms; each page creates its own instance
    Dropzone: () => import('dropzone').then((m) => {
        m.Dropzone.autoDiscover = false;
        return m.Dropzone;
    }),
};
const loading = {};

window.loadLibrary = (name) => {
    loading[name] ??= libraries[name]().then((lib) => (window[name] = lib));
    return loading[name];
};

// start fetching Dropzone as soon as a page has an upload form
if (document.querySelector('form.dropzone')) {
    window.loadLibrary('Dropzone');
}

// add vue - not currently using
// window.Vue = require('vue');
//
// /**
//  * Next, we will create a fresh Vue application instance and attach it to
//  * the page. Then, you may begin adding components to this application
//  * or customize the JavaScript scaffolding to fit your unique needs.
//  */
//
// Vue.component('event-list', require('./components/EventList.vue'));
//
// const app = new Vue({
//     el: '#app-container'
// });
