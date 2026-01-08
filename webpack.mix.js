let mix = require('laravel-mix');

mix.webpackConfig({
    stats: {
        children: true
    }
});

// Build main JavaScript with vendor splitting
mix.js('resources/assets/js/app.js', 'public/js')
    .extract(['vue', 'jquery', 'axios'])
    .sourceMaps();

// Build Tailwind CSS - single unified build
mix.postCss('resources/css/tailwind.css', 'public/css', [
    require('tailwindcss'),
    require('autoprefixer'),
]);

// Production optimizations
if (mix.inProduction()) {
    mix.version();
}
