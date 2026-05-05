<script setup>
import { ref, computed, onMounted, onActivated } from 'vue';
import { useRouter } from 'vue-router';
import { useFoldersStore } from '../stores/folders';
import { useUiStore } from '../stores/ui';
import Spinner from '../components/Spinner.vue';
import PullRefresh from '../components/PullRefresh.vue';

const router  = useRouter();
const folders = useFoldersStore();
const ui      = useUiStore();

const collapsed = ref(new Set());      // ids de carpetas colapsadas
const creating  = ref(false);
const newName   = ref('');
const newParent = ref(0);
const renaming  = ref(null);           // { id, name }
const renameValue = ref('');
const movingFolder = ref(null);        // { id, name, parent } cuando elegimos destino para mover
const dragId    = ref(null);

onMounted(async () => {
  await folders.load();
});

// Refrescar al volver a la pestaña (los counts pueden haber cambiado tras
// subidas, asignaciones o cambios desde otras vistas).
onActivated(() => { folders.load(true); });

const stats = computed(() => {
  const all  = folders.flat;
  const tot  = all.length;
  const imgs = all.reduce((acc, f) => acc + (f.count || 0), 0);
  return { folders: tot, images: imgs };
});

function toggle(id) {
  if (collapsed.value.has(id)) collapsed.value.delete(id);
  else                          collapsed.value.add(id);
  collapsed.value = new Set(collapsed.value); // forzar reactividad
}

function isVisible(node) {
  // Un nodo es visible si ninguno de sus ancestros está colapsado
  let p = node.parent;
  while (p) {
    if (collapsed.value.has(p)) return false;
    const pNode = folders.byId[p];
    if (!pNode) break;
    p = pNode.parent;
  }
  return true;
}

const visibleFlat = computed(() => folders.flat.filter(isVisible));

function expandAll()   { collapsed.value = new Set(); }
function collapseAll() {
  // Colapsar sólo las que tienen hijos
  const ids = new Set();
  folders.flat.forEach(f => {
    if (folders.flat.some(x => x.parent === f.id)) ids.add(f.id);
  });
  collapsed.value = ids;
}

function hasChildren(id) {
  return folders.flat.some(f => f.parent === id);
}

// ── CREAR ─────────────────────────────────────────────────
function startCreate(parentId = 0) {
  newParent.value = parentId;
  newName.value = '';
  creating.value = true;
  // Expande el padre si está colapsado
  if (parentId > 0) collapsed.value.delete(parentId);
  setTimeout(() => document.getElementById('fnew-input')?.focus(), 50);
}

async function confirmCreate() {
  const name = newName.value.trim();
  if (!name) { creating.value = false; return; }
  try {
    await folders.create(name, newParent.value);
    ui.toast(`📁 "${name}" creada`, 'ok');
  } catch (e) {
    ui.toast(e.message || 'Error al crear', 'err');
  } finally {
    creating.value = false;
    newName.value = '';
  }
}

// ── RENOMBRAR ─────────────────────────────────────────────
function startRename(node) {
  renaming.value = { id: node.id };
  renameValue.value = node.name;
  setTimeout(() => document.getElementById('fren-input')?.focus(), 50);
}

async function confirmRename() {
  if (!renaming.value) return;
  const node = folders.byId[renaming.value.id];
  const name = renameValue.value.trim();
  if (!name || name === node?.name) { renaming.value = null; return; }
  try {
    await folders.rename(renaming.value.id, name);
    ui.toast('✏️ Renombrada', 'ok');
  } catch (e) {
    ui.toast(e.message || 'Error', 'err');
  } finally {
    renaming.value = null;
  }
}

// ── MOVER ─────────────────────────────────────────────────
function startMove(node) {
  movingFolder.value = { id: node.id, name: node.name, parent: node.parent };
}

