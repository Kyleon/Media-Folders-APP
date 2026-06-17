/**
 * Operaciones puras de renombrado en lote. Se aplican localmente para el
 * preview instantáneo; el servidor las re-aplica como fuente de verdad.
 *
 * Operaciones soportadas:
 *  - replace { find, replace, regex, case_sensitive }
 *  - prefix { value }
 *  - suffix { value }
 *  - sequence { pattern, start, padding }   pattern usa {n}
 *  - from_filename { strip_ext, separator_to_space }
 *  - from_alt
 *  - case { mode: lower|upper|title|sentence }
 *  - trim
 */

export function applyOpLocal(old, item, op, p, idx) {
  p = p || {};
  switch (op) {
    case 'replace': {
      if (!p.find) return old;
      if (p.regex) {
        try {
          const flags = (p.case_sensitive ? '' : 'i') + 'gu';
          return old.replace(new RegExp(p.find, flags), p.replace || '');
        } catch { return old; }
      }
      if (p.case_sensitive) return old.split(p.find).join(p.replace || '');
      return old.replace(new RegExp(escapeRe(p.find), 'gi'), p.replace || '');
    }
    case 'prefix': return (p.value || '') + old;
    case 'suffix': return old + (p.value || '');
    case 'sequence': {
      const n = idx + (parseInt(p.start, 10) || 1);
      const padded = p.padding > 0 ? String(n).padStart(p.padding, '0') : String(n);
      return (p.pattern || '{n}').replace(/\{n\}/g, padded);
    }
    case 'from_filename': {
      let name = item.filename || '';
      if (!name && item.url) name = item.url.split('/').pop() || '';
      if (p.strip_ext) {
        const dot = name.lastIndexOf('.');
        if (dot > 0) name = name.slice(0, dot);
      }
      if (p.separator_to_space) {
        name = name.replace(/[_-]+/g, ' ').replace(/\s+/g, ' ').trim();
      }
      return name;
    }
    case 'from_alt': return item.alt || old;
    case 'case': {
      switch (p.mode) {
        case 'lower': return old.toLowerCase();
        case 'upper': return old.toUpperCase();
        case 'sentence': {
          const s = old.toLowerCase();
          return s.charAt(0).toUpperCase() + s.slice(1);
        }
        case 'title':
        default:
          return old.toLowerCase().replace(/(?:^|\s)\S/g, c => c.toUpperCase());
      }
    }
    case 'trim': return old.replace(/\s+/g, ' ').trim();
    default: return old;
  }
}

export function escapeRe(s) {
  return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}
