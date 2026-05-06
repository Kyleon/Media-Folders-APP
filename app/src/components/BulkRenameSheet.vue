<script setup>
import { ref, computed, watch } from 'vue';
import { MediaAPI } from '../api/endpoints';
import { useUiStore } from '../stores/ui';
import Spinner from './Spinner.vue';

/**
 * Editor por lotes de títulos de imágenes. Bottomsheet con tabs por operación,
 * preview en tiempo real (cliente) + verificación servidor antes de aplicar.
 */
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  ids:        { type: Array,   default: () => [] },
  // items para preview local rápido (sin pedir al servidor en cada cambio).
  // Cada item: { id, title, alt?, filename? }
  items:      { type: Array,   default: () => [] },
});
const emit = defineEmits(['update:modelValue', 'applied']);
const ui = useUiStore();

const operation = ref('replace');
const params = ref({
  // replace
  find: '', replace: '', regex: false, case_sensitive: false,
  // prefix / suffix
  value: '',
  // sequence
  pattern: 'Foto #{n}', start: 1, padding: 3, order: 'date_asc',
  // from_filename
  strip_ext: true, separator_to_space: true,
  // case
  mode: 'title',
});

const applying = ref(false);
const serverPreview = ref(null);   // resultado del endpoint /preview
const loadingPreview = ref(false);

function close() { emit('update:modelValue', false); }

watch(() => props.modelValue, (v) => {
  if (v) {
    serverPreview.value = null;
    operation.value = 'replace';
  }
});

// Preview local rápido — recalcula cada vez que cambia params/operation
const localPreview = computed(() => {
  const list = props.items.slice();
  const out = [];
  let i = 0;
  for (const it of list) {
    const old = it.title || '';
    const next = applyOpLocal(old, it, operation.value, params.value, i);
    out.push({ id: it.id, old, new: next, changed: next !== old });
    i++;
  }
  return out;
});

const localChangedCount = computed(() => localPreview.value.filter(x => x.changed).length);

function applyOpLocal(old, item, op, p, idx) {
  switch (op) {
    case 'replace': {
      if (!p.find) return old;
      if (p.regex) {
        try {
          const flags = (p.case_sensitive ? '' : 'i') + 'gu';
          return old.replace(new RegExp(p.find, flags), p.replace || '');
        } catch { return old; }
      }
      if (p.case_sensitive) return old.split(p.find).join(p.replace || '');
      return old.replace(new RegExp(escapeRe(p.find), 'gi'), p.replace || '');
    }
    case 'prefix': return (p.value || '') + old;
    case 'suffix': return old + (p.value || '');
    case 'sequence': {
      const n = idx + (parseInt(p.start, 10) || 1);
      const padded = p.padding > 0 ? String(n).padStart(p.padding, '0') : String(n);
      return (p.pattern || '{n}').replace(/\{n\}/g, padded);
    }
    case 'from_filename': {
      let name = item.filename || '';
      if (!name && item.url) name = item.url.split('/').pop() || '';
      if (p.strip_ext) {
        const dot = name.lastIndexOf('.');
        if (dot > 0) name = name.slice(0, dot);
      }
      if (p.separator_to_space) {
        name = name.replace(/[_-]+/g, ' ').replace(/\s+/g, ' ').trim();
      }
      return name;
    }
    case 'from_alt': return item.alt || old;
    case 'case': {
      switch (p.mode) {
        case 'lower': return old.toLowerCase();
        case 'upper': return old.toUpperCase();
        case 'sentence': {
          const s = old.toLowerCase();
          return s.charAt(0).toUpperCase() + s.slice(1);
        }
        case 'title':
        default:
          return old.toLowerCase().replace(/(?:^|\s)\S/g, c => c.toUpperCase());
      }
    }
    case 'trim': return old.replace(/\s+/g, ' ').trim();
    default: return old;
  }
}
function escapeRe(s) { return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }

