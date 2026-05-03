<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { useFoldersStore } from '../stores/folders';
import { useUiStore } from '../stores/ui';
import { MediaAPI } from '../api/endpoints';
import { extractPalette } from '../utils/palette';
import Spinner from '../components/Spinner.vue';

const folders = useFoldersStore();
const ui      = useUiStore();

const folderId = ref(0);
const queue    = ref([]); // [{ file, status, progress, error, result, previewUrl }]
const running  = ref(false);
const fileInput   = ref(null);
const cameraInput = ref(null);

onMounted(() => folders.load());

// Liberar object URLs al desmontar para evitar fugas de memoria
onBeforeUnmount(() => {
  queue.value.forEach(it => { if (it.previewUrl) URL.revokeObjectURL(it.previewUrl); });
});

function pickFiles()  { fileInput.value?.click(); }
function pickCamera() { cameraInput.value?.click(); }

function isImage(f) { return (f.type || '').startsWith('image/'); }
function isVideo(f) { return (f.type || '').startsWith('video/'); }

function onFiles(e) {
  const files = Array.from(e.target.files || []);
  files.forEach(f => {
    queue.value.push({
      file: f,
      status: 'pending',
      progress: 0,
      error: null,
      result: null,
      previewUrl: isImage(f) ? URL.createObjectURL(f) : null,
    });
  });
  e.target.value = ''; // reset
}

async function runQueue() {
  if (running.value) return;
  running.value = true;
  for (const item of queue.value) {
    if (item.status !== 'pending') continue;
    item.status = 'uploading';
    try {
      const res = await MediaAPI.upload(item.file, folderId.value || null);
      item.status = 'ok';
      item.result = res;
      // Extraer paleta dominante en cliente y guardarla en el server (best effort).
      // No bloquea la subida en caso de fallo.
      if (item.file.type?.startsWith('image/')) {
        try {
          const palette = await extractPalette(item.file, 5);
          if (palette.length) await MediaAPI.setPalette(res.id, palette);
        } catch { /* silencioso */ }
      }
    } catch (e) {
      item.status = 'err';
      item.error  = e.message;
    }
  }
  running.value = false;
  await folders.load(true);
  ui.toast('✓ Subida finalizada', 'ok');
}

function removeQueued(idx) {
  const it = queue.value[idx];
  if (it?.previewUrl) URL.revokeObjectURL(it.previewUrl);
  queue.value.splice(idx, 1);
}

function clearDone() {
  const ok = queue.value.filter(i => i.status === 'ok');
  ok.forEach(it => { if (it.previewUrl) URL.revokeObjectURL(it.previewUrl); });
  queue.value = queue.value.filter(i => i.status !== 'ok');
}

function clearAll() {
  queue.value.forEach(it => { if (it.previewUrl) URL.revokeObjectURL(it.previewUrl); });
  queue.value = [];
}

const okCount  = computed(() => queue.value.filter(i => i.status === 'ok').length);
const errCount = computed(() => queue.value.filter(i => i.status === 'err').length);
const total    = computed(() => queue.value.length);
const pendingCount = computed(() => queue.value.filter(i => i.status === 'pending').length);
const totalSize    = computed(() => queue.value.reduce((acc, it) => acc + it.file.size, 0));

function fmtSize(b) {
  if (!b) return '';
  if (b < 1024) return b + ' B';
  if (b < 1024*1024) return (b / 1024).toFixed(1) + ' KB';
  return (b / 1024 / 1024).toFixed(1) + ' MB';
}

function fileIcon(f) {
  if (isImage(f)) return '🖼';
  if (isVideo(f)) return '🎬';
  if ((f.type || '').startsWith('audio/')) return '🔊';
  if (f.type === 'application/pdf') return '📄';
  return '📎';
}
</script>

