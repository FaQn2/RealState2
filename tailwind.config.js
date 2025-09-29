
/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './*.php',
    './templates/front-page.php',
    './templates/selector.php',
    './assets/js/**/*.js',
    './apartamento/**/*.php', 
     "./filtros/**/*.php"
  ],
  theme: {
    extend: {
      colors: {
        primary: 'rgb(var(--color-primary) / <alpha-value>)',
        secondary: 'rgb(var(--color-secondary) / <alpha-value>)',
        text: 'rgb(var(--color-text) / <alpha-value>)',
        white: 'rgb(var(--color-white) / <alpha-value>)',
        black: 'rgb(var(--color-black) / <alpha-value>)',
      },
      screens: {
        // Apunta a anchuras de dispositivos concretos
        'xs': '320px',          // iPhone 5/SE
        'xs2': '375px',        // punto medio “entre”
        'xs3': '420px',         // iPhone 13 Pro Max
        // luego vienen los estándares
        /* 'sm': '640px',
        'md': '768px',
        'lg': '1024px', */
      },
      fontFamily: {
        fuente_primaria: 'var(--fuente_primaria)',
        fuente_secundaria: 'var(--fuente_secundaria)', 
      },
      keyframes: {
        // simple fade en paginas, galerias, y cards de info y selector en desktop
        'fade-in': {
          '0%':   { opacity: '0' },
          '100%': { opacity: '1' }
        },
        'fade-out': {
          '0%':   { opacity: '1' },
          '100%': { opacity: '0' }
        },
        //fade de selector de pisos e info en Mobile
        'fade-in-up': {
          '0%':   { opacity: '1', transform: 'translateY(100%)' },
          '100%': { opacity: '1', transform: 'translateY(0)' }
        },
         'fade-out-down': {
          '0%':   { opacity: '1', transform: 'translateY(0)' },
          '100%': { opacity: '1', transform: 'translateY(100%)' }
        },
      },
      animation: {
        'fade-in': 'fade-in .3s linear  both',
        'fade-out': 'fade-out .3s linear   both',
        'fade-in-up': 'fade-in-up 0.5s ease-out both',
        'fade-out-down': 'fade-out-down 0.5s ease-out both',
      },
    },
  },
  plugins: [],
};
