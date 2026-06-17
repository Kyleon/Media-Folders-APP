/**
 * Catálogo central de claves usadas en localStorage por la PWA.
 * Antes estaban dispersas: ypva.auth en stores/auth.js, ypva.theme en
 * stores/ui.js, ypva.media.view en views/Media.vue, etc. Riesgo de
 * typos silenciosos — esta lista única evita colisiones y facilita
 * sweep-deletes en logout.
 */
export const STORAGE_KEYS = {
  AUTH:            'ypva.auth',
  THEME:           'ypva.theme',
  MEDIA_VIEW:      'ypva.media.view',
  LOGIN_MODE:      'ypva.loginMode',
  AUTO_LOGOUT_MIN: 'ypva.autoLogoutMin',
};

/** Lee y parsea JSON. Devuelve fallback si falla. */
export function readJSON(key, fallback = null) {
  try {
    const raw = localStorage.getItem(key);
    return raw ? JSON.parse(raw) : fallback;
  } catch { return fallback; }
}

/** Lee como número (parseFloat) con fallback. */
export function readNum(key, fallback = 0) {
  const v = parseFloat(localStorage.getItem(key));
  return Number.isFinite(v) ? v : fallback;
}

/** Escribe JSON (silencioso ante errores de cuota). */
export function writeJSON(key, value) {
  try { localStorage.setItem(key, JSON.stringify(value)); }
  catch { /* quota, modo privado, etc — ignoramos */ }
}
