/**
 * Cliente HTTP minimal para la API REST de WordPress (yzmf/v1).
 *
 * - Lee credenciales desde el store de auth (Application Password / contraseña).
 * - 401 lanza error al caller para que decida (NO hace logout automático
 *   global porque distintos endpoints pueden fallar por permisos sin que la
 *   sesión esté realmente inválida).
 * - Tras varios 401 SEGUIDOS contra el mismo dominio en poco tiempo, se
 *   considera que la sesión sí está inválida y se cierra para evitar bucles.
 * - Maneja JSON y multipart (subida de archivos).
 */

import { useAuthStore } from '../stores/auth';
import { useUiStore } from '../stores/ui';
import router from '../router';

function buildAuthHeader(creds) {
  if (!creds || !creds.username || !creds.appPassword) return null;
  // App passwords vienen con espacios — los toleramos
  const pw = creds.appPassword.replace(/\s+/g, '');
  return 'Basic ' + btoa(creds.username + ':' + pw);
}

// Ventana corta de 401 consecutivos para detectar sesión realmente inválida.
const AUTH_FAIL_WINDOW_MS  = 8000;
const AUTH_FAIL_THRESHOLD  = 4;
let authFailures = [];

function recordAuthFailure() {
  const now = Date.now();
  authFailures = authFailures.filter(t => now - t < AUTH_FAIL_WINDOW_MS);
  authFailures.push(now);
  if (authFailures.length >= AUTH_FAIL_THRESHOLD) {
    authFailures = [];
    return true;
  }
  return false;
}

async function request(method, path, { params, body, isMultipart = false, signal } = {}) {
  const auth = useAuthStore();
  if (!auth.creds) throw new Error('No autenticado');

  const url = new URL(path.replace(/^\//, ''), auth.creds.baseUrl);
  if (params) {
    Object.entries(params).forEach(([k, v]) => {
      if (v === null || v === undefined || v === '') return;
      if (Array.isArray(v)) v.forEach(item => url.searchParams.append(k + '[]', item));
      else url.searchParams.set(k, v);
    });
  }

  const headers = { Authorization: buildAuthHeader(auth.creds) };
  let payload;
  if (isMultipart) {
    payload = body; // FormData
  } else if (body !== undefined) {
    headers['Content-Type'] = 'application/json';
    payload = JSON.stringify(body);
  }

  const res = await fetch(url.toString(), {
    method,
    headers,
    body: payload,
    signal,
    credentials: 'omit',
  });

  let data = null;
  const text = await res.text();
  if (text) {
    try { data = JSON.parse(text); } catch { data = text; }
  }

  if (!res.ok) {
    const msg = (data && data.message) || (typeof data === 'string' ? data : 'Error ' + res.status);
    const err = new Error(msg);
    err.status = res.status;
    err.code = data && data.code;
    err.data = data;

    // Detección de sesión realmente inválida: muchos 401 consecutivos en
    // pocos segundos contra distintos endpoints. En ese caso sí logout.
    if (res.status === 401 && recordAuthFailure()) {
      try {
        useUiStore().toast('🔒 Sesión inválida — inicia de nuevo', 'err');
        auth.logout();
        router.replace({ name: 'login' });
      } catch {}
    }

    throw err;
  }

  // Reset del contador en cualquier respuesta OK
  if (authFailures.length) authFailures = [];

  return data;
}

export const api = {
  get:    (path, params, opts)        => request('GET',    path, { ...opts, params }),
  post:   (path, body, params, opts)  => request('POST',   path, { ...opts, body, params }),
  put:    (path, body, params, opts)  => request('PUT',    path, { ...opts, body, params }),
  del:    (path, params, opts)        => request('DELETE', path, { ...opts, params }),
  upload: (path, formData, params, opts) =>
    request('POST', path, { ...opts, body: formData, isMultipart: true, params }),
};
