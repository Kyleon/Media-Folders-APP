<script setup>
import { ref, computed, onMounted, watch, onBeforeUnmount, nextTick } from 'vue';
import { useFoldersStore } from '../stores/folders';
import { MediaAPI } from '../api/endpoints';
import FolderPicker from './FolderPicker.vue';
import Spinner from './Spinner.vue';

/**
 * Picker de imágenes en bottomsheet con browser de carpetas.
 * Soporta selección simple o múltiple y excluir IDs (ya añadidos).
 * Carga con scroll infinito (IntersectionObserver sobre un sentinel
 * al final del grid).
 */
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  multiple:   { type: Boolean, default: false },
  exclude:    { type: Array,   default: () => [] }, // IDs a no mostrar
  title:      { type: String,  default: 'Elegir imagen' },
});
const emit = defineEmits(['update:modelValue', 'pick']);

const folders = useFoldersStore();

const folder    = ref(-1);     // -1 todas, 0 sin carpeta, >0 id
const showFP    = ref(false);
const items     = ref([]);
const total     = ref(0);
const page      = ref(1);
const pages     = ref(1);
const loading   = ref(false);
const search    = ref('');
const selected  = ref([]);

const sentinel  = ref(null);
let observer    = null;

const folderName = computed(() => {
  if (folder.value === -1) return 'Todos los medios';
  if (folder.value === 0)  return 'Sin carpeta';
  return folders.byId[folder.value]?.name || '?';
});

let debounce;
function onSearch() {
  clearTimeout(debounce);
  debounce = setTimeout(() => { resetAndLoad(); }, 300);
}

watch(folder, () => { resetAndLoad(); });
watch(() => props.modelValue, async (v) => {
  if (v) {
    selected.value = [];
    resetAndLoad();
    await nextTick();
    setupObserver();
  } else {
    teardownObserver();
  }
});

function resetAndLoad() {
  page.value = 1;
  items.value = [];
  load();
}

async function load() {
  if (loading.value) return;
  loading.value = true;
  try {
    const res = await MediaAPI.list({
      page: page.value,
      per_page: 40,
      folder: folder.value,
      search: search.value,
      orderby: 'date',
      order: 'DESC',
      mime: 'image',
    });
    const fresh = res.images.filter(i => !props.exclude.includes(i.id));
    if (page.value === 1) {
      items.value = fresh;
    } else {
      // Concatenar evitando duplicados por id (defensa por carreras)
      const existing = new Set(items.value.map(i => i.id));
      items.value = items.value.concat(fresh.filter(i => !existing.has(i.id)));
    }
    total.value = res.total;
    pages.value = res.pages;
  } finally {
    loading.value = false;
  }
}

function loadMore() {
  if (loading.value) return;
  if (page.value >= pages.value) return;
  page.value++;
  load();
}

/* ─────── IntersectionObserver para scroll infinito ─────── */
function setupObserver() {
  teardownObserver();
  if (!sentinel.value) return;
  observer = new IntersectionObserver((entries) => {
    if (entries.some(e => e.isIntersecting)) loadMore();
  }, {
    // El observer se asocia al viewport; el sheet tiene su propio scroll
    // pero el viewport del browser cubre el sheet completo, así que
    // visible = a punto de aparecer al hacer scroll dentro del sheet.
    rootMargin: '300px 0px',
    threshold: 0,
  });
  observer.observe(sentinel.value);
}

function teardownObserver() {
  if (observer) {
    observer.disconnect();
    observer = null;
  }
}

onBeforeUnmount(() => teardownObserver());

function toggle(img) {
  if (props.multiple) {
    const idx = selected.value.findIndex(i => i.id === img.id);
    if (idx === -1) selected.value.push(img);
    else            selected.value.splice(idx, 1);
  } else {
    emit('pick', img);
    emit('update:modelValue', false);
  }
}

function isSelected(id) { return selected.value.some(i => i.id === id); }

function confirmMulti() {
  emit('pick', [...selected.value]);
  emit('update:modelValue', false);
}

function close() { emit('update:modelValue', false); }
</script>

<template>
  <transition name="sheet">
    <div v-if="modelValue" class="sheet-overlay" @click.self="close">
      <div class="sheet">
        <div class="sheet-handle" />
        <div class="sheet-head">
          <h3>{{ title }}</h3>
          <button class="close-btn" @click="close">✕</button>
        </div>

        <div class="filters">
          <button class="folder-btn" @click="showFP = true">
            📁 {{ folderName }}
            <span class="muted small">{{ total }}</span>
          </button>
          <input v-model="search" @input="onSearch" placeholder="Buscar…" class="search" />
        </div>

        <div v-if="loading && !items.length" class="center muted"><Spinner /> Cargando…</div>
        <div v-else-if="!items.length" class="empty muted">📭 Sin imágenes</div>

        <div v-else class="grid">
          <div v-for="img in items" :key="img.id"
            role="button"
            tabindex="0"
            class="item"
            :class="{ 'is-sel': isSelected(img.id) }"
            @click="toggle(img)"
            @keydown.enter.prevent="toggle(img)"
            @keydown.space.prevent="toggle(img)">
            <img v-if="img.thumb" :src="img.thumb" :alt="img.title" loading="lazy" class="thumb-img" />
            <div v-if="multiple" class="check" :class="{ on: isSelected(img.id) }">
              <span v-if="isSelected(img.id)">✓</span>
            </div>
            <span class="name">{{ img.title || img.filename }}</span>
          </div>
        </div>

        <!-- Sentinel para scroll infinito -->
        <div ref="sentinel" class="sentinel" aria-hidden="true">
          <Spinner v-if="loading && items.length" :size="14" />
          <span v-else-if="page < pages" class="muted small">Cargando más… ({{ items.length }} / {{ total }})</span>
          <span v-else-if="items.length" class="muted small">{{ items.length }} / {{ total }}</span>
        </div>

        <div v-if="multiple" class="footer">
          <span class="muted small">{{ selected.length }} seleccionadas</span>
          <button class="btn pri" :disabled="!selected.length" @click="confirmMulti">
            Añadir {{ selected.length || '' }}
          </button>
        </div>
      </div>
    </div>
  </transition>

  <FolderPicker v-model="showFP"
    :selected="folder"
    title="Filtrar por carpeta"
    @pick="(id) => { folder = id; }" />
