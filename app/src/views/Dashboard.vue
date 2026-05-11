<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useFoldersStore } from '../stores/folders';
import { StatsAPI, MediaAPI, PortfoliosAPI, MapAPI } from '../api/endpoints';
import ThemeSwitch from '../components/ThemeSwitch.vue';
import Spinner from '../components/Spinner.vue';
import PullRefresh from '../components/PullRefresh.vue';
import DashLatest from '../components/DashLatest.vue';
import DashMiniMap from '../components/DashMiniMap.vue';
import DashHeatmap from '../components/DashHeatmap.vue';
import DashPalette from '../components/DashPalette.vue';

const router  = useRouter();
const auth    = useAuthStore();
const folders = useFoldersStore();

const loading        = ref(true);
const stats          = ref(null);
const statsError     = ref(false);
const fallbackTotals = ref(null);
const topTags        = ref([]);

// Refs hijos para refrescar al pull-to-refresh
const latestRef  = ref(null);
const miniMapRef = ref(null);
const paletteRef = ref(null);

async function load(fresh = false) {
  loading.value = true;
  statsError.value = false;
  try {
    const [s, t] = await Promise.all([
      StatsAPI.get({ fresh }),
      StatsAPI.tags().catch(() => []),
    ]);
    stats.value = s;
    topTags.value = (t || []).slice(0, 12);
  } catch (e) {
    console.warn('Stats endpoint no disponible, usando fallback', e?.message);
    statsError.value = true;
    stats.value = null;
    await loadFallback();
  } finally {
    loading.value = false;
  }
}

