<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { UsersAPI } from '../api/endpoints';
import { useUiStore } from '../stores/ui';
import Spinner from '../components/Spinner.vue';

const props = defineProps({ id: { type: [String, Number], required: true } });
const router = useRouter();
const ui     = useUiStore();

const user    = ref(null);
const loading = ref(true);
const saving  = ref(false);
const dirty   = ref(false);
const newPassword = ref('');

const appPasswords = ref([]);
const loadingApps  = ref(false);
const showCreatePwd = ref(false);
const newPwdName    = ref('');
const creatingPwd   = ref(false);
const justCreated   = ref(null);  // { name, password, uuid }

async function load() {
  loading.value = true;
  try {
    user.value = await UsersAPI.detail(props.id);
    dirty.value = false;
    await loadAppPasswords();
  } catch (e) {
    ui.toast(e.message || 'Error al cargar', 'err');
    router.back();
  } finally {
    loading.value = false;
  }
}

async function loadAppPasswords() {
  loadingApps.value = true;
  try {
    appPasswords.value = await UsersAPI.appPasswords.list(props.id);
  } catch (e) {
    appPasswords.value = [];
  } finally {
    loadingApps.value = false;
  }
}

onMounted(load);

function markDirty() { dirty.value = true; }

async function save() {
  saving.value = true;
  try {
    const body = {
      first_name: user.value.first_name || '',
      last_name:  user.value.last_name || '',
      name:       user.value.name || '',
      email:      user.value.email || '',
      url:        user.value.url || '',
      description: user.value.description || '',
      roles:      user.value.roles || [],
    };
    if (newPassword.value) body.password = newPassword.value;
    user.value = await UsersAPI.update(props.id, body);
    newPassword.value = '';
    dirty.value = false;
    ui.toast('✓ Usuario actualizado', 'ok');
  } catch (e) {
    ui.toast(e.message || 'Error', 'err');
  } finally { saving.value = false; }
}

async function remove() {
  if (!confirm(`¿Eliminar el usuario "${user.value.username}"? Esta acción no se puede deshacer.`)) return;
  try {
    await UsersAPI.remove(props.id);
    ui.toast('🗑 Usuario eliminado', 'ok');
    router.replace({ name: 'users' });
  } catch (e) { ui.toast(e.message || 'Error', 'err'); }
}

async function createAppPassword() {
  if (!newPwdName.value.trim()) { ui.toast('Ponle un nombre identificativo', 'err'); return; }
  creatingPwd.value = true;
  try {
    const r = await UsersAPI.appPasswords.create(props.id, newPwdName.value.trim());
    justCreated.value = r;
    newPwdName.value = '';
    showCreatePwd.value = false;
    await loadAppPasswords();
  } catch (e) {
    ui.toast(e.message || 'Error', 'err');
  } finally {
    creatingPwd.value = false;
  }
}

async function revokeAppPassword(uuid, name) {
  if (!confirm(`¿Revocar "${name}"? Cualquier app o dispositivo que use esta contraseña dejará de funcionar.`)) return;
  try {
    await UsersAPI.appPasswords.remove(props.id, uuid);
    ui.toast('🔒 Contraseña revocada', 'ok');
    await loadAppPasswords();
  } catch (e) { ui.toast(e.message || 'Error', 'err'); }
}

async function copyJustCreated() {
  if (!justCreated.value?.password) return;
  try {
    await navigator.clipboard.writeText(justCreated.value.password);
    ui.toast('📋 Contraseña copiada', 'ok');
  } catch { ui.toast('No se pudo copiar', 'err'); }
}

function fmt(v) {
  if (!v) return '—';
  return new Date(v).toLocaleString('es-ES', { dateStyle: 'short', timeStyle: 'short' });
}

const isMe = computed(() => user.value?.id === user.value?.me_id);
</script>

