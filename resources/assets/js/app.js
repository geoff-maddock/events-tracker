/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

 import 'bootstrap';

//import Swal from 'sweetalert2';
//const Swal = require('sweetalert2')

window.moment = require('moment');
window.Swal = require('sweetalert2');
window.Dropzone = require('dropzone');
console.log('loaded: /resources/assets/js');
// add any other dependencies - things i'm including manually in app.layout

window.Vue = require('vue');
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
