<template>
  <ion-page>
    <ion-content :fullscreen="true" class="admin-content">
      <div class="admin-layout">

        <!-- Barra superior -->
        <header class="admin-topbar">
          <div>
            <p class="topbar-label">BovWeight CR</p>
            <h2>Panel administrativo</h2>
          </div>

          <div class="topbar-actions">
            <button class="secondary-btn" @click="irBitacora">
              Bitácora
            </button>

            <button class="logout-btn" @click="cerrarSesion">
              <span>Salir</span>
            </button>
          </div>
        </header>

        <!-- Hero -->
        <section class="admin-hero">
          <div>
            <p class="eyebrow">Gestión de usuarios</p>
            <h1>Usuarios registrados</h1>
            <p class="hero-text">
              Consulta las cuentas del sistema, revisa sus roles y activa o desactiva usuarios.
            </p>
          </div>

          <button
            class="refresh-btn"
            @click="cargarUsuarios"
            :disabled="cargando"
          >
            {{ cargando ? 'Actualizando...' : 'Actualizar' }}
          </button>
        </section>

        <!-- Mensaje de acción -->
        <div v-if="mensaje" class="success-box">
          {{ mensaje }}
        </div>

        <!-- Resumen -->
        <section class="stats-grid">
          <article class="stat-card">
            <span class="stat-icon">👥</span>
            <div>
              <p>Total usuarios</p>
              <strong>{{ usuarios.length }}</strong>
            </div>
          </article>

          <article class="stat-card">
            <span class="stat-icon">✅</span>
            <div>
              <p>Activos</p>
              <strong>{{ totalActivos }}</strong>
            </div>
          </article>

          <article class="stat-card">
            <span class="stat-icon">⛔</span>
            <div>
              <p>Inactivos</p>
              <strong>{{ totalInactivos }}</strong>
            </div>
          </article>

          <article class="stat-card">
            <span class="stat-icon">🛡️</span>
            <div>
              <p>Administradores</p>
              <strong>{{ totalAdmins }}</strong>
            </div>
          </article>
        </section>

        <!-- Buscador -->
        <div class="search-panel">
          <span class="search-icon">🔎</span>

          <input
            v-model="busqueda"
            class="search-input"
            type="text"
            placeholder="Buscar por nombre, correo o rol"
          />
        </div>

        <!-- Tabla -->
        <section class="users-panel">
          <div class="panel-header">
            <div>
              <h3>Listado de usuarios</h3>
              <p>Total mostrado: {{ usuariosFiltrados.length }}</p>
            </div>
          </div>

          <div v-if="cargando" class="state-box">
            <ion-spinner name="crescent" />
            <p>Cargando usuarios...</p>
          </div>

          <div v-else-if="error" class="error-box">
            {{ error }}
          </div>

          <div v-else-if="usuariosFiltrados.length === 0" class="state-box">
            <p>No se encontraron usuarios.</p>
          </div>

          <div v-else class="table-wrap">
            <table class="users-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Nombre</th>
                  <th>Correo</th>
                  <th>Rol</th>
                  <th>Estado</th>
                  <th>Registro</th>
                  <th>Acción</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="usuario in usuariosFiltrados"
                  :key="usuario.id"
                >
                  <td class="id-cell">#{{ usuario.id }}</td>

                  <td>
                    <div class="user-cell">
                      <div class="avatar">
                        {{ obtenerIniciales(usuario.name) }}
                      </div>

                      <span>{{ usuario.name }}</span>
                    </div>
                  </td>

                  <td>{{ usuario.email }}</td>

                  <td>
                    <span
                      class="role-pill"
                      :class="String(usuario.rol || '').toLowerCase()"
                    >
                      {{ usuario.rol || 'Sin rol' }}
                    </span>
                  </td>

                  <td>
                    <span
                      class="status-pill"
                      :class="estaActivo(usuario) ? 'active' : 'inactive'"
                    >
                      {{ estaActivo(usuario) ? 'Activo' : 'Inactivo' }}
                    </span>
                  </td>

                  <td>{{ formatearFecha(usuario.created_at) }}</td>

                  <td>
                    <button
                      class="toggle-status-btn"
                      :class="estaActivo(usuario) ? 'deactivate' : 'activate'"
                      :disabled="procesandoId === usuario.id || esUsuarioActual(usuario)"
                      @click="confirmarCambioEstado(usuario)"
                    >
                      <span v-if="procesandoId === usuario.id">
                        Procesando...
                      </span>

                      <span v-else-if="esUsuarioActual(usuario)">
                        Cuenta actual
                      </span>

                      <span v-else>
                        {{ estaActivo(usuario) ? 'Desactivar' : 'Activar' }}
                      </span>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import {
  IonContent,
  IonPage,
  IonSpinner
} from '@ionic/vue';

