<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useUiStore } from '../stores/ui';
import Spinner from '../components/Spinner.vue';

const router = useRouter();
const route  = useRoute();
const auth   = useAuthStore();
const ui     = useUiStore();

const baseUrl     = ref(import.meta.env.VITE_DEFAULT_BASE_URL || 'https://yezraelperez.es');
const username    = ref('');
const appPassword = ref('');
const submitting  = ref(false);
const error       = ref('');

onMounted(() => ui.applyTheme());

async function login() {
  error.value = '';
  if (!baseUrl.value || !username.value || !appPassword.value) {
    error.value = 'Rellena todos los campos.';
    return;
  }
  submitting.value = true;
  try {
    // Probar credenciales con un GET ligero
    const url = baseUrl.value.replace(/\/+$/, '') + '/wp-json/yzmf/v1/folders';
    const pw  = appPassword.value.replace(/\s+/g, '');
    const headers = { Authorization: 'Basic ' + btoa(username.value + ':' + pw) };
    const res = await fetch(url, { headers, credentials: 'omit' });
    if (!res.ok) {
      const body = await res.text();
      throw new Error(res.status === 401 ? 'Credenciales inválidas' : 'No se pudo conectar (' + res.status + ')');
    }
    auth.login({ baseUrl: baseUrl.value, username: username.value, appPassword: pw });
    ui.toast('✓ Sesión iniciada', 'ok');
    const redirect = route.query.redirect || '/';
    router.replace(redirect);
  } catch (e) {
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
        <div class="login-logo">Y</div>
        <h1>Yezrael Pérez · Admin</h1>
        <p class="muted">Panel móvil de medios y portfolios</p>
      </div>

      <div class="field">
        <label for="baseUrl">URL del sitio</label>
        <input id="baseUrl" v-model="baseUrl" type="url" autocomplete="off" />
      </div>
      <div class="field">
        <label for="username">Usuario</label>
        <input id="username" v-model="username" autocomplete="username" />
      </div>
      <div class="field">
        <label for="appPw">Application Password</label>
        <input id="appPw" v-model="appPassword" type="password" autocomplete="current-password" placeholder="xxxx xxxx xxxx xxxx xxxx xxxx" />
        <p class="hint muted">
          Crea una en <code>Usuarios → Tu perfil → Application Passwords</code>.
        </p>
      </div>

      <button class="btn pri" :disabled="submitting" @click="login" style="width:100%">
        <Spinner v-if="submitting" :size="16" />
        <span v-else>Entrar</span>
      </button>

      <p v-if="error" class="danger" style="margin-top:12px;font-size:13px">{{ error }}</p>
    </div>
  </div>
</template>

<style scoped>
.login-wrap {
  min-height: 100vh; min-height: 100dvh;
  display: flex; align-items: center; justify-content: center;
  padding: 16px;
}
.login-card { width: 100%; max-width: 380px; }
.login-head { text-align: center; margin-bottom: 22px; }
.login-logo {
  width: 56px; height: 56px;
  margin: 0 auto 12px;
  border-radius: 14px;
  background: var(--accent);
  color: #0f0f0f;
  display: flex; align-items: center; justify-content: center;
  font-size: 28px; font-weight: 700;
}
h1 { font-size: 19px; margin: 0 0 4px; }
.hint { font-size: 11px; margin-top: 4px; }
code { background: var(--s2); padding: 1px 5px; border-radius: 3px; font-size: 11px; }
</style>
