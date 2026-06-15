<template>
  <ion-page>
    <ion-content :fullscreen="true" class="vet-content">
      <div class="page">

        <header class="topbar">
          <div>
            <p class="eyebrow">BovWeight CR · Médico</p>
            <h1>Panel veterinario</h1>
            <p class="subtitle">
              Seguimiento de fincas, animales, historial de peso y solicitudes ganaderas.
            </p>
          </div>

          <button class="logout-btn" @click="cerrarSesion">
            Salir
          </button>
        </header>

        <section v-if="loading" class="state-card">
          <div class="loader"></div>
          <p>Cargando información veterinaria…</p>
        </section>

        <section v-else-if="errorMsg" class="state-card error">
          <p>{{ errorMsg }}</p>
          <button class="primary-btn" @click="cargarDatos">
            Reintentar
          </button>
        </section>

        <template v-else>
          <section class="profile-card">
            <div class="avatar">
              🩺
            </div>

            <div class="profile-info">
              <p class="profile-label">Veterinario asignado</p>
              <h2>{{ perfil?.usuario?.name || 'Veterinario' }}</h2>
              <p>{{ perfil?.usuario?.email }}</p>

              <div class="tag-row">
                <span class="tag">
                  Código: {{ perfil?.perfil_veterinario?.codigo_colegiado || 'No registrado' }}
                </span>

                <span class="tag">
                  {{ perfil?.perfil_veterinario?.especialidad || 'Especialidad no registrada' }}
                </span>
              </div>
            </div>
          </section>

          <section class="stats-grid">
            <article class="stat-card">
              <span class="stat-icon">🏡</span>
              <p>Fincas asignadas</p>
              <strong>{{ perfil?.total_fincas || 0 }}</strong>
            </article>

            <article class="stat-card">
              <span class="stat-icon">🐄</span>
              <p>Animales disponibles</p>
              <strong>{{ perfil?.total_animales || 0 }}</strong>
            </article>

            <article class="stat-card">
              <span class="stat-icon">📬</span>
              <p>Solicitudes activas</p>
              <strong>{{ solicitudesActivas.length }}</strong>
            </article>

            <article class="stat-card">
              <span class="stat-icon">🩺</span>
              <p>Atenciones realizadas</p>
              <strong>{{ historialAtenciones.length }}</strong>
            </article>
          </section>

          <section class="section-card">
            <div class="section-head">
              <div>
                <p class="section-kicker">Solicitudes ganaderas</p>
                <h3>Solicitudes activas</h3>
              </div>
            </div>

            <div v-if="solicitudesActivas.length === 0" class="empty">
              No tiene solicitudes pendientes o en revisión.
            </div>

            <div v-else class="request-list">
              <article
                v-for="solicitud in solicitudesActivas"
                :key="solicitud.id"
                class="request-card"
              >
                <div class="request-head">
                  <div>
                    <p class="request-kicker">
                      Solicitud #{{ solicitud.id }}
                    </p>

                    <h4>
                      {{ solicitud.animal?.nombre || 'Animal no disponible' }}
                    </h4>

                    <p>
                      Arete:
                      <strong>{{ solicitud.animal?.numero_arete || 'No registrado' }}</strong>
                    </p>

                    <p>
                      Finca:
                      <strong>{{ obtenerNombreFincaSolicitud(solicitud) }}</strong>
                    </p>

                    <p>
                      Ganadero:
                      <strong>{{ solicitud.ganadero?.name || 'No disponible' }}</strong>
                    </p>

                    <p>
                      Fecha solicitud:
                      <strong>{{ formatearFechaSolicitud(solicitud.fecha_solicitud || solicitud.created_at) }}</strong>
                    </p>
                  </div>

                  <span
                    class="status-pill"
                    :class="claseEstadoSolicitud(solicitud.estado)"
                  >
                    {{ textoEstadoSolicitud(solicitud.estado) }}
                  </span>
                </div>

                <div class="request-body">
                  <span>Motivo</span>
                  <p>{{ solicitud.motivo }}</p>
                </div>

                <textarea
                  v-model.trim="respuestasSolicitudes[solicitud.id]"
                  class="response-input"
                  rows="3"
                  placeholder="Escribe una respuesta o recomendación..."
                ></textarea>

                <div class="request-actions">
                  <button
                    class="ghost-btn"
                    @click="abrirAnimal(solicitud.animal_id)"
                  >
                    Ver animal
                  </button>

                  <button
                    v-if="solicitud.estado === 'pendiente'"
                    class="soft-btn"
                    :disabled="savingSolicitudId === solicitud.id"
                    @click="actualizarSolicitud(solicitud.id, 'en_revision')"
                  >
                    En revisión
                  </button>

                  <button
                    class="primary-btn mini"
                    :disabled="savingSolicitudId === solicitud.id"
                    @click="actualizarSolicitud(solicitud.id, 'atendida')"
                  >
                    Atendida
                  </button>

                  <button
                    class="danger-btn"
                    :disabled="savingSolicitudId === solicitud.id"
                    @click="actualizarSolicitud(solicitud.id, 'rechazada')"
                  >
                    Rechazar
                  </button>
                </div>
              </article>
            </div>
          </section>

          <section class="section-card">
            <div class="section-head">
              <div>
                <p class="section-kicker">Historial clínico</p>
                <h3>Animales atendidos</h3>
              </div>
            </div>

            <div v-if="historialAtenciones.length === 0" class="empty">
              Todavía no hay animales atendidos por solicitudes.
            </div>

            <div v-else class="history-grid">
              <article
                v-for="solicitud in historialAtenciones"
                :key="solicitud.id"
                class="history-card"
              >
                <div class="history-head">
                  <div class="history-avatar">
                    {{ inicialAnimalSolicitud(solicitud) }}
                  </div>

                  <div class="history-info">
                    <p class="request-kicker">
                      Atención #{{ solicitud.id }}
                    </p>

                    <h4>
                      {{ solicitud.animal?.nombre || 'Animal no disponible' }}
                    </h4>

                    <p>
                      Arete: {{ solicitud.animal?.numero_arete || 'No registrado' }}
                    </p>

                    <p>
                      Finca: {{ obtenerNombreFincaSolicitud(solicitud) }}
                    </p>

                    <p>
                      Ganadero: {{ solicitud.ganadero?.name || 'No disponible' }}
                    </p>
                  </div>

                  <span
                    class="status-pill"
                    :class="claseEstadoSolicitud(solicitud.estado)"
                  >
                    {{ textoEstadoSolicitud(solicitud.estado) }}
                  </span>
                </div>

                <div class="history-detail">
                  <span>Motivo original</span>
                  <p>{{ solicitud.motivo }}</p>
                </div>

                <div class="history-detail">
                  <span>Respuesta veterinaria</span>
                  <p>{{ solicitud.respuesta_veterinario || 'Sin respuesta registrada.' }}</p>
                </div>

                <div class="history-meta">
                  <span>
                    Solicitud: {{ formatearFechaSolicitud(solicitud.fecha_solicitud || solicitud.created_at) }}
                  </span>

                  <span>
                    Atención: {{ formatearFechaSolicitud(solicitud.fecha_atencion || solicitud.updated_at) }}
                  </span>
                </div>

                <div class="request-actions">
                  <button
                    class="ghost-btn"
                    @click="abrirAnimal(solicitud.animal_id)"
                  >
                    Ver animal
                  </button>
                </div>
              </article>
            </div>
          </section>

          <section class="section-card">
            <div class="section-head">
              <div>
                <p class="section-kicker">Fincas atendidas</p>
                <h3>Fincas visitadas por solicitudes</h3>
              </div>
            </div>

            <div v-if="fincasAtendidasPorSolicitudes.length === 0" class="empty">
              Aún no hay fincas atendidas por medio de solicitudes veterinarias.
            </div>

            <div v-else class="farm-list">
              <article
                v-for="item in fincasAtendidasPorSolicitudes"
                :key="item.fincaId"
                class="farm-card attended-farm"
              >
                <div>
                  <h4>{{ item.nombre }}</h4>
                  <p>{{ item.ubicacion || 'Ubicación no registrada' }}</p>
                  <small>
                    Ganadero: {{ item.ganadero || 'No disponible' }}
                  </small>
                  <small>
                    Atenciones realizadas: {{ item.totalAtenciones }}
                  </small>
                </div>

                <span class="farm-badge attended">
                  Atendida
                </span>
              </article>
            </div>
          </section>

          <section class="section-card">
            <div class="section-head">
              <div>
                <p class="section-kicker">Perfil profesional</p>
                <h3>Datos del veterinario</h3>
              </div>

              <button
                class="ghost-btn"
                @click="toggleEditarPerfil"
              >
                {{ editandoPerfil ? 'Cancelar' : 'Editar' }}
              </button>
            </div>

            <div v-if="!editandoPerfil" class="info-list">
              <div class="info-item">
                <span>Teléfono de urgencias</span>
                <strong>{{ perfil?.perfil_veterinario?.telefono_urgencias || 'No registrado' }}</strong>
              </div>

              <div class="info-item">
                <span>Código colegiado</span>
                <strong>{{ perfil?.perfil_veterinario?.codigo_colegiado || 'No registrado' }}</strong>
              </div>

              <div class="info-item">
                <span>Especialidad</span>
                <strong>{{ perfil?.perfil_veterinario?.especialidad || 'No registrada' }}</strong>
              </div>
            </div>

            <div v-else class="form-grid">
              <label>
                Código colegiado
                <input
                  v-model.trim="formPerfil.codigo_colegiado"
                  type="text"
                  placeholder="Ej: VET-2026"
                />
              </label>

              <label>
                Teléfono de urgencias
                <input
                  v-model.trim="formPerfil.telefono_urgencias"
                  type="text"
                  placeholder="Ej: 8888-8888"
                />
              </label>

              <label>
                Especialidad
                <input
                  v-model.trim="formPerfil.especialidad"
                  type="text"
                  placeholder="Ej: Medicina bovina"
                />
              </label>

              <button
                class="primary-btn full"
                :disabled="savingPerfil"
                @click="guardarPerfil"
              >
                {{ savingPerfil ? 'Guardando…' : 'Guardar perfil' }}
              </button>
            </div>
          </section>

          <section class="section-card">
            <div class="section-head">
              <div>
                <p class="section-kicker">Fincas asignadas</p>
                <h3>Fincas que puede atender</h3>
              </div>
            </div>

            <div v-if="fincas.length === 0" class="empty">
              No tiene fincas asignadas todavía.
            </div>

            <div v-else class="farm-list">
              <article
                v-for="finca in fincas"
                :key="finca.id"
                class="farm-card"
              >
                <div>
                  <h4>{{ finca.nombre }}</h4>
                  <p>{{ finca.ubicacion || 'Ubicación no registrada' }}</p>
                  <small>
                    Ganadero: {{ obtenerNombrePropietario(finca) }}
                  </small>
                </div>

                <span class="farm-badge">
                  Asignada
                </span>
              </article>
            </div>
          </section>

          <section class="section-card">
            <div class="section-head">
              <div>
                <p class="section-kicker">Animales asignados</p>
                <h3>Animales de fincas asignadas</h3>
              </div>

              <input
                v-model.trim="busqueda"
                class="search-input"
                type="search"
                placeholder="Buscar animal..."
              />
            </div>

            <div v-if="animalesFiltrados.length === 0" class="empty">
              No hay animales para mostrar.
            </div>

            <div v-else class="animal-list">
              <article
                v-for="animal in animalesFiltrados"
                :key="animal.id"
                class="animal-card"
                @click="abrirAnimal(animal.id)"
              >
                <div class="animal-icon">
                  🐮
                </div>

                <div class="animal-info">
                  <h4>{{ animal.nombre }}</h4>
                  <p>Arete: {{ animal.numero_arete }}</p>
                  <small>
                    {{ animal.raza?.nombre || 'Raza no registrada' }}
                    ·
                    {{ animal.finca?.nombre || 'Finca no registrada' }}
                  </small>
                </div>

                <span class="arrow">›</span>
              </article>
            </div>
          </section>
        </template>

      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import {
  IonPage,
  IonContent,
  onIonViewWillEnter,
} from '@ionic/vue';

