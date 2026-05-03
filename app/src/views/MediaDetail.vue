<script setup>
import { ref, onMounted, onBeforeUnmount, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { MediaAPI } from '../api/endpoints';
import { useFoldersStore } from '../stores/folders';
import { useMediaStore } from '../stores/media';
import { useUiStore } from '../stores/ui';
import Spinner from '../components/Spinner.vue';
import FolderPicker from '../components/FolderPicker.vue';
import GeoTagger from '../components/GeoTagger.vue';
import L from 'leaflet';
import { useKeyboardShortcuts } from '../composables/useKeyboardShortcuts';

const props = defineProps({ id: { type: [String, Number], required: true } });
const router  = useRouter();
const folders = useFoldersStore();
const media   = useMediaStore();
const ui      = useUiStore();

const item       = ref(null);
const loading    = ref(true);
const saving     = ref(false);
const generating = ref(false);
const tab        = ref('meta');
const folderId   = ref(0);
const showFolderPicker = ref(false);
const showGeoTagger = ref(false);
const miniMapEl = ref(null);
let miniMap = null;
let miniMarker = null;

const form = ref({ title: '', alt: '', seo_title: '', caption: '', description: '' });

onMounted(async () => {
  await folders.load();
  try {
    item.value = await MediaAPI.detail(props.id);
    form.value = {
      title: item.value.title || '',
      alt:   item.value.alt   || '',
      seo_title: item.value.seo_title || '',
      caption: item.value.caption || '',
      description: item.value.description || '',
    };
    folderId.value = item.value.folder_ids?.[0] || 0;
  } catch (e) {
    ui.toast(e.message, 'err');
    router.back();
  } finally {
    loading.value = false;
  }
});

async function save() {
  saving.value = true;
  try {
    const updated = await MediaAPI.update(props.id, form.value);
    if (folderId.value !== (item.value.folder_ids?.[0] || 0)) {
      await MediaAPI.setFolder(props.id, folderId.value);
    }
    item.value = updated;
    ui.toast('✓ Guardado', 'ok');
  } catch (e) {
    ui.toast(e.message, 'err');
  } finally {
    saving.value = false;
  }
}

async function generateAI() {
  generating.value = true;
  try {
    const r = await MediaAPI.generateAI(props.id);
    form.value.alt     = r.alt;
    form.value.caption = r.caption;
    ui.toast('✓ Generado con IA', 'ok');
  } catch (e) {
    ui.toast(e.message, 'err');
  } finally {
    generating.value = false;
  }
}

async function copyUrl() {
  try {
    await navigator.clipboard.writeText(item.value.url);
    ui.toast('📋 URL copiada', 'ok');
  } catch { ui.toast('No se pudo copiar', 'err'); }
}

async function remove() {
  if (!confirm('¿Eliminar esta imagen? No se puede deshacer.')) return;
  try {
    await MediaAPI.remove(props.id);
    ui.toast('🗑 Eliminada', 'ok');
    router.replace({ name: 'media' });
  } catch (e) {
    ui.toast(e.message, 'err');
  }
}

async function onGeoPick(payload) {
  try {
    const updated = await MediaAPI.setGeo(props.id, payload);
    item.value = updated;
    ui.toast('📍 Ubicación guardada', 'ok');
    renderMiniMap();
  } catch (e) {
    ui.toast(e.message, 'err');
  }
}

async function onGeoClear() {
  try {
    const updated = await MediaAPI.setGeo(props.id, { lat: null, lng: null });
    item.value = updated;
    ui.toast('Ubicación quitada', 'ok');
    if (miniMarker) { miniMarker.remove(); miniMarker = null; }
    if (miniMap)    { miniMap.remove();    miniMap = null; }
  } catch (e) {
    ui.toast(e.message, 'err');
  }
}

function renderMiniMap() {
  if (tab.value !== 'geo' || !item.value?.geo) return;
  // Esperar al render del DOM
  setTimeout(() => {
    if (!miniMapEl.value) return;
    if (miniMap) { miniMap.remove(); miniMap = null; }
    miniMap = L.map(miniMapEl.value, {
      center: [item.value.geo.lat, item.value.geo.lng],
      zoom: 12,
      zoomControl: true,
      dragging: true,
      scrollWheelZoom: false,
    });
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
      attribution: '© OSM © CARTO',
      subdomains: 'abcd',
      maxZoom: 19,
    }).addTo(miniMap);
    miniMarker = L.marker([item.value.geo.lat, item.value.geo.lng], {
      icon: L.divIcon({
        className: '',
        html: '<div style="width:14px;height:14px;border-radius:50%;background:#c8a97e;border:2px solid #0f0f0f;box-shadow:0 0 0 2px #c8a97e"></div>',
        iconSize: [14, 14], iconAnchor: [7, 7],
      }),
    }).addTo(miniMap);
    setTimeout(() => miniMap.invalidateSize(), 100);
  }, 50);
}

