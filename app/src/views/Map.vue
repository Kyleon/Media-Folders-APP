<script setup>
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import L from 'leaflet';
import { useLocationsStore } from '../stores/locations';
import { useFoldersStore } from '../stores/folders';
import { useUiStore } from '../stores/ui';
import { GeoAPI, MapAPI, MediaAPI } from '../api/endpoints';
import Spinner from '../components/Spinner.vue';

const locations = useLocationsStore();
const folders   = useFoldersStore();
const ui        = useUiStore();

const mapEl     = ref(null);
const map       = ref(null);
const markers   = ref({});
const newMarker = ref(null);

const editing = ref(false);
const form    = ref(makeEmptyForm());
const placeQ  = ref('');
const placeResults = ref([]);

const saving  = ref(false);

const showPhotos = ref(true);   // toggle capa de fotos
const photos     = ref([]);
const photoLayer = ref(null);   // L.layerGroup
const photoPreview = ref(null); // foto activa para preview lateral

function makeEmptyForm() {
  return {
    id: 0, name: '', tag: '', description: '', gallery_url: '',
    lat: null, lng: null, hero_id: 0, folder_ids: [], photo_ids: [],
  };
}

onMounted(async () => {
  await Promise.all([locations.load(), folders.load(), loadPhotos()]);
  initMap();
  renderMarkers();
  renderPhotoLayer();
});

async function loadPhotos() {
  try {
    photos.value = await MediaAPI.listGeo(2000);
  } catch (e) {
    console.warn('No se pudieron cargar fotos georreferenciadas', e);
  }
}

function renderPhotoLayer() {
  if (!map.value) return;
  if (photoLayer.value) {
    photoLayer.value.clearLayers();
    map.value.removeLayer(photoLayer.value);
    photoLayer.value = null;
  }
  if (!showPhotos.value || !photos.value.length) return;

  photoLayer.value = L.layerGroup();
  for (const p of photos.value) {
    const m = L.marker([p.lat, p.lng], {
      icon: L.divIcon({
        className: '',
        html: '<div class="photo-pin" style="width:10px;height:10px;border-radius:50%;background:#4e8ef7;border:2px solid #0f0f0f;box-shadow:0 0 0 1px rgba(78,142,247,.5)"></div>',
        iconSize: [10, 10], iconAnchor: [5, 5],
      }),
    });
    m.on('click', (e) => {
      L.DomEvent.stop(e);
      photoPreview.value = p;
    });
    photoLayer.value.addLayer(m);
  }
  photoLayer.value.addTo(map.value);
}

function togglePhotos() {
  showPhotos.value = !showPhotos.value;
  renderPhotoLayer();
}


onBeforeUnmount(() => {
  if (map.value) map.value.remove();
});

function initMap() {
  map.value = L.map(mapEl.value, {
    center: [42.5987, -5.5703],
    zoom: 4,
  });
  L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
    attribution: '© OSM © CARTO',
    subdomains: 'abcd', maxZoom: 20,
  }).addTo(map.value);

  map.value.on('click', (e) => {
    if (!editing.value) return;
    placePin(e.latlng.lat, e.latlng.lng);
    reverseGeocode(e.latlng.lat, e.latlng.lng);
  });
}

function pinDiv(active) {
  return L.divIcon({
    className: '',
    html: `<div style="width:14px;height:14px;border-radius:50%;background:${active ? '#fff' : '#c8a97e'};border:2px solid #0f0f0f;box-shadow:0 0 0 2px ${active ? '#c8a97e' : 'transparent'}"></div>`,
    iconSize: [14, 14], iconAnchor: [7, 7],
  });
}

function renderMarkers() {
  Object.values(markers.value).forEach(m => m.remove());
  markers.value = {};
  for (const loc of locations.items) {
    if (!loc.lat || !loc.lng) continue;
    const m = L.marker([loc.lat, loc.lng], { icon: pinDiv(false) }).addTo(map.value);
    m.on('click', () => openLocation(loc.id));
    markers.value[loc.id] = m;
  }
}

watch(() => locations.items, () => { if (map.value) renderMarkers(); }, { deep: true });

function openLocation(id) {
  const loc = locations.items.find(l => l.id === id);
  if (!loc) return;
  form.value = {
    id: loc.id,
    name: loc.name,
    tag: loc.tag || '',
    description: loc.description || '',
    gallery_url: loc.gallery_url || '',
    lat: loc.lat, lng: loc.lng,
    hero_id: loc.hero_id || 0,
    folder_ids: loc.folder_ids || [],
    photo_ids: loc.photo_ids || [],
  };
  editing.value = true;
  placePin(loc.lat, loc.lng);
  map.value.flyTo([loc.lat, loc.lng], Math.max(map.value.getZoom(), 7), { duration: .6 });

  // Resaltar marker
  Object.entries(markers.value).forEach(([mid, m]) => m.setIcon(pinDiv(parseInt(mid) === id)));
}

