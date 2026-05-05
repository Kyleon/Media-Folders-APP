<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { MediaAPI, PortfoliosAPI } from '../api/endpoints';
import Spinner from '../components/Spinner.vue';

const router = useRouter();
const route  = useRoute();

const items   = ref([]);
const idx     = ref(0);
const loading = ref(true);
const playing = ref(true);
const interval = ref(parseInt(localStorage.getItem('ypva.slideshow.interval') || '5', 10)); // segundos
const showUI  = ref(true);
const fit     = ref(localStorage.getItem('ypva.slideshow.fit') || 'contain'); // contain | cover
const transition = ref(localStorage.getItem('ypva.slideshow.transition') || 'fade'); // fade | slide
const showInfo = ref(false);

let autoTimer  = null;
let uiHideTimer = null;

// Origen: ?folder=ID  o  ?portfolio=ID  o  ?ids=1,2,3
async function loadItems() {
  loading.value = true;
  items.value = [];

  try {
    const folder    = route.query.folder;
    const portfolio = route.query.portfolio;
    const ids       = route.query.ids;
    const orderQ    = route.query.order || 'ASC';

    if (portfolio) {
      const r = await PortfoliosAPI.gallery(portfolio);
      items.value = (r.gallery || []).map(g => ({
        id: g.id, src: g.url || g.medium || g.thumb,
        title: g.title, alt: g.alt, caption: g.caption,
      }));
    } else if (ids) {
      // ids como CSV "1,2,3" — cargar detalle de cada uno (poco común)
      const list = ids.split(',').map(x => parseInt(x, 10)).filter(Boolean);
      const arr = await Promise.all(list.map(id => MediaAPI.detail(id).catch(() => null)));
      items.value = arr.filter(Boolean).map(it => ({
        id: it.id, src: it.url, title: it.title, alt: it.alt, caption: it.caption,
      }));
    } else {
      // Todo el folder o todos los medios
      const params = { per_page: 200, mime: 'image', orderby: 'date', order: orderQ };
      if (folder !== undefined && folder !== '') params.folder = parseInt(folder, 10);
      const r = await MediaAPI.list(params);
      items.value = (r.images || []).map(it => ({
        id: it.id, src: it.medium || it.url, title: it.title, alt: it.alt, caption: it.caption,
      }));
    }
  } finally {
    loading.value = false;
    if (items.value.length && playing.value) startAuto();
    armUiHide();
  }
}

const current = computed(() => items.value[idx.value] || null);
const total   = computed(() => items.value.length);

function next() {
  if (!items.value.length) return;
  idx.value = (idx.value + 1) % items.value.length;
}
function prev() {
  if (!items.value.length) return;
  idx.value = idx.value === 0 ? items.value.length - 1 : idx.value - 1;
}

function startAuto() {
  stopAuto();
  if (!playing.value || !items.value.length) return;
  autoTimer = setInterval(next, interval.value * 1000);
}
function stopAuto() { if (autoTimer) { clearInterval(autoTimer); autoTimer = null; } }

function togglePlay() {
  playing.value = !playing.value;
  if (playing.value) startAuto(); else stopAuto();
}

function setInterval_(v) {
  interval.value = v;
  localStorage.setItem('ypva.slideshow.interval', String(v));
  if (playing.value) startAuto();
}

function setFit(v) {
  fit.value = v;
  localStorage.setItem('ypva.slideshow.fit', v);
}

function exitSlideshow() {
  stopAuto();
  exitFullscreen();
  router.back();
}

// Fullscreen
function enterFullscreen() {
  const el = document.documentElement;
  const req = el.requestFullscreen || el.webkitRequestFullscreen || el.msRequestFullscreen;
  if (req) req.call(el).catch(() => {});
}
function exitFullscreen() {
  if (document.fullscreenElement) document.exitFullscreen?.();
}
function toggleFullscreen() {
  if (document.fullscreenElement) exitFullscreen(); else enterFullscreen();
}

// Auto-hide UI tras 3s sin actividad
function armUiHide() {
  showUI.value = true;
  if (uiHideTimer) clearTimeout(uiHideTimer);
  uiHideTimer = setTimeout(() => { showUI.value = false; }, 3000);
}
function onMouseMove() { armUiHide(); }