<template>
  <div v-if="loading" class="center muted"><Spinner /> Cargando…</div>

  <div v-else-if="user" class="ud-layout">
    <!-- Header con avatar -->
    <div class="card head-card">
      <img v-if="user.avatar_urls?.['96']" :src="user.avatar_urls['96']" class="head-ava" :alt="user.username" />
      <div v-else class="head-ava-fallback">{{ (user.username || '?').charAt(0).toUpperCase() }}</div>
      <div class="head-info">
        <h2>{{ user.name || user.username }}</h2>
        <span class="muted small">@{{ user.username }}</span>
        <span class="muted small">{{ user.email }}</span>
      </div>
    </div>

    <!-- Datos -->
    <div class="card">
      <h3 class="section">Datos</h3>
      <div class="row">
        <div class="field" style="flex:1">
          <label>Nombre</label>
          <input v-model="user.first_name" @input="markDirty" />
        </div>
        <div class="field" style="flex:1">
          <label>Apellidos</label>
          <input v-model="user.last_name" @input="markDirty" />
        </div>
      </div>
      <div class="field">
        <label>Nombre completo (a mostrar)</label>
        <input v-model="user.name" @input="markDirty" />
      </div>
      <div class="field">
        <label>Email</label>
        <input v-model="user.email" @input="markDirty" type="email" />
      </div>
      <div class="field">
        <label>Web</label>
        <input v-model="user.url" @input="markDirty" type="url" placeholder="https://" />
      </div>
      <div class="field">
        <label>Biografía</label>
        <textarea v-model="user.description" @input="markDirty" rows="2"></textarea>
      </div>
      <div class="field">
        <label>Rol</label>
        <select v-model="user.roles[0]" @change="markDirty">
          <option value="administrator">Administrador</option>
          <option value="editor">Editor</option>
          <option value="author">Autor</option>
          <option value="contributor">Colaborador</option>
          <option value="subscriber">Suscriptor</option>
        </select>
      </div>
      <div class="field">
        <label>Nueva contraseña <span class="muted small">(opcional)</span></label>
        <input v-model="newPassword" @input="markDirty" type="password" placeholder="Dejar vacío para mantener la actual" />
      </div>
      <button class="btn pri" :disabled="saving || !dirty" @click="save" style="width:100%;margin-top:10px">
        <Spinner v-if="saving" :size="14" />
        <span v-else>{{ dirty ? 'Guardar cambios' : 'Sin cambios' }}</span>
      </button>
    </div>

    <!-- Application Passwords -->
    <div class="card">
      <div class="ap-head">
        <h3 class="section" style="margin:0">Application Passwords</h3>
        <button class="btn sm pri" @click="showCreatePwd = true">+ Nueva</button>
      </div>
      <p class="muted small" style="margin:6px 0 12px">
        Tokens largos para acceder a la API o a esta app desde dispositivos. Si pierdes uno o lo dejaste de usar, revócalo aquí.
      </p>

      <div v-if="justCreated" class="just-created">
        <span class="muted small">⚠ Guarda esta contraseña ahora. No volverá a mostrarse.</span>
        <div class="just-created-row">
          <code>{{ justCreated.password }}</code>
          <button class="btn sm" @click="copyJustCreated">📋 Copiar</button>
        </div>
        <button class="btn sm ghost" style="margin-top:6px" @click="justCreated = null">He guardado la contraseña</button>
      </div>

      <div v-if="loadingApps" class="center muted small"><Spinner :size="12" /> Cargando…</div>
      <div v-else-if="!appPasswords.length" class="muted small" style="padding:8px 0">Aún no hay contraseñas de aplicación creadas.</div>
      <div v-else class="ap-list">
        <div v-for="p in appPasswords" :key="p.uuid" class="ap-row">
          <div class="ap-info">
            <strong>{{ p.name }}</strong>
            <span class="muted small">
              Creada {{ fmt(p.created) }} ·
              Último uso: {{ p.last_used ? fmt(p.last_used) : 'nunca' }}
              <span v-if="p.last_ip"> · {{ p.last_ip }}</span>
            </span>
          </div>
          <button class="btn sm danger" @click="revokeAppPassword(p.uuid, p.name)">Revocar</button>
        </div>
      </div>
    </div>

    <!-- Eliminar -->
    <div class="card danger-zone">
      <button class="btn danger" @click="remove" style="width:100%">🗑 Eliminar usuario</button>
    </div>

    <transition name="sheet">
      <div v-if="showCreatePwd" class="sheet-overlay" @click.self="showCreatePwd = false">
        <div class="sheet">
          <div class="sheet-handle" />
          <h3>Nueva Application Password</h3>
          <p class="muted small" style="margin-bottom:10px">
            Ponle un nombre que identifique para qué la usas (ej: "PWA iPhone", "Lightroom", "Plug-in Capture One").
          </p>
          <div class="field">
            <label>Nombre identificativo</label>
            <input v-model="newPwdName" placeholder="Ej: iPhone PWA" maxlength="60" autofocus />
          </div>
          <div class="row" style="margin-top:14px">
            <button class="btn pri" :disabled="creatingPwd" @click="createAppPassword" style="flex:1">
              <Spinner v-if="creatingPwd" :size="14" />
              <span v-else>Crear contraseña</span>
            </button>
            <button class="btn ghost" @click="showCreatePwd = false">Cancelar</button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.center { display: flex; gap: 10px; justify-content: center; padding: 30px; }

