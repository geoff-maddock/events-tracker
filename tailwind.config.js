/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: [
    './resources/views/**/*.blade.php',
    './resources/js/**/*.js',
    './resources/js/**/*.vue',
  ],
  theme: {
    extend: {
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

