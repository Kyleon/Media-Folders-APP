import { onMounted, onBeforeUnmount } from 'vue';

/**
 * Composable para registrar atajos globales.
 * Ignora automáticamente cuando el foco está en input/textarea/contenteditable.
 *
 * Uso:
 *   useKeyboardShortcuts({
 *     'm':           () => router.push({ name: 'media' }),
 *     'p':           () => router.push({ name: 'portfolios' }),
 *     'mod+k':       openSearchPalette,    // Cmd o Ctrl
 *     'escape':      closeAll,
 *   });
 *
 * Modificadores soportados: mod (cmd/ctrl), shift, alt
 */
export function useKeyboardShortcuts(map) {
  const handler = (e) => {
    // Ignorar si estamos escribiendo en un campo (excepto Esc y atajos con mod)
    const tag = (e.target?.tagName || '').toLowerCase();
    const isEditing = tag === 'input' || tag === 'textarea' || tag === 'select' || e.target?.isContentEditable;
    const hasMod    = e.ctrlKey || e.metaKey;
    if (isEditing && !hasMod && e.key !== 'Escape') return;

    const parts = [];
    if (hasMod)     parts.push('mod');
    if (e.shiftKey) parts.push('shift');
    if (e.altKey)   parts.push('alt');
    parts.push(e.key.toLowerCase());
    const combo = parts.join('+');

    const fn = map[combo];
    if (fn) {
      e.preventDefault();
      fn(e);
    }
  };

  onMounted(()       => window.addEventListener('keydown', handler));
  onBeforeUnmount(() => window.removeEventListener('keydown', handler));
}
