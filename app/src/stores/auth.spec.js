import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useAuthStore } from './auth';

describe('auth store', () => {
  beforeEach(() => {
    localStorage.clear();
    setActivePinia(createPinia());
  });

  it('arranca sin credenciales', () => {
    const a = useAuthStore();
    expect(a.isAuthed).toBe(false);
    expect(a.creds).toBeNull();
  });

  it('login normaliza baseUrl quitando trailing slashes duplicados', () => {
    const a = useAuthStore();
    a.login({ baseUrl: 'https://example.com////', username: 'u', appPassword: 'p' });
    expect(a.creds.baseUrl).toBe('https://example.com/');
    expect(a.isAuthed).toBe(true);
  });

  it('login persiste en localStorage', () => {
    const a = useAuthStore();
    a.login({ baseUrl: 'https://example.com', username: 'u', appPassword: 'p' });
    const stored = JSON.parse(localStorage.getItem('ypva.auth'));
    expect(stored.username).toBe('u');
    expect(stored.baseUrl).toBe('https://example.com/');
  });

  it('logout limpia state y localStorage', () => {
    const a = useAuthStore();
    a.login({ baseUrl: 'https://example.com', username: 'u', appPassword: 'p' });
    a.logout();
    expect(a.isAuthed).toBe(false);
    expect(localStorage.getItem('ypva.auth')).toBeNull();
  });

  it('hidrata desde localStorage al crearse', () => {
    localStorage.setItem('ypva.auth', JSON.stringify({
      baseUrl: 'https://example.com/',
      username: 'u',
      appPassword: 'p',
    }));
    const a = useAuthStore();
    expect(a.isAuthed).toBe(true);
    expect(a.creds.username).toBe('u');
  });

  it('descarta sesiones antiguas que guardaban la contraseña real', () => {
    localStorage.setItem('ypva.auth', JSON.stringify({
      baseUrl: 'https://example.com/', username: 'u', appPassword: 'real', authMode: 'password',
    }));
    const a = useAuthStore();
    expect(a.isAuthed).toBe(false);
    expect(localStorage.getItem('ypva.auth')).toBeNull();
  });

  it('logout revoca la clave generada por la PWA', () => {
    const fetchMock = vi.fn(() => Promise.resolve({ ok: true }));
    vi.stubGlobal('fetch', fetchMock);
    const a = useAuthStore();
    a.login({ baseUrl: 'https://example.com', username: 'u', appPassword: 'gen', authMode: 'password' });
    expect(a.creds.generated).toBe(true);
    a.logout();
    expect(fetchMock).toHaveBeenCalledTimes(1);
    const [url, opts] = fetchMock.mock.calls[0];
    expect(url).toBe('https://example.com/wp-json/yzmf/v1/auth/token');
    expect(opts.method).toBe('DELETE');
    vi.unstubAllGlobals();
  });

  it('logout NO revoca claves pegadas a mano (modo app) ni con revoke:false', () => {
    const fetchMock = vi.fn(() => Promise.resolve({ ok: true }));
    vi.stubGlobal('fetch', fetchMock);
    const a = useAuthStore();
    a.login({ baseUrl: 'https://example.com', username: 'u', appPassword: 'manual', authMode: 'app' });
    a.logout();
    a.login({ baseUrl: 'https://example.com', username: 'u', appPassword: 'gen', authMode: 'password' });
    a.logout({ revoke: false });
    expect(fetchMock).not.toHaveBeenCalled();
    vi.unstubAllGlobals();
  });
});
