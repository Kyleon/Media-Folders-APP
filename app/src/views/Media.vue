<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useFoldersStore } from '../stores/folders';
import { useMediaStore } from '../stores/media';
import { useUiStore } from '../stores/ui';
import Spinner from '../components/Spinner.vue';
import Skeleton from '../components/Skeleton.vue';
import PullRefresh from '../components/PullRefresh.vue';
import GeoTagger from '../components/GeoTagger.vue';
import { StatsAPI } from '../api/endpoints';

const router  = useRouter();
const route   = useRoute();
const folders = useFoldersStore();
const media   = useMediaStore();
const ui      = useUiStore();

const showFolderPicker = ref(false);
const searchInput      = ref('');
const creatingFolder   = ref(false);
const newFolderName    = ref('');
const newFolderParent  = ref(0);
const folderActions    = ref(null);
const showMovePicker   = ref(false);   // sheet "Mover a carpeta" (selección múltiple)
const aiProgress       = ref(null);    // { done, errors, total } durante bulk AI
const movingFolder     = ref(null);    // { id, name } cuando estamos eligiendo destino para mover una carpeta
const dragFolderId     = ref(null);    // id de carpeta arrastrada (drag & drop nativo en desktop)
const showBulkGeo      = ref(false);   // sheet de geotag para seleccionados
const showTagPicker    = ref(false);
const showColorPicker  = ref(false);
const allTags          = ref([]);      // [{tag, count}]
const allColors        = ref([]);      // [{color, count}]
const viewMode         = ref(localStorage.getItem('ypva.media.view') || 'grid'); // 'grid' | 'list'
const activeQuickFilter = ref(null);  // id del filtro rápido activo

watch(viewMode, (v) => localStorage.setItem('ypva.media.view', v));

// Filtros rápidos predefinidos
const QUICK_FILTERS = [
  { id: 'today',     label: '🆕 Hoy',           apply: () => ({ orderby: 'date', order: 'DESC', search: '', mime: '' }) },
  { id: 'no-alt',    label: '⚠ Sin alt',        apply: () => ({ search: '__NO_ALT__', mime: 'image' }) },
  { id: 'no-folder', label: '📭 Sin carpeta',   apply: () => ({ folder: 0 }) },
  { id: 'images',    label: '🖼 Solo fotos',    apply: () => ({ mime: 'image' }) },
  { id: 'videos',    label: '🎬 Solo vídeos',   apply: () => ({ mime: 'video' }) },
  { id: 'biggest',   label: '📦 Más pesadas',   apply: () => ({ orderby: 'size', order: 'DESC', mime: '' }) },
];

function applyQuickFilter(qf) {
  if (activeQuickFilter.value === qf.id) {
    // Toggle: desactivar
    activeQuickFilter.value = null;
    media.setFilter({ folder: -1, search: '', mime: '', orderby: 'date', order: 'DESC' });
    media.load(true);
    return;
  }
  activeQuickFilter.value = qf.id;
  const patch = qf.apply();
  media.setFilter(patch);
  media.load(true);
}

// Long-press para entrar en modo selección
let pressTimer = null;
function startPress(id) {
  clearTimeout(pressTimer);
  pressTimer = setTimeout(() => {
    if (!media.selectMode) media.enterSelectMode(id);
  }, 450);
}
function cancelPress() { clearTimeout(pressTimer); }

function onItemTap(img, evt) {
  cancelPress();
  if (media.selectMode) {
    media.toggleSelect(img.id);
  } else {
    router.push({ name: 'media-detail', params: { id: img.id } });
  }
}

onMounted(async () => {
  // Sincronizar filtros desde la URL si vienen
  const f = route.query.folder;
  if (f !== undefined && f !== null && f !== '') {
    media.filter.folder = parseInt(f);
  }
  if (route.query.tag)   media.filter.tag   = String(route.query.tag);
  if (route.query.color) media.filter.color = String(route.query.color);
  await Promise.all([ folders.load(), media.load(true) ]);
});

let debounce;
function onSearch() {
  clearTimeout(debounce);
  debounce = setTimeout(() => {
    media.setFilter({ search: searchInput.value });
    media.load(true);
  }, 300);
}

function selectFolder(id) {
  media.setFilter({ folder: id });
  media.load(true);
  showFolderPicker.value = false;
  router.replace({ query: { ...route.query, folder: id } });
}

function startCreate(parentId = 0) {
  newFolderParent.value = parentId;
  newFolderName.value = '';
  creatingFolder.value = true;
  folderActions.value = null;
  // Foco al input tras render
  setTimeout(() => {
    document.getElementById('new-folder-input')?.focus();
  }, 50);
}