import {
  getPerfilVeterinario,
  actualizarPerfilVeterinario,
  getVeterinarioFincas,
  getVeterinarioAnimales,
  getSolicitudesVeterinariasVeterinario,
  responderSolicitudVeterinaria,
  limpiarSesion,
  formatFecha,
  type AnimalDto,
  type FincaDto,
  type SolicitudVeterinariaDto,
  type EstadoSolicitudVeterinaria,
  type VeterinarioPerfilDto,
} from '@/services/api';

const router = useRouter();

const loading = ref(true);
const savingPerfil = ref(false);
const savingSolicitudId = ref<number | null>(null);
const errorMsg = ref('');
const editandoPerfil = ref(false);
const busqueda = ref('');

const perfil = ref<VeterinarioPerfilDto | null>(null);
const fincas = ref<FincaDto[]>([]);
const animales = ref<AnimalDto[]>([]);
const solicitudes = ref<SolicitudVeterinariaDto[]>([]);

const respuestasSolicitudes = reactive<Record<number, string>>({});

const formPerfil = reactive({
  codigo_colegiado: '',
  telefono_urgencias: '',
  especialidad: '',
});

const animalesFiltrados = computed(() => {
  const texto = busqueda.value.toLowerCase();

  if (!texto) {
    return animales.value;
  }

  return animales.value.filter((animal) => {
    const nombre = animal.nombre?.toLowerCase() || '';
    const arete = animal.numero_arete?.toLowerCase() || '';
    const finca = animal.finca?.nombre?.toLowerCase() || '';
    const raza = animal.raza?.nombre?.toLowerCase() || '';

    return (
      nombre.includes(texto) ||
      arete.includes(texto) ||
      finca.includes(texto) ||
      raza.includes(texto)
    );
  });
});

