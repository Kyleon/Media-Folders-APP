import { defineStore } from 'pinia';
import { PortfoliosAPI, PortfolioCatsAPI } from '../api/endpoints';

export const usePortfoliosStore = defineStore('portfolios', {
  state: () => ({
    items: [],
    total: 0,
    pages: 1,
    page: 1,
    loading: false,
    categories: [],
    search: '',
    categoryFilter: 0,
  }),
  actions: {
    async load(reset = false) {
      if (reset) this.page = 1;
      this.loading = true;
      try {
        const res = await PortfoliosAPI.list({
          page: this.page,
          per_page: 20,
          search: this.search,
          status: 'any',
          category: this.categoryFilter,
        });
        this.items = res.items;
        this.total = res.total;
        this.pages = res.pages;
      } finally {
        this.loading = false;
      }
    },
    async loadCategories() {
      this.categories = await PortfolioCatsAPI.list();
    },
    async create(body)              { return PortfoliosAPI.create(body); },
    async update(id, body)          { return PortfoliosAPI.update(id, body); },
    async remove(id, force = false) { return PortfoliosAPI.remove(id, force); },
    async detail(id)                { return PortfoliosAPI.detail(id); },
    async syncFolder(id, folder_id, orderby, order) {
      return PortfoliosAPI.syncFolder(id, folder_id, orderby, order);
    },
    async createCategory(name, parent = 0) {
      const r = await PortfolioCatsAPI.create(name, parent);
      await this.loadCategories();
      return r;
    },
  },
});
