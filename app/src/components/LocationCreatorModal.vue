<script setup>
import { ref, watch, nextTick, onBeforeUnmount } from 'vue';
import L from 'leaflet';
import { GeoAPI, MapAPI } from '../api/endpoints';
import { useUiStore } from '../stores/ui';
import Spinner from './Spinner.vue';

/**
 * Sheet con mini-mapa para crear una ubicación nueva sobre la marcha.
 * Pensado para abrirse desde otra pantalla (p. ej. PortfolioDetail) y
 * devolver al padre la ubicación creada vía evento `created`.
 *
 * Props:
 *  - modelValue: boolean (v-model)
 *  - title:      texto del header
 *
 * Events:
 *  - update:modelValue
 *  - created (location)  → la ubicación creada tal y como la devuelve la API,
 *                          con id, name, tag, lat, lng
 */
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title:      { type: String,  default: 'Crear ubicación en el mapa' },
});
const emit = defineEmits(['update:modelValue', 'created']);

const ui = useUiStore();

const mapEl   = ref(null);
const map     = ref(null);
const marker  = ref(null);

const placeQ        = ref('');
const placeResults  = ref([]);
const saving        = ref(false);

const form = ref(makeEmpty());
function makeEmpty() {
  return { name: '', tag: '', lat: null, lng: null };
}

function close() { emit('update:modelValue', false); }

watch(() => props.modelValue, async (v) => {
  if (v) {
    form.value = makeEmpty();
    placeQ.value = '';
    placeResults.value = [];
    await nextTick();
    initMap();
  } else {
    destroyMap();
  }
});

onBeforeUnmount(destroyMap);

function destroyMap() {
  if (marker.value) { marker.value.remove(); marker.value = null; }
  if (map.value) { map.value.remove(); map.value = null; }
}

function initMap() {
  if (map.value || !mapEl.value) return;
  map.value = L.map(mapEl.value, {
    center: [42.5987, -5.5703],
    zoom: 4,
    zoomControl: true,
  });
  L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
    attribution: '© OSM © CARTO',
    subdomains: 'abcd', maxZoom: 20,
  }).addTo(map.value);

  map.value.on('click', (e) => {
    placePin(e.latlng.lat, e.latlng.lng);
    reverseGeocode(e.latlng.lat, e.latlng.lng);
  });

  // El sheet anima entrada; Leaflet a veces calcula tiles antes de que el
  // contenedor termine de animarse → invalidateSize a los 350ms.
  setTimeout(() => map.value && map.value.invalidateSize(), 380);
}

function placePin(lat, lng) {
  form.value.lat = lat;
  form.value.lng = lng;
  if (marker.value) marker.value.remove();
  marker.value = L.marker([lat, lng], {
    icon: L.divIcon({
      className: '',
      html: '<div style="width:18px;height:18px;border-radius:50%;background:#fff;border:3px solid #c8a97e"></div>',
      iconSize: [18, 18], iconAnchor: [9, 9],
    }),
    draggable: true,
  }).addTo(map.value);
  marker.value.on('dragend', () => {
    const p = marker.value.getLatLng();
    form.value.lat = p.lat; form.value.lng = p.lng;
    reverseGeocode(p.lat, p.lng);
  });
}

let searchDebounce;
function onPlaceInput() {
  clearTimeout(searchDebounce);
  if (placeQ.value.length < 3) { placeResults.value = []; return; }
  searchDebounce = setTimeout(async () => {
    try { placeResults.value = await GeoAPI.search(placeQ.value); }
    catch { placeResults.value = []; }
  }, 350);
}

function pickPlace(r) {
  const lat = parseFloat(r.lat), lng = parseFloat(r.lon);
  placePin(lat, lng);
  if (map.value) map.value.flyTo([lat, lng], 10, { duration: .6 });
  placeQ.value = r.display_name.split(',').slice(0, 3).join(',');
  placeResults.value = [];
  if (!form.value.name) form.value.name = placeQ.value;
  reverseGeocode(lat, lng);
}

async function reverseGeocode(lat, lng) {
  try {
    const r = await GeoAPI.reverse(lat, lng);
    if (r.address) {
      const a = r.address;
      const tag = [a.city || a.town || a.village, a.country].filter(Boolean).join(', ');
      if (!form.value.name && tag) form.value.name = tag;
      if (!form.value.tag  && tag) form.value.tag  = tag;
    }
  } catch { /* silencioso */ }
}

