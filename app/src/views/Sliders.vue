<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useSlidersStore } from '../stores/sliders';
import { useUiStore } from '../stores/ui';
import Spinner from '../components/Spinner.vue';
import PullRefresh from '../components/PullRefresh.vue';

const sliders = useSlidersStore();
const ui = useUiStore();
const router = useRouter();
const creating = ref(false);

onMounted(async () => {
  await sliders.fetchAll();
});

async function newSlider() {
  if (creating.value) return;
  creating.value = true;
  try {
    const created = await sliders.create('Nuevo slider');
    router.push({ name: 'slider-detail', params: { id: created.id } });
  } catch (e) {
    ui.toast('No se pudo crear el slider', 'err');
  } finally {
    creating.value = false;
  }
}

async function duplicate(id, e) {
  e.stopPropagation();
  try {
    await sliders.duplicate(id);
    ui.toast('Slider duplicado', 'ok');
  } catch {
    ui.toast('No se pudo duplicar', 'err');
  }
}

async function remove(slider, e) {
  e.stopPropagation();
  if (!confirm(`¿Eliminar "${slider.title}"?`)) return;
  try {
    await sliders.remove(slider.id);
    ui.toast('Slider eliminado', 'ok');
  } catch {
    ui.toast('No se pudo eliminar', 'err');
  }
}

function formatDate(iso) {
  if (!iso) return '';
  const d = new Date(iso);
  return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
}
</script>

<template>
  <div>
    <PullRefresh @refresh="() => sliders.fetchAll()" />

    <div class="row" style="margin-bottom:12px">
      <h2 style="margin:0; flex:1">Sliders</h2>
      <button class="btn pri" :disabled="creating" @click="newSlider">
        + Nuevo
      </button>
    </div>

    <div v-if="sliders.loading" class="center muted"><Spinner /> Cargando…</div>
    <div v-else-if="!sliders.items.length" class="empty muted">
      📭 No hay sliders todavía.<br />
      <button class="btn pri" style="margin-top:14px" @click="newSlider">Crear el primero</button>
    </div>

    <div v-else class="list">
      <div
        v-for="s in sliders.items"
        :key="s.id"
        class="scard"
        @click="router.push({ name: 'slider-detail', params: { id: s.id } })"
      >
        <div class="sthumb">
          <img v-if="s.thumbnail" :src="s.thumbnail" :alt="s.title" />
          <div v-else class="sthumb-empty">▦</div>
        </div>
        <div class="sinfo">
          <span class="sname">{{ s.title }}</span>
          <span class="smeta muted small">
            <span :class="'status ' + s.status">{{ s.status }}</span>
            <span>· {{ s.slides_count }} slide{{ s.slides_count === 1 ? '' : 's' }}</span>
            <span>· {{ formatDate(s.modified) }}</span>
          </span>
        </div>
        <div class="sactions" @click.stop>
          <button class="iconbtn" title="Duplicar" @click="duplicate(s.id, $event)">⎘</button>
          <button class="iconbtn danger" title="Eliminar" @click="remove(s, $event)">✕</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.center { display: flex; gap: 10px; justify-content: center; padding: 30px; }
.empty { text-align: center; padding: 40px 16px; }

.list { display: flex; flex-direction: column; gap: 8px; }

@media (min-width: 768px) {
  .list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 10px;
  }
}

.scard {
  display: flex;
  gap: 12px;
  background: var(--s1);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 8px;
  align-items: center;
  cursor: pointer;
  transition: border-color .15s, transform .12s;
}
@media (hover: hover) {
  .scard:hover { border-color: var(--accent); transform: translateY(-1px); }
}
.scard:active { transform: scale(.98); }

.sthumb {
  width: 80px; height: 56px;
  background: var(--s2);
  border-radius: var(--radius);
  overflow: hidden;
  display: flex; align-items: center; justify-content: center;
  flex: 0 0 80px;
}
.sthumb img { width: 100%; height: 100%; object-fit: cover; }
.sthumb-empty { font-size: 28px; color: var(--text-mute); }

.sinfo { flex: 1; min-width: 0; }
.sname {
  display: block;
  font-size: 14px;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.smeta { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 2px; font-size: 11px; }
.status { padding: 1px 6px; border-radius: 3px; font-size: 10px; text-transform: uppercase; background: var(--s2); }
.status.publish { background: var(--accent-lo); color: var(--accent); }
.status.draft, .status.trash { background: var(--s3); }
.small { font-size: 11px; }

.sactions { display: flex; gap: 4px; }
.iconbtn {
  background: transparent;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  width: 32px; height: 32px;
  cursor: pointer;
  color: var(--text);
  font-size: 14px;
  transition: background .15s, border-color .15s;
}
.iconbtn:hover { background: var(--s2); border-color: var(--accent); }
.iconbtn.danger:hover { border-color: #c44; color: #c44; }
</style>
