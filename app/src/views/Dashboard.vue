<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useFoldersStore } from '../stores/folders';
import { StatsAPI, MediaAPI, PortfoliosAPI, MapAPI } from '../api/endpoints';
import ThemeSwitch from '../components/ThemeSwitch.vue';
import Spinner from '../components/Spinner.vue';
import PullRefresh from '../components/PullRefresh.vue';

const router  = useRouter();
const auth    = useAuthStore();
const folders = useFoldersStore();

const loading       = ref(true);
const stats         = ref(null);
const statsError    = ref(false);   // true si /stats falla
const fallbackTotals = ref(null);   // KPIs básicos cuando /stats no está disponible

async function load() {
  loading.value = true;
  statsError.value = false;
  try {
    stats.value = await StatsAPI.get();
  } catch (e) {
    // El endpoint /stats es nuevo. Si falla (plugin viejo en server), caemos a KPIs básicos
    console.warn('Stats endpoint no disponible, usando fallback', e?.message);
    statsError.value = true;
    stats.value = null;
    await loadFallback();
  } finally {
    loading.value = false;
  }
}

async function loadFallback() {
  try {
    const [m, p, l] = await Promise.all([
      MediaAPI.list({ per_page: 1, page: 1 }).then(r => r.total).catch(() => 0),
      PortfoliosAPI.list({ per_page: 1, page: 1 }).then(r => r.total).catch(() => 0),
      MapAPI.list().then(r => r.length).catch(() => 0),
      folders.load(true),
    ]);
    fallbackTotals.value = {
      media: m,
      portfolios: p,
      locations: l,
      folders: folders.flat.length,
    };
  } catch (e) {
    console.error(e);
  }
}

onMounted(load);

function logout() {
  auth.logout();
  router.replace({ name: 'login' });
}

// Sparkline en SVG (sólo si hay stats reales)
const sparkPath = computed(() => {
  if (!stats.value?.uploads_30d?.length) return '';
  const data = stats.value.uploads_30d;
  const max  = Math.max(1, ...data.map(d => d.count));
  const w    = 200;
  const h    = 40;
  const stepX = w / (data.length - 1 || 1);
  return data.map((d, i) => {
    const x = i * stepX;
    const y = h - (d.count / max) * h;
    return (i === 0 ? 'M' : 'L') + x.toFixed(1) + ',' + y.toFixed(1);
  }).join(' ');
});

const totalUploads30d = computed(() => stats.value?.uploads_30d?.reduce((a, d) => a + d.count, 0) || 0);

// KPIs unificados: si hay stats, vienen de ahí; si no, del fallback
const kpis = computed(() => {
  if (stats.value) return stats.value.totals;
  if (fallbackTotals.value) return {
    media: fallbackTotals.value.media,
    portfolios: fallbackTotals.value.portfolios,
    locations: fallbackTotals.value.locations,
    folders: fallbackTotals.value.folders,
  };
  return null;
});
</script>

