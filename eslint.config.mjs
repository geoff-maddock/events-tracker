import js from '@eslint/js';
import globals from 'globals';

export default [
    {
        ignores: [
            'public/js/app.js', // the Vite bundle
            'public/js/swagger.js', // committed swagger-ui build behind /api/docs
            'public/js/*.min.js',
        ],
    },

    js.configs.recommended,

    {
        files: ['resources/assets/js/**/*.js'],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                ...globals.browser,
                // Exposed on window by resources/assets/js/bootstrap.js.
                Swal: 'readonly',
            },
        },
        rules: {
            // Surface unused symbols without failing the build on pre-existing
            // ones; real bugs (no-undef etc.) stay as errors via recommended.
            'no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
        },
    },

    // The hand-written scripts served straight from public/js (#2269). They're
    // classic <script> tags, not modules, and run beside jQuery and the Vite bundle.
    {
        files: ['public/js/*.js'],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'script',
            globals: {
                ...globals.browser,
                ...globals.jquery,
                Swal: 'readonly',
            },
        },
        rules: {
            'no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
        },
    },
];
