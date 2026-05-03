<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { usePortfoliosStore } from '../stores/portfolios';
import { useFoldersStore } from '../stores/folders';
import { useUiStore } from '../stores/ui';
import Spinner from '../components/Spinner.vue';
import { PORTFOLIO_LAYOUTS } from '../utils/portfolio';

const router     = useRouter();
const portfolios = usePortfoliosStore();
const folders    = useFoldersStore();
const ui         = useUiStore();

const form = ref({
  title:   '',
  excerpt: '',
  status:  'draft',
  layout:  'st1',
  categories: [],
  linked_folder: 0,
});
const syncFolderAfter = ref(true);
const saving = ref(false);

onMounted(() => {
  portfolios.loadCategories();
  folders.load();
});

async function create() {
  if (!form.value.title.trim()) {
    ui.toast('El título es obligatorio', 'err'); return;
  }
  saving.value = true;
  try {
    const created = await portfolios.create({ ...form.value });
    if (syncFolderAfter.value && form.value.linked_folder > 0) {
      await portfolios.syncFolder(created.id, form.value.linked_folder, 'date', 'ASC');
      ui.toast('✓ Portfolio creado y galería sincronizada', 'ok');
    } else {
      ui.toast('✓ Portfolio creado', 'ok');
    }
    router.replace({ name: 'portfolio-detail', params: { id: created.id } });
  } catch (e) {
    ui.toast(e.message, 'err');
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <div class="card">
    <div class="field">
      <label>Título *</label>
      <input v-model="form.title" placeholder="Sesión Galicia, Otoño…" />
    </div>
    <div class="field">
      <label>Resumen</label>
      <textarea v-model="form.excerpt" rows="2"></textarea>
    </div>
    <div class="field">
      <label>Layout</label>
      <select v-model="form.layout">
        <option v-for="l in PORTFOLIO_LAYOUTS" :key="l.code" :value="l.code">
          {{ l.label }}
        </option>
      </select>
    </div>
    <div class="field">
      <label>Estado</label>
      <select v-model="form.status">
        <option value="draft">Borrador</option>
        <option value="publish">Publicar</option>
        <option value="private">Privado</option>
      </select>
    </div>
    <div class="field">
      <label>Categorías</label>
      <div class="checks">
        <label v-for="c in portfolios.categories" :key="c.id" class="cat-check">
          <input type="checkbox" :value="c.id" v-model="form.categories" />
          <span>{{ c.name }}</span>
        </label>
        <p v-if="!portfolios.categories.length" class="muted small">Sin categorías</p>
      </div>
    </div>
    <div class="field">
      <label>Vincular carpeta YZMF como galería</label>
      <select v-model.number="form.linked_folder">
        <option :value="0">— Ninguna —</option>
        <option v-for="f in folders.flat" :key="f.id" :value="f.id">
          {{ '— '.repeat(f.depth) }}{{ f.name }} ({{ f.count }})
        </option>
      </select>
    </div>
    <label v-if="form.linked_folder > 0" class="cat-check" style="margin-top:6px">
      <input type="checkbox" v-model="syncFolderAfter" />
      <span>Sincronizar imágenes de la carpeta automáticamente</span>
    </label>

    <button class="btn pri" :disabled="saving" @click="create" style="width:100%;margin-top:14px">
      <Spinner v-if="saving" :size="14" />
      <span v-else>Crear portfolio</span>
    </button>
  </div>
</template>

<style scoped>
.checks { display: flex; flex-wrap: wrap; gap: 6px; }
.cat-check {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 6px 10px;
  background: var(--s2);
  border-radius: 16px;
  font-size: 13px;
}
.cat-check input { width: auto; }
.small { font-size: 11px; }
</style>
