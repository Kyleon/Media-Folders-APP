<script setup>
import { computed, onMounted, ref, watch, onBeforeUnmount } from 'vue';
import { useRoute, useRouter, onBeforeRouteLeave } from 'vue-router';
import draggable from 'vuedraggable';
import { useSlidersStore } from '../stores/sliders';
import { useUiStore } from '../stores/ui';
import Spinner from '../components/Spinner.vue';
import SliderSettings from '../components/SliderSettings.vue';
import SlideForm from '../components/SlideForm.vue';

const props = defineProps({
  id: { type: [String, Number], required: true },
});

const sliders = useSlidersStore();
const ui = useUiStore();
const router = useRouter();

const titleInput = ref('');
const showSettings = ref(false);

const slides = computed({
  get: () => sliders.current?.data?.slides ?? [],
  set: (v) => sliders.reorderSlides(v),
});

const settings = computed({
  get: () => sliders.current?.data?.settings ?? {},
  set: (v) => sliders.updateSettings(v),
});

onMounted(async () => {
  await sliders.fetchOne(Number(props.id));
  titleInput.value = sliders.current?.title || '';
});

watch(() => sliders.current?.title, (t) => {
  titleInput.value = t || '';
});

function onTitleBlur() {
  if (sliders.current && titleInput.value !== sliders.current.title) {
    sliders.setTitle(titleInput.value);
  }
}

async function save() {
  if (!sliders.current) return;
  try {
    await sliders.save();
    ui.toast('Slider guardado', 'ok');
  } catch (e) {
    ui.toast('Error al guardar: ' + (e.message || e), 'err');
  }
}

function addSlide() {
  sliders.addSlide();
}

function removeSlide(slideId) {
  if (!confirm('¿Eliminar este slide?')) return;
  sliders.removeSlide(slideId);
}

function duplicateSlide(slideId) {
  sliders.duplicateSlide(slideId);
}

function applyStyleToAll() {
  if (!confirm('¿Aplicar el estilo del primer slide a todos los demás?')) return;
  sliders.applyStyleToAll();
  ui.toast('Estilo aplicado a todos los slides', 'ok');
}

const previewUrl = computed(() => {
  // Preview: usar shortcode insertado en una página específica si existe.
  // De momento mostramos un atajo al sitio público para que el usuario añada
  // el shortcode donde quiera.
  return null;
});

// Aviso al salir si hay cambios sin guardar
onBeforeRouteLeave((to, from, next) => {
  if (sliders.isDirty) {
    if (confirm('Tienes cambios sin guardar. ¿Salir y descartarlos?')) {
      sliders.current = null;
      sliders.isDirty = false;
      next();
    } else {
      next(false);
    }
  } else {
    next();
  }
});

// Atajo Ctrl+S / Cmd+S para guardar
function onKey(e) {
  if ((e.ctrlKey || e.metaKey) && e.key === 's') {
    e.preventDefault();
    save();
  }
}
onMounted(() => window.addEventListener('keydown', onKey));
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
</script>

<template>
  <div>
    <div v-if="sliders.loading" class="center muted"><Spinner /> Cargando…</div>

    <div v-else-if="!sliders.current" class="center muted">
      Slider no encontrado.
      <button class="btn" @click="router.push({ name: 'sliders' })">← Volver</button>
    </div>

    <div v-else class="sd-layout">
      <!-- Header con título editable + acciones globales -->
      <div class="sd-header">
        <button class="btn" title="Volver" @click="router.push({ name: 'sliders' })">←</button>
        <input
          class="sd-title"
          v-model="titleInput"
          @blur="onTitleBlur"
          @keydown.enter.prevent="$event.target.blur()"
          placeholder="Título del slider"
        />
        <span v-if="sliders.isDirty" class="dirty muted small">● Sin guardar</span>
        <button class="btn" @click="showSettings = !showSettings">
          ⚙ Settings
        </button>
        <button class="btn pri" :disabled="sliders.saving || !sliders.isDirty" @click="save">
          {{ sliders.saving ? 'Guardando…' : 'Guardar' }}
        </button>
      </div>

      <!-- Panel de settings globales -->
      <div v-if="showSettings" class="sd-settings">
        <h3 style="margin:0 0 8px">Configuración global</h3>
        <SliderSettings
          :model-value="settings"
          @change="sliders.updateSettings"
        />
      </div>

      <!-- Slides -->
      <div class="sd-section">
        <div class="row" style="justify-content:space-between; align-items:center; margin-bottom:8px">
          <h3 style="margin:0">
            Slides
            <span class="muted small">({{ slides.length }})</span>
          </h3>
          <div class="row" style="gap:6px">
            <button v-if="slides.length > 1" class="btn small" @click="applyStyleToAll" title="Aplicar estilo del primer slide a todos">
              Igualar estilos
            </button>
            <button class="btn pri" @click="addSlide">+ Slide</button>
          </div>
        </div>

        <div v-if="!slides.length" class="empty muted">
          📭 Sin slides todavía.<br />
          <button class="btn pri" style="margin-top:14px" @click="addSlide">Añadir el primero</button>
        </div>

        <draggable
          v-else
          v-model="slides"
          item-key="id"
          handle=".drag"
          :animation="200"
          class="slides-list"
        >
          <template #item="{ element: slide }">
            <SlideForm
              :slide="slide"
              @update="(patch) => sliders.updateSlide(slide.id, patch)"
              @update-style="(patch) => sliders.updateSlideStyle(slide.id, patch)"
              @remove="removeSlide(slide.id)"
              @duplicate="duplicateSlide(slide.id)"
            />
          </template>
        </draggable>
      </div>

      <!-- Cómo usar el slider -->
      <div class="sd-help muted small">
        💡 Para mostrar este slider en una página, copia este shortcode en el contenido:
        <code class="snippet">[yzmf_slider id="{{ sliders.current.id }}"]</code>
      </div>
    </div>
  </div>
</template>

<style scoped>
.center { display: flex; gap: 10px; justify-content: center; padding: 30px; align-items: center; }
.empty { text-align: center; padding: 40px 16px; }

.sd-layout { display: flex; flex-direction: column; gap: 14px; }

.sd-header {
  display: flex;
  gap: 8px;
  align-items: center;
  flex-wrap: wrap;
  position: sticky;
  top: 0;
  background: var(--bg);
  padding: 8px 0;
  z-index: 10;
  border-bottom: 1px solid var(--border);
}
.sd-title {
  flex: 1;
  background: transparent;
  border: 1px solid transparent;
  border-radius: var(--radius);
  color: var(--text);
  font-size: 18px;
  font-weight: 600;
  padding: 8px 10px;
  min-width: 200px;
}
.sd-title:focus, .sd-title:hover {
  background: var(--s1);
  border-color: var(--border);
  outline: none;
}
.dirty { color: var(--accent); }

.sd-settings {
  background: var(--s1);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 14px;
}
.sd-section { display: flex; flex-direction: column; }
.slides-list { display: flex; flex-direction: column; gap: 8px; }

.sd-help {
  background: var(--s1);
  border: 1px dashed var(--border);
  border-radius: var(--radius);
  padding: 10px 14px;
  font-size: 12px;
}
.snippet {
  display: inline-block;
  margin-left: 6px;
  padding: 2px 6px;
  background: var(--s2);
  border-radius: 4px;
  font-family: ui-monospace, Menlo, monospace;
  user-select: all;
}

.row { display: flex; gap: 8px; flex-wrap: wrap; }
.btn.small { padding: 4px 8px; font-size: 12px; }
.muted { color: var(--text-mute); }
.small { font-size: 11px; }
</style>
