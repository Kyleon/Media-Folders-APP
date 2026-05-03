/**
 * Cliente HTTP minimal para la API REST de WordPress (yzmf/v1).
 *
 * - Lee credenciales desde el store de auth (Application Password).
 * - Convierte 401 en logout automático.
 * - Maneja JSON y multipart (subida de archivos).
 */

import { useAuthStore } from '../stores/auth';

function buildAuthHeader(creds) {
  if (!creds || !creds.username || !creds.appPassword) return null;
  // App passwords vienen con espacios — los toleramos
  const pw = creds.appPassword.replace(/\s+/g, '');
  return 'Basic ' + btoa(creds.username + ':' + pw);
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

  if (res.status === 401) {
    auth.logout();
    throw new Error('Sesión inválida — vuelve a iniciar sesión');
  }

  let data = null;
  const text = await res.text();
  if (text) {
    try { data = JSON.parse(text); } catch { data = text; }
  }

  if (!res.ok) {
    const msg = (data && data.message) || (typeof data === 'string' ? data : 'Error ' + res.status);
    const err = new Error(msg);
    err.status = res.status;
    err.data = data;
    throw err;
  }
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
