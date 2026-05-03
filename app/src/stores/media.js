import { defineStore } from 'pinia';
import { MediaAPI } from '../api/endpoints';

export const useMediaStore = defineStore('media', {
  state: () => ({
    items: [],
    total: 0,
    pages: 1,
    page: 1,
    loading: false,
    filter: {
      folder: -1,    // -1 todas, 0 sin carpeta, >0 id
      search: '',
      orderby: 'date',
      order: 'DESC',
      mime: '',
      tag: '',     // tag IA opcional
      color: '',   // color hex opcional (#RRGGBB)
    },
    // Selección múltiple
    selectMode: false,
    selectedIds: [],
  }),
  getters: {
    selectedCount: (s) => s.selectedIds.length,
    isSelected:    (s) => (id) => s.selectedIds.includes(id),
  },
  actions: {
    async load(reset = false) {
      if (reset) { this.page = 1; this.items = []; }
      this.loading = true;
      try {
        const res = await MediaAPI.list({
          page: this.page,
          per_page: 40,
          folder: this.filter.folder,
          search: this.filter.search,
          orderby: this.filter.orderby,
          order: this.filter.order,
          mime: this.filter.mime,
          tag:   this.filter.tag,
          color: this.filter.color,
        });
        this.items = reset || this.page === 1 ? res.images : [...this.items, ...res.images];
        this.total = res.total;
        this.pages = res.pages;
      } finally {
        this.loading = false;
      }
    },
    async loadMore() {
      if (this.page >= this.pages || this.loading) return;
      this.page++;
      await this.load(false);
    },
    setFilter(patch) {
      Object.assign(this.filter, patch);
    },
    async removeOne(id) {
      await MediaAPI.remove(id);
      this.items = this.items.filter(i => i.id !== id);
      this.total = Math.max(0, this.total - 1);
    },
    async update(id, body) {
      const updated = await MediaAPI.update(id, body);
      const idx = this.items.findIndex(i => i.id === id);
      if (idx !== -1) this.items[idx] = updated;
      return updated;
    },
    async setFolder(id, folder_id) {
      await MediaAPI.setFolder(id, folder_id);
      if (this.filter.folder > 0 && folder_id !== this.filter.folder) {
        this.items = this.items.filter(i => i.id !== id);
      }
    },
    async generateAI(id) {
      const r = await MediaAPI.generateAI(id);
      const idx = this.items.findIndex(i => i.id === id);
      if (idx !== -1) {
        this.items[idx].alt     = r.alt;
        this.items[idx].caption = r.caption;
      }
      return r;
    },

    // ── Selección múltiple ──
    enterSelectMode(id) {
      this.selectMode = true;
      if (id !== undefined && !this.selectedIds.includes(id)) {
        this.selectedIds.push(id);
      }
    },
    exitSelectMode() {
      this.selectMode = false;
      this.selectedIds = [];
    },
    toggleSelect(id) {
      const idx = this.selectedIds.indexOf(id);
      if (idx === -1) this.selectedIds.push(id);
      else            this.selectedIds.splice(idx, 1);
      if (this.selectedIds.length === 0) this.selectMode = false;
    },
    selectAllVisible() {
      this.selectedIds = this.items.map(i => i.id);
    },

    // ── Acciones masivas ──
    async bulkMoveTo(folder_id) {
      const ids = [...this.selectedIds];
      const errors = [];
      for (const id of ids) {
        try { await MediaAPI.setFolder(id, folder_id); }
        catch (e) { errors.push({ id, error: e.message }); }
      }
      // Si la lista actual está filtrada por una carpeta y las imágenes ya no pertenecen, sacarlas
      if (this.filter.folder > 0 && folder_id !== this.filter.folder) {
        this.items = this.items.filter(i => !ids.includes(i.id));
      }
      this.exitSelectMode();
      return { moved: ids.length - errors.length, errors };
    },
    async bulkDelete() {
      const ids = [...this.selectedIds];
      const errors = [];
      for (const id of ids) {
        try { await MediaAPI.remove(id); }
        catch (e) { errors.push({ id, error: e.message }); }
      }
      this.items = this.items.filter(i => !ids.includes(i.id));
      this.total = Math.max(0, this.total - (ids.length - errors.length));
      this.exitSelectMode();
      return { deleted: ids.length - errors.length, errors };
    },
    async bulkGenerateAI(onProgress) {
      const ids = [...this.selectedIds];
      let done = 0, errors = 0;
      for (const id of ids) {
        try {
          const r = await MediaAPI.generateAI(id);
          const idx = this.items.findIndex(i => i.id === id);
          if (idx !== -1) {
            this.items[idx].alt     = r.alt;
            this.items[idx].caption = r.caption;
          }
          done++;
        } catch (e) {
          errors++;
        }
        if (onProgress) onProgress({ done, errors, total: ids.length });
      }
      this.exitSelectMode();
      return { done, errors, total: ids.length };
    },
    bulkCopyUrls() {
      const urls = this.selectedIds
        .map(id => this.items.find(i => i.id === id)?.url)
        .filter(Boolean);
      return urls;
    },
    async setGeo(id, body) {
      const r = await MediaAPI.setGeo(id, body);
      const idx = this.items.findIndex(i => i.id === id);
      if (idx !== -1) this.items[idx] = r;
      return r;
    },
    async bulkGeo(body) {
      const ids = [...this.selectedIds];
      const r = await MediaAPI.bulkGeo(ids, body);
      // Actualizar geo en items locales
      ids.forEach(id => {
        const item = this.items.find(i => i.id === id);
        if (item) {
          if (body.lat == null || body.lng == null) item.geo = null;
          else item.geo = { lat: body.lat, lng: body.lng, place: body.place || '', source: 'manual' };
        }
      });
      this.exitSelectMode();
      return r;
    },
  },
});