<template>
  <div>
    <PullRefresh @refresh="load" />

    <div class="hello card">
      <div>
        <p class="muted small">Hola</p>
        <h2>{{ auth.creds?.username }}</h2>
      </div>
      <ThemeSwitch />
    </div>

    <div v-if="loading" class="loading"><Spinner /> Cargando…</div>

    <template v-else-if="kpis">
      <!-- KPIs principales -->
      <div class="kpis">
        <button class="kpi card" @click="$router.push({ name: 'media' })">
          <span class="kpi-num">{{ kpis.media }}</span>
          <span class="kpi-lbl">Medios</span>
        </button>
        <button class="kpi card" @click="$router.push({ name: 'portfolios' })">
          <span class="kpi-num">{{ kpis.portfolios }}</span>
          <span class="kpi-lbl">Portfolios</span>
        </button>
        <button class="kpi card" @click="$router.push({ name: 'map' })">
          <span class="kpi-num">{{ kpis.locations }}</span>
          <span class="kpi-lbl">Ubicaciones</span>
        </button>
        <button class="kpi card" @click="$router.push({ name: 'folders' })">
          <span class="kpi-num">{{ kpis.folders }}</span>
          <span class="kpi-lbl">Carpetas</span>
        </button>
      </div>

      <!-- Aviso cuando el plugin no expone /stats todavía -->
      <div v-if="statsError" class="card warn-card">
        <span class="card-label">Estadísticas avanzadas no disponibles</span>
        <p class="muted small" style="margin:6px 0 0">
          El plugin <code>yz-media-folders</code> en el servidor no expone aún el endpoint <code>/stats</code>.
          Sube la última versión de <code>class-rest.php</code> para ver almacenamiento, gráficas y salud del catálogo.
        </p>
      </div>

      <template v-if="stats">
        <!-- Almacenamiento -->
        <div class="card stats-card">
          <span class="card-label">Almacenamiento</span>
          <span class="big">{{ stats.totals.storage_h }}</span>
          <span class="muted small">{{ stats.totals.images }} imágenes</span>
        </div>

        <!-- Sparkline subidas -->
        <div class="card stats-card">
          <div class="sparkline-head">
            <span class="card-label">Últimos 30 días</span>
            <span class="muted small">{{ totalUploads30d }} subidas</span>
          </div>
          <svg viewBox="0 0 200 40" preserveAspectRatio="none" class="sparkline">
            <path :d="sparkPath" fill="none" stroke="var(--accent)" stroke-width="1.5" />
          </svg>
          <div class="spark-axis muted small">
            <span>{{ stats.uploads_30d?.[0]?.date }}</span>
            <span>hoy</span>
          </div>
        </div>

        <!-- Top carpetas -->
        <div v-if="stats.top_folders?.length" class="card">
          <span class="card-label">Top carpetas</span>
          <button v-for="f in stats.top_folders" :key="f.id"
            class="top-row"
            @click="$router.push({ name: 'media', query: { folder: f.id } })">
            <span class="muted">📁</span>
            <span class="top-name">{{ f.name }}</span>
            <span class="top-count">{{ f.count }}</span>
          </button>
        </div>

        <!-- Salud del catálogo -->
        <div v-if="stats.health.missing_alt > 0 || stats.health.portfolios_no_thumb > 0" class="card health">
          <span class="card-label">Salud del catálogo</span>
          <div v-if="stats.health.missing_alt > 0" class="health-row">
            <span class="warn-num">{{ stats.health.missing_alt }}</span>
            <span class="health-lbl">imágenes sin alt text</span>
          </div>
          <div v-if="stats.health.portfolios_no_thumb > 0" class="health-row">
            <span class="warn-num">{{ stats.health.portfolios_no_thumb }}</span>
            <span class="health-lbl">portfolios sin imagen destacada</span>
          </div>
        </div>
      </template>

      <!-- Acciones rápidas -->
      <div class="actions">
        <h3>Acciones rápidas</h3>
        <button class="btn pri" @click="$router.push({ name: 'upload' })">↑ Subir fotos</button>
        <button class="btn"     @click="$router.push({ name: 'folders' })">📁 Gestionar carpetas</button>
        <button class="btn"     @click="$router.push({ name: 'portfolio-new' })">◇ Nuevo portfolio</button>
        <button class="btn"     @click="$router.push({ name: 'portfolio-categories' })">📂 Categorías de portfolio</button>
        <button class="btn"     @click="$router.push({ name: 'settings' })">⚙ Ajustes</button>
        <button class="btn ghost danger" @click="logout">Cerrar sesión</button>
      </div>
    </template>
  </div>
</template>

<style scoped>
.hello {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 16px;
}
.small { font-size: 11px; margin: 0 0 2px; }
h2 { margin: 0; font-size: 18px; }
.loading { display: flex; gap: 10px; align-items: center; padding: 24px; justify-content: center; color: var(--text-mute); }

.kpis {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-bottom: 14px;
}
.kpi {
  display: flex; flex-direction: column;
  align-items: flex-start; gap: 4px;
  padding: 14px;
  text-align: left;
  cursor: pointer;
  transition: transform .15s;
}
.kpi:active { transform: scale(.98); }
.kpi-num { font-size: 26px; font-weight: 700; color: var(--accent); line-height: 1; }
.kpi-lbl { font-size: 11px; color: var(--text-mute); text-transform: uppercase; letter-spacing: .5px; }

.warn-card { border-color: var(--info); margin-bottom: 14px; }
.warn-card code { background: var(--s2); padding: 1px 5px; border-radius: 3px; font-size: 11px; }

.stats-card { margin-bottom: 14px; display: flex; flex-direction: column; gap: 4px; }
.card-label { font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: var(--text-mute); font-weight: 600; }
.big { font-size: 24px; font-weight: 700; }

.sparkline-head { display: flex; justify-content: space-between; margin-bottom: 6px; }
.sparkline { width: 100%; height: 40px; }
.spark-axis { display: flex; justify-content: space-between; margin-top: 2px; }

.top-row {
  display: flex; gap: 10px; align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid var(--border);
  text-align: left;
  width: 100%;
}
.top-row:last-of-type { border-bottom: 0; }
.top-name { flex: 1; font-size: 13px; }
.top-count { font-size: 12px; color: var(--accent); font-weight: 600; }

.health { margin-bottom: 14px; }
.health-row {
  display: flex; gap: 10px; align-items: baseline;
  padding: 6px 0;
}
.warn-num { font-size: 18px; font-weight: 700; color: var(--danger); }
.health-lbl { font-size: 13px; }

.actions { display: flex; flex-direction: column; gap: 8px; margin-top: 14px; }
.actions h3 { margin: 4px 0 8px; font-size: 13px; text-transform: uppercase; letter-spacing: .5px; color: var(--text-mute); font-weight: 600; }
.actions .btn { width: 100%; justify-content: flex-start; }

/* En escritorio: layout en 2-3 columnas para aprovechar el espacio */
@media (min-width: 768px) {
  .kpis {
    grid-template-columns: repeat(4, 1fr);
  }
}

@media (min-width: 1024px) {
  .dashboard-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
  }
}
</style>