async function refreshServerPreview() {
  loadingPreview.value = true;
  try {
    serverPreview.value = await MediaAPI.bulkRenamePreview(
      props.ids, operation.value, normalizedParams()
    );
  } catch (e) {
    ui.toast(e.message || 'Error', 'err');
  } finally { loadingPreview.value = false; }
}

function normalizedParams() {
  // Solo enviamos los params relevantes para la op elegida (limpio)
  const p = params.value;
  switch (operation.value) {
    case 'replace':
      return { find: p.find, replace: p.replace, regex: !!p.regex, case_sensitive: !!p.case_sensitive };
    case 'prefix':
    case 'suffix':
      return { value: p.value };
    case 'sequence':
      return { pattern: p.pattern, start: parseInt(p.start, 10) || 1, padding: parseInt(p.padding, 10) || 0, order: p.order };
    case 'from_filename':
      return { strip_ext: !!p.strip_ext, separator_to_space: !!p.separator_to_space };
    case 'case':
      return { mode: p.mode };
    case 'from_alt':
    case 'trim':
    default:
      return {};
  }
}

async function apply() {
  const changes = serverPreview.value?.changes || localPreview.value.filter(x => x.changed);
  if (!changes.length) { ui.toast('Nada que cambiar con esta configuración', 'err'); return; }
  if (!confirm(`Aplicar cambios a ${changes.length} título${changes.length === 1 ? '' : 's'}? Esta acción no se puede deshacer.`)) return;

  applying.value = true;
  try {
    const r = await MediaAPI.bulkRename(props.ids, operation.value, normalizedParams());
    ui.toast(`✓ ${r.updated} título${r.updated === 1 ? '' : 's'} actualizado${r.updated === 1 ? '' : 's'}`, 'ok');
    emit('applied', r);
    close();
  } catch (e) {
    ui.toast(e.message || 'Error al aplicar', 'err');
  } finally { applying.value = false; }
}

const ops = [
  { id: 'replace',       label: 'Buscar y reemplazar', icon: '🔁' },
  { id: 'prefix',        label: 'Añadir prefijo',      icon: '◀' },
  { id: 'suffix',        label: 'Añadir sufijo',       icon: '▶' },
  { id: 'sequence',      label: 'Numerar',             icon: '#' },
  { id: 'from_filename', label: 'Desde nombre',        icon: '📄' },
  { id: 'from_alt',      label: 'Desde alt',           icon: '🤖' },
  { id: 'case',          label: 'Mayúsculas',          icon: 'Aa' },
  { id: 'trim',          label: 'Limpiar espacios',    icon: '⌫' },
];
</script>

