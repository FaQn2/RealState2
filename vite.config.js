import { defineConfig } from 'vite';
import postcss from './postcss.config.js';

export default defineConfig({
  root: '.',
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    rollupOptions: {
      input: 'assets/css/style.css',
      output: {
        assetFileNames: (assetInfo) => {
          // Si es exactamente style.css, mantenemos ese nombre
          if (assetInfo.name === 'style.css') return 'assets/style.css';
          // Otros assets pueden tener hash
          return 'assets/[name]-[hash][extname]';
        },
      },
    },
  },
  css: {
    postcss,
  },
});
