/**
 * The site's JavaScript entry point (built by Vite). bootstrap.js sets up
 * axios, SweetAlert and the confirm modal; filter-panel.js is the list pages'
 * "Show Filters" toggle; dialog-focus.js keeps keyboard focus inside an open modal;
 * form-errors.js takes focus to the error summary of a failed form; toast.js is
 * window.notify(), the corner messages that flash() and failed requests use.
 */

import './bootstrap';
import './filter-panel';
import './dialog-focus';
import './form-errors';
import './toast';

// Note: Alpine.js is loaded via CDN in the layout file
// SweetAlert2 (window.Swal) is loaded on demand by bootstrap.js (#2302)

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
