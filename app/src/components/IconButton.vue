<script setup>
/**
 * Wrapper de botón con icono y aria-label obligatorio.
 *
 * Antes había decenas de <button>🗑</button> sin texto accesible. Este
 * componente fuerza el label para que ningún botón-icono nuevo se cuele
 * sin texto para lectores de pantalla.
 *
 * Uso:
 *   <IconButton label="Eliminar" @click="...">🗑</IconButton>
 *   <IconButton label="Cerrar" :pressed="open" @click="...">✕</IconButton>
 */
const props = defineProps({
  label:   { type: String, required: true },
  pressed: { type: Boolean, default: null },
  type:    { type: String, default: 'button' },
  disabled:{ type: Boolean, default: false },
});
</script>

<template>
  <button
    :type="type"
    :disabled="disabled"
    :aria-label="label"
    :aria-pressed="pressed === null ? undefined : String(pressed)"
    :title="label">
    <slot />
  </button>
</template>
