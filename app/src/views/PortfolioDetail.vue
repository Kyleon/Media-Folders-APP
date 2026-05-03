<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { usePortfoliosStore } from '../stores/portfolios';
import { useFoldersStore } from '../stores/folders';
import { useUiStore } from '../stores/ui';
import { PortfoliosAPI } from '../api/endpoints';
import Spinner from '../components/Spinner.vue';
import draggable from 'vuedraggable';
import MediaPicker from '../components/MediaPicker.vue';
import PortfolioMetaForm from '../components/PortfolioMetaForm.vue';
import { PORTFOLIO_LAYOUTS } from '../utils/portfolio';

const props = defineProps({ id: { type: [String, Number], required: true } });
const router     = useRouter();
const portfolios = usePortfoliosStore();
const folders    = useFoldersStore();
const ui         = useUiStore();

const item    = ref(null);
const loading = ref(true);
const saving  = ref(false);
const syncing = ref(false);
const reordering = ref(false);
const galleryDirty = ref(false);
const showAddPicker = ref(false);
const showHeroPicker = ref(false);
const showAdvanced  = ref(false);
const duplicating   = ref(false);

const form = ref({
  title: '', excerpt: '', status: 'draft', layout: 'st1',
  categories: [], linked_folder: 0,
});
const galleryItems = ref([]);

onMounted(async () => {
  await Promise.all([portfolios.loadCategories(), folders.load()]);
  await reload();
});

async function reload() {
  loading.value = true;
  try {
    const detail = await portfolios.detail(props.id);
    item.value = detail;
    form.value = {
      title: detail.title,
      excerpt: detail.excerpt || '',
      status: detail.status,
      layout: detail.layout,
      categories: detail.categories.map(c => c.id),
      linked_folder: detail.linked_folder || 0,
    };
    // Cargar galería con thumbs (endpoint dedicado que devuelve URLs de imagen)
    const gall = await PortfoliosAPI.gallery(props.id);
    galleryItems.value = gall.gallery || [];
  } catch (e) {
    ui.toast(e.message, 'err');
    router.back();
  } finally {
    loading.value = false;
  }
}

async function save() {
  saving.value = true;
  try {
    await portfolios.update(props.id, { ...form.value });
    ui.toast('✓ Guardado', 'ok');
    await reload();
  } catch (e) {
    ui.toast(e.message, 'err');
  } finally {
    saving.value = false;
  }
}

async function syncFromFolder() {
  if (!form.value.linked_folder) {
    ui.toast('Selecciona una carpeta primero', 'err'); return;
  }
  if (!confirm('Esto reemplazará la galería actual con las imágenes de la carpeta. ¿Continuar?')) return;
  syncing.value = true;
  try {
    await portfolios.syncFolder(props.id, form.value.linked_folder, 'date', 'ASC');
    ui.toast('✓ Galería sincronizada', 'ok');
    await reload();
  } catch (e) {
    ui.toast(e.message, 'err');
  } finally {
    syncing.value = false;
  }
}

function onDragEnd() {
  galleryDirty.value = true;
}

async function saveOrder() {
  reordering.value = true;
  try {
    const ids = galleryItems.value.map(g => g.id);
    await PortfoliosAPI.setGallery(props.id, ids);
    ui.toast('✓ Orden guardado', 'ok');
    galleryDirty.value = false;
  } catch (e) {
    ui.toast(e.message, 'err');
  } finally {
    reordering.value = false;
  }
}

async function removeFromGallery(id) {
  if (!confirm('¿Quitar esta imagen de la galería?')) return;
  galleryItems.value = galleryItems.value.filter(g => g.id !== id);
  galleryDirty.value = true;
}

function onAddImages(picked) {
  // picked es array de objetos image (multiple=true)
  const newOnes = picked.filter(p => !galleryItems.value.some(g => g.id === p.id));
  newOnes.forEach(p => {
    galleryItems.value.push({
      id: p.id,
      thumb: p.thumb,
      medium: p.medium,
      url: p.url,
      title: p.title,
      alt: p.alt,
      caption: p.caption,
    });
  });
  if (newOnes.length) {
    galleryDirty.value = true;
    ui.toast(`+ ${newOnes.length} añadidas (recuerda guardar el orden)`, 'ok');
  }
}

