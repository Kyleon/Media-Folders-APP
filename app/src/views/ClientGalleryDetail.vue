<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { ClientPortalAPI, PortfoliosAPI, FoldersAPI, MediaAPI } from '../api/endpoints';
import { useFoldersStore } from '../stores/folders';
import { usePortfoliosStore } from '../stores/portfolios';
import { useUiStore } from '../stores/ui';
import Spinner from '../components/Spinner.vue';
import MediaPicker from '../components/MediaPicker.vue';
import draggable from 'vuedraggable';

const props = defineProps({ id: { type: [String, Number], required: true } });
const router     = useRouter();
const folders    = useFoldersStore();
const portfolios = usePortfoliosStore();
const ui         = useUiStore();

const item    = ref(null);
const loading = ref(true);
const saving  = ref(false);
const dirty   = ref(false);
const tab     = ref('config');     // config | images | activity
const showPicker = ref(false);
const showSyncSheet = ref(false);
const actions = ref([]);
const loadingActions = ref(false);
const expiresInput = ref('');
const passwordInput = ref('');
const clearPassword = ref(false);
const syncFolderId = ref(0);
const syncPortfolioId = ref(0);

async function load() {
  loading.value = true;
  try {
    const [it] = await Promise.all([
      ClientPortalAPI.detail(props.id),
      folders.load(),
      portfolios.load(true),
    ]);
    item.value = it;
    expiresInput.value = it.expires
      ? new Date(it.expires * 1000).toISOString().slice(0, 10)
      : '';
    passwordInput.value = '';
    clearPassword.value = false;
    dirty.value = false;
  } catch (e) {
    ui.toast(e.message || 'Error al cargar', 'err');
    router.back();
  } finally {
    loading.value = false;
  }
}

onMounted(load);

const imageIds = computed(() => (item.value?.images || []).map(i => i.id));

function markDirty() { dirty.value = true; }

function onClearPasswordToggle() {
  passwordInput.value = '';
  markDirty();
}

async function save() {
  saving.value = true;
  try {
    const body = {
      title:          item.value.title,
      client_name:    item.value.client_name || '',
      client_email:   item.value.client_email || '',
      message:        item.value.message || '',
      allow_download: !!item.value.allow_download,
      allow_comments: !!item.value.allow_comments,
      images:         imageIds.value,
    };
    if (expiresInput.value) {
      body.expires = Math.floor(new Date(expiresInput.value + 'T23:59:59').getTime() / 1000);
    } else {
      body.expires = 0;
    }
    if (clearPassword.value) {
      body.password = '';
    } else if (passwordInput.value) {
      body.password = passwordInput.value;
    }
    const updated = await ClientPortalAPI.update(props.id, body);
    item.value = { ...item.value, ...updated };
    // Reload images con thumbs (admin_update no las devuelve)
    item.value.images = (await ClientPortalAPI.detail(props.id)).images;
    passwordInput.value = '';
    clearPassword.value = false;
    dirty.value = false;
    ui.toast('✓ Guardado', 'ok');
  } catch (e) {
    ui.toast(e.message || 'Error al guardar', 'err');
  } finally {
    saving.value = false;
  }
}

async function remove() {
  if (!confirm(`¿Eliminar la galería "${item.value.title}"? Esta acción no se puede deshacer y borra también todas las favoritas y comentarios.`)) return;
  try {
    await ClientPortalAPI.remove(props.id);
    ui.toast('🗑 Galería eliminada', 'ok');
    router.replace({ name: 'client-galleries' });
  } catch (e) { ui.toast(e.message || 'Error', 'err'); }
}

async function copyLink() {
  try {
    await navigator.clipboard.writeText(item.value.url);
    ui.toast('📋 Enlace copiado', 'ok');
  } catch { ui.toast('No se pudo copiar', 'err'); }
}

function onPickImages(picked) {
  const arr = Array.isArray(picked) ? picked : [picked];
  const existing = new Set(imageIds.value);
  const news = arr.filter(p => !existing.has(p.id));
  if (!news.length) { ui.toast('Ya estaban todas en la galería', 'ok'); return; }
  item.value.images.push(...news.map(p => ({
    id: p.id, title: p.title, alt: p.alt,
    thumb: p.thumb, medium: p.medium, url: p.url,
  })));
  markDirty();
  ui.toast(`+ ${news.length} imágenes`, 'ok');
}