import {
  getAdminUsuarios,
  cambiarEstadoUsuarioAdmin,
  type UsuarioDto,
  formatFecha
} from '@/services/api';

const router = useRouter();

const usuarios = ref<UsuarioDto[]>([]);
const busqueda = ref('');
const cargando = ref(false);
const error = ref('');
const mensaje = ref('');
const procesandoId = ref<number | null>(null);

const obtenerUsuarioActual = (): UsuarioDto | null => {
  const userRaw = localStorage.getItem('user');

  if (!userRaw) {
    return null;
  }

  try {
    return JSON.parse(userRaw) as UsuarioDto;
  } catch {
    return null;
  }
};

const usuarioActual = computed(() => obtenerUsuarioActual());

const irBitacora = () => {
  router.push('/admin/bitacoras');
};

const cerrarSesion = () => {
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  localStorage.removeItem('perfil');
  localStorage.removeItem('animalSel');
  localStorage.removeItem('fincaSel');
  localStorage.removeItem('pesajeSel');
  sessionStorage.clear();

  router.replace('/login');
};

const cargarUsuarios = async () => {
  cargando.value = true;
  error.value = '';
  mensaje.value = '';

  try {
    const response = await getAdminUsuarios();
    usuarios.value = response.datos;
  } catch (err: any) {
    error.value =
      err?.message ||
      'No se pudieron cargar los usuarios.';
  } finally {
    cargando.value = false;
  }
};

const estaActivo = (usuario: UsuarioDto): boolean => {
  return usuario.activo === true || usuario.activo === 1;
};

const esUsuarioActual = (usuario: UsuarioDto): boolean => {
  return usuarioActual.value?.id === usuario.id;
};

const confirmarCambioEstado = async (usuario: UsuarioDto) => {
  if (esUsuarioActual(usuario)) {
    error.value = 'No puede activar o desactivar su propia cuenta.';
    return;
  }

  const accion = estaActivo(usuario)
    ? 'desactivar'
    : 'activar';

  const confirmado = window.confirm(
    `¿Desea ${accion} la cuenta de ${usuario.name}?`
  );

  if (!confirmado) {
    return;
  }

  await cambiarEstado(usuario);
};

const cambiarEstado = async (usuario: UsuarioDto) => {
  procesandoId.value = usuario.id;
  error.value = '';
  mensaje.value = '';

  try {
    const response = await cambiarEstadoUsuarioAdmin(usuario.id);
    const usuarioActualizado = response.datos;

    usuarios.value = usuarios.value.map((item) => {
      if (item.id === usuarioActualizado.id) {
        return usuarioActualizado;
      }

      return item;
    });

    mensaje.value =
      response.message ||
      response.mensaje ||
      'Estado del usuario actualizado correctamente.';
  } catch (err: any) {
    error.value =
      err?.message ||
      'No se pudo cambiar el estado del usuario.';
  } finally {
    procesandoId.value = null;
  }
};

const usuariosFiltrados = computed(() => {
  const texto = busqueda.value
    .trim()
    .toLowerCase();

  if (!texto) {
    return usuarios.value;
  }

  return usuarios.value.filter((usuario) => {
    return (
      usuario.name?.toLowerCase().includes(texto) ||
      usuario.email?.toLowerCase().includes(texto) ||
      String(usuario.rol || '').toLowerCase().includes(texto)
    );
  });
});

const totalActivos = computed(() => {
  return usuarios.value.filter((usuario) => estaActivo(usuario)).length;
});

const totalInactivos = computed(() => {
  return usuarios.value.filter((usuario) => !estaActivo(usuario)).length;
});

const totalAdmins = computed(() => {
  return usuarios.value.filter((usuario) => {
    return String(usuario.rol || '').toLowerCase() === 'admin';
  }).length;
});

const formatearFecha = (
  fecha: string | undefined
): string => {
  return formatFecha(fecha);
};