const solicitudesActivas = computed(() =>
  solicitudes.value.filter((solicitud) =>
    solicitud.estado === 'pendiente' ||
    solicitud.estado === 'en_revision'
  )
);

const historialAtenciones = computed(() =>
  solicitudes.value.filter((solicitud) =>
    solicitud.estado === 'atendida' ||
    solicitud.estado === 'rechazada'
  )
);

const fincasAtendidasPorSolicitudes = computed(() => {
  const mapa = new Map<number, {
    fincaId: number;
    nombre: string;
    ubicacion: string | null;
    ganadero: string | null;
    totalAtenciones: number;
  }>();

  historialAtenciones.value.forEach((solicitud) => {
    const fincaId = Number(solicitud.finca_id);

    if (!fincaId) {
      return;
    }

    const fincaNombre =
      solicitud.finca?.nombre ||
      solicitud.animal?.finca?.nombre ||
      'Finca no registrada';

    const fincaUbicacion =
      solicitud.finca?.ubicacion ||
      solicitud.animal?.finca?.ubicacion ||
      null;

    const ganaderoNombre =
      solicitud.ganadero?.name || null;

    const existente = mapa.get(fincaId);

    if (existente) {
      existente.totalAtenciones += 1;
      return;
    }

    mapa.set(fincaId, {
      fincaId,
      nombre: fincaNombre,
      ubicacion: fincaUbicacion,
      ganadero: ganaderoNombre,
      totalAtenciones: 1,
    });
  });

  return Array.from(mapa.values());
});