function removeImage(id) {
  item.value.images = item.value.images.filter(i => i.id !== id);
  markDirty();
}

function onReorder() { markDirty(); }

async function syncFromFolder() {
  if (!syncFolderId.value) { ui.toast('Elige una carpeta', 'err'); return; }
  try {
    const r = await MediaAPI.list({ folder: syncFolderId.value, per_page: 200, mime: 'image' });
    const news = (r.images || []).map(x => ({
      id: x.id, title: x.title, alt: x.alt,
      thumb: x.thumb, medium: x.medium, url: x.url,
    }));
    if (!confirm(`Importar ${news.length} imágenes de la carpeta? Las actuales se conservarán (puedes quitarlas después).`)) return;
    const existing = new Set(imageIds.value);
    const filtered = news.filter(n => !existing.has(n.id));
    item.value.images.push(...filtered);
    markDirty();
    showSyncSheet.value = false;
    syncFolderId.value = 0;
    ui.toast(`+ ${filtered.length} imágenes importadas`, 'ok');
  } catch (e) { ui.toast(e.message || 'Error', 'err'); }
}

async function syncFromPortfolio() {
  if (!syncPortfolioId.value) { ui.toast('Elige un portfolio', 'err'); return; }
  try {
    const r = await PortfoliosAPI.gallery(syncPortfolioId.value);
    const news = (r.gallery || []).map(g => ({
      id: g.id, title: g.title, alt: g.alt,
      thumb: g.thumb, medium: g.medium, url: g.url,
    }));
    if (!confirm(`Importar ${news.length} imágenes del portfolio? Las actuales se conservarán.`)) return;
    const existing = new Set(imageIds.value);
    const filtered = news.filter(n => !existing.has(n.id));
    item.value.images.push(...filtered);
    markDirty();
    showSyncSheet.value = false;
    syncPortfolioId.value = 0;
    ui.toast(`+ ${filtered.length} imágenes importadas`, 'ok');
  } catch (e) { ui.toast(e.message || 'Error', 'err'); }
}

function clearAll() {
  if (!item.value.images.length) return;
  if (!confirm(`Quitar las ${item.value.images.length} imágenes de la galería? (no las borra del catálogo)`)) return;
  item.value.images = [];
  markDirty();
}

async function loadActivity() {
  loadingActions.value = true;
  try {
    actions.value = await ClientPortalAPI.actions(props.id);
  } catch (e) { ui.toast(e.message, 'err'); }
  finally { loadingActions.value = false; }
}

watch(tab, (v) => { if (v === 'activity' && !actions.value.length) loadActivity(); });

const favCount = computed(() => actions.value.filter(a => a.type === 'favorite').length);
const commentCount = computed(() => actions.value.filter(a => a.type === 'comment').length);

// ── Export de favoritas ─────────────────────────────────────────────
const favorites = computed(() =>
  actions.value
    .filter(a => a.type === 'favorite')
    .filter((a, idx, arr) => arr.findIndex(x => x.att_id === a.att_id) === idx) // dedupe
);

const commentsByAtt = computed(() => {
  const map = {};
  for (const a of actions.value) {
    if (a.type !== 'comment') continue;
    const text = a.payload?.text || '';
    if (!text) continue;
    if (!map[a.att_id]) map[a.att_id] = [];
    map[a.att_id].push(text);
  }
  return map;
});

function safeName(s) {
  return String(s || 'gallery').replace(/[^a-z0-9_-]+/gi, '-').replace(/^-+|-+$/g, '').toLowerCase() || 'gallery';
}

function downloadBlob(content, filename, mime) {
  const blob = new Blob([content], { type: mime });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(() => URL.revokeObjectURL(url), 200);
}

function exportFavoritesTXT() {
  if (!favorites.value.length) { ui.toast('No hay favoritas que exportar', 'err'); return; }
  const lines = favorites.value
    .map(f => f.filename || '')
    .filter(Boolean);
  const txt = lines.join('\n') + '\n';
  const name = `favoritas-${safeName(item.value.title)}-${item.value.id}.txt`;
  downloadBlob(txt, name, 'text/plain;charset=utf-8');
  ui.toast(`✓ Descargando ${lines.length} nombres`, 'ok');
}

