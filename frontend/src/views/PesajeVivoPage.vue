<template>
  <ion-page>
    <ion-content :fullscreen="true" class="vivo-bg">

      <!-- APP BAR -->
      <div class="app-bar">
        <button class="back-btn" @click="router.back()">‹</button>

        <div class="app-bar-center">
          <div class="page-title">Pesaje con IA</div>
          <div class="page-sub">Estimación inteligente de peso</div>
        </div>

        <div class="ai-badge">🤖 IA</div>
      </div>

      <div class="body-pad">

        <!-- ANIMAL SELECTOR -->
        <div class="animal-card" @click="mostrarSelector = true">
          <div class="anim-emo">
            {{ animalSel ? '🐄' : '➕' }}
          </div>

          <div class="anim-info">
            <div class="anim-name">
              {{ animalSel ? nombreAnimal(animalSel) : 'Selecciona el animal' }}
            </div>

            <div class="anim-sub">
              {{ animalSel ? detalleAnimal(animalSel) : 'Toca para elegir o crear uno nuevo' }}
            </div>
          </div>

          <span class="chev">›</span>
        </div>

        <!-- VIEWFINDER -->
        <div class="viewfinder-wrap">
          <div class="viewfinder">

            <img
              v-if="imagenPreview"
              :src="imagenPreview"
              class="preview-img"
              alt="Imagen seleccionada"
            />

            <div v-if="estado === 'idle'" class="vf-idle">
              <div class="vf-corners">
                <div class="corner tl"></div>
                <div class="corner tr"></div>
                <div class="corner bl"></div>
                <div class="corner br"></div>
              </div>

              <div class="vf-hint" v-if="!imagenPreview">
                <span class="vf-ico">📷</span>
                <span>Selecciona una foto o toma una captura</span>
              </div>

              <div v-else class="result-chip">
                Imagen lista para analizar
              </div>
            </div>

            <div v-else-if="estado === 'analizando'" class="vf-analyzing">
              <div class="scan-line"></div>

              <div class="analyze-msg">
                <div class="spinner"></div>
                <span>{{ mensajeAnalisis }}</span>
              </div>
            </div>

            <div v-else-if="estado === 'resultado'" class="vf-result-overlay">
              <div class="vf-corners vf-corners-green">
                <div class="corner tl"></div>
                <div class="corner tr"></div>
                <div class="corner bl"></div>
                <div class="corner br"></div>
              </div>

              <div class="result-chip">
                ✓ Estimación generada
              </div>
            </div>

          </div>

          <div class="model-label">
            <span class="model-dot"></span>
            BovAI · Modelo morfométrico v1.0
          </div>
        </div>

        <!-- ACCIONES DE IMAGEN -->
        <div v-if="estado !== 'resultado'" class="image-actions">

          <button
            class="btn-secondary"
            :disabled="estado === 'analizando'"
            @click="tomarFoto"
          >
            📷 Tomar foto
          </button>

          <button
            class="btn-secondary"
            :disabled="estado === 'analizando'"
            @click="cargarGaleria"
          >
            🖼️ Galería
          </button>

        </div>

        <input
          ref="fileInput"
          type="file"
          accept="image/*"
          class="hidden-input"
          @change="cargarImagenDesdeInput"
        />

        <!-- BOTÓN ANALIZAR -->
        <div v-if="estado !== 'resultado'" class="shutter-area">

          <div class="shutter-hint">
            {{ textoEstado }}
          </div>

          <button
            class="shutter-btn"
            :class="{ 'shutter-disabled': !puedeAnalizar }"
            :disabled="!puedeAnalizar"
            @click="analizarImagen"
          >
            <div class="shutter-inner">
              <span v-if="estado === 'analizando'" class="shutter-spinner"></span>
              <span v-else>🤖</span>
            </div>
          </button>

        </div>

        <!-- RESULTADO -->
        <Transition name="slide-up">
          <div v-if="estado === 'resultado'" class="result-card">

            <div class="result-header">
              <span class="result-title">Estimación de peso</span>

              <span class="confidence-badge">
                {{ confianza }}% confianza
              </span>
            </div>

            <div class="result-weight-row">
              <div class="result-weight">
                {{ pesoEstimado }}
                <span class="result-unit">kg</span>
              </div>

              <div class="result-range">
                ± {{ margen }} kg
              </div>
            </div>

            <div class="result-metrics">
              <div class="rm-item">
                <span class="rm-lbl">Condición corporal</span>
                <span class="rm-val">{{ cc }}/5</span>
              </div>

              <div class="rm-item">
                <span class="rm-lbl">Alzada estimada</span>
                <span class="rm-val">{{ alzada }} cm</span>
              </div>
            </div>

            <p class="warning-note">
              Esta medición es una estimación. Para transacciones formales usa una báscula certificada.
            </p>

          </div>
        </Transition>

        <!-- BOTONES RESULTADO -->
        <div v-if="estado === 'resultado'" class="action-area">

          <button class="btn-retry" @click="reiniciar">
            🔄 Nueva imagen
          </button>

          <button
            class="btn-save"
            :disabled="saving"
            @click="guardar"
          >
            <span v-if="saving">Guardando…</span>
            <span v-else>💾 Guardar pesaje</span>
          </button>

        </div>

        <!-- FEEDBACK -->
        <div
          v-if="feedbackMsg"
          class="feedback"
          :class="feedbackOk ? 'feedback-ok' : 'feedback-err'"
        >
          {{ feedbackMsg }}
        </div>

        <!-- INFO -->
        <div class="info-section">
          <div class="sec-title">¿Cómo funciona?</div>

          <div class="info-card">

            <div class="info-step">
              <div class="step-num">1</div>
              <div>
                <div class="step-title">Selecciona el animal</div>
                <div class="step-sub">Elige de tu inventario o crea uno nuevo</div>
              </div>
            </div>

            <div class="info-step">
              <div class="step-num">2</div>
              <div>
                <div class="step-title">Carga una imagen</div>
                <div class="step-sub">Puedes tomar foto o elegir desde galería</div>
              </div>
            </div>

            <div class="info-step">
              <div class="step-num">3</div>
              <div>
                <div class="step-title">Obtén el peso estimado</div>
                <div class="step-sub">El servicio IA procesa la imagen y calcula el peso</div>
              </div>
            </div>

          </div>
        </div>

      </div>

      <!-- MODAL SELECTOR ANIMAL -->
      <ion-modal
        :is-open="mostrarSelector"
        @didDismiss="cerrarSelector"
      >
        <ion-content class="vivo-bg">

          <div class="app-bar">
            <button class="back-btn" @click="cerrarSelector">✕</button>

            <div class="app-bar-center">
              <div class="page-title small-title">Seleccionar animal</div>
            </div>

            <button class="mini-add" @click="abrirCrearAnimal">
              +
            </button>
          </div>

          <div class="body-pad">

            <div class="search-box">
              <span>🔍</span>

              <input
                v-model="busqueda"
                class="search-input"
                placeholder="Buscar por nombre o arete…"
              />
            </div>

            <button class="btn-create-animal" @click="abrirCrearAnimal">
              ➕ Crear nuevo animal
            </button>

            <div v-if="loadingAnimales" class="status-loading">
              Cargando animales…
            </div>

            <div
              v-for="a in animalesFiltrados"
              :key="a.id"
              class="animal-opt"
              @click="elegirAnimal(a)"
            >
              <div class="anim-emo">🐄</div>

              <div class="opt-body">
                <div class="opt-name">
                  {{ nombreAnimal(a) }}
                </div>

                <div class="opt-sub">
                  {{ detalleAnimal(a) }}
                </div>

                <div class="opt-finca">
                  🌾 {{ a.finca?.nombre || 'Sin finca' }}
                </div>
              </div>
            </div>

            <div
              v-if="!loadingAnimales && animalesFiltrados.length === 0"
              class="empty-state"
            >
              No hay animales registrados.
            </div>

          </div>

        </ion-content>
      </ion-modal>

      <!-- MODAL CREAR ANIMAL -->
      <ion-modal
        :is-open="mostrarCrearAnimal"
        @didDismiss="cerrarCrearAnimal"
      >
        <ion-content class="vivo-bg">

          <div class="app-bar">
            <button class="back-btn" @click="cerrarCrearAnimal">✕</button>

            <div class="app-bar-center">
              <div class="page-title small-title">Nuevo animal</div>
              <div class="page-sub">Registro rápido para pesaje IA</div>
            </div>

            <div style="width:34px"></div>
          </div>

          <div class="body-pad">

            <div class="form-card">

              <div class="field-group">
                <label class="field-label">Número de arete</label>

                <input
                  v-model="nuevoAnimal.numero_arete"
                  class="field-input"
                  placeholder="Ej: CR005"
                />
              </div>

              <div class="field-group">
                <label class="field-label">Nombre</label>

                <input
                  v-model="nuevoAnimal.nombre"
                  class="field-input"
                  placeholder="Ej: Aurora"
                />
              </div>

              <div class="field-group">
                <label class="field-label">Raza</label>

                <select
                  v-model.number="nuevoAnimal.raza_id"
                  class="field-input"
                >
                  <option :value="1">Brahman</option>
                  <option :value="2">Holstein</option>
                </select>
              </div>

              <div class="field-group">
                <label class="field-label">Fecha de nacimiento</label>

                <input
                  v-model="nuevoAnimal.fecha_nacimiento"
                  class="field-input"
                  type="date"
                />
              </div>

              <div class="field-group">
                <label class="field-label">Finca</label>

                <select
                  v-model.number="nuevoAnimal.finca_id"
                  class="field-input"
                >
                  <option :value="0" disabled>
                    Selecciona una finca
                  </option>

                  <option
                    v-for="finca in fincas"
                    :key="finca.id"
                    :value="finca.id"
                  >
                    {{ finca.nombre }}
                  </option>
                </select>
              </div>

              <div
                v-if="errorCrearAnimal"
                class="feedback feedback-err"
              >
                {{ errorCrearAnimal }}
              </div>

              <div class="form-actions">

                <button
                  class="btn-retry"
                  @click="cerrarCrearAnimal"
                >
                  Cancelar
                </button>

                <button
                  class="btn-save"
                  :disabled="savingAnimal"
                  @click="guardarNuevoAnimal"
                >
                  <span v-if="savingAnimal">Guardando…</span>
                  <span v-else>Guardar animal</span>
                </button>

              </div>

            </div>

          </div>

        </ion-content>
      </ion-modal>

    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import {
  ref,
  computed,
  onMounted
} from 'vue';