function cancelCreate() {
  creatingFolder.value = false;
  newFolderName.value = '';
}

async function confirmCreate() {
  const name = newFolderName.value.trim();
  if (!name) { cancelCreate(); return; }
  try {
    await folders.create(name, newFolderParent.value);
    ui.toast(`📁 "${name}" creada`, 'ok');
  } catch (e) {
    ui.toast(e.message || 'Error al crear', 'err');
  } finally {
    cancelCreate();
  }
}

async function renameFolder(folder) {
  const name = window.prompt('Nuevo nombre:', folder.name);
  if (!name || name.trim() === folder.name) { folderActions.value = null; return; }
  try {
    await folders.rename(folder.id, name.trim());
    ui.toast('✏️ Renombrada', 'ok');
  } catch (e) {
    ui.toast(e.message || 'Error al renombrar', 'err');
  } finally {
    folderActions.value = null;
  }
}

function startMoveFolder(folder) {
  movingFolder.value = folder;
  folderActions.value = null;
}

async function moveFolderTo(parentId) {
  if (!movingFolder.value) return;
  const f = movingFolder.value;
  if (parentId === f.parent) { movingFolder.value = null; return; }
  try {
    await folders.move(f.id, parentId);
    ui.toast(parentId === 0 ? `📁 "${f.name}" movida a la raíz` : `📁 "${f.name}" movida`, 'ok');
  } catch (e) {
    ui.toast(e.message || 'No se pudo mover', 'err');
  } finally {
    movingFolder.value = null;
  }
}

const moveTargets = computed(() => {
  if (!movingFolder.value) return [];
  const excluded = folders.descendantIds(movingFolder.value.id);
  return folders.flat.filter(f => !excluded.has(f.id));
});

// Drag & drop nativo (sólo desktop, los móviles usan "Mover a...")
function onFolderDragStart(e, folder) {
  dragFolderId.value = folder.id;
  e.dataTransfer.effectAllowed = 'move';
  e.dataTransfer.setData('text/plain', folder.id);
}

function onFolderDragOver(e, target) {
  if (!dragFolderId.value) return;
  if (dragFolderId.value === target?.id) return;
  // Excluir descendientes
  const excluded = folders.descendantIds(dragFolderId.value);
  if (target && excluded.has(target.id)) return;
  e.preventDefault();
  e.dataTransfer.dropEffect = 'move';
}

async function onFolderDrop(e, target) {
  e.preventDefault();
  if (!dragFolderId.value) return;
  const targetId = target?.id ?? 0; // null = raíz
  const sourceId = dragFolderId.value;
  dragFolderId.value = null;
  if (sourceId === targetId) return;
  try {
    await folders.move(sourceId, targetId);
    ui.toast('📁 Carpeta movida', 'ok');
  } catch (e) {
    ui.toast(e.message || 'No se pudo mover', 'err');
  }
}

function onFolderDragEnd() {
  dragFolderId.value = null;
}

async function deleteFolder(folder) {
  if (!confirm(`¿Eliminar la carpeta "${folder.name}"?\n\nLas imágenes NO se borran, sólo se quitan de la carpeta.`)) {
    folderActions.value = null;
    return;
  }
  try {
    await folders.remove(folder.id);
    ui.toast('🗑 Carpeta eliminada', 'ok');
    // Si estábamos viendo esa carpeta, volver a "todas"
    if (media.filter.folder === folder.id) selectFolder(-1);
  } catch (e) {
    ui.toast(e.message || 'Error al eliminar', 'err');
  } finally {
    folderActions.value = null;
  }
}

// ── Acciones masivas ──
async function bulkMove(folderId) {
  const r = await media.bulkMoveTo(folderId);
  showMovePicker.value = false;
  ui.toast(`✓ ${r.moved} movidas${r.errors.length ? ' · ' + r.errors.length + ' errores' : ''}`, r.errors.length ? 'err' : 'ok');
  await folders.load(true); // refrescar counts
}

async function bulkDelete() {
  if (!confirm(`¿Eliminar ${media.selectedCount} imágenes? No se puede deshacer.`)) return;
  const r = await media.bulkDelete();
  ui.toast(`🗑 ${r.deleted} eliminadas${r.errors.length ? ' · ' + r.errors.length + ' errores' : ''}`, r.errors.length ? 'err' : 'ok');
  await folders.load(true);
}

