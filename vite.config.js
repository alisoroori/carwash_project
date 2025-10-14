import { defineConfig } from 'vite'
import legacy from '@vitejs/plugin-legacy'

export default defineConfig({
  // Root directory for development server
  root: './frontend',
  
  // Public directory for static assets
  publicDir: '../backend/auth/uploads',
  
  // Build configuration
  build: {
    // Output directory relative to root
    outDir: '../dist',
    
    // Generate manifest for PHP integration
    manifest: true,
    
    // Multiple entry points
    rollupOptions: {
      input: {
        main: './frontend/js/main.js',
        styles: './src/input.css'
      }
    }
  },
  
  // CSS configuration - Remove the require() calls and use PostCSS config file
  css: {
    postcss: './postcss.config.js',
  },
  
  // Development server configuration
  server: {
    // Port for Vite dev server
    port: 3000,
    
    // CORS configuration for PHP backend
    cors: true,
    
    // Proxy configuration for PHP backend
    proxy: {
      '/backend': {
        target: 'http://localhost',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/backend/, '/carwash_project/backend')
      },
      '/api': {
        target: 'http://localhost/carwash_project',
        changeOrigin: true
      }
    }
  },
  
  // Plugins
  plugins: [
    // Legacy browser support
    legacy({
      targets: ['defaults', 'not IE 11']
    })
  ],
  
  // Define global constants
  define: {
    __APP_VERSION__: JSON.stringify(process.env.npm_package_version),
  }
})