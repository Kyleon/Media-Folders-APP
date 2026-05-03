<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { MediaAPI, PortfoliosAPI } from '../api/endpoints';
import { useFoldersStore } from '../stores/folders';

const props = defineProps({ modelValue: { type: Boolean, default: false } });
const emit  = defineEmits(['update:modelValue']);

const router  = useRouter();
const folders = useFoldersStore();

const query    = ref('');
const inputEl  = ref(null);
const loading  = ref(false);
const results  = ref({ media: [], portfolios: [], folders: [] });
const selected = ref(0);

let debounce;

watch(() => props.modelValue, async (v) => {
  if (v) {
    query.value = '';
    results.value = { media: [], portfolios: [], folders: [] };
    selected.value = 0;
    await nextTick();
    inputEl.value?.focus();
    if (!folders.flat.length) folders.load();
  }
});

watch(query, () => {
  clearTimeout(debounce);
  if (!query.value.trim()) {
    results.value = { media: [], portfolios: [], folders: [] };
    return;
  }
  debounce = setTimeout(search, 250);
});

async function search() {
  loading.value = true;
  const q = query.value.trim();
  try {
    // Carpetas: filtro local, instantáneo
    const folderResults = folders.flat
      .filter(f => f.name.toLowerCase().includes(q.toLowerCase()))
      .slice(0, 5)
      .map(f => ({ type: 'folder', id: f.id, title: f.name, subtitle: `${f.count} imágenes`, icon: '📁' }));

    // Media + Portfolios: en paralelo
    const [m, p] = await Promise.all([
      MediaAPI.list({ search: q, per_page: 8, page: 1 }).catch(() => ({ images: [] })),
      PortfoliosAPI.list({ search: q, per_page: 5, page: 1 }).catch(() => ({ items: [] })),
    ]);

    results.value = {
      folders: folderResults,
      media: (m.images || []).map(i => ({
        type: 'media', id: i.id, title: i.title || i.filename,
        subtitle: i.filesize_h, icon: '🖼', thumb: i.thumb,
      })),
      portfolios: (p.items || []).map(po => ({
        type: 'portfolio', id: po.id, title: po.title,
        subtitle: po.status === 'publish' ? 'Publicado' : po.status,
        icon: '◇', thumb: po.hero_url,
      })),
    };
    selected.value = 0;
  } finally {
    loading.value = false;
  }
}

const flatResults = computed(() => [
  ...results.value.folders,
  ...results.value.media,
  ...results.value.portfolios,
]);

function close() { emit('update:modelValue', false); }

function go(item) {
  if (item.type === 'media')     router.push({ name: 'media-detail', params: { id: item.id } });
  if (item.type === 'portfolio') router.push({ name: 'portfolio-detail', params: { id: item.id } });
  if (item.type === 'folder')    router.push({ name: 'media', query: { folder: item.id } });
  close();
}

function onKey(e) {
  const list = flatResults.value;
  if (e.key === 'ArrowDown') {
    e.preventDefault();
    selected.value = Math.min(selected.value + 1, list.length - 1);
  } else if (e.key === 'ArrowUp') {
    e.preventDefault();
    selected.value = Math.max(selected.value - 1, 0);
  } else if (e.key === 'Enter') {
    e.preventDefault();
    if (list[selected.value]) go(list[selected.value]);
  } else if (e.key === 'Escape') {
    e.preventDefault();
    close();
  }
}
</script>

