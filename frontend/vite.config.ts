import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  base: './',
  plugins: [react()],
  server: {
    host: '0.0.0.0',
    port: 5173,
    allowedHosts: true as any,
    cors: true,
    headers: {
      'X-Frame-Options': 'ALLOWALL'
    },
    proxy: {
      '/admin/api': 'http://127.0.0.1:8080',
      '/public': 'http://127.0.0.1:8080',
      '/api': 'http://127.0.0.1:8080'
    }
  },
  preview: {
    host: '0.0.0.0',
    port: 4173
  }
})