</template>

<style scoped>
.sheet-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.5);
  z-index: 1350;
  display: flex; align-items: flex-end;
}
.sheet {
  width: 100%;
  max-height: 92vh;
  background: var(--s1);
  border-top-left-radius: 18px;
  border-top-right-radius: 18px;
  padding: 14px 16px calc(20px + env(safe-area-inset-bottom));
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}
.sheet-handle { width: 40px; height: 4px; background: var(--border2); border-radius: 2px; margin: -4px auto 12px; }
.sheet-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.sheet-head h3 { margin: 0; font-size: 14px; text-transform: uppercase; color: var(--text-mute); letter-spacing: .5px; }
.close-btn { font-size: 22px; color: var(--text-mute); padding: 4px 8px; }

.filters { display: flex; flex-direction: column; gap: 8px; margin-bottom: 12px; }
.folder-btn {
  display: flex; align-items: center; justify-content: space-between;
  background: var(--s2); border: 1px solid var(--border);
  border-radius: var(--radius); padding: 10px 12px;
  font-size: 13px; color: var(--text);
}
.search { background: var(--s2); }
.small { font-size: 11px; }

.center { display: flex; gap: 10px; justify-content: center; padding: 30px; }
.empty { text-align: center; padding: 40px 16px; }

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(96px, 1fr));
  gap: 4px;
  align-content: start;
}

/* CARDS CUADRADAS — fix definitivo:
   - aspect-ratio en el .item (el grid cell completo) garantiza
     que cada celda es 1:1 sin importar lo que tenga dentro.
   - El .name es overlay absoluto sobre la imagen (no añade altura).
   - La imagen llena el .item al 100%. */
.item {
  position: relative;
  width: 100%;
  aspect-ratio: 1 / 1;
  background: var(--s2); border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  cursor: pointer;
  user-select: none;
  transition: border-color .15s, transform .12s;
}
/* Fallback para navegadores sin aspect-ratio: padding-bottom hack
   sobre un pseudo, con la imagen absoluta encima */
@supports not (aspect-ratio: 1 / 1) {
  .item::before {
    content: "";
    display: block;
    padding-bottom: 100%;
  }
}
.item:active { transform: scale(.97); }
.item.is-sel { border-color: var(--accent); }
.item:focus-visible {
  outline: 2px solid var(--accent);
  outline-offset: 2px;
}

.thumb-img {
  position: absolute;
  inset: 0;
  width: 100%; height: 100%;
  object-fit: cover;
  display: block;
}

.check {
  position: absolute; top: 4px; right: 4px;
  width: 22px; height: 22px;
  border-radius: 50%;
  background: rgba(0,0,0,.5);
  border: 2px solid white;
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; color: white;
  z-index: 2;
}
.check.on {
  background: var(--accent);
  border-color: var(--accent);
  color: #0f0f0f;
}

.name {
  position: absolute;
  left: 0; right: 0; bottom: 0;
  padding: 14px 6px 4px;
  font-size: 10px;
  color: white;
  background: linear-gradient(to top, rgba(0,0,0,.7), rgba(0,0,0,0));
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  z-index: 1;
}

.sentinel {
  display: flex; justify-content: center; align-items: center;
  gap: 8px;
  padding: 14px 8px;
  min-height: 36px;
}

.footer {
  display: flex; align-items: center; justify-content: space-between;
  margin-top: 12px;
  padding-top: 10px;
  border-top: 1px solid var(--border);
  gap: 10px;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 38px;
  padding: 0 16px;
  border-radius: var(--radius);
  font-size: 14px;
  font-weight: 500;
  background: var(--s2);
  color: var(--text);
  border: 1px solid var(--border);
}
.btn.pri { background: var(--accent); color: #0f0f0f; border-color: var(--accent); }
.btn:disabled { opacity: .4; }

.sheet-enter-active, .sheet-leave-active { transition: opacity .25s; }
.sheet-enter-active .sheet, .sheet-leave-active .sheet { transition: transform .25s; }
.sheet-enter-from, .sheet-leave-to { opacity: 0; }
.sheet-enter-from .sheet, .sheet-leave-to .sheet { transform: translateY(100%); }
</style>