.ud-layout { display: flex; flex-direction: column; gap: 14px; }
@media (min-width: 1024px) {
  .ud-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(360px, 480px);
    gap: 20px;
    align-items: start;
  }
  .head-card { grid-column: 1 / -1; }
  .danger-zone { grid-column: 1 / -1; }
}

.section { margin: 0 0 12px; font-size: 12px; text-transform: uppercase; letter-spacing: .5px; color: var(--text-mute); font-weight: 600; }
.btn.sm { min-height: 32px; padding: 4px 10px; font-size: 12px; }

/* Header */
.head-card { display: flex; align-items: center; gap: 16px; }
.head-ava, .head-ava-fallback {
  width: 64px; height: 64px;
  border-radius: 50%;
  flex-shrink: 0;
  background: var(--s2);
  display: flex; align-items: center; justify-content: center;
  font-size: 28px; font-weight: 700; color: var(--accent);
}
.head-ava { object-fit: cover; }
.head-info { display: flex; flex-direction: column; gap: 4px; min-width: 0; }
.head-info h2 { margin: 0; font-size: 18px; }
.small { font-size: 11px; }

/* Application Passwords */
.ap-head { display: flex; justify-content: space-between; align-items: center; }

.just-created {
  background: rgba(200, 169, 126, 0.12);
  border-left: 3px solid var(--accent);
  padding: 12px;
  border-radius: 4px;
  margin-bottom: 12px;
}
.just-created-row { display: flex; gap: 8px; align-items: center; margin-top: 8px; }
.just-created code {
  flex: 1;
  background: var(--bg);
  padding: 8px 10px;
  border-radius: 4px;
  font-family: monospace;
  font-size: 12px;
  color: var(--accent);
  word-break: break-all;
}

.ap-list { display: flex; flex-direction: column; gap: 8px; }
.ap-row {
  display: flex; gap: 10px; align-items: center;
  padding: 10px;
  background: var(--s2);
  border-radius: var(--radius);
}
.ap-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 2px; }
.ap-info strong { font-size: 13px; }

/* Sheet */
.sheet-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 1350; display: flex; align-items: flex-end; }
.sheet {
  width: 100%; max-width: 520px; margin: 0 auto;
  background: var(--s1);
  border-top-left-radius: 18px; border-top-right-radius: 18px;
  padding: 14px 16px calc(20px + env(safe-area-inset-bottom));
}
.sheet-handle { width: 40px; height: 4px; background: var(--border2); border-radius: 2px; margin: -4px auto 12px; }
.sheet h3 { margin: 0 0 8px; font-size: 14px; text-transform: uppercase; color: var(--text-mute); letter-spacing: .5px; }
.sheet-enter-active, .sheet-leave-active { transition: opacity .25s; }
.sheet-enter-active .sheet, .sheet-leave-active .sheet { transition: transform .25s; }
.sheet-enter-from, .sheet-leave-to { opacity: 0; }
.sheet-enter-from .sheet, .sheet-leave-to .sheet { transform: translateY(100%); }
</style>
