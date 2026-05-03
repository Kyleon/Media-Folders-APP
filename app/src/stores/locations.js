import { defineStore } from 'pinia';
import { MapAPI } from '../api/endpoints';

export const useLocationsStore = defineStore('locations', {
  state: () => ({
    items: [],
    loading: false,
  }),
  actions: {
    async load() {
      this.loading = true;
      try {
        this.items = await MapAPI.list();
      } finally {
        this.loading = false;
      }
    },
    async save(body) {
      const r = await MapAPI.create(body); // POST sirve para create y update (con id en body)
      await this.load();
      return r;
    },
    async update(id, body) {
      const r = await MapAPI.update(id, body);
      await this.load();
      return r;
    },
    async remove(id) {
      await MapAPI.remove(id);
      this.items = this.items.filter(l => l.id !== id);
    },
  },
});