<template>
  <transition name="sheet">
    <div v-if="modelValue" class="sheet-overlay" @click.self="close">
      <div class="sheet">
        <div class="sheet-handle" />
        <div class="sheet-head">
          <h3>Editar títulos en lote ({{ ids.length }})</h3>
          <button class="close-btn" @click="close">✕</button>
        </div>

        <!-- Tabs de operaciones -->
        <div class="op-tabs">
          <button v-for="op in ops" :key="op.id"
            class="op-tab" :class="{ on: operation === op.id }"
            @click="operation = op.id">
            <span class="op-icon">{{ op.icon }}</span>
            <span class="op-label">{{ op.label }}</span>
          </button>
        </div>

        <!-- Parámetros por operación -->
        <div class="op-params">
          <!-- replace -->
          <template v-if="operation === 'replace'">
            <div class="row">
              <div class="field" style="flex:1">
                <label>Buscar</label>
                <input v-model="params.find" placeholder="IMG_" />
              </div>
              <div class="field" style="flex:1">
                <label>Reemplazar por</label>
                <input v-model="params.replace" placeholder="Atardecer Cádiz" />
              </div>
            </div>
            <div class="checks">
              <label class="check"><input type="checkbox" v-model="params.regex" /> Expresión regular</label>
              <label class="check"><input type="checkbox" v-model="params.case_sensitive" /> Distinguir mayúsculas</label>
            </div>
          </template>

          <!-- prefix / suffix -->
          <template v-else-if="operation === 'prefix' || operation === 'suffix'">
            <div class="field">
              <label>Texto a {{ operation === 'prefix' ? 'añadir al inicio' : 'añadir al final' }}</label>
              <input v-model="params.value" :placeholder="operation === 'prefix' ? 'Boda María — ' : ' (© Yezrael)'" />
            </div>
          </template>

          <!-- sequence -->
          <template v-else-if="operation === 'sequence'">
            <div class="field">
              <label>Patrón ({{ '{n}' }} se reemplaza por el número)</label>
              <input v-model="params.pattern" placeholder="Foto #{n}" />
            </div>
            <div class="row">
              <div class="field" style="flex:1">
                <label>Empezar en</label>
                <input type="number" v-model.number="params.start" min="0" />
              </div>
              <div class="field" style="flex:1">
                <label>Relleno (ceros a la izquierda)</label>
                <input type="number" v-model.number="params.padding" min="0" max="6" />
              </div>
              <div class="field" style="flex:2">
                <label>Orden</label>
                <select v-model="params.order">
                  <option value="">Selección actual</option>
                  <option value="date_asc">Fecha (más antigua primero)</option>
                  <option value="date_desc">Fecha (más nueva primero)</option>
                  <option value="title_asc">Título A-Z</option>
                  <option value="title_desc">Título Z-A</option>
                </select>
              </div>
            </div>
          </template>

          <!-- from_filename -->
          <template v-else-if="operation === 'from_filename'">
            <p class="muted small">Reemplaza el título con el nombre del archivo en disco.</p>
            <div class="checks">
              <label class="check"><input type="checkbox" v-model="params.strip_ext" /> Quitar extensión (.JPG, .PNG…)</label>
              <label class="check"><input type="checkbox" v-model="params.separator_to_space" /> Convertir _ y - en espacios</label>
            </div>
          </template>

          <!-- from_alt -->
          <template v-else-if="operation === 'from_alt'">
            <p class="muted small">
              Usa el alt text como título. Si una imagen no tiene alt, mantiene el título actual.
              Útil tras generar metadatos con IA.
            </p>
          </template>

          <!-- case -->
          <template v-else-if="operation === 'case'">
            <div class="field">
              <label>Convertir mayúsculas/minúsculas</label>
              <div class="case-options">
                <label v-for="m in [
                  { id: 'title',    label: 'Cada Palabra En Mayúscula' },
                  { id: 'sentence', label: 'Solo la primera letra' },
                  { id: 'lower',    label: 'todo en minúsculas' },
                  { id: 'upper',    label: 'TODO EN MAYÚSCULAS' },
                ]" :key="m.id" class="case-option" :class="{ on: params.mode === m.id }">
                  <input type="radio" v-model="params.mode" :value="m.id" />
                  <span>{{ m.label }}</span>
                </label>
              </div>
            </div>
          </template>

          <!-- trim -->
          <template v-else-if="operation === 'trim'">
            <p class="muted small">Quita espacios redundantes y los del principio/final del título.</p>
          </template>
        </div>

        <!-- Preview -->
        <div class="preview-card">
          <div class="preview-head">
            <span class="card-label">
              Previsualización ({{ localChangedCount }} de {{ localPreview.length }} cambian)
            </span>
            <button class="btn sm ghost" :disabled="loadingPreview" @click="refreshServerPreview">
              <Spinner v-if="loadingPreview" :size="12" />
              <span v-else>↻ Verificar en servidor</span>
            </button>
          </div>
          <div class="preview-list">
            <div v-for="(p, idx) in localPreview" :key="p.id" class="preview-row" :class="{ changed: p.changed, 'no-change': !p.changed }">
              <span class="row-idx">{{ idx + 1 }}</span>
              <span class="row-old" :title="p.old">{{ p.old || '(sin título)' }}</span>
              <span class="row-arrow">→</span>
              <span class="row-new" :title="p.new">{{ p.new || '(vacío)' }}</span>
            </div>
            <p v-if="!localPreview.length" class="muted small" style="text-align:center;padding:14px">
              Sin imágenes seleccionadas para previsualizar.
            </p>
          </div>
        </div>

        <!-- Acciones -->
        <div class="row" style="margin-top:14px">
          <button class="btn pri" :disabled="applying || !localChangedCount" @click="apply" style="flex:1">
            <Spinner v-if="applying" :size="14" />
            <span v-else>✓ Aplicar a {{ localChangedCount }} título{{ localChangedCount === 1 ? '' : 's' }}</span>
          </button>
          <button class="btn ghost" :disabled="applying" @click="close">Cancelar</button>
        </div>
      </div>
    </div>
  </transition>
