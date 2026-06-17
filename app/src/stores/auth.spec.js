import { describe, it, expect, beforeEach } from 'vitest';
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
});
