<script setup>
import { ref, watch, onBeforeUnmount, nextTick } from 'vue';
import L from 'leaflet';
import { GeoAPI } from '../api/endpoints';
import { useFocusTrap } from '../composables/useFocusTrap';

/**
 * Bottomsheet con mapa para asignar lat/lng a una o varias imágenes.
 * Props:
 *   modelValue   bool   v-model (visible)
 *   initialLat   number? coordenada inicial (si la imagen ya tiene)
 *   initialLng   number?
 *   initialPlace string?
 *   subtitle     string? texto pequeño bajo el título (ej: "X imágenes seleccionadas")
 *   title        string  título del sheet
 *   allowClear   bool    mostrar botón "Quitar ubicación"
 *
 * Emits:
 *   pick({ lat, lng, place })
 *   clear()                 — para borrar la geo
 */
const props = defineProps({
  modelValue:   { type: Boolean, default: false },
  initialLat:   { type: [Number, null], default: null },
  initialLng:   { type: [Number, null], default: null },
  initialPlace: { type: String,  default: '' },
  subtitle:     { type: String,  default: '' },
  title:        { type: String,  default: 'Ubicación' },
  allowClear:   { type: Boolean, default: true },
});
const emit = defineEmits(['update:modelValue', 'pick', 'clear']);

const mapEl = ref(null);
const map   = ref(null);
const marker = ref(null);
const lat = ref(null);
const lng = ref(null);
const place = ref('');

const placeQ = ref('');
const placeResults = ref([]);
const searching   = ref(false);
const searchError = ref('');
const geoStatus = ref('');

let mapInited = false;

function close() {
  emit('update:modelValue', false);
}

const sheetEl = ref(null);
useFocusTrap(sheetEl, () => props.modelValue, close);

function initMap() {
  if (mapInited || !mapEl.value) return;
  mapInited = true;

  const center = (lat.value && lng.value) ? [lat.value, lng.value] : [42.5987, -5.5703];
  const zoom   = (lat.value && lng.value) ? 12 : 4;

  map.value = L.map(mapEl.value, { center, zoom });
  L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
    attribution: '© OSM © CARTO',
    subdomains: 'abcd',
    maxZoom: 19,
  }).addTo(map.value);

  if (lat.value && lng.value) drawMarker(lat.value, lng.value);

  map.value.on('click', (e) => {
    drawMarker(e.latlng.lat, e.latlng.lng);
    reverseGeocode(e.latlng.lat, e.latlng.lng);
  });

  // Forzar recalcular tamaño tras animación de entrada del sheet
  setTimeout(() => map.value?.invalidateSize(), 350);
}

function drawMarker(la, ln) {
  lat.value = la;
  lng.value = ln;
  if (marker.value) marker.value.remove();
  marker.value = L.marker([la, ln], {
    icon: L.divIcon({
      className: '',
      html: '<div style="width:18px;height:18px;border-radius:50%;background:#fff;border:3px solid #c8a97e;box-shadow:0 0 0 2px #0f0f0f"></div>',
      iconSize: [18, 18], iconAnchor: [9, 9],
    }),
    draggable: true,
  }).addTo(map.value);
  marker.value.on('dragend', () => {
    const p = marker.value.getLatLng();
    drawMarker(p.lat, p.lng);
    reverseGeocode(p.lat, p.lng);
  });
}

let searchDebounce;
function onPlaceInput() {
  clearTimeout(searchDebounce);
  searchError.value = '';
  if (placeQ.value.length < 3) {
    placeResults.value = [];
    searching.value = false;
    return;
  }
  searching.value = true;
  searchDebounce = setTimeout(async () => {
    try {
      const r = await GeoAPI.search(placeQ.value);
      placeResults.value = Array.isArray(r) ? r : [];
      if (!placeResults.value.length) searchError.value = 'Sin resultados';
    } catch (e) {
      placeResults.value = [];
      // status 429 = rate limit, 502 = Nominatim caído, etc.
      searchError.value = e?.status === 429
        ? 'Demasiadas búsquedas, espera unos segundos'
        : (e?.message || 'Error al buscar');
    } finally {
      searching.value = false;
    }
  }, 350);
}