function exportFavoritesCSV() {
  if (!favorites.value.length) { ui.toast('No hay favoritas que exportar', 'err'); return; }
  const esc = (v) => {
    const s = String(v == null ? '' : v);
    return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
  };
  const header = ['filename', 'id', 'title', 'comments'];
  const rows = favorites.value.map(f => [
    f.filename || '',
    f.att_id,
    f.att_title || '',
    (commentsByAtt.value[f.att_id] || []).join(' | '),
  ]);
  const csv = [header, ...rows].map(r => r.map(esc).join(',')).join('\n') + '\n';
  // BOM para Excel
  const name = `favoritas-${safeName(item.value.title)}-${item.value.id}.csv`;
  downloadBlob('﻿' + csv, name, 'text/csv;charset=utf-8');
  ui.toast(`✓ Descargando CSV con ${rows.length} filas`, 'ok');
}

async function copyFavoritesToClipboard() {
  if (!favorites.value.length) { ui.toast('No hay favoritas', 'err'); return; }
  const txt = favorites.value.map(f => f.filename || '').filter(Boolean).join('\n');
  try {
    await navigator.clipboard.writeText(txt);
    ui.toast(`📋 ${favorites.value.length} nombres copiados`, 'ok');
  } catch { ui.toast('No se pudo copiar', 'err'); }
}
</script>

