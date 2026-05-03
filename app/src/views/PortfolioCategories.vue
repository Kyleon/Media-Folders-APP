<script setup>
import { ref, onMounted, computed } from 'vue';
import { usePortfoliosStore } from '../stores/portfolios';
import { useUiStore } from '../stores/ui';
import { PortfolioCatsAPI } from '../api/endpoints';
import Spinner from '../components/Spinner.vue';

const portfolios = usePortfoliosStore();
const ui         = useUiStore();

const loading       = ref(true);
const creating      = ref(false);
const newName       = ref('');
const newParent     = ref(0);
const renaming      = ref(null);  // { id, name }
const renameValue   = ref('');

onMounted(async () => {
  await portfolios.loadCategories();
  loading.value = false;
});

// Construir árbol jerárquico desde el array plano
const tree = computed(() => {
  const nodes = portfolios.categories.map(c => ({ ...c, children: [] }));
  const byId  = Object.fromEntries(nodes.map(n => [n.id, n]));
  const roots = [];
  for (const n of nodes) {
    if (n.parent && byId[n.parent]) byId[n.parent].children.push(n);
    else                            roots.push(n);
  }
  return roots;
});

function flattenTree(nodes, depth = 0, out = []) {
  for (const n of nodes) {
    out.push({ ...n, depth });
    if (n.children?.length) flattenTree(n.children, depth + 1, out);
  }
  return out;
}
const flat = computed(() => flattenTree(tree.value));

async function startCreate(parentId = 0) {
  newParent.value = parentId;
  newName.value = '';
  creating.value = true;
  setTimeout(() => document.getElementById('cat-new-input')?.focus(), 50);
}

async function confirmCreate() {
  const name = newName.value.trim();
  if (!name) { creating.value = false; return; }
  try {
    await portfolios.createCategory(name, newParent.value);
    ui.toast('✓ Categoría creada', 'ok');
  } catch (e) {
    ui.toast(e.message || 'Error al crear', 'err');
  } finally {
    creating.value = false;
    newName.value = '';
  }
}

function startRename(cat) {
  renaming.value = cat;
  renameValue.value = cat.name;
  setTimeout(() => document.getElementById('cat-rename-input')?.focus(), 50);
}

async function confirmRename() {
  if (!renaming.value) return;
  const name = renameValue.value.trim();
  if (!name || name === renaming.value.name) { renaming.value = null; return; }
  try {
    await PortfolioCatsAPI.update(renaming.value.id, { name });
    await portfolios.loadCategories();
    ui.toast('✏️ Renombrada', 'ok');
  } catch (e) {
    ui.toast(e.message || 'Error', 'err');
  } finally {
    renaming.value = null;
  }
}

async function removeCategory(cat) {
  if (!confirm(`¿Eliminar la categoría "${cat.name}"?\n\nLos portfolios que la tenían se quedarán sin esta categoría.`)) return;
  try {
    await PortfolioCatsAPI.remove(cat.id);
    await portfolios.loadCategories();
    ui.toast('🗑 Eliminada', 'ok');
  } catch (e) {
    ui.toast(e.message || 'Error', 'err');
  }
}
</script>

<template>
  <div>
    <div class="card">
      <div class="head">
        <h3 class="section">Categorías de portfolio</h3>
        <button class="add-btn" @click="startCreate(0)">+ Nueva</button>
      </div>

      <div v-if="loading" class="center muted"><Spinner /> Cargando…</div>

      <div v-else>
        <div v-if="creating && newParent === 0" class="row-line creating">
          <input id="cat-new-input"
            v-model="newName"
            placeholder="Nombre de la categoría…"
            @keydown.enter="confirmCreate"
            @keydown.escape="creating = false"
            @blur="confirmCreate"
            maxlength="80"
            class="inline-input" />
        </div>

        <div v-if="!flat.length && !creating" class="empty muted">
          Sin categorías. Pulsa "+ Nueva" para crear la primera.
        </div>

        <template v-for="cat in flat" :key="cat.id">
          <div class="row-line" :style="{ paddingLeft: (8 + cat.depth * 16) + 'px' }">
            <span class="cat-icon">📂</span>
            <input v-if="renaming?.id === cat.id"
              id="cat-rename-input"
              v-model="renameValue"
              @keydown.enter="confirmRename"
              @keydown.escape="renaming = null"
              @blur="confirmRename"
              maxlength="80"
              class="inline-input" />
            <span v-else class="cat-name">{{ cat.name }}</span>
            <span class="cat-count muted small">{{ cat.count }}</span>

            <div v-if="renaming?.id !== cat.id" class="actions">
              <button @click="startCreate(cat.id)" title="Subcategoría">＋</button>
              <button @click="startRename(cat)"   title="Renombrar">✏</button>
              <button class="danger" @click="removeCategory(cat)" title="Eliminar">🗑</button>
            </div>
          </div>

          <div v-if="creating && newParent === cat.id" class="row-line creating"
            :style="{ paddingLeft: (8 + (cat.depth + 1) * 16) + 'px' }">
            <span class="cat-icon">📂</span>
            <input id="cat-new-input"
              v-model="newName"
              placeholder="Nombre…"
              @keydown.enter="confirmCreate"
              @keydown.escape="creating = false"
              @blur="confirmCreate"
              maxlength="80"
              class="inline-input" />
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<style scoped>
.head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.section { margin: 0; font-size: 12px; text-transform: uppercase; letter-spacing: .5px; color: var(--text-mute); font-weight: 600; }
.add-btn {
  padding: 6px 12px;
  background: var(--accent-lo);
  color: var(--accent);
  border-radius: 16px;
  font-size: 12px;
  font-weight: 500;
}

.center { display: flex; gap: 10px; justify-content: center; padding: 30px; }
.empty { padding: 30px 16px; text-align: center; font-size: 13px; }

.row-line {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 8px;
  border-bottom: 1px solid var(--border);
}
.row-line.creating { background: var(--s2); }
.cat-icon { width: 20px; }
.cat-name { flex: 1; font-size: 14px; }
.cat-count { font-size: 11px; }

.inline-input {
  flex: 1;
  background: transparent;
  border: 0;
  padding: 0;
  font-size: 14px;
  color: var(--text);
}
.inline-input:focus { outline: none; }

.actions { display: flex; gap: 2px; }
.actions button {
  width: 32px; height: 32px;
  color: var(--text-mute);
  font-size: 14px;
  border-radius: var(--radius);
}
.actions button:active { background: var(--s2); }
.actions button.danger { color: var(--danger); }

.small { font-size: 11px; }
</style>
