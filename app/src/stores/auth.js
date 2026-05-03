import { defineStore } from 'pinia';

const STORAGE_KEY = 'ypva.auth';

function loadFromStorage() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    return raw ? JSON.parse(raw) : null;
  } catch { return null; }
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    creds: loadFromStorage(), // { baseUrl, username, appPassword, displayName? }
  }),
  getters: {
    isAuthed: (s) => !!s.creds,
  },
  actions: {
    login(creds) {
      // Normalizar baseUrl: garantizar trailing slash y sin doble
      const baseUrl = (creds.baseUrl || '').replace(/\/+$/, '') + '/';
      const next = { ...creds, baseUrl };
      this.creds = next;
      localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
    },
    logout() {
      this.creds = null;
      localStorage.removeItem(STORAGE_KEY);
    },
  },
});