<template>
  <div>
    <div class="card">
      <div class="field">
        <label>Carpeta destino</label>
        <select v-model.number="folderId">
          <option :value="0">— Sin carpeta —</option>
          <option v-for="f in folders.flat" :key="f.id" :value="f.id">
            {{ '— '.repeat(f.depth) }}{{ f.name }}
          </option>
        </select>
      </div>

      <div class="actions">
        <button class="btn pri" @click="pickCamera">📷 Hacer foto</button>
        <button class="btn"     @click="pickFiles">🖼 Galería</button>
      </div>

      <input ref="fileInput"   type="file" accept="image/*,video/*" multiple hidden @change="onFiles" />
      <input ref="cameraInput" type="file" accept="image/*" capture="environment" hidden @change="onFiles" />
    </div>

    <div v-if="queue.length" class="queue card">
      <div class="queue-head">
        <h3>Cola ({{ total }})</h3>
        <span class="muted small">
          {{ fmtSize(totalSize) }} total
          <template v-if="okCount || errCount"> · {{ okCount }} ok · {{ errCount }} err</template>
        </span>
      </div>

      <div class="qgrid">
        <div v-for="(it, idx) in queue" :key="idx" class="qcard" :class="'st-' + it.status">
          <div class="qthumb">
            <img v-if="it.previewUrl" :src="it.previewUrl" :alt="it.file.name" />
            <div v-else class="qthumb-icon">{{ fileIcon(it.file) }}</div>

            <!-- Overlay de estado -->
            <div class="qstate" :class="it.status">
              <span v-if="it.status === 'pending'">⏳</span>
              <Spinner v-else-if="it.status === 'uploading'" :size="14" />
              <span v-else-if="it.status === 'ok'">✓</span>
              <span v-else>✕</span>
            </div>

            <button v-if="it.status !== 'uploading'" class="qrm" @click.stop="removeQueued(idx)">✕</button>
          </div>
          <div class="qname" :title="it.file.name">{{ it.file.name }}</div>
          <div class="qmeta">
            <span class="muted">{{ fmtSize(it.file.size) }}</span>
          </div>
          <div v-if="it.error" class="qerror danger small" :title="it.error">{{ it.error }}</div>
        </div>
      </div>

      <div class="row" style="margin-top:12px">
        <button class="btn pri" :disabled="running || !pendingCount" @click="runQueue" style="flex:1">
          <Spinner v-if="running" :size="14" />
          <span v-else>↑ Subir {{ pendingCount }} archivos</span>
        </button>
        <button class="btn ghost" v-if="okCount" @click="clearDone">Limpiar OK</button>
        <button class="btn ghost danger" v-if="!running && total > 0" @click="clearAll">Vaciar</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.actions { display: flex; gap: 8px; margin-top: 8px; }
.actions .btn { flex: 1; }

.queue { margin-top: 16px; }
.queue-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.queue-head h3 { margin: 0; font-size: 13px; text-transform: uppercase; color: var(--text-mute); letter-spacing: .5px; }
.small { font-size: 11px; }

.qgrid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
  gap: 8px;
}
.qcard {
  display: flex; flex-direction: column;
  background: var(--s1);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
}
.qcard.st-uploading { border-color: var(--info); }
.qcard.st-ok        { border-color: var(--ok); }
.qcard.st-err       { border-color: var(--danger); }

.qthumb {
  position: relative;
  aspect-ratio: 1;
  background: var(--s2);
  display: flex; align-items: center; justify-content: center;
}
.qthumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.qthumb-icon { font-size: 36px; }

.qstate {
  position: absolute; bottom: 4px; left: 4px;
  background: rgba(0,0,0,.65);
  width: 22px; height: 22px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  color: white;
  font-size: 13px;
}
.qstate.ok  { background: var(--ok); }
.qstate.err { background: var(--danger); }

.qrm {
  position: absolute; top: 4px; right: 4px;
  width: 22px; height: 22px;
  border-radius: 50%;
  background: rgba(0,0,0,.65);
  color: white;
  font-size: 11px;
}

.qname {
  padding: 6px 8px 0;
  font-size: 11px;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.qmeta { padding: 0 8px 6px; display: flex; justify-content: space-between; font-size: 10px; }
.qerror {
  padding: 0 8px 6px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
