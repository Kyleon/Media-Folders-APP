<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';

/**
 * Wrapper que activa pull-to-refresh cuando el usuario arrastra
 * desde el top de la página (con el scroll en 0).
 *
 * Uso:
 *   <PullRefresh @refresh="onRefresh" />
 *   async function onRefresh() { await loadData(); }
 */
const emit = defineEmits(['refresh']);

const startY    = ref(0);
const pullY     = ref(0);
const refreshing = ref(false);
const ready     = ref(false);     // pasó el umbral
const TRIGGER_DISTANCE = 70;

let active = false;

function onTouchStart(e) {
  // Sólo si estamos arriba del scroll
  const scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
  if (scrollTop > 0 || refreshing.value) { active = false; return; }
  active = true;
  startY.value = e.touches[0].clientY;
  pullY.value  = 0;
  ready.value  = false;
}

function onTouchMove(e) {
  if (!active || refreshing.value) return;
  const dy = e.touches[0].clientY - startY.value;
  if (dy <= 0) { pullY.value = 0; return; }
  // Resistencia: cada vez se nota más pesado
  pullY.value = Math.min(120, dy * 0.5);
  ready.value = pullY.value >= TRIGGER_DISTANCE;
}

async function onTouchEnd() {
  if (!active) return;
  active = false;
  if (ready.value && !refreshing.value) {
    refreshing.value = true;
    pullY.value = TRIGGER_DISTANCE;
    try {
      await emit('refresh');
    } finally {
      // Pequeño delay para que se note la animación
      setTimeout(() => {
        refreshing.value = false;
        pullY.value = 0;
        ready.value = false;
      }, 300);
    }
  } else {
    pullY.value = 0;
    ready.value = false;
  }
}

onMounted(() => {
  document.addEventListener('touchstart', onTouchStart, { passive: true });
  document.addEventListener('touchmove',  onTouchMove,  { passive: true });
  document.addEventListener('touchend',   onTouchEnd);
});

onBeforeUnmount(() => {
  document.removeEventListener('touchstart', onTouchStart);
  document.removeEventListener('touchmove',  onTouchMove);
  document.removeEventListener('touchend',   onTouchEnd);
});
</script>

<template>
  <div class="pr-indicator" :style="{ transform: `translateY(${pullY}px)` }" v-show="pullY > 0 || refreshing">
    <div class="pr-icon" :class="{ ready, refreshing }">
      <span v-if="refreshing" class="spinner"></span>
      <span v-else>{{ ready ? '↑' : '↓' }}</span>
    </div>
    <span class="pr-text muted">{{ refreshing ? 'Actualizando…' : (ready ? 'Suelta para actualizar' : 'Tira para actualizar') }}</span>
  </div>
</template>

<style scoped>
.pr-indicator {
  position: fixed;
  top: 52px;     /* debajo del topbar */
  left: 0; right: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 12px 0;
  pointer-events: none;
  z-index: 100;
  transition: transform .2s;
}
.pr-icon {
  width: 32px; height: 32px;
  background: var(--s1);
  border: 1px solid var(--border);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 18px;
  box-shadow: var(--shadow);
  transition: transform .2s, color .15s;
}
.pr-icon.ready { color: var(--accent); transform: rotate(180deg); }
.pr-text { font-size: 11px; }
.spinner {
  width: 14px; height: 14px;
  border: 2px solid var(--accent);
  border-right-color: transparent;
  border-radius: 50%;
  animation: spin .8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
