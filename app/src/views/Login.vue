<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useUiStore } from '../stores/ui';
import { useBrandStore } from '../stores/brand';
import Spinner from '../components/Spinner.vue';

const router = useRouter();
const route  = useRoute();
const auth   = useAuthStore();
const ui     = useUiStore();
const brand  = useBrandStore();

const baseUrl   = ref(import.meta.env.VITE_DEFAULT_BASE_URL || 'https://yezraelperez.es');
const username  = ref('');
const password  = ref('');
const showPwd   = ref(false);
const submitting = ref(false);
const error     = ref('');
const mode      = ref(localStorage.getItem('ypva.loginMode') || 'password'); // 'password' | 'app'

onMounted(() => {
  ui.applyTheme();
  // Cargar marca (logo + iniciales) si la pantalla de login se abre standalone
  brand.hydrateFromCache();
  brand.load();
});

const isAppMode = computed(() => mode.value === 'app');

function setMode(m) {
  mode.value = m;
  localStorage.setItem('ypva.loginMode', m);
  password.value = '';
  error.value = '';
}

async function login() {
  error.value = '';
  if (!baseUrl.value || !username.value || !password.value) {
    error.value = 'Rellena todos los campos.';
    return;
  }
  submitting.value = true;
  try {
    // En modo Application Password, quitamos los espacios que WordPress
    // muestra al generar la clave (xxxx xxxx xxxx xxxx).
    const pw = isAppMode.value ? password.value.replace(/\s+/g, '') : password.value;
    const url = baseUrl.value.replace(/\/+$/, '') + '/wp-json/yzmf/v1/folders';
    const headers = { Authorization: 'Basic ' + btoa(username.value + ':' + pw) };
    const res = await fetch(url, { headers, credentials: 'omit' });
    if (!res.ok) {
      let msg;
      if (res.status === 401)      msg = 'Usuario o contraseña incorrectos';
      else if (res.status === 403) msg = 'Acceso denegado por el servidor (permisos o lockout activo)';
      else if (res.status === 404) msg = 'No se encontró la API. Comprueba la URL del sitio.';
      else                         msg = 'No se pudo conectar (' + res.status + ')';
      throw new Error(msg);
    }
    auth.login({
      baseUrl: baseUrl.value,
      username: username.value,
      appPassword: pw,
      authMode: mode.value,
    });
    ui.toast('✓ Sesión iniciada', 'ok');
    const redirect = route.query.redirect || '/';
    router.replace(redirect);
  } catch (e) {
    // Sin red puede fallar antes con TypeError
    error.value = e.message || 'Error de conexión';
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <div class="login-wrap safe-top safe-bottom">
    <div class="login-card card">
      <div class="login-head">
        <div class="login-logo">
          <img v-if="brand.logoUrl" :src="brand.logoUrl" :alt="brand.name" />
          <span v-else>{{ brand.initials || 'Y' }}</span>
        </div>
        <h1>{{ brand.name || 'Yezrael Pérez' }} · Admin</h1>
        <p class="muted">Panel de medios y portfolios</p>
      </div>

      <!-- Tabs de modo de login -->
      <div class="login-tabs">
        <button class="lt-tab" :class="{ on: !isAppMode }" @click="setMode('password')" type="button">
          🔑 Usuario + contraseña
        </button>
        <button class="lt-tab" :class="{ on: isAppMode }" @click="setMode('app')" type="button">
          🔐 Application Password
        </button>
      </div>

      <form @submit.prevent="login" autocomplete="on">
        <div class="field">
          <label for="baseUrl">URL del sitio</label>
          <input id="baseUrl" v-model="baseUrl" type="url" autocomplete="off" required />
        </div>
        <div class="field">
          <label for="username">Usuario</label>
          <input id="username" v-model="username" autocomplete="username" required />
        </div>
        <div class="field">
          <label for="pwd">{{ isAppMode ? 'Application Password' : 'Contraseña' }}</label>
          <div class="pwd-row">
            <input id="pwd" v-model="password"
              :type="showPwd ? 'text' : 'password'"
              :autocomplete="isAppMode ? 'off' : 'current-password'"
              :placeholder="isAppMode ? 'xxxx xxxx xxxx xxxx xxxx xxxx' : 'Tu contraseña de WordPress'"
              required />
            <button type="button" class="pwd-toggle" @click="showPwd = !showPwd"
              :title="showPwd ? 'Ocultar' : 'Mostrar'">
              {{ showPwd ? '🙈' : '👁' }}
            </button>
          </div>
          <p v-if="!isAppMode" class="hint muted">
            La contraseña habitual de WordPress de tu usuario.
          </p>
          <p v-else class="hint muted">
            Crea una en <code>Ajustes → Usuarios → tu usuario → Application Passwords</code>.
            Recomendado para sesiones largas — no caduca al cambiar tu contraseña principal.
          </p>
        </div>

        <button class="btn pri" :disabled="submitting" type="submit" style="width:100%">
          <Spinner v-if="submitting" :size="16" />
          <span v-else>Entrar</span>
        </button>
      </form>

      <p v-if="error" role="alert" aria-live="assertive" class="danger" style="margin-top:12px;font-size:13px">{{ error }}</p>

      <p class="muted small switch-hint">
        <template v-if="!isAppMode">
          ¿Tu sesión se cierra demasiado pronto? Usa una
          <button type="button" class="link" @click="setMode('app')">Application Password</button>.
        </template>
        <template v-else>
          ¿Prefieres un login simple?
          <button type="button" class="link" @click="setMode('password')">Usar usuario y contraseña</button>.
        </template>
      </p>
    </div>
  </div>
</template>

<style scoped>
.login-wrap {
  min-height: 100vh; min-height: 100dvh;
  display: flex; align-items: center; justify-content: center;
  padding: 16px;
}
.login-card { width: 100%; max-width: 420px; }
.login-head { text-align: center; margin-bottom: 22px; }
.login-logo {
  width: 56px; height: 56px;
  margin: 0 auto 12px;
  border-radius: 14px;
  background: var(--accent);
  color: #0f0f0f;
  display: flex; align-items: center; justify-content: center;
  font-size: 28px; font-weight: 700;
  overflow: hidden;
}
.login-logo img { width: 100%; height: 100%; object-fit: contain; padding: 6px; }

h1 { font-size: 19px; margin: 0 0 4px; }
.small { font-size: 11px; }

/* Tabs */
.login-tabs {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 4px;
  background: var(--s2);
  border-radius: var(--radius);
  padding: 3px;
  margin-bottom: 16px;
}
.lt-tab {
  padding: 8px 6px;
  border-radius: calc(var(--radius) - 2px);
  font-size: 12px;
  color: var(--text-mute);
  font-weight: 500;
  background: transparent;
  border: 0;
  cursor: pointer;
  transition: background .12s, color .12s;
}
.lt-tab:hover { color: var(--text); }
.lt-tab.on   { background: var(--s1); color: var(--text); box-shadow: 0 1px 2px rgba(0,0,0,.2); }

/* Password con toggle */
.pwd-row { position: relative; }
.pwd-row input { padding-right: 40px; width: 100%; }
.pwd-toggle {
  position: absolute;
  right: 4px; top: 50%;
  transform: translateY(-50%);
  width: 32px; height: 32px;
  background: transparent;
  border: 0;
  font-size: 14px;
  cursor: pointer;
  color: var(--text-mute);
  border-radius: 6px;
}
.pwd-toggle:hover { color: var(--accent); background: var(--s2); }

.hint { font-size: 11px; margin-top: 4px; line-height: 1.4; }
code { background: var(--s2); padding: 1px 5px; border-radius: 3px; font-size: 11px; }

.switch-hint {
  margin-top: 16px;
  text-align: center;
  font-size: 11px;
  line-height: 1.5;
}
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
</style>