import {
  useRouter
} from 'vue-router';

import {
  IonPage,
  IonContent,
  IonModal
} from '@ionic/vue';

import {
  Camera,
  CameraResultType,
  CameraSource
} from '@capacitor/camera';

import {
  AnimalDto,
  FincaDto,
  getAnimales,
  getPerfilCompleto,
  crearAnimalRapido,
  crearPesaje,
  estimarPesoPorImagen
} from '@/services/api';

const router = useRouter();

type Estado =
  | 'idle'
  | 'analizando'
  | 'resultado';

const animales = ref<AnimalDto[]>([]);
const fincas = ref<FincaDto[]>([]);

const animalSel = ref<AnimalDto | null>(null);

const mostrarSelector = ref(false);
const mostrarCrearAnimal = ref(false);

const busqueda = ref('');

const loadingAnimales = ref(true);
const saving = ref(false);
const savingAnimal = ref(false);

const estado = ref<Estado>('idle');

const feedbackMsg = ref('');
const feedbackOk = ref(false);

const errorCrearAnimal = ref('');

const imagenPreview = ref<string>('');
const imagenBlob = ref<Blob | null>(null);

const fileInput = ref<HTMLInputElement | null>(null);

const pesoEstimado = ref(0);
const margen = ref(0);
const confianza = ref(0);
const cc = ref('---');
const alzada = ref(0);

