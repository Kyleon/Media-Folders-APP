<script setup>
import BottomNav from './BottomNav.vue';
import { useRoute, useRouter } from 'vue-router';
import { computed, onMounted } from 'vue';
import { useBrandStore } from '../stores/brand';

const route  = useRoute();
const router = useRouter();
const brand  = useBrandStore();

const titles = {
  dashboard: 'Inicio',
  media: 'Medios',
  'media-detail': 'Imagen',
  folders: 'Carpetas',
  upload: 'Subir',
  portfolios: 'Portfolios',
  'portfolio-new': 'Nuevo portfolio',
  'portfolio-detail': 'Portfolio',
  'portfolio-categories': 'Categorías',
  'client-galleries': 'Galerías de cliente',
  'client-gallery-detail': 'Galería de cliente',
  users: 'Usuarios',
  'user-detail': 'Usuario',
  map: 'Mapa',
  exif: 'Estadísticas EXIF',
  settings: 'Ajustes',
};
const title = computed(() => titles[route.name] || brand.name || 'YPVA');

const showBack = computed(() => {
  // Mostrar back en sub-rutas; en raíces de cada tab (root paths) no.
  const roots = ['/', '/media', '/portfolios', '/folders', '/map', '/upload', '/settings', '/client-galleries', '/users'];
  return !roots.includes(route.path);
});

onMounted(() => {
  // Hidrata desde localStorage para no parpadear, luego carga del server
  brand.hydrateFromCache();
  brand.load();
});

function isImageLogo() {
  return !!brand.logoUrl;
}
</script>

<template>
  <div class="shell">
    <header class="topbar safe-top" role="banner">
      <button class="topbar-back" v-if="showBack" @click="router.back()" aria-label="Volver">‹</button>

      <button class="brand-mark"
        :class="{ 'has-initials': !isImageLogo() }"
        @click="router.push({ name: 'dashboard' })"
        :aria-label="`Ir al inicio (${brand.name || 'YPVA'})`">
        <img v-if="isImageLogo()" :src="brand.logoUrl" :alt="brand.name || 'Logo'" class="brand-logo" />
        <span v-else class="brand-initials">{{ brand.initials }}</span>
      </button>

      <h1 class="topbar-title">{{ title }}</h1>

      <div class="topbar-actions">
        <button class="topbar-icon" @click="router.push({ name: 'settings' })" aria-label="Ajustes">⚙</button>
      </div>
    </header>
    <main class="content" role="main"><slot /></main>
    <BottomNav />
  </div>
</template>

<style scoped>
.shell {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  min-height: 100dvh;
}
.topbar {
  position: sticky; top: 0;
  display: flex;
  align-items: center;
  gap: 8px;
  height: 52px;
  padding: 0 16px;
  background: var(--bg);
  border-bottom: 1px solid var(--border);
  z-index: 10;
  backdrop-filter: blur(8px);
}
.topbar-back {
  width: 36px; height: 36px;
  margin-left: -8px;
  font-size: 28px;
  color: var(--text-mute);
}
/* brand-mark se adapta al ancho del logo (manteniendo la altura fija de la
   topbar). Cuando solo hay iniciales, queda como cuadrado con fondo. */
.brand-mark {
  display: inline-flex; align-items: center; justify-content: center;
  height: 40px;
  width: auto;
  min-width: 40px;
  max-width: 220px;
  padding: 2px 8px;
  border-radius: 8px;
  background: transparent;
  overflow: hidden;
  flex-shrink: 0;
  cursor: pointer;
  border: 0;
  transition: background .12s, transform .12s;
}
.brand-mark:hover { background: var(--s2); transform: scale(1.04); }

/* Modo iniciales: mantén el cuadrado con fondo */
.brand-mark.has-initials {
  width: 40px;
  background: var(--s2);
  padding: 0;
}
.brand-mark.has-initials:hover { background: var(--s3); }

.brand-logo {
  height: 100%;
  width: auto;
  max-width: 100%;
  object-fit: contain;
  display: block;
}
.brand-initials { font-size: 14px; font-weight: 700; color: var(--accent); letter-spacing: .5px; }

.topbar-title {
  margin: 0;
  font-size: 17px;
  font-weight: 600;
  letter-spacing: .2px;
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.topbar-actions {
  display: flex; gap: 4px;
  margin-left: auto;
}
.topbar-icon {
  width: 36px; height: 36px;
  border-radius: 8px;
  font-size: 18px;
  color: var(--text-mute);
  transition: color .12s, background .12s;
}
.topbar-icon:hover { color: var(--accent); background: var(--s2); }
.content {
  flex: 1;
  padding: 16px;
  padding-bottom: calc(80px + env(safe-area-inset-bottom));
}

/* Tablet ≥768px: padding más generoso, contenido centrado */
@media (min-width: 768px) {
  .topbar {
    height: 60px;
    padding: 0 32px;
  }
  .topbar-title { font-size: 19px; }
  .brand-mark { height: 46px; max-width: 280px; }
  .brand-mark.has-initials { width: 46px; }
  .content {
    max-width: 1180px;
    margin: 0 auto;
    padding: 24px 32px calc(80px + env(safe-area-inset-bottom));
  }
}

@media (min-width: 1600px) {
  .brand-mark { max-width: 320px; }
}

/* Escritorio ≥1024px: dejar 220px a la izquierda para el sidebar.
   Aprovechamos todo el ancho disponible — cada vista decide su rejilla interna. */
@media (min-width: 1024px) {
  .topbar {
    padding-left: calc(220px + 24px);
  }
  .content {
    padding-left: calc(220px + 24px);
    padding-right: 24px;
    padding-bottom: 32px;
    max-width: none;
    margin: 0;
  }
}

/* Pantallas grandes (>1440px) y 4K: padding más generoso */
@media (min-width: 1600px) {
  .content { padding-right: 40px; padding-left: calc(220px + 40px); }
  .topbar  { padding-left: calc(220px + 40px); }
}
</style>