const obtenerIniciales = (nombre: string | undefined): string => {
  if (!nombre) {
    return 'U';
  }

  const partes = nombre
    .trim()
    .split(' ')
    .filter(Boolean);

  if (partes.length === 1) {
    return partes[0].charAt(0).toUpperCase();
  }

  return `${partes[0].charAt(0)}${partes[1].charAt(0)}`.toUpperCase();
};

onMounted(() => {
  cargarUsuarios();
});
</script>

<style scoped>
.admin-content {
  --background: #edf4ef;
}

.admin-layout {
  width: min(1180px, calc(100% - 32px));
  margin: 0 auto;
  padding: 28px 0 48px;
}

/* Topbar */
.admin-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 22px;
  padding: 18px 22px;
  border-radius: 22px;
  background: rgba(255, 255, 255, 0.86);
  box-shadow: 0 14px 35px rgba(27, 72, 43, 0.12);
  backdrop-filter: blur(10px);
}

.topbar-label {
  margin: 0 0 4px;
  color: #2d7a4a;
  font-size: 0.76rem;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.13em;
}

.admin-topbar h2 {
  margin: 0;
  color: #123f25;
  font-size: 1.25rem;
  font-weight: 900;
}

.topbar-actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.logout-btn,
.secondary-btn {
  border: none;
  border-radius: 14px;
  padding: 11px 18px;
  font-weight: 900;
  cursor: pointer;
  transition: transform 0.18s, opacity 0.18s;
  font-family: inherit;
}