async function bulkAI() {
  aiProgress.value = { done: 0, errors: 0, total: media.selectedCount };
  await media.bulkGenerateAI(p => { aiProgress.value = p; });
  ui.toast(`✨ ${aiProgress.value.done} generadas${aiProgress.value.errors ? ' · ' + aiProgress.value.errors + ' errores' : ''}`, aiProgress.value.errors ? 'err' : 'ok');
  aiProgress.value = null;
}

async function bulkCopyUrls() {
  const urls = media.bulkCopyUrls();
  if (!urls.length) return;
  try {
    await navigator.clipboard.writeText(urls.join('\n'));
    ui.toast(`📋 ${urls.length} URLs copiadas`, 'ok');
    media.exitSelectMode();
  } catch {
    ui.toast('No se pudo copiar', 'err');
  }
}

async function onBulkGeoPick(payload) {
  try {
    const r = await media.bulkGeo(payload);
    ui.toast(`📍 ${r.updated} ubicaciones asignadas`, 'ok');
  } catch (e) {
    ui.toast(e.message, 'err');
  }
}

async function openTagPicker() {
  showTagPicker.value = true;
  if (!allTags.value.length) {
    try { allTags.value = await StatsAPI.tags(); }
    catch (e) { ui.toast(e.message, 'err'); }
  }
}
async function openColorPicker() {
  showColorPicker.value = true;
  if (!allColors.value.length) {
    try { allColors.value = await StatsAPI.colors(); }
    catch (e) { ui.toast(e.message, 'err'); }
  }
}
function applyTagFilter(tag) {
  media.setFilter({ tag: media.filter.tag === tag ? '' : tag });
  media.load(true);
  showTagPicker.value = false;
}
function applyColorFilter(color) {
  media.setFilter({ color: media.filter.color === color ? '' : color });
  media.load(true);
  showColorPicker.value = false;
}
function clearTagFilter()   { media.setFilter({ tag: '' });   media.load(true); }
function clearColorFilter() { media.setFilter({ color: '' }); media.load(true); }

async function onBulkGeoClear() {
  if (!confirm(`¿Quitar ubicación de ${media.selectedCount} imágenes?`)) return;
  try {
    const r = await media.bulkGeo({ lat: null, lng: null });
    ui.toast(`Ubicación quitada de ${r.updated}`, 'ok');
  } catch (e) {
    ui.toast(e.message, 'err');
  }
}

function toggleOrder() {
  media.setFilter({ order: media.filter.order === 'DESC' ? 'ASC' : 'DESC' });
  media.load(true);
}

function changeOrderby(v) {
  media.setFilter({ orderby: v });
  media.load(true);
}

function changeMime(v) {
  media.setFilter({ mime: v });
  media.load(true);
}

const folderName = computed(() => {
  if (media.filter.folder === -1) return 'Todos los medios';
  if (media.filter.folder === 0)  return 'Sin carpeta';
  return folders.byId[media.filter.folder]?.name || '?';
});

function thumbOrIcon(item) {
  if (item.thumb) return null;
  if ((item.mime || '').startsWith('video/'))  return '🎬';
  if ((item.mime || '').startsWith('audio/'))  return '🔊';
  if (item.mime === 'application/pdf')         return '📄';
  return '📎';
}

// Infinite scroll
const sentinel = ref(null);
let observer;
function setupObserver() {
  if (observer) observer.disconnect();
  if (!sentinel.value) return;
  observer = new IntersectionObserver(([entry]) => {
    if (entry.isIntersecting && !media.loading && media.page < media.pages) {
      media.loadMore();
    }
  });
  observer.observe(sentinel.value);
}
watch(sentinel, setupObserver);
</script>

