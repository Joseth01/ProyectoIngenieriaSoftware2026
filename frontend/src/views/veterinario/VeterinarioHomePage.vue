<template>
  <ion-page>
    <ion-content :fullscreen="true" class="vet-content">
      <div class="page">

        <header class="topbar">
          <div>
            <p class="eyebrow">BovWeight CR · Médico</p>
            <h1>Panel veterinario</h1>
            <p class="subtitle">
              Seguimiento de fincas, animales e historial de peso.
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
                <p class="section-kicker">Animales</p>
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
  limpiarSesion,
  type AnimalDto,
  type FincaDto,
  type VeterinarioPerfilDto,
} from '@/services/api';

const router = useRouter();

const loading = ref(true);
const savingPerfil = ref(false);
const errorMsg = ref('');
const editandoPerfil = ref(false);
const busqueda = ref('');

const perfil = ref<VeterinarioPerfilDto | null>(null);
const fincas = ref<FincaDto[]>([]);
const animales = ref<AnimalDto[]>([]);

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

function obtenerNombrePropietario(finca: FincaDto): string {
  return finca.propietario?.name || finca.usuario?.name || 'No disponible';
}

function cargarFormularioPerfil() {
  formPerfil.codigo_colegiado =
    perfil.value?.perfil_veterinario?.codigo_colegiado || '';

  formPerfil.telefono_urgencias =
    perfil.value?.perfil_veterinario?.telefono_urgencias || '';

  formPerfil.especialidad =
    perfil.value?.perfil_veterinario?.especialidad || '';
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
    ] = await Promise.all([
      getPerfilVeterinario(),
      getVeterinarioFincas(),
      getVeterinarioAnimales(),
    ]);

    perfil.value = perfilResp.datos;
    fincas.value = fincasResp.datos || [];
    animales.value = animalesResp.datos || [];

    cargarFormularioPerfil();
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
.profile-label {
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
.primary-btn {
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

.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
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

.primary-btn {
  background: linear-gradient(135deg, #12321f, #2c7a4a);
  color: white;
  border-radius: 14px;
  padding: 12px 16px;
}

.primary-btn.full {
  width: 100%;
}

.primary-btn:disabled {
  opacity: .65;
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
.search-input {
  border: 1.5px solid #dce7df;
  background: #f7fbf8;
  border-radius: 14px;
  padding: 12px;
  outline: none;
  font-family: inherit;
}

.search-input {
  min-width: 210px;
}

.farm-list,
.animal-list {
  display: grid;
  gap: 10px;
}

.farm-card {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: center;
}

.farm-card h4,
.animal-card h4 {
  margin: 0 0 4px;
  color: #12321f;
}

.farm-card p,
.animal-card p {
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

@media (max-width: 720px) {
  .page {
    padding: 16px;
  }

  .topbar,
  .profile-card,
  .section-head,
  .farm-card {
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
}
</style>