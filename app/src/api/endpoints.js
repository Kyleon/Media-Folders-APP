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
  setFolderBulk: (ids, folder_id) => api.post(NS + 'media/folder/bulk', { ids, folder_id }),
  removeBulk: (ids)               => api.post(NS + 'media/delete/bulk', { ids }),
  upload: (file, folder_id)   => {
    const fd = new FormData();
    fd.append('file', file);
    if (folder_id) fd.append('folder_id', folder_id);
    return api.upload(NS + 'media', fd);
  },
  generateAI: (id)            => api.post(NS + 'media/' + id + '/ai'),
  setGeo: (id, body)          => api.put(NS + 'media/' + id + '/geo', body),
  bulkGeo: (ids, body)        => api.post(NS + 'media/geo/bulk', { ids, ...body }),
  listGeo: (params = {})      => {
    // Cache-buster: aunque /yzmf/v1/* envía no-cache headers, LSCache puede
    // estar sirviendo entradas guardadas antes de que esos headers se
    // añadieran. Un _t distinto por petición evita el cache hit.
    const p = typeof params === 'number' ? { limit: params } : { ...params };
    p._t = Date.now();
    return api.get(NS + 'media/geo/all', p);
  },
  scanExifStart:              () => api.post(NS + 'media/geo/scan-exif'),
  scanExifStatus:             () => api.get(NS + 'media/geo/scan-exif', { _t: Date.now() }),
  setPalette: (id, palette)   => api.put(NS + 'media/' + id + '/palette', { palette }),
  bulkRenamePreview: (ids, operation, params) =>
    api.post(NS + 'media/bulk-rename/preview', { ids, operation, params }),
  bulkRename: (ids, operation, params) =>
    api.post(NS + 'media/bulk-rename', { ids, operation, params }),
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
  duplicate: (id, body = {}) => api.post(NS + 'portfolios/' + id + '/duplicate', body),
  getMeta:    (id)              => api.get(NS + 'portfolios/' + id + '/meta'),
  setMeta:    (id, values)      => api.put(NS + 'portfolios/' + id + '/meta', { values }),
  listGeo:    ()                => api.get(NS + 'portfolios/geo/all'),
};

export const StatsAPI = {
  get:    (opts = {}) => api.get(NS + 'stats', opts.fresh ? { fresh: 1 } : {}),
  exif:   () => api.get(NS + 'stats/exif'),
  tags:   () => api.get(NS + 'tags'),
  colors: () => api.get(NS + 'colors'),
};

export const PortfolioCatsAPI = {
  list:   ()                 => api.get(NS + 'portfolio-categories'),
  create: (name, parent=0)   => api.post(NS + 'portfolio-categories', { name, parent }),
  update: (id, body)         => api.put(NS + 'portfolio-categories/' + id, body),
  remove: (id)               => api.del(NS + 'portfolio-categories/' + id),
};

/**
 * Marca: logo + nombre + color que aparecen en la cabecera de la PWA.
 */
export const BrandAPI = {
  get: ()       => api.get(NS + 'brand'),
  set: (body)   => api.put(NS + 'brand', body),
};

/**
 * Usuarios: usamos directamente los endpoints nativos de WordPress.
 * Necesita rol con capability 'list_users' / 'create_users' / etc.
 */
const WP_USERS_NS = 'wp-json/wp/v2/users';
export const UsersAPI = {
  list:    (params = {}) => api.get(WP_USERS_NS, { context: 'edit', per_page: 50, ...params }),
  detail:  (id)          => api.get(WP_USERS_NS + '/' + id, { context: 'edit' }),
  me:      ()            => api.get(WP_USERS_NS + '/me',   { context: 'edit' }),
  create:  (body)        => api.post(WP_USERS_NS, body),
  update:  (id, body)    => api.put(WP_USERS_NS + '/' + id, body),
  remove:  (id, reassign = null) => {
    const q = { force: true };
    if (reassign != null) q.reassign = reassign;
    return api.del(WP_USERS_NS + '/' + id, q);
  },
  // Application Passwords (REST nativo)
  appPasswords: {
    list:   (uid)        => api.get(WP_USERS_NS + '/' + uid + '/application-passwords', { context: 'edit' }),
    create: (uid, name)  => api.post(WP_USERS_NS + '/' + uid + '/application-passwords', { name }),
    remove: (uid, uuid)  => api.del(WP_USERS_NS + '/' + uid + '/application-passwords/' + uuid),
  },
};

/**
 * Registro de actividad de autenticación.
 */
export const AuthLogAPI = {
  activity:    ()     => api.get(NS + 'auth/activity'),
  clearLocks:  ()     => api.post(NS + 'auth/clear-locks'),
  getSettings: ()     => api.get(NS + 'auth/settings'),
  setSettings: (body) => api.put(NS + 'auth/settings', body),
};

/**
 * Portal de cliente: galerías privadas con token.
 * Endpoints admin (auth WP). Los públicos /cp/{token}/* los consume el
 * frontend standalone del plugin yzmf-client-portal.
 */
export const ClientPortalAPI = {
  list:    ()         => api.get(NS + 'cp/admin/galleries'),
  detail:  (id)       => api.get(NS + 'cp/admin/galleries/' + id),
  create:  (body)     => api.post(NS + 'cp/admin/galleries', body),
  update:  (id, body) => api.put(NS + 'cp/admin/galleries/' + id, body),
  remove:  (id)       => api.del(NS + 'cp/admin/galleries/' + id),
  actions: (id)       => api.get(NS + 'cp/admin/galleries/' + id + '/actions'),
};

/**
 * Sliders: CPT yzmf_slider con meta JSON (settings + slides).
 * Detalle del modelo en docs/SLIDER_DESIGN.md.
 */
export const SlidersAPI = {
  list:      (params)         => api.get(NS + 'sliders', params),
  detail:    (id)             => api.get(NS + 'sliders/' + id),
  create:    (body)           => api.post(NS + 'sliders', body),
  update:    (id, body)       => api.put(NS + 'sliders/' + id, body),
  remove:    (id, force=false)=> api.del(NS + 'sliders/' + id, { force: force ? 1 : 0 }),
  duplicate: (id)             => api.post(NS + 'sliders/' + id + '/duplicate'),
};