<template>
  <div v-if="loading" class="center muted"><Spinner /> Cargando…</div>

  <div v-else-if="item" class="cgd-layout">
    <!-- Header con título y enlace -->
    <div class="card head-card">
      <div class="head-row">
        <input v-model="item.title" @input="markDirty" class="title-input" placeholder="Título de la galería" />
        <span class="status" :class="item.expires && item.expires < Date.now()/1000 ? 'expired' : 'active'">
          {{ item.expires && item.expires < Date.now()/1000 ? 'Expirada' : 'Activa' }}
        </span>
      </div>
      <div class="link-row">
        <input readonly :value="item.url" class="link-input" @click="$event.target.select()" />
        <button class="btn sm" @click="copyLink">📋</button>
        <a class="btn sm" :href="item.url" target="_blank" rel="noopener" title="Abrir">↗</a>
      </div>
      <div class="link-stats muted small">
        👁 {{ item.views }} visitas · 🖼 {{ item.images.length }} imágenes
      </div>
    </div>

    <!-- Tabs -->
    <div class="tabs">
      <button class="tab" :class="{ on: tab === 'config' }"   @click="tab = 'config'">⚙ Configuración</button>
      <button class="tab" :class="{ on: tab === 'images' }"   @click="tab = 'images'">🖼 Imágenes ({{ item.images.length }})</button>
      <button class="tab" :class="{ on: tab === 'activity' }" @click="tab = 'activity'">📊 Actividad</button>
    </div>

    <!-- Tab Configuración -->
    <div v-if="tab === 'config'" class="card">
      <div class="grid-2">
        <div class="field">
          <label>Nombre del cliente</label>
          <input v-model="item.client_name" @input="markDirty" placeholder="Ej: María García" />
        </div>
        <div class="field">
          <label>Email del cliente</label>
          <input v-model="item.client_email" @input="markDirty" type="email" placeholder="cliente@ejemplo.com" />
        </div>
      </div>

      <div class="field">
        <label>Mensaje (visible al cliente al abrir la galería)</label>
        <textarea v-model="item.message" @input="markDirty" rows="3"
          placeholder="Hola, aquí tienes las fotos de la sesión. Marca tus favoritas con la estrella."></textarea>
      </div>

      <div class="grid-2">
        <div class="field">
          <label>Caducidad</label>
          <input v-model="expiresInput" @change="markDirty" type="date" />
          <p class="muted small" style="margin-top:4px">Vacío = nunca caduca</p>
        </div>
        <div class="field">
          <label>Contraseña</label>
          <input v-if="!clearPassword" v-model="passwordInput" type="password"
            :placeholder="item.has_password ? '(definida — escribe nueva para cambiar)' : 'Sin contraseña'"
            @input="markDirty" />
          <label v-if="item.has_password" style="display:flex;gap:6px;align-items:center;margin-top:6px;font-size:12px">
            <input type="checkbox" v-model="clearPassword" @change="onClearPasswordToggle" style="width:auto" />
            Quitar contraseña
          </label>
        </div>
      </div>

      <div class="field">
        <label>Permisos del cliente</label>
        <div class="checks">
          <label class="check"><input type="checkbox" v-model="item.allow_download" @change="markDirty" /> ⬇ Permitir descarga</label>
          <label class="check"><input type="checkbox" v-model="item.allow_comments" @change="markDirty" /> 💬 Permitir comentarios</label>
        </div>
      </div>

      <div class="row" style="margin-top:12px">
        <button class="btn pri" :disabled="saving || !dirty" @click="save" style="flex:1">
          <Spinner v-if="saving" :size="14" />
          <span v-else>{{ dirty ? 'Guardar cambios' : 'Sin cambios' }}</span>
        </button>
        <button class="btn danger" @click="remove">🗑 Eliminar</button>
      </div>
    </div>

    <!-- Tab Imágenes -->
    <div v-else-if="tab === 'images'" class="card">
      <div class="img-head">
        <span class="card-label">{{ item.images.length }} imágenes</span>
        <div class="row">
          <button class="btn sm" @click="showPicker = true">+ Añadir</button>
          <button class="btn sm" @click="showSyncSheet = true">↻ Importar</button>
          <button v-if="item.images.length" class="btn sm ghost danger" @click="clearAll">Vaciar</button>
        </div>
      </div>
      <p class="muted small">Arrastra para reordenar. El orden se respeta cuando el cliente ve la galería.</p>

      <div v-if="!item.images.length" class="empty muted">
        <p>Aún no hay imágenes en esta galería.</p>
        <button class="btn pri" @click="showPicker = true" style="margin-top:10px">+ Añadir imágenes</button>
      </div>

      <draggable v-else
        v-model="item.images"
        item-key="id"
        class="img-grid"
        :animation="180"
        :force-fallback="true"
        :fallback-tolerance="3"
        :delay="600"
        :delay-on-touch-only="true"
        ghost-class="g-ghost"
        chosen-class="g-chosen"
        @end="onReorder">
        <template #item="{ element: img }">
          <div class="img-card">
            <img :src="img.full || img.url || img.thumb" :alt="img.alt || img.title" loading="lazy" draggable="false" />
            <button class="img-rm" @click="removeImage(img.id)" title="Quitar de galería">✕</button>
          </div>
        </template>
      </draggable>

      <div v-if="item.images.length" class="row" style="margin-top:14px">
        <button class="btn pri" :disabled="saving || !dirty" @click="save" style="flex:1">
          <Spinner v-if="saving" :size="14" />
          <span v-else>{{ dirty ? 'Guardar orden y cambios' : 'Sin cambios' }}</span>
        </button>
      </div>
    </div>

    <!-- Tab Actividad -->
    <div v-else-if="tab === 'activity'" class="card">
      <div v-if="loadingActions" class="center muted"><Spinner /> Cargando actividad…</div>
      <template v-else>
        <div class="act-stats">
          <div class="stat">
            <span class="num">{{ favCount }}</span>
            <span class="lbl">Favoritas</span>
          </div>
          <div class="stat">
            <span class="num">{{ commentCount }}</span>
            <span class="lbl">Comentarios</span>
          </div>
          <div class="stat">
            <span class="num">{{ item.views }}</span>
            <span class="lbl">Visitas</span>
          </div>
        </div>

        <div v-if="favorites.length" class="export-card">
          <div class="export-head">
            <span class="card-label">Exportar selección del cliente</span>
            <span class="muted small">{{ favorites.length }} favoritas</span>
          </div>
          <p class="muted small" style="margin-bottom:10px">
            Lista de nombres de archivo originales — pégalos en Lightroom o Capture One para filtrar las mismas imágenes en tu catálogo.
          </p>
          <div class="export-actions">
            <button class="btn pri sm" @click="exportFavoritesTXT" title="Una línea por archivo">
              📄 Descargar .txt
            </button>
            <button class="btn sm" @click="exportFavoritesCSV" title="Con título y comentarios">
              📊 Descargar .csv
            </button>
            <button class="btn ghost sm" @click="copyFavoritesToClipboard" title="Al portapapeles">
              📋 Copiar lista
            </button>
          </div>
          <details class="export-preview">
            <summary>Ver previsualización ({{ favorites.length }} líneas)</summary>
            <pre>{{ favorites.map(f => f.filename || '(?)').join('\n') }}</pre>
          </details>
        </div>

        <div v-if="!actions.length" class="empty muted">
          <p>Aún no hay actividad del cliente.</p>
          <p class="small">Cuando marque favoritas o comente verás aquí cada acción con fecha y hora.</p>
        </div>

        <div v-else class="act-list">
          <div v-for="a in actions" :key="a.id" class="act-row">
            <img v-if="a.thumb || a.full" :src="a.full || a.url || a.thumb" class="act-thumb" />
            <div class="act-icon" v-else>{{ a.type === 'favorite' ? '★' : '💬' }}</div>
            <div class="act-body">
              <div class="act-line">
                <span class="act-type" :class="a.type">{{ a.type === 'favorite' ? '★ Favorita' : '💬 Comentario' }}</span>
                <span class="act-date muted">{{ new Date(a.date).toLocaleString('es-ES') }}</span>
              </div>
              <p v-if="a.type === 'comment' && a.payload?.text" class="act-text">"{{ a.payload.text }}"</p>
              <button class="act-link" @click="$router.push({ name: 'media-detail', params: { id: a.att_id } })">
                Ver imagen #{{ a.att_id }} →
              </button>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- MediaPicker para añadir -->
    <MediaPicker v-model="showPicker"
      :multiple="true"
      :exclude="imageIds"
      title="Añadir imágenes a la galería"
      @pick="onPickImages" />

    <!-- Bottomsheet de import desde carpeta/portfolio -->
    <transition name="sheet">
      <div v-if="showSyncSheet" class="sheet-overlay" @click.self="showSyncSheet = false">
        <div class="sheet">
          <div class="sheet-handle" />
          <h3>Importar imágenes</h3>

          <div class="field">
            <label>Desde una carpeta</label>
            <div class="row">
              <select v-model.number="syncFolderId" style="flex:1">
                <option :value="0">— Elige carpeta —</option>
                <option v-for="f in folders.flat" :key="f.id" :value="f.id">
                  {{ '— '.repeat(f.depth) }}{{ f.name }} ({{ f.count }})
                </option>
              </select>
              <button class="btn pri" :disabled="!syncFolderId" @click="syncFromFolder">Importar</button>
            </div>
          </div>

          <div class="field" style="margin-top:14px">
            <label>Desde un portfolio</label>
            <div class="row">
              <select v-model.number="syncPortfolioId" style="flex:1">
                <option :value="0">— Elige portfolio —</option>
                <option v-for="p in portfolios.items" :key="p.id" :value="p.id">{{ p.title }}</option>
              </select>
              <button class="btn pri" :disabled="!syncPortfolioId" @click="syncFromPortfolio">Importar</button>
            </div>
          </div>

          <button class="btn ghost" @click="showSyncSheet = false" style="width:100%;margin-top:14px">Cancelar</button>
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.center { display: flex; gap: 10px; justify-content: center; padding: 30px; }
.empty { text-align: center; padding: 30px 16px; }
.small { font-size: 11px; }

