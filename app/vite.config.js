import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  base: '/',
  server: {
    port: 5173,
    proxy: {
      '/version2/api': {
        target: 'https://version2-3-juss.onrender.com',
        changeOrigin: true,
      },
      '/version2/auth': {
        target: 'https://version2-3-juss.onrender.com',
        changeOrigin: true,
      }
    }
  }
})