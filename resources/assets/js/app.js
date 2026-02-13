/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import './bootstrap';

// Note: Alpine.js is loaded via CDN in the layout file
import Visibility from './utilities/visibility';

// used for dates
// window.moment = require('moment');

// sweet alert used for flash messages / alerts 
import Swal from 'sweetalert2';
window.Swal = Swal;

// dropzone used for file uploading
import { Dropzone } from 'dropzone';
window.Dropzone = Dropzone;

// flatpickr for date/time picking
import flatpickr from "flatpickr";
window.flatpickr = flatpickr;

// init visibility
Visibility.init('#event-repo');

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
