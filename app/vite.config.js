import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
  plugins: [
    vue(),
    VitePWA({
      registerType: 'prompt',
      includeAssets: ['favicon.svg', 'robots.txt'],
      manifest: {
        name: 'Yezrael Pérez · Admin',
        short_name: 'YPVA',
        description: 'Panel móvil para gestionar medios y portfolios.',
        theme_color: '#0f0f0f',
        background_color: '#0f0f0f',
        display: 'standalone',
        orientation: 'portrait',
        start_url: '/',
        scope: '/',
        icons: [
          { src: 'icons/icon-192.png',          sizes: '192x192', type: 'image/png', purpose: 'any' },
          { src: 'icons/icon-512.png',          sizes: '512x512', type: 'image/png', purpose: 'any' },
          { src: 'icons/icon-512-maskable.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
        ],
      },
      workbox: {
        globPatterns: ['**/*.{js,css,html,svg,png,ico,woff2}'],
        // Servicio offline simple — la app degrada con gracia si la API no responde
        runtimeCaching: [
          {
            urlPattern: ({ url }) => url.pathname.startsWith('/wp-json/yzmf/v1/map/data'),
            handler: 'StaleWhileRevalidate',
            options: { cacheName: 'yzmf-map' },
          },
        ],
      },
    }),
  ],
  server: {
    host: true,
    port: 5173,
  },
  build: {
    outDir: 'dist',
    sourcemap: true,
  },
});
