<script setup>
import { useUiStore } from '../stores/ui';
const ui = useUiStore();
</script>

<template>
  <transition-group name="t" tag="div">
    <div v-for="t in ui.toasts" :key="t.id" class="toast" :class="t.type">
      {{ t.message }}
    </div>
  </transition-group>
</template>

<style scoped>
.t-enter-active, .t-leave-active { transition: opacity .3s, transform .3s; }
.t-enter-from, .t-leave-to       { opacity: 0; transform: translate(-50%, 8px); }
</style>
