/**
 * Constantes compartidas. Antes había magic numbers dispersos por la PWA
 * (300/350ms en debounces, zoom 12 en cuatro sitios, 5MB max logo…).
 * Centralizarlos hace los cambios consistentes en un solo punto.
 */

// Debounce de inputs de búsqueda
export const DEBOUNCE_SEARCH_MS   = 300;
// Debounce del geocoder Nominatim (más generoso por la política de la API)
export const DEBOUNCE_GEOCODE_MS  = 350;

// Long-press para entrar en modo selección
export const LONG_PRESS_MS        = 450;

// Mapa
export const GEO_DEFAULT_CENTER   = [40.4168, -3.7038];  // Madrid
export const GEO_DEFAULT_ZOOM     = 12;
export const GEO_FIT_BOUNDS_MAX_ZOOM = 7;

// Subida
export const LOGO_MAX_BYTES       = 5 * 1024 * 1024;

// Listado de media
export const MEDIA_PER_PAGE       = 40;
