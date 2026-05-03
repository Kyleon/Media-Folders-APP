<template>
  <span class="spinner" :style="{ width: size+'px', height: size+'px' }" />
</template>

<script setup>
defineProps({ size: { type: Number, default: 20 } });
</script>
