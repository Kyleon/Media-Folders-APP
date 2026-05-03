<script setup>
import BottomNav from './BottomNav.vue';
import { useRoute } from 'vue-router';
import { computed } from 'vue';

const route = useRoute();

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
  map: 'Mapa',
  settings: 'Ajustes',
};
const title = computed(() => titles[route.name] || 'YPVA');
</script>

<template>
  <div class="shell">
    <header class="topbar safe-top">
      <button class="topbar-back" v-if="$route.path !== '/' && $route.path !== '/media' && $route.path !== '/portfolios' && $route.path !== '/map' && $route.path !== '/upload' && $route.path !== '/settings'" @click="$router.back()">‹</button>
      <h1 class="topbar-title">{{ title }}</h1>
    </header>
    <main class="content"><slot /></main>
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
.topbar-title {
  margin: 0;
  font-size: 17px;
  font-weight: 600;
  letter-spacing: .2px;
}
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
  .content {
    max-width: 1180px;
    margin: 0 auto;
    padding: 24px 32px calc(80px + env(safe-area-inset-bottom));
  }
}

/* Escritorio ≥1024px: dejar 220px a la izquierda para el sidebar */
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
</style>
