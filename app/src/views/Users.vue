<script setup>
import { ref, onMounted, onActivated, computed } from 'vue';
import { useRouter } from 'vue-router';
import { UsersAPI } from '../api/endpoints';
import { useUiStore } from '../stores/ui';
import Spinner from '../components/Spinner.vue';

const router = useRouter();
const ui     = useUiStore();
const users   = ref([]);
const loading = ref(true);
const search  = ref('');
const showCreate = ref(false);
const creating  = ref(false);
const newUser   = ref({ username: '', email: '', password: '', name: '', roles: ['author'] });

async function load() {
  loading.value = true;
  try {
    users.value = await UsersAPI.list({ per_page: 100 });
  } catch (e) {
    ui.toast(e.message || 'Sin permisos para listar usuarios', 'err');
    users.value = [];
  } finally {
    loading.value = false;
  }
}

onMounted(load);
onActivated(load);

const filtered = computed(() => {
  if (!search.value.trim()) return users.value;
  const s = search.value.toLowerCase();
  return users.value.filter(u =>
    (u.username || '').toLowerCase().includes(s) ||
    (u.email || '').toLowerCase().includes(s) ||
    (u.name || '').toLowerCase().includes(s)
  );
});

async function createUser() {
  if (!newUser.value.username || !newUser.value.email || !newUser.value.password) {
    ui.toast('Faltan campos requeridos', 'err'); return;
  }
  creating.value = true;
  try {
    const created = await UsersAPI.create(newUser.value);
    ui.toast('✓ Usuario creado', 'ok');
    showCreate.value = false;
    newUser.value = { username: '', email: '', password: '', name: '', roles: ['author'] };
    await load();
    router.push({ name: 'user-detail', params: { id: created.id } });
  } catch (e) {
    ui.toast(e.message || 'Error al crear', 'err');
  } finally {
    creating.value = false;
  }
}

function avatar(u) {
  return u.avatar_urls?.['96'] || u.avatar_urls?.['48'] || '';
}

function rolesLabel(roles) {
  if (!roles || !roles.length) return '—';
  return roles.join(', ');
}
</script>

<template>
  <div>
    <div class="row" style="margin-bottom:12px">
      <input v-model="search" placeholder="Buscar usuario…" />
      <button class="btn pri" @click="showCreate = true">+ Nuevo usuario</button>
    </div>

    <div v-if="loading" class="center muted"><Spinner /> Cargando…</div>
    <div v-else-if="!filtered.length" class="empty muted">
      <p v-if="!users.length">No tienes permisos para listar usuarios o no hay ninguno.</p>
      <p v-else>Sin resultados.</p>
    </div>

    <div v-else class="users-grid">
      <button v-for="u in filtered" :key="u.id" class="ucard"
        @click="$router.push({ name: 'user-detail', params: { id: u.id } })">
        <img v-if="avatar(u)" :src="avatar(u)" class="uava" :alt="u.username" />
        <div v-else class="uava-fallback">{{ (u.username || '?').charAt(0).toUpperCase() }}</div>
        <div class="uinfo">
          <span class="uname">{{ u.name || u.username }}</span>
          <span class="umeta muted small">{{ u.email }}</span>
          <span class="umeta muted small">{{ rolesLabel(u.roles) }}</span>
        </div>
      </button>
    </div>

    <transition name="sheet">
      <div v-if="showCreate" class="sheet-overlay" @click.self="showCreate = false">
        <div class="sheet">
          <div class="sheet-handle" />
          <h3>Crear usuario</h3>
          <div class="field"><label>Usuario</label><input v-model="newUser.username" placeholder="usuario" /></div>
          <div class="field"><label>Email</label><input v-model="newUser.email" type="email" /></div>
          <div class="field"><label>Nombre completo</label><input v-model="newUser.name" /></div>
          <div class="field"><label>Contraseña</label><input v-model="newUser.password" type="password" /></div>
          <div class="field">
            <label>Rol</label>
            <select v-model="newUser.roles[0]">
              <option value="administrator">Administrador</option>
              <option value="editor">Editor</option>
              <option value="author">Autor</option>
              <option value="contributor">Colaborador</option>
              <option value="subscriber">Suscriptor</option>
            </select>
          </div>
          <div class="row" style="margin-top:14px">
            <button class="btn pri" :disabled="creating" @click="createUser" style="flex:1">
              <Spinner v-if="creating" :size="14" />
              <span v-else>Crear</span>
            </button>
            <button class="btn ghost" @click="showCreate = false">Cancelar</button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.center { display: flex; gap: 10px; justify-content: center; padding: 30px; }
.empty { text-align: center; padding: 40px 16px; }

.users-grid { display: flex; flex-direction: column; gap: 8px; }
@media (min-width: 768px) {
  .users-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 10px;
  }
}

.ucard {
  display: flex; gap: 12px;
  background: var(--s1);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 10px;
  text-align: left;
  align-items: center;
  cursor: pointer;
  transition: border-color .12s;
}
.ucard:hover { border-color: var(--accent); }

.uava, .uava-fallback {
  width: 48px; height: 48px; border-radius: 50%;
  flex-shrink: 0;
  background: var(--s2);
  display: flex; align-items: center; justify-content: center;
  font-size: 20px; font-weight: 700; color: var(--accent);
}
.uava { object-fit: cover; }

.uinfo { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.uname { font-size: 14px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.umeta { font-size: 11px; }

.sheet-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 1350; display: flex; align-items: flex-end; }
.sheet {
  width: 100%; max-width: 560px; margin: 0 auto;
  background: var(--s1);
  border-top-left-radius: 18px; border-top-right-radius: 18px;
  padding: 14px 16px calc(20px + env(safe-area-inset-bottom));
  max-height: 90vh; overflow-y: auto;
}
.sheet-handle { width: 40px; height: 4px; background: var(--border2); border-radius: 2px; margin: -4px auto 12px; }
.sheet h3 { margin: 0 0 14px; font-size: 14px; text-transform: uppercase; color: var(--text-mute); letter-spacing: .5px; }
.sheet-enter-active, .sheet-leave-active { transition: opacity .25s; }
.sheet-enter-active .sheet, .sheet-leave-active .sheet { transition: transform .25s; }
.sheet-enter-from, .sheet-leave-to { opacity: 0; }
.sheet-enter-from .sheet, .sheet-leave-to .sheet { transform: translateY(100%); }
</style>
