<template>
  <ion-page>
    <ion-content :fullscreen="true" class="admin-content">
      <div class="admin-layout">

        <!-- Barra superior -->
        <header class="admin-topbar">
          <div>
            <p class="topbar-label">BovWeight CR</p>
            <h2>Bitácora del sistema</h2>
          </div>

          <div class="topbar-actions">
            <button class="secondary-btn" @click="irUsuarios">
              Usuarios
            </button>

            <button class="logout-btn" @click="cerrarSesion">
              Salir
            </button>
          </div>
        </header>

        <!-- Hero -->
        <section class="admin-hero">
          <div>
            <p class="eyebrow">Auditoría</p>
            <h1>Registro de actividad</h1>
            <p class="hero-text">
              Consulta los eventos importantes del sistema: inicios de sesión,
              cambios de estado y acciones administrativas.
            </p>
          </div>

          <button
            class="refresh-btn"
            @click="cargarBitacoras"
            :disabled="cargando"
          >
            {{ cargando ? 'Actualizando...' : 'Actualizar' }}
          </button>
        </section>

        <!-- Resumen -->
        <section class="stats-grid">
          <article class="stat-card">
            <span class="stat-icon">📋</span>
            <div>
              <p>Total eventos</p>
              <strong>{{ bitacoras.length }}</strong>
            </div>
          </article>

          <article class="stat-card">
            <span class="stat-icon">🔐</span>
            <div>
              <p>Autenticación</p>
              <strong>{{ totalAutenticacion }}</strong>
            </div>
          </article>

          <article class="stat-card">
            <span class="stat-icon">🛡️</span>
            <div>
              <p>Administración</p>
              <strong>{{ totalAdministracion }}</strong>
            </div>
          </article>

          <article class="stat-card">
            <span class="stat-icon">👤</span>
            <div>
              <p>Usuarios</p>
              <strong>{{ totalUsuarios }}</strong>
            </div>
          </article>
        </section>

        <!-- Filtros -->
        <div class="filters-grid">
          <div class="search-panel">
            <span class="search-icon">🔎</span>

            <input
              v-model="busqueda"
              class="search-input"
              type="text"
              placeholder="Buscar por acción, módulo, usuario o descripción"
            />
          </div>

          <select v-model="moduloFiltro" class="filter-select">
            <option value="">Todos los módulos</option>
            <option
              v-for="modulo in modulosDisponibles"
              :key="modulo"
              :value="modulo"
            >
              {{ modulo }}
            </option>
          </select>
        </div>

        <!-- Tabla -->
        <section class="logs-panel">
          <div class="panel-header">
            <div>
              <h3>Eventos registrados</h3>
              <p>Total mostrado: {{ bitacorasFiltradas.length }}</p>
            </div>
          </div>

          <div v-if="cargando" class="state-box">
            <ion-spinner name="crescent" />
            <p>Cargando bitácora...</p>
          </div>

          <div v-else-if="error" class="error-box">
            {{ error }}
          </div>

          <div v-else-if="bitacorasFiltradas.length === 0" class="state-box">
            <p>No se encontraron eventos.</p>
          </div>

          <div v-else class="table-wrap">
            <table class="logs-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Fecha</th>
                  <th>Usuario</th>
                  <th>Acción</th>
                  <th>Módulo</th>
                  <th>Descripción</th>
                  <th>Entidad</th>
                  <th>IP</th>
                  <th>Detalles</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="item in bitacorasFiltradas"
                  :key="item.id"
                >
                  <td class="id-cell">#{{ item.id }}</td>

                  <td>{{ formatearFechaHora(item.created_at) }}</td>

                  <td>
                    <div class="user-cell">
                      <div class="avatar">
                        {{ obtenerIniciales(item.usuario?.name) }}
                      </div>

                      <div>
                        <strong>{{ item.usuario?.name || 'Sistema' }}</strong>
                        <small>{{ item.usuario?.email || 'Sin usuario' }}</small>
                      </div>
                    </div>
                  </td>

                  <td>
                    <span class="action-pill">
                      {{ formatearAccion(item.accion) }}
                    </span>
                  </td>

                  <td>
                    <span class="module-pill">
                      {{ item.modulo || 'Sin módulo' }}
                    </span>
                  </td>

                  <td class="description-cell">
                    {{ item.descripcion || 'Sin descripción' }}
                  </td>

                  <td>
                    <span class="entity-text">
                      {{ item.entidad_tipo || 'N/A' }}
                      <span v-if="item.entidad_id">
                        #{{ item.entidad_id }}
                      </span>
                    </span>
                  </td>

                  <td>{{ item.ip || 'N/A' }}</td>

                  <td>
                    <button
                      class="details-btn"
                      @click="abrirDetalles(item)"
                    >
                      Ver
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Modal simple de detalles -->
        <div
          v-if="bitacoraSeleccionada"
          class="modal-backdrop"
          @click.self="cerrarDetalles"
        >
          <section class="details-modal">
            <header class="modal-header">
              <div>
                <p class="topbar-label">Detalle del evento</p>
                <h3>{{ bitacoraSeleccionada.accion }}</h3>
              </div>

              <button class="close-btn" @click="cerrarDetalles">
                Cerrar
              </button>
            </header>

            <div class="details-grid">
              <article>
                <span>Usuario</span>
                <strong>
                  {{ bitacoraSeleccionada.usuario?.name || 'Sistema' }}
                </strong>
              </article>

              <article>
                <span>Módulo</span>
                <strong>
                  {{ bitacoraSeleccionada.modulo || 'Sin módulo' }}
                </strong>
              </article>

              <article>
                <span>Entidad</span>
                <strong>
                  {{ bitacoraSeleccionada.entidad_tipo || 'N/A' }}
                  <span v-if="bitacoraSeleccionada.entidad_id">
                    #{{ bitacoraSeleccionada.entidad_id }}
                  </span>
                </strong>
              </article>

              <article>
                <span>IP</span>
                <strong>
                  {{ bitacoraSeleccionada.ip || 'N/A' }}
                </strong>
              </article>
            </div>

            <div class="json-panels">
              <div>
                <h4>Datos anteriores</h4>
                <pre>{{ mostrarJson(bitacoraSeleccionada.datos_anteriores) }}</pre>
              </div>

              <div>
                <h4>Datos nuevos</h4>
                <pre>{{ mostrarJson(bitacoraSeleccionada.datos_nuevos) }}</pre>
              </div>
            </div>
          </section>
        </div>

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
  getAdminBitacoras,
  type BitacoraDto
} from '@/services/api';