async function refreshAll() {
  await load(true); // bypass del cache server-side
  latestRef.value?.refresh?.();
  miniMapRef.value?.refresh?.();
  paletteRef.value?.refresh?.();
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

// Health score: media de cuántos campos críticos tienen las imágenes (0-100)
const healthScore = computed(() => {
  if (!stats.value?.totals?.images) return null;
  const total = stats.value.totals.images;
  if (!total) return null;
  const h = stats.value.health || {};
  const parts = [
    1 - (h.missing_alt    || 0) / total,
    1 - (h.missing_geo    || 0) / total,
    1 - (h.missing_tags   || 0) / total,
    1 - (h.missing_folder || 0) / total,
  ];
  const avg = parts.reduce((a, x) => a + Math.max(0, x), 0) / parts.length;
  return Math.round(avg * 100);
});

const healthRing = computed(() => {
  const v = healthScore.value;
  if (v === null) return { dash: '0 100', color: 'var(--text-mute)' };
  const color = v >= 80 ? 'var(--ok)' : v >= 50 ? 'var(--info)' : v >= 30 ? 'var(--accent)' : 'var(--danger)';
  return { dash: `${v} ${100 - v}`, color };
});

// Tareas pendientes (cards-chip clicables)
const tasks = computed(() => {
  if (!stats.value) return [];
  const h = stats.value.health || {};
  const out = [];
  if (h.missing_alt > 0) out.push({
    label: 'Sin alt text', count: h.missing_alt, icon: '📝',
    to: { name: 'media', query: { search: '__NO_ALT__', mime: 'image' } },
  });
  if (h.missing_tags > 0) out.push({
    label: 'Sin etiquetas IA', count: h.missing_tags, icon: '🏷',
    to: { name: 'media' }, // sin filtro específico aún
    hint: 'Genera con IA al abrir',
  });
  if (h.missing_geo > 0) out.push({
    label: 'Sin geolocalización', count: h.missing_geo, icon: '📍',
    to: { name: 'media' },
  });
  if (h.missing_folder > 0) out.push({
    label: 'Sin carpeta', count: h.missing_folder, icon: '📭',
    to: { name: 'media', query: { folder: 0 } },
  });
  if (h.portfolios_no_thumb > 0) out.push({
    label: 'Portfolios sin destacada', count: h.portfolios_no_thumb, icon: '🖼',
    to: { name: 'portfolios' },
  });
  return out;
});

const totalUploads30d = computed(() => stats.value?.uploads_30d?.reduce((a, d) => a + d.count, 0) || 0);
</script>

<template>
  <div>
    <PullRefresh @refresh="refreshAll" />

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
          Sube la última versión de <code>class-rest.php</code>.
        </p>
      </div>

      <div class="dash-grid">
        <!-- 1. Últimas subidas — full-width -->
        <DashLatest ref="latestRef" class="span-full" />

        <!-- 2. Heatmap calendario — full-width -->
        <DashHeatmap v-if="stats?.uploads_365d?.length"
          :data="stats.uploads_365d"
          class="span-full" />

        <template v-if="stats">
          <!-- 3. Health score + tareas pendientes -->
          <div class="card health-card" v-if="healthScore !== null || tasks.length">
            <div class="hc-head">
              <span class="card-label">Salud del catálogo</span>
              <div class="ring-wrap" v-if="healthScore !== null">
                <svg viewBox="0 0 36 36" class="ring">
                  <circle cx="18" cy="18" r="15.9155" stroke="var(--s2)" stroke-width="3" fill="none" />
                  <circle cx="18" cy="18" r="15.9155"
                    :stroke="healthRing.color" stroke-width="3" fill="none"
                    :stroke-dasharray="healthRing.dash"
                    stroke-dashoffset="25"
                    stroke-linecap="round" />
                </svg>
                <span class="ring-num" :style="{ color: healthRing.color }">{{ healthScore }}%</span>
              </div>
            </div>

            <div v-if="tasks.length" class="tasks-list">
              <button v-for="t in tasks" :key="t.label"
                class="task-chip"
                @click="$router.push(t.to)"
                :title="t.hint || ''">
                <span class="task-icon">{{ t.icon }}</span>
                <span class="task-lbl">{{ t.label }}</span>
                <span class="task-num">{{ t.count }}</span>
              </button>
            </div>
            <p v-else class="muted small ok-msg">✓ Sin tareas pendientes. Catálogo impecable.</p>
          </div>

          <!-- 4. Mini-mapa -->
          <DashMiniMap ref="miniMapRef" />

          <!-- 5. Almacenamiento -->
          <div class="card stats-card">
            <span class="card-label">Almacenamiento</span>
            <span class="big">{{ stats.totals.storage_h }}</span>
            <span class="muted small">{{ stats.totals.images }} imágenes · {{ totalUploads30d }} en 30d</span>
          </div>

          <!-- 6. Top carpetas -->
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

          <!-- 7. Top tags IA -->
          <div v-if="topTags.length" class="card">
            <span class="card-label">Etiquetas más usadas</span>
            <div class="tags-cloud">
              <button v-for="t in topTags" :key="t.tag"
                class="tag-chip"
                @click="$router.push({ name: 'media', query: { tag: t.tag } })">
                🏷 {{ t.tag }}
                <span class="tag-count">{{ t.count }}</span>
              </button>
            </div>
          </div>
        </template>

        <!-- 8. Acciones rápidas -->
        <div class="card actions-card">
          <span class="card-label">Acciones rápidas</span>
          <div class="actions">
            <button class="btn pri" @click="$router.push({ name: 'upload' })">↑ Subir fotos</button>
            <button class="btn"     @click="$router.push({ name: 'folders' })">📁 Gestionar carpetas</button>
            <button class="btn"     @click="$router.push({ name: 'portfolio-new' })">◇ Nuevo portfolio</button>
            <button class="btn"     @click="$router.push({ name: 'portfolio-categories' })">📂 Categorías</button>
            <button class="btn"     @click="$router.push({ name: 'client-galleries' })">🔐 Galerías de cliente</button>
            <button class="btn"     @click="$router.push({ name: 'exif' })">📊 Estadísticas EXIF</button>
            <button class="btn"     @click="$router.push({ name: 'settings' })">⚙ Ajustes</button>
            <button class="btn ghost danger" @click="logout">Cerrar sesión</button>
          </div>
        </div>

        <!-- 9. Paleta global — full-width -->
        <DashPalette ref="paletteRef" class="span-full" />
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

.card-label { font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: var(--text-mute); font-weight: 600; }
.big { font-size: 24px; font-weight: 700; }
.stats-card { display: flex; flex-direction: column; gap: 4px; }

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

/* Health card */
.health-card { display: flex; flex-direction: column; gap: 10px; }
.hc-head { display: flex; justify-content: space-between; align-items: center; }
.ring-wrap { position: relative; width: 56px; height: 56px; }
.ring { width: 56px; height: 56px; transform: rotate(-90deg); }
.ring-num {
  position: absolute; inset: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 700;
}
.tasks-list { display: flex; flex-direction: column; gap: 6px; }
.task-chip {
  display: flex; align-items: center; gap: 10px;
  padding: 8px 12px;
  background: var(--s2);
  border-radius: var(--radius);
  text-align: left;
  width: 100%;
  transition: background .12s, border-color .12s;
  border: 1px solid transparent;
}
.task-chip:hover { border-color: var(--accent); background: var(--s1); }
.task-icon { font-size: 16px; }
.task-lbl  { flex: 1; font-size: 13px; }
.task-num  { font-size: 13px; font-weight: 700; color: var(--danger); font-variant-numeric: tabular-nums; }
.ok-msg { color: var(--ok); margin: 4px 0 0; }

/* Tags cloud */
.tags-cloud { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px; }
.tag-chip {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 4px 10px;
  background: var(--accent-lo);
  color: var(--accent);
  border-radius: 12px;
  font-size: 11px;
  font-weight: 500;
  transition: transform .12s;
}
.tag-chip:hover { transform: translateY(-1px); }
.tag-count {
  background: rgba(0,0,0,.25);
  padding: 0 6px;
  border-radius: 8px;
  font-size: 10px;
}

.actions-card { display: flex; flex-direction: column; gap: 10px; }
.actions { display: flex; flex-direction: column; gap: 8px; }
.actions .btn { width: 100%; justify-content: flex-start; }

.dash-grid { display: flex; flex-direction: column; gap: 14px; }

/* Tablet en adelante: rejilla con cards que pueden hacer span-full */
@media (min-width: 768px) {
  .kpis { grid-template-columns: repeat(4, 1fr); }
  .dash-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
    align-items: start;
  }
  .span-full { grid-column: 1 / -1; }
}
@media (min-width: 1280px) {
  .dash-grid { grid-template-columns: repeat(3, 1fr); gap: 16px; }
}
@media (min-width: 1800px) {
  .dash-grid { grid-template-columns: repeat(4, 1fr); }
}
@media (min-width: 2400px) {
  .dash-grid { grid-template-columns: repeat(5, 1fr); }
}
</style>