<template>
  <div>
    <PullRefresh @refresh="async () => { await Promise.all([folders.load(true), media.load(true)]); }" />

    <!-- Toolbar de selección (solo en modo select) -->
    <div v-if="media.selectMode" class="sel-toolbar">
      <button class="sel-cancel" @click="media.exitSelectMode()">✕</button>
      <span class="sel-count">{{ media.selectedCount }} sel.</span>
      <span class="spacer" />
      <button class="sel-act" @click="media.selectAllVisible()" title="Seleccionar todo visible">☑</button>
      <button class="sel-act" @click="showMovePicker = true" :disabled="!media.selectedCount" title="Mover a carpeta">📁</button>
      <button class="sel-act" @click="showBulkGeo = true" :disabled="!media.selectedCount" title="Asignar ubicación">📍</button>
      <button class="sel-act" @click="bulkAI" :disabled="!media.selectedCount" title="Generar IA">✨</button>
      <button class="sel-act" @click="bulkCopyUrls" :disabled="!media.selectedCount" title="Copiar URLs">📋</button>
      <button class="sel-act danger" @click="bulkDelete" :disabled="!media.selectedCount" title="Eliminar">🗑</button>
    </div>

    <!-- Filtros normales -->
    <div v-else class="filters">
      <button class="folder-btn" @click="showFolderPicker = true">
        📁 {{ folderName }}
        <span class="muted small">{{ media.total }} archivos</span>
      </button>

      <input v-model="searchInput" @input="onSearch" placeholder="Buscar…" class="search" />
    </div>

    <!-- Filtros rápidos predefinidos -->
    <div class="quick-filters">
      <button v-for="qf in QUICK_FILTERS" :key="qf.id"
        class="qf-chip" :class="{ on: activeQuickFilter === qf.id }"
        @click="applyQuickFilter(qf)">
        {{ qf.label }}
      </button>
      <button class="qf-chip" :class="{ on: !!media.filter.tag }" @click="openTagPicker">
        🏷 {{ media.filter.tag || 'Tag' }}
        <span v-if="media.filter.tag" class="qf-clear" @click.stop="clearTagFilter">✕</span>
      </button>
      <button class="qf-chip" :class="{ on: !!media.filter.color }" @click="openColorPicker">
        <span v-if="media.filter.color" class="color-dot" :style="{ background: media.filter.color }"></span>
        <span v-else>🎨</span>
        {{ media.filter.color ? media.filter.color : 'Color' }}
        <span v-if="media.filter.color" class="qf-clear" @click.stop="clearColorFilter">✕</span>
      </button>
    </div>

    <div class="chips">
      <button class="chip" :class="{ on: media.filter.mime === ''      }" @click="changeMime('')">Todos</button>
      <button class="chip" :class="{ on: media.filter.mime === 'image' }" @click="changeMime('image')">🖼 Fotos</button>
      <button class="chip" :class="{ on: media.filter.mime === 'video' }" @click="changeMime('video')">🎬 Vídeo</button>
      <button class="chip" :class="{ on: media.filter.mime === 'pdf'   }" @click="changeMime('pdf')">📄 PDF</button>

      <span class="spacer" />

      <select :value="media.filter.orderby" @change="e => changeOrderby(e.target.value)" class="select">
        <option value="date">Fecha</option>
        <option value="title">Nombre</option>
        <option value="size">Tamaño</option>
      </select>
      <button class="chip" @click="toggleOrder">{{ media.filter.order === 'DESC' ? '↓' : '↑' }}</button>
      <div class="view-toggle">
        <button class="vt-btn" :class="{ on: viewMode === 'grid' }" @click="viewMode = 'grid'" title="Cuadrícula">⊞</button>
        <button class="vt-btn" :class="{ on: viewMode === 'list' }" @click="viewMode = 'list'" title="Lista">≡</button>
      </div>
    </div>

    <div class="grid" :class="{ 'list-view': viewMode === 'list' }">
      <button
        v-for="img in media.items"
        :key="img.id"
        class="item"
        :class="{ 'is-sel': media.isSelected(img.id), 'sel-mode': media.selectMode }"
        @click="onItemTap(img)"
        @touchstart.passive="startPress(img.id)"
        @touchend="cancelPress"
        @touchmove.passive="cancelPress"
        @mousedown="startPress(img.id)"
        @mouseup="cancelPress"
        @mouseleave="cancelPress"
        @contextmenu.prevent="media.enterSelectMode(img.id)">
        <div class="thumb">
          <img v-if="img.thumb" :src="img.thumb" :alt="img.title" loading="lazy" />
          <div v-else class="thumb-icon">{{ thumbOrIcon(img) }}</div>
          <div v-if="media.selectMode" class="sel-check" :class="{ on: media.isSelected(img.id) }">
            <span v-if="media.isSelected(img.id)">✓</span>
          </div>
        </div>
        <div class="meta">
          <span class="name" :title="img.title">{{ img.title || img.filename }}</span>
          <span class="size">{{ img.filesize_h }}</span>
          <span v-if="viewMode === 'list'" class="meta-extra">
            {{ img.date }} · {{ img.width }}×{{ img.height }} · {{ img.mime?.replace('image/', '').toUpperCase() }}
          </span>
        </div>
      </button>
    </div>

    <!-- Toast de progreso IA en lote -->
    <div v-if="aiProgress" class="ai-progress">
      <div class="ai-progress-head">
        <span>✨ Generando IA</span>
        <span class="muted small">{{ aiProgress.done + aiProgress.errors }} / {{ aiProgress.total }}</span>
      </div>
      <div class="ai-bar">
        <div class="ai-bar-fill" :style="{ width: ((aiProgress.done + aiProgress.errors) / aiProgress.total * 100) + '%' }" />
      </div>
    </div>

    <!-- Skeleton mientras carga la primera página -->
    <div v-if="media.loading && !media.items.length" class="grid">
      <div v-for="n in 12" :key="n" class="item skeleton-item">
        <Skeleton variant="thumb" />
        <Skeleton variant="text" width="80%" />
        <Skeleton variant="text" width="40%" />
      </div>
    </div>
    <div v-else-if="media.loading" class="center muted"><Spinner /> Cargando más…</div>
    <div v-else-if="!media.items.length" class="empty muted">📭 Sin resultados</div>
    <div ref="sentinel" v-if="media.page < media.pages" style="height:30px"></div>

    <!-- Folder picker bottomsheet -->
    <transition name="sheet">
      <div v-if="showFolderPicker" class="sheet-overlay" @click.self="showFolderPicker = false">
        <div class="sheet">
          <div class="sheet-handle" />

          <div class="sheet-head">
            <h3>Carpetas</h3>
            <div style="display:flex;gap:6px">
              <button class="manage-btn" @click="$router.push({ name: 'folders' }); showFolderPicker = false">
                📁 Gestionar
              </button>
              <button class="add-btn" @click="startCreate(0)" :disabled="creatingFolder">+ Nueva</button>
            </div>
          </div>

          <button class="folder-row" :class="{ on: media.filter.folder === -1 }" @click="selectFolder(-1)">
            🖼 Todos los medios
          </button>
          <button class="folder-row" :class="{ on: media.filter.folder === 0 }" @click="selectFolder(0)">
            📭 Sin carpeta
          </button>
          <hr>

          <!-- Input para crear carpeta en raíz -->
          <div v-if="creatingFolder && newFolderParent === 0" class="folder-row creating" style="padding-left:12px">
            <span style="margin-right:6px">📁</span>
            <input id="new-folder-input"
              v-model="newFolderName"
              @keydown.enter="confirmCreate"
              @keydown.escape="cancelCreate"
              @blur="confirmCreate"
              placeholder="Nombre de la carpeta…"
              maxlength="60"
              class="folder-input" />
          </div>

          <template v-for="f in folders.flat" :key="f.id">
            <div class="folder-line"
              :class="{ 'drag-target': dragFolderId && dragFolderId !== f.id && !folders.descendantIds(dragFolderId).has(f.id) }"
              @dragover="onFolderDragOver($event, f)"
              @drop="onFolderDrop($event, f)">
              <button class="folder-row"
                :class="{ on: media.filter.folder === f.id }"
                :style="{ paddingLeft: (12 + f.depth * 16) + 'px' }"
                draggable="true"
                @click="selectFolder(f.id)"
                @dragstart="onFolderDragStart($event, f)"
                @dragend="onFolderDragEnd">
                📁 {{ f.name }} <span class="muted small">({{ f.count }})</span>
              </button>
              <button class="folder-action" @click.stop="folderActions = folderActions?.id === f.id ? null : { id: f.id, name: f.name, parent: f.parent }">⋯</button>
            </div>

            <!-- Menú de acciones -->
            <div v-if="folderActions?.id === f.id" class="folder-menu">
              <button class="fm-btn" @click="startCreate(f.id)">＋ Sub</button>
              <button class="fm-btn" @click="renameFolder(f)">✏ Renombrar</button>
              <button class="fm-btn" @click="startMoveFolder(f)">📤 Mover</button>
              <button class="fm-btn danger" @click="deleteFolder(f)">🗑 Eliminar</button>
            </div>

            <!-- Input para crear subcarpeta -->
            <div v-if="creatingFolder && newFolderParent === f.id" class="folder-row creating"
              :style="{ paddingLeft: (12 + (f.depth + 1) * 16) + 'px' }">
              <span style="margin-right:6px">📁</span>
              <input id="new-folder-input"
                v-model="newFolderName"
                @keydown.enter="confirmCreate"
                @keydown.escape="cancelCreate"
                @blur="confirmCreate"
                placeholder="Nombre…"
                maxlength="60"
                class="folder-input" />
            </div>
          </template>
        </div>
      </div>
    </transition>

    <!-- Sheet "Mover a" para acciones masivas -->
    <transition name="sheet">
      <div v-if="showMovePicker" class="sheet-overlay" @click.self="showMovePicker = false">
        <div class="sheet">
          <div class="sheet-handle" />
          <div class="sheet-head">
            <h3>Mover {{ media.selectedCount }} a…</h3>
            <button class="close-btn" @click="showMovePicker = false">✕</button>
          </div>
          <button class="folder-row" @click="bulkMove(0)">📭 Sin carpeta</button>
          <hr>
          <button v-for="f in folders.flat" :key="f.id"
            class="folder-row"
            :style="{ paddingLeft: (12 + f.depth * 16) + 'px' }"
            @click="bulkMove(f.id)">
            📁 {{ f.name }}
          </button>
        </div>
      </div>
    </transition>

    <!-- Sheet de Tags -->
    <transition name="sheet">
      <div v-if="showTagPicker" class="sheet-overlay" @click.self="showTagPicker = false">
        <div class="sheet">
          <div class="sheet-handle" />
          <div class="sheet-head">
            <h3>Filtrar por etiqueta</h3>
            <button class="close-btn" @click="showTagPicker = false">✕</button>
          </div>
          <p v-if="!allTags.length" class="muted small empty-msg">
            No hay tags todavía. Genera con IA imágenes para que aparezcan aquí.
          </p>
          <div v-else class="tag-cloud">
            <button v-for="t in allTags" :key="t.tag"
              class="tag-chip" :class="{ on: media.filter.tag === t.tag }"
              @click="applyTagFilter(t.tag)">
              {{ t.tag }} <span class="muted small">{{ t.count }}</span>
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Sheet de Colores -->
    <transition name="sheet">
      <div v-if="showColorPicker" class="sheet-overlay" @click.self="showColorPicker = false">
        <div class="sheet">
          <div class="sheet-handle" />
          <div class="sheet-head">
            <h3>Filtrar por color dominante</h3>
            <button class="close-btn" @click="showColorPicker = false">✕</button>
          </div>
          <p v-if="!allColors.length" class="muted small empty-msg">
            No hay paletas todavía. Sube imágenes nuevas (la paleta se calcula al subir).
          </p>
          <div v-else class="color-grid">
            <button v-for="c in allColors" :key="c.color"
              class="color-cell" :class="{ on: media.filter.color === c.color }"
              :style="{ background: c.color }"
              :title="c.color + ' · ' + c.count + ' imágenes'"
              @click="applyColorFilter(c.color)">
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- GeoTagger en lote -->
    <GeoTagger v-model="showBulkGeo"
      :initial-lat="null"
      :initial-lng="null"
      title="Asignar ubicación a varias"
      :subtitle="media.selectedCount + ' imágenes seleccionadas'"
      :allow-clear="false"
      @pick="onBulkGeoPick" />

    <!-- Sheet "Mover carpeta a..." (cambiar parent) -->
    <transition name="sheet">
      <div v-if="movingFolder" class="sheet-overlay" @click.self="movingFolder = null">
        <div class="sheet">
          <div class="sheet-handle" />
          <div class="sheet-head">
            <h3>Mover "{{ movingFolder.name }}" a…</h3>
            <button class="close-btn" @click="movingFolder = null">✕</button>
          </div>
          <button class="folder-row" @click="moveFolderTo(0)">📁 Raíz (sin padre)</button>
          <hr v-if="moveTargets.length">
          <button v-for="f in moveTargets" :key="f.id"
            class="folder-row"
            :style="{ paddingLeft: (12 + f.depth * 16) + 'px' }"
            @click="moveFolderTo(f.id)">
            📁 {{ f.name }}
          </button>
          <p v-if="!moveTargets.length" class="muted small" style="text-align:center;padding:14px">
            No hay otras carpetas disponibles como destino.
          </p>
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.filters { display: flex; flex-direction: column; gap: 8px; margin-bottom: 12px; }
.folder-btn {
  display: flex; align-items: center; justify-content: space-between;
  background: var(--s1); border: 1px solid var(--border);
  border-radius: var(--radius); padding: 10px 12px;
  font-size: 14px; color: var(--text);
  min-height: var(--tap);
}
.search { background: var(--s1); }
.small { font-size: 11px; }

