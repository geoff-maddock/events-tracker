let mix = require('laravel-mix');

// purgecss is intended to remove any css that is not used by the project
require('laravel-mix-purgecss');

// base app js and css
// this builds public/css/app.css
mix.js('resources/assets/js/app.js', 'public/js')
    .sass('resources/assets/sass/app.scss', 'public/css')
    // .purgeCss()
;


// build dark theme
// use the app.css above
mix.styles([
    'public/css/app.css',
    'resources/assets/css/custom.css',
    'resources/assets/css/dark-theme.css',
    'resources/assets/css/sweetalert.css'
], 'public/css/dark.css')
// .purgeCss()
;

// build light theme
mix.styles([
    'public/css/app.css',
    'resources/assets/css/custom.css',
    'resources/assets/css/light-theme.css',
    'resources/assets/css/flatly-bootstrap.min.css',
    'resources/assets/css/sweetalert.css'
], 'public/css/light.css')
// .purgeCss()
;