function onPickHero(picked) {
  // picked es objeto único
  setHero(picked.id);
}

async function setHero(attId) {
  try {
    await PortfoliosAPI.update(props.id, { hero_id: attId });
    item.value.hero_id  = attId;
    const g = galleryItems.value.find(x => x.id === attId);
    item.value.hero_url = g?.medium || g?.thumb || '';
    ui.toast('★ Imagen destacada actualizada', 'ok');
  } catch (e) {
    ui.toast(e.message, 'err');
  }
}

async function clearHero() {
  if (!confirm('¿Quitar la imagen destacada? Volverá a usarse la primera de la galería.')) return;
  try {
    await PortfoliosAPI.update(props.id, { hero_id: 0 });
    item.value.hero_id = 0;
    item.value.hero_url = galleryItems.value[0]?.medium || galleryItems.value[0]?.thumb || '';
    ui.toast('Imagen destacada quitada', 'ok');
  } catch (e) {
    ui.toast(e.message, 'err');
  }
}

async function duplicate() {
  const newTitle = prompt('Título para la copia:', item.value.title + ' (copia)');
  if (newTitle === null) return; // cancelado
  const includeGallery = confirm('¿Copiar también la galería de imágenes?\n\nOK = sí · Cancelar = sólo configuración (galería vacía)');
  duplicating.value = true;
  try {
    const created = await PortfoliosAPI.duplicate(props.id, {
      title: newTitle.trim() || (item.value.title + ' (copia)'),
      include_gallery: includeGallery,
    });
    ui.toast('✓ Plantilla creada', 'ok');
    router.push({ name: 'portfolio-detail', params: { id: created.id } });
  } catch (e) {
    ui.toast(e.message, 'err');
  } finally {
    duplicating.value = false;
  }
}

async function remove() {
  if (!confirm('¿Mover este portfolio a la papelera?')) return;
  try {
    await portfolios.remove(props.id, false);
    ui.toast('🗑 Eliminado', 'ok');
    router.replace({ name: 'portfolios' });
  } catch (e) {
    ui.toast(e.message, 'err');
  }
}
</script>