.chips {
  display: flex; gap: 6px; align-items: center;
  margin-bottom: 12px;
  overflow-x: auto;
  scrollbar-width: none;
  padding-bottom: 4px;
}
.chips::-webkit-scrollbar { display: none; }

.quick-filters {
  display: flex; gap: 6px;
  margin-bottom: 8px;
  overflow-x: auto;
  scrollbar-width: none;
  padding-bottom: 4px;
}
.quick-filters::-webkit-scrollbar { display: none; }
.qf-chip {
  flex: 0 0 auto;
  padding: 6px 12px;
  border-radius: 20px;
  background: var(--s2);
  border: 1px solid var(--border);
  color: var(--text-mute);
  font-size: 12px;
  white-space: nowrap;
  min-height: 32px;
}
.qf-chip.on {
  background: var(--accent-lo);
  color: var(--accent);
  border-color: var(--accent);
  font-weight: 500;
}
.qf-clear {
  margin-left: 4px;
  padding: 0 4px;
  border-radius: 50%;
  font-size: 10px;
  opacity: .7;
}
.qf-clear:hover { opacity: 1; }
.color-dot {
  display: inline-block;
  width: 12px; height: 12px;
  border-radius: 50%;
  border: 1px solid var(--border);
  vertical-align: middle;
  margin-right: 4px;
}

