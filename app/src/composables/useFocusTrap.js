import { onBeforeUnmount, watch, nextTick } from 'vue';

/**
 * Focus trap mínimo para bottomsheets y modales:
 *  - Al abrirse mueve el foco al primer elemento focuseable del contenedor.
 *  - Al pulsar Escape llama a onClose.
 *  - Tab / Shift+Tab quedan atrapados dentro del contenedor.
 *  - Al cerrarse restaura el foco al elemento previo.
 *
 * Uso:
 *   const sheetEl = ref(null);
 *   useFocusTrap(sheetEl, () => modelOpen.value, close);
 */
const FOCUSABLE_SEL = [
  'a[href]',
  'button:not([disabled])',
  'input:not([disabled])',
  'select:not([disabled])',
  'textarea:not([disabled])',
  '[tabindex]:not([tabindex="-1"])',
].join(',');

export function useFocusTrap(containerRef, isOpenGetter, onClose) {
  let previouslyFocused = null;

  function getFocusable() {
    if (!containerRef.value) return [];
    return Array.from(containerRef.value.querySelectorAll(FOCUSABLE_SEL))
      .filter(el => !el.hasAttribute('disabled') && el.offsetParent !== null);
  }

  function onKeydown(e) {
    if (!isOpenGetter()) return;
    if (e.key === 'Escape') {
      e.preventDefault();
      onClose?.();
      return;
    }
    if (e.key !== 'Tab') return;
    const focusables = getFocusable();
    if (!focusables.length) return;
    const first = focusables[0];
    const last  = focusables[focusables.length - 1];
    if (e.shiftKey && document.activeElement === first) {
      e.preventDefault();
      last.focus();
    } else if (!e.shiftKey && document.activeElement === last) {
      e.preventDefault();
      first.focus();
    }
  }

  watch(isOpenGetter, async (open) => {
    if (open) {
      previouslyFocused = document.activeElement;
      await nextTick();
      const focusables = getFocusable();
      (focusables[0] || containerRef.value)?.focus();
      document.addEventListener('keydown', onKeydown);
    } else {
      document.removeEventListener('keydown', onKeydown);
      try { previouslyFocused?.focus(); } catch {}
      previouslyFocused = null;
    }
  }, { immediate: true });

  onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKeydown);
  });
}
