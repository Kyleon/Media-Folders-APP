import { defineStore } from 'pinia';

const THEME_KEY = 'ypva.theme';

function initialTheme() {
  const saved = localStorage.getItem(THEME_KEY);
  if (saved === 'light' || saved === 'dark') return saved;
  return window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
}

export const useUiStore = defineStore('ui', {
  state: () => ({
    theme: initialTheme(),
    toasts: [],
  }),
  actions: {
    setTheme(t) {
      if (t !== 'light' && t !== 'dark') return;
      this.theme = t;
      localStorage.setItem(THEME_KEY, t);
      document.documentElement.setAttribute('data-theme', t);
      document.querySelector('meta[name="theme-color"]')
        ?.setAttribute('content', t === 'light' ? '#fafafa' : '#0f0f0f');
    },
    toggleTheme() {
      this.setTheme(this.theme === 'dark' ? 'light' : 'dark');
    },
    applyTheme() {
      document.documentElement.setAttribute('data-theme', this.theme);
    },
    toast(message, type = 'info', timeout = 2800) {
      const id = Date.now() + Math.random();
      this.toasts.push({ id, message, type });
      setTimeout(() => {
        this.toasts = this.toasts.filter(t => t.id !== id);
      }, timeout);
    },
  },
});
