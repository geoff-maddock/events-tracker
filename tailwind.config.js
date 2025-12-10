/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
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
    'lg:col-span-1',
    'lg:col-span-2',
    'lg:grid-cols-[2fr,1fr]',
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

