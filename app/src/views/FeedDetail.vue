<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { VueDraggable } from 'vue-draggable-plus';
import { useFeedStore } from '../stores/feed';
import { useUiStore } from '../stores/ui';
import Spinner from '../components/Spinner.vue';
import MediaPicker from '../components/MediaPicker.vue';

const props = defineProps({ id: { type: [String, Number], required: true } });
const route  = useRoute();
const router = useRouter();
const feed   = useFeedStore();
const ui     = useUiStore();

const postId = computed(() => Number(props.id ?? route.params.id));

const loading = ref(true);
const saving  = ref(false);
const post    = ref(null);

const caption = ref('');
const status  = ref('draft');
const photos  = ref([]);
const tags    = ref([]);
const tagInput = ref('');
const showPicker = ref(false);

const likes    = ref(0);

// Comentarios
const comments = ref([]);
const cStatus  = ref('all');
const cLoading = ref(false);

const pickedIds = computed(() => photos.value.map(p => p.id));
const suggestions = computed(() => {
  const used = new Set(tags.value);
  const q = tagInput.value.trim().toLowerCase().replace(/^#/, '');
  return feed.hashtags
    .filter(h => !used.has(h.tag) && (q === '' || h.tag.includes(q)))
    .slice(0, 8);
});

onMounted(async () => {
  feed.loadHashtags();
  await loadPost();
  loadComments();
});

async function loadPost() {
  loading.value = true;
  try {
    const p = await feed.detail(postId.value);
    post.value = p;
    caption.value = p.caption || '';
    status.value  = p.status || 'draft';
    photos.value  = (p.photos || []).slice();
    tags.value    = (p.hashtags || []).slice();
    likes.value   = p.likes || 0;
  } catch (e) {
    ui.toast('No se pudo cargar la publicación', 'err');
    router.replace({ name: 'feed' });
  } finally {
    loading.value = false;
  }
}

/* ── Fotos ── */
function onPick(imgs) {
  const arr = Array.isArray(imgs) ? imgs : [imgs];
  const existing = new Set(pickedIds.value);
  arr.forEach(img => { if (!existing.has(img.id)) photos.value.push(img); });
}
function removePhoto(id) { photos.value = photos.value.filter(p => p.id !== id); }

/* ── Hashtags ── */
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

/* ── Guardar / borrar ── */
async function save() {
  saving.value = true;
  try {
    const updated = await feed.update(postId.value, {
      caption: caption.value,
      status: status.value,
      photos: pickedIds.value,
      hashtags: tags.value,
    });
    post.value = updated;
    tags.value = (updated.hashtags || []).slice();
    ui.toast('✓ Guardado', 'ok');
  } catch (e) {
    ui.toast(e.message, 'err');
  } finally {
    saving.value = false;
  }
}

async function togglePublish() {
  status.value = status.value === 'publish' ? 'draft' : 'publish';
  await save();
}

async function remove() {
  const ok = await ui.confirm({
    title: 'Eliminar publicación',
    message: '¿Seguro? Se moverá a la papelera.',
    confirmText: 'Eliminar',
    destructive: true,
  });
  if (!ok) return;
  try {
    await feed.remove(postId.value);
    ui.toast('Publicación eliminada', 'ok');
    router.replace({ name: 'feed' });
  } catch (e) {
    ui.toast(e.message, 'err');
  }
}

/* ── Comentarios ── */
async function loadComments() {
  cLoading.value = true;
  try {
    comments.value = await feed.listComments(postId.value, cStatus.value);
  } catch (e) {
    comments.value = [];
  } finally {
    cLoading.value = false;
  }
}
function setCStatus(s) { cStatus.value = s; loadComments(); }

async function moderate(c, action) {
  try {
    await feed.moderateComment(c.id, action);
    ui.toast('✓ Actualizado', 'ok');
    loadComments();
  } catch (e) { ui.toast(e.message, 'err'); }
}
async function delComment(c) {
  const ok = await ui.confirm({ title: 'Borrar comentario', message: '¿Eliminar definitivamente?', confirmText: 'Borrar', destructive: true });
  if (!ok) return;
  try {
    await feed.removeComment(c.id);
    comments.value = comments.value.filter(x => x.id !== c.id);
  } catch (e) { ui.toast(e.message, 'err'); }
}

function fmtDate(iso) {
  if (!iso) return '';
  try { return new Date(iso).toLocaleString('es-ES', { dateStyle: 'medium', timeStyle: 'short' }); }
  catch { return iso; }
}
</script>

<template>
  <div v-if="loading" class="center"><Spinner /> Cargando…</div>

  <div v-else-if="post" class="detail">
    <!-- Cabecera -->
    <div class="head card">
      <div class="head-row">
        <span class="badge-status" :class="status">{{ status === 'publish' ? 'Publicado' : 'Borrador' }}</span>
        <span class="stat">❤ {{ likes }}</span>
        <span class="stat">💬 {{ post.comment_count }}</span>
        <span class="spacer"></span>
        <button class="btn small" @click="togglePublish" :disabled="saving">
          {{ status === 'publish' ? 'Pasar a borrador' : 'Publicar' }}
        </button>
      </div>
      <p class="muted small">{{ fmtDate(post.date) }}</p>
    </div>

    <!-- Edición -->
    <div class="card">
      <div class="field">
        <label>Fotos</label>
        <VueDraggable v-if="photos.length" v-model="photos" :animation="180" handle=".drag" class="photos">
          <div v-for="p in photos" :key="p.id" class="photo">
            <span class="drag" aria-label="Arrastrar">⠿</span>
            <img :src="p.thumb || p.medium" :alt="p.alt || ''" />
            <button class="rm" @click="removePhoto(p.id)" aria-label="Quitar">✕</button>
          </div>
        </VueDraggable>
        <button class="btn add-photos" @click="showPicker = true">
          🖼️ {{ photos.length ? 'Añadir más fotos' : 'Elegir fotos' }}
        </button>
      </div>

      <div class="field">
        <label>Texto</label>
        <textarea v-model="caption" rows="4" placeholder="Escribe algo…"></textarea>
      </div>

      <div class="field">
        <label>Hashtags</label>
        <div class="tags-input">
          <span v-for="t in tags" :key="t" class="tag">
            #{{ t }} <button @click="removeTag(t)" aria-label="Quitar">✕</button>
          </span>
          <input v-model="tagInput" @keydown="onTagKey" @blur="tagInput.trim() && addTag(tagInput)" placeholder="Añadir hashtag…" />
        </div>
        <div v-if="suggestions.length" class="suggestions">
          <button v-for="s in suggestions" :key="s.slug" class="sug" @click="addTag(s.tag)">
            #{{ s.tag }} <span class="count">{{ s.count }}</span>
          </button>
        </div>
      </div>

      <div class="actions">
        <button class="btn pri" :disabled="saving" @click="save">
          <Spinner v-if="saving" :size="14" /><span v-else>Guardar</span>
        </button>
        <button class="btn danger" :disabled="saving" @click="remove">Eliminar</button>
      </div>

      <p class="muted small hint">
        💡 Las publicaciones publicadas aparecen en la web con el shortcode
        <code>[yzmf_feed]</code>.
      </p>
    </div>

    <!-- Comentarios -->
    <div class="card">
      <div class="comments-head">
        <h3>Comentarios</h3>
        <div class="c-tabs">
          <button :class="{ on: cStatus === 'all' }" @click="setCStatus('all')">Todos</button>
          <button :class="{ on: cStatus === 'hold' }" @click="setCStatus('hold')">Pendientes</button>
          <button :class="{ on: cStatus === 'approve' }" @click="setCStatus('approve')">Aprobados</button>
          <button :class="{ on: cStatus === 'spam' }" @click="setCStatus('spam')">Spam</button>
        </div>
      </div>

      <div v-if="cLoading" class="center small"><Spinner :size="14" /> Cargando…</div>
      <p v-else-if="!comments.length" class="muted small">Sin comentarios en esta vista.</p>

      <ul v-else class="comment-list">
        <li v-for="c in comments" :key="c.id" class="comment">
          <div class="c-body">
            <b>{{ c.author || '—' }}</b>
            <span class="c-status" :class="c.status">{{ c.status }}</span>
            <p class="c-text">{{ c.content }}</p>
            <span class="muted small">{{ fmtDate(c.date) }}</span>
          </div>
          <div class="c-actions">
            <button v-if="!c.approved" class="btn small" @click="moderate(c, 'approve')">Aprobar</button>
            <button v-else class="btn small" @click="moderate(c, 'unapprove')">Ocultar</button>
            <button class="btn small" @click="moderate(c, 'spam')">Spam</button>
            <button class="btn small danger" @click="delComment(c)">Borrar</button>
          </div>
        </li>
      </ul>
    </div>

    <MediaPicker v-model="showPicker" :multiple="true" :exclude="pickedIds" title="Elegir fotos" @pick="onPick" />
  </div>
</template>

<style scoped>
.center { display: flex; gap: 10px; justify-content: center; padding: 40px; color: var(--text-mute); }
.detail { display: flex; flex-direction: column; gap: 14px; }

.head-row { display: flex; align-items: center; gap: 12px; }
.spacer { flex: 1; }
.stat { font-size: 14px; color: var(--text-mute); }
.badge-status {
  font-size: 10px; padding: 3px 8px; border-radius: 10px;
  text-transform: uppercase; letter-spacing: .3px; font-weight: 600;
}
.badge-status.publish { background: var(--ok); color: #04120a; }
.badge-status.draft   { background: var(--s3); color: var(--text-mute); }

.photos { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; }
.photo { position: relative; width: 84px; height: 84px; border-radius: var(--radius); overflow: hidden; border: 1px solid var(--border); }
.photo img { width: 100%; height: 100%; object-fit: cover; display: block; }
.photo .drag { position: absolute; top: 2px; left: 2px; background: rgba(0,0,0,.5); color: #fff; font-size: 12px; padding: 0 4px; border-radius: 4px; cursor: grab; }
.photo .rm { position: absolute; top: 2px; right: 2px; width: 20px; height: 20px; border-radius: 50%; background: rgba(0,0,0,.6); color: #fff; font-size: 11px; }
.add-photos { width: 100%; }

.tags-input { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; background: var(--s2); border: 1px solid var(--border); border-radius: var(--radius); padding: 8px; }
.tags-input input { flex: 1; min-width: 120px; border: 0; background: transparent; padding: 4px; }
.tag { display: inline-flex; align-items: center; gap: 4px; background: var(--accent-lo); color: var(--accent); padding: 3px 8px; border-radius: 14px; font-size: 13px; }
.tag button { color: inherit; font-size: 11px; }
.suggestions { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
.sug { padding: 4px 9px; border-radius: 14px; background: var(--s2); border: 1px solid var(--border); color: var(--text-mute); font-size: 12px; }
.sug .count { opacity: .6; }

.actions { display: flex; gap: 10px; margin-top: 14px; }
.actions .btn { flex: 1; }
.hint { margin-top: 12px; }
.hint code { background: var(--s2); padding: 1px 6px; border-radius: 4px; }

.comments-head { display: flex; flex-direction: column; gap: 8px; margin-bottom: 12px; }
.comments-head h3 { margin: 0; font-size: 15px; }
.c-tabs { display: flex; gap: 6px; flex-wrap: wrap; }
.c-tabs button { padding: 5px 10px; border-radius: 14px; background: var(--s2); border: 1px solid var(--border); color: var(--text-mute); font-size: 12px; }
.c-tabs button.on { background: var(--accent-lo); color: var(--accent); border-color: var(--accent); }

.comment-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 12px; }
.comment { display: flex; flex-direction: column; gap: 8px; padding: 12px; background: var(--s2); border-radius: var(--radius); }
@media (min-width: 640px) { .comment { flex-direction: row; justify-content: space-between; align-items: flex-start; } }
.c-body { min-width: 0; }
.c-text { margin: 4px 0; font-size: 14px; line-height: 1.4; white-space: pre-wrap; }
.c-status { font-size: 10px; margin-left: 8px; padding: 1px 6px; border-radius: 8px; background: var(--s3); color: var(--text-mute); text-transform: uppercase; }
.c-status.approved { background: var(--ok); color: #04120a; }
.c-status.spam { background: var(--danger); color: #fff; }
.c-actions { display: flex; gap: 6px; flex-wrap: wrap; flex-shrink: 0; }

.small { font-size: 12px; }
.btn.small { min-height: 32px; padding: 0 10px; font-size: 12px; }
</style>
