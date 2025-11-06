import { defineConfig } from 'vite'

export default defineConfig({
  root: 'frontend',
  build: {
    outDir: '../dist',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        main: 'frontend/css/input.css'
      }
    }
  },
  server: {
    port: 3000,
    proxy: {
      '/carwash_project': {
        target: 'http://localhost',
        changeOrigin: true
      }
    }
  }
})