function pickPlace(r) {
  const la = parseFloat(r.lat), ln = parseFloat(r.lon);
  drawMarker(la, ln);
  map.value.flyTo([la, ln], 12, { duration: 0.7 });
  placeQ.value = r.display_name.split(',').slice(0, 3).join(',');
  place.value  = r.display_name.split(',').slice(0, 2).join(',').trim();
  placeResults.value = [];
}

async function reverseGeocode(la, ln) {
  geoStatus.value = '🔍 Buscando…';
  try {
    const r = await GeoAPI.reverse(la, ln);
    if (r?.address) {
      const a = r.address;
      const tag = [a.city || a.town || a.village || a.municipality, a.country].filter(Boolean).join(', ');
      if (!place.value && tag) place.value = tag;
      geoStatus.value = '📍 ' + (r.display_name?.split(',').slice(0, 3).join(',') || tag);
    } else {
      geoStatus.value = '';
    }
  } catch {
    geoStatus.value = '';
  }
}

function useMyLocation() {
  if (!navigator.geolocation) {
    geoStatus.value = '⚠ Geolocalización no disponible';
    return;
  }
  geoStatus.value = '📡 Obteniendo ubicación…';
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      drawMarker(pos.coords.latitude, pos.coords.longitude);
      map.value?.flyTo([pos.coords.latitude, pos.coords.longitude], 14, { duration: 0.7 });
      reverseGeocode(pos.coords.latitude, pos.coords.longitude);
    },
    (err) => { geoStatus.value = '⚠ ' + (err.message || 'No se pudo obtener'); }
  );
}

function emitPick() {
  if (lat.value === null || lng.value === null) return;
  emit('pick', { lat: lat.value, lng: lng.value, place: place.value });
  close();
}

function doClear() {
  emit('clear');
  close();
}

watch(() => props.modelValue, async (v) => {
  if (v) {
    // Inicializar valores desde props cada vez que se abre
    lat.value   = props.initialLat;
    lng.value   = props.initialLng;
    place.value = props.initialPlace || '';
    placeQ.value = '';
    placeResults.value = [];
    geoStatus.value = '';
    await nextTick();
    initMap();
    // Reset center/zoom al abrir
    if (map.value) {
      if (lat.value && lng.value) {
        map.value.flyTo([lat.value, lng.value], 12, { duration: 0 });
        drawMarker(lat.value, lng.value);
      } else {
        map.value.flyTo([42.5987, -5.5703], 4, { duration: 0 });
        if (marker.value) { marker.value.remove(); marker.value = null; }
      }
      setTimeout(() => map.value.invalidateSize(), 300);
    }
  }
});

onBeforeUnmount(() => {
  if (map.value) {
    map.value.remove();
    map.value = null;
  }
});
</script>