/* Tag cloud */
.tag-cloud { display: flex; flex-wrap: wrap; gap: 6px; padding: 4px 0; }
.tag-chip {
  padding: 6px 12px;
  background: var(--s2);
  border: 1px solid var(--border);
  border-radius: 16px;
  font-size: 13px;
  color: var(--text);
}
.tag-chip.on { background: var(--accent); color: #0f0f0f; border-color: var(--accent); }
.empty-msg { padding: 20px; text-align: center; }

/* Color grid */
.color-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(36px, 1fr));
  gap: 4px;
  padding: 4px 0;
}
.color-cell {
  aspect-ratio: 1;
  border-radius: 6px;
  border: 2px solid transparent;
  transition: transform .15s, border-color .15s;
}
.color-cell:hover { transform: scale(1.1); border-color: rgba(255,255,255,.3); }
.color-cell.on { border-color: white; transform: scale(1.15); }
.chip {
  flex: 0 0 auto;
  padding: 6px 12px;
  border-radius: 20px;
  background: var(--s2); color: var(--text-mute);
  font-size: 12px; font-weight: 500;
  border: 1px solid var(--border);
  white-space: nowrap;
  min-height: 32px;
}
.chip.on { background: var(--accent); color: #0f0f0f; border-color: var(--accent); }
.select {
  padding: 6px 8px; height: 32px; font-size: 12px;
  background: var(--s2); border: 1px solid var(--border); color: var(--text);
  border-radius: 16px;
  width: auto;
}

.view-toggle {
  display: flex; gap: 2px;
  background: var(--s2);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 2px;
}
.vt-btn {
  width: 28px; height: 28px;
  border-radius: 12px;
  font-size: 14px;
  color: var(--text-mute);
}
.vt-btn.on { background: var(--s1); color: var(--accent); }

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
  gap: 6px;
}
/* Vista lista: 1 fila por ítem con thumbnail a la izquierda */
.grid.list-view {
  display: flex; flex-direction: column;
  gap: 4px;
}
.grid.list-view .item {
  flex-direction: row;
  align-items: center;
  padding: 8px;
  gap: 12px;
}
.grid.list-view .thumb {
  flex: 0 0 60px;
  width: 60px; height: 60px;
  aspect-ratio: 1;
  border-radius: var(--radius);
}
.grid.list-view .meta {
  flex: 1; min-width: 0;
  padding: 0;
}
.meta-extra {
  display: block;
  font-size: 11px;
  color: var(--text-mute);
  margin-top: 2px;
}
.item {
  display: flex; flex-direction: column;
  background: var(--s1); border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  text-align: left;
  position: relative;
  user-select: none;
  -webkit-user-select: none;
  transition: border-color .15s, transform .1s;
}
.item:active { transform: scale(.98); }
.item.is-sel { border-color: var(--accent); }
.item.is-sel::after {
  content: '';
  position: absolute; inset: 0;
  background: rgba(200, 169, 126, .14);
  pointer-events: none;
}
.sel-check {
  position: absolute;
  top: 6px; right: 6px;
  width: 22px; height: 22px;
  border-radius: 50%;
  background: rgba(0,0,0,.5);
  border: 2px solid white;
  display: flex; align-items: center; justify-content: center;
  font-size: 12px;
  color: white;
  z-index: 2;
}
.sel-check.on {
  background: var(--accent);
  border-color: var(--accent);
  color: #0f0f0f;
}

