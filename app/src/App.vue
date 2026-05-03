<script setup>
import { onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import { useUiStore } from './stores/ui';
import { useAuthStore } from './stores/auth';
import AppShell from './components/AppShell.vue';
import Toast from './components/Toast.vue';
import UpdateBanner from './components/UpdateBanner.vue';

const ui    = useUiStore();
const auth  = useAuthStore();
const route = useRoute();

onMounted(() => ui.applyTheme());

const showShell = computed(() => auth.isAuthed && !route.meta.public);
</script>

<template>
  <AppShell v-if="showShell">
    <router-view />
  </AppShell>
  <router-view v-else />
  <Toast />
  <UpdateBanner />
</template>