// Atajos de teclado
function onKey(e) {
  switch (e.key) {
    case 'ArrowRight':
    case ' ':
      e.preventDefault(); next(); break;
    case 'ArrowLeft':
      e.preventDefault(); prev(); break;
    case 'p':
    case 'P':
      togglePlay(); break;
    case 'f':
    case 'F':
      toggleFullscreen(); break;
    case 'i':
    case 'I':
      showInfo.value = !showInfo.value; break;
    case 'Escape':
      exitSlideshow(); break;
  }
  armUiHide();
}

// Swipe táctil
let touchStartX = null;
function onTouchStart(e) { touchStartX = e.touches[0].clientX; armUiHide(); }
function onTouchEnd(e) {
  if (touchStartX === null) return;
  const dx = e.changedTouches[0].clientX - touchStartX;
  if (Math.abs(dx) > 60) { if (dx > 0) prev(); else next(); }
  touchStartX = null;
}

watch(playing, (v) => { if (v) startAuto(); else stopAuto(); });

onMounted(async () => {
  document.body.style.overflow = 'hidden';
  document.addEventListener('keydown', onKey);
  document.addEventListener('mousemove', onMouseMove);
  await loadItems();
});

onBeforeUnmount(() => {
  stopAuto();
  if (uiHideTimer) clearTimeout(uiHideTimer);
  document.body.style.overflow = '';
  document.removeEventListener('keydown', onKey);
  document.removeEventListener('mousemove', onMouseMove);
  exitFullscreen();
});
</script>

<template>
  <div class="slideshow"
    :class="{ 'no-ui': !showUI }"
    @touchstart.passive="onTouchStart"
    @touchend="onTouchEnd"
    @click="armUiHide">

    <div v-if="loading" class="ss-loading"><Spinner /> Cargando…</div>

    <template v-else-if="!total">
      <div class="ss-empty">
        <p>📭 No hay imágenes para presentar.</p>
        <button class="btn pri" @click="exitSlideshow">Volver</button>
      </div>
    </template>

    <template v-else>
      <!-- Imagen -->
      <transition :name="transition === 'fade' ? 'ss-fade' : 'ss-slide'" mode="out-in">
        <img :key="current?.id"
          class="ss-img"
          :class="{ 'fit-contain': fit === 'contain', 'fit-cover': fit === 'cover' }"
          :src="current?.src"
          :alt="current?.alt || current?.title || ''"
          loading="eager" />
      </transition>

      <!-- Info overlay -->
      <transition name="ss-fade">
        <div v-if="showInfo && current" class="ss-info">
          <h2 v-if="current.title">{{ current.title }}</h2>
          <p v-if="current.caption">{{ current.caption }}</p>
          <p v-else-if="current.alt" class="muted">{{ current.alt }}</p>
        </div>
      </transition>

      <!-- Controles -->
      <transition name="ss-fade">
        <div v-show="showUI" class="ss-controls" @click.stop>
          <div class="ss-bar top">
            <button class="ss-btn" @click="exitSlideshow" title="Salir (Esc)">✕</button>
            <span class="ss-counter">{{ idx + 1 }} / {{ total }}</span>
            <div class="ss-spacer"></div>
            <button class="ss-btn" @click="showInfo = !showInfo" :class="{ on: showInfo }" title="Info (I)">ⓘ</button>
            <button class="ss-btn" @click="setFit(fit === 'contain' ? 'cover' : 'contain')" :title="fit === 'contain' ? 'Llenar pantalla' : 'Mostrar completa'">
              {{ fit === 'contain' ? '⛶' : '◱' }}
            </button>
            <button class="ss-btn" @click="toggleFullscreen" title="Fullscreen (F)">⛶</button>
          </div>

          <button class="ss-nav left" @click="prev" title="Anterior (←)">‹</button>
          <button class="ss-nav right" @click="next" title="Siguiente (→/Espacio)">›</button>

          <div class="ss-bar bottom">
            <button class="ss-btn big" @click="togglePlay" :title="playing ? 'Pausar (P)' : 'Reproducir (P)'">
              {{ playing ? '⏸' : '▶' }}
            </button>
            <div class="ss-interval">
              <label>Intervalo</label>
              <button v-for="v in [3, 5, 8, 12, 20]" :key="v"
                class="ss-pill"
                :class="{ on: interval === v }"
                @click="setInterval_(v)">{{ v }}s</button>
            </div>
          </div>
        </div>
      </transition>
    </template>
  </div>
</template>

