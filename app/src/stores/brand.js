import { defineStore } from 'pinia';
import { BrandAPI } from '../api/endpoints';

/**
 * Marca de la app: logo, nombre, color principal.
 * Se carga al iniciar y se cachea localStorage para evitar flicker entre cargas.
 */
export const useBrandStore = defineStore('brand', {
  state: () => ({
    name: '',
    initials: 'YZ',
    logoUrl: '',
    logoMime: '',
    logoId: 0,
    primaryColor: '',
    loaded: false,
  }),
  actions: {
    hydrateFromCache() {
      try {
        const raw = localStorage.getItem('ypva.brand');
        if (raw) {
          const b = JSON.parse(raw);
          this.name         = b.name || '';
          this.initials     = b.initials || 'YZ';
          this.logoUrl      = b.logoUrl || '';
          this.logoMime     = b.logoMime || '';
          this.logoId       = b.logoId || 0;
          this.primaryColor = b.primaryColor || '';
        }
      } catch {}
    },
    persist() {
      try {
        localStorage.setItem('ypva.brand', JSON.stringify({
          name: this.name, initials: this.initials,
          logoUrl: this.logoUrl, logoMime: this.logoMime, logoId: this.logoId,
          primaryColor: this.primaryColor,
        }));
      } catch {}
    },
    apply(data) {
      this.name         = data.name || '';
      this.initials     = data.initials || 'YZ';
      this.logoUrl      = data.logo_url || '';
      this.logoMime     = data.logo_mime || '';
      this.logoId       = data.logo_id || 0;
      this.primaryColor = data.primary_color || '';
      this.loaded = true;
      this.applyTheme();
      this.persist();
    },
    applyTheme() {
      if (this.primaryColor && /^#[0-9A-F]{6}$/i.test(this.primaryColor)) {
        document.documentElement.style.setProperty('--accent', this.primaryColor);
      } else {
        document.documentElement.style.removeProperty('--accent');
      }
    },
    async load(force = false) {
      if (this.loaded && !force) return;
      try {
        const data = await BrandAPI.get();
        this.apply(data);
      } catch (e) {
        // si falla, dejamos lo que haya en cache
      }
    },
    async save(patch) {
      const data = await BrandAPI.set(patch);
      this.apply(data);
      return data;
    },
  },
});
