<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useUiStore } from '../stores/ui';
import { useBrandStore } from '../stores/brand';
import { AuthLogAPI, UsersAPI, MediaAPI } from '../api/endpoints';
import ThemeSwitch from '../components/ThemeSwitch.vue';
import MediaPicker from '../components/MediaPicker.vue';
import Spinner from '../components/Spinner.vue';

const router = useRouter();
const auth   = useAuthStore();
const ui     = useUiStore();
const brand  = useBrandStore();

// ── Marca ──────────────────────────────────────────────────────────
const brandName    = ref('');
const brandColor   = ref('');
const brandInitials = ref('');
const showLogoPicker = ref(false);
const savingBrand  = ref(false);
const brandDirty   = ref(false);
const logoFileInput = ref(null);
const uploadingLogo = ref(false);

function syncBrandLocal() {
  brandName.value     = brand.name || '';
  brandColor.value    = brand.primaryColor || '';
  brandInitials.value = brand.initials || '';
  brandDirty.value = false;
}

async function saveBrand() {
  savingBrand.value = true;
  try {
    await brand.save({
      name:           brandName.value,
      primary_color:  brandColor.value || '',
      initials:       brandInitials.value,
    });
    syncBrandLocal();
    ui.toast('✓ Marca actualizada', 'ok');
  } catch (e) { ui.toast(e.message || 'Error', 'err'); }
  finally { savingBrand.value = false; }
}

async function pickLogo(image) {
  try {
    await brand.save({ logo_id: image.id });
    ui.toast('✓ Logo actualizado', 'ok');
  } catch (e) { ui.toast(e.message || 'Error', 'err'); }
}

function openLogoFilePicker() {
  logoFileInput.value?.click();
}

async function onLogoFileSelected(e) {
  const file = e.target.files?.[0];
  // Resetear el input para que seleccionar el mismo archivo otra vez vuelva a disparar change
  if (e.target) e.target.value = '';
  if (!file) return;

  // Validación básica
  if (file.size > 5 * 1024 * 1024) {
    ui.toast('El logo no puede pesar más de 5 MB', 'err');
    return;
  }
  if (!/^image\//.test(file.type) && !/\.(svg|png|jpe?g|webp)$/i.test(file.name)) {
    ui.toast('Solo se aceptan imágenes (SVG, PNG, JPG, WebP)', 'err');
    return;
  }

  uploadingLogo.value = true;
  try {
    // Subir al media library (sin carpeta) y aplicar como logo
    const att = await MediaAPI.upload(file, null);
    if (!att?.id) throw new Error('La subida no devolvió un ID de attachment');
    await brand.save({ logo_id: att.id });
    ui.toast('✓ Logo subido y aplicado', 'ok');
  } catch (err) {
    ui.toast(err.message || 'Error al subir el logo', 'err');
  } finally {
    uploadingLogo.value = false;
  }
}

async function clearLogo() {
  if (!confirm('¿Quitar el logo actual?')) return;
  try {
    await brand.save({ logo_id: 0 });
    ui.toast('Logo eliminado', 'ok');
  } catch (e) { ui.toast(e.message || 'Error', 'err'); }
}

// ── Sesión actual + me ─────────────────────────────────────────────
const me = ref(null);
const loadingMe = ref(false);

async function loadMe() {
  loadingMe.value = true;
  try {
    me.value = await UsersAPI.me();
  } catch (e) {
    // Si falla (401), probablemente el server no tiene activo el handler de
    // Basic Auth para contraseñas regulares. La sesión sigue siendo válida
    // para los endpoints de yz-media-folders. Mostramos un aviso suave.
    me.value = null;
  } finally { loadingMe.value = false; }
}

// ── Seguridad: actividad + lockouts + settings ─────────────────────
const activity = ref({ log: [], locks: [] });
const loadingActivity = ref(false);
const authSettings = ref({ max_attempts: 5, window_minutes: 15, lockout_minutes: 30 });
const savingAuthSettings = ref(false);

async function loadActivity() {
  loadingActivity.value = true;
  try {
    activity.value = await AuthLogAPI.activity();
    authSettings.value = await AuthLogAPI.getSettings();
  } catch (e) { /* opcional */ }
  finally { loadingActivity.value = false; }
}

