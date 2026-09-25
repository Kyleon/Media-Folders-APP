import { describe, it, expect } from 'vitest';
import { basicAuth } from './basicAuth';

describe('basicAuth', () => {
  it('codifica ASCII igual que btoa', () => {
    expect(basicAuth('user', 'pass')).toBe('Basic ' + btoa('user:pass'));
  });

  it('soporta caracteres no Latin-1 (UTF-8) sin lanzar', () => {
    const h = basicAuth('yezrael', 'contraseña€');
    const decoded = new TextDecoder().decode(Uint8Array.from(atob(h.slice(6)), c => c.charCodeAt(0)));
    expect(decoded).toBe('yezrael:contraseña€');
  });
});
