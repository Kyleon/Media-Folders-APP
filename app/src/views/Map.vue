<script setup>
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import L from 'leaflet';
// Plugin de clustering. Registra L.markerClusterGroup como side-effect.
import 'leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import { useLocationsStore } from '../stores/locations';
import { useFoldersStore } from '../stores/folders';
import { useUiStore } from '../stores/ui';
import { GeoAPI, MapAPI, MediaAPI, PortfoliosAPI } from '../api/endpoints';
import Spinner from '../components/Spinner.vue';
import { useRouter } from 'vue-router';

const router = useRouter();

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

// Capa de fotos OFF por defecto: cargar 500-2000 markers individuales
// congela el hilo principal en móvil. El user la activa explícitamente.
const showPhotos = ref(false);
const photos     = ref([]);
const photoLayer = ref(null);   // L.layerGroup
const photoPreview = ref(null); // foto activa para preview lateral

// Filtros combinables (AND) sobre la capa de fotos. 0 = sin filtro.
const photoFilterFolder    = ref(0);
const photoFilterPortfolio = ref(0);

// Estado del scan EXIF en background (polling cada 5s mientras esté running).
const exifScan = ref({ running: false, processed: 0, total: 0, found: 0 });
let exifPollTimer = null;

const showPortfolios = ref(true);
const portfolios     = ref([]);   // sólo los que tienen ubicación (para mapa)
const allPortfolios  = ref([]);   // todos (para el selector del sheet)
const portfolioLayer = ref(null);
const portfolioPreview = ref(null);

function makeEmptyForm() {
  return {
    id: 0, name: '', tag: '', description: '', gallery_url: '',
    lat: null, lng: null, hero_id: 0, folder_ids: [], photo_ids: [],
    portfolio_ids: [],
  };
}

onMounted(async () => {
  // Carga prioritaria: locations + portfolios geo + lista ligera de
  // portfolios. Las fotos se cargan SOLO cuando el user activa la capa.
  await Promise.all([
    locations.load(), folders.load(),
    loadPortfolios(), loadAllPortfolios(),
  ]);
  initMap();
  renderMarkers();
  renderPortfolioLayer();
  // Comprueba si hay un scan EXIF corriendo (auto-arrancado por el plugin
  // tras subir nueva versión). Si lo hay, mostramos el indicador y
  // recargamos las fotos cuando termine.
  pollExifStatus();
});

async function loadPhotos() {
  try {
    // Cap bajado a 500 (alineado con el cap server-side). Filtros combinables
    // por carpeta + portfolio (AND).
    const params = { limit: 500 };
    if (photoFilterFolder.value > 0)    params.folder_id    = photoFilterFolder.value;
    if (photoFilterPortfolio.value > 0) params.portfolio_id = photoFilterPortfolio.value;
    photos.value = await MediaAPI.listGeo(params);
  } catch (e) {
    // Silencioso: no es crítico. ui.toast lo reporta en otros flujos.
  }
}

// Re-cargar fotos cuando cambien los filtros (si la capa está visible).
watch([photoFilterFolder, photoFilterPortfolio], async () => {
  if (!showPhotos.value) return;
  await loadPhotos();
  renderPhotoLayer();
});

/* ─────── EXIF scan polling ─────── */

async function pollExifStatus() {
  try {
    exifScan.value = await MediaAPI.scanExifStatus();
  } catch { /* no auth o endpoint no disponible: silencioso */ }
  if (exifScan.value.running) {
    exifPollTimer = setTimeout(pollExifStatus, 5000);
  } else {
    exifPollTimer = null;
    // Si terminó y la capa está visible, recarga para mostrar las nuevas
    // fotos geolocalizadas que aparecieron tras el scan.
    if (showPhotos.value) {
      await loadPhotos();
      renderPhotoLayer();
    }
  }
}