async function saveAuthSettings() {
  savingAuthSettings.value = true;
  try {
    await AuthLogAPI.setSettings(authSettings.value);
    ui.toast('✓ Política de seguridad actualizada', 'ok');
  } catch (e) { ui.toast(e.message || 'Error', 'err'); }
  finally { savingAuthSettings.value = false; }
}

async function clearLocks() {
  if (!confirm('¿Liberar todas las IPs bloqueadas?')) return;
  try {
    await AuthLogAPI.clearLocks();
    activity.value.locks = [];
    ui.toast('✓ Bloqueos liberados', 'ok');
  } catch (e) { ui.toast(e.message || 'Error', 'err'); }
}

// ── Auto-logout ────────────────────────────────────────────────────
const autoLogoutMin = ref(parseInt(localStorage.getItem('ypva.autoLogoutMin') || '0', 10));

function saveAutoLogout() {
  localStorage.setItem('ypva.autoLogoutMin', String(autoLogoutMin.value));
  ui.toast('✓ Auto-logout actualizado', 'ok');
}

// ── Acciones generales ─────────────────────────────────────────────
function logout() {
  auth.logout();
  router.replace({ name: 'login' });
}

function clearCaches() {
  if ('caches' in window) caches.keys().then(keys => keys.forEach(k => caches.delete(k)));
  ui.toast('🧹 Cachés limpiadas', 'ok');
}

const recentLogins = computed(() => activity.value.log.filter(e => e.type === 'login').slice(0, 10));
const recentFailed = computed(() => activity.value.log.filter(e => e.type === 'failed').slice(0, 10));

function fmtDate(iso) {
  if (!iso) return '';
  return new Date(iso).toLocaleString('es-ES', { dateStyle: 'short', timeStyle: 'short' });
}