</template>

<style scoped>
.sheet-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.5);
  z-index: 1350;
  display: flex; align-items: flex-end;
}
.sheet {
  width: 100%; max-width: 720px; margin: 0 auto;
  background: var(--s1);
  border-top-left-radius: 18px; border-top-right-radius: 18px;
  padding: 14px 16px calc(20px + env(safe-area-inset-bottom));
  max-height: 90vh; overflow-y: auto;
}
.sheet-handle { width: 40px; height: 4px; background: var(--border2); border-radius: 2px; margin: -4px auto 12px; }
.sheet-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
.sheet-head h3 { margin: 0; font-size: 14px; text-transform: uppercase; color: var(--text-mute); letter-spacing: .5px; }
.close-btn { font-size: 22px; color: var(--text-mute); padding: 4px 8px; }

.op-tabs {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 4px;
  background: var(--s2);
  padding: 4px;
  border-radius: var(--radius);
  margin-bottom: 14px;
}
.op-tab {
  display: flex; align-items: center; gap: 6px;
  padding: 8px 10px;
  background: transparent;
  border: 0;
  color: var(--text-mute);
  font-size: 12px;
  cursor: pointer;
  border-radius: 6px;
  text-align: left;
}
.op-tab:hover { color: var(--text); }
.op-tab.on { background: var(--s1); color: var(--text); box-shadow: 0 1px 2px rgba(0,0,0,.2); }
.op-icon { font-size: 14px; flex-shrink: 0; }
.op-label { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.op-params { background: var(--s2); padding: 12px; border-radius: var(--radius); margin-bottom: 14px; }
.checks { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 10px; }
.check { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; }
.check input { width: auto; }

.case-options { display: flex; flex-direction: column; gap: 6px; }
.case-option {
  display: flex; gap: 10px; align-items: center;
  padding: 8px 12px;
  background: var(--s1);
  border-radius: var(--radius);
  font-size: 13px;
  cursor: pointer;
  border: 1px solid var(--border);
}
.case-option.on { border-color: var(--accent); background: var(--accent-lo); }
.case-option input { width: auto; }

/* Preview */
.preview-card {
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 10px;
}
.preview-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 6px; }
.card-label { font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: var(--text-mute); font-weight: 600; }
.btn.sm { min-height: 30px; padding: 4px 10px; font-size: 11px; }

.preview-list { max-height: 280px; overflow-y: auto; display: flex; flex-direction: column; gap: 2px; }
.preview-row {
  display: grid;
  grid-template-columns: 28px 1fr 16px 1fr;
  gap: 8px;
  padding: 6px 8px;
  font-size: 12px;
  border-radius: 4px;
  align-items: center;
}
.preview-row.no-change { opacity: 0.4; }
.preview-row.changed { background: rgba(200, 169, 126, 0.08); }
.row-idx { color: var(--text-mute); font-variant-numeric: tabular-nums; font-size: 10px; }
.row-old { color: var(--text-mute); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.row-arrow { color: var(--text-mute); text-align: center; }
.row-new { color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 500; }
.preview-row.changed .row-new { color: var(--accent); }

.sheet-enter-active, .sheet-leave-active { transition: opacity .25s; }
.sheet-enter-active .sheet, .sheet-leave-active .sheet { transition: transform .25s; }
.sheet-enter-from, .sheet-leave-to { opacity: 0; }
.sheet-enter-from .sheet, .sheet-leave-to .sheet { transform: translateY(100%); }
</style>