/* Toolbar selección */
.sel-toolbar {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 8px;
  background: var(--accent-lo);
  border: 1px solid var(--accent);
  border-radius: var(--radius);
  margin-bottom: 12px;
  position: sticky;
  top: 52px; /* debajo del topbar */
  z-index: 5;
}
.sel-cancel { font-size: 18px; padding: 0 10px; color: var(--text); }
.sel-count { font-size: 13px; font-weight: 600; color: var(--accent); }
.sel-act {
  width: 38px; height: 38px;
  border-radius: var(--radius);
  font-size: 16px;
  background: var(--s1);
}
.sel-act:active { background: var(--s2); }
.sel-act:disabled { opacity: .4; }
.sel-act.danger { color: var(--danger); }

/* Progreso IA en lote */
.ai-progress {
  position: fixed;
  bottom: calc(80px + env(safe-area-inset-bottom));
  left: 16px; right: 16px;
  background: var(--s1);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 10px 14px;
  z-index: 90;
}
.ai-progress-head { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 6px; }
.ai-bar { background: var(--s2); height: 4px; border-radius: 2px; overflow: hidden; }
.ai-bar-fill { background: var(--accent); height: 100%; transition: width .3s; }
.close-btn { font-size: 22px; color: var(--text-mute); padding: 4px 8px; }
.thumb {
  aspect-ratio: 1;
  background: var(--s2);
  display: flex; align-items: center; justify-content: center;
}
.thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.thumb-icon { font-size: 32px; }
.meta { padding: 6px 8px; }
.name {
  display: block; font-size: 12px; font-weight: 500;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.size { font-size: 10px; color: var(--text-mute); }

/* Skeleton placeholder mientras carga */
.skeleton-item {
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 0;
  display: flex; flex-direction: column;
  background: var(--s1);
}
.skeleton-item > * + * { margin-top: 4px; }
.skeleton-item :deep(.skel.thumb) { border-radius: var(--radius) var(--radius) 0 0; }
.skeleton-item :deep(.skel.text)  { margin: 4px 8px; }

.center { display: flex; gap: 10px; justify-content: center; padding: 30px; }
.empty { text-align: center; padding: 40px 16px; }

/* Bottomsheet */
.sheet-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.5);
  z-index: 1350;  /* consistente con el resto de bottomsheets, encima de Leaflet */
  display: flex; align-items: flex-end;
}
.sheet {
  width: 100%;
  max-height: 80vh;
  background: var(--s1);
  border-top-left-radius: 18px;
  border-top-right-radius: 18px;
  padding: 14px 16px calc(20px + env(safe-area-inset-bottom));
  overflow-y: auto;
}
.sheet-handle {
  width: 40px; height: 4px;
  background: var(--border2);
  border-radius: 2px;
  margin: -4px auto 12px;
}
.sheet h3 { margin: 0; font-size: 14px; text-transform: uppercase; color: var(--text-mute); letter-spacing: .5px; }
.sheet-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.add-btn {
  padding: 6px 12px;
  background: var(--accent-lo);
  color: var(--accent);
  border-radius: 16px;
  font-size: 12px;
  font-weight: 500;
}
.add-btn:disabled { opacity: .4; }
.manage-btn {
  padding: 6px 12px;
  background: var(--s2);
  color: var(--text-mute);
  border-radius: 16px;
  font-size: 12px;
}
.manage-btn:active { background: var(--s3); color: var(--text); }