function shortUA(ua) {
  if (!ua) return '';
  if (/iPhone|iPad/.test(ua)) return '📱 iOS';
  if (/Android/.test(ua))    return '📱 Android';
  if (/Edg\//.test(ua))      return '🖥 Edge';
  if (/Chrome/.test(ua))     return '🖥 Chrome';
  if (/Firefox/.test(ua))    return '🖥 Firefox';
  if (/Safari/.test(ua))     return '🖥 Safari';
  return '🖥 Otro';
}

onMounted(async () => {
  if (!brand.loaded) await brand.load();
  syncBrandLocal();
  loadMe();
  loadActivity();
});
</script>

<template>
  <div class="set-grid">

    <!-- 1. Identidad / Marca ─────────────────────────────────────── -->
    <div class="card">
      <h3 class="section">Marca</h3>

      <input ref="logoFileInput" type="file"
        accept="image/svg+xml,image/png,image/jpeg,image/webp,image/*"
        hidden
        @change="onLogoFileSelected" />

      <div class="brand-preview">
        <div class="logo-slot" @click="openLogoFilePicker" title="Subir logo desde tu dispositivo">
          <Spinner v-if="uploadingLogo" :size="20" />
          <template v-else>
            <img v-if="brand.logoUrl" :src="brand.logoUrl" :alt="brand.name" />
            <span v-else class="logo-initials">{{ brand.initials }}</span>
          </template>
        </div>
        <div class="brand-text">
          <strong>{{ brand.name || 'YPVA' }}</strong>
          <span class="muted small">El logo aparece en la cabecera de la app</span>
        </div>
      </div>

      <div class="row" style="margin-top:10px; flex-wrap: wrap; gap: 6px">
        <button class="btn pri sm" :disabled="uploadingLogo" @click="openLogoFilePicker">
          <Spinner v-if="uploadingLogo" :size="14" />
          <span v-else>{{ brand.logoUrl ? '↑ Subir nuevo' : '↑ Subir logo' }}</span>
        </button>
        <button class="btn sm ghost" :disabled="uploadingLogo" @click="showLogoPicker = true" title="Reusar imagen ya subida">
          📁 Galería
        </button>
        <button v-if="brand.logoUrl" class="btn sm ghost danger" :disabled="uploadingLogo" @click="clearLogo">Quitar</button>
      </div>
      <p class="muted small" style="margin-top:8px">
        SVG, PNG, JPG o WebP. Hasta 5 MB. Recomendado cuadrado o con fondo transparente.
      </p>

      <div class="field" style="margin-top:14px">
        <label>Nombre de la marca</label>
        <input v-model="brandName" @input="brandDirty = true" placeholder="Ej: Yezrael Pérez" maxlength="60" />
      </div>
      <div class="row">
        <div class="field" style="flex:1">
          <label>Iniciales (sin logo)</label>
          <input v-model="brandInitials" @input="brandDirty = true" placeholder="Ej: YP" maxlength="3" style="text-transform:uppercase" />
        </div>
        <div class="field" style="flex:1">
          <label>Color principal</label>
          <div class="color-row">
            <input v-model="brandColor" @input="brandDirty = true" placeholder="#C8A97E" maxlength="7" style="font-family:monospace" />
            <input type="color" v-model="brandColor" @input="brandDirty = true" class="color-pick" />
          </div>
        </div>
      </div>
      <button class="btn pri" :disabled="savingBrand || !brandDirty" @click="saveBrand" style="width:100%;margin-top:10px">
        <Spinner v-if="savingBrand" :size="14" />
        <span v-else>{{ brandDirty ? 'Guardar marca' : 'Sin cambios' }}</span>
      </button>
    </div>

    <!-- 2. Apariencia ────────────────────────────────────────────── -->
    <div class="card">
      <h3 class="section">Apariencia</h3>
      <div class="kv-row">
        <span class="muted">Tema</span>
        <ThemeSwitch />
      </div>
    </div>

    <!-- 3. Sesión actual ────────────────────────────────────────── -->
    <div class="card">
      <h3 class="section">Sesión</h3>
      <div class="kv-row"><span class="muted">Sitio</span><span class="val">{{ auth.creds?.baseUrl }}</span></div>
      <div class="kv-row"><span class="muted">Usuario</span><span class="val">{{ auth.creds?.username }}</span></div>
      <div class="kv-row">
        <span class="muted">Tipo de sesión</span>
        <span class="val">
          <span v-if="auth.creds?.authMode === 'app'" class="auth-badge app">🔐 Application Password</span>
          <span v-else class="auth-badge pwd">🔑 Contraseña de usuario</span>
        </span>
      </div>
      <div class="kv-row" v-if="me">
        <span class="muted">Roles</span>
        <span class="val">{{ (me.roles || []).join(', ') || '—' }}</span>
      </div>
      <p v-if="auth.creds?.authMode !== 'app'" class="muted small auth-tip">
        💡 Si usas el panel a menudo, crea una <button type="button" class="link" @click="$router.push({ name: 'user-detail', params: { id: me?.id || 'me' } })">Application Password</button> y cambia a ese modo en el próximo login. Así no perderás la sesión cuando cambies tu contraseña principal.
      </p>
      <div class="field" style="margin-top:12px">
        <label>Auto-cerrar sesión por inactividad</label>
        <select v-model.number="autoLogoutMin" @change="saveAutoLogout">
          <option :value="0">Nunca</option>
          <option :value="5">5 minutos</option>
          <option :value="15">15 minutos</option>
          <option :value="30">30 minutos</option>
          <option :value="60">1 hora</option>
          <option :value="240">4 horas</option>
          <option :value="720">12 horas</option>
        </select>
      </div>
      <button class="btn danger" @click="logout" style="width:100%;margin-top:12px">Cerrar sesión</button>
    </div>

    <!-- 4. Seguridad: actividad ──────────────────────────────────── -->
    <div class="card">
      <h3 class="section">Seguridad — actividad</h3>
      <div v-if="loadingActivity" class="center muted small"><Spinner :size="12" /> Cargando…</div>
      <template v-else>
        <div class="auth-stats">
          <div class="stat">
            <span class="num">{{ recentLogins.length }}</span>
            <span class="lbl">Logins recientes</span>
          </div>
          <div class="stat">
            <span class="num">{{ recentFailed.length }}</span>
            <span class="lbl">Intentos fallidos</span>
          </div>
          <div class="stat">
            <span class="num" :class="{ alert: activity.locks.length }">{{ activity.locks.length }}</span>
            <span class="lbl">IPs bloqueadas</span>
          </div>
        </div>

        <div v-if="activity.locks.length" class="locks-card">
          <p class="muted small" style="margin-bottom:6px">IPs actualmente bloqueadas:</p>
          <div v-for="l in activity.locks" :key="l.ip" class="lock-row">
            <span class="ip">{{ l.ip }}</span>
            <span class="muted small">hasta {{ fmtDate(l.unlock_iso) }}</span>
          </div>
          <button class="btn sm ghost" style="margin-top:8px" @click="clearLocks">Liberar bloqueos</button>
        </div>

        <details class="activity-log" style="margin-top:10px">
          <summary>Ver historial completo ({{ activity.log.length }} eventos)</summary>
          <div class="log-table">
            <div v-for="e in activity.log" :key="e.ts" class="log-row" :class="'t-' + e.type">
              <span class="log-type">
                <template v-if="e.type === 'login'">✓</template>
                <template v-else-if="e.type === 'failed'">✕</template>
                <template v-else>🔒</template>
              </span>
              <span class="log-when">{{ fmtDate(e.ts_iso) }}</span>
              <span class="log-who">{{ e.user || '—' }}</span>
              <span class="log-ip">{{ e.ip }}</span>
              <span class="log-ua">{{ shortUA(e.ua) }}</span>
            </div>
          </div>
        </details>
      </template>
    </div>

    <!-- 5. Política de bloqueo ───────────────────────────────────── -->
    <div class="card">
      <h3 class="section">Política de bloqueo de logins</h3>
      <p class="muted small" style="margin-bottom:10px">
        Cuando una IP supera el umbral de intentos fallidos en una ventana de
        tiempo, se bloquea durante el tiempo de penalización.
      </p>
      <div class="row">
        <div class="field" style="flex:1">
          <label>Intentos máximos</label>
          <input type="number" min="0" v-model.number="authSettings.max_attempts" />
        </div>
        <div class="field" style="flex:1">
          <label>Ventana (min)</label>
          <input type="number" min="1" v-model.number="authSettings.window_minutes" />
        </div>
        <div class="field" style="flex:1">
          <label>Penalización (min)</label>
          <input type="number" min="1" v-model.number="authSettings.lockout_minutes" />
        </div>
      </div>
      <button class="btn pri" :disabled="savingAuthSettings" @click="saveAuthSettings" style="width:100%;margin-top:10px">
        <Spinner v-if="savingAuthSettings" :size="14" />
        <span v-else>Guardar política</span>
      </button>
      <p class="muted small" style="margin-top:6px">0 intentos = desactivar lockout.</p>
    </div>

    <!-- 6. Usuarios ──────────────────────────────────────────────── -->
    <div class="card">
      <h3 class="section">Usuarios</h3>
      <button class="btn" @click="$router.push({ name: 'users' })" style="width:100%;margin-bottom:8px">
        👤 Gestionar usuarios y contraseñas de aplicación
      </button>
      <p class="muted small">
        Aunque eres el usuario principal, aquí puedes crear acceso para asistentes
        o revocar Application Passwords antiguas.
      </p>
    </div>

    <!-- 7. Gestión ──────────────────────────────────────────────── -->
    <div class="card">
      <h3 class="section">Gestión</h3>
      <button class="btn" @click="$router.push({ name: 'folders' })" style="width:100%;margin-bottom:8px">📁 Carpetas de medios</button>
      <button class="btn" @click="$router.push({ name: 'portfolio-categories' })" style="width:100%;margin-bottom:8px">📂 Categorías de portfolio</button>
      <button class="btn" @click="$router.push({ name: 'client-galleries' })" style="width:100%;margin-bottom:8px">🔐 Galerías de cliente</button>
      <button class="btn" @click="$router.push({ name: 'exif' })" style="width:100%">📷 Estadísticas EXIF</button>
    </div>

    <!-- 8. Mantenimiento ────────────────────────────────────────── -->
    <div class="card">
      <h3 class="section">Mantenimiento</h3>
      <button class="btn" @click="clearCaches" style="width:100%">🧹 Limpiar cachés</button>
      <p class="muted small" style="margin-top:8px">
        Limpia los datos cacheados por el Service Worker. Útil si la app no muestra los últimos cambios.
      </p>
    </div>

    <!-- 9. Acerca de ────────────────────────────────────────────── -->
    <div class="card">
      <h3 class="section">Acerca de</h3>
      <p class="muted small">
        {{ brand.name || 'YPVA' }} Admin · v0.2.0<br>
        Panel para fotógrafos · plugin <code>yz-media-folders</code>
      </p>
    </div>
  </div>

  <MediaPicker v-model="showLogoPicker"
    :multiple="false"
    title="Elegir logo"
    @pick="pickLogo" />
</template>

<style scoped>
.set-grid { display: flex; flex-direction: column; gap: 14px; }
@media (min-width: 768px) {
  .set-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
    gap: 16px;
    align-items: start;
  }
}
@media (min-width: 1600px) { .set-grid { grid-template-columns: repeat(auto-fill, minmax(420px, 1fr)); } }

