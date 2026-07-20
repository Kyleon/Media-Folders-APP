<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useFeedStore } from '../stores/feed';
import Spinner from '../components/Spinner.vue';
import EmptyState from '../components/EmptyState.vue';

/**
 * Feed (lista). Rejilla de publicaciones con scroll infinito, filtro por
 * hashtag (nube de chips) y búsqueda de texto. FAB para crear.
 */
const router = useRouter();
const feed   = useFeedStore();

const sentinel = ref(null);
let observer   = null;
let debounce;

const hasFilters = computed(() =>
  feed.filter.search !== '' || feed.filter.hashtag !== '' || feed.filter.status !== '');

onMounted(async () => {
  await feed.load(true);
  feed.loadHashtags();
  await nextTick();
  setupObserver();
});

onBeforeUnmount(() => teardownObserver());

function setupObserver() {
  teardownObserver();
  if (!sentinel.value) return;
  observer = new IntersectionObserver((entries) => {
    if (entries.some(e => e.isIntersecting)) feed.loadMore();
  }, { rootMargin: '400px 0px', threshold: 0 });
  observer.observe(sentinel.value);
}
function teardownObserver() {
  if (observer) { observer.disconnect(); observer = null; }
}

function onSearch() {
  clearTimeout(debounce);
  debounce = setTimeout(() => feed.load(true), 300);
}

function toggleHashtag(slug) {
  feed.setFilter({ hashtag: feed.filter.hashtag === slug ? '' : slug });
  feed.load(true);
}

function setStatus(status) {
  feed.setFilter({ status });
  feed.load(true);
}

function clearFilters() {
  feed.setFilter({ search: '', hashtag: '', status: '' });
  feed.load(true);
}

function open(id)  { router.push({ name: 'feed-detail', params: { id } }); }
function create()  { router.push({ name: 'feed-new' }); }

function coverUrl(post) {
  return post.cover?.medium || post.cover?.thumb || '';
}
</script>

<template>
  <div class="feed-view">
    <!-- Filtros -->
    <div class="toolbar">
      <input
        v-model="feed.filter.search"
        @input="onSearch"
        placeholder="Buscar en el feed…"
        class="search"
        aria-label="Buscar publicaciones" />
      <div class="status-tabs">
        <button :class="{ on: feed.filter.status === '' }" @click="setStatus('')">Todo</button>
        <button :class="{ on: feed.filter.status === 'publish' }" @click="setStatus('publish')">Publicado</button>
        <button :class="{ on: feed.filter.status === 'draft' }" @click="setStatus('draft')">Borrador</button>
      </div>
    </div>

    <div v-if="feed.hashtags.length" class="tag-cloud">
      <button
        v-for="h in feed.hashtags"
        :key="h.slug"
        class="tag-chip"
        :class="{ on: feed.filter.hashtag === h.slug }"
        @click="toggleHashtag(h.slug)">
        #{{ h.tag }} <span class="count">{{ h.count }}</span>
      </button>
    </div>

    <!-- Grid -->
    <div v-if="feed.loading && !feed.items.length" class="center"><Spinner /> Cargando…</div>

    <EmptyState
      v-else-if="!feed.items.length"
      icon="📸"
      title="Sin publicaciones"
      :description="hasFilters ? 'Ninguna coincide con el filtro.' : 'Crea tu primera publicación con fotos, texto y hashtags.'"
      :actionLabel="hasFilters ? 'Quitar filtros' : 'Nueva publicación'"
      @action="hasFilters ? clearFilters() : create()" />

    <div v-else class="grid">
      <button v-for="post in feed.items" :key="post.id" class="card" @click="open(post.id)">
        <div class="thumb">
          <img v-if="coverUrl(post)" :src="coverUrl(post)" :alt="post.cover?.alt || ''" loading="lazy" />
          <div v-else class="thumb-empty">Sin foto</div>
          <span v-if="post.photos_count > 1" class="badge-multi" aria-label="Varias fotos">▣ {{ post.photos_count }}</span>
          <span class="badge-status" :class="post.status">{{ post.status === 'publish' ? 'Publicado' : 'Borrador' }}</span>
        </div>
        <div class="info">
          <p class="caption">{{ post.caption || '—' }}</p>
          <div class="counters">
            <span>❤ {{ post.likes }}</span>
            <span>💬 {{ post.comment_count }}</span>
          </div>
        </div>
      </button>
    </div>

    <div ref="sentinel" class="sentinel" aria-hidden="true">
      <Spinner v-if="feed.loading && feed.items.length" :size="14" />
      <span v-else-if="feed.page < feed.pages" class="muted small">Cargando más…</span>
      <span v-else-if="feed.items.length" class="muted small">{{ feed.items.length }} / {{ feed.total }}</span>
    </div>

    <!-- FAB crear -->
    <button class="fab" @click="create" aria-label="Nueva publicación">+</button>
  </div>
