<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { MediaAPI } from '../api/endpoints';
import Skeleton from './Skeleton.vue';

const router  = useRouter();
const items   = ref([]);
const loading = ref(true);

async function load() {
  loading.value = true;
  try {
    const r = await MediaAPI.list({ orderby: 'date', order: 'DESC', per_page: 12, mime: 'image' });
    items.value = r.images || r.items || r || [];
  } catch (e) {
    items.value = [];
  } finally {
    loading.value = false;
  }
}

defineExpose({ refresh: load });
onMounted(load);

function open(id) {
  router.push({ name: 'media-detail', params: { id } });
}
</script>

<template>
  <div class="card latest-card">
    <div class="head">
      <span class="card-label">Últimas subidas</span>
      <button class="more" @click="$router.push({ name: 'media' })">Ver todas →</button>
    </div>

    <div v-if="loading" class="strip">
      <div v-for="n in 8" :key="n" class="thumb skel">
        <Skeleton variant="thumb" />
      </div>
    </div>

    <div v-else-if="!items.length" class="empty muted small">
      Aún no hay imágenes. Sube tu primera foto.
    </div>

    <div v-else class="strip">
      <button v-for="it in items" :key="it.id"
        class="thumb"
        @click="open(it.id)"
        :title="it.title || it.filename">
        <img :src="it.thumb || it.url" :alt="it.alt || it.title" loading="lazy" />
      </button>
    </div>
  </div>
</template>

<style scoped>
.head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.card-label { font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: var(--text-mute); font-weight: 600; }
.more { font-size: 11px; color: var(--accent); padding: 0; }

.strip {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 6px;
}
@media (min-width: 480px)  { .strip { grid-template-columns: repeat(6, 1fr); } }
@media (min-width: 768px)  { .strip { grid-template-columns: repeat(8, 1fr); } }
@media (min-width: 1280px) { .strip { grid-template-columns: repeat(12, 1fr); } }

.thumb {
  position: relative;
  aspect-ratio: 1;
  border-radius: var(--radius);
  overflow: hidden;
  background: var(--s2);
  padding: 0;
  cursor: pointer;
}
.thumb.skel { cursor: default; }
.thumb img {
  width: 100%; height: 100%;
  object-fit: cover;
  display: block;
  transition: transform .25s ease-out;
}
@media (hover: hover) {
  .thumb:hover img { transform: scale(1.06); }
}

.empty { padding: 20px; text-align: center; }
.small { font-size: 11px; }
</style>
