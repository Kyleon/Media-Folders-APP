<script setup>
import { ref, onMounted, onActivated, computed } from 'vue';
import { useRouter } from 'vue-router';
import { ClientPortalAPI } from '../api/endpoints';
import { useUiStore } from '../stores/ui';
import Spinner from '../components/Spinner.vue';
import PullRefresh from '../components/PullRefresh.vue';

const router = useRouter();
const ui     = useUiStore();

const items   = ref([]);
const loading = ref(true);
const search  = ref('');
const filter  = ref('all'); // all | active | expired

async function load() {
  loading.value = true;
  try {
    items.value = await ClientPortalAPI.list();
  } catch (e) {
    ui.toast(e.message || 'Error al cargar', 'err');
  } finally {
    loading.value = false;
  }
}

onMounted(load);
onActivated(load);

async function createNew() {
  const title = prompt('Título de la galería (puedes editarlo después):', 'Nueva galería');
  if (title === null) return;
  try {
    const created = await ClientPortalAPI.create({ title: title.trim() || 'Nueva galería' });
    ui.toast('✓ Galería creada', 'ok');
    router.push({ name: 'client-gallery-detail', params: { id: created.id } });
  } catch (e) { ui.toast(e.message || 'Error', 'err'); }
}

async function copyLink(g) {
  try {
    await navigator.clipboard.writeText(g.url);
    ui.toast('📋 Enlace copiado', 'ok');
  } catch { ui.toast('No se pudo copiar', 'err'); }
}

function statusOf(g) {
  if (g.expires && g.expires < Math.floor(Date.now() / 1000)) return 'expired';
  return 'active';
}

const filtered = computed(() => {
  let out = items.value;
  if (filter.value !== 'all') out = out.filter(g => statusOf(g) === filter.value);
  if (search.value.trim()) {
    const s = search.value.trim().toLowerCase();
    out = out.filter(g =>
      (g.title || '').toLowerCase().includes(s) ||
      (g.client_name || '').toLowerCase().includes(s)
    );
  }
  return out;
});

const counts = computed(() => ({
  all:     items.value.length,
  active:  items.value.filter(g => statusOf(g) === 'active').length,
  expired: items.value.filter(g => statusOf(g) === 'expired').length,
}));

function fmtDate(ts) {
  if (!ts) return '—';
  const d = new Date(ts * 1000);
  return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });
}
</script>

<template>
  <div>
    <PullRefresh @refresh="load" />

    <div class="row" style="margin-bottom:12px">
      <input v-model="search" placeholder="Buscar por título o cliente…" />
      <button class="btn pri" @click="createNew">+ Nueva galería</button>
    </div>

    <div class="filters">
      <button class="chip" :class="{ on: filter === 'all' }"     @click="filter = 'all'">Todas <span class="ct">{{ counts.all }}</span></button>
      <button class="chip" :class="{ on: filter === 'active' }"  @click="filter = 'active'">Activas <span class="ct">{{ counts.active }}</span></button>
      <button class="chip" :class="{ on: filter === 'expired' }" @click="filter = 'expired'">Expiradas <span class="ct">{{ counts.expired }}</span></button>
    </div>

    <div v-if="loading" class="center muted"><Spinner /> Cargando…</div>
    <div v-else-if="!filtered.length" class="empty muted">
      <p v-if="!items.length">📭 Aún no hay galerías de cliente.</p>
      <p v-else>Sin resultados con este filtro.</p>
    </div>

    <div v-else class="list">
      <div v-for="g in filtered" :key="g.id" class="gcard"
        :class="{ expired: statusOf(g) === 'expired' }">
        <button class="gcard-main" @click="$router.push({ name: 'client-gallery-detail', params: { id: g.id } })">
          <div class="gtop">
            <span class="gtitle">{{ g.title }}</span>
            <span class="gstatus" :class="statusOf(g)">
              {{ statusOf(g) === 'expired' ? 'Expirada' : 'Activa' }}
            </span>
          </div>
          <div class="gmeta">
            <span v-if="g.client_name">👤 {{ g.client_name }}</span>
            <span>🖼 {{ g.image_count }} fotos</span>
            <span v-if="g.has_password">🔒 Con contraseña</span>
            <span v-if="g.expires">📅 Expira {{ fmtDate(g.expires) }}</span>
            <span>👁 {{ g.views }} visitas</span>
          </div>
        </button>
        <div class="gactions">
          <button class="iconbtn" @click="copyLink(g)" title="Copiar enlace">📋</button>
          <a class="iconbtn" :href="g.url" target="_blank" rel="noopener" title="Abrir">↗</a>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.center { display: flex; gap: 10px; justify-content: center; padding: 30px; }
.empty { text-align: center; padding: 40px 16px; }

.filters { display: flex; gap: 6px; margin-bottom: 14px; flex-wrap: wrap; }
.chip {
  padding: 6px 12px;
  background: var(--s2);
  border-radius: 16px;
  font-size: 12px;
  color: var(--text-mute);
  display: inline-flex; align-items: center; gap: 6px;
}
.chip.on { background: var(--accent-lo); color: var(--accent); }
.ct {
  background: rgba(0,0,0,.25);
  padding: 0 6px;
  border-radius: 8px;
  font-size: 10px;
}

.list { display: flex; flex-direction: column; gap: 8px; }
@media (min-width: 768px) {
  .list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 10px;
  }
}
@media (min-width: 1600px) { .list { grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); } }

.gcard {
  display: flex; align-items: stretch;
  background: var(--s1);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  transition: border-color .12s;
}
.gcard:hover { border-color: var(--accent); }
.gcard.expired { opacity: 0.65; }

.gcard-main {
  flex: 1;
  text-align: left;
  padding: 12px;
  background: transparent;
  cursor: pointer;
  min-width: 0;
}
.gtop { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 6px; }
.gtitle {
  font-size: 14px; font-weight: 500;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  flex: 1;
}
.gstatus {
  flex-shrink: 0;
  font-size: 10px;
  padding: 2px 8px;
  border-radius: 10px;
  text-transform: uppercase;
  letter-spacing: .3px;
}
.gstatus.active  { background: var(--accent-lo); color: var(--accent); }
.gstatus.expired { background: var(--s3); color: var(--text-mute); }

.gmeta {
  display: flex; flex-wrap: wrap; gap: 10px;
  font-size: 11px; color: var(--text-mute);
}

.gactions {
  display: flex; flex-direction: column;
  border-left: 1px solid var(--border);
}
.iconbtn {
  flex: 1;
  width: 44px;
  display: flex; align-items: center; justify-content: center;
  font-size: 14px;
  color: var(--text-mute);
  text-decoration: none;
  background: transparent;
  border: 0;
  cursor: pointer;
  transition: background .12s, color .12s;
}
.iconbtn:hover { background: var(--s2); color: var(--accent); }
.iconbtn + .iconbtn { border-top: 1px solid var(--border); }
</style>