<template>
  <transition name="palette">
    <div v-if="modelValue" class="palette-overlay" @click.self="close">
      <div class="palette" @keydown="onKey">
        <div class="palette-input">
          <span class="search-icon">🔍</span>
          <input ref="inputEl" v-model="query" placeholder="Buscar medios, portfolios, carpetas…" />
          <kbd>esc</kbd>
        </div>

        <div class="palette-body">
          <div v-if="loading && !flatResults.length" class="muted center">Buscando…</div>
          <div v-else-if="!query.trim()" class="muted center">
            <p>Empieza a escribir para buscar.</p>
            <p class="hint small">Cmd/Ctrl+K · ↑↓ navegar · Enter abrir · Esc cerrar</p>
          </div>
          <div v-else-if="!flatResults.length" class="muted center">
            Sin resultados para "{{ query }}".
          </div>

          <template v-else>
            <div v-if="results.folders.length" class="group">
              <div class="group-label">Carpetas</div>
              <button v-for="(it, i) in results.folders" :key="'f' + it.id"
                class="result-row" :class="{ on: selected === i }"
                @click="go(it)" @mouseover="selected = i">
                <span class="r-icon">{{ it.icon }}</span>
                <span class="r-title">{{ it.title }}</span>
                <span class="r-sub muted small">{{ it.subtitle }}</span>
              </button>
            </div>

            <div v-if="results.media.length" class="group">
              <div class="group-label">Medios</div>
              <button v-for="(it, i) in results.media" :key="'m' + it.id"
                class="result-row" :class="{ on: selected === results.folders.length + i }"
                @click="go(it)" @mouseover="selected = results.folders.length + i">
                <img v-if="it.thumb" :src="it.thumb" class="r-thumb" :alt="it.title" />
                <span v-else class="r-icon">{{ it.icon }}</span>
                <span class="r-title">{{ it.title }}</span>
                <span class="r-sub muted small">{{ it.subtitle }}</span>
              </button>
            </div>

            <div v-if="results.portfolios.length" class="group">
              <div class="group-label">Portfolios</div>
              <button v-for="(it, i) in results.portfolios" :key="'p' + it.id"
                class="result-row" :class="{ on: selected === results.folders.length + results.media.length + i }"
                @click="go(it)" @mouseover="selected = results.folders.length + results.media.length + i">
                <img v-if="it.thumb" :src="it.thumb" class="r-thumb" :alt="it.title" />
                <span v-else class="r-icon">{{ it.icon }}</span>
                <span class="r-title">{{ it.title }}</span>
                <span class="r-sub muted small">{{ it.subtitle }}</span>
              </button>
            </div>
          </template>
        </div>
      </div>
    </div>
  </transition>
</template>

<style scoped>
.palette-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.55);
  z-index: 1500;  /* encima de TODO */
  display: flex; justify-content: center; align-items: flex-start;
  padding: 10vh 16px 16px;
  backdrop-filter: blur(4px);
}
.palette {
  width: 100%;
  max-width: 600px;
  background: var(--s1);
  border: 1px solid var(--border);
  border-radius: 14px;
  box-shadow: 0 20px 60px rgba(0,0,0,.5);
  overflow: hidden;
  display: flex; flex-direction: column;
  max-height: 70vh;
}

.palette-input {
  display: flex; align-items: center; gap: 8px;
  padding: 12px 14px;
  border-bottom: 1px solid var(--border);
}
.palette-input input {
  flex: 1;
  background: transparent;
  border: 0;
  font-size: 16px;
  color: var(--text);
  outline: none;
  padding: 0;
}
.search-icon { font-size: 16px; opacity: .6; }
kbd {
  font-family: inherit;
  font-size: 10px;
  padding: 2px 6px;
  background: var(--s3);
  border-radius: 3px;
  color: var(--text-mute);
}

.palette-body {
  flex: 1;
  overflow-y: auto;
  padding: 8px;
}
.center { text-align: center; padding: 30px 16px; }
.hint { margin-top: 8px; }

.group { margin-bottom: 10px; }
.group-label {
  font-size: 10px;
  text-transform: uppercase;
  color: var(--text-mute);
  font-weight: 600;
  letter-spacing: .5px;
  padding: 6px 8px 4px;
}

.result-row {
  display: flex; align-items: center; gap: 10px;
  width: 100%;
  text-align: left;
  padding: 8px;
  border-radius: var(--radius);
  font-size: 13px;
  color: var(--text);
}
.result-row.on { background: var(--accent-lo); color: var(--accent); }
.r-icon { width: 28px; text-align: center; font-size: 16px; flex: 0 0 28px; }
.r-thumb { width: 28px; height: 28px; object-fit: cover; border-radius: 4px; flex: 0 0 28px; }
.r-title { flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.r-sub { flex: 0 0 auto; }
.small { font-size: 11px; }

.palette-enter-active, .palette-leave-active { transition: opacity .15s; }
.palette-enter-active .palette, .palette-leave-active .palette { transition: transform .15s; }
.palette-enter-from, .palette-leave-to { opacity: 0; }
.palette-enter-from .palette, .palette-leave-to .palette { transform: translateY(-12px); }
</style>