.cgd-layout { display: flex; flex-direction: column; gap: 14px; }

/* Header card */
.head-card { display: flex; flex-direction: column; gap: 10px; }
.head-row { display: flex; align-items: center; gap: 10px; }
.title-input {
  flex: 1;
  background: transparent;
  border: 0;
  padding: 4px 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--text);
  border-bottom: 2px solid transparent;
}
.title-input:focus { outline: none; border-bottom-color: var(--accent); }

.status {
  flex-shrink: 0;
  font-size: 10px;
  padding: 3px 10px;
  border-radius: 12px;
  text-transform: uppercase;
  letter-spacing: .3px;
}
.status.active  { background: var(--accent-lo); color: var(--accent); }
.status.expired { background: var(--s3); color: var(--text-mute); }

.link-row { display: flex; gap: 6px; }
.link-input {
  flex: 1;
  background: var(--s2);
  border: 1px solid var(--border);
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 12px;
  font-family: monospace;
  color: var(--text-mute);
}
.btn.sm { min-height: 32px; padding: 4px 10px; font-size: 12px; }
.link-stats { display: flex; gap: 14px; }

/* Tabs */
.tabs {
  display: flex;
  gap: 4px;
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

.grid-2 {
  display: grid;
  grid-template-columns: 1fr;
  gap: 12px;
}
@media (min-width: 768px) { .grid-2 { grid-template-columns: 1fr 1fr; } }

.checks { display: flex; flex-wrap: wrap; gap: 12px; }
.check { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; }
.check input { width: auto; }

/* Imágenes */
.img-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; flex-wrap: wrap; gap: 6px; }
.card-label { font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: var(--text-mute); font-weight: 600; }

