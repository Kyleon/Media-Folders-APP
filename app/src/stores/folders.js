import { defineStore } from 'pinia';
import { FoldersAPI } from '../api/endpoints';

function flatten(nodes, depth = 0, out = []) {
  for (const n of nodes || []) {
    out.push({ id: n.id, name: n.name, parent: n.parent, count: n.count, depth });
    if (n.children?.length) flatten(n.children, depth + 1, out);
  }
  return out;
}

export const useFoldersStore = defineStore('folders', {
  state: () => ({
    tree: [],
    loading: false,
  }),
  getters: {
    flat: (s) => flatten(s.tree),
    byId: (s) => Object.fromEntries(flatten(s.tree).map(f => [f.id, f])),
  },
  actions: {
    async load(force = false) {
      if (this.tree.length && !force) return;
      this.loading = true;
      try {
        this.tree = await FoldersAPI.list();
      } finally {
        this.loading = false;
      }
    },
    async create(name, parent = 0) {
      await FoldersAPI.create(name, parent);
      await this.load(true);
    },
    async rename(id, name) {
      await FoldersAPI.rename(id, name);
      await this.load(true);
    },
    async move(id, parent) {
      await FoldersAPI.move(id, parent);
      await this.load(true);
    },
    async remove(id) {
      await FoldersAPI.remove(id);
      await this.load(true);
    },
    /** Devuelve los IDs descendientes de una carpeta (incluida ella) — útil para excluir destinos inválidos */
    descendantIds(id) {
      const out = new Set([id]);
      const walk = (nodes) => {
        for (const n of nodes || []) {
          if (out.has(n.parent) || n.id === id) {
            out.add(n.id);
            if (n.children?.length) walk(n.children);
          } else if (n.children?.length) walk(n.children);
        }
      };
      walk(this.tree);
      return out;
    },
  },
});