<style scoped>
.slideshow {
  position: fixed;
  inset: 0;
  background: #000;
  z-index: 9999;
  display: flex; align-items: center; justify-content: center;
  cursor: default;
  overflow: hidden;
}
.slideshow.no-ui { cursor: none; }

.ss-loading {
  color: #aaa;
  display: flex; gap: 10px; align-items: center;
  font-size: 14px;
}

.ss-empty {
  color: white;
  text-align: center;
}

.ss-img {
  max-width: 100%;
  max-height: 100%;
  user-select: none;
  -webkit-user-drag: none;
}
.ss-img.fit-contain { object-fit: contain; }
.ss-img.fit-cover {
  width: 100vw; height: 100vh;
  object-fit: cover;
}

/* Info overlay */
.ss-info {
  position: absolute;
  left: 50%; bottom: 100px;
  transform: translateX(-50%);
  max-width: min(900px, 90%);
  background: rgba(0,0,0,.65);
  color: white;
  padding: 16px 24px;
  border-radius: 12px;
  text-align: center;
  backdrop-filter: blur(8px);
}
.ss-info h2 { margin: 0 0 6px; font-size: 18px; font-weight: 600; }
.ss-info p { margin: 0; font-size: 13px; line-height: 1.5; opacity: .85; }
.muted { opacity: .65; }

/* Controles */
.ss-controls {
  position: absolute;
  inset: 0;
  pointer-events: none;
}
.ss-controls > * { pointer-events: auto; }

.ss-bar {
  position: absolute;
  left: 0; right: 0;
  display: flex; align-items: center; gap: 8px;
  padding: 14px 18px;
}
.ss-bar.top    { top: 0;    background: linear-gradient(to bottom, rgba(0,0,0,.55), transparent); }
.ss-bar.bottom { bottom: 0; background: linear-gradient(to top,    rgba(0,0,0,.65), transparent); padding-bottom: calc(14px + env(safe-area-inset-bottom)); }

.ss-spacer { flex: 1; }

.ss-counter {
  color: white;
  font-size: 13px;
  font-variant-numeric: tabular-nums;
  letter-spacing: 0.5px;
}

.ss-btn {
  width: 38px; height: 38px;
  background: rgba(255,255,255,.1);
  border: 1px solid rgba(255,255,255,.15);
  color: white;
  border-radius: 50%;
  font-size: 17px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  transition: background .12s, border-color .12s, transform .12s;
}
.ss-btn:hover { background: rgba(255,255,255,.2); border-color: rgba(255,255,255,.3); }
.ss-btn:active { transform: scale(.94); }
.ss-btn.on    { background: var(--accent); color: #0f0f0f; border-color: var(--accent); }
.ss-btn.big   { width: 52px; height: 52px; font-size: 22px; }

.ss-nav {
  position: absolute;
  top: 50%; transform: translateY(-50%);
  width: 56px; height: 56px;
  background: rgba(0,0,0,.4);
  color: white;
  border-radius: 50%;
  font-size: 36px;
  line-height: 1;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  transition: background .12s, opacity .12s;
  opacity: .7;
}
.ss-nav:hover { background: rgba(0,0,0,.65); opacity: 1; }
.ss-nav.left  { left: 18px; padding-right: 4px; }
.ss-nav.right { right: 18px; padding-left: 4px; }

.ss-interval {
  display: flex; align-items: center; gap: 6px;
  margin-left: auto;
}
.ss-interval label {
  color: rgba(255,255,255,.7);
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: .5px;
  margin-right: 4px;
}
.ss-pill {
  background: rgba(255,255,255,.08);
  color: white;
  border: 1px solid rgba(255,255,255,.15);
  padding: 5px 10px;
  border-radius: 14px;
  font-size: 11px;
  cursor: pointer;
  transition: background .12s, color .12s, border-color .12s;
}
.ss-pill.on { background: var(--accent); color: #0f0f0f; border-color: var(--accent); }

/* Transiciones */
.ss-fade-enter-active, .ss-fade-leave-active { transition: opacity .35s ease-out; }
.ss-fade-enter-from, .ss-fade-leave-to { opacity: 0; }

.ss-slide-enter-active, .ss-slide-leave-active { transition: transform .35s ease-out, opacity .35s ease-out; }
.ss-slide-enter-from { transform: translateX(40px); opacity: 0; }
.ss-slide-leave-to   { transform: translateX(-40px); opacity: 0; }
</style>
