import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],

  // React is served from /version2/app/
  base: '/version2/app/',

  server: {
    port: 5173,

    proxy: {
      '/version2/api': {
        target: 'http://localhost',
        changeOrigin: true,
      },

      '/version2/auth': {
        target: 'http://localhost',
        changeOrigin: true,
      }
    }
  }
});