<template>
  <transition name="sheet">
    <div v-if="modelValue" class="sheet-overlay" @click.self="close">
      <div ref="sheetEl" class="sheet"
        role="dialog" aria-modal="true" aria-labelledby="geotagger-title" tabindex="-1">
        <div class="sheet-handle" />
        <div class="sheet-head">
          <div>
            <h3 id="geotagger-title">{{ title }}</h3>
            <p v-if="subtitle" class="muted small" style="margin:2px 0 0">{{ subtitle }}</p>
          </div>
          <button class="close-btn" @click="close" aria-label="Cerrar">✕</button>
        </div>

        <div class="search-wrap">
          <input v-model="placeQ" @input="onPlaceInput"
            placeholder="🔍 Buscar lugar (ciudad, monumento…)"
            aria-label="Buscar lugar" />
          <div v-if="searching" class="search-status muted small">🔍 Buscando…</div>
          <div v-else-if="searchError && placeQ.length >= 3" class="search-status danger small">
            {{ searchError }}
          </div>
          <div v-else-if="placeResults.length" class="search-results">
            <button v-for="r in placeResults" :key="r.place_id" class="sr" @click="pickPlace(r)">
              {{ r.display_name.split(',').slice(0, 3).join(',') }}
            </button>
          </div>
        </div>

        <div ref="mapEl" class="map" />

        <div class="map-tools">
          <button class="btn ghost sm" @click="useMyLocation">📡 Mi ubicación</button>
          <span v-if="geoStatus" class="muted small">{{ geoStatus }}</span>
        </div>

        <div class="field" v-if="lat !== null && lng !== null">
          <label>Lugar (opcional)</label>
          <input v-model="place" placeholder="Sevilla, España" />
        </div>

        <p v-if="lat !== null && lng !== null" class="coords muted small">
          📍 {{ lat.toFixed(5) }}, {{ lng.toFixed(5) }}
        </p>
        <p v-else class="muted small" style="text-align:center;padding:6px">
          Toca el mapa, busca un lugar o usa tu ubicación
        </p>

        <div class="footer">
          <button v-if="allowClear && (initialLat !== null || initialLng !== null)"
            class="btn danger" @click="doClear">🗑 Quitar ubicación</button>
          <span class="spacer" />
          <button class="btn ghost" @click="close">Cancelar</button>
          <button class="btn pri" :disabled="lat === null || lng === null" @click="emitPick">
            💾 Guardar
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
  z-index: 1400;  /* por encima de Leaflet (1000) y de cualquier mapa embebido */
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
  display: flex; flex-direction: column;
}
.sheet-handle { width: 40px; height: 4px; background: var(--border2); border-radius: 2px; margin: -4px auto 12px; }
.sheet-head { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
.sheet-head h3 { margin: 0; font-size: 16px; }
.close-btn { font-size: 22px; color: var(--text-mute); padding: 4px 8px; }
.small { font-size: 11px; }

.search-wrap {
  position: relative;
  margin-bottom: 10px;
  /* Por encima del mapa Leaflet (que llega a z-index 700 internamente). */
  z-index: 1000;
}
.search-status {
  padding: 6px 10px;
  margin-top: 4px;
  background: var(--s2);
  border-radius: var(--radius);
  border: 1px solid var(--border);
}
.search-status.danger { color: var(--danger); border-color: var(--danger); }
.search-results {
  position: absolute; top: 100%; left: 0; right: 0;
  background: var(--s2); border: 1px solid var(--border);
  border-radius: var(--radius);
  margin-top: 4px;
  max-height: 200px; overflow-y: auto;
  /* Por encima de los panes de Leaflet (markerPane = 600, popup = 700). */
  z-index: 1100;
  box-shadow: 0 8px 24px rgba(0,0,0,.4);
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

.map {
  height: 320px;
  border-radius: var(--radius);
  overflow: hidden;
  margin-bottom: 8px;
}

.map-tools { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }

.field { margin-bottom: 8px; }
.coords { text-align: center; padding: 4px 0; }

.footer {
  display: flex; align-items: center; gap: 8px;
  margin-top: 12px;
  padding-top: 10px;
  border-top: 1px solid var(--border);
}
.spacer { flex: 1; }
.btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 38px; padding: 0 14px; border-radius: var(--radius); font-size: 13px; font-weight: 500; background: var(--s2); color: var(--text); border: 1px solid var(--border); }
.btn.sm { min-height: 32px; padding: 0 10px; font-size: 12px; }
.btn.pri { background: var(--accent); color: #0f0f0f; border-color: var(--accent); }
.btn.ghost { background: transparent; }
.btn.danger { background: var(--danger); color: white; border-color: var(--danger); }
.btn:disabled { opacity: .4; }

.sheet-enter-active, .sheet-leave-active { transition: opacity .25s; }
.sheet-enter-active .sheet, .sheet-leave-active .sheet { transition: transform .25s; }
.sheet-enter-from, .sheet-leave-to { opacity: 0; }
.sheet-enter-from .sheet, .sheet-leave-to .sheet { transform: translateY(100%); }
</style>