const mensajeAnalisis = ref('Analizando imagen…');
let timerAnalisis: ReturnType<typeof setTimeout> | null = null;

const nuevoAnimal = ref({
  numero_arete: '',
  nombre: '',
  raza_id: 1,
  fecha_nacimiento: new Date().toISOString().slice(0, 10),
  finca_id: 0
});

const animalesFiltrados = computed(() => {
  const q = busqueda.value.trim().toLowerCase();

  if (!q) {
    return animales.value;
  }

  return animales.value.filter((animal) => {
    const nombre =
      animal.nombre?.toLowerCase() ?? '';

    const arete =
      animal.numero_arete?.toLowerCase() ?? '';

    return (
      nombre.includes(q) ||
      arete.includes(q)
    );
  });
});

const puedeAnalizar = computed(() => {
  return Boolean(
    animalSel.value &&
    imagenBlob.value &&
    estado.value !== 'analizando'
  );
});

const textoEstado = computed(() => {
  if (!animalSel.value) {
    return 'Primero selecciona un animal';
  }

  if (!imagenBlob.value) {
    return 'Toma una foto o carga una imagen';
  }

  if (estado.value === 'analizando') {
    return 'Analizando imagen con IA...';
  }

  return 'Toca para estimar el peso';
});

function nombreAnimal(animal: AnimalDto): string {
  return animal.nombre ||
    `Arete ${animal.numero_arete}`;
}

