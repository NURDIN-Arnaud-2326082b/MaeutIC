import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const apiBaseUrl = env.VITE_API_URL || 'http://localhost:8000/api'
  const backendTarget = /^https?:\/\//.test(apiBaseUrl)
    ? apiBaseUrl.replace(/\/api\/?$/, '')
    : 'http://localhost:8000'

  const publicAssetDirs = [
    '/profile_images',
    '/post_images',
    '/post_pdfs',
    '/author_images',
    '/book_images',
    '/article_images',
    '/article_pdfs',
    '/audio',
  ]

  const proxy = {
    '/api': {
      target: backendTarget,
      changeOrigin: true,
    },
  }

  publicAssetDirs.forEach((dir) => {
    proxy[dir] = {
      target: backendTarget,
      changeOrigin: true,
    }
  })

  return {
    plugins: [react()],
    server: {
      port: 3000,
      proxy,
    },
    build: {
      outDir: '../public/react',
      emptyOutDir: true,
      manifest: true,
      rollupOptions: {
        input: './src/main.jsx',
      },
    },
  }
})