.img-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
  gap: 6px;
  margin-top: 10px;
}
@media (min-width: 1280px) { .img-grid { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 8px; } }
@media (min-width: 1800px) { .img-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px; } }

.img-card {
  position: relative;
  aspect-ratio: 1;
  background: var(--s2);
  border-radius: 4px;
  overflow: hidden;
  cursor: grab;
  user-select: none;
  -webkit-user-drag: none;
  touch-action: pan-y;
}
.img-card:active { cursor: grabbing; }
.img-card img { width: 100%; height: 100%; object-fit: cover; pointer-events: none; }

.img-rm {
  position: absolute;
  top: 4px; right: 4px;
  width: 22px; height: 22px;
  border-radius: 50%;
  background: rgba(0,0,0,.65);
  color: white;
  font-size: 11px;
  display: flex; align-items: center; justify-content: center;
  opacity: 0;
  transition: opacity .15s, background .15s;
}
.img-card:hover .img-rm { opacity: 1; }
.img-rm:hover { background: var(--danger); }
@media (hover: none) { .img-rm { opacity: .85; } }

.g-ghost  { opacity: .35; }
.g-chosen { transform: scale(1.05); box-shadow: var(--shadow); }

/* Actividad */
.act-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 14px; }
.stat {
  background: var(--s2);
  border-radius: var(--radius);
  padding: 14px;
  text-align: center;
}
.stat .num { display: block; font-size: 22px; font-weight: 700; color: var(--accent); line-height: 1; }
.stat .lbl { display: block; font-size: 11px; color: var(--text-mute); text-transform: uppercase; letter-spacing: .5px; margin-top: 4px; }

.act-list { display: flex; flex-direction: column; gap: 8px; }
.act-row {
  display: flex; gap: 12px;
  padding: 10px;
  background: var(--s2);
  border-radius: var(--radius);
  align-items: flex-start;
}
.act-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; flex-shrink: 0; }
.act-icon  { width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; background: var(--s3); border-radius: 4px; flex-shrink: 0; font-size: 22px; }
.act-body  { flex: 1; min-width: 0; }
.act-line  { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; }
.act-type {
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 10px;
  font-weight: 500;
}
.act-type.favorite { background: var(--accent-lo); color: var(--accent); }
.act-type.comment  { background: rgba(120, 180, 200, .15); color: var(--info); }
.act-date { font-size: 11px; }
.act-text { font-size: 13px; margin: 6px 0; line-height: 1.4; color: var(--text); }
.act-link { padding: 0; color: var(--accent); font-size: 11px; margin-top: 4px; cursor: pointer; }

.export-card {
  background: var(--s2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 14px;
  margin-bottom: 14px;
}
.export-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
.export-actions { display: flex; flex-wrap: wrap; gap: 8px; }
.export-preview { margin-top: 10px; }
.export-preview summary {
  cursor: pointer;
  font-size: 12px;
  color: var(--text-mute);
  user-select: none;
}
.export-preview summary:hover { color: var(--accent); }
.export-preview pre {
  margin: 8px 0 0;
  padding: 10px 12px;
  background: var(--bg);
  border-radius: 4px;
  font-family: monospace;
  font-size: 11px;
  line-height: 1.5;
  max-height: 240px;
  overflow: auto;
  color: var(--text-mute);
}

/* Sheet importar */
.sheet-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 1350; display: flex; align-items: flex-end; }
.sheet {
  width: 100%;
  max-width: 600px;
  margin: 0 auto;
  background: var(--s1);
  border-top-left-radius: 18px;
  border-top-right-radius: 18px;
  padding: 14px 16px calc(20px + env(safe-area-inset-bottom));
}
.sheet-handle { width: 40px; height: 4px; background: var(--border2); border-radius: 2px; margin: -4px auto 12px; }
.sheet h3 { margin: 0 0 14px; font-size: 14px; text-transform: uppercase; color: var(--text-mute); letter-spacing: .5px; }

.sheet-enter-active, .sheet-leave-active { transition: opacity .25s; }
.sheet-enter-active .sheet, .sheet-leave-active .sheet { transition: transform .25s; }
.sheet-enter-from, .sheet-leave-to { opacity: 0; }
.sheet-enter-from .sheet, .sheet-leave-to .sheet { transform: translateY(100%); }
</style>