.folder-row {
  display: block; width: 100%;
  text-align: left;
  padding: 12px;
  border-radius: var(--radius);
  font-size: 14px;
  color: var(--text);
}
.folder-row:active { background: var(--s2); }
.folder-row.on    { background: var(--accent-lo); color: var(--accent); font-weight: 500; }
.folder-row.creating { display: flex; align-items: center; background: var(--s2); }

.folder-line { display: flex; align-items: center; transition: background .15s; }
.folder-line .folder-row { flex: 1; min-width: 0; }
.folder-line.drag-target { background: var(--accent-lo); }
.folder-line.drag-target .folder-row { color: var(--accent); font-weight: 500; }
.folder-action {
  width: 36px; height: 36px;
  color: var(--text-mute);
  font-size: 18px;
  border-radius: var(--radius);
}
.folder-action:active { background: var(--s2); }

.folder-input {
  flex: 1;
  background: transparent;
  border: 0;
  padding: 0;
  font-size: 14px;
  color: var(--text);
}
.folder-input:focus { outline: none; border: 0; }

.folder-menu {
  display: flex; gap: 4px;
  padding: 4px 12px 8px;
}
.fm-btn {
  flex: 1;
  padding: 8px;
  background: var(--s2);
  border-radius: var(--radius);
  font-size: 12px;
  color: var(--text);
}
.fm-btn:active { background: var(--s3); }
.fm-btn.danger { color: var(--danger); }

hr { border: 0; border-top: 1px solid var(--border); margin: 8px 0; }

.sheet-enter-active, .sheet-leave-active { transition: opacity .25s; }
.sheet-enter-active .sheet, .sheet-leave-active .sheet { transition: transform .25s; }
.sheet-enter-from, .sheet-leave-to { opacity: 0; }
.sheet-enter-from .sheet, .sheet-leave-to .sheet { transform: translateY(100%); }
</style>
