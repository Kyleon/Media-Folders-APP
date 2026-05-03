<script setup>
import { ref, onMounted, watch } from 'vue';
import { usePortfoliosStore } from '../stores/portfolios';
import { layoutShort } from '../utils/portfolio';
import Spinner from '../components/Spinner.vue';
import PullRefresh from '../components/PullRefresh.vue';

const portfolios = usePortfoliosStore();
const search = ref('');
const cat    = ref(0);

onMounted(async () => {
  await Promise.all([
    portfolios.load(true),
    portfolios.loadCategories(),
  ]);
});

let debounce;
function onSearch() {
  clearTimeout(debounce);
  debounce = setTimeout(() => {
    portfolios.search = search.value;
    portfolios.categoryFilter = cat.value;
    portfolios.load(true);
  }, 300);
}

watch(cat, () => { portfolios.categoryFilter = cat.value; portfolios.load(true); });

function nextPage() {
  if (portfolios.page < portfolios.pages) {
    portfolios.page++;
    portfolios.load();
  }
}
function prevPage() {
  if (portfolios.page > 1) {
    portfolios.page--;
    portfolios.load();
  }
}
</script>

<template>
  <div>
    <PullRefresh @refresh="async () => { await Promise.all([portfolios.load(true), portfolios.loadCategories()]); }" />

    <div class="row" style="margin-bottom:12px">
      <input v-model="search" @input="onSearch" placeholder="Buscar portfolios…" />
      <button class="btn pri" @click="$router.push({ name: 'portfolio-new' })">+ Nuevo</button>
    </div>

    <div class="row" style="margin-bottom:12px">
      <select v-model.number="cat" style="flex:1">
        <option :value="0">Todas las categorías</option>
        <option v-for="c in portfolios.categories" :key="c.id" :value="c.id">
          {{ c.name }} ({{ c.count }})
        </option>
      </select>
      <button class="btn" @click="$router.push({ name: 'portfolio-categories' })" title="Gestionar categorías">📂</button>
    </div>

    <div v-if="portfolios.loading" class="center muted"><Spinner /> Cargando…</div>
    <div v-else-if="!portfolios.items.length" class="empty muted">📭 Sin portfolios</div>

    <div v-else class="list">
      <button v-for="p in portfolios.items" :key="p.id" class="pcard"
        @click="$router.push({ name: 'portfolio-detail', params: { id: p.id } })">
        <div class="pthumb">
          <img v-if="p.hero_url" :src="p.hero_url" :alt="p.title" />
          <div v-else class="pthumb-empty">◇</div>
        </div>
        <div class="pinfo">
          <span class="pname">{{ p.title }}</span>
          <span class="pmeta muted small">
            <span :class="'status ' + p.status">{{ p.status }}</span>
            <span>· {{ layoutShort(p.layout) }}</span>
            <span v-if="p.linked_folder">· 📁 vinculado</span>
          </span>
        </div>
      </button>
    </div>

    <div v-if="portfolios.pages > 1" class="pagination">
      <button class="btn" :disabled="portfolios.page <= 1" @click="prevPage">← Anterior</button>
      <span class="muted">{{ portfolios.page }} / {{ portfolios.pages }}</span>
      <button class="btn" :disabled="portfolios.page >= portfolios.pages" @click="nextPage">Siguiente →</button>
    </div>
  </div>
</template>

<style scoped>
.center { display: flex; gap: 10px; justify-content: center; padding: 30px; }
.empty { text-align: center; padding: 40px 16px; }

.list { display: flex; flex-direction: column; gap: 8px; }
.pcard {
  display: flex; gap: 12px;
  background: var(--s1); border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 8px;
  text-align: left;
  align-items: center;
}
.pcard:active { transform: scale(.98); }
.pthumb {
  width: 64px; height: 64px;
  background: var(--s2);
  border-radius: var(--radius);
  overflow: hidden;
  display: flex; align-items: center; justify-content: center;
  flex: 0 0 64px;
}
.pthumb img { width: 100%; height: 100%; object-fit: cover; }
.pthumb-empty { font-size: 28px; color: var(--text-mute); }
.pinfo { flex: 1; min-width: 0; }
.pname { display: block; font-size: 14px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pmeta { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 2px; font-size: 11px; }
.status { padding: 1px 6px; border-radius: 3px; font-size: 10px; text-transform: uppercase; background: var(--s2); }
.status.publish { background: var(--accent-lo); color: var(--accent); }
.status.draft   { background: var(--s3); }
.small { font-size: 11px; }

.pagination { display: flex; justify-content: space-between; align-items: center; margin-top: 16px; gap: 8px; }
</style>
