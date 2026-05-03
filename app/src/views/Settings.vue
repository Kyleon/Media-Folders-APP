<script setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useUiStore } from '../stores/ui';
import ThemeSwitch from '../components/ThemeSwitch.vue';

const router = useRouter();
const auth   = useAuthStore();
const ui     = useUiStore();

function logout() {
  auth.logout();
  router.replace({ name: 'login' });
}

function clearCaches() {
  // Limpia el cache del SW si existe
  if ('caches' in window) {
    caches.keys().then(keys => keys.forEach(k => caches.delete(k)));
  }
  ui.toast('🧹 Cachés limpiadas', 'ok');
}
</script>

<template>
  <div>
    <div class="card">
      <h3 class="section">Sesión</h3>
      <div class="kv-row"><span class="muted">Sitio</span><span class="val">{{ auth.creds?.baseUrl }}</span></div>
      <div class="kv-row"><span class="muted">Usuario</span><span class="val">{{ auth.creds?.username }}</span></div>
      <button class="btn danger" @click="logout" style="width:100%;margin-top:12px">Cerrar sesión</button>
    </div>

    <div class="card" style="margin-top:14px">
      <h3 class="section">Apariencia</h3>
      <div class="kv-row">
        <span class="muted">Tema</span>
        <ThemeSwitch />
      </div>
    </div>

    <div class="card" style="margin-top:14px">
      <h3 class="section">Gestión</h3>
      <button class="btn" @click="$router.push({ name: 'folders' })" style="width:100%;margin-bottom:8px">
        📁 Carpetas de medios
      </button>
      <button class="btn" @click="$router.push({ name: 'portfolio-categories' })" style="width:100%">
        📂 Categorías de portfolio
      </button>
    </div>

    <div class="card" style="margin-top:14px">
      <h3 class="section">Mantenimiento</h3>
      <button class="btn" @click="clearCaches" style="width:100%">🧹 Limpiar cachés</button>
      <p class="muted small" style="margin-top:8px">
        Limpia los datos cacheados por el Service Worker.
        Útil si la app no muestra los últimos cambios.
      </p>
    </div>

    <div class="card" style="margin-top:14px">
      <h3 class="section">Acerca de</h3>
      <p class="muted small">
        YPVA Admin · v0.1.0<br>
        Panel móvil para Yezrael Pérez · yezraelperez.es
      </p>
    </div>
  </div>
</template>

<style scoped>
.section { margin: 0 0 12px; font-size: 12px; text-transform: uppercase; letter-spacing: .5px; color: var(--text-mute); font-weight: 600; }
.kv-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; font-size: 13px; border-bottom: 1px solid var(--border); }
.kv-row:last-child { border-bottom: 0; }
.val { font-size: 12px; max-width: 60%; text-align: right; word-break: break-all; }
.small { font-size: 11px; }
</style>