function obtenerNombrePropietario(finca: FincaDto): string {
  return finca.propietario?.name || finca.usuario?.name || 'No disponible';
}

function obtenerNombreFincaSolicitud(solicitud: SolicitudVeterinariaDto): string {
  return solicitud.finca?.nombre ||
    solicitud.animal?.finca?.nombre ||
    'No registrada';
}

function inicialAnimalSolicitud(solicitud: SolicitudVeterinariaDto): string {
  const base =
    solicitud.animal?.nombre ||
    solicitud.animal?.numero_arete ||
    'AN';

  return base
    .trim()
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((parte) => parte[0])
    .join('')
    .toUpperCase() || 'AN';
}

function formatearFechaSolicitud(valor: string | null | undefined): string {
  return formatFecha(valor);
}

function textoEstadoSolicitud(estado: EstadoSolicitudVeterinaria): string {
  if (estado === 'pendiente') {
    return 'Pendiente';
  }

  if (estado === 'en_revision') {
    return 'En revisión';
  }

  if (estado === 'atendida') {
    return 'Atendida';
  }

  return 'Rechazada';
}

function claseEstadoSolicitud(estado: EstadoSolicitudVeterinaria): string {
  if (estado === 'pendiente') {
    return 'status-pending';
  }

  if (estado === 'en_revision') {
    return 'status-review';
  }

  if (estado === 'atendida') {
    return 'status-done';
  }

  return 'status-rejected';
}

