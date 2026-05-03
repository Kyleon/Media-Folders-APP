<script setup>
import { onMounted, ref } from 'vue';

const needsRefresh = ref(false);
let updateSW = null;

onMounted(async () => {
  // vite-plugin-pwa expone useRegisterSW de forma virtual
  try {
    const { registerSW } = await import('virtual:pwa-register');
    updateSW = registerSW({
      onNeedRefresh() { needsRefresh.value = true; },
      onOfflineReady() { /* opcional: avisar offline-ready */ },
    });
  } catch (e) {
    // En dev no existe el módulo; ignorar
  }
});

async function applyUpdate() {
  if (updateSW) await updateSW(true);
}

function dismiss() {
  needsRefresh.value = false;
}
</script>

<template>
  <transition name="fade">
    <div v-if="needsRefresh" class="update-banner">
      <span>✨ Hay una versión nueva disponible</span>
      <div class="actions">
        <button class="btn-link" @click="dismiss">Más tarde</button>
        <button class="btn-pri" @click="applyUpdate">Actualizar</button>
      </div>
    </div>
  </transition>
</template>

<style scoped>
.update-banner {
  position: fixed;
  bottom: calc(80px + env(safe-area-inset-bottom));
  left: 16px; right: 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  background: var(--s1);
  border: 1px solid var(--accent);
  border-radius: var(--radius-lg);
  padding: 14px;
  box-shadow: var(--shadow);
  z-index: 200;
  font-size: 13px;
}
.actions { display: flex; gap: 8px; justify-content: flex-end; }
.btn-link {
  padding: 8px 12px;
  font-size: 13px;
  color: var(--text-mute);
}
.btn-pri {
  padding: 8px 16px;
  background: var(--accent);
  color: #0f0f0f;
  border-radius: var(--radius);
  font-size: 13px;
  font-weight: 600;
}

.fade-enter-active, .fade-leave-active { transition: opacity .25s, transform .25s; }
.fade-enter-from, .fade-leave-to       { opacity: 0; transform: translateY(8px); }
</style>