const router = useRouter();

const bitacoras = ref<BitacoraDto[]>([]);
const bitacoraSeleccionada = ref<BitacoraDto | null>(null);
const busqueda = ref('');
const moduloFiltro = ref('');
const cargando = ref(false);
const error = ref('');

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

const irUsuarios = () => {
  router.push('/admin/usuarios');
};

const cargarBitacoras = async () => {
  cargando.value = true;
  error.value = '';

  try {
    const response = await getAdminBitacoras();
    bitacoras.value = response.datos;
  } catch (err: any) {
    error.value =
      err?.message ||
      'No se pudo cargar la bitácora.';
  } finally {
    cargando.value = false;
  }
};

const bitacorasFiltradas = computed(() => {
  const texto = busqueda.value
    .trim()
    .toLowerCase();

  return bitacoras.value.filter((item) => {
    const coincideModulo =
      !moduloFiltro.value ||
      item.modulo === moduloFiltro.value;

    const usuario = item.usuario?.name || '';
    const correo = item.usuario?.email || '';

    const coincideTexto =
      !texto ||
      item.accion?.toLowerCase().includes(texto) ||
      String(item.modulo || '').toLowerCase().includes(texto) ||
      String(item.descripcion || '').toLowerCase().includes(texto) ||
      usuario.toLowerCase().includes(texto) ||
      correo.toLowerCase().includes(texto);

    return coincideModulo && coincideTexto;
  });
});

const modulosDisponibles = computed(() => {
  const modulos = bitacoras.value
    .map((item) => item.modulo)
    .filter((modulo): modulo is string => Boolean(modulo));

  return Array.from(new Set(modulos)).sort();
});

const totalAutenticacion = computed(() => {
  return bitacoras.value.filter((item) => item.modulo === 'Autenticación').length;
});

const totalAdministracion = computed(() => {
  return bitacoras.value.filter((item) => item.modulo === 'Administración').length;
});

const totalUsuarios = computed(() => {
  return bitacoras.value.filter((item) => item.modulo === 'Usuarios').length;
});

