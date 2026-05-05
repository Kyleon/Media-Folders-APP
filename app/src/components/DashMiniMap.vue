<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import { MediaAPI } from '../api/endpoints';
import L from 'leaflet';

const router = useRouter();
const mapEl  = ref(null);
const count  = ref(0);
const loading = ref(true);
let map = null;

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

function render(photos) {
  if (!mapEl.value) return;
  if (map) { map.remove(); map = null; }

  if (!photos.length) return;

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
