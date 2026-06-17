import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
  plugins: [
    vue(),
    VitePWA({
      // autoUpdate: el SW activa la nueva versión sin esperar confirmación.
      // Cambiado desde 'prompt' tras varios despliegues en los que el
      // banner de actualización no se mostraba en algunos clientes y
      // dejaba la PWA atascada en una versión antigua.
      registerType: 'autoUpdate',
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
        navigationPreload: true,
        runtimeCaching: [
          // Thumbnails y assets servidos por WordPress. Cache eterno con cap.
          {
            urlPattern: /\/wp-content\/uploads\/.*\.(?:jpg|jpeg|png|webp|svg|gif)(?:\?.*)?$/i,
            handler: 'CacheFirst',
            options: {
              cacheName: 'yzmf-uploads',
              expiration: { maxEntries: 400, maxAgeSeconds: 60 * 60 * 24 * 30 },
              cacheableResponse: { statuses: [0, 200] },
            },
          },
          // Listas pequeñas de la API que se consultan mucho. SWR.
          {
            urlPattern: ({ url }) => /\/wp-json\/yzmf\/v1\/(folders|stats|tags|colors|brand)/.test(url.pathname),
            handler: 'StaleWhileRevalidate',
            options: {
              cacheName: 'yzmf-lists',
              expiration: { maxEntries: 50, maxAgeSeconds: 60 * 10 },
            },
          },
          // Datos del mapa público (también accesible sin auth).
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
    // Sin sourcemaps en producción: en builds anteriores se subían a Hostinger
    // y permitían reverse-engineering del flujo de auth. Para debugging local
    // pásalo a 'inline' o 'hidden' temporalmente (deploy.ps1 ya filtra .map
    // como cinturón y tirantes).
    sourcemap: false,
    rollupOptions: {
      output: {
        // Chunks manuales para que las libs pesadas se cacheen entre rutas
        // en vez de duplicarse en cada vista que las importa.
        manualChunks: {
          leaflet: ['leaflet'],
          draggable: ['vue-draggable-plus'],
        },
      },
    },
  },
});
