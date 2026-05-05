<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { StatsAPI } from '../api/endpoints';

const router  = useRouter();
const colors  = ref([]);
const loading = ref(true);

async function load() {
  loading.value = true;
  try {
    const r = await StatsAPI.colors();
    colors.value = (r || []).slice(0, 24);
  } catch (e) {
    colors.value = [];
  } finally {
    loading.value = false;
  }
}

onMounted(load);
defineExpose({ refresh: load });

function pickColor(hex) {
  router.push({ name: 'media', query: { color: hex } });
}

const totalUses = computed(() => colors.value.reduce((a, c) => a + c.count, 0));
</script>

<template>
  <div class="card palette-card">
    <div class="head">
      <span class="card-label">Paleta global</span>
      <span class="muted small" v-if="totalUses">{{ totalUses }} usos · click para filtrar</span>
    </div>

    <div v-if="loading" class="palette-grid">
      <div v-for="n in 24" :key="n" class="swatch skel"></div>
    </div>

    <div v-else-if="!colors.length" class="empty muted small">
      Aún no hay paletas. Las imágenes nuevas se analizan automáticamente.
    </div>

    <div v-else class="palette-grid">
      <button v-for="c in colors" :key="c.color"
        class="swatch"
        :style="{ background: c.color }"
        :title="`${c.color} · ${c.count} fotos`"
        @click="pickColor(c.color)">
      </button>
    </div>
  </div>
</template>

<style scoped>
.head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 6px; }
.card-label { font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: var(--text-mute); font-weight: 600; }
.small { font-size: 11px; }

.palette-grid {
  display: grid;
  grid-template-columns: repeat(8, 1fr);
  gap: 4px;
}
@media (min-width: 1280px) { .palette-grid { grid-template-columns: repeat(12, 1fr); } }

.swatch {
  width: 100%;
  aspect-ratio: 1;
  border-radius: 4px;
  border: 1px solid rgba(255,255,255,.06);
  cursor: pointer;
  transition: transform .12s, box-shadow .12s;
  padding: 0;
}
.swatch:hover { transform: scale(1.15); z-index: 2; box-shadow: 0 2px 8px rgba(0,0,0,.4); }
.swatch.skel { background: var(--s2); cursor: default; animation: skel 1.4s ease-in-out infinite; }
@keyframes skel { 50% { opacity: .6; } }

.empty { padding: 20px; text-align: center; }
</style>