const formatearFechaHora = (fecha: string | undefined): string => {
  if (!fecha) {
    return 'Sin fecha';
  }

  const date = new Date(fecha);

  if (Number.isNaN(date.getTime())) {
    return fecha;
  }

  return date.toLocaleString('es-CR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const obtenerIniciales = (nombre: string | undefined): string => {
  if (!nombre) {
    return 'S';
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

const formatearAccion = (accion: string): string => {
  return accion
    .replaceAll('_', ' ')
    .toLowerCase();
};

const abrirDetalles = (item: BitacoraDto) => {
  bitacoraSeleccionada.value = item;
};

const cerrarDetalles = () => {
  bitacoraSeleccionada.value = null;
};

const mostrarJson = (valor: Record<string, any> | null | undefined): string => {
  if (!valor) {
    return 'Sin datos';
  }

  return JSON.stringify(valor, null, 2);
};

onMounted(() => {
  cargarBitacoras();
});
</script>

<style scoped>
.admin-content {
  --background: #edf4ef;
}

.admin-layout {
  width: min(1240px, calc(100% - 32px));
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
.secondary-btn,
.refresh-btn,
.details-btn,
.close-btn {
  border: none;
  font-family: inherit;
  cursor: pointer;
  font-weight: 900;
  transition: transform 0.18s, opacity 0.18s, background 0.18s;
}

.logout-btn {
  border-radius: 14px;
  padding: 11px 18px;
  color: #ffffff;
  background: linear-gradient(135deg, #9b1c1c, #d43b3b);
  box-shadow: 0 10px 22px rgba(155, 28, 28, 0.22);
}

.secondary-btn {
  border-radius: 14px;
  padding: 11px 18px;
  color: #1e5631;
  background: #e3f3e7;
}

.logout-btn:hover,
.secondary-btn:hover,
.refresh-btn:hover,
.details-btn:hover,
.close-btn:hover {
  transform: translateY(-1px);
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
  max-width: 720px;
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
}

.refresh-btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
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

/* Filtros */
.filters-grid {
  display: grid;
  grid-template-columns: 1fr 240px;
  gap: 14px;
  margin-bottom: 18px;
}

.search-panel {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 0 18px;
  height: 58px;
  border-radius: 18px;
  background: #ffffff;
  box-shadow: 0 14px 30px rgba(27, 72, 43, 0.1);
}

.search-icon {
  opacity: 0.65;
}

.search-input,
.filter-select {
  width: 100%;
  border: none;
  outline: none;
  color: #1f2933;
  background: transparent;
  font-size: 0.95rem;
  font-family: inherit;
}

.filter-select {
  height: 58px;
  padding: 0 18px;
  border-radius: 18px;
  background: #ffffff;
  box-shadow: 0 14px 30px rgba(27, 72, 43, 0.1);
}

/* Tabla */
.logs-panel {
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

.logs-table {
  width: 100%;
  min-width: 1180px;
  border-collapse: collapse;
}

.logs-table th,
.logs-table td {
  padding: 16px 18px;
  text-align: left;
  border-bottom: 1px solid #e7eee8;
  color: #33443a;
  font-size: 0.88rem;
  vertical-align: middle;
}

.logs-table th {
  color: #254834;
  background: #eff7f1;
  font-size: 0.76rem;
  font-weight: 950;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.logs-table tr:hover td {
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
}

.user-cell strong {
  display: block;
  color: #21372a;
  font-weight: 900;
}

.user-cell small {
  display: block;
  color: #77857c;
  font-size: 0.76rem;
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

.action-pill,
.module-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 7px 12px;
  border-radius: 999px;
  font-size: 0.74rem;
  font-weight: 950;
  text-transform: capitalize;
  white-space: nowrap;
}

.action-pill {
  color: #16436f;
  background: #dceeff;
}

.module-pill {
  color: #1f5d37;
  background: #e3f3e7;
}

.description-cell {
  max-width: 280px;
}

.entity-text {
  font-weight: 800;
  color: #496254;
}

.details-btn {
  border-radius: 12px;
  padding: 9px 14px;
  color: white;
  background: linear-gradient(135deg, #1e5631, #3a9e61);
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

/* Modal */
.modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 50;
  display: grid;
  place-items: center;
  padding: 20px;
  background: rgba(7, 25, 14, 0.55);
}

.details-modal {
  width: min(900px, 100%);
  max-height: 86vh;
  overflow-y: auto;
  border-radius: 24px;
  background: #ffffff;
  box-shadow: 0 28px 70px rgba(0, 0, 0, 0.28);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 22px 24px;
  border-bottom: 1px solid #e4ebe5;
}

.modal-header h3 {
  margin: 0;
  color: #123f25;
  font-size: 1.25rem;
  font-weight: 950;
}

.close-btn {
  border-radius: 14px;
  padding: 11px 18px;
  color: #1e5631;
  background: #e3f3e7;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
  padding: 22px 24px;
}

.details-grid article {
  padding: 14px;
  border-radius: 16px;
  background: #f2f7f3;
}

.details-grid span {
  display: block;
  margin-bottom: 4px;
  color: #6c7a70;
  font-size: 0.76rem;
  font-weight: 800;
}

.details-grid strong {
  color: #123f25;
  font-size: 0.9rem;
}

.json-panels {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
  padding: 0 24px 24px;
}

.json-panels h4 {
  margin: 0 0 8px;
  color: #123f25;
}

.json-panels pre {
  min-height: 180px;
  max-height: 320px;
  overflow: auto;
  margin: 0;
  padding: 16px;
  border-radius: 16px;
  color: #dceeff;
  background: #102016;
  font-size: 0.78rem;
  line-height: 1.5;
}

@media (max-width: 980px) {
  .stats-grid,
  .details-grid,
  .json-panels {
    grid-template-columns: repeat(2, 1fr);
  }

  .filters-grid {
    grid-template-columns: 1fr;
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
    width: min(100% - 22px, 1240px);
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

  .stats-grid,
  .details-grid,
  .json-panels {
    grid-template-columns: 1fr;
  }

  .admin-hero {
    padding: 24px;
  }
}
</style>