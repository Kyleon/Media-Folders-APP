<script setup>
import { onMounted, computed, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useUiStore } from './stores/ui';
import { useAuthStore } from './stores/auth';
import AppShell from './components/AppShell.vue';
import Toast from './components/Toast.vue';
import UpdateBanner from './components/UpdateBanner.vue';
import SearchPalette from './components/SearchPalette.vue';
import ConfirmDialog from './components/ConfirmDialog.vue';
import { useKeyboardShortcuts } from './composables/useKeyboardShortcuts';
import { useAutoLogout } from './composables/useAutoLogout';

const ui     = useUiStore();
const auth   = useAuthStore();
const route  = useRoute();
const router = useRouter();

const confirmDialogRef = ref(null);
onMounted(() => {
  ui.applyTheme();
  ui.registerConfirmDialog(confirmDialogRef.value);
});

const showShell    = computed(() => auth.isAuthed && !route.meta.public && !route.meta.fullscreen);
const showPalette  = ref(false);

// Atajos globales (sólo cuando hay sesión)
useKeyboardShortcuts({
  'mod+k':  () => { if (auth.isAuthed) showPalette.value = true; },
  '/':      () => { if (auth.isAuthed) showPalette.value = true; },
  'g h':    () => { if (auth.isAuthed) router.push({ name: 'dashboard' }); },
  'm':      () => { if (auth.isAuthed) router.push({ name: 'media' }); },
  'p':      () => { if (auth.isAuthed) router.push({ name: 'portfolios' }); },
  'u':      () => { if (auth.isAuthed) router.push({ name: 'upload' }); },
  'f':      () => { if (auth.isAuthed) router.push({ name: 'folders' }); },
  'shift+m':() => { if (auth.isAuthed) router.push({ name: 'map' }); },
});

// Auto-logout por inactividad (configurable en Ajustes)
useAutoLogout(() => {
  if (!auth.isAuthed) return;
  ui.toast('🔒 Sesión cerrada por inactividad', 'ok');
  auth.logout();
  router.replace({ name: 'login' });
});
</script>

<template>
  <AppShell v-if="showShell">
    <router-view v-slot="{ Component, route: r }">
      <keep-alive :max="6">
        <component :is="Component" v-if="r.meta.keepAlive" :key="r.name" />
      </keep-alive>
      <component :is="Component" v-if="!r.meta.keepAlive" />
    </router-view>
  </AppShell>
  <router-view v-else />
  <Toast />
  <UpdateBanner />
  <SearchPalette v-model="showPalette" />
  <ConfirmDialog ref="confirmDialogRef" />
</template>