function cargarFormularioPerfil() {
  formPerfil.codigo_colegiado =
    perfil.value?.perfil_veterinario?.codigo_colegiado || '';

  formPerfil.telefono_urgencias =
    perfil.value?.perfil_veterinario?.telefono_urgencias || '';

  formPerfil.especialidad =
    perfil.value?.perfil_veterinario?.especialidad || '';
}

function prepararRespuestasSolicitudes() {
  solicitudes.value.forEach((solicitud) => {
    if (respuestasSolicitudes[solicitud.id] === undefined) {
      respuestasSolicitudes[solicitud.id] =
        solicitud.respuesta_veterinario || '';
    }
  });
}

function toggleEditarPerfil() {
  editandoPerfil.value = !editandoPerfil.value;

  if (editandoPerfil.value) {
    cargarFormularioPerfil();
  }
}

async function cargarDatos() {
  loading.value = true;
  errorMsg.value = '';

  try {
    const [
      perfilResp,
      fincasResp,
      animalesResp,
      solicitudesResp,
    ] = await Promise.all([
      getPerfilVeterinario(),
      getVeterinarioFincas(),
      getVeterinarioAnimales(),
      getSolicitudesVeterinariasVeterinario(),
    ]);

    perfil.value = perfilResp.datos;
    fincas.value = fincasResp.datos || [];
    animales.value = animalesResp.datos || [];
    solicitudes.value = solicitudesResp.datos || [];

    cargarFormularioPerfil();
    prepararRespuestasSolicitudes();
  } catch (error: unknown) {
    errorMsg.value = error instanceof Error
      ? error.message
      : 'No se pudo cargar el panel veterinario.';
  } finally {
    loading.value = false;
  }
}

async function guardarPerfil() {
  savingPerfil.value = true;
  errorMsg.value = '';

  try {
    const resp = await actualizarPerfilVeterinario({
      codigo_colegiado: formPerfil.codigo_colegiado || null,
      telefono_urgencias: formPerfil.telefono_urgencias || null,
      especialidad: formPerfil.especialidad || null,
    });

    if (perfil.value) {
      perfil.value.perfil_veterinario = resp.datos;
    }

    editandoPerfil.value = false;
  } catch (error: unknown) {
    errorMsg.value = error instanceof Error
      ? error.message
      : 'No se pudo actualizar el perfil veterinario.';
  } finally {
    savingPerfil.value = false;
  }
}

async function actualizarSolicitud(
  id: number,
  estado: 'en_revision' | 'atendida' | 'rechazada'
) {
  savingSolicitudId.value = id;
  errorMsg.value = '';

  try {
    const respuesta = respuestasSolicitudes[id] || null;

    const resp = await responderSolicitudVeterinaria(
      id,
      {
        estado,
        respuesta_veterinario: respuesta,
      }
    );

    solicitudes.value = solicitudes.value.map((solicitud) =>
      solicitud.id === id
        ? resp.datos
        : solicitud
    );

    respuestasSolicitudes[id] =
      resp.datos.respuesta_veterinario || '';
  } catch (error: unknown) {
    errorMsg.value = error instanceof Error
      ? error.message
      : 'No se pudo actualizar la solicitud veterinaria.';
  } finally {
    savingSolicitudId.value = null;
  }
}

function abrirAnimal(id: number) {
  router.push(`/veterinario/animales/${id}`);
}

function cerrarSesion() {
  limpiarSesion();
  router.replace('/login');
}

onMounted(() => {
  cargarDatos();
});

onIonViewWillEnter(() => {
  cargarDatos();
});
</script>

<style scoped>
.vet-content {
  --background: #f3f7f4;
}

.page {
  min-height: 100vh;
  padding: 24px;
  color: #14251b;
}

