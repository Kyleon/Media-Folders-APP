import { describe, it, expect } from 'vitest';
import { applyOpLocal, escapeRe } from './bulk-rename';

describe('bulk-rename applyOpLocal', () => {
  describe('replace', () => {
    it('replace literal sin regex', () => {
      expect(applyOpLocal('foo bar foo', {}, 'replace', { find: 'foo', replace: 'baz' }, 0))
        .toBe('baz bar baz');
    });

    it('replace case-insensitive por defecto', () => {
      expect(applyOpLocal('Foo Bar foo', {}, 'replace', { find: 'foo', replace: 'X' }, 0))
        .toBe('X Bar X');
    });

    it('replace case_sensitive=true respeta el case', () => {
      expect(applyOpLocal('Foo Bar foo', {}, 'replace', { find: 'foo', replace: 'X', case_sensitive: true }, 0))
        .toBe('Foo Bar X');
    });

    it('replace con regex y grupo', () => {
      expect(applyOpLocal('IMG_1234.jpg', {}, 'replace', { find: '(\\d+)', replace: '#$1', regex: true }, 0))
        .toBe('IMG_#1234.jpg');
    });

    it('replace con regex inválida cae al old original', () => {
      expect(applyOpLocal('foo', {}, 'replace', { find: '(', replace: 'x', regex: true }, 0))
        .toBe('foo');
    });

    it('replace sin find devuelve old', () => {
      expect(applyOpLocal('foo', {}, 'replace', { find: '' }, 0)).toBe('foo');
    });
  });

  describe('prefix / suffix', () => {
    it('prefix', () => {
      expect(applyOpLocal('foo', {}, 'prefix', { value: 'Pre_' }, 0)).toBe('Pre_foo');
    });
    it('suffix', () => {
      expect(applyOpLocal('foo', {}, 'suffix', { value: '_post' }, 0)).toBe('foo_post');
    });
  });

  describe('sequence', () => {
    it('sequence con padding', () => {
      expect(applyOpLocal('x', {}, 'sequence', { pattern: 'foto-{n}', start: 1, padding: 3 }, 4))
        .toBe('foto-005');
    });
    it('sequence sin padding y start por defecto', () => {
      expect(applyOpLocal('x', {}, 'sequence', { pattern: '{n}' }, 0)).toBe('1');
    });
  });

  describe('from_filename', () => {
    it('extrae nombre sin extensión y separadores a espacios', () => {
      expect(applyOpLocal('old', { filename: 'mi_foto-de-vacaciones.jpg' }, 'from_filename', {
        strip_ext: true, separator_to_space: true,
      }, 0)).toBe('mi foto de vacaciones');
    });
    it('extrae del url cuando filename está vacío', () => {
      expect(applyOpLocal('old', { url: 'https://x/y/foo.jpg' }, 'from_filename', { strip_ext: true }, 0))
        .toBe('foo');
    });
  });

  describe('from_alt', () => {
    it('usa alt si existe', () => {
      expect(applyOpLocal('old', { alt: 'descripción accesible' }, 'from_alt', {}, 0))
        .toBe('descripción accesible');
    });
    it('si no hay alt deja el old', () => {
      expect(applyOpLocal('old', {}, 'from_alt', {}, 0)).toBe('old');
    });
  });

  describe('case', () => {
    it('lower / upper / title / sentence', () => {
      expect(applyOpLocal('Foo Bar', {}, 'case', { mode: 'lower' }, 0)).toBe('foo bar');
      expect(applyOpLocal('foo bar', {}, 'case', { mode: 'upper' }, 0)).toBe('FOO BAR');
      expect(applyOpLocal('foo bar', {}, 'case', { mode: 'title' }, 0)).toBe('Foo Bar');
      expect(applyOpLocal('FOO bar', {}, 'case', { mode: 'sentence' }, 0)).toBe('Foo bar');
    });
  });

  describe('trim', () => {
    it('colapsa espacios y elimina extremos', () => {
      expect(applyOpLocal('  foo   bar  ', {}, 'trim', {}, 0)).toBe('foo bar');
    });
  });

  describe('escapeRe', () => {
    it('escapa metacaracteres', () => {
      expect(escapeRe('a.b*c?')).toBe('a\\.b\\*c\\?');
    });
  });

  describe('robustez', () => {
    it('op desconocida devuelve old', () => {
      expect(applyOpLocal('foo', {}, 'nonexistent', {}, 0)).toBe('foo');
    });
    it('params null no rompe', () => {
      expect(applyOpLocal('foo', {}, 'prefix', null, 0)).toBe('foo');
    });
  });
});