<template>
  <div v-if="loading" class="center muted"><Spinner /> Cargando…</div>

  <div v-else-if="item">
    <div v-if="item.hero_url" class="hero">
      <img :src="item.hero_url" :alt="item.title" />
      <div class="hero-badge" :class="{ fallback: !item.hero_id }">
        <span v-if="item.hero_id">★ Imagen destacada</span>
        <span v-else>🖼 Primera de la galería (sin destacada)</span>
      </div>
      <div class="hero-actions">
        <button class="hero-btn" @click="showHeroPicker = true">Cambiar</button>
        <button v-if="item.hero_id" class="hero-btn" @click="clearHero">Quitar</button>
      </div>
    </div>
    <div v-else class="hero hero-empty">
      <span class="muted">Sin imagen destacada</span>
      <button class="btn pri" style="margin-top:10px" @click="showHeroPicker = true">★ Elegir destacada</button>
    </div>

    <div class="card">
      <div class="field">
        <label>Título</label>
        <input v-model="form.title" />
      </div>
      <div class="field">
        <label>Resumen</label>
        <textarea v-model="form.excerpt" rows="2"></textarea>
      </div>
      <div class="row">
        <div class="field" style="flex:1">
          <label>Estado</label>
          <select v-model="form.status">
            <option value="draft">Borrador</option>
            <option value="publish">Publicar</option>
            <option value="private">Privado</option>
            <option value="pending">Pendiente</option>
          </select>
        </div>
        <div class="field" style="flex:1">
          <label>Layout</label>
          <select v-model="form.layout">
            <option v-for="l in PORTFOLIO_LAYOUTS" :key="l.code" :value="l.code">
              {{ l.short }}
            </option>
          </select>
        </div>
      </div>
      <div class="field">
        <label>Categorías</label>
        <div class="checks">
          <label v-for="c in portfolios.categories" :key="c.id" class="cat-check">
            <input type="checkbox" :value="c.id" v-model="form.categories" />
            <span>{{ c.name }}</span>
          </label>
        </div>
      </div>

      <button class="btn pri" :disabled="saving" @click="save" style="width:100%;margin-top:8px">
        <Spinner v-if="saving" :size="14" />
        <span v-else>Guardar cambios</span>
      </button>
    </div>

    <div class="card" style="margin-top:14px">
      <div class="gallery-head">
        <h3 class="section" style="margin:0">Galería ({{ galleryItems.length }})</h3>
        <div class="gh-actions">
          <button class="btn sm" @click="showAddPicker = true">+ Añadir</button>
          <button v-if="galleryDirty" class="btn pri sm" :disabled="reordering" @click="saveOrder">
            <Spinner v-if="reordering" :size="12" />
            <span v-else>💾 Guardar</span>
          </button>
        </div>
      </div>
      <p class="muted small" style="margin:0 0 8px">Mantén pulsado y arrastra para reordenar</p>

      <draggable v-if="galleryItems.length"
        v-model="galleryItems"
        item-key="id"
        class="ggrid"
        :animation="180"
        :delay="120"
        :delay-on-touch-only="true"
        ghost-class="g-ghost"
        chosen-class="g-chosen"
        drag-class="g-drag"
        @end="onDragEnd">
        <template #item="{ element: g }">
          <div class="gitem" :class="{ 'is-hero': g.id === item.hero_id }">
            <img :src="g.thumb" :alt="g.alt || g.title" loading="lazy" />
            <button class="ghero"
              :class="{ on: g.id === item.hero_id }"
              @click.stop="setHero(g.id)"
              :title="g.id === item.hero_id ? 'Es la imagen destacada' : 'Marcar como destacada'">★</button>
            <button class="grm" @click.stop="removeFromGallery(g.id)" title="Quitar de galería">✕</button>
          </div>
        </template>
      </draggable>
      <p v-else class="muted small">Sin imágenes en la galería.</p>

      <hr style="border:0; border-top:1px solid var(--border); margin:14px 0">

      <div class="field">
        <label>Vincular carpeta YZMF</label>
        <select v-model.number="form.linked_folder">
          <option :value="0">— Ninguna —</option>
          <option v-for="f in folders.flat" :key="f.id" :value="f.id">
            {{ '— '.repeat(f.depth) }}{{ f.name }} ({{ f.count }})
          </option>
        </select>
      </div>
      <button class="btn" :disabled="syncing || !form.linked_folder" @click="syncFromFolder" style="width:100%">
        <Spinner v-if="syncing" :size="14" />
        <span v-else>↻ Sincronizar galería desde carpeta</span>
      </button>
    </div>

    <!-- Configuración avanzada (campos del tema kotlis) -->
    <div class="card adv-card" style="margin-top:14px;padding:0">
      <button class="adv-head" @click="showAdvanced = !showAdvanced">
        <span>⚙️ Configuración avanzada del tema</span>
        <span class="adv-arrow" :class="{ open: showAdvanced }">▶</span>
      </button>
      <div v-if="showAdvanced" class="adv-body">
        <p class="muted small" style="margin:0 0 12px">
          Campos del tema kotlis específicos del layout <strong>{{ item.layout }}</strong>.
          Controla el sidebar, secciones de contenido, datos del proyecto, botón y prev/next.
        </p>
        <PortfolioMetaForm :portfolio-id="item.id" :layout-key="item.layout" />
      </div>
    </div>

    <div class="card danger-zone" style="margin-top:14px">
      <a class="btn" :href="item.permalink" target="_blank" style="width:100%;margin-bottom:8px">↗ Ver portfolio</a>
      <a class="btn" :href="item.edit_url"  target="_blank" style="width:100%;margin-bottom:8px">⚙ Editor de WP</a>
      <button class="btn" @click="duplicate" :disabled="duplicating" style="width:100%;margin-bottom:8px">
        <Spinner v-if="duplicating" :size="14" />
        <span v-else>📋 Duplicar como plantilla</span>
      </button>
      <button class="btn danger" @click="remove" style="width:100%">🗑 Mover a papelera</button>
    </div>
  </div>

  <MediaPicker v-model="showAddPicker"
    :multiple="true"
    :exclude="galleryItems.map(g => g.id)"
    title="Añadir imágenes a la galería"
    @pick="onAddImages" />

  <MediaPicker v-model="showHeroPicker"
    :multiple="false"
    title="Elegir imagen destacada"
    @pick="onPickHero" />
