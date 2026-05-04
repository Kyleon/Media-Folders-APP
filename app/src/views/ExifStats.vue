<script setup>
import { ref, computed, onMounted } from 'vue';
import { StatsAPI } from '../api/endpoints';
import { useUiStore } from '../stores/ui';
import Spinner from '../components/Spinner.vue';
import PullRefresh from '../components/PullRefresh.vue';

const ui = useUiStore();
const loading = ref(true);
const data = ref(null);

async function load() {
  loading.value = true;
  try {
    data.value = await StatsAPI.exif();
  } catch (e) {
    ui.toast(e.message, 'err');
  } finally {
    loading.value = false;
  }
}

onMounted(load);

function pct(count, max) {
  return max ? (count / max * 100).toFixed(0) + '%' : '0%';
}

const sections = computed(() => [
  { key: 'cameras',   label: 'Cámaras',     emoji: '📷' },
  { key: 'focals',    label: 'Distancia focal', emoji: '🔍' },
  { key: 'apertures', label: 'Aperturas',   emoji: '⊙' },
  { key: 'isos',      label: 'ISO',         emoji: '🌗' },
  { key: 'shutters',  label: 'Velocidades', emoji: '⏱' },
]);
</script>

<template>
  <div>
    <PullRefresh @refresh="load" />

    <div v-if="loading" class="center muted"><Spinner /> Calculando estadísticas…</div>

    <template v-else-if="data">
      <p class="muted small intro">
        Histograma EXIF de <strong>{{ data.total }}</strong> imágenes con metadatos.
      </p>

      <div class="exif-grid">
        <div v-for="sec in sections" :key="sec.key" class="card section">
          <h3>{{ sec.emoji }} {{ sec.label }}</h3>
          <div v-if="!data[sec.key]?.length" class="muted small">Sin datos.</div>
          <div v-else class="bars">
            <div v-for="row in data[sec.key]" :key="row.label" class="bar-row">
              <span class="bar-label">{{ row.label }}</span>
              <div class="bar-track">
                <div class="bar-fill" :style="{ width: pct(row.count, Math.max(...data[sec.key].map(r => r.count))) }"></div>
              </div>
              <span class="bar-count">{{ row.count }}</span>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.center { display: flex; gap: 10px; justify-content: center; padding: 30px; }
.intro { margin: 0 0 14px; }
.small { font-size: 11px; }

.exif-grid { display: flex; flex-direction: column; gap: 14px; }
@media (min-width: 1024px) {
  .exif-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
    gap: 16px;
    align-items: start;
  }
}
@media (min-width: 1800px) { .exif-grid { grid-template-columns: repeat(auto-fill, minmax(420px, 1fr)); } }

.section { margin-bottom: 0; }
.section h3 {
  margin: 0 0 12px;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: .5px;
  color: var(--text-mute);
  font-weight: 600;
}

.bars { display: flex; flex-direction: column; gap: 6px; }
.bar-row { display: flex; align-items: center; gap: 10px; }
.bar-label {
  flex: 0 0 110px;
  font-size: 12px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.bar-track {
  flex: 1;
  background: var(--s2);
  height: 14px;
  border-radius: 7px;
  overflow: hidden;
}
.bar-fill {
  background: var(--accent);
  height: 100%;
  border-radius: 7px;
  transition: width .4s ease-out;
}
.bar-count {
  flex: 0 0 40px;
  text-align: right;
  font-size: 11px;
  color: var(--text-mute);
  font-variant-numeric: tabular-nums;
}
</style>
