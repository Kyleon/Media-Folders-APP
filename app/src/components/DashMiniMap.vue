<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import { MediaAPI } from '../api/endpoints';
// Leaflet se carga DINÁMICAMENTE al primer render para no inflar el bundle
// inicial del Dashboard (~150KB minified). Sin esto, '/' arrastra Leaflet
// aunque el user no entre a /map.

const router = useRouter();
const mapEl  = ref(null);
const count  = ref(0);
const loading = ref(true);
let map = null;
let L = null; // se hidrata en el primer render()

async function load() {
  loading.value = true;
  try {
    const photos = await MediaAPI.listGeo(120);
    count.value = photos.length;
    setTimeout(() => render(photos), 50);
  } finally {
    loading.value = false;
  }
}

async function render(photos) {
  if (!mapEl.value) return;
  if (map) { map.remove(); map = null; }

  if (!photos.length) return;

  if (!L) {
    // Import dinámico: este chunk se sirve solo cuando hace falta.
    L = (await import('leaflet')).default;
  }

  // Bounds que abarquen todos los puntos
  const lats = photos.map(p => p.lat).filter(Number.isFinite);
  const lngs = photos.map(p => p.lng).filter(Number.isFinite);
  if (!lats.length) return;

  map = L.map(mapEl.value, {
    zoomControl: false,
    dragging: false,
    scrollWheelZoom: false,
    doubleClickZoom: false,
    boxZoom: false,
    keyboard: false,
    attributionControl: false,
  });

  L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
    subdomains: 'abcd',
    maxZoom: 19,
  }).addTo(map);

  const dotIcon = L.divIcon({
    className: '',
    html: '<div class="dash-dot"></div>',
    iconSize: [10, 10], iconAnchor: [5, 5],
  });

  photos.forEach(p => {
    if (Number.isFinite(p.lat) && Number.isFinite(p.lng)) {
      L.marker([p.lat, p.lng], { icon: dotIcon, interactive: false }).addTo(map);
    }
  });

  const bounds = L.latLngBounds(photos.map(p => [p.lat, p.lng]));
  map.fitBounds(bounds, { padding: [16, 16], maxZoom: 7 });

  setTimeout(() => map.invalidateSize(), 100);
}

onMounted(load);
onBeforeUnmount(() => { if (map) { map.remove(); map = null; } });

defineExpose({ refresh: load });
</script>

<template>
  <div class="card minimap-card" @click="$router.push({ name: 'map' })">
    <div class="head">
      <span class="card-label">Mapa de fotos</span>
      <span class="muted small">{{ count }} ubicación{{ count === 1 ? '' : 'es' }} →</span>
    </div>
    <div ref="mapEl" class="map"></div>
    <div v-if="!loading && !count" class="empty muted small">
      Aún no has geolocalizado ninguna foto.
    </div>
  </div>
</template>

<style scoped>
.minimap-card {
  cursor: pointer;
  transition: border-color .15s;
  display: flex; flex-direction: column; gap: 8px;
}
@media (hover: hover) {
  .minimap-card:hover { border-color: var(--accent); }
}
.head { display: flex; justify-content: space-between; align-items: center; }
.card-label { font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: var(--text-mute); font-weight: 600; }
.small { font-size: 11px; }
.map {
  height: 180px;
  border-radius: var(--radius);
  overflow: hidden;
  background: var(--s2);
  pointer-events: none;
  /* Leaflet usa z-index hasta 700 en sus tile/marker/popup panes y por defecto
     no crea un stacking context (solo position:relative). Sin esto los tiles
     se cuelan por encima del bottom-nav (z-index 50) cuando el card scrollea
     hasta esa zona. isolation:isolate confina los z-index internos. */
  isolation: isolate;
  position: relative;
  z-index: 0;
}
.empty { padding: 20px; text-align: center; }
</style>

<style>
/* Marker dot styling — global porque Leaflet inyecta el HTML */
.dash-dot {
  width: 10px; height: 10px;
  border-radius: 50%;
  background: var(--accent);
  box-shadow: 0 0 0 2px rgba(0,0,0,.5);
}
</style>