.section { margin: 0 0 12px; font-size: 12px; text-transform: uppercase; letter-spacing: .5px; color: var(--text-mute); font-weight: 600; }
.kv-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; font-size: 13px; border-bottom: 1px solid var(--border); }
.kv-row:last-child { border-bottom: 0; }
.val { font-size: 12px; max-width: 60%; text-align: right; word-break: break-all; }
.small { font-size: 11px; }
.center { display: flex; gap: 8px; align-items: center; padding: 12px; justify-content: center; }
.btn.sm { min-height: 32px; padding: 4px 10px; font-size: 12px; }

/* Marca */
.brand-preview { display: flex; align-items: center; gap: 14px; }
.logo-slot {
  width: 64px; height: 64px;
  background: var(--s2);
  border: 2px dashed var(--border2);
  border-radius: 12px;
  overflow: hidden;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  transition: border-color .12s, transform .12s;
}
.logo-slot:hover { border-color: var(--accent); transform: scale(1.04); }
.logo-slot img { width: 100%; height: 100%; object-fit: contain; padding: 6px; }
.logo-initials { font-size: 22px; font-weight: 700; color: var(--accent); }
.brand-text { display: flex; flex-direction: column; gap: 2px; }
.brand-text strong { font-size: 14px; }

.color-row { display: flex; gap: 6px; align-items: center; }
.color-pick { width: 36px; height: 36px; padding: 0; border-radius: 6px; cursor: pointer; flex-shrink: 0; border: 1px solid var(--border); }

