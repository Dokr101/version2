import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  base: '/version2/app/dist/',
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
})
