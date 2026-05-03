<script setup>
import { onMounted } from 'vue';
import { useFoldersStore } from '../stores/folders';

const props = defineProps({
  modelValue: { type: Boolean, default: false },           // v-model: visible
  selected:   { type: Number,  default: null },            // id seleccionado actualmente (visual)
  title:      { type: String,  default: 'Elegir carpeta' },
  showAll:    { type: Boolean, default: true },            // mostrar opción "Todos los medios"
  showNone:   { type: Boolean, default: true },            // mostrar opción "Sin carpeta"
  noneLabel:  { type: String,  default: '— Sin carpeta —' },
  allLabel:   { type: String,  default: '🖼 Todos los medios' },
});
const emit = defineEmits(['update:modelValue', 'pick']);

const folders = useFoldersStore();

onMounted(() => folders.load());

function pick(id) {
  emit('pick', id);
  emit('update:modelValue', false);
}

function close() { emit('update:modelValue', false); }
</script>

<template>
  <transition name="sheet">
    <div v-if="modelValue" class="sheet-overlay" @click.self="close">
      <div class="sheet">
        <div class="sheet-handle" />
        <div class="sheet-head">
          <h3>{{ title }}</h3>
          <button class="close-btn" @click="close">✕</button>
        </div>

        <button v-if="showAll"
          class="folder-row" :class="{ on: selected === -1 }"
          @click="pick(-1)">
          {{ allLabel }}
        </button>
        <button v-if="showNone"
          class="folder-row" :class="{ on: selected === 0 }"
          @click="pick(0)">
          📭 {{ noneLabel }}
        </button>
        <hr v-if="showAll || showNone">

        <button v-for="f in folders.flat" :key="f.id"
          class="folder-row" :class="{ on: selected === f.id }"
          :style="{ paddingLeft: (12 + f.depth * 16) + 'px' }"
          @click="pick(f.id)">
          📁 {{ f.name }} <span class="muted small">({{ f.count }})</span>
        </button>
      </div>
    </div>
  </transition>
</template>

<style scoped>
.sheet-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.5);
  z-index: 200;
  display: flex; align-items: flex-end;
}
.sheet {
  width: 100%;
  max-height: 80vh;
  background: var(--s1);
  border-top-left-radius: 18px;
  border-top-right-radius: 18px;
  padding: 14px 16px calc(20px + env(safe-area-inset-bottom));
  overflow-y: auto;
}
.sheet-handle { width: 40px; height: 4px; background: var(--border2); border-radius: 2px; margin: -4px auto 12px; }
.sheet-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.sheet-head h3 { margin: 0; font-size: 14px; text-transform: uppercase; color: var(--text-mute); letter-spacing: .5px; }
.close-btn { font-size: 22px; color: var(--text-mute); padding: 4px 8px; }

.folder-row {
  display: block; width: 100%;
  text-align: left;
  padding: 12px;
  border-radius: var(--radius);
  font-size: 14px;
  color: var(--text);
}
.folder-row:active { background: var(--s2); }
.folder-row.on    { background: var(--accent-lo); color: var(--accent); font-weight: 500; }
hr { border: 0; border-top: 1px solid var(--border); margin: 8px 0; }
.small { font-size: 11px; }

.sheet-enter-active, .sheet-leave-active { transition: opacity .25s; }
.sheet-enter-active .sheet, .sheet-leave-active .sheet { transition: transform .25s; }
.sheet-enter-from, .sheet-leave-to { opacity: 0; }
.sheet-enter-from .sheet, .sheet-leave-to .sheet { transform: translateY(100%); }
</style>
