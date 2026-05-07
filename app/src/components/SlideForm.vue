<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import MediaPicker from './MediaPicker.vue';
import GeoTagger from './GeoTagger.vue';
import { MediaAPI } from '../api/endpoints';

const props = defineProps({
  slide:      { type: Object, required: true },
  portfolios: { type: Array,  default: () => [] },
});
const emit = defineEmits(['update', 'updateStyle', 'remove', 'duplicate']);

const showPicker = ref(false);
const showGeo = ref(false);
const expanded = ref(false);

/* ─────── Thumbnail de la imagen seleccionada ─────── */
const imageThumb = ref('');
const imageMedium = ref('');

async function loadImageInfo(id) {
  if (!id) {
    imageThumb.value = '';
    imageMedium.value = '';
    return;
  }
  try {
    const data = await MediaAPI.detail(id);
    imageThumb.value  = data.thumb  || data.url || '';
    imageMedium.value = data.medium || data.url || data.thumb || '';
  } catch {
    imageThumb.value = '';
    imageMedium.value = '';
  }
}

watch(() => [props.slide.image_id, props.slide.type], ([id, type]) => {
  if (type === 'image' && id) loadImageInfo(id);
  else { imageThumb.value = ''; imageMedium.value = ''; }
}, { immediate: false });

onMounted(() => {
  if (props.slide.type === 'image' && props.slide.image_id) {
    loadImageInfo(props.slide.image_id);
  }
});

/* ─────── Helpers de mutación ─────── */
function patch(field, value) {
  emit('update', { [field]: value });
}

function patchStyle(field, value) {
  emit('updateStyle', { [field]: value });
}

function onPickImage(image) {
  showPicker.value = false;
  // Aprovechamos las URLs ya disponibles del picker para evitar refetch
  if (image.thumb)  imageThumb.value  = image.thumb;
  if (image.medium) imageMedium.value = image.medium;
  if (image.url)    imageMedium.value = imageMedium.value || image.url;
  patch('image_id', image.id);
}

function onPickGeo({ lat, lng, place }) {
  showGeo.value = false;
  emit('update', { lat, lng, location: place || props.slide.location });
}

function onClearGeo() {
  showGeo.value = false;
  emit('update', { lat: null, lng: null });
}

/* ─────── Selector de portfolio para el botón ─────── */
// Detecta si la button_url actual coincide con la URL de un portfolio
const buttonLinkMode = ref('url');   // 'url' | 'portfolio'
const selectedPortfolioId = ref(0);

function detectButtonMode() {
  const url = props.slide.button_url || '';
  if (!url) {
    buttonLinkMode.value = 'url';
    selectedPortfolioId.value = 0;
    return;
  }
  // Buscar en portfolios por link o slug
  const match = props.portfolios.find(p => {
    if (p.link && url === p.link) return true;
    if (p.slug && url === '/portfolio/' + p.slug + '/') return true;
    if (p.slug && url === '/portfolio/' + p.slug) return true;
    return false;
  });
  if (match) {
    buttonLinkMode.value = 'portfolio';
    selectedPortfolioId.value = match.id;
  } else {
    buttonLinkMode.value = 'url';
    selectedPortfolioId.value = 0;
  }
}

watch(() => [props.slide.button_url, props.portfolios.length], detectButtonMode, { immediate: true });

function onPortfolioPick(idStr) {
  const id = Number(idStr);
  selectedPortfolioId.value = id;
  if (!id) {
    patch('button_url', '');
    return;
  }
  const p = props.portfolios.find(x => x.id === id);
  if (!p) return;
  // Preferimos la URL pública real si la API la expone; fallback a /portfolio/slug/
  const url = p.link || (p.slug ? '/portfolio/' + p.slug + '/' : '');
  patch('button_url', url);
}

function typeLabel(t) {
  return { image: '🖼️ Imagen', video_file: '🎬 Vídeo MP4', video_embed: '▶ Embed' }[t] || t;
}
</script>

