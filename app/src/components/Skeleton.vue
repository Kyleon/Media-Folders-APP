<script setup>
defineProps({
  // 'card' | 'thumb' | 'row' | 'text' | 'circle'
  variant: { type: String, default: 'card' },
  count:   { type: Number, default: 1 },
  width:   { type: String, default: '100%' },
  height:  { type: String, default: '' },
});
</script>

<template>
  <template v-for="i in count" :key="i">
    <div class="skel" :class="variant" :style="{ width, height: height || undefined }"></div>
  </template>
</template>

<style scoped>
.skel {
  background: linear-gradient(90deg,
    var(--s2) 0%,
    var(--s3) 40%,
    var(--s2) 80%);
  background-size: 200% 100%;
  animation: shimmer 1.4s ease-in-out infinite;
  border-radius: var(--radius);
}
@keyframes shimmer {
  0%   { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* Variantes */
.skel.thumb {
  aspect-ratio: 1;
  border-radius: var(--radius);
}
.skel.card {
  height: 80px;
  margin-bottom: 8px;
}
.skel.row {
  height: 16px;
  margin-bottom: 6px;
}
.skel.text {
  height: 12px;
  border-radius: 3px;
  margin-bottom: 4px;
}
.skel.circle {
  width: 40px; height: 40px;
  border-radius: 50%;
}
</style>
