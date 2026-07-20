import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';

// Mock de la capa de endpoints para no tocar red.
vi.mock('../api/endpoints', () => ({
  FeedAPI: {
    list: vi.fn(),
    hashtags: vi.fn(),
    detail: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    remove: vi.fn(),
  },
  FeedCommentsAPI: {
    list: vi.fn(),
    moderate: vi.fn(),
    remove: vi.fn(),
  },
}));

import { FeedAPI } from '../api/endpoints';
import { useFeedStore } from './feed';

describe('feed store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('arranca con estado vacío', () => {
    const s = useFeedStore();
    expect(s.items).toEqual([]);
    expect(s.total).toBe(0);
    expect(s.filter.hashtag).toBe('');
  });

  it('load(reset) pide la primera página y guarda items/total/pages', async () => {
    FeedAPI.list.mockResolvedValue({
      items: [{ id: 1 }, { id: 2 }],
      total: 2,
      pages: 1,
    });
    const s = useFeedStore();
    await s.load(true);

    expect(FeedAPI.list).toHaveBeenCalledWith(expect.objectContaining({ page: 1, per_page: 20 }));
    expect(s.items).toHaveLength(2);
    expect(s.total).toBe(2);
    expect(s.pages).toBe(1);
    expect(s.loading).toBe(false);
  });

  it('loadMore concatena la siguiente página', async () => {
    FeedAPI.list
      .mockResolvedValueOnce({ items: [{ id: 1 }], total: 2, pages: 2 })
      .mockResolvedValueOnce({ items: [{ id: 2 }], total: 2, pages: 2 });
    const s = useFeedStore();
    await s.load(true);
    await s.loadMore();

    expect(s.page).toBe(2);
    expect(s.items.map(i => i.id)).toEqual([1, 2]);
  });

  it('remove quita el item localmente y decrementa el total', async () => {
    FeedAPI.list.mockResolvedValue({ items: [{ id: 1 }, { id: 2 }], total: 2, pages: 1 });
    FeedAPI.remove.mockResolvedValue({ id: 1, deleted: true });
    const s = useFeedStore();
    await s.load(true);
    await s.remove(1);

    expect(FeedAPI.remove).toHaveBeenCalledWith(1, false);
    expect(s.items.map(i => i.id)).toEqual([2]);
    expect(s.total).toBe(1);
  });

  it('setFilter fusiona el parche de filtros', () => {
    const s = useFeedStore();
    s.setFilter({ hashtag: 'retrato', status: 'publish' });
    expect(s.filter.hashtag).toBe('retrato');
    expect(s.filter.status).toBe('publish');
    expect(s.filter.search).toBe('');
  });
});