</template>

<style scoped>
.feed-view { position: relative; }

.toolbar { display: flex; flex-direction: column; gap: 10px; margin-bottom: 12px; }
.search { background: var(--s2); }
.status-tabs { display: flex; gap: 6px; }
.status-tabs button {
  flex: 1;
  padding: 8px 10px;
  border-radius: var(--radius);
  background: var(--s2);
  border: 1px solid var(--border);
  color: var(--text-mute);
  font-size: 13px;
}
.status-tabs button.on { background: var(--accent-lo); color: var(--accent); border-color: var(--accent); }

.tag-cloud { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 14px; }
.tag-chip {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 5px 10px;
  border-radius: 16px;
  background: var(--s2);
  border: 1px solid var(--border);
  color: var(--text-mute);
  font-size: 12px;
}
.tag-chip.on { background: var(--accent-lo); color: var(--accent); border-color: var(--accent); }
.tag-chip .count { opacity: .6; font-size: 11px; }

.center { display: flex; gap: 10px; justify-content: center; padding: 40px; color: var(--text-mute); }

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 10px;
}
@media (min-width: 768px) {
  .grid { grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); }
}

.card {
  display: flex;
  flex-direction: column;
  text-align: left;
  background: var(--s1);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  transition: border-color .15s, transform .12s;
}
.card:hover { border-color: var(--accent); }
.card:active { transform: scale(.98); }

.thumb {
  position: relative;
  width: 100%;
  aspect-ratio: 1 / 1;
  background: var(--s2);
}
.thumb img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.thumb-empty {
  position: absolute; inset: 0;
  display: flex; align-items: center; justify-content: center;
  color: var(--dim); font-size: 12px;
}
.badge-multi {
  position: absolute; top: 8px; right: 8px;
  background: rgba(0,0,0,.55); color: #fff;
  font-size: 11px; padding: 2px 6px; border-radius: 10px;
}
.badge-status {
  position: absolute; top: 8px; left: 8px;
  font-size: 10px; padding: 2px 7px; border-radius: 10px;
  text-transform: uppercase; letter-spacing: .3px; font-weight: 600;
}
.badge-status.publish { background: var(--ok); color: #04120a; }
.badge-status.draft   { background: var(--s3); color: var(--text-mute); }

.info { padding: 8px 10px 10px; display: flex; flex-direction: column; gap: 6px; }
.caption {
  margin: 0; font-size: 13px; line-height: 1.35; color: var(--text);
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.counters { display: flex; gap: 12px; font-size: 12px; color: var(--text-mute); }

.sentinel { display: flex; justify-content: center; align-items: center; gap: 8px; padding: 18px; min-height: 40px; }
.small { font-size: 11px; }

.fab {
  position: fixed;
  right: 18px;
  bottom: calc(72px + env(safe-area-inset-bottom));
  width: 56px; height: 56px;
  border-radius: 50%;
  background: var(--accent); color: #0f0f0f;
  font-size: 30px; line-height: 1;
  box-shadow: var(--shadow);
  z-index: 40;
}
@media (min-width: 1024px) {
  .fab { right: 32px; bottom: 32px; }
}
</style>
