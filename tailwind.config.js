/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./views/**/*.php",
    "./public/**/*.js"
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
        display: ['Space Grotesk', 'sans-serif'],
      },
    }
  },
  plugins: [],
}