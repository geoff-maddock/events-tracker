let mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

// base app js and css
// mix.js('resources/assets/js/app.js', 'public/js')
//     .sass('resources/assets/sass/app.scss', 'public/css');

mix.js('resources/assets/js/app.js', 'public/js')
    .sass('resources/assets/sass/app.scss', 'public/css');

mix.styles([
    'public/css/app.css',
    'resources/assets/css/custom.css',
    'resources/assets/css/dark-theme.css',
    'resources/assets/css/superhero-bootstrap.min.css',
    'resources/assets/css/sweetalert.css'
], 'public/css/dark.css');

mix.styles([
    'public/css/app.css',
    'resources/assets/css/custom.css',
    'resources/assets/css/light-theme.css',
    'resources/assets/css/sweetalert.css'
], 'public/css/light.css');