function newLocation() {
  form.value = makeEmptyForm();
  editing.value = true;
  if (newMarker.value) { newMarker.value.remove(); newMarker.value = null; }
  Object.values(markers.value).forEach(m => m.setIcon(pinDiv(false)));
}

function closeEdit() {
  editing.value = false;
  if (newMarker.value) { newMarker.value.remove(); newMarker.value = null; }
  Object.values(markers.value).forEach(m => m.setIcon(pinDiv(false)));
}

function placePin(lat, lng) {
  form.value.lat = lat;
  form.value.lng = lng;
  if (newMarker.value) newMarker.value.remove();
  newMarker.value = L.marker([lat, lng], {
    icon: L.divIcon({ className: '', html: '<div style="width:18px;height:18px;border-radius:50%;background:#fff;border:3px solid #c8a97e"></div>', iconSize:[18,18], iconAnchor:[9,9] }),
    draggable: true,
  }).addTo(map.value);
  newMarker.value.on('dragend', () => {
    const p = newMarker.value.getLatLng();
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
  map.value.flyTo([lat, lng], 10, { duration: .8 });
  placeQ.value = r.display_name.split(',').slice(0,3).join(',');
  placeResults.value = [];
  if (!form.value.name) form.value.name = placeQ.value;
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
    if (form.value.id) await MapAPI.update(form.value.id, form.value);
    else                await MapAPI.create(form.value);
    await locations.load();
    ui.toast('✓ Guardado', 'ok');
    editing.value = false;
    if (newMarker.value) { newMarker.value.remove(); newMarker.value = null; }
  } catch (e) {
    ui.toast(e.message, 'err');
  } finally {
    saving.value = false;
  }
}

async function remove() {
  if (!form.value.id) return;
  if (!confirm('¿Eliminar esta ubicación?')) return;
  try {
    await locations.remove(form.value.id);
    ui.toast('🗑 Eliminada', 'ok');
    closeEdit();
  } catch (e) { ui.toast(e.message, 'err'); }
}

function toggleFolder(id) {
  const idx = form.value.folder_ids.indexOf(id);
  if (idx === -1) form.value.folder_ids.push(id);
  else            form.value.folder_ids.splice(idx, 1);
}
</script>

<template>
  <div class="map-wrap">
    <div ref="mapEl" class="map" />

    <!-- Toggle de capa de fotos -->
    <div v-if="!editing" class="map-overlay-controls">
      <button class="layer-toggle" :class="{ on: showPhotos }" @click="togglePhotos">
        📸 Fotos
        <span class="muted small">{{ photos.length }}</span>
      </button>
    </div>

    <button v-if="!editing" class="fab" @click="newLocation">+ Nueva ubicación</button>

    <!-- Preview de foto al click sobre pin azul -->
    <div v-if="photoPreview" class="photo-preview" @click.self="photoPreview = null">
      <div class="pp-card">
        <button class="pp-close" @click="photoPreview = null">✕</button>
        <img v-if="photoPreview.large || photoPreview.medium || photoPreview.thumb"
          :src="photoPreview.large || photoPreview.medium || photoPreview.thumb"
          :alt="photoPreview.alt || photoPreview.title"
          loading="lazy" />
        <div class="pp-body">
          <div class="pp-title">{{ photoPreview.title }}</div>
          <div v-if="photoPreview.place" class="pp-place muted small">📍 {{ photoPreview.place }}</div>
          <button class="btn pri sm" @click="$router.push({ name: 'media-detail', params: { id: photoPreview.id } })">Abrir imagen</button>
        </div>
      </div>
    </div>

    <transition name="sheet">
      <div v-if="editing" class="sheet-overlay" @click.self="closeEdit">
        <div class="sheet">
          <div class="sheet-handle" />
          <div class="sheet-head">
            <h3>{{ form.id ? 'Editar ubicación' : 'Nueva ubicación' }}</h3>
            <button class="close-btn" @click="closeEdit">✕</button>
          </div>

          <div class="field">
            <label>Buscar lugar</label>
            <input v-model="placeQ" @input="onPlaceInput" placeholder="Ciudad, monumento…" />
            <div v-if="placeResults.length" class="search-results">
              <button v-for="r in placeResults" :key="r.place_id" class="sr" @click="pickPlace(r)">
                {{ r.display_name.split(',').slice(0,3).join(',') }}
              </button>
            </div>
          </div>

          <div class="field">
            <label>Nombre *</label>
            <input v-model="form.name" />
          </div>
          <div class="row">
            <div class="field" style="flex:1">
              <label>Etiqueta</label>
              <input v-model="form.tag" placeholder="Paisaje, viajes…" />
            </div>
          </div>
          <div class="field">
            <label>Descripción</label>
            <textarea v-model="form.description" rows="2"></textarea>
          </div>
          <div class="field">
            <label>URL galería</label>
            <input v-model="form.gallery_url" type="url" placeholder="https://…" />
          </div>

          <div class="field">
            <label>Carpetas vinculadas</label>
            <div class="checks">
              <button
                v-for="f in folders.flat" :key="f.id"
                class="folder-chip"
                :class="{ on: form.folder_ids.includes(f.id) }"
                @click="toggleFolder(f.id)">
                📁 {{ f.name }}
              </button>
              <p v-if="!folders.flat.length" class="muted small">Sin carpetas</p>
            </div>
          </div>

          <p v-if="form.lat && form.lng" class="muted small coords">
            📍 {{ form.lat.toFixed(5) }}, {{ form.lng.toFixed(5) }}
          </p>
          <p v-else class="muted small coords">
            Toca el mapa para colocar el pin
          </p>

          <div class="row" style="margin-top:8px">
            <button class="btn pri" :disabled="saving" @click="save" style="flex:1">
              <Spinner v-if="saving" :size="14" />
              <span v-else>💾 Guardar</span>
            </button>
            <button v-if="form.id" class="btn danger" @click="remove">🗑</button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.map-wrap { position: relative; height: calc(100vh - 52px - 56px - env(safe-area-inset-top) - env(safe-area-inset-bottom)); margin: -16px; }
.map { width: 100%; height: 100%; }

.fab {
  position: absolute;
  right: 16px;
  bottom: calc(16px + env(safe-area-inset-bottom));
  background: var(--accent);
  color: #0f0f0f;
  padding: 12px 18px;
  border-radius: 30px;
  font-size: 14px;
  font-weight: 600;
  box-shadow: var(--shadow);
  z-index: 1100;  /* por encima de los controles Leaflet (1000) */
}

.map-overlay-controls {
  position: absolute;
  top: 12px; right: 12px;
  z-index: 1100;  /* por encima de los controles Leaflet (1000) */
  display: flex; gap: 6px;
}
.layer-toggle {
  display: flex; align-items: center; gap: 6px;
  padding: 8px 14px;
  background: rgba(15,15,15,.85);
  color: #aaa;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 500;
  border: 1px solid #2e2e2e;
}
.layer-toggle.on {
  color: #4e8ef7;
  border-color: #4e8ef7;
}
.layer-toggle:active { background: rgba(15,15,15,1); }

.photo-preview {
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,.5);
  z-index: 1200;  /* encima del FAB y los controles del mapa */
  display: flex; align-items: center; justify-content: center;
  padding: 20px;
}
.pp-card {
  width: 100%; max-width: 360px;
  background: var(--s1);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  position: relative;
  box-shadow: var(--shadow);
}
@media (min-width: 768px)  { .pp-card { max-width: 520px; } }
@media (min-width: 1280px) { .pp-card { max-width: 720px; } }
.pp-close {
  position: absolute; top: 8px; right: 8px;
  width: 28px; height: 28px;
  background: rgba(0,0,0,.6);
  color: white;
  border-radius: 50%;
  font-size: 14px;
  z-index: 1;
}
.pp-card img {
  width: 100%;
  max-height: min(60vh, 480px);
  object-fit: contain;
  background: var(--s2);
  display: block;
}
.pp-body { padding: 12px; display: flex; flex-direction: column; gap: 6px; }
.pp-title { font-size: 14px; font-weight: 500; }
.pp-place { font-size: 11px; }
.pp-body .btn.sm { min-height: 34px; padding: 0 12px; font-size: 12px; }

