/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class',
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        canard: {
          50: '#e6f4f5',
          100: '#cce8ea',
          200: '#99d2d6',
          300: '#66bcbc',
          400: '#33a6a7',
          500: '#018c96',
          600: '#016d76',
          700: '#015762',
          800: '#014050',
          900: '#012a3a',
        },
        mandarine: {
          50: '#fef3e2',
          100: '#fde6c0',
          200: '#fbcd82',
          500: '#ec8927',
          600: '#d07620',
          700: '#b56519',
        },
      },
    },
  },
  plugins: [],
}
