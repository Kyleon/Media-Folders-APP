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
    /** Ref del componente ConfirmDialog montado en App.vue — wireado por
     *  registerConfirmDialog(). null si aún no se ha registrado.            */
    _confirmDialog: null,
  }),
  actions: {
    /** App.vue llama esto pasando la ref del ConfirmDialog. */
    registerConfirmDialog(ref) { this._confirmDialog = ref; },
    /** Reemplazo accesible de window.confirm. Devuelve Promise<boolean>. */
    confirm(opts) {
      if (!this._confirmDialog) return Promise.resolve(window.confirm(opts.message || ''));
      return this._confirmDialog.confirm(opts);
    },
    /** Reemplazo accesible de window.prompt. Devuelve Promise<string|null>. */
    prompt(opts) {
      if (!this._confirmDialog) return Promise.resolve(window.prompt(opts.message || '', opts.defaultValue || ''));
      return this._confirmDialog.prompt(opts);
    },
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