watch(tab, (v) => { if (v === 'geo') renderMiniMap(); });
watch(() => item.value?.geo, () => { if (tab.value === 'geo') renderMiniMap(); });

onBeforeUnmount(() => {
  if (miniMap) { miniMap.remove(); miniMap = null; }
});

const exifEntries = computed(() => Object.entries(item.value?.exif || {}));

const folderName = computed(() => {
  if (!folderId.value) return '— Sin carpeta —';
  return folders.byId[folderId.value]?.name || '?';
});

// ── Navegación entre fotos ──────────────────────────────────────
// Usamos los items que la pantalla de Media tenga cargados.
// Si está vacío (entró directo por URL), los botones quedan deshabilitados.
const currentIndex = computed(() => {
  if (!media.items.length) return -1;
  return media.items.findIndex(i => i.id == props.id);
});
const prevId = computed(() => {
  const i = currentIndex.value;
  return i > 0 ? media.items[i - 1].id : null;
});
const nextId = computed(() => {
  const i = currentIndex.value;
  return i >= 0 && i < media.items.length - 1 ? media.items[i + 1].id : null;
});

function goPrev() {
  if (prevId.value) router.replace({ name: 'media-detail', params: { id: prevId.value } });
}
function goNext() {
  if (nextId.value) router.replace({ name: 'media-detail', params: { id: nextId.value } });
}

// Atajos de teclado
useKeyboardShortcuts({
  'arrowleft':  goPrev,
  'arrowright': goNext,
  'escape':     () => router.back(),
});

// Swipe táctil
let touchStartX = null;
let touchStartY = null;
function onTouchStart(e) {
  touchStartX = e.touches[0].clientX;
  touchStartY = e.touches[0].clientY;
}
function onTouchEnd(e) {
  if (touchStartX === null) return;
  const dx = e.changedTouches[0].clientX - touchStartX;
  const dy = e.changedTouches[0].clientY - touchStartY;
  // Sólo swipe horizontal claro (no diagonal, no vertical)
  if (Math.abs(dx) > 60 && Math.abs(dy) < 40) {
    if (dx > 0) goPrev();
    else        goNext();
  }
  touchStartX = null;
}