</template>

<style scoped>
.center { display: flex; gap: 10px; justify-content: center; padding: 30px; }

.hero {
  position: relative;
  aspect-ratio: 16/9;
  border-radius: var(--radius-lg);
  overflow: hidden;
  margin-bottom: 14px;
  background: var(--s2);
}
.hero img { width: 100%; height: 100%; object-fit: cover; }
.hero-empty {
  display: flex; align-items: center; justify-content: center;
  border: 1px dashed var(--border2);
  font-size: 13px;
  text-align: center;
  padding: 20px;
}
.hero-badge {
  position: absolute;
  bottom: 8px; left: 8px;
  background: rgba(0,0,0,.65);
  color: var(--accent);
  padding: 4px 10px;
  border-radius: 16px;
  font-size: 11px;
  font-weight: 500;
}
.hero-badge.fallback { color: var(--text-mute); }
.hero-actions {
  position: absolute;
  top: 8px; right: 8px;
  display: flex; gap: 6px;
}
.hero-btn {
  background: rgba(0,0,0,.65);
  color: white;
  padding: 5px 12px;
  border-radius: 16px;
  font-size: 11px;
  font-weight: 500;
}
.hero-btn:active { background: rgba(0,0,0,.85); }

.hero-empty {
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  gap: 4px;
}

.gh-actions { display: flex; gap: 6px; }

/* Configuración avanzada (kotlis meta) */
.adv-card { overflow: hidden; }
.adv-head {
  display: flex; align-items: center; justify-content: space-between;
  width: 100%;
  padding: 14px 16px;
  background: var(--s2);
  font-size: 13px;
  font-weight: 600;
  color: var(--text);
  text-transform: uppercase;
  letter-spacing: .5px;
}
.adv-head:active { background: var(--s3); }
.adv-arrow { color: var(--text-mute); font-size: 11px; transition: transform .15s; }
.adv-arrow.open { transform: rotate(90deg); }
.adv-body { padding: 14px 16px; background: var(--bg); }

.section { margin: 0 0 10px; font-size: 12px; text-transform: uppercase; letter-spacing: .5px; color: var(--text-mute); }

.gallery-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 6px;
}
.btn.sm { min-height: 32px; padding: 4px 10px; font-size: 12px; }

.ggrid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px; }
.gitem {
  position: relative;
  aspect-ratio: 1;
  background: var(--s2);
  border-radius: 4px;
  overflow: hidden;
  cursor: grab;
  user-select: none;
  -webkit-user-select: none;
  -webkit-touch-callout: none;
  transition: transform .15s, box-shadow .15s;
}
.gitem:active { cursor: grabbing; }
.gitem img { width: 100%; height: 100%; object-fit: cover; display: block; pointer-events: none; }
.gitem.is-hero { box-shadow: 0 0 0 2px var(--accent); }

.grm, .ghero {
  position: absolute;
  width: 22px; height: 22px;
  border-radius: 50%;
  background: rgba(0,0,0,.6);
  color: white;
  font-size: 11px;
  display: flex; align-items: center; justify-content: center;
  transition: opacity .15s, background .15s, color .15s;
}
.grm  { top: 4px; right: 4px; opacity: 0; }
.ghero { top: 4px; left: 4px; opacity: 0.55; }

.ghero.on {
  background: var(--accent);
  color: #0f0f0f;
  opacity: 1;
}

/* En móvil (touch) los botones siempre visibles para que se puedan pulsar.
   En desktop sólo aparecen al hover. */
@media (hover: hover) {
  .gitem:hover .grm,
  .gitem:hover .ghero { opacity: 1; }
}
@media (hover: none) {
  .grm, .ghero { opacity: 0.85; }
  .ghero.on { opacity: 1; }
}

/* Estados de drag — clases Sortable.js */
.g-ghost  { opacity: .35; }
.g-chosen { transform: scale(1.05); box-shadow: var(--shadow); }
.g-drag   { transform: rotate(2deg); }

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
.danger-zone { border-color: var(--border); }
</style>
