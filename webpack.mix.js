let mix = require('laravel-mix');

// purgecss is intended to remove any css that is not used by the project
require('laravel-mix-purgecss');

// base app js and css
// this builds public/css/app.css
mix.js('resources/assets/js/app.js', 'public/js')
    .sass('resources/assets/sass/app.scss', 'public/css')
    .sourceMaps()
    // .purgeCss()
;


// build dark theme
// use the app.css above
mix.styles([
    'public/css/app.css',
    'resources/assets/css/custom-layout.css',
    'resources/assets/css/dark-theme.css',
    // 'resources/assets/css/superhero-bootstrap.min.css', // no need to add this here 
    'resources/assets/css/sweetalert.css'
], 'public/css/dark.css')
// .purgeCss()
;

// build light theme
mix.styles([
    'public/css/app.css',
    // disable to test out flatly theme on it's own
    'resources/assets/css/custom-layout.css',
    'resources/assets/css/light-theme.css',
    'resources/assets/css/flatly-bootstrap.min.css',  // this overrides with light theme styles from bootswatch flatly
    'resources/assets/css/sweetalert.css'
], 'public/css/light.css')
// .purgeCss()
;

