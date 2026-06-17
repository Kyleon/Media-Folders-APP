<script setup>
import { computed } from 'vue';
import { useUiStore } from '../stores/ui';
const ui = useUiStore();

// Separamos toasts informativos vs errores para asignar aria-live distintos.
// 'polite' espera a una pausa natural del lector; 'assertive' interrumpe.
const polite    = computed(() => ui.toasts.filter(t => t.type !== 'err'));
const assertive = computed(() => ui.toasts.filter(t => t.type === 'err'));
</script>

<template>
  <!-- Dos contenedores con live-regions distintas. Los lectores de pantalla
       anuncian los toasts automáticamente al cambiar el contenido. -->
  <div role="status" aria-live="polite" aria-atomic="true" class="toast-region polite">
    <transition-group name="t" tag="div">
      <div v-for="t in polite" :key="t.id" class="toast" :class="t.type">
        {{ t.message }}
      </div>
    </transition-group>
  </div>
  <div role="alert" aria-live="assertive" aria-atomic="true" class="toast-region assertive">
    <transition-group name="t" tag="div">
      <div v-for="t in assertive" :key="t.id" class="toast" :class="t.type">
        {{ t.message }}
      </div>
    </transition-group>
  </div>
</template>

<style scoped>
.toast-region { /* solo wrapper invisible para semántica aria */ }
.t-enter-active, .t-leave-active { transition: opacity .3s, transform .3s; }
.t-enter-from, .t-leave-to       { opacity: 0; transform: translate(-50%, 8px); }
</style>
