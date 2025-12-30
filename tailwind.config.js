/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  important: true, // Add !important to all utilities to override Bootstrap
  content: [
    './resources/views/**/*.blade.php',
    './resources/js/**/*.js',
    './resources/js/**/*.vue',
  ],
  safelist: [
    'grid-cols-1',
    'grid-cols-2',
    'grid-cols-3',
    'lg:grid-cols-1',
    'lg:grid-cols-2',
    'lg:grid-cols-3',
    'lg:grid-cols-4',
    'xl:grid-cols-4',
    '2xl:grid-cols-4',
    'lg:col-span-1',
    'lg:col-span-2',
    'lg:grid-cols-[2fr,1fr]',
    // Dark mode classes for theme toggle
    'dark:bg-dark-surface',
    'dark:bg-dark-card',
    'dark:bg-dark-bg',
    'dark:bg-dark-border',
    'dark:border-dark-border',
    'dark:text-gray-100',
    'dark:text-gray-300',
    'dark:text-gray-400',
    'dark:text-white',
    'dark:hover:bg-dark-border',
    'dark:hover:text-white',
    'bg-white',
    'bg-gray-100',
    'border-gray-200',
    'text-gray-900',
    'text-gray-600',
    'text-gray-500',
    'hover:bg-gray-200',
    'hover:text-gray-900',
  ],
  theme: {
    extend: {
      screens: {
        '2xl': '1600px',
        '3xl': '2000px',
      },
      colors: {
        // Custom dark theme colors matching the design mockup
        'dark-bg': '#0f172a',
        'dark-surface': '#1e293b',
        'dark-card': '#334155',
        'dark-border': '#475569',
        'primary': '#5bc0de',
        'primary-hover': '#7be0ee',
        'accent': '#df691a',
        'accent-hover': '#ec8541',
      },
      fontFamily: {
        sans: ['Nunito', 'Roboto', 'sans-serif'],
      },
    },
  },
  plugins: [
    require('@tailwindcss/line-clamp'),
  ],
}

