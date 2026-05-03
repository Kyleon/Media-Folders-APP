<script setup>
import { ref, computed, watch } from 'vue';
import { useUiStore } from '../stores/ui';
import { PortfoliosAPI } from '../api/endpoints';
import Spinner from './Spinner.vue';
import MediaPicker from './MediaPicker.vue';

/**
 * Renderiza dinámicamente los meta avanzados del portfolio (campos del tema kotlis).
 * Recibe portfolioId, hace fetch al schema + values, y permite editar y guardar.
 */
const props = defineProps({
  portfolioId: { type: [Number, String], required: true },
  // Layout actual del portfolio. Si cambia, recargamos el schema.
  layoutKey:   { type: String, default: '' },
});
const emit = defineEmits(['saved']);

const ui = useUiStore();

const loading  = ref(true);
const saving   = ref(false);
const schema   = ref([]);
const values   = ref({});
const previews = ref({}); // url precalculada por meta key
const expanded = ref({}); // sección -> bool
const layout   = ref('');
const loadError = ref('');

// Para image picker
const pickingFor = ref(null); // meta key activa para el media picker
const showPicker = ref(false);

async function load() {
  loading.value = true;
  loadError.value = '';
  try {
    const data = await PortfoliosAPI.getMeta(props.portfolioId);
    layout.value = data.layout || '';
    schema.value = data.schema || [];

    // Separar previews y construir un objeto fresco con TODAS las keys del schema
    // (aunque vengan undefined del backend). Esto asegura que v-model tiene siempre algo a lo que apuntar.
    const incoming = { ...(data.values || {}) };
    const p = {};
    Object.keys(incoming).forEach(k => {
      if (k.endsWith('__preview')) {
        p[k.replace('__preview', '')] = incoming[k];
        delete incoming[k];
      }
    });

    const v = {};
    schema.value.forEach(f => {
      if (f.type === 'repeater') {
        v[f.key] = Array.isArray(incoming[f.key]) ? incoming[f.key] : [];
      } else if (f.type === 'image') {
        v[f.key] = incoming[f.key] ?? null;
      } else {
        v[f.key] = (incoming[f.key] !== undefined && incoming[f.key] !== null) ? incoming[f.key] : (f.default ?? '');
      }
    });
    values.value = v;
    previews.value = p;

    // Expandir todas las secciones por defecto la primera vez
    sections.value.forEach(s => { if (!(s in expanded.value)) expanded.value[s] = true; });
  } catch (e) {
    loadError.value = e.message || 'No se pudo cargar la configuración';
    ui.toast(loadError.value, 'err');
  } finally {
    loading.value = false;
  }
}

watch([() => props.portfolioId, () => props.layoutKey], load, { immediate: true });

const sections = computed(() => {
  const seen = [];
  for (const f of schema.value) {
    if (!seen.find(s => s === f.section)) seen.push(f.section);
  }
  return seen;
});

function sectionLabel(sec) {
  const f = schema.value.find(x => x.section === sec && x.section_label);
  return f?.section_label || sec.charAt(0).toUpperCase() + sec.slice(1);
}

function fieldsOf(sec) {
  return schema.value.filter(f => f.section === sec);
}

function toggle(sec) { expanded.value[sec] = !expanded.value[sec]; }

// Toggle helpers (porque las opciones on/off pueden ser yes/no o st1/st2)
function isOn(field) {
  const v = values.value[field.key];
  return v === field.on || (v === '' && field.default === field.on);
}
function setToggle(field, on) {
  values.value[field.key] = on ? field.on : field.off;
}

// Repeater helpers — reasignamos arrays para asegurar reactividad
function addRow(field) {
  const cur = Array.isArray(values.value[field.key]) ? values.value[field.key] : [];
  const row = {};
  field.fields.forEach(sub => { row[sub.key] = sub.default ?? ''; });
  values.value = { ...values.value, [field.key]: [...cur, row] };
}
function removeRow(field, idx) {
  const cur = Array.isArray(values.value[field.key]) ? values.value[field.key] : [];
  values.value = { ...values.value, [field.key]: cur.filter((_, i) => i !== idx) };
}
function moveRow(field, idx, dir) {
  const cur = Array.isArray(values.value[field.key]) ? [...values.value[field.key]] : [];
  const next = idx + dir;
  if (next < 0 || next >= cur.length) return;
  [cur[idx], cur[next]] = [cur[next], cur[idx]];
  values.value = { ...values.value, [field.key]: cur };
}

// Image picker
function openPicker(fieldKey) {
  pickingFor.value = fieldKey;
  showPicker.value = true;
}
function onPickImage(picked) {
  if (!pickingFor.value) return;
  values.value[pickingFor.value] = picked.id;
  previews.value[pickingFor.value] = picked.medium || picked.thumb || '';
  pickingFor.value = null;
}
function clearImage(fieldKey) {
  values.value[fieldKey] = null;
  previews.value[fieldKey] = '';
}

