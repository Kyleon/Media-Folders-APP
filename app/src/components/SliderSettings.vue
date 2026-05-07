<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: { type: Object, required: true },
});
const emit = defineEmits(['update:modelValue', 'change']);

const settings = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
});

function patch(field, value) {
  emit('change', { [field]: value });
}
</script>

<template>
  <div class="settings">
    <div class="row">
      <label class="field">
        <span class="muted small">Altura</span>
        <input
          type="text"
          :value="settings.height"
          placeholder="100vh"
          @input="patch('height', $event.target.value)"
        />
      </label>

      <label class="field">
        <span class="muted small">Velocidad (ms)</span>
        <input
          type="number"
          min="500"
          step="500"
          :value="settings.speed"
          @input="patch('speed', Number($event.target.value))"
        />
      </label>
    </div>

    <div class="row">
      <label class="field">
        <span class="muted small">Transición</span>
        <select
          :value="settings.transition"
          @change="patch('transition', $event.target.value)"
        >
          <option value="slide">Deslizar</option>
          <option value="fade">Fundido</option>
        </select>
      </label>

      <label class="field">
        <span class="muted small">Paginación</span>
        <select
          :value="settings.pagination"
          @change="patch('pagination', $event.target.value)"
        >
          <option value="bullets">Bullets</option>
          <option value="progress">Barra de progreso</option>
          <option value="none">Sin paginación</option>
        </select>
      </label>
    </div>

    <div class="checks">
      <label class="check">
        <input
          type="checkbox"
          :checked="settings.autoplay"
          @change="patch('autoplay', $event.target.checked)"
        />
        <span>Autoplay</span>
      </label>

      <label class="check">
        <input
          type="checkbox"
          :checked="settings.loop"
          @change="patch('loop', $event.target.checked)"
        />
        <span>Loop infinito</span>
      </label>

      <label class="check">
        <input
          type="checkbox"
          :checked="settings.navigation"
          @change="patch('navigation', $event.target.checked)"
        />
        <span>Flechas de navegación</span>
      </label>

      <label class="check">
        <input
          type="checkbox"
          :checked="settings.kenburns"
          @change="patch('kenburns', $event.target.checked)"
        />
        <span>Efecto kenburns global</span>
      </label>
    </div>
  </div>
</template>

<style scoped>
.settings { display: flex; flex-direction: column; gap: 12px; }
.row { display: flex; gap: 8px; flex-wrap: wrap; }
.field { display: flex; flex-direction: column; gap: 4px; flex: 1; min-width: 140px; }
.field input, .field select {
  background: var(--s2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  color: var(--text);
  padding: 8px 10px;
  font-size: 14px;
}
.checks { display: flex; flex-wrap: wrap; gap: 8px 16px; }
.check { display: inline-flex; align-items: center; gap: 8px; cursor: pointer; user-select: none; }
.check input { accent-color: var(--accent); }
.muted { color: var(--text-mute); }
.small { font-size: 11px; }
</style>