<template>
  <div class="slide-form" :class="{ expanded }">
    <!-- Header colapsable: drag handle, miniatura, título, botones -->
    <div class="sf-head" @click="expanded = !expanded">
      <span class="drag" title="Arrastrar para reordenar">⋮⋮</span>
      <div class="sf-thumb">
        <img v-if="slide.type === 'image' && imageThumb" :src="imageThumb" :alt="slide.title" />
        <span v-else class="sf-type">{{ typeLabel(slide.type) }}</span>
      </div>
      <div class="sf-meta">
        <span class="sf-title">{{ slide.title || 'Slide sin título' }}</span>
        <span class="muted small">{{ slide.location || '—' }}</span>
      </div>
      <div class="sf-actions" @click.stop>
        <button class="iconbtn" title="Duplicar" @click="emit('duplicate')">⎘</button>
        <button class="iconbtn danger" title="Eliminar" @click="emit('remove')">✕</button>
        <button class="iconbtn" :title="expanded ? 'Colapsar' : 'Editar'" @click="expanded = !expanded">
          {{ expanded ? '▴' : '▾' }}
        </button>
      </div>
    </div>

    <!-- Form expandido -->
    <div v-if="expanded" class="sf-body">

      <!-- Tipo de slide -->
      <label class="field">
        <span class="muted small">Tipo</span>
        <select :value="slide.type" @change="patch('type', $event.target.value)">
          <option value="image">Imagen</option>
          <option value="video_file">Vídeo MP4 (de la biblioteca)</option>
          <option value="video_embed">Embed (YouTube / Vimeo)</option>
        </select>
      </label>

      <!-- Selector de medio según tipo -->
      <div v-if="slide.type === 'image'" class="field">
        <span class="muted small">Imagen</span>
        <div class="image-preview" v-if="imageMedium || imageThumb">
          <img :src="imageMedium || imageThumb" :alt="slide.title" />
        </div>
        <div class="row">
          <span class="muted small" style="flex:1">
            {{ slide.image_id ? 'ID #' + slide.image_id : 'Sin imagen' }}
          </span>
          <button class="btn" @click="showPicker = true">📷 {{ slide.image_id ? 'Cambiar' : 'Elegir' }}</button>
        </div>
      </div>

      <div v-else-if="slide.type === 'video_file'" class="field">
        <span class="muted small">Vídeo MP4 (attachment ID)</span>
        <input
          type="number"
          :value="slide.video_id || ''"
          placeholder="ID del attachment de tipo vídeo"
          @input="patch('video_id', Number($event.target.value) || 0)"
        />
        <span class="muted small">Sube el MP4 desde Medios. La PWA todavía no filtra vídeos en MediaPicker.</span>
      </div>

      <div v-else class="field">
        <span class="muted small">URL del embed</span>
        <input
          type="url"
          :value="slide.video_embed_url"
          placeholder="https://www.youtube.com/embed/..."
          @input="patch('video_embed_url', $event.target.value)"
        />
        <span class="muted small">Usa la URL de tipo /embed/. YouTube y Vimeo recomendados.</span>
      </div>

      <!-- Textos -->
      <div class="row">
        <label class="field">
          <span class="muted small">Subtítulo</span>
          <input
            type="text"
            :value="slide.subtitle"
            @input="patch('subtitle', $event.target.value)"
          />
        </label>
        <label class="field">
          <span class="muted small">Título</span>
          <input
            type="text"
            :value="slide.title"
            @input="patch('title', $event.target.value)"
          />
        </label>
      </div>

      <label class="field">
        <span class="muted small">Texto</span>
        <textarea
          rows="2"
          :value="slide.text"
          @input="patch('text', $event.target.value)"
        ></textarea>
      </label>

      <!-- Ubicación -->
      <div class="field">
        <span class="muted small">Ubicación</span>
        <div class="row">
          <input
            type="text"
            :value="slide.location"
            placeholder="Ej. Vík, Islandia"
            @input="patch('location', $event.target.value)"
            style="flex:1"
          />
          <button class="btn" @click="showGeo = true">📍 Mapa</button>
        </div>
        <span v-if="slide.lat != null && slide.lng != null" class="muted small">
          {{ slide.lat.toFixed(4) }}, {{ slide.lng.toFixed(4) }}
        </span>
      </div>

      <!-- Botón -->
      <div class="row">
        <label class="field">
          <span class="muted small">Texto del botón</span>
          <input
            type="text"
            :value="slide.button_text"
            placeholder="Ver galería"
            @input="patch('button_text', $event.target.value)"
          />
        </label>
      </div>

      <div class="field">
        <span class="muted small">Enlace del botón</span>
        <div class="link-tabs" role="tablist">
          <button
            class="tab"
            :class="{ active: buttonLinkMode === 'url' }"
            @click="buttonLinkMode = 'url'"
            type="button"
          >🔗 URL</button>
          <button
            class="tab"
            :class="{ active: buttonLinkMode === 'portfolio' }"
            @click="buttonLinkMode = 'portfolio'"
            type="button"
            :disabled="!portfolios.length"
            :title="portfolios.length ? '' : 'Cargando portfolios…'"
          >◇ Portfolio</button>
        </div>

        <input
          v-if="buttonLinkMode === 'url'"
          type="text"
          :value="slide.button_url"
          placeholder="/contacto, https://otro.com, #ancla, etc."
          @input="patch('button_url', $event.target.value)"
        />

        <select
          v-else
          :value="selectedPortfolioId"
          @change="onPortfolioPick($event.target.value)"
        >
          <option :value="0">— Selecciona un portfolio —</option>
          <option v-for="p in portfolios" :key="p.id" :value="p.id">
            {{ p.title }}
          </option>
        </select>

        <span v-if="slide.button_url" class="muted small">
          → {{ slide.button_url }}
        </span>
      </div>

      <!-- Estilos -->
      <details class="style-block">
        <summary>🎨 Estilo de este slide</summary>
        <div class="style-grid">
          <label class="field">
            <span class="muted small">Color overlay</span>
            <input
              type="color"
              :value="slide.style.overlay_color"
              @input="patchStyle('overlay_color', $event.target.value)"
            />
          </label>
          <label class="field">
            <span class="muted small">Opacidad overlay</span>
            <input
              type="range"
              min="0"
              max="1"
              step="0.05"
              :value="slide.style.overlay_opacity"
              @input="patchStyle('overlay_opacity', Number($event.target.value))"
            />
            <span class="muted small">{{ Math.round(slide.style.overlay_opacity * 100) }}%</span>
          </label>
          <label class="field">
            <span class="muted small">Color del texto</span>
            <input
              type="color"
              :value="slide.style.text_color"
              @input="patchStyle('text_color', $event.target.value)"
            />
          </label>
          <label class="field">
            <span class="muted small">Alineación texto</span>
            <select
              :value="slide.style.text_alignment"
              @change="patchStyle('text_alignment', $event.target.value)"
            >
              <option value="start">Izquierda</option>
              <option value="center">Centro</option>
              <option value="end">Derecha</option>
            </select>
          </label>
          <label class="field">
            <span class="muted small">Posición vertical</span>
            <select
              :value="slide.style.vertical_position"
              @change="patchStyle('vertical_position', $event.target.value)"
            >
              <option value="top">Arriba</option>
              <option value="center">Centro</option>
              <option value="bottom">Abajo</option>
            </select>
          </label>
          <label class="check">
            <input
              type="checkbox"
              :checked="slide.style.kenburns"
              @change="patchStyle('kenburns', $event.target.checked)"
            />
            <span>Kenburns en este slide</span>
          </label>
        </div>
      </details>
    </div>

    <!-- Pickers -->
    <MediaPicker
      v-model="showPicker"
      :multiple="false"
      title="Elegir imagen del slide"
      @pick="onPickImage"
    />

    <GeoTagger
      v-model="showGeo"
      :initial-lat="slide.lat"
      :initial-lng="slide.lng"
      :initial-place="slide.location"
      title="Ubicación del slide"
      @pick="onPickGeo"
      @clear="onClearGeo"
    />
  </div>