async function save() {
  saving.value = true;
  try {
    const data = await PortfoliosAPI.setMeta(props.portfolioId, values.value);
    schema.value = data.schema || schema.value;
    const v = { ...data.values };
    const p = {};
    Object.keys(v).forEach(k => {
      if (k.endsWith('__preview')) {
        p[k.replace('__preview', '')] = v[k];
        delete v[k];
      }
    });
    values.value = v;
    previews.value = p;
    ui.toast('✓ Configuración guardada', 'ok');
    emit('saved');
  } catch (e) {
    ui.toast(e.message, 'err');
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <div>
    <div v-if="loading" class="center muted"><Spinner /> Cargando…</div>

    <div v-else-if="loadError" class="center danger">⚠ {{ loadError }}</div>

    <div v-else-if="!schema.length" class="empty card">
      <p class="muted small" style="margin:0">
        El layout actual <strong>{{ layout || '(sin layout)' }}</strong> no tiene campos editables mapeados aún.
        Cambia el layout (Column Grid, Carousel, Full Width, Slider) en la sección principal del portfolio
        para activar los campos correspondientes.
      </p>
    </div>

    <template v-else>
      <div v-for="sec in sections" :key="sec" class="section card">
        <button class="sec-head" @click="toggle(sec)">
          <span class="sec-label">{{ sectionLabel(sec) }}</span>
          <span class="sec-arrow" :class="{ open: expanded[sec] }">▶</span>
        </button>

        <div v-show="expanded[sec]" class="sec-body">
          <div v-for="field in fieldsOf(sec)" :key="field.key" class="field-block">

            <!-- Toggle (yes/no, st1/st2, etc) -->
            <label v-if="field.type === 'toggle'" class="row-toggle">
              <span class="toggle-label">{{ field.label }}</span>
              <button type="button" class="toggle-switch" :class="{ on: isOn(field) }" @click="setToggle(field, !isOn(field))">
                <span class="toggle-knob"></span>
              </button>
            </label>

            <!-- Text / URL -->
            <div v-else-if="field.type === 'text' || field.type === 'url'" class="field">
              <label>{{ field.label }}</label>
              <input :type="field.type === 'url' ? 'url' : 'text'"
                v-model="values[field.key]"
                :placeholder="field.placeholder || ''" />
            </div>

            <!-- Textarea -->
            <div v-else-if="field.type === 'textarea'" class="field">
              <label>{{ field.label }}</label>
              <textarea v-model="values[field.key]" :rows="field.rows || 2" :placeholder="field.placeholder || ''"></textarea>
            </div>

            <!-- Select -->
            <div v-else-if="field.type === 'select'" class="field">
              <label>{{ field.label }}</label>
              <select v-model="values[field.key]">
                <option v-for="opt in field.options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
            </div>

            <!-- Image -->
            <div v-else-if="field.type === 'image'" class="field">
              <label>{{ field.label }}</label>
              <div class="image-field">
                <div class="image-preview">
                  <img v-if="previews[field.key]" :src="previews[field.key]" :alt="field.label" />
                  <span v-else class="muted small">Sin imagen</span>
                </div>
                <div class="image-actions">
                  <button type="button" class="btn sm" @click="openPicker(field.key)">
                    {{ values[field.key] ? 'Cambiar' : 'Elegir imagen' }}
                  </button>
                  <button v-if="values[field.key]" type="button" class="btn sm danger" @click="clearImage(field.key)">Quitar</button>
                </div>
              </div>
            </div>

            <!-- Repeater -->
            <div v-else-if="field.type === 'repeater'" class="field">
              <div class="rep-head">
                <label>{{ field.label }}</label>
                <button type="button" class="btn pri sm" @click="addRow(field)">+ {{ field.item_label || 'Item' }}</button>
              </div>

              <div v-if="!values[field.key]?.length" class="muted small empty">Sin elementos. Pulsa "+ {{ field.item_label || 'Item' }}".</div>

              <div v-for="(row, idx) in values[field.key]" :key="idx" class="rep-item">
                <div class="rep-item-head">
                  <span class="muted small">#{{ idx + 1 }}</span>
                  <span class="spacer" />
                  <button type="button" class="rep-act" @click="moveRow(field, idx, -1)" :disabled="idx === 0">↑</button>
                  <button type="button" class="rep-act" @click="moveRow(field, idx,  1)" :disabled="idx === values[field.key].length - 1">↓</button>
                  <button type="button" class="rep-act danger" @click="removeRow(field, idx)">✕</button>
                </div>
                <div v-for="sub in field.fields" :key="sub.key" class="field rep-sub">
                  <label>{{ sub.label }}</label>
                  <input v-if="sub.type === 'text' || sub.type === 'url'"
                    :type="sub.type === 'url' ? 'url' : 'text'"
                    v-model="row[sub.key]"
                    :placeholder="sub.placeholder || ''" />
                  <select v-else-if="sub.type === 'select'" v-model="row[sub.key]">
                    <option v-for="opt in sub.options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                  </select>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>

      <div class="save-row">
        <button class="btn pri" :disabled="saving" @click="save">
          <Spinner v-if="saving" :size="14" />
          <span v-else>💾 Guardar configuración</span>
        </button>
      </div>
    </template>

    <MediaPicker v-model="showPicker"
      :multiple="false"
      title="Elegir imagen"
      @pick="onPickImage" />
  </div>
</template>

<style scoped>
.center { display: flex; gap: 10px; justify-content: center; padding: 30px; }
.center.danger { color: var(--danger); }
.empty { padding: 16px; }

.section { padding: 0; margin-bottom: 10px; overflow: hidden; }
.sec-head {
  display: flex; align-items: center; justify-content: space-between;
  width: 100%;
  padding: 14px 16px;
  background: var(--s2);
  font-size: 13px;
  font-weight: 600;
  color: var(--text);
  text-transform: uppercase;
  letter-spacing: .5px;
}
.sec-head:active { background: var(--s3); }
.sec-label { font-size: 13px; }
.sec-arrow {
  color: var(--text-mute);
  font-size: 11px;
  transition: transform .15s;
}
.sec-arrow.open { transform: rotate(90deg); }

.sec-body { padding: 14px 16px; }

.field-block { margin-bottom: 14px; }
.field-block:last-child { margin-bottom: 0; }

.field { display: flex; flex-direction: column; gap: 4px; }
.field label {
  font-size: 12px;
  color: var(--text-mute);
  font-weight: 500;
}

/* Toggle */
.row-toggle {
  display: flex; align-items: center; gap: 12px;
  cursor: pointer;
  padding: 4px 0;
}
.toggle-label { flex: 1; font-size: 14px; }
.toggle-switch {
  width: 42px; height: 24px;
  background: var(--s3);
  border-radius: 12px;
  border: 1px solid var(--border);
  position: relative;
  transition: background .15s;
  flex: 0 0 auto;
}
.toggle-switch.on { background: var(--accent); border-color: var(--accent); }
.toggle-knob {
  position: absolute;
  top: 2px; left: 2px;
  width: 18px; height: 18px;
  background: var(--text);
  border-radius: 50%;
  transition: transform .15s, background .15s;
}
.toggle-switch.on .toggle-knob { background: #0f0f0f; transform: translateX(18px); }

/* Image field */
.image-field { display: flex; align-items: center; gap: 12px; }
.image-preview {
  width: 96px; height: 64px;
  background: var(--s2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  display: flex; align-items: center; justify-content: center;
  flex: 0 0 96px;
}
.image-preview img { width: 100%; height: 100%; object-fit: cover; display: block; }
.image-actions { display: flex; flex-direction: column; gap: 6px; flex: 1; }

.btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 36px; padding: 0 14px; border-radius: var(--radius); font-size: 13px; background: var(--s2); color: var(--text); border: 1px solid var(--border); }
.btn.sm { min-height: 30px; padding: 0 10px; font-size: 12px; }
.btn.pri { background: var(--accent); color: #0f0f0f; border-color: var(--accent); }
.btn.danger { background: transparent; color: var(--danger); border-color: var(--danger); }
.btn:disabled { opacity: .4; }

/* Repeater */
.rep-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.empty { padding: 10px; text-align: center; }

.rep-item {
  background: var(--s2);
  border-radius: var(--radius);
  padding: 10px;
  margin-bottom: 8px;
  border: 1px solid var(--border);
}
.rep-item-head {
  display: flex; align-items: center; gap: 4px;
  margin-bottom: 6px;
}
.spacer { flex: 1; }
.rep-act {
  width: 26px; height: 26px;
  background: transparent;
  color: var(--text-mute);
  border-radius: var(--radius);
  font-size: 12px;
}
.rep-act:active:not(:disabled) { background: var(--s3); }
.rep-act:disabled { opacity: .35; }
.rep-act.danger { color: var(--danger); }

.rep-sub { margin-bottom: 8px; }
.rep-sub:last-child { margin-bottom: 0; }

.save-row {
  display: flex; justify-content: flex-end;
  margin-top: 14px;
}
.save-row .btn { min-width: 200px; }
.small { font-size: 11px; }
</style>
