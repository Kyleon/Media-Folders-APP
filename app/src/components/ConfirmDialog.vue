<script setup>
import { ref } from 'vue';
import { useFocusTrap } from '../composables/useFocusTrap';

/**
 * Reemplazo accesible de window.confirm/prompt.
 *
 * Uso:
 *   const dlg = ref(null);
 *   <ConfirmDialog ref="dlg" />
 *   const ok = await dlg.value.confirm({ title, message, confirmText, destructive });
 *   const name = await dlg.value.prompt({ title, message, defaultValue });
 *   (devuelve null si cancela el prompt)
 */
const state = ref({
  open: false,
  mode: 'confirm', // 'confirm' | 'prompt'
  title: '',
  message: '',
  confirmText: 'Aceptar',
  cancelText: 'Cancelar',
  destructive: false,
  inputValue: '',
});
let resolver = null;

const sheetEl = ref(null);
useFocusTrap(sheetEl, () => state.value.open, () => onCancel());

function onConfirm() {
  state.value.open = false;
  if (state.value.mode === 'prompt') resolver?.(state.value.inputValue);
  else resolver?.(true);
  resolver = null;
}
function onCancel() {
  state.value.open = false;
  if (state.value.mode === 'prompt') resolver?.(null);
  else resolver?.(false);
  resolver = null;
}

function confirm(opts = {}) {
  state.value = {
    ...state.value,
    open: true, mode: 'confirm',
    title: opts.title || 'Confirmar',
    message: opts.message || '',
    confirmText: opts.confirmText || 'Aceptar',
    cancelText: opts.cancelText || 'Cancelar',
    destructive: !!opts.destructive,
  };
  return new Promise(res => { resolver = res; });
}
function prompt(opts = {}) {
  state.value = {
    ...state.value,
    open: true, mode: 'prompt',
    title: opts.title || '',
    message: opts.message || '',
    confirmText: opts.confirmText || 'Aceptar',
    cancelText: opts.cancelText || 'Cancelar',
    destructive: false,
    inputValue: opts.defaultValue || '',
  };
  return new Promise(res => { resolver = res; });
}

defineExpose({ confirm, prompt });
</script>

<template>
  <transition name="fade">
    <div v-if="state.open" class="cd-overlay" @click.self="onCancel">
      <div ref="sheetEl" class="cd-box"
        :role="state.destructive ? 'alertdialog' : 'dialog'"
        aria-modal="true"
        aria-labelledby="cd-title"
        :aria-describedby="state.message ? 'cd-message' : undefined"
        tabindex="-1">
        <h3 id="cd-title" class="cd-title">{{ state.title }}</h3>
        <p v-if="state.message" id="cd-message" class="cd-msg">{{ state.message }}</p>

        <input v-if="state.mode === 'prompt'"
          v-model="state.inputValue"
          class="cd-input"
          autofocus
          @keyup.enter="onConfirm" />

        <div class="cd-actions">
          <button class="btn ghost" @click="onCancel">{{ state.cancelText }}</button>
          <button class="btn"
            :class="state.destructive ? 'danger' : 'pri'"
            @click="onConfirm">
            {{ state.confirmText }}
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<style scoped>
.cd-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.55);
  z-index: 1400;
  display: flex; align-items: center; justify-content: center;
  padding: 16px;
}
.cd-box {
  width: 100%; max-width: 420px;
  background: var(--s1);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 18px 20px;
  display: flex; flex-direction: column; gap: 12px;
}
.cd-title { margin: 0; font-size: 16px; font-weight: 600; }
.cd-msg   { margin: 0; color: var(--text-mute); font-size: 14px; line-height: 1.4; }
.cd-input { width: 100%; }
.cd-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 4px; }
.fade-enter-active, .fade-leave-active { transition: opacity .15s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