.auth-badge {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 10px;
  background: var(--s2);
}
.auth-badge.app { background: var(--accent-lo); color: var(--accent); }
.auth-tip { margin-top: 6px; font-size: 11px; line-height: 1.5; padding: 8px 10px; background: var(--s2); border-radius: 6px; }
.link {
  background: transparent;
  border: 0;
  padding: 0;
  color: var(--accent);
  cursor: pointer;
  font: inherit;
  text-decoration: underline;
}
.link:hover { opacity: .8; }

/* Auth stats */
.auth-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 12px; }
.stat { background: var(--s2); padding: 10px; border-radius: var(--radius); text-align: center; }
.stat .num { display: block; font-size: 20px; font-weight: 700; color: var(--accent); line-height: 1; }
.stat .num.alert { color: var(--danger); }
.stat .lbl { display: block; font-size: 10px; color: var(--text-mute); text-transform: uppercase; letter-spacing: .5px; margin-top: 4px; }

.locks-card { background: rgba(208, 98, 94, 0.08); border-left: 3px solid var(--danger); padding: 10px; border-radius: 4px; }
.lock-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 12px; }
.lock-row .ip { font-family: monospace; }

.activity-log summary { cursor: pointer; font-size: 12px; color: var(--text-mute); user-select: none; }
.activity-log summary:hover { color: var(--accent); }
.log-table { display: flex; flex-direction: column; gap: 4px; margin-top: 10px; max-height: 320px; overflow-y: auto; }
.log-row {
  display: grid;
  grid-template-columns: 24px 1fr 1fr auto auto;
  gap: 8px;
  padding: 6px 8px;
  font-size: 11px;
  border-radius: 4px;
  background: var(--s2);
  align-items: center;
}
.log-row.t-failed  { background: rgba(208, 98, 94, .12); }
.log-row.t-lockout { background: rgba(208, 98, 94, .25); }
.log-type { font-size: 12px; }
.log-row.t-login   .log-type { color: var(--ok); }
.log-row.t-failed  .log-type { color: var(--danger); }
.log-row.t-lockout .log-type { color: var(--danger); }
.log-when { color: var(--text-mute); white-space: nowrap; }
.log-who { font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.log-ip { font-family: monospace; color: var(--text-mute); }
.log-ua { color: var(--text-mute); }
</style>
