<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { VueDraggable } from 'vue-draggable-plus';
import { useFeedStore } from '../stores/feed';
import { useUiStore } from '../stores/ui';
import Spinner from '../components/Spinner.vue';
import MediaPicker from '../components/MediaPicker.vue';

/**
 * Crear publicación: fotos (varias, ordenables), texto, hashtags y estado.
 */
const router = useRouter();
const feed   = useFeedStore();
const ui     = useUiStore();

const caption  = ref('');
const status   = ref('draft');
const photos   = ref([]);       // objetos imagen: { id, thumb, medium, alt, ... }
const tags     = ref([]);       // nombres normalizados de hashtag
const tagInput = ref('');
const showPicker = ref(false);
const saving   = ref(false);

const pickedIds = computed(() => photos.value.map(p => p.id));

// Sugerencias de hashtags ya existentes que aún no se han añadido.
const suggestions = computed(() => {
  const used = new Set(tags.value);
  const q = tagInput.value.trim().toLowerCase().replace(/^#/, '');
  return feed.hashtags
    .filter(h => !used.has(h.tag) && (q === '' || h.tag.includes(q)))
    .slice(0, 8);
});

onMounted(() => feed.loadHashtags());

function onPick(imgs) {
  const arr = Array.isArray(imgs) ? imgs : [imgs];
  const existing = new Set(pickedIds.value);
  arr.forEach(img => { if (!existing.has(img.id)) photos.value.push(img); });
}
function removePhoto(id) {
  photos.value = photos.value.filter(p => p.id !== id);
}

function normalize(tag) {
  return String(tag).trim().replace(/^#/, '').toLowerCase().replace(/[^\p{L}\p{N}_-]+/gu, '');
}
function addTag(raw) {
  const n = normalize(raw);
  if (n && !tags.value.includes(n)) tags.value.push(n);
  tagInput.value = '';
}
function onTagKey(e) {
  if (e.key === 'Enter' || e.key === ',' || e.key === ' ') {
    e.preventDefault();
    if (tagInput.value.trim()) addTag(tagInput.value);
  } else if (e.key === 'Backspace' && tagInput.value === '' && tags.value.length) {
    tags.value.pop();
  }
}
function removeTag(t) { tags.value = tags.value.filter(x => x !== t); }

async function create() {
  if (!photos.value.length && !caption.value.trim()) {
    ui.toast('Añade al menos una foto o un texto', 'err');
    return;
  }
  saving.value = true;
  try {
    const created = await feed.create({
      caption: caption.value,
      status: status.value,
      photos: pickedIds.value,
      hashtags: tags.value,
    });
    ui.toast(status.value === 'publish' ? '✓ Publicación creada' : '✓ Borrador guardado', 'ok');
    router.replace({ name: 'feed-detail', params: { id: created.id } });
  } catch (e) {
    ui.toast(e.message, 'err');
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <div class="card">
    <!-- Fotos -->
    <div class="field">
      <label>Fotos</label>
      <VueDraggable v-if="photos.length" v-model="photos" :animation="180" handle=".drag" class="photos">
        <div v-for="p in photos" :key="p.id" class="photo">
          <span class="drag" aria-label="Arrastrar para reordenar">⠿</span>
          <img :src="p.thumb || p.medium" :alt="p.alt || ''" />
          <button class="rm" @click="removePhoto(p.id)" aria-label="Quitar foto">✕</button>
        </div>
      </VueDraggable>
      <button class="btn add-photos" @click="showPicker = true">
        🖼️ {{ photos.length ? 'Añadir más fotos' : 'Elegir fotos' }}
      </button>
    </div>

    <!-- Texto -->
    <div class="field">
      <label>Texto</label>
      <textarea v-model="caption" rows="4" placeholder="Escribe algo… puedes usar #hashtags aquí también"></textarea>
    </div>

    <!-- Hashtags -->
    <div class="field">
      <label>Hashtags</label>
      <div class="tags-input">
        <span v-for="t in tags" :key="t" class="tag">
          #{{ t }} <button @click="removeTag(t)" aria-label="Quitar">✕</button>
        </span>
        <input
          v-model="tagInput"
          @keydown="onTagKey"
          @blur="tagInput.trim() && addTag(tagInput)"
          placeholder="Añadir hashtag…" />
      </div>
      <div v-if="suggestions.length" class="suggestions">
        <button v-for="s in suggestions" :key="s.slug" class="sug" @click="addTag(s.tag)">
          #{{ s.tag }} <span class="count">{{ s.count }}</span>
        </button>
      </div>
    </div>

    <!-- Estado -->
    <div class="field">
      <label>Estado</label>
      <select v-model="status">
        <option value="draft">Borrador</option>
        <option value="publish">Publicar</option>
      </select>
    </div>

    <button class="btn pri" :disabled="saving" @click="create" style="width:100%;margin-top:14px">
      <Spinner v-if="saving" :size="14" />
      <span v-else>{{ status === 'publish' ? 'Publicar' : 'Guardar borrador' }}</span>
    </button>

    <MediaPicker
      v-model="showPicker"
      :multiple="true"
      :exclude="pickedIds"
      title="Elegir fotos"
      @pick="onPick" />
  </div>
</template>

<style scoped>
.photos { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; }
.photo {
  position: relative;
  width: 84px; height: 84px;
  border-radius: var(--radius);
  overflow: hidden;
  border: 1px solid var(--border);
}
.photo img { width: 100%; height: 100%; object-fit: cover; display: block; }
.photo .drag {
  position: absolute; top: 2px; left: 2px;
  background: rgba(0,0,0,.5); color: #fff;
  font-size: 12px; padding: 0 4px; border-radius: 4px;
  cursor: grab;
}
.photo .rm {
  position: absolute; top: 2px; right: 2px;
  width: 20px; height: 20px; border-radius: 50%;
  background: rgba(0,0,0,.6); color: #fff; font-size: 11px;
}
.add-photos { width: 100%; }

.tags-input {
  display: flex; flex-wrap: wrap; gap: 6px; align-items: center;
  background: var(--s2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 8px;
}
.tags-input input {
  flex: 1; min-width: 120px;
  border: 0; background: transparent; padding: 4px;
}
.tag {
  display: inline-flex; align-items: center; gap: 4px;
  background: var(--accent-lo); color: var(--accent);
  padding: 3px 8px; border-radius: 14px; font-size: 13px;
}
.tag button { color: inherit; font-size: 11px; }

.suggestions { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
.sug {
  padding: 4px 9px; border-radius: 14px;
  background: var(--s2); border: 1px solid var(--border);
  color: var(--text-mute); font-size: 12px;
}
.sug .count { opacity: .6; }
</style>