.logout-btn {
  color: #ffffff;
  background: linear-gradient(135deg, #9b1c1c, #d43b3b);
  box-shadow: 0 10px 22px rgba(155, 28, 28, 0.22);
}

.secondary-btn {
  color: #1e5631;
  background: #e3f3e7;
}

.logout-btn:hover,
.secondary-btn:hover {
  transform: translateY(-1px);
}

.logout-btn:active,
.secondary-btn:active {
  transform: translateY(0);
  opacity: 0.86;
}

/* Hero */
.admin-hero {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 22px;
  margin-bottom: 18px;
  padding: 32px;
  border-radius: 28px;
  color: white;
  background:
    radial-gradient(circle at top right, rgba(255, 255, 255, 0.22), transparent 34%),
    linear-gradient(135deg, #0d2b1a 0%, #1e5631 55%, #3a9e61 100%);
  box-shadow: 0 22px 48px rgba(18, 63, 37, 0.22);
}

.eyebrow {
  margin: 0 0 7px;
  font-size: 0.78rem;
  font-weight: 900;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  opacity: 0.72;
}

.admin-hero h1 {
  margin: 0;
  font-size: clamp(2rem, 4vw, 3.1rem);
  font-weight: 950;
  letter-spacing: -0.04em;
}

.hero-text {
  max-width: 650px;
  margin: 8px 0 0;
  color: rgba(255, 255, 255, 0.83);
  line-height: 1.5;
}

.refresh-btn {
  min-width: 142px;
  border: 1.5px solid rgba(255, 255, 255, 0.75);
  border-radius: 16px;
  padding: 13px 18px;
  color: white;
  background: rgba(255, 255, 255, 0.08);
  font-weight: 900;
  cursor: pointer;
  transition: background 0.18s, transform 0.18s;
}

.refresh-btn:hover {
  background: rgba(255, 255, 255, 0.16);
  transform: translateY(-1px);
}

.refresh-btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

/* Messages */
.success-box {
  margin-bottom: 18px;
  padding: 14px 18px;
  border-radius: 16px;
  color: #146c2e;
  background: #dff6e5;
  font-weight: 800;
  box-shadow: 0 12px 26px rgba(27, 72, 43, 0.08);
}

/* Stats */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
  margin-bottom: 18px;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 18px;
  border-radius: 20px;
  background: #ffffff;
  box-shadow: 0 14px 30px rgba(27, 72, 43, 0.1);
}

.stat-icon {
  display: grid;
  place-items: center;
  width: 44px;
  height: 44px;
  border-radius: 16px;
  background: #e3f3e7;
  font-size: 1.2rem;
}

.stat-card p {
  margin: 0 0 3px;
  color: #657568;
  font-size: 0.82rem;
  font-weight: 700;
}

.stat-card strong {
  color: #123f25;
  font-size: 1.5rem;
  font-weight: 950;
}

/* Search */
.search-panel {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 18px;
  padding: 0 18px;
  height: 58px;
  border-radius: 18px;
  background: #ffffff;
  box-shadow: 0 14px 30px rgba(27, 72, 43, 0.1);
}

.search-icon {
  opacity: 0.65;
}

.search-input {
  width: 100%;
  border: none;
  outline: none;
  color: #1f2933;
  background: transparent;
  font-size: 0.95rem;
  font-family: inherit;
}

.search-input::placeholder {
  color: #8a968d;
}

/* Panel tabla */
.users-panel {
  overflow: hidden;
  border-radius: 24px;
  background: #ffffff;
  box-shadow: 0 22px 48px rgba(27, 72, 43, 0.13);
}

.panel-header {
  display: flex;
  justify-content: space-between;
  gap: 14px;
  padding: 22px 24px;
  border-bottom: 1px solid #e4ebe5;
}

.panel-header h3 {
  margin: 0;
  color: #123f25;
  font-size: 1.22rem;
  font-weight: 900;
}

.panel-header p {
  margin: 4px 0 0;
  color: #6c7a70;
  font-size: 0.88rem;
}

.table-wrap {
  width: 100%;
  overflow-x: auto;
}

.users-table {
  width: 100%;
  min-width: 980px;
  border-collapse: collapse;
}

.users-table th,
.users-table td {
  padding: 16px 18px;
  text-align: left;
  border-bottom: 1px solid #e7eee8;
  color: #33443a;
  font-size: 0.92rem;
}

.users-table th {
  color: #254834;
  background: #eff7f1;
  font-size: 0.78rem;
  font-weight: 950;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.users-table tr:hover td {
  background: #f8fbf8;
}

.id-cell {
  color: #708076;
  font-weight: 900;
}

.user-cell {
  display: flex;
  align-items: center;
  gap: 12px;
  font-weight: 850;
}

.avatar {
  display: grid;
  place-items: center;
  width: 38px;
  height: 38px;
  border-radius: 14px;
  color: white;
  background: linear-gradient(135deg, #1e5631, #3a9e61);
  font-size: 0.78rem;
  font-weight: 950;
}

.role-pill,
.status-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 7px 12px;
  border-radius: 999px;
  font-size: 0.76rem;
  font-weight: 950;
  text-transform: capitalize;
}

.role-pill {
  color: #1f5d37;
  background: #e3f3e7;
}

.role-pill.admin {
  color: #16436f;
  background: #dceeff;
}

.status-pill.active {
  color: #146c2e;
  background: #dff6e5;
}

.status-pill.inactive {
  color: #9b1c1c;
  background: #fde0e0;
}

.toggle-status-btn {
  min-width: 118px;
  border: none;
  border-radius: 13px;
  padding: 10px 13px;
  color: white;
  font-size: 0.78rem;
  font-weight: 950;
  cursor: pointer;
  transition: transform 0.18s, opacity 0.18s;
}

.toggle-status-btn:hover:not(:disabled) {
  transform: translateY(-1px);
}

.toggle-status-btn.activate {
  background: linear-gradient(135deg, #1e5631, #3a9e61);
}

.toggle-status-btn.deactivate {
  background: linear-gradient(135deg, #9b1c1c, #d43b3b);
}

.toggle-status-btn:disabled {
  cursor: not-allowed;
  opacity: 0.55;
  background: #8a968d;
}

.state-box,
.error-box {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 11px;
  min-height: 180px;
  padding: 28px;
  text-align: center;
  color: #516055;
}

.error-box {
  min-height: auto;
  margin: 22px;
  border-radius: 16px;
  color: #9b1c1c;
  background: #fde0e0;
}

@media (max-width: 880px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .admin-hero {
    align-items: flex-start;
    flex-direction: column;
  }

  .refresh-btn {
    width: 100%;
  }
}

@media (max-width: 620px) {
  .admin-layout {
    width: min(100% - 22px, 1180px);
    padding-top: 14px;
  }

  .admin-topbar,
  .topbar-actions {
    align-items: stretch;
    flex-direction: column;
    width: 100%;
  }

  .logout-btn,
  .secondary-btn {
    width: 100%;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .admin-hero {
    padding: 24px;
  }
}
</style>