</template>

<style scoped>
.slide-form {
  background: var(--s1);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
}
.slide-form.expanded { border-color: var(--accent); }

.sf-head {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px;
  cursor: pointer;
}

.drag {
  font-weight: 700;
  color: var(--text-mute);
  cursor: grab;
  padding: 4px 6px;
}

.sf-thumb {
  width: 60px;
  height: 40px;
  background: var(--s2);
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  color: var(--text-mute);
  flex: 0 0 60px;
  overflow: hidden;
}
.sf-thumb img {
  width: 100%; height: 100%;
  object-fit: cover;
  display: block;
}
.sf-type { font-size: 10px; }

.sf-meta { flex: 1; min-width: 0; }
.sf-title {
  display: block;
  font-size: 14px;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.sf-actions { display: flex; gap: 4px; }

.sf-body {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 12px;
  border-top: 1px solid var(--border);
  background: var(--bg);
}

.image-preview {
  width: 100%;
  aspect-ratio: 16 / 9;
  background: var(--s2);
  border-radius: var(--radius);
  overflow: hidden;
  margin-bottom: 6px;
}
.image-preview img {
  width: 100%; height: 100%;
  object-fit: cover;
  display: block;
}

.row { display: flex; gap: 8px; flex-wrap: wrap; }
.field { display: flex; flex-direction: column; gap: 4px; flex: 1; min-width: 140px; }
.field input, .field select, .field textarea {
  background: var(--s2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  color: var(--text);
  padding: 8px 10px;
  font-size: 14px;
  font-family: inherit;
}
.field input[type="color"] { padding: 2px; height: 36px; cursor: pointer; }
.field input[type="range"] { padding: 0; }

.check { display: inline-flex; align-items: center; gap: 8px; cursor: pointer; user-select: none; }
.check input { accent-color: var(--accent); }

/* Tabs URL / Portfolio */
.link-tabs {
  display: flex;
  gap: 0;
  background: var(--s2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  margin-bottom: 4px;
  width: fit-content;
}
.link-tabs .tab {
  background: transparent;
  border: 0;
  color: var(--text-mute);
  padding: 6px 12px;
  font-size: 12px;
  cursor: pointer;
  transition: background .15s, color .15s;
}
.link-tabs .tab.active {
  background: var(--accent);
  color: #0f0f0f;
}
.link-tabs .tab:disabled {
  opacity: .4;
  cursor: not-allowed;
}

.style-block {
  margin-top: 4px;
  border-top: 1px dashed var(--border);
  padding-top: 8px;
}
.style-block summary {
  cursor: pointer;
  font-size: 13px;
  color: var(--text-mute);
  padding: 4px 0;
}
.style-block summary:hover { color: var(--accent); }
.style-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 8px;
  margin-top: 8px;
}

.iconbtn {
  background: transparent;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  width: 30px; height: 30px;
  cursor: pointer;
  color: var(--text);
  font-size: 13px;
}
.iconbtn:hover { background: var(--s2); border-color: var(--accent); }
.iconbtn.danger:hover { border-color: #c44; color: #c44; }

.muted { color: var(--text-mute); }
.small { font-size: 11px; }
</style>
