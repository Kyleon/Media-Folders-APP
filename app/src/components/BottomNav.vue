<script setup>
import { useRoute, useRouter } from 'vue-router';
import { computed, ref } from 'vue';

const route  = useRoute();
const router = useRouter();

// Ítems principales: siempre visibles en la barra inferior (móvil).
const primary = [
  { name: 'dashboard', label: 'Inicio', icon: '◐' },
  { name: 'media',     label: 'Medios', icon: '▦' },
  { name: 'feed',      label: 'Feed',   icon: '❏' },
  { name: 'upload',    label: 'Subir',  icon: '↑' },
];

// Ítems secundarios: en móvil viven bajo el botón "Más"; en escritorio se
// muestran planos en la barra lateral (que ya escala con grid-auto-rows).
const overflow = [
  { name: 'folders',    label: 'Carpetas',   icon: '▤' },
  { name: 'portfolios', label: 'Portfolios', icon: '◇' },
  { name: 'sliders',    label: 'Sliders',    icon: '⊞' },
  { name: 'map',        label: 'Mapa',       icon: '◈' },
  { name: 'settings',   label: 'Ajustes',    icon: '⚙' },
];

const showMore = ref(false);

const activeName = computed(() => {
  const n = route.name;
  if (n === 'media-detail')        return 'media';
  if (n?.startsWith?.('feed'))      return 'feed';
  if (n?.startsWith?.('portfolio')) return 'portfolios';
  if (n?.startsWith?.('slider'))    return 'sliders';
  return n;
});

const overflowNames = computed(() => overflow.map(i => i.name));
const overflowActive = computed(() => overflowNames.value.includes(activeName.value));

function go(name) {
  showMore.value = false;
  router.push({ name });
}
</script>

<template>
  <nav class="bottom-nav safe-bottom" aria-label="Navegación principal"
       :style="{ '--bn-cols': primary.length + 1 }">
    <!-- Principales (siempre) -->
    <button
      v-for="it in primary" :key="it.name"
      class="bn-btn" :class="{ active: activeName === it.name }"
      :aria-current="activeName === it.name ? 'page' : undefined"
      :aria-label="it.label"
      @click="go(it.name)">
      <span class="bn-icon" aria-hidden="true">{{ it.icon }}</span>
      <span class="bn-label">{{ it.label }}</span>
    </button>

    <!-- Botón "Más" (solo móvil) -->
    <button
      class="bn-btn mobile-only" :class="{ active: overflowActive }"
      aria-haspopup="true" :aria-expanded="showMore"
      aria-label="Más secciones"
      @click="showMore = true">
      <span class="bn-icon" aria-hidden="true">⋯</span>
      <span class="bn-label">Más</span>
    </button>

    <!-- Secundarios (solo escritorio: planos en el sidebar) -->
    <button
      v-for="it in overflow" :key="'d-' + it.name"
      class="bn-btn desktop-only" :class="{ active: activeName === it.name }"
      :aria-current="activeName === it.name ? 'page' : undefined"
      :aria-label="it.label"
      @click="go(it.name)">
      <span class="bn-icon" aria-hidden="true">{{ it.icon }}</span>
      <span class="bn-label">{{ it.label }}</span>
    </button>
  </nav>

  <!-- Hoja desplegable "Más" (móvil) -->
  <transition name="sheet">
    <div v-if="showMore" class="sheet-overlay mobile-only" @click.self="showMore = false">
      <div class="sheet" role="dialog" aria-modal="true" aria-label="Más secciones">
        <div class="sheet-handle" />
        <div class="more-grid">
          <button
            v-for="it in overflow" :key="'m-' + it.name"
            class="more-btn" :class="{ active: activeName === it.name }"
            @click="go(it.name)">
            <span class="more-icon" aria-hidden="true">{{ it.icon }}</span>
            <span class="more-label">{{ it.label }}</span>
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<style scoped>
.bottom-nav {
  position: fixed;
  bottom: 0; left: 0; right: 0;
  display: grid;
  grid-template-columns: repeat(var(--bn-cols, 5), 1fr);
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

/* En móvil los ítems de escritorio no ocupan celda (no son grid items). */
.desktop-only { display: none; }

/* Hoja "Más" */
.sheet-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.5);
  z-index: 1350;
  display: flex; align-items: flex-end;
}
.sheet {
  width: 100%;
  background: var(--s1);
  border-top-left-radius: 18px;
  border-top-right-radius: 18px;
  padding: 14px 16px calc(24px + env(safe-area-inset-bottom));
}
.sheet-handle { width: 40px; height: 4px; background: var(--border2); border-radius: 2px; margin: -4px auto 16px; }
.more-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
}
.more-btn {
  display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;
  padding: 18px 8px;
  border-radius: var(--radius);
  background: var(--s2);
  border: 1px solid var(--border);
  color: var(--text);
}
.more-btn.active { background: var(--accent-lo); color: var(--accent); border-color: var(--accent); }
.more-icon { font-size: 24px; line-height: 1; }
.more-label { font-size: 12px; }

.sheet-enter-active, .sheet-leave-active { transition: opacity .25s; }
.sheet-enter-active .sheet, .sheet-leave-active .sheet { transition: transform .25s; }
.sheet-enter-from, .sheet-leave-to { opacity: 0; }
.sheet-enter-from .sheet, .sheet-leave-to .sheet { transform: translateY(100%); }

/* Escritorio ≥1024px: barra lateral, todo plano (sin "Más"). */
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

  .desktop-only { display: flex; }   /* mostrar secundarios en el sidebar */
  .mobile-only  { display: none; }   /* ocultar "Más" y su hoja */
}
</style>
