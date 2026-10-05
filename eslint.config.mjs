import js from '@eslint/js';
import globals from 'globals';

export default [
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
];
