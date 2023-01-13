let mix = require('laravel-mix');

// base app js and css
// this builds public/css/app.css
mix.js('resources/assets/js/app.js', 'public/js')
    .js('resources/assets/js/swagger.js','public/js')
    .sass('resources/assets/sass/app.scss', 'public/css')
    .sourceMaps()
;


// build dark theme
// use the app.css above
mix.styles([
    'public/css/app.css',
    'resources/assets/css/custom-layout.css',
    'resources/assets/css/dark-theme.css',
    'resources/assets/css/sweetalert.css'
], 'public/css/dark.css')
;

// build light theme
mix.styles([
    'public/css/app.css',
    'resources/assets/css/custom-layout.css',
    'resources/assets/css/light-theme.css',
    'resources/assets/css/flatly-bootstrap.min.css',  // this overrides with light theme styles from bootswatch flatly
    'resources/assets/css/sweetalert.css'
], 'public/css/light.css')
;