function detalleAnimal(animal: AnimalDto): string {
  return `#${animal.numero_arete} · ${animal.raza?.nombre || 'Sin raza'}`;
}

function nombreRazaPorId(id: number): string {
  if (id === 1) {
    return 'brahman';
  }

  if (id === 2) {
    return 'nelore';
  }

  if (id === 3) {
    return 'angus';
  }

  return 'brahman';
}

async function cargarDatos() {
  loadingAnimales.value = true;

  try {
    const [
      animalesResponse,
      perfilResponse
    ] = await Promise.all([
      getAnimales(),
      getPerfilCompleto()
    ]);

    animales.value =
      animalesResponse.datos || [];

    fincas.value =
      perfilResponse.datos.fincas || [];

    if (
      fincas.value.length > 0 &&
      nuevoAnimal.value.finca_id === 0
    ) {
      nuevoAnimal.value.finca_id =
        fincas.value[0].id;
    }

  } catch (error: any) {
    feedbackMsg.value =
      error?.message ||
      'No se pudieron cargar los datos iniciales.';

    feedbackOk.value = false;

  } finally {
    loadingAnimales.value = false;
  }
}

function elegirAnimal(animal: AnimalDto) {
  animalSel.value = animal;
  mostrarSelector.value = false;
  busqueda.value = '';
}

function cerrarSelector() {
  mostrarSelector.value = false;
}

function abrirCrearAnimal() {
  mostrarCrearAnimal.value = true;
}

function cerrarCrearAnimal() {
  mostrarCrearAnimal.value = false;
  errorCrearAnimal.value = '';
}

async function guardarNuevoAnimal() {
  errorCrearAnimal.value = '';

  if (!nuevoAnimal.value.numero_arete.trim()) {
    errorCrearAnimal.value =
      'Ingresa el número de arete.';
    return;
  }

  if (!nuevoAnimal.value.nombre.trim()) {
    errorCrearAnimal.value =
      'Ingresa el nombre del animal.';
    return;
  }

  if (!nuevoAnimal.value.fecha_nacimiento) {
    errorCrearAnimal.value =
      'Selecciona la fecha de nacimiento.';
    return;
  }

  if (!nuevoAnimal.value.finca_id) {
    errorCrearAnimal.value =
      'Selecciona una finca.';
    return;
  }

  savingAnimal.value = true;

  try {
    const response =
      await crearAnimalRapido({
        numero_arete: nuevoAnimal.value.numero_arete.trim(),
        nombre: nuevoAnimal.value.nombre.trim(),
        raza_id: nuevoAnimal.value.raza_id,
        nombre_raza: nombreRazaPorId(nuevoAnimal.value.raza_id),
        fecha_nacimiento: nuevoAnimal.value.fecha_nacimiento,
        finca_id: nuevoAnimal.value.finca_id
      });

    const animalCreado =
      response.datos;

    animales.value.unshift(animalCreado);

    animalSel.value =
      animalCreado;

    nuevoAnimal.value = {
      numero_arete: '',
      nombre: '',
      raza_id: 1,
      fecha_nacimiento: new Date().toISOString().slice(0, 10),
      finca_id: fincas.value[0]?.id || 0
    };

    mostrarCrearAnimal.value = false;
    mostrarSelector.value = false;

    feedbackMsg.value =
      '✓ Animal creado y seleccionado.';

    feedbackOk.value = true;

  } catch (error: any) {
    errorCrearAnimal.value =
      error?.message ||
      'No se pudo crear el animal.';

  } finally {
    savingAnimal.value = false;
  }
}

