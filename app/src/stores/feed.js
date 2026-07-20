import { defineStore } from 'pinia';
import { FeedAPI, FeedCommentsAPI } from '../api/endpoints';

/**
 * Store del feed de publicaciones (tipo Instagram). Sigue el patrón de
 * media.js: paginación con loadMore() que concatena páginas, filtros por
 * texto/hashtag/estado, y acciones CRUD + moderación de comentarios.
 */
export const useFeedStore = defineStore('feed', {
  state: () => ({
    items: [],
    total: 0,
    pages: 1,
    page: 1,
    loading: false,
    filter: {
      search: '',
      hashtag: '',   // slug o nombre del hashtag; '' = todos
      status: '',    // '' = publish+draft, 'publish', 'draft'
    },
    hashtags: [],    // [{ tag, slug, count }]
  }),
  actions: {
    async load(reset = false) {
      if (reset) { this.page = 1; this.items = []; }
      this.loading = true;
      try {
        const res = await FeedAPI.list({
          page: this.page,
          per_page: 20,
          search: this.filter.search,
          hashtag: this.filter.hashtag,
          status: this.filter.status,
        });
        const items = res.items || [];
        this.items = reset || this.page === 1 ? items : [...this.items, ...items];
        this.total = res.total || 0;
        this.pages = res.pages || 1;
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
    async loadHashtags() {
      this.hashtags = await FeedAPI.hashtags();
    },
    async detail(id)         { return FeedAPI.detail(id); },
    async create(body)       { return FeedAPI.create(body); },
    async update(id, body)   { return FeedAPI.update(id, body); },
    async remove(id, force = false) {
      await FeedAPI.remove(id, force);
      this.items = this.items.filter(i => i.id !== id);
      this.total = Math.max(0, this.total - 1);
    },

    // ── Comentarios (moderación) ──
    async listComments(postId, status = 'all') {
      return FeedCommentsAPI.list(postId, status);
    },
    async moderateComment(cid, action) {
      return FeedCommentsAPI.moderate(cid, action);
    },
    async removeComment(cid) {
      return FeedCommentsAPI.remove(cid);
    },
  },
});
