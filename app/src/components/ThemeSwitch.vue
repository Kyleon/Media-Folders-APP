<script setup>
import { useUiStore } from '../stores/ui';
const ui = useUiStore();
</script>

<template>
  <button class="theme-switch" @click="ui.toggleTheme()" :title="ui.theme === 'dark' ? 'Cambiar a claro' : 'Cambiar a oscuro'">
    <span v-if="ui.theme === 'dark'">☀ Claro</span>
    <span v-else>🌙 Oscuro</span>
  </button>
</template>

<style scoped>
.theme-switch {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  border-radius: var(--radius);
  background: var(--s2);
  border: 1px solid var(--border);
  color: var(--text);
  font-size: 13px;
  min-height: var(--tap);
}
.theme-switch:active { background: var(--s3); }
</style>
