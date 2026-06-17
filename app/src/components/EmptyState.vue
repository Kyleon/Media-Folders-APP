<script setup>
/**
 * Estado vacío con CTA opcional. Sustituye al "📭 Sin resultados" sin
 * contexto por un mensaje útil con sugerencia de acción.
 *
 * Uso:
 *   <EmptyState icon="📭" title="Sin imágenes" description="Sube tu primera foto."
 *               actionLabel="Subir" @action="goUpload" />
 */
defineProps({
  icon:        { type: String, default: '📭' },
  title:       { type: String, default: 'Sin resultados' },
  description: { type: String, default: '' },
  actionLabel: { type: String, default: '' },
});
defineEmits(['action']);
</script>

<template>
  <div class="empty-state" role="status">
    <div class="es-icon" aria-hidden="true">{{ icon }}</div>
    <h2 class="es-title">{{ title }}</h2>
    <p v-if="description" class="es-desc muted">{{ description }}</p>
    <button v-if="actionLabel" class="btn pri" @click="$emit('action')" style="margin-top:12px">
      {{ actionLabel }}
    </button>
  </div>
</template>

<style scoped>
.empty-state {
  display: flex; flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 48px 20px;
  gap: 6px;
}
.es-icon  { font-size: 42px; line-height: 1; opacity: .7; }
.es-title { margin: 8px 0 0; font-size: 16px; font-weight: 600; }
.es-desc  { margin: 4px 0 0; font-size: 13px; max-width: 320px; line-height: 1.4; }
</style>
