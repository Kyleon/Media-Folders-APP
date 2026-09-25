import { defineStore } from 'pinia';
import { basicAuth } from '../utils/basicAuth';

const STORAGE_KEY = 'ypva.auth';

function loadFromStorage() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    const creds = raw ? JSON.parse(raw) : null;
    // Migración: sesiones antiguas en modo 'password' guardaban la contraseña
    // REAL de WordPress. Las descartamos para obligar a un login nuevo, que
    // ya guarda una Application Password generada (y revocable).
    if (creds && creds.authMode === 'password' && !creds.generated) {
      localStorage.removeItem(STORAGE_KEY);
      return null;
    }
    return creds;
  } catch { return null; }
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    creds: loadFromStorage(), // { baseUrl, username, appPassword, authMode, generated?, displayName? }
  }),
  getters: {
    isAuthed: (s) => !!s.creds,
  },
  actions: {
    login(creds) {
      // Normalizar baseUrl: garantizar trailing slash y sin doble
      const baseUrl = (creds.baseUrl || '').replace(/\/+$/, '') + '/';
      const next = { ...creds, baseUrl, generated: creds.authMode === 'password' };
      this.creds = next;
      localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
    },
    /**
     * Cierra sesión. Si la clave la generó la PWA (login con contraseña), la
     * revoca en el servidor para que no quede una Application Password viva.
     * Las claves pegadas a mano (modo 'app') no se tocan: son del usuario.
     */
    logout({ revoke = true } = {}) {
      const creds = this.creds;
      this.creds = null;
      localStorage.removeItem(STORAGE_KEY);
      if (revoke && creds && creds.generated && creds.appPassword) {
        fetch(creds.baseUrl + 'wp-json/yzmf/v1/auth/token', {
          method: 'DELETE',
          headers: { Authorization: basicAuth(creds.username, creds.appPassword.replace(/\s+/g, '')) },
          credentials: 'omit',
          keepalive: true,
        }).catch(() => {});
      }
    },
  },
});
