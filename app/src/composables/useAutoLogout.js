import { onMounted, onBeforeUnmount, watch } from 'vue';

/**
 * Auto-logout por inactividad. Lee la configuración de localStorage
 * (ypva.autoLogoutMin) y dispara onTimeout() cuando no hay actividad
 * de teclado/ratón/táctil durante ese tiempo.
 *
 * - 0 = desactivado
 * - Reset al detectar mousemove, keydown, touchstart, scroll, click
 * - Comprueba localStorage en cada reinicio para soportar cambios en caliente
 */
export function useAutoLogout(onTimeout) {
  let timer = null;
  let listening = false;
  const events = ['mousemove', 'keydown', 'touchstart', 'scroll', 'click'];

  function getMinutes() {
    const v = parseInt(localStorage.getItem('ypva.autoLogoutMin') || '0', 10);
    return Number.isFinite(v) && v > 0 ? v : 0;
  }

  function clearTimer() {
    if (timer) { clearTimeout(timer); timer = null; }
  }

  function reset() {
    clearTimer();
    const minutes = getMinutes();
    if (minutes <= 0) return;
    timer = setTimeout(() => {
      try { onTimeout?.(); } catch {}
    }, minutes * 60 * 1000);
  }

  function attach() {
    if (listening) return;
    events.forEach(e => window.addEventListener(e, reset, { passive: true }));
    listening = true;
    reset();
  }

  function detach() {
    if (!listening) return;
    events.forEach(e => window.removeEventListener(e, reset));
    listening = false;
    clearTimer();
  }

  // Reaccionar a cambios de localStorage (cuando usuario cambia el ajuste)
  function onStorage(e) {
    if (e.key === 'ypva.autoLogoutMin') reset();
  }

  onMounted(() => {
    attach();
    window.addEventListener('storage', onStorage);
  });
  onBeforeUnmount(() => {
    detach();
    window.removeEventListener('storage', onStorage);
  });

  return { reset, attach, detach };
}