async function save() {
  if (!form.value.name.trim()) { ui.toast('El nombre es obligatorio', 'err'); return; }
  if (!form.value.lat || !form.value.lng) { ui.toast('Coloca un pin en el mapa', 'err'); return; }
  saving.value = true;
  try {
    const r = await MapAPI.create({
      name: form.value.name.trim(),
      tag:  form.value.tag.trim(),
      lat:  form.value.lat,
      lng:  form.value.lng,
    });
    // La API devuelve { id }. Componemos un objeto location coherente para el padre.
    const loc = {
      id: r.id,
      name: form.value.name.trim(),
      tag:  form.value.tag.trim(),
      lat:  form.value.lat,
      lng:  form.value.lng,
      folder_ids: [], photo_ids: [], portfolio_ids: [],
    };
    ui.toast('✓ Ubicación creada', 'ok');
    emit('created', loc);
    close();
  } catch (e) {
    ui.toast(e.message, 'err');
  } finally {
    saving.value = false;
  }
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

        <div class="body">
          <div class="field">
            <label>Buscar lugar</label>
            <input v-model="placeQ" @input="onPlaceInput" placeholder="Ciudad, monumento…" />
            <div v-if="placeResults.length" class="search-results">
              <button v-for="r in placeResults" :key="r.place_id" class="sr" @click="pickPlace(r)">
                {{ r.display_name.split(',').slice(0,3).join(',') }}
              </button>
            </div>
          </div>

          <div ref="mapEl" class="mini-map" />

          <p v-if="form.lat && form.lng" class="muted small coords">
            📍 {{ form.lat.toFixed(5) }}, {{ form.lng.toFixed(5) }}
          </p>
          <p v-else class="muted small coords">
            Busca un lugar o toca el mapa para colocar el pin
          </p>

          <div class="field">
            <label>Nombre *</label>
            <input v-model="form.name" placeholder="Ej: Plaza Mayor de León" />
          </div>
          <div class="field">
            <label>Etiqueta <span class="muted small">(opcional)</span></label>
            <input v-model="form.tag" placeholder="Paisaje, viajes…" />
          </div>
        </div>

        <div class="footer">
          <button class="btn" @click="close">Cancelar</button>
          <button class="btn pri" :disabled="saving" @click="save">
            <Spinner v-if="saving" :size="14" />
            <span v-else>💾 Crear ubicación</span>
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<style scoped>
.sheet-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.5);
  z-index: 1400;
  display: flex; align-items: flex-end;
}
.sheet {
  width: 100%;
  height: 92vh;
  max-height: 92vh;
  background: var(--s1);
  border-top-left-radius: 18px;
  border-top-right-radius: 18px;
  padding: 14px 16px calc(20px + env(safe-area-inset-bottom));
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.sheet-handle { width: 40px; height: 4px; background: var(--border2); border-radius: 2px; margin: -4px auto 12px; }
.sheet-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex: 0 0 auto; }
.sheet-head h3 { margin: 0; font-size: 16px; }
.close-btn { font-size: 22px; color: var(--text-mute); padding: 4px 8px; }

.body {
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  display: flex; flex-direction: column; gap: 10px;
}

.mini-map {
  width: 100%;
  height: 38vh;
  min-height: 220px;
  background: var(--s2);
  border-radius: var(--radius);
  overflow: hidden;
}

.search-results {
  background: var(--s2); border: 1px solid var(--border);
  border-radius: var(--radius);
  margin-top: 4px;
  max-height: 180px; overflow-y: auto;
}
.sr {
  display: block; width: 100%; text-align: left;
  padding: 10px 12px;
  font-size: 13px;
  color: var(--text);
  border-bottom: 1px solid var(--border);
}
.sr:last-child { border-bottom: 0; }
.sr:active { background: var(--s3); }

.field { display: flex; flex-direction: column; gap: 4px; }
.field label { font-size: 12px; color: var(--text-mute); text-transform: uppercase; letter-spacing: .5px; }
.coords { margin: 0; }
.small { font-size: 11px; }

.footer {
  flex: 0 0 auto;
  display: flex; gap: 10px;
  margin-top: 10px;
  padding-top: 10px;
  border-top: 1px solid var(--border);
  background: var(--s1);
}
.footer .btn { flex: 1; }

.btn {
  display: inline-flex; align-items: center; justify-content: center;
  gap: 8px; min-height: 38px; padding: 0 16px;
  border-radius: var(--radius); font-size: 14px; font-weight: 500;
  background: var(--s2); color: var(--text); border: 1px solid var(--border);
}
.btn.pri { background: var(--accent); color: #0f0f0f; border-color: var(--accent); }
.btn:disabled { opacity: .4; }

.sheet-enter-active, .sheet-leave-active { transition: opacity .25s; }
.sheet-enter-active .sheet, .sheet-leave-active .sheet { transition: transform .25s; }
.sheet-enter-from, .sheet-leave-to { opacity: 0; }
.sheet-enter-from .sheet, .sheet-leave-to .sheet { transform: translateY(100%); }
</style>
