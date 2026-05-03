<script setup>
import { useRoute, useRouter } from 'vue-router';
import { computed } from 'vue';

const route  = useRoute();
const router = useRouter();

const items = [
  { name: 'dashboard',  label: 'Inicio',     icon: '◐' },
  { name: 'media',      label: 'Medios',     icon: '▦' },
  { name: 'upload',     label: 'Subir',      icon: '↑' },
  { name: 'portfolios', label: 'Portfolios', icon: '◇' },
  { name: 'map',        label: 'Mapa',       icon: '◈' },
];

const activeName = computed(() => {
  // Mapear sub-rutas a su tab principal
  if (route.name === 'media-detail')   return 'media';
  if (route.name?.startsWith('portfolio')) return 'portfolios';
  return route.name;
});
</script>

<template>
  <nav class="bottom-nav safe-bottom">
    <button
      v-for="it in items" :key="it.name"
      class="bn-btn" :class="{ active: activeName === it.name }"
      @click="router.push({ name: it.name })">
      <span class="bn-icon">{{ it.icon }}</span>
      <span class="bn-label">{{ it.label }}</span>
    </button>
  </nav>
</template>

<style scoped>
.bottom-nav {
  position: fixed;
  bottom: 0; left: 0; right: 0;
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  background: var(--s1);
  border-top: 1px solid var(--border);
  z-index: 50;
  padding-top: 4px;
  padding-bottom: env(safe-area-inset-bottom);
}
.bn-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 2px;
  height: 56px;
  color: var(--text-mute);
  font-size: 10px;
  transition: color .15s, background .15s;
}
.bn-btn.active { color: var(--accent); }
.bn-icon { font-size: 22px; line-height: 1; }
.bn-label { font-size: 10px; letter-spacing: .3px; }

/* Escritorio ≥1024px: barra lateral fija a la izquierda en lugar de bottom-nav */
@media (min-width: 1024px) {
  .bottom-nav {
    top: 0; bottom: auto;
    width: 220px;
    height: 100vh;
    grid-template-columns: 1fr;
    grid-auto-rows: 44px;
    align-content: flex-start;
    padding: 70px 12px 12px;
    border-top: 0;
    border-right: 1px solid var(--border);
    gap: 4px;
  }
  .bn-btn {
    flex-direction: row;
    justify-content: flex-start;
    height: 44px;
    gap: 12px;
    padding: 0 14px;
    border-radius: var(--radius);
    font-size: 14px;
  }
  .bn-btn:hover { background: var(--s2); }
  .bn-btn.active { background: var(--accent-lo); }
  .bn-icon { font-size: 18px; }
  .bn-label { font-size: 14px; }
}
</style>