// Recargar el detalle cuando cambia el id (al navegar entre fotos)
watch(() => props.id, async (newId) => {
  if (!newId) return;
  loading.value = true;
  try {
    item.value = await MediaAPI.detail(newId);
    form.value = {
      title: item.value.title || '',
      alt:   item.value.alt   || '',
      seo_title: item.value.seo_title || '',
      caption: item.value.caption || '',
      description: item.value.description || '',
    };
    folderId.value = item.value.folder_ids?.[0] || 0;
    if (tab.value === 'geo') renderMiniMap();
  } catch (e) {
    ui.toast(e.message, 'err');
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div v-if="loading" class="center muted"><Spinner /> Cargando…</div>

  <div v-else-if="item">
    <div class="hero" @touchstart.passive="onTouchStart" @touchend="onTouchEnd">
      <img v-if="(item.mime || '').startsWith('image/')" :src="item.medium || item.url" :alt="item.title" />
      <div v-else class="hero-icon">📎</div>

      <!-- Botones flotantes prev/next -->
      <button v-if="prevId" class="nav-btn nav-prev" @click="goPrev" title="Anterior (←)">‹</button>
      <button v-if="nextId" class="nav-btn nav-next" @click="goNext" title="Siguiente (→)">›</button>
      <span v-if="currentIndex >= 0" class="nav-counter">
        {{ currentIndex + 1 }} / {{ media.items.length }}
      </span>
    </div>

    <div class="hero-actions">
      <button class="btn ghost" @click="copyUrl">📋 Copiar URL</button>
      <a class="btn ghost" :href="item.url" target="_blank">↗ Abrir</a>
    </div>

    <div class="tabs">
      <button class="tab" :class="{ on: tab === 'meta' }"  @click="tab = 'meta'">Datos</button>
      <button class="tab" :class="{ on: tab === 'geo' }"   @click="tab = 'geo'">📍 Ubic.</button>
      <button class="tab" :class="{ on: tab === 'exif' }"  @click="tab = 'exif'">EXIF</button>
      <button class="tab" :class="{ on: tab === 'tools' }" @click="tab = 'tools'">⚒</button>
    </div>

    <!-- Tab Datos -->
    <div v-if="tab === 'meta'" class="card">
      <div class="field">
        <label>Título</label>
        <input v-model="form.title" />
      </div>
      <div class="field">
        <label>Alt text (SEO)</label>
        <input v-model="form.alt" placeholder="Describe la imagen" />
      </div>
      <div class="field">
        <label>SEO Title</label>
        <input v-model="form.seo_title" />
      </div>
      <div class="field">
        <label>Pie de foto</label>
        <textarea v-model="form.caption" rows="2"></textarea>
      </div>
      <div class="field">
        <label>Descripción</label>
        <textarea v-model="form.description" rows="3"></textarea>
      </div>
      <div class="field">
        <label>Carpeta</label>
        <button class="folder-picker-btn" @click="showFolderPicker = true">
          <span>📁 {{ folderName }}</span>
          <span class="muted">›</span>
        </button>
      </div>

      <div v-if="item.tags?.length" class="field">
        <label>Etiquetas IA</label>
        <div class="tags-list">
          <button v-for="t in item.tags" :key="t"
            class="tag-pill"
            @click="$router.push({ name: 'media', query: { tag: t } })"
            :title="'Filtrar por: ' + t">
            🏷 {{ t }}
          </button>
        </div>
      </div>

      <div class="row" style="margin-top:14px">
        <button class="btn" :disabled="generating" @click="generateAI">
          <Spinner v-if="generating" :size="14" />
          <span v-else>✨ Generar alt+caption</span>
        </button>
        <span class="spacer" />
        <button class="btn pri" :disabled="saving" @click="save">
          <Spinner v-if="saving" :size="14" />
          <span v-else>Guardar</span>
        </button>
      </div>
    </div>

    <!-- Tab Ubicación -->
    <div v-else-if="tab === 'geo'" class="card">
      <div v-if="item.geo">
        <div ref="miniMapEl" class="mini-map"></div>

        <div class="geo-info">
          <div v-if="item.geo.place" class="geo-place">📍 {{ item.geo.place }}</div>
          <div class="muted small">
            {{ item.geo.lat.toFixed(5) }}, {{ item.geo.lng.toFixed(5) }}
            <span v-if="item.geo.source === 'exif'" class="src-badge">EXIF</span>
            <span v-else class="src-badge manual">Manual</span>
          </div>
        </div>

        <div class="row" style="margin-top:12px">
          <button class="btn" @click="showGeoTagger = true" style="flex:1">📍 Cambiar</button>
          <button class="btn danger" @click="onGeoClear">🗑 Quitar</button>
        </div>
      </div>

      <div v-else class="geo-empty">
        <span class="big-icon">📍</span>
        <p class="muted small">Esta imagen no tiene ubicación asignada.</p>
        <button class="btn pri" @click="showGeoTagger = true" style="margin-top:10px">
          📍 Asignar ubicación
        </button>
      </div>
    </div>

    <!-- Tab EXIF -->
    <div v-else-if="tab === 'exif'" class="card">
      <div v-if="exifEntries.length" class="exif-grid">
        <div v-for="[k, v] in exifEntries" :key="k" class="exif-card">
          <span class="exif-k">{{ k }}</span>
          <span class="exif-v">{{ v }}</span>
        </div>
      </div>
      <p v-else class="muted small">Sin datos EXIF</p>

      <hr style="margin:14px 0; border:0; border-top:1px solid var(--border)">

      <div class="kv-row"><span class="muted">Dimensiones</span><span>{{ item.width }}×{{ item.height }}</span></div>
      <div class="kv-row"><span class="muted">Tamaño</span><span>{{ item.filesize_h }}</span></div>
      <div class="kv-row"><span class="muted">Tipo</span><span>{{ item.mime }}</span></div>
      <div class="kv-row"><span class="muted">Fecha</span><span>{{ item.date }}</span></div>
      <div class="kv-row"><span class="muted">Archivo</span><span class="filename">{{ item.filename }}</span></div>
    </div>

    <!-- Tab Herramientas -->
    <div v-else class="card">
      <button class="btn" style="width:100%;margin-bottom:10px" @click="copyUrl">📋 Copiar URL pública</button>
      <a class="btn" style="width:100%;margin-bottom:10px" :href="item.edit_url" target="_blank">⚙️ Editor de WordPress ↗</a>
      <button class="btn danger" style="width:100%" @click="remove">🗑 Eliminar imagen</button>
    </div>
  </div>

  <FolderPicker v-model="showFolderPicker"
    :selected="folderId"
    :show-all="false"
    title="Cambiar carpeta"
    @pick="(id) => { folderId = id < 0 ? 0 : id; }" />

  <GeoTagger v-model="showGeoTagger"
    :initial-lat="item?.geo?.lat ?? null"
    :initial-lng="item?.geo?.lng ?? null"
    :initial-place="item?.geo?.place || ''"
    title="Ubicación de la imagen"
    @pick="onGeoPick"
    @clear="onGeoClear" />
</template>

<style scoped>
.center { display: flex; gap: 10px; justify-content: center; padding: 40px; }

.hero {
  position: relative;
  background: var(--s2);
  border-radius: var(--radius-lg);
  overflow: hidden;
  margin-bottom: 12px;
  display: flex; align-items: center; justify-content: center;
  aspect-ratio: 4/3;
  touch-action: pan-y;  /* permitir scroll vertical, capturamos solo swipe horizontal */
}
.hero img { width: 100%; height: 100%; object-fit: contain; display: block; }
.hero-icon { font-size: 60px; }

.nav-btn {
  position: absolute;
  top: 50%; transform: translateY(-50%);
  width: 44px; height: 44px;
  background: rgba(0,0,0,.55);
  color: white;
  border-radius: 50%;
  font-size: 28px;
  line-height: 1;
  display: flex; align-items: center; justify-content: center;
  transition: background .15s, opacity .15s;
  opacity: .8;
  z-index: 2;
}
.nav-btn:hover { opacity: 1; background: rgba(0,0,0,.8); }
.nav-prev { left: 8px; padding-right: 4px; }
.nav-next { right: 8px; padding-left: 4px; }

.nav-counter {
  position: absolute;
  bottom: 8px; left: 50%; transform: translateX(-50%);
  background: rgba(0,0,0,.55);
  color: white;
  padding: 3px 10px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 500;
}

.hero-actions { display: flex; gap: 8px; margin-bottom: 16px; }
.hero-actions .btn { flex: 1; }

.tabs {
  display: flex;
  gap: 4px;
  margin-bottom: 12px;
  background: var(--s2);
  border-radius: var(--radius);
  padding: 3px;
}
.tab {
  flex: 1;
  padding: 8px;
  border-radius: calc(var(--radius) - 2px);
  font-size: 13px;
  color: var(--text-mute);
  font-weight: 500;
}
.tab.on { background: var(--s1); color: var(--text); box-shadow: 0 1px 2px rgba(0,0,0,.2); }

.exif-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 6px;
}
.exif-card {
  background: var(--s2);
  border-radius: var(--radius);
  padding: 8px 10px;
  display: flex; flex-direction: column;
}
.exif-k { font-size: 10px; color: var(--text-mute); text-transform: uppercase; letter-spacing: .5px; }
.exif-v { font-size: 13px; font-weight: 500; }

.kv-row {
  display: flex;
  justify-content: space-between;
  padding: 6px 0;
  font-size: 13px;
  border-bottom: 1px solid var(--border);
}
.kv-row:last-child { border-bottom: 0; }
.filename { font-family: monospace; font-size: 11px; word-break: break-all; max-width: 60%; text-align: right; }
.small { font-size: 12px; }

.folder-picker-btn {
  display: flex; align-items: center; justify-content: space-between;
  width: 100%;
  background: var(--s1);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 10px 12px;
  font-size: 14px;
  color: var(--text);
  min-height: var(--tap);
}
.folder-picker-btn:active { background: var(--s2); }

.tags-list { display: flex; flex-wrap: wrap; gap: 4px; }
.tag-pill {
  padding: 4px 10px;
  background: var(--accent-lo);
  color: var(--accent);
  border-radius: 12px;
  font-size: 11px;
  font-weight: 500;
}
.tag-pill:active { background: var(--accent); color: #0f0f0f; }

.mini-map {
  height: 200px;
  border-radius: var(--radius);
  overflow: hidden;
  background: var(--s2);
  margin-bottom: 10px;
}
.geo-info { display: flex; flex-direction: column; gap: 4px; }
.geo-place { font-size: 14px; font-weight: 500; }
.src-badge {
  display: inline-block;
  margin-left: 6px;
  padding: 1px 7px;
  background: var(--s2);
  border-radius: 10px;
  font-size: 10px;
  color: var(--text-mute);
  text-transform: uppercase;
}
.src-badge.manual { background: var(--accent-lo); color: var(--accent); }

.geo-empty {
  text-align: center;
  padding: 24px 12px;
}
.big-icon { font-size: 48px; display: block; margin-bottom: 8px; opacity: .6; }
</style>