.sheet-overlay {
  position: absolute; inset: 0;
  background: rgba(0,0,0,.5);
  z-index: 1300;  /* el bottomsheet por encima de todo el mapa */
  display: flex; align-items: flex-end;
}
.sheet {
  width: 100%;
  max-height: 85%;
  background: var(--s1);
  border-top-left-radius: 18px;
  border-top-right-radius: 18px;
  padding: 14px 16px calc(20px + env(safe-area-inset-bottom));
  overflow-y: auto;
}
.sheet-handle { width: 40px; height: 4px; background: var(--border2); border-radius: 2px; margin: -4px auto 12px; }
.sheet-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.sheet-head h3 { margin: 0; font-size: 16px; }
.close-btn { font-size: 22px; color: var(--text-mute); padding: 4px 8px; }

.search-results {
  background: var(--s2); border: 1px solid var(--border);
  border-radius: var(--radius);
  margin-top: 4px;
  max-height: 200px; overflow-y: auto;
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

.checks { display: flex; flex-wrap: wrap; gap: 6px; }
.folder-chip {
  padding: 6px 10px;
  background: var(--s2);
  border-radius: 16px;
  font-size: 12px;
  color: var(--text-mute);
  border: 1px solid var(--border);
}
.folder-chip.on { background: var(--accent-lo); color: var(--accent); border-color: var(--accent); }

.coords { margin: 8px 0; }
.small { font-size: 11px; }

.sheet-enter-active, .sheet-leave-active { transition: opacity .25s; }
.sheet-enter-active .sheet, .sheet-leave-active .sheet { transition: transform .25s; }
.sheet-enter-from, .sheet-leave-to { opacity: 0; }
.sheet-enter-from .sheet, .sheet-leave-to .sheet { transform: translateY(100%); }
</style>
