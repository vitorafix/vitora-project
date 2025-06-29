// tailwind.config.js
module.exports = {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue', // اگر از Vue.js استفاده می‌کنید، این خط را اضافه کنید
  ],
  theme: {
    extend: {
      colors: {
        brown: {
          900: '#4a2c2a',
          800: '#6f4e37',
        },
        green: {
          700: '#38a169',
          800: '#2f855a',
        },
      },
      fontFamily: {
        sans: ['Vazirmatn', 'sans-serif'], // فونت سفارشی
      },
    },
  },
  plugins: [],
}