async function tomarFoto() {
  await obtenerImagenCapacitor(
    CameraSource.Camera
  );
}

async function cargarGaleria() {
  try {
    await obtenerImagenCapacitor(
      CameraSource.Photos
    );
  } catch {
    fileInput.value?.click();
  }
}

async function obtenerImagenCapacitor(
  source: CameraSource
) {
  try {
    const foto =
      await Camera.getPhoto({
        quality: 85,
        allowEditing: false,
        resultType: CameraResultType.Uri,
        source
      });

    if (!foto.webPath) {
      throw new Error(
        'No se pudo obtener la imagen.'
      );
    }

    imagenPreview.value =
      foto.webPath;

    const response =
      await fetch(foto.webPath);

    imagenBlob.value =
      await response.blob();

    estado.value = 'idle';
    feedbackMsg.value = '';

  } catch (error: any) {
    throw new Error(
      error?.message ||
      'No se pudo cargar la imagen.'
    );
  }
}

function cargarImagenDesdeInput(event: Event) {
  const input =
    event.target as HTMLInputElement;

  const file =
    input.files?.[0];

  if (!file) {
    return;
  }

  imagenBlob.value = file;

  imagenPreview.value =
    URL.createObjectURL(file);

  estado.value = 'idle';
  feedbackMsg.value = '';

  input.value = '';
}

async function analizarImagen() {
  if (!animalSel.value) {
    feedbackMsg.value =
      'Selecciona un animal antes de analizar.';

    feedbackOk.value = false;
    return;
  }

  if (!imagenBlob.value) {
    feedbackMsg.value =
      'Toma una foto o carga una imagen.';

    feedbackOk.value = false;
    return;
  }

  estado.value = 'analizando';
  feedbackMsg.value = '';
  mensajeAnalisis.value = 'Analizando imagen…';

  // Si tarda más de 8 s (posible cold start en Render), avisamos al usuario
  timerAnalisis = setTimeout(() => {
    mensajeAnalisis.value = 'Despertando servicio de IA… (puede tardar ~30 s la primera vez)';
  }, 8000);

  try {
    const response =
      await estimarPesoPorImagen(
        imagenBlob.value
      );

    const datos =
      response.datos;

    pesoEstimado.value =
      Math.round(
        Number(datos.peso_estimado || 0)
      );

    confianza.value =
      Math.round(
        Number(datos.confianza ?? 90)
      );

    margen.value =
      Math.max(
        8,
        Math.round(pesoEstimado.value * 0.05)
      );

    cc.value =
      String(datos.condicion_corporal ?? '---');

    alzada.value =
      Math.round(
        Number(datos.alzada_estimada ?? 0)
      );

    estado.value = 'resultado';

  } catch (error: any) {
    estado.value = 'idle';

    feedbackMsg.value =
      error?.message ||
      'Error al estimar el peso con IA.';

    feedbackOk.value = false;
  } finally {
    if (timerAnalisis) {
      clearTimeout(timerAnalisis);
      timerAnalisis = null;
    }
    mensajeAnalisis.value = 'Analizando imagen…';
  }
}

function reiniciar() {
  estado.value = 'idle';
  feedbackMsg.value = '';
  imagenPreview.value = '';
  imagenBlob.value = null;
  pesoEstimado.value = 0;
  margen.value = 0;
  confianza.value = 0;
  cc.value = '---';
  alzada.value = 0;
}

