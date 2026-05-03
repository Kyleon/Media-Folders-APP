<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useFoldersStore } from '../stores/folders';
import { MediaAPI } from '../api/endpoints';
import FolderPicker from './FolderPicker.vue';
import Spinner from './Spinner.vue';

/**
 * Picker de imágenes en bottomsheet con browser de carpetas.
 * Soporta selección simple o múltiple y excluir IDs (ya añadidos).
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

const folderName = computed(() => {
  if (folder.value === -1) return 'Todos los medios';
  if (folder.value === 0)  return 'Sin carpeta';
  return folders.byId[folder.value]?.name || '?';
});

let debounce;
function onSearch() {
  clearTimeout(debounce);
  debounce = setTimeout(load, 300);
}

watch(folder, load);
watch(() => props.modelValue, (v) => {
  if (v) {
    selected.value = [];
    page.value = 1;
    load();
  }
});

async function load() {
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
    items.value = res.images.filter(i => !props.exclude.includes(i.id));
    total.value = res.total;
    pages.value = res.pages;
  } finally {
    loading.value = false;
  }
}

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

function loadMore() {
  if (page.value >= pages.value || loading.value) return;
  page.value++;
  load();
}
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
          <button v-for="img in items" :key="img.id"
            class="item"
            :class="{ 'is-sel': isSelected(img.id) }"
            @click="toggle(img)">
            <div class="thumb">
              <img v-if="img.thumb" :src="img.thumb" :alt="img.title" loading="lazy" />
              <div v-if="multiple" class="check" :class="{ on: isSelected(img.id) }">
                <span v-if="isSelected(img.id)">✓</span>
              </div>
            </div>
            <span class="name">{{ img.title || img.filename }}</span>
          </button>
        </div>

        <button v-if="page < pages" class="more-btn" @click="loadMore" :disabled="loading">
          <Spinner v-if="loading" :size="14" /><span v-else>Cargar más ({{ items.length }} / {{ total }})</span>
        </button>

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
    @pick="(id) => { folder = id; page = 1; }" />
</template>

<style scoped>
.sheet-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.5);
  z-index: 150;
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
  flex: 1;
  overflow-y: auto;
}
.item {
  display: flex; flex-direction: column;
  background: var(--s2); border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  text-align: left;
  position: relative;
  transition: border-color .15s;
}
.item:active { transform: scale(.97); }
.item.is-sel { border-color: var(--accent); }
.thumb { aspect-ratio: 1; background: var(--s3); position: relative; }
.thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.check {
  position: absolute; top: 4px; right: 4px;
  width: 22px; height: 22px;
  border-radius: 50%;
  background: rgba(0,0,0,.5);
  border: 2px solid white;
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; color: white;
}
.check.on {
  background: var(--accent);
  border-color: var(--accent);
  color: #0f0f0f;
}
.name {
  padding: 4px 6px;
  font-size: 10px;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.more-btn {
  width: 100%; margin-top: 10px;
  padding: 10px; background: var(--s2);
  border-radius: var(--radius);
  font-size: 13px; color: var(--text-mute);
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