.topbar {
  background: linear-gradient(135deg, #12321f, #2c7a4a);
  color: white;
  border-radius: 26px;
  padding: 24px;
  display: flex;
  justify-content: space-between;
  gap: 18px;
  align-items: flex-start;
  box-shadow: 0 18px 45px rgba(18, 50, 31, .25);
}

.eyebrow,
.section-kicker,
.profile-label,
.request-kicker {
  margin: 0;
  font-size: .72rem;
  text-transform: uppercase;
  letter-spacing: .12em;
  opacity: .7;
  font-weight: 800;
}

.topbar h1 {
  margin: 6px 0;
  font-size: 1.9rem;
  font-weight: 900;
}

.subtitle {
  margin: 0;
  color: rgba(255,255,255,.78);
  line-height: 1.45;
}

.logout-btn,
.ghost-btn,
.primary-btn,
.soft-btn,
.danger-btn {
  border: none;
  font-family: inherit;
  cursor: pointer;
  font-weight: 800;
}

.logout-btn {
  background: rgba(255,255,255,.14);
  color: white;
  border: 1px solid rgba(255,255,255,.22);
  padding: 10px 14px;
  border-radius: 14px;
}

.profile-card,
.section-card,
.state-card {
  background: white;
  border-radius: 24px;
  padding: 22px;
  margin-top: 18px;
  box-shadow: 0 10px 30px rgba(15, 35, 22, .08);
}

.profile-card {
  display: flex;
  align-items: center;
  gap: 18px;
}

.avatar {
  width: 72px;
  height: 72px;
  border-radius: 24px;
  display: grid;
  place-items: center;
  background: #e9f8ef;
  font-size: 2rem;
}

.profile-info h2 {
  margin: 4px 0;
  color: #12321f;
}

.profile-info p {
  margin: 0;
  color: #66736b;
}

.tag-row {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: 12px;
}

.tag,
.farm-badge {
  background: #e9f8ef;
  color: #1d6b3c;
  padding: 7px 10px;
  border-radius: 999px;
  font-size: .75rem;
  font-weight: 800;
}

.farm-badge.attended {
  background: #e8f1ff;
  color: #1e4f91;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
  margin-top: 18px;
}

.stat-card {
  background: white;
  border-radius: 22px;
  padding: 18px;
  box-shadow: 0 10px 30px rgba(15, 35, 22, .08);
}

.stat-icon {
  font-size: 1.6rem;
}

.stat-card p {
  margin: 10px 0 4px;
  color: #66736b;
  font-weight: 700;
}

.stat-card strong {
  font-size: 1.8rem;
  color: #12321f;
}

.section-head {
  display: flex;
  justify-content: space-between;
  gap: 14px;
  align-items: center;
  margin-bottom: 14px;
}

.section-head h3 {
  margin: 3px 0 0;
  color: #12321f;
}

.ghost-btn {
  background: #eef6f1;
  color: #1d6b3c;
  padding: 9px 13px;
  border-radius: 12px;
}

.soft-btn {
  background: #e8f1ff;
  color: #1e4f91;
  padding: 9px 13px;
  border-radius: 12px;
}

.danger-btn {
  background: #fee2e2;
  color: #991b1b;
  padding: 9px 13px;
  border-radius: 12px;
}

.primary-btn {
  background: linear-gradient(135deg, #12321f, #2c7a4a);
  color: white;
  border-radius: 14px;
  padding: 12px 16px;
}

.primary-btn.mini {
  padding: 9px 13px;
  border-radius: 12px;
}

.primary-btn.full {
  width: 100%;
}

.primary-btn:disabled,
.ghost-btn:disabled,
.soft-btn:disabled,
.danger-btn:disabled {
  opacity: .65;
  cursor: not-allowed;
}

.info-list {
  display: grid;
  gap: 10px;
}

.info-item,
.farm-card,
.animal-card {
  border: 1px solid #e6ece8;
  background: #fbfdfb;
  border-radius: 18px;
  padding: 14px;
}

.info-item {
  display: flex;
  justify-content: space-between;
  gap: 12px;
}

.info-item span {
  color: #66736b;
}

.info-item strong {
  color: #12321f;
  text-align: right;
}

.form-grid {
  display: grid;
  gap: 12px;
}

.form-grid label {
  display: grid;
  gap: 6px;
  font-size: .85rem;
  font-weight: 800;
  color: #33443a;
}

.form-grid input,
.search-input,
.response-input {
  border: 1.5px solid #dce7df;
  background: #f7fbf8;
  border-radius: 14px;
  padding: 12px;
  outline: none;
  font-family: inherit;
}

.response-input {
  width: 100%;
  resize: vertical;
  min-height: 84px;
  margin-top: 12px;
  box-sizing: border-box;
}

.search-input {
  min-width: 210px;
}

.farm-list,
.animal-list,
.request-list,
.history-grid {
  display: grid;
  gap: 10px;
}

.farm-card {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: center;
}

.attended-farm small {
  display: block;
  margin-top: 3px;
}

.farm-card h4,
.animal-card h4,
.request-card h4,
.history-card h4 {
  margin: 0 0 4px;
  color: #12321f;
}

.farm-card p,
.animal-card p,
.request-card p,
.history-card p {
  margin: 0 0 3px;
  color: #66736b;
}

.farm-card small,
.animal-card small {
  color: #829086;
}

.animal-card {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  transition: transform .15s, box-shadow .15s;
}

.animal-card:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 22px rgba(15, 35, 22, .08);
}

.animal-icon {
  width: 46px;
  height: 46px;
  border-radius: 16px;
  background: #e9f8ef;
  display: grid;
  place-items: center;
  font-size: 1.4rem;
}

.animal-info {
  flex: 1;
}

.arrow {
  font-size: 1.8rem;
  color: #2c7a4a;
}

.request-card,
.history-card {
  border: 1px solid #e6ece8;
  background: #fbfdfb;
  border-radius: 18px;
  padding: 16px;
}

.request-head,
.history-head {
  display: flex;
  justify-content: space-between;
  gap: 14px;
}

.history-head {
  align-items: flex-start;
}

.history-avatar {
  width: 48px;
  height: 48px;
  border-radius: 16px;
  background: #e9f8ef;
  color: #1d6b3c;
  display: grid;
  place-items: center;
  font-weight: 900;
  flex-shrink: 0;
}

.history-info {
  flex: 1;
}

.request-body,
.request-response,
.history-detail {
  margin-top: 12px;
  background: white;
  border-radius: 14px;
  padding: 12px;
  border: 1px solid #edf2ef;
}

.request-body span,
.request-response span,
.history-detail span {
  display: block;
  font-size: .72rem;
  text-transform: uppercase;
  letter-spacing: .08em;
  font-weight: 900;
  color: #829086;
  margin-bottom: 5px;
}

.request-body p,
.request-response p,
.history-detail p {
  color: #33443a;
  line-height: 1.45;
}

.history-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}