async function guardar() {
  if (!animalSel.value) {
    feedbackMsg.value =
      'Selecciona un animal.';

    feedbackOk.value = false;
    return;
  }

  if (!pesoEstimado.value) {
    feedbackMsg.value =
      'Primero estima el peso con IA.';

    feedbackOk.value = false;
    return;
  }

  saving.value = true;

  try {
    await crearPesaje({
      animal_id: animalSel.value.id,
      peso_estimado: pesoEstimado.value,
      peso_real: null,
      fecha: new Date().toISOString().slice(0, 10),
    });

    feedbackMsg.value =
      '✓ Pesaje guardado exitosamente.';

    feedbackOk.value = true;

    setTimeout(() => {
      reiniciar();
    }, 1500);

  } catch (error: any) {
    feedbackMsg.value =
      error?.message ||
      'Error al guardar el pesaje.';

    feedbackOk.value = false;

  } finally {
    saving.value = false;
  }
}

onMounted(() => {
  cargarDatos();
});
</script>

<style scoped>
.vivo-bg {
  --background: #F2F5F3;
}

.app-bar {
  background: #ffffff;
  padding: 12px 16px 10px;
  display: flex;
  align-items: center;
  gap: 10px;
  border-bottom: 1px solid #E5E7EB;
}

.back-btn {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  background: #F2F5F3;
  border: none;
  cursor: pointer;
  font-size: 22px;
  color: #374151;
  line-height: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.app-bar-center {
  flex: 1;
}

.page-title {
  font-size: 1.125rem;
  font-weight: 800;
  color: #1A3D28;
}

.small-title {
  font-size: 1.125rem;
}

.page-sub {
  font-size: .6875rem;
  color: #6B7280;
  margin-top: 1px;
}

.ai-badge {
  padding: 4px 10px;
  border-radius: 9999px;
  background: linear-gradient(90deg, #EEF9F2, #D8F3DC);
  border: 1px solid #B7E5CC;
  font-size: .6875rem;
  font-weight: 800;
  color: #1E5631;
  flex-shrink: 0;
}

.body-pad {
  padding: 14px 16px 36px;
}

.animal-card {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #ffffff;
  border-radius: 14px;
  padding: 12px 14px;
  margin-bottom: 14px;
  box-shadow: 0 1px 4px rgba(0,0,0,.07);
  cursor: pointer;
  border: 1.5px solid #E5E7EB;
}

.anim-emo {
  width: 42px;
  height: 42px;
  border-radius: 11px;
  background: #EEF9F2;
  border: 1px solid #D8F3DC;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  flex-shrink: 0;
}

.anim-info,
.opt-body {
  flex: 1;
}

.anim-name,
.opt-name {
  font-size: .875rem;
  font-weight: 700;
  color: #111827;
}

.anim-sub,
.opt-sub {
  font-size: .6875rem;
  color: #6B7280;
  margin-top: 1px;
}

.opt-finca {
  margin-top: 3px;
  font-size: .68rem;
  color: #1E5631;
  font-weight: 700;
}

.chev {
  color: #9CA3AF;
  font-size: 20px;
}

.viewfinder-wrap {
  margin-bottom: 14px;
}

.viewfinder {
  width: 100%;
  aspect-ratio: 4 / 3;
  background: linear-gradient(160deg, #0D2B1A 0%, #1A3D28 60%, #0a1f12 100%);
  border-radius: 20px;
  overflow: hidden;
  position: relative;
  box-shadow: 0 8px 28px rgba(13,43,26,.4);
}

.preview-img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.vf-idle,
.vf-analyzing,
.vf-result-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.vf-idle {
  flex-direction: column;
}

.vf-analyzing {
  flex-direction: column;
  gap: 16px;
  background: rgba(0,0,0,.25);
}

.vf-result-overlay {
  align-items: flex-end;
  padding-bottom: 16px;
  background: rgba(0,0,0,.15);
}

.vf-corners {
  position: absolute;
  inset: 20px;
}

.corner {
  position: absolute;
  width: 22px;
  height: 22px;
  border-color: rgba(255,255,255,.75);
  border-style: solid;
}

.corner.tl {
  top: 0;
  left: 0;
  border-width: 2.5px 0 0 2.5px;
}

.corner.tr {
  top: 0;
  right: 0;
  border-width: 2.5px 2.5px 0 0;
}

.corner.bl {
  bottom: 0;
  left: 0;
  border-width: 0 0 2.5px 2.5px;
}

.corner.br {
  bottom: 0;
  right: 0;
  border-width: 0 2.5px 2.5px 0;
}

.vf-corners-green .corner {
  border-color: #52B788;
}

.vf-hint {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  color: rgba(255,255,255,.7);
  font-size: .8125rem;
  text-align: center;
  padding: 0 20px;
}

.vf-ico {
  font-size: 2rem;
  opacity: .75;
}

.scan-line {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(90deg, transparent, #52B788, transparent);
  animation: scan 1.8s ease-in-out infinite;
}

@keyframes scan {
  0% {
    top: 0%;
    opacity: 0;
  }

  10% {
    opacity: 1;
  }

  90% {
    opacity: 1;
  }

  100% {
    top: 100%;
    opacity: 0;
  }
}

.analyze-msg {
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(0,0,0,.65);
  padding: 10px 16px;
  border-radius: 9999px;
  color: #ffffff;
  font-size: .8125rem;
  font-weight: 600;
}

.spinner,
.shutter-spinner {
  border-radius: 50%;
  border: 3px solid rgba(255,255,255,.25);
  border-top-color: #ffffff;
  animation: spin .7s linear infinite;
  display: block;
}

.spinner {
  width: 16px;
  height: 16px;
}

.shutter-spinner {
  width: 24px;
  height: 24px;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.result-chip {
  background: rgba(30,86,49,.9);
  padding: 6px 14px;
  border-radius: 9999px;
  color: #ffffff;
  font-size: .75rem;
  font-weight: 700;
  border: 1px solid rgba(82,183,136,.4);
}

.model-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: .625rem;
  color: #6B7280;
  margin-top: 6px;
  padding: 0 4px;
}

.model-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #52B788;
  flex-shrink: 0;
  box-shadow: 0 0 5px #52B788;
}

.image-actions {
  display: flex;
  gap: 10px;
  margin-bottom: 12px;
}

.btn-secondary {
  flex: 1;
  border: 1.5px solid #D8F3DC;
  background: #ffffff;
  color: #1E5631;
  font-weight: 800;
  border-radius: 14px;
  padding: 12px;
  cursor: pointer;
}

.btn-secondary:disabled {
  opacity: .6;
}

.hidden-input {
  display: none;
}

.shutter-area {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
}

.shutter-hint {
  font-size: .75rem;
  color: #6B7280;
  font-weight: 600;
}

.shutter-btn {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: linear-gradient(135deg, #1A3D28, #3A9E61);
  border: 4px solid #ffffff;
  box-shadow: 0 4px 20px rgba(26,61,40,.4);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.shutter-disabled {
  opacity: .45;
  cursor: not-allowed;
}

.shutter-inner {
  font-size: 26px;
  line-height: 1;
}

.result-card {
  background: #ffffff;
  border-radius: 18px;
  padding: 16px;
  box-shadow: 0 4px 18px rgba(0,0,0,.09);
  margin-bottom: 14px;
  border: 1.5px solid #D8F3DC;
}

.result-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
}

.result-title {
  font-size: .8125rem;
  font-weight: 700;
  color: #374151;
}

.confidence-badge {
  padding: 3px 10px;
  border-radius: 9999px;
  background: #EEF9F2;
  color: #1E5631;
  font-size: .625rem;
  font-weight: 800;
  border: 1px solid #D8F3DC;
}

.result-weight-row {
  display: flex;
  align-items: baseline;
  gap: 10px;
  margin-bottom: 12px;
}

.result-weight {
  font-size: 2.75rem;
  font-weight: 900;
  color: #1A3D28;
  line-height: 1;
}

.result-unit {
  font-size: 1.125rem;
  font-weight: 600;
  color: #6B7280;
  margin-left: 3px;
}

.result-range {
  font-size: .75rem;
  color: #9CA3AF;
  font-weight: 600;
}

.result-metrics {
  display: flex;
  border-top: 1px solid #E5E7EB;
  padding-top: 10px;
}

.rm-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 0 8px;
}

.rm-item:first-child {
  padding-left: 0;
  border-right: 1px solid #E5E7EB;
}

.rm-item:last-child {
  padding-left: 16px;
}

.rm-lbl {
  font-size: .625rem;
  color: #9CA3AF;
  text-transform: uppercase;
  letter-spacing: .05em;
}

.rm-val {
  font-size: .9375rem;
  font-weight: 800;
  color: #111827;
}

.warning-note {
  margin: 12px 0 0;
  padding: 10px;
  border-radius: 10px;
  background: #FFF7ED;
  color: #9A3412;
  font-size: .72rem;
  line-height: 1.4;
}

.action-area,
.form-actions {
  display: flex;
  gap: 10px;
  margin-bottom: 14px;
}

.btn-retry {
  flex: 1;
  padding: 13px;
  background: #F2F5F3;
  border: 1.5px solid #E5E7EB;
  border-radius: 14px;
  font-size: .8125rem;
  font-weight: 700;
  color: #374151;
  cursor: pointer;
  font-family: inherit;
}

.btn-save {
  flex: 2;
  padding: 13px;
  background: linear-gradient(135deg, #1E5631, #3A9E61);
  color: #ffffff;
  font-size: .9375rem;
  font-weight: 700;
  border: none;
  border-radius: 14px;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(30,86,49,.3);
  font-family: inherit;
}

.btn-save:disabled {
  opacity: .65;
}

.feedback {
  padding: 10px 14px;
  border-radius: 10px;
  font-size: .8125rem;
  font-weight: 600;
  margin-bottom: 12px;
}

.feedback-ok {
  background: #EEF9F2;
  color: #1E5631;
  border: 1px solid #D8F3DC;
}

.feedback-err {
  background: #FEE2E2;
  color: #B91C1C;
  border: 1px solid #FECACA;
}

.info-section {
  margin-top: 4px;
}

.sec-title {
  font-size: .875rem;
  font-weight: 700;
  color: #111827;
  margin-bottom: 10px;
}

.info-card,
.form-card {
  background: #ffffff;
  border-radius: 16px;
  padding: 4px 0;
  box-shadow: 0 1px 3px rgba(0,0,0,.06);
}

.form-card {
  padding: 16px;
}

.info-step {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 16px;
}

.info-step:not(:last-child) {
  border-bottom: 1px solid #F3F4F6;
}

.step-num {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #EEF9F2;
  border: 1.5px solid #D8F3DC;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: .75rem;
  font-weight: 800;
  color: #1E5631;
  flex-shrink: 0;
}

.step-title {
  font-size: .875rem;
  font-weight: 700;
  color: #111827;
}

.step-sub {
  font-size: .6875rem;
  color: #6B7280;
  margin-top: 2px;
}

.status-loading,
.empty-state {
  padding: 16px;
  text-align: center;
  color: #6B7280;
  font-size: .875rem;
}

.search-box {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #ffffff;
  border: 1.5px solid #E5E7EB;
  border-radius: 12px;
  padding: 10px 14px;
  margin-bottom: 12px;
}

.search-input {
  flex: 1;
  border: none;
  background: transparent;
  outline: none;
  font-size: .875rem;
  font-family: inherit;
  color: #111827;
}

.animal-opt {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  background: #ffffff;
  border-radius: 12px;
  margin-bottom: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,.06);
  cursor: pointer;
}

.mini-add {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  border: none;
  background: #1E5631;
  color: white;
  font-size: 22px;
  font-weight: 800;
}

.btn-create-animal {
  width: 100%;
  border: none;
  border-radius: 14px;
  padding: 12px;
  background: #1E5631;
  color: white;
  font-weight: 800;
  margin-bottom: 12px;
}

.field-group {
  margin-bottom: 14px;
}

.field-label {
  display: block;
  margin-bottom: 7px;
  font-size: .8rem;
  font-weight: 800;
  color: #374151;
}

.field-input {
  width: 100%;
  border: 1.5px solid #E5E7EB;
  border-radius: 12px;
  padding: 12px;
  background: #F9FAFB;
  color: #111827;
  font-size: .9rem;
  outline: none;
}

.field-input:focus {
  border-color: #1E5631;
  background: #ffffff;
}

.slide-up-enter-active {
  transition: all .35s cubic-bezier(.22,1,.36,1);
}

.slide-up-enter-from {
  transform: translateY(24px);
  opacity: 0;
}
</style>