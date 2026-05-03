/**
 * Helpers de endpoint del namespace yzmf/v1.
 * Cada función devuelve la promesa del cliente fetch.
 */

import { api } from './client';

const NS = 'wp-json/yzmf/v1/';

export const FoldersAPI = {
  list:   ()                  => api.get(NS + 'folders'),
  create: (name, parent = 0)  => api.post(NS + 'folders', { name, parent }),
  rename: (id, name)          => api.put(NS + 'folders/' + id, { name }),
  move:   (id, parent)        => api.put(NS + 'folders/' + id, { parent }),
  remove: (id)                => api.del(NS + 'folders/' + id),
};

export const MediaAPI = {
  list:   (params)            => api.get(NS + 'media', params),
  detail: (id)                => api.get(NS + 'media/' + id),
  update: (id, body)          => api.put(NS + 'media/' + id, body),
  remove: (id)                => api.del(NS + 'media/' + id),
  setFolder: (id, folder_id)  => api.put(NS + 'media/' + id + '/folder', { folder_id }),
  upload: (file, folder_id)   => {
    const fd = new FormData();
    fd.append('file', file);
    if (folder_id) fd.append('folder_id', folder_id);
    return api.upload(NS + 'media', fd);
  },
  generateAI: (id)            => api.post(NS + 'media/' + id + '/ai'),
  setGeo: (id, body)          => api.put(NS + 'media/' + id + '/geo', body),
  bulkGeo: (ids, body)        => api.post(NS + 'media/geo/bulk', { ids, ...body }),
  listGeo: (limit = 1000)     => api.get(NS + 'media/geo/all', { limit }),
};

export const MapAPI = {
  publicData:    ()        => api.get(NS + 'map/data'),
  list:          ()        => api.get(NS + 'map/locations'),
  create:        (body)    => api.post(NS + 'map/locations', body),
  update:        (id, body)=> api.put(NS + 'map/locations/' + id, body),
  remove:        (id)      => api.del(NS + 'map/locations/' + id),
};

export const GeoAPI = {
  search:  (q)             => api.get(NS + 'geocode/search',  { q }),
  reverse: (lat, lng)      => api.get(NS + 'geocode/reverse', { lat, lng }),
};

export const PortfoliosAPI = {
  list:    (params)        => api.get(NS + 'portfolios', params),
  detail:  (id)            => api.get(NS + 'portfolios/' + id),
  create:  (body)          => api.post(NS + 'portfolios', body),
  update:  (id, body)      => api.put(NS + 'portfolios/' + id, body),
  remove:  (id, force=false) => api.del(NS + 'portfolios/' + id, { force: force ? 1 : 0 }),
  gallery: (id)            => api.get(NS + 'portfolios/' + id + '/gallery'),
  setGallery: (id, ids)    => api.put(NS + 'portfolios/' + id + '/gallery', { gallery: ids }),
  syncFolder: (id, folder_id, orderby='date', order='ASC') =>
    api.post(NS + 'portfolios/' + id + '/sync-folder', { folder_id, orderby, order }),
  getMeta:    (id)              => api.get(NS + 'portfolios/' + id + '/meta'),
  setMeta:    (id, values)      => api.put(NS + 'portfolios/' + id + '/meta', { values }),
};

export const StatsAPI = {
  get: () => api.get(NS + 'stats'),
};

export const PortfolioCatsAPI = {
  list:   ()                 => api.get(NS + 'portfolio-categories'),
  create: (name, parent=0)   => api.post(NS + 'portfolio-categories', { name, parent }),
  update: (id, body)         => api.put(NS + 'portfolio-categories/' + id, body),
  remove: (id)               => api.del(NS + 'portfolio-categories/' + id),
};