.history-meta span {
  background: #eef6f1;
  color: #1d6b3c;
  padding: 7px 10px;
  border-radius: 999px;
  font-size: .72rem;
  font-weight: 800;
}

.request-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}

.status-pill {
  height: fit-content;
  padding: 7px 11px;
  border-radius: 999px;
  font-size: .75rem;
  font-weight: 900;
  white-space: nowrap;
}

.status-pending {
  background: #fff7ed;
  color: #9a3412;
}

.status-review {
  background: #e8f1ff;
  color: #1e4f91;
}

.status-done {
  background: #dcfce7;
  color: #166534;
}

.status-rejected {
  background: #fee2e2;
  color: #991b1b;
}

.empty {
  padding: 18px;
  border-radius: 16px;
  background: #f7fbf8;
  color: #66736b;
  text-align: center;
}

.state-card {
  text-align: center;
}

.state-card.error {
  color: #b42318;
}

.loader {
  width: 34px;
  height: 34px;
  border: 4px solid #d9eadf;
  border-top-color: #2c7a4a;
  border-radius: 50%;
  margin: 0 auto 12px;
  animation: spin .75s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 900px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 720px) {
  .page {
    padding: 16px;
  }

  .topbar,
  .profile-card,
  .section-head,
  .farm-card,
  .request-head,
  .history-head {
    flex-direction: column;
    align-items: stretch;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .search-input {
    min-width: 100%;
  }

  .info-item {
    flex-direction: column;
  }

  .info-item strong {
    text-align: left;
  }

  .request-actions {
    flex-direction: column;
  }

  .history-meta {
    flex-direction: column;
  }
}
</style>