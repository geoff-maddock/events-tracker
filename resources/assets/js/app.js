/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import 'bootstrap';
import Visibility from './utilities/visibility';

// used for dates
// window.moment = require('moment');

// sweet alert used for flash messages / alerts 
window.Swal = require('sweetalert2');

// dropzone used for file uploading 
const { Dropzone } = require("dropzone");
window.Dropzone = Dropzone;

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