async function moveTo(parentId) {
  if (!movingFolder.value) return;
  const f = movingFolder.value;
  if (parentId === f.parent) { movingFolder.value = null; return; }
  try {
    await folders.move(f.id, parentId);
    ui.toast(`📁 "${f.name}" ${parentId === 0 ? 'movida a la raíz' : 'movida'}`, 'ok');
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

// ── ELIMINAR ──────────────────────────────────────────────
async function remove(node) {
  const childCount = folders.flat.filter(f => f.parent === node.id).length;
  let msg = `¿Eliminar la carpeta "${node.name}"?\n\nLas imágenes NO se borran, solo se quitan de la carpeta.`;
  if (childCount > 0) msg += `\n\n⚠️ Tiene ${childCount} subcarpeta${childCount !== 1 ? 's' : ''}.`;
  if (!confirm(msg)) return;
  try {
    await folders.remove(node.id);
    ui.toast('🗑 Carpeta eliminada', 'ok');
  } catch (e) {
    ui.toast(e.message || 'Error al eliminar', 'err');
  }
}

// ── DRAG & DROP NATIVO (escritorio) ───────────────────────
function onDragStart(e, node) {
  dragId.value = node.id;
  e.dataTransfer.effectAllowed = 'move';
  e.dataTransfer.setData('text/plain', node.id);
}

function canDropOn(targetId) {
  if (!dragId.value) return false;
  if (dragId.value === targetId) return false;
  const excl = folders.descendantIds(dragId.value);
  return !excl.has(targetId);
}

function onDragOver(e, targetId) {
  if (!canDropOn(targetId)) return;
  e.preventDefault();
  e.dataTransfer.dropEffect = 'move';
}

async function onDrop(e, targetId) {
  e.preventDefault();
  if (!dragId.value) return;
  const sourceId = dragId.value;
  dragId.value = null;
  if (sourceId === targetId) return;
  try {
    await folders.move(sourceId, targetId);
    ui.toast('📁 Carpeta movida', 'ok');
  } catch (err) {
    ui.toast(err.message || 'No se pudo mover', 'err');
  }
}

function onDragEnd() { dragId.value = null; }

// ── NAVEGAR A LAS IMÁGENES DE UNA CARPETA ────────────────
function browseFolder(id) {
  router.push({ name: 'media', query: { folder: id } });
}
</script>

<template>
  <div>
    <PullRefresh @refresh="async () => { await folders.load(true); }" />

    <!-- Header con stats y acciones globales -->
    <div class="card head-card">
      <div class="stats-line">
        <div>
          <span class="big">{{ stats.folders }}</span>
          <span class="muted small">carpetas</span>
        </div>
        <div>
          <span class="big">{{ stats.images }}</span>
          <span class="muted small">imágenes</span>
        </div>
      </div>
      <div class="head-actions">
        <button class="btn pri" @click="startCreate(0)">+ Nueva carpeta</button>
        <button class="btn ghost" @click="expandAll" title="Expandir todo">⊟</button>
        <button class="btn ghost" @click="collapseAll" title="Colapsar todo">⊞</button>
      </div>
    </div>

    <div v-if="folders.loading && !folders.flat.length" class="center muted">
      <Spinner /> Cargando…
    </div>

    <!-- Árbol -->
    <div v-else class="tree card">
      <!-- Input para crear en raíz -->
      <div v-if="creating && newParent === 0" class="row-line creating"
        :style="{ paddingLeft: '12px' }">
        <span class="t-icon">📁</span>
        <input id="fnew-input"
          v-model="newName"
          placeholder="Nombre de la carpeta…"
          @keydown.enter="confirmCreate"
          @keydown.escape="creating = false"
          @blur="confirmCreate"
          maxlength="60"
          class="inline-input" />
      </div>

      <div v-if="!folders.flat.length && !creating" class="empty muted">
        Sin carpetas. Pulsa "+ Nueva carpeta" para crear la primera.
      </div>

      <template v-for="node in visibleFlat" :key="node.id">
        <div class="row-line"
          :class="{ 'drag-target': dragId && canDropOn(node.id) }"
          :style="{ paddingLeft: (12 + node.depth * 18) + 'px' }"
          draggable="true"
          @dragstart="onDragStart($event, node)"
          @dragover="onDragOver($event, node.id)"
          @drop="onDrop($event, node.id)"
          @dragend="onDragEnd">

          <button v-if="hasChildren(node.id)"
            class="t-toggle"
            :class="{ open: !collapsed.has(node.id) }"
            @click.stop="toggle(node.id)">▶</button>
          <span v-else class="t-toggle-empty"></span>

          <span class="t-icon">📁</span>

          <input v-if="renaming?.id === node.id"
            id="fren-input"
            v-model="renameValue"
            @keydown.enter="confirmRename"
            @keydown.escape="renaming = null"
            @blur="confirmRename"
            maxlength="60"
            class="inline-input" />

          <button v-else class="t-name" @click="browseFolder(node.id)" title="Ver imágenes">
            {{ node.name }}
          </button>

          <span class="t-count muted small">{{ node.count }}</span>

          <div v-if="renaming?.id !== node.id" class="t-actions">
            <button v-if="node.count > 0"
              @click="$router.push({ name: 'slideshow', query: { folder: node.id } })"
              title="Modo presentación">▶</button>
            <button @click="startCreate(node.id)" title="Subcarpeta">＋</button>
            <button @click="startRename(node)" title="Renombrar">✏</button>
            <button @click="startMove(node)" title="Mover a otra carpeta">📤</button>
            <button class="danger" @click="remove(node)" title="Eliminar">🗑</button>
          </div>
        </div>

        <!-- Input para crear subcarpeta -->
        <div v-if="creating && newParent === node.id" class="row-line creating"
          :style="{ paddingLeft: (12 + (node.depth + 1) * 18 + 28) + 'px' }">
          <span class="t-icon">📁</span>
          <input id="fnew-input"
            v-model="newName"
            placeholder="Nombre…"
            @keydown.enter="confirmCreate"
            @keydown.escape="creating = false"
            @blur="confirmCreate"
            maxlength="60"
            class="inline-input" />
        </div>
      </template>
    </div>

    <p class="hint muted small">
      💡 <strong>Móvil</strong>: usa los botones en cada carpeta · <strong>Escritorio</strong>: arrastra una carpeta sobre otra para moverla
    </p>

    <!-- Sheet "Mover a..." -->
    <transition name="sheet">
      <div v-if="movingFolder" class="sheet-overlay" @click.self="movingFolder = null">
        <div class="sheet">
          <div class="sheet-handle" />
          <div class="sheet-head">
            <h3>Mover "{{ movingFolder.name }}" a…</h3>
            <button class="close-btn" @click="movingFolder = null">✕</button>
          </div>
          <button class="folder-row" @click="moveTo(0)">📁 Raíz (sin padre)</button>
          <hr v-if="moveTargets.length">
          <button v-for="f in moveTargets" :key="f.id"
            class="folder-row"
            :style="{ paddingLeft: (12 + f.depth * 16) + 'px' }"
            @click="moveTo(f.id)">
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
.head-card {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 14px;
  flex-wrap: wrap;
  gap: 10px;
}
.stats-line { display: flex; gap: 24px; }
.stats-line > div { display: flex; flex-direction: column; }
.big { font-size: 22px; font-weight: 700; color: var(--accent); line-height: 1; }
.head-actions { display: flex; gap: 6px; }
.head-actions .btn.ghost { padding: 6px 10px; min-height: 36px; }
.small { font-size: 11px; }

.center { display: flex; gap: 10px; justify-content: center; padding: 30px; }
.empty { padding: 30px 16px; text-align: center; font-size: 13px; }

.tree { padding: 0; overflow: hidden; }

.row-line {
  display: flex; align-items: center; gap: 6px;
  padding: 10px 12px 10px 0;
  border-bottom: 1px solid var(--border);
  transition: background .12s;
}
.row-line:last-child { border-bottom: 0; }
.row-line.creating { background: var(--s2); }
.row-line.drag-target { background: var(--accent-lo); }
.row-line.drag-target .t-name { color: var(--accent); font-weight: 500; }

.t-toggle, .t-toggle-empty {
  width: 22px; height: 22px;
  flex: 0 0 22px;
  display: flex; align-items: center; justify-content: center;
  font-size: 9px;
  color: var(--text-mute);
  transition: transform .15s;
}
.t-toggle:active { background: var(--s2); border-radius: 4px; }
.t-toggle.open { transform: rotate(90deg); }

.t-icon { width: 22px; flex: 0 0 22px; text-align: center; }

.t-name {
  flex: 1;
  font-size: 14px;
  color: var(--text);
  text-align: left;
  padding: 4px 6px;
  border-radius: var(--radius);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.t-name:active { background: var(--s2); }

.t-count {
  flex: 0 0 auto;
  font-size: 11px;
  padding: 0 6px;
}

.inline-input {
  flex: 1;
  background: transparent;
  border: 0;
  padding: 4px 6px;
  font-size: 14px;
  color: var(--text);
}
.inline-input:focus { outline: none; }

.t-actions { display: flex; gap: 2px; flex: 0 0 auto; }
.t-actions button {
  width: 30px; height: 30px;
  color: var(--text-mute);
  font-size: 13px;
  border-radius: var(--radius);
}
.t-actions button:active { background: var(--s2); }
.t-actions button.danger { color: var(--danger); }

.hint { text-align: center; padding: 12px 0; font-size: 11px; }
.hint strong { color: var(--text); }

/* Sheet "Mover a..." */
.sheet-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.5);
  z-index: 1350;  /* consistente con el resto de bottomsheets */
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
.sheet-handle { width: 40px; height: 4px; background: var(--border2); border-radius: 2px; margin: -4px auto 12px; }
.sheet-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.sheet-head h3 { margin: 0; font-size: 14px; text-transform: uppercase; color: var(--text-mute); letter-spacing: .5px; }
.close-btn { font-size: 22px; color: var(--text-mute); padding: 4px 8px; }
.folder-row {
  display: block; width: 100%;
  text-align: left;
  padding: 12px;
  border-radius: var(--radius);
  font-size: 14px;
  color: var(--text);
}
.folder-row:active { background: var(--s2); }
hr { border: 0; border-top: 1px solid var(--border); margin: 8px 0; }

.sheet-enter-active, .sheet-leave-active { transition: opacity .25s; }
.sheet-enter-active .sheet, .sheet-leave-active .sheet { transition: transform .25s; }
.sheet-enter-from, .sheet-leave-to { opacity: 0; }
.sheet-enter-from .sheet, .sheet-leave-to .sheet { transform: translateY(100%); }

/* Acciones en escritorio: aparecen on hover. En móvil siempre visibles */
@media (hover: hover) {
  .t-actions { opacity: 0; transition: opacity .15s; }
  .row-line:hover .t-actions { opacity: 1; }
}
</style>