async function startExifScan() {
  try {
    exifScan.value = await MediaAPI.scanExifStart();
    if (exifScan.value.running && !exifPollTimer) pollExifStatus();
    ui.toast(exifScan.value.running
      ? `📍 Escaneando ${exifScan.value.total} imágenes…`
      : 'Sin imágenes pendientes de escanear', 'ok');
  } catch (e) {
    ui.toast(e.message || 'No se pudo iniciar el escaneo', 'err');
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

  // L.markerClusterGroup agrupa markers próximos en un círculo con conteo,
  // y "spiderfy" abre como abanico los que están exactamente en el mismo
  // punto. Resuelve dos cosas a la vez: perf con cientos de markers y
  // solapamientos en zooms altos.
  photoLayer.value = L.markerClusterGroup({
    chunkedLoading: true,            // procesa en chunks para no bloquear el hilo
    spiderfyOnMaxZoom: true,         // abre abanico en zoom máximo
    showCoverageOnHover: false,      // sin polígono al hover (más limpio)
    zoomToBoundsOnClick: true,
    maxClusterRadius: 50,            // px — clusters más compactos en mobile
    spiderfyDistanceMultiplier: 1.4,
    disableClusteringAtZoom: 19,
    iconCreateFunction: (cluster) => {
      const n = cluster.getChildCount();
      const size = n < 10 ? 'sm' : n < 100 ? 'md' : 'lg';
      return L.divIcon({
        html: `<div class="yz-cluster yz-cluster-${size}"><span>${n}</span></div>`,
        className: 'yz-cluster-wrap',
        iconSize: L.point(40, 40),
      });
    },
  });

  const dotIcon = L.divIcon({
    className: '',
    html: '<div class="yz-photo-pin"></div>',
    iconSize: [14, 14], iconAnchor: [7, 7],
  });

  const markers = [];
  for (const p of photos.value) {
    if (!Number.isFinite(p.lat) || !Number.isFinite(p.lng)) continue;
    const m = L.marker([p.lat, p.lng], { icon: dotIcon });
    m.on('click', (e) => {
      L.DomEvent.stop(e);
      photoPreview.value = p;
    });
    markers.push(m);
  }
  photoLayer.value.addLayers(markers);  // bulk add — mucho más rápido que addLayer N veces
  photoLayer.value.addTo(map.value);
}

async function togglePhotos() {
  showPhotos.value = !showPhotos.value;
  // Carga diferida: solo descargamos las fotos cuando el user activa la capa.
  if (showPhotos.value && !photos.value.length) {
    await loadPhotos();
  }
  renderPhotoLayer();
}

async function loadPortfolios() {
  try {
    portfolios.value = await PortfoliosAPI.listGeo();
  } catch (e) {
    console.warn('No se pudieron cargar portfolios geolocalizados', e);
  }
}

async function loadAllPortfolios() {
  try {
    // Lista ligera para el selector del sheet — incluye los sin ubicación.
    const res = await PortfoliosAPI.list({ per_page: 100, status: 'any' });
    allPortfolios.value = res.items || [];
  } catch (e) {
    console.warn('No se pudieron cargar portfolios', e);
  }
}

function renderPortfolioLayer() {
  if (!map.value) return;
  if (portfolioLayer.value) {
    portfolioLayer.value.clearLayers();
    map.value.removeLayer(portfolioLayer.value);
    portfolioLayer.value = null;
  }
  if (!showPortfolios.value || !portfolios.value.length) return;

  portfolioLayer.value = L.layerGroup();
  // Agrupa por location_id para offsets en clusters pequeños (cuando varios portfolios comparten ubicación)
  const byLoc = new Map();
  for (const p of portfolios.value) {
    const arr = byLoc.get(p.location_id) || [];
    arr.push(p);
    byLoc.set(p.location_id, arr);
  }
  for (const [, arr] of byLoc) {
    arr.forEach((p, idx) => {
      // Pequeño desplazamiento circular si hay varios en la misma coord
      const step = arr.length > 1 ? (idx / arr.length) * Math.PI * 2 : 0;
      const r    = arr.length > 1 ? 0.0006 : 0;
      const lat  = p.lat + Math.cos(step) * r;
      const lng  = p.lng + Math.sin(step) * r;
      const m = L.marker([lat, lng], {
        icon: L.divIcon({
          className: '',
          html: '<div class="portfolio-pin" style="width:12px;height:12px;border-radius:3px;background:#a78bfa;border:2px solid #0f0f0f;box-shadow:0 0 0 1px rgba(167,139,250,.5)"></div>',
          iconSize: [12, 12], iconAnchor: [6, 6],
        }),
      });
      m.on('click', (e) => {
        L.DomEvent.stop(e);
        portfolioPreview.value = p;
      });
      portfolioLayer.value.addLayer(m);
    });
  }
  portfolioLayer.value.addTo(map.value);
}

function togglePortfolios() {
  showPortfolios.value = !showPortfolios.value;
  renderPortfolioLayer();
}


onBeforeUnmount(() => {
  if (map.value) map.value.remove();
  if (exifPollTimer) { clearTimeout(exifPollTimer); exifPollTimer = null; }
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
    portfolio_ids: loc.portfolio_ids || [],
  };
  editing.value = true;
  placePin(loc.lat, loc.lng);
  map.value.flyTo([loc.lat, loc.lng], Math.max(map.value.getZoom(), 7), { duration: .6 });

  // Resaltar marker
  Object.entries(markers.value).forEach(([mid, m]) => m.setIcon(pinDiv(parseInt(mid) === id)));
}

function togglePortfolioLink(portfolioId) {
  const idx = form.value.portfolio_ids.indexOf(portfolioId);
  if (idx === -1) form.value.portfolio_ids.push(portfolioId);
  else            form.value.portfolio_ids.splice(idx, 1);
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
  // Resolve dirección estructurada para rellenar también la etiqueta
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
    if (form.value.id) await MapAPI.update(form.value.id, form.value);
    else                await MapAPI.create(form.value);
    await Promise.all([locations.load(), loadPortfolios(), loadAllPortfolios()]);
    renderPortfolioLayer();
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

    <!-- Toggles de capas + filtros -->
    <div v-if="!editing" class="map-overlay-controls">
      <button class="layer-toggle photos" :class="{ on: showPhotos }" @click="togglePhotos"
        :aria-pressed="showPhotos">
        📸 Fotos
        <span class="muted small">{{ photos.length }}</span>
      </button>
      <button class="layer-toggle portfolios" :class="{ on: showPortfolios }" @click="togglePortfolios"
        :aria-pressed="showPortfolios">
        🗂 Portfolios
        <span class="muted small">{{ portfolios.length }}</span>
      </button>

      <!-- Filtros combinables sobre la capa de fotos (visible solo si está activa) -->
      <div v-if="showPhotos" class="map-photo-filters">
        <label class="ftr-row">
          <span class="ftr-label">Carpeta</span>
          <select v-model.number="photoFilterFolder" class="ftr-select">
            <option :value="0">— Todas —</option>
            <option v-for="f in folders.flat" :key="f.id" :value="f.id">
              {{ '— '.repeat(f.depth) }}{{ f.name }} ({{ f.count }})
            </option>
          </select>
        </label>
        <label class="ftr-row">
          <span class="ftr-label">Portfolio</span>
          <select v-model.number="photoFilterPortfolio" class="ftr-select">
            <option :value="0">— Todos —</option>
            <option v-for="p in allPortfolios" :key="p.id" :value="p.id">
              {{ p.title || ('#' + p.id) }}
            </option>
          </select>
        </label>
        <button v-if="photoFilterFolder || photoFilterPortfolio"
          class="ftr-clear" @click="photoFilterFolder = 0; photoFilterPortfolio = 0"
          aria-label="Limpiar filtros">✕ Limpiar</button>
      </div>

      <!-- Indicador del scan EXIF: progreso si corre, botón si no. -->
      <div v-if="exifScan.running" class="exif-scan-pill" role="status" aria-live="polite">
        <span>📍 EXIF: {{ exifScan.processed }}/{{ exifScan.total }}</span>
        <span class="muted small">{{ exifScan.found }} con geo</span>
      </div>
      <button v-else class="exif-scan-rerun"
        @click="startExifScan" title="Buscar GPS en EXIF de imágenes sin geo">
        🔍 {{ exifScan.finished ? 'Re-escanear EXIF' : 'Escanear EXIF' }}
      </button>
    </div>

    <button v-if="!editing" class="fab" @click="newLocation">+ Nueva ubicación</button>

    <!-- Preview de foto al click sobre pin azul -->
    <div v-if="photoPreview" class="photo-preview" @click.self="photoPreview = null"
      @keydown.escape="photoPreview = null">
      <div class="pp-card" role="dialog" aria-modal="true" aria-labelledby="pp-foto-title" tabindex="-1">
        <button class="pp-close" @click="photoPreview = null" aria-label="Cerrar">✕</button>
        <img v-if="photoPreview.large || photoPreview.medium || photoPreview.thumb"
          :src="photoPreview.large || photoPreview.medium || photoPreview.thumb"
          :alt="photoPreview.alt || photoPreview.title"
          loading="lazy" />
        <div class="pp-body">
          <div id="pp-foto-title" class="pp-title">{{ photoPreview.title }}</div>
          <div v-if="photoPreview.place" class="pp-place muted small">📍 {{ photoPreview.place }}</div>
          <button class="btn pri sm" @click="$router.push({ name: 'media-detail', params: { id: photoPreview.id } })">Abrir imagen</button>
        </div>
      </div>
    </div>

    <!-- Preview de portfolio al click sobre pin violeta -->
    <div v-if="portfolioPreview" class="photo-preview" @click.self="portfolioPreview = null"
      @keydown.escape="portfolioPreview = null">
      <div class="pp-card pp-card-portfolio" role="dialog" aria-modal="true" aria-labelledby="pp-port-title" tabindex="-1">
        <button class="pp-close" @click="portfolioPreview = null" aria-label="Cerrar">✕</button>
        <img v-if="portfolioPreview.hero_url"
          :src="portfolioPreview.hero_url"
          :alt="portfolioPreview.title"
          loading="lazy" />
        <div class="pp-body">
          <div id="pp-port-title" class="pp-title">🗂 {{ portfolioPreview.title }}</div>
          <div v-if="portfolioPreview.location_name" class="pp-place muted small">📍 {{ portfolioPreview.location_name }}</div>
          <button class="btn pri sm" @click="router.push({ name: 'portfolio-detail', params: { id: portfolioPreview.id } })">Abrir portfolio</button>
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

          <div class="field">
            <label>Portfolios vinculados <span class="muted small">(opcional)</span></label>
            <div class="checks">
              <button
                v-for="p in allPortfolios" :key="'pf-' + p.id"
                class="folder-chip portfolio-chip"
                :class="{ on: form.portfolio_ids.includes(p.id) }"
                @click="togglePortfolioLink(p.id)"
                :title="p.location_id && p.location_id !== form.id ? `Actualmente vinculado a otra ubicación (id ${p.location_id})` : ''">
                🗂 {{ p.title }}
                <span v-if="p.location_id && p.location_id !== form.id && !form.portfolio_ids.includes(p.id)" class="muted small">· otra ubicación</span>
              </button>
              <p v-if="!allPortfolios.length" class="muted small">Sin portfolios aún.</p>
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
/* Mapa anclado al viewport entre topbar (52/60 px) y bottom-nav (60 px + safe-bottom).
   Usamos position: fixed para que sea inmune a paddings/overflows del flujo padre
   y al cambio de viewport en móvil (barra URL oculta/visible, modo standalone PWA, etc.) */
.map-wrap {
  position: fixed;
  top: 52px;
  left: 0;
  right: 0;
  bottom: calc(60px + env(safe-area-inset-bottom));
  margin: 0;
  z-index: 5;  /* por debajo de topbar (10) y bottom-nav (50) */
}
@media (min-width: 768px) {
  .map-wrap { top: 60px; }
}
@media (min-width: 1024px) {
  /* Escritorio: sidebar fijo a la izquierda (220 px), sin bottom-nav */
  .map-wrap {
    top: 60px;
    left: 220px;
    bottom: 0;
  }
}
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
  display: flex; flex-wrap: wrap; gap: 6px;
  max-width: calc(100% - 24px);
  justify-content: flex-end;
}

.map-photo-filters {
  display: flex; flex-direction: column; gap: 4px;
  flex-basis: 100%;
  align-items: flex-end;
  padding: 8px 10px;
  background: rgba(15,15,15,.85);
  border: 1px solid #2e2e2e;
  border-radius: 10px;
}
.ftr-row {
  display: flex; align-items: center; gap: 8px;
  font-size: 12px; color: #aaa;
}
.ftr-label { min-width: 60px; }
.ftr-select {
  min-height: 32px;
  padding: 4px 8px;
  background: rgba(34,34,34,.95);
  color: #e6e6e6;
  border: 1px solid #2e2e2e;
  border-radius: 6px;
  font-size: 12px;
  max-width: 180px;
}
.ftr-clear {
  align-self: flex-end;
  padding: 4px 10px;
  background: transparent;
  color: #aaa;
  border: 1px solid #3a3a3a;
  border-radius: 12px;
  font-size: 11px;
}
.ftr-clear:hover { color: #e6e6e6; border-color: #5a5a5a; }

.exif-scan-pill, .exif-scan-rerun {
  display: flex; align-items: center; gap: 6px;
  padding: 6px 12px;
  background: rgba(15,15,15,.85);
  color: #c8a97e;
  border: 1px solid #c8a97e;
  border-radius: 16px;
  font-size: 12px;
}
.exif-scan-pill .muted { color: #888; font-size: 11px; }
.exif-scan-rerun { cursor: pointer; }
.exif-scan-rerun:hover { background: rgba(200,169,126,.16); }
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
.layer-toggle.photos.on {
  color: #4e8ef7;
  border-color: #4e8ef7;
}
.layer-toggle.portfolios.on {
  color: #a78bfa;
  border-color: #a78bfa;
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
.portfolio-chip.on { background: rgba(167,139,250,.16); color: #a78bfa; border-color: #a78bfa; }
.pp-card.pp-card-portfolio .pp-title { color: #a78bfa; }

.coords { margin: 8px 0; }
.small { font-size: 11px; }

.sheet-enter-active, .sheet-leave-active { transition: opacity .25s; }
.sheet-enter-active .sheet, .sheet-leave-active .sheet { transition: transform .25s; }
.sheet-enter-from, .sheet-leave-to { opacity: 0; }
.sheet-enter-from .sheet, .sheet-leave-to .sheet { transform: translateY(100%); }
</style>

<!-- Estilos NO scoped: Leaflet inyecta el HTML del divIcon en otro stacking
     context, así que :scoped no llega. Estos sólo se aplican a las clases
     que prefijamos con yz- (no afectan a otros componentes). -->
<style>
.yz-photo-pin {
  width: 14px; height: 14px;
  border-radius: 50%;
  background: #4e8ef7;
  border: 2px solid #0f0f0f;
  box-shadow: 0 0 0 1px rgba(78,142,247,.5);
}

.yz-cluster-wrap { background: transparent; border: 0; }
.yz-cluster {
  display: flex; align-items: center; justify-content: center;
  width: 40px; height: 40px;
  border-radius: 50%;
  color: #0f0f0f;
  font-weight: 700;
  font-size: 13px;
  background: rgba(78,142,247,.85);
  border: 2px solid #0f0f0f;
  box-shadow: 0 2px 8px rgba(0,0,0,.4);
  transition: transform .15s;
}
.yz-cluster:hover { transform: scale(1.08); }
.yz-cluster-sm { background: rgba(78,142,247,.85); }
.yz-cluster-md { background: rgba(200,169,126,.9); width: 46px; height: 46px; font-size: 14px; }
.yz-cluster-lg { background: rgba(224,85,85,.9);  width: 54px; height: 54px; font-size: 15px; color: #fff; }

/* Spider lines (las que conectan al centro al hacer click en un punto con
   varios markers exactamente iguales) */
.leaflet-cluster-anim .leaflet-marker-icon,
.leaflet-cluster-anim .leaflet-marker-shadow,
.leaflet-cluster-spider-leg {
  transition: stroke .3s ease-out, stroke-opacity .3s ease-out;
}
</style>
