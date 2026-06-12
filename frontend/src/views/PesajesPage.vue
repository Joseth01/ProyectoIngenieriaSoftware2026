<template>
  <ion-page>
    <ion-content :fullscreen="true" class="page-bg">

      <div class="app-bar">
        <div>
          <div class="page-title">Registrar peso</div>
          <div class="page-sub">Nueva medición manual</div>
        </div>

        <button class="icon-btn" @click="resetForm">
          Reiniciar
        </button>
      </div>

      <div class="body-pad">

        <div
          class="ia-banner"
          @click="router.push('/tabs/pesaje-vivo')"
        >
          <div class="ia-left">
            <div class="ia-mark">IA</div>

            <div>
              <div class="ia-title">
                Usar IA para estimar peso
              </div>

              <div class="ia-sub">
                Tomar fotografía del animal
              </div>
            </div>
          </div>

          <span class="ia-chev">›</span>
        </div>

        <div class="form-section">
          <div class="form-sec-title">Animal</div>

          <div class="animal-sel" @click="abrirModal">
            <div class="anim-mark">
              {{ animalSel ? inicialAnimal : 'SE' }}
            </div>

            <div class="anim-sel-info">
              <div class="anim-sel-name">
                {{
                  animalSel
                    ? (animalSel.nombre || `Arete ${animalSel.numero_arete}`)
                    : 'Seleccionar o crear animal'
                }}
              </div>

              <div class="anim-sel-id">
                {{
                  animalSel
                    ? `Arete ${animalSel.numero_arete} · ${animalSel.raza?.nombre || 'Sin raza'}`
                    : 'Toca para elegir un animal'
                }}
              </div>
            </div>

            <span class="chev">›</span>
          </div>
        </div>

        <div class="form-section">
          <div class="section-row">
            <div>
              <div class="form-sec-title">Peso registrado</div>
              <div class="form-sec-sub">Debe estar entre 50 kg y 1200 kg</div>
            </div>

            <div class="range-pill">50 a 1200 kg</div>
          </div>

          <div
            class="weight-wrap"
            :class="{ 'input-error': pesoTieneError }"
          >
            <input
              v-model.number="peso"
              type="number"
              class="weight-input"
              placeholder="0"
              min="50"
              max="1200"
              step="0.1"
              inputmode="decimal"
              @input="limpiarMensajes"
            />

            <span class="weight-unit">
              kg
            </span>
          </div>

          <div class="adjust-row">
            <button class="adj" type="button" @click="ajustarPeso(-5)">
              −5
            </button>

            <button class="adj" type="button" @click="ajustarPeso(-1)">
              −1
            </button>

            <button class="adj" type="button" @click="ajustarPeso(1)">
              +1
            </button>

            <button class="adj" type="button" @click="ajustarPeso(5)">
              +5
            </button>
          </div>
        </div>

        <div class="form-section">
          <div class="section-row">
            <div>
              <div class="form-sec-title">Peso real</div>
              <div class="form-sec-sub">Opcional, solo si usaste báscula</div>
            </div>
          </div>

          <div
            class="field-wrap"
            :class="{ 'input-error': pesoRealTieneError }"
          >
            <input
              v-model.number="pesoReal"
              type="number"
              class="field-input"
              placeholder="Ej. 420"
              min="50"
              max="1200"
              step="0.1"
              inputmode="decimal"
              @input="limpiarMensajes"
            />

            <span class="field-suffix">
              kg
            </span>
          </div>
        </div>

        <div class="form-section">
          <div class="form-sec-title">Detalles</div>

          <div class="form-group">
            <label class="form-label">
              Fecha de medición
            </label>

            <div class="date-field-wrap">
              <input
                ref="fechaMedicionInput"
                v-model="fecha"
                type="date"
                class="field-input date-input date-input-real"
                :max="hoy"
                @click="abrirCalendarioMedicion"
                @focus="abrirCalendarioMedicion"
                @input="limpiarMensajes"
              />

              <button
                type="button"
                class="date-picker-btn"
                @click="abrirCalendarioMedicion"
              >
                Seleccionar
              </button>
            </div>
          </div>
        </div>

        <div
          v-if="success"
          class="feedback success"
        >
          Pesaje registrado correctamente.
        </div>

        <div
          v-if="errorMsg"
          class="feedback error"
        >
          {{ errorMsg }}
        </div>

        <button
          class="btn-save"
          :disabled="saving"
          @click="guardar"
        >
          <span v-if="saving">
            Guardando...
          </span>

          <span v-else>
            Guardar pesaje
          </span>
        </button>

        <div class="sec-title">
          Pesajes recientes
        </div>

        <div
          v-if="loadingPesajes"
          class="status-box status-loading"
        >
          Cargando pesajes...
        </div>

        <div
          v-else-if="pesajesValidos.length === 0"
          class="empty-state"
        >
          No hay pesajes válidos registrados.
        </div>

        <div
          v-for="p in pesajesValidos"
          :key="p.id"
          class="pesaje-row"
        >
          <div class="pr-mark">
            {{ inicialPesaje(p) }}
          </div>

          <div class="pr-info">
            <div class="pr-animal">
              {{
                p.animal?.nombre ||
                `Animal ${p.animal_id}`
              }}
            </div>

            <div class="pr-fecha">
              {{ formatFecha(p.fecha) }}
            </div>
          </div>

          <div class="pr-right">
            <div class="pr-peso">
              {{ pesoNumerico(p).toFixed(0) }} kg
            </div>

            <div class="pr-fuente">
              {{ p.fuente?.nombre || 'Sin fuente' }}
            </div>
          </div>
        </div>

      </div>

      <ion-modal :is-open="mostrarSelector" @didDismiss="cerrarModal">
        <ion-content class="page-bg">

          <div class="app-bar">
            <div>
              <div class="page-title modal-title">
                {{ modoModal === 'seleccionar' ? 'Seleccionar animal' : 'Nuevo animal' }}
              </div>
              <div class="page-sub">
                {{ modoModal === 'seleccionar' ? 'Elige el animal para el pesaje' : 'Registra un nuevo animal' }}
              </div>
            </div>

            <button class="icon-btn" @click="cerrarModal">
              Cerrar
            </button>
          </div>

          <div class="modal-tab-row">
            <button
              class="modal-tab"
              :class="{ active: modoModal === 'seleccionar' }"
              @click="modoModal = 'seleccionar'"
            >
              Seleccionar
            </button>

            <button
              class="modal-tab"
              :class="{ active: modoModal === 'crear' }"
              @click="modoModal = 'crear'"
            >
              Nuevo animal
            </button>
          </div>

          <div class="body-pad">

            <template v-if="modoModal === 'seleccionar'">
              <div class="search-box">
                <span class="search-label">Buscar</span>

                <input
                  v-model="busqueda"
                  class="search-input"
                  placeholder="Nombre o arete"
                />
              </div>

              <div
                v-for="a in animalesFiltrados"
                :key="a.id"
                class="animal-sel-opt"
                @click="elegirAnimal(a)"
              >
                <div class="anim-mark">
                  {{ inicialDeTexto(a.nombre || a.numero_arete || 'A') }}
                </div>

                <div class="anim-sel-info">
                  <div class="anim-sel-name">
                    {{ a.nombre || `Arete ${a.numero_arete}` }}
                  </div>

                  <div class="anim-sel-id">
                    Arete {{ a.numero_arete }} · {{ a.raza?.nombre || 'Sin raza' }}
                  </div>
                </div>
              </div>

              <div
                v-if="animalesFiltrados.length === 0"
                class="empty-state"
              >
                No se encontraron animales. Puedes registrar uno nuevo.
              </div>
            </template>

            <template v-else>
              <div class="nf-field">
                <label class="nf-label">
                  Número de arete <span class="req">*</span>
                </label>

                <input
                  v-model.trim="nuevoArete"
                  type="text"
                  class="nf-input"
                  placeholder="Ej. 001-2025"
                />
              </div>

              <div class="nf-field">
                <label class="nf-label">
                  Nombre del animal <span class="req">*</span>
                </label>

                <input
                  v-model.trim="nuevoNombre"
                  type="text"
                  class="nf-input"
                  placeholder="Ej. Canela"
                />
              </div>

              <div class="nf-field">
                <label class="nf-label">
                  Raza <span class="req">*</span>
                </label>

                <select v-model="nuevoRazaId" class="nf-input nf-select">
                  <option value="" disabled>
                    Selecciona una raza
                  </option>

                  <option
                    v-for="r in razas"
                    :key="r.id"
                    :value="r.id"
                  >
                    {{ r.nombre }}
                  </option>
                </select>
              </div>

              <div class="nf-field">
                <label class="nf-label">
                  Fecha de nacimiento <span class="req">*</span>
                </label>

                <div class="date-field-wrap">
                  <input
                    ref="fechaNacimientoInput"
                    v-model="nuevoFechaNacimiento"
                    type="date"
                    class="nf-input date-input-real"
                    :max="hoy"
                    @click="abrirCalendarioNacimiento"
                    @focus="abrirCalendarioNacimiento"
                  />

                  <button
                    type="button"
                    class="date-picker-btn"
                    @click="abrirCalendarioNacimiento"
                  >
                    Seleccionar
                  </button>
                </div>

                <p class="nf-hint">
                  Usa el calendario para evitar errores de formato.
                </p>
              </div>

              <div class="nf-field">
                <label class="nf-label">
                  Finca <span class="req">*</span>
                </label>

                <select
                  v-model="nuevoFincaId"
                  class="nf-input nf-select"
                  :disabled="fincas.length === 1"
                >
                  <option value="" disabled>
                    Selecciona una finca
                  </option>

                  <option
                    v-for="f in fincas"
                    :key="f.id"
                    :value="f.id"
                  >
                    {{ f.nombre }}
                  </option>
                </select>

                <p v-if="fincas.length === 0" class="nf-hint">
                  No tienes fincas registradas.
                  <a @click="cerrarModal(); router.push('/tabs/perfil')" class="nf-link">
                    Crea una primero.
                  </a>
                </p>
              </div>

              <div class="nf-divider">
                <span>Peso de ingreso a la finca</span>
              </div>

              <div class="nf-field">
                <label class="nf-label">
                  Peso al ingresar <span class="nf-optional">(opcional)</span>
                </label>

                <div
                  class="nf-peso-wrap"
                  :class="{ 'input-error': pesoIngresoTieneError }"
                >
                  <input
                    v-model.number="nuevoPesoIngreso"
                    type="number"
                    class="nf-input"
                    placeholder="Ej. 320"
                    min="50"
                    max="1200"
                    step="0.1"
                    inputmode="decimal"
                  />

                  <span class="nf-peso-unit">kg</span>
                </div>

                <div class="nf-hint">
                  Si registras un peso de ingreso, debe estar entre 50 kg y 1200 kg.
                </div>
              </div>

              <div v-if="nuevoPesoIngreso" class="nf-field">
                <label class="nf-label">
                  Fecha de ingreso
                </label>

                <div class="date-field-wrap">
                  <input
                    ref="fechaIngresoInput"
                    v-model="nuevoFechaIngreso"
                    type="date"
                    class="nf-input date-input-real"
                    :max="hoy"
                    @click="abrirCalendarioIngreso"
                    @focus="abrirCalendarioIngreso"
                  />

                  <button
                    type="button"
                    class="date-picker-btn"
                    @click="abrirCalendarioIngreso"
                  >
                    Seleccionar
                  </button>
                </div>
              </div>

              <div
                v-if="errorCrear"
                class="feedback error"
                style="margin-bottom:12px"
              >
                {{ errorCrear }}
              </div>

              <div
                v-if="successCrear"
                class="feedback success"
                style="margin-bottom:12px"
              >
                {{ successCrear }}
              </div>

              <button
                class="btn-save"
                :disabled="guardandoAnimal || fincas.length === 0"
                @click="crearAnimal"
              >
                <span v-if="guardandoAnimal">
                  Creando animal...
                </span>

                <span v-else>
                  Crear y seleccionar
                </span>
              </button>
            </template>

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

import { useRouter } from 'vue-router';
import { IonPage, IonContent, IonModal } from '@ionic/vue';

import {
  getAnimales,
  getPesajes,
  crearPesaje,
  crearAnimal as apiCrearAnimal,
  getRazas,
  getFincasByUsuario,
  pesoNumerico,
  formatFecha,
  AnimalDto,
  PesajeDto,
  RazaDto,
  FincaDto,
} from '@/services/api';

const PESO_MINIMO = 50;
const PESO_MAXIMO = 1200;
const FUENTE_MANUAL = 3;

const router = useRouter();

const animales = ref<AnimalDto[]>([]);
const pesajes = ref<PesajeDto[]>([]);
const razas = ref<RazaDto[]>([]);
const fincas = ref<FincaDto[]>([]);

const animalSel = ref<AnimalDto | null>(null);
const peso = ref<number | null>(null);
const pesoReal = ref<number | null>(null);
const fecha = ref(new Date().toISOString().slice(0, 10));
const saving = ref(false);
const loadingPesajes = ref(true);

const success = ref(false);
const errorMsg = ref('');

const mostrarSelector = ref(false);
const modoModal = ref<'seleccionar' | 'crear'>('seleccionar');
const busqueda = ref('');

const nuevoArete = ref('');
const nuevoNombre = ref('');
const nuevoRazaId = ref<number | ''>('');
const nuevoFechaNacimiento = ref('');
const nuevoFincaId = ref<number | ''>('');
const guardandoAnimal = ref(false);
const errorCrear = ref('');
const successCrear = ref('');
const nuevoPesoIngreso = ref<number | null>(null);
const nuevoFechaIngreso = ref(new Date().toISOString().slice(0, 10));
const hoy = new Date().toISOString().slice(0, 10);

const fechaNacimientoInput = ref<HTMLInputElement | null>(null);
const fechaIngresoInput = ref<HTMLInputElement | null>(null);
const fechaMedicionInput = ref<HTMLInputElement | null>(null);

let userId: number | undefined;

const animalesFiltrados = computed(() => {
  const q = busqueda.value.toLowerCase().trim();

  if (!q) {
    return animales.value;
  }

  return animales.value.filter(a =>
    (a.nombre || '').toLowerCase().includes(q) ||
    (a.numero_arete || '').toLowerCase().includes(q)
  );
});

const pesajesValidos = computed(() => {
  return pesajes.value
    .filter((p) => {
      const valor = pesoNumerico(p);
      return valor >= PESO_MINIMO && valor <= PESO_MAXIMO;
    })
    .slice(0, 10);
});

const inicialAnimal = computed(() => {
  if (!animalSel.value) {
    return 'AN';
  }

  return inicialDeTexto(
    animalSel.value.nombre ||
    animalSel.value.numero_arete ||
    'A'
  );
});

const pesoTieneError = computed(() => {
  if (peso.value === null || peso.value === undefined) {
    return false;
  }

  return !pesoEnRango(Number(peso.value));
});

const pesoRealTieneError = computed(() => {
  if (pesoReal.value === null || pesoReal.value === undefined) {
    return false;
  }

  return !pesoEnRango(Number(pesoReal.value));
});

const pesoIngresoTieneError = computed(() => {
  if (nuevoPesoIngreso.value === null || nuevoPesoIngreso.value === undefined) {
    return false;
  }

  return !pesoEnRango(Number(nuevoPesoIngreso.value));
});

function abrirCalendarioNacimiento() {
  fechaNacimientoInput.value?.showPicker?.();
}

function abrirCalendarioIngreso() {
  fechaIngresoInput.value?.showPicker?.();
}

function abrirCalendarioMedicion() {
  fechaMedicionInput.value?.showPicker?.();
}

function inicialDeTexto(valor: string): string {
  const limpio = valor.trim();

  if (!limpio) {
    return 'AN';
  }

  return limpio
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map(p => p[0])
    .join('')
    .toUpperCase();
}

function inicialPesaje(pesaje: PesajeDto): string {
  return inicialDeTexto(
    pesaje.animal?.nombre ||
    String(pesaje.animal_id || 'A')
  );
}

function limpiarMensajes() {
  errorMsg.value = '';
  success.value = false;
}

function pesoEnRango(valor: number): boolean {
  return Number.isFinite(valor) &&
    valor >= PESO_MINIMO &&
    valor <= PESO_MAXIMO;
}

function validarPesoManual(): string | null {
  if (!animalSel.value) {
    return 'Selecciona un animal antes de guardar el pesaje.';
  }

  if (peso.value === null || peso.value === undefined || Number.isNaN(Number(peso.value))) {
    return 'Ingresa el peso registrado.';
  }

  if (!pesoEnRango(Number(peso.value))) {
    return `El peso registrado debe estar entre ${PESO_MINIMO} kg y ${PESO_MAXIMO} kg.`;
  }

  if (
    pesoReal.value !== null &&
    pesoReal.value !== undefined &&
    !pesoEnRango(Number(pesoReal.value))
  ) {
    return `El peso real debe estar entre ${PESO_MINIMO} kg y ${PESO_MAXIMO} kg.`;
  }

  if (!fecha.value) {
    return 'Selecciona la fecha de medición.';
  }

  if (fecha.value > hoy) {
    return 'La fecha de medición no puede ser futura.';
  }

  return null;
}

function validarPesoIngreso(): string | null {
  if (
    nuevoPesoIngreso.value === null ||
    nuevoPesoIngreso.value === undefined
  ) {
    return null;
  }

  if (!pesoEnRango(Number(nuevoPesoIngreso.value))) {
    return `El peso de ingreso debe estar entre ${PESO_MINIMO} kg y ${PESO_MAXIMO} kg.`;
  }

  if (!nuevoFechaIngreso.value) {
    return 'Selecciona la fecha de ingreso.';
  }

  if (nuevoFechaIngreso.value > hoy) {
    return 'La fecha de ingreso no puede ser futura.';
  }

  return null;
}

function extraerMensajeError(error: unknown, mensajeDefault: string): string {
  if (error instanceof Error && error.message) {
    return error.message;
  }

  return mensajeDefault;
}

const limpiarFormAnimal = () => {
  nuevoArete.value = '';
  nuevoNombre.value = '';
  nuevoRazaId.value = '';
  nuevoFechaNacimiento.value = '';
  nuevoPesoIngreso.value = null;
  nuevoFechaIngreso.value = new Date().toISOString().slice(0, 10);
  errorCrear.value = '';
  successCrear.value = '';

  if (fincas.value.length === 1) {
    nuevoFincaId.value = fincas.value[0].id;
  } else {
    nuevoFincaId.value = '';
  }
};

const abrirModal = async () => {
  modoModal.value = 'seleccionar';
  busqueda.value = '';
  mostrarSelector.value = true;

  try {
    const [aData, fData] = await Promise.all([
      getAnimales(),
      userId
        ? getFincasByUsuario(userId)
        : Promise.resolve({ exito: true, mensaje: '', datos: fincas.value }),
    ]);

    animales.value = aData.datos || [];
    fincas.value = fData.datos || [];

    if (fincas.value.length === 1) {
      nuevoFincaId.value = fincas.value[0].id;
    }
  } catch {
    errorMsg.value = 'No se pudieron cargar los animales o fincas.';
  }
};

const cerrarModal = () => {
  mostrarSelector.value = false;
  limpiarFormAnimal();
};

const elegirAnimal = (a: AnimalDto) => {
  animalSel.value = a;
  mostrarSelector.value = false;
  limpiarMensajes();
};

function resetForm() {
  animalSel.value = null;
  peso.value = null;
  pesoReal.value = null;
  fecha.value = new Date().toISOString().slice(0, 10);
  errorMsg.value = '';
  success.value = false;
}

function ajustarPeso(cambio: number) {
  limpiarMensajes();

  const actual = Number(peso.value || 0);
  const nuevo = actual + cambio;

  if (nuevo <= 0) {
    peso.value = null;
    return;
  }

  if (nuevo > PESO_MAXIMO) {
    peso.value = PESO_MAXIMO;
    return;
  }

  peso.value = Number(nuevo.toFixed(1));
}

const crearAnimal = async () => {
  errorCrear.value = '';
  successCrear.value = '';

  if (!nuevoArete.value.trim()) {
    errorCrear.value = 'El número de arete es obligatorio.';
    return;
  }

  if (!nuevoNombre.value.trim()) {
    errorCrear.value = 'El nombre es obligatorio.';
    return;
  }

  if (!nuevoRazaId.value) {
    errorCrear.value = 'Selecciona una raza.';
    return;
  }

  if (!nuevoFechaNacimiento.value) {
    errorCrear.value = 'La fecha de nacimiento es obligatoria.';
    return;
  }

  if (nuevoFechaNacimiento.value > hoy) {
    errorCrear.value = 'La fecha de nacimiento no puede ser futura.';
    return;
  }

  if (!nuevoFincaId.value) {
    errorCrear.value = 'Selecciona una finca.';
    return;
  }

  const errorPesoIngreso = validarPesoIngreso();

  if (errorPesoIngreso) {
    errorCrear.value = errorPesoIngreso;
    return;
  }

  const razaId = Number(nuevoRazaId.value);
  const fincaId = Number(nuevoFincaId.value);
  const razaSel = razas.value.find(r => Number(r.id) === razaId);

  if (!razaSel) {
    errorCrear.value = 'Raza no válida. Recarga la página e intenta de nuevo.';
    return;
  }

  guardandoAnimal.value = true;

  try {
    const animal = (await apiCrearAnimal({
      numero_arete: nuevoArete.value.trim(),
      nombre: nuevoNombre.value.trim(),
      raza_id: razaId,
      nombre_raza: razaSel.nombre.toLowerCase(),
      fecha_nacimiento: nuevoFechaNacimiento.value,
      finca_id: fincaId,
    } as any)).datos!;

    if (
      nuevoPesoIngreso.value !== null &&
      nuevoPesoIngreso.value !== undefined
    ) {
      await crearPesaje({
        animal_id: animal.id,
        peso_estimado: Number(nuevoPesoIngreso.value),
        peso_real: Number(nuevoPesoIngreso.value),
        fecha: nuevoFechaIngreso.value,
        fuente_id: FUENTE_MANUAL,
      } as any);
    }

    const [aData, pData] = await Promise.all([
      getAnimales(),
      getPesajes(),
    ]);

    animales.value = aData.datos || [];

    pesajes.value = (pData.datos || [])
      .sort((a, b) =>
        new Date(b.fecha).getTime() -
        new Date(a.fecha).getTime()
      );

    successCrear.value = nuevoPesoIngreso.value
      ? `Animal "${animal.nombre}" creado y pesaje de ingreso registrado.`
      : `Animal "${animal.nombre}" creado correctamente.`;

    setTimeout(() => {
      elegirAnimal(animal);
      limpiarFormAnimal();
    }, 900);

  } catch (e: unknown) {
    errorCrear.value = extraerMensajeError(e, 'Error al crear el animal.');
  } finally {
    guardandoAnimal.value = false;
  }
};

async function guardar() {
  limpiarMensajes();

  const errorValidacion = validarPesoManual();

  if (errorValidacion) {
    errorMsg.value = errorValidacion;
    return;
  }

  saving.value = true;

  try {
    await crearPesaje({
      animal_id: animalSel.value!.id,
      peso_estimado: Number(peso.value),
      peso_real: pesoReal.value !== null && pesoReal.value !== undefined
        ? Number(pesoReal.value)
        : undefined,
      fecha: fecha.value,
      fuente_id: FUENTE_MANUAL,
    } as any);

    const r = await getPesajes();

    pesajes.value = (r.datos || [])
      .sort((a, b) =>
        new Date(b.fecha).getTime() -
        new Date(a.fecha).getTime()
      );

    resetForm();
    success.value = true;

  } catch (e: unknown) {
    console.error(e);
    errorMsg.value = extraerMensajeError(e, 'Error al guardar el pesaje.');
  } finally {
    saving.value = false;
  }
}

onMounted(async () => {
  const raw = localStorage.getItem('user');
  userId = raw ? (JSON.parse(raw) as { id?: number }).id : undefined;

  try {
    const [aData, pData, rData] = await Promise.all([
      getAnimales(),
      getPesajes(),
      getRazas(),
    ]);

    animales.value = aData.datos || [];

    pesajes.value = (pData.datos || [])
      .sort((a, b) =>
        new Date(b.fecha).getTime() -
        new Date(a.fecha).getTime()
      );

    razas.value = rData.datos || [];

    if (userId) {
      fincas.value = (await getFincasByUsuario(userId)).datos || [];

      if (fincas.value.length === 1) {
        nuevoFincaId.value = fincas.value[0].id;
      }
    }
  } catch {
    errorMsg.value = 'No se pudieron cargar los datos iniciales.';
  } finally {
    loadingPesajes.value = false;
  }
});
</script>

<style scoped>
.page-bg {
  --background: #F2F5F3;
}

.app-bar {
  background: #fff;
  padding: 16px 18px 14px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #E5E7EB;
}

.page-title {
  font-size: 1.25rem;
  font-weight: 900;
  color: #1A3D28;
  letter-spacing: -.03em;
}

.page-sub {
  font-size: .8125rem;
  color: #6B7280;
  margin-top: 2px;
}

.modal-title {
  font-size: 1.125rem;
}

.icon-btn {
  min-width: 76px;
  height: 38px;
  padding: 0 12px;
  border: 1px solid #E5E7EB;
  border-radius: 999px;
  background: #F2F5F3;
  color: #1A3D28;
  cursor: pointer;
  font-size: .75rem;
  font-weight: 800;
  font-family: inherit;
}

.body-pad {
  padding: 16px;
}

.ia-banner {
  background:
    radial-gradient(circle at top right, rgba(116,198,157,.22), transparent 38%),
    linear-gradient(135deg, #0D2B1A, #2D7A4A);
  padding: 16px;
  border-radius: 18px;
  margin-bottom: 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  color: #fff;
  cursor: pointer;
  box-shadow: 0 6px 18px rgba(30,86,49,.22);
}

.ia-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.ia-mark {
  width: 42px;
  height: 42px;
  border-radius: 14px;
  background: rgba(255,255,255,.16);
  border: 1px solid rgba(255,255,255,.22);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: .75rem;
  font-weight: 900;
}

.ia-title {
  font-size: .9375rem;
  font-weight: 900;
}

.ia-sub {
  font-size: .75rem;
  color: rgba(255,255,255,.72);
  margin-top: 2px;
}

.ia-chev,
.chev {
  color: #9CA3AF;
  font-size: 22px;
}

.ia-chev {
  color: rgba(255,255,255,.75);
}

.form-section {
  background: #fff;
  padding: 16px;
  border-radius: 18px;
  margin-bottom: 14px;
  box-shadow: 0 1px 3px rgba(0,0,0,.06);
}

.section-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 10px;
}

.form-sec-title {
  font-size: .75rem;
  font-weight: 900;
  margin-bottom: 4px;
  color: #374151;
  text-transform: uppercase;
  letter-spacing: .07em;
}

.form-sec-sub {
  font-size: .75rem;
  color: #6B7280;
}

.range-pill {
  background: #EEF9F2;
  color: #1E5631;
  border: 1px solid #D8F3DC;
  border-radius: 999px;
  padding: 5px 10px;
  font-size: .6875rem;
  font-weight: 900;
  white-space: nowrap;
}

.animal-sel,
.animal-sel-opt {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #F9FAFB;
  padding: 13px;
  border-radius: 14px;
  cursor: pointer;
  border: 1.5px solid transparent;
  transition: border-color .15s, box-shadow .15s, transform .15s;
}

.animal-sel:hover,
.animal-sel-opt:hover {
  border-color: #1E5631;
  box-shadow: 0 4px 14px rgba(0,0,0,.08);
  transform: translateY(-1px);
}

.animal-sel-opt {
  margin-bottom: 10px;
  background: #fff;
}

.anim-mark,
.pr-mark {
  width: 42px;
  height: 42px;
  border-radius: 14px;
  background: #EEF9F2;
  color: #1E5631;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: .75rem;
  font-weight: 900;
  flex-shrink: 0;
}

.anim-sel-info {
  flex: 1;
}

.anim-sel-name {
  font-size: .9rem;
  font-weight: 800;
  color: #111827;
}

.anim-sel-id {
  font-size: .75rem;
  color: #6B7280;
  margin-top: 2px;
}

.weight-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  margin-bottom: 12px;
}

.weight-input {
  width: 100%;
  text-align: center;
  font-size: 2.6rem;
  font-weight: 900;
  color: #1E5631;
  border: 2px solid #D8F3DC;
  background: #EEF9F2;
  border-radius: 16px;
  padding: 18px 48px 18px 20px;
  outline: none;
  font-family: inherit;
  transition: border-color .15s, background .15s;
}

.weight-input:focus {
  border-color: #2D7A4A;
  background: #fff;
}

.weight-unit {
  position: absolute;
  right: 16px;
  font-size: 1rem;
  font-weight: 800;
  color: #2D7A4A;
}

.adjust-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
}

.adj {
  padding: 9px;
  border-radius: 10px;
  border: 1.5px solid #D8F3DC;
  background: #EEF9F2;
  color: #1E5631;
  font-size: .75rem;
  font-weight: 900;
  cursor: pointer;
  font-family: inherit;
  transition: background .15s, transform .15s;
}

.adj:hover {
  background: #D8F3DC;
  transform: translateY(-1px);
}

.field-wrap {
  display: flex;
  align-items: center;
  position: relative;
}

.field-input,
.search-input {
  width: 100%;
  padding: 12px 44px 12px 14px;
  border: 1.5px solid #E5E7EB;
  border-radius: 14px;
  font-size: .875rem;
  color: #111827;
  background: #F2F5F3;
  outline: none;
  font-family: inherit;
  transition: border-color .15s, background .15s;
  box-sizing: border-box;
}

.field-input:focus,
.search-input:focus {
  border-color: #1E5631;
  background: #fff;
}

.date-input {
  padding-right: 14px;
}

.field-suffix {
  position: absolute;
  right: 14px;
  font-size: .8125rem;
  font-weight: 800;
  color: #6B7280;
}

.input-error input,
.input-error .field-input,
.input-error .nf-input,
.input-error .weight-input {
  border-color: #EF4444;
  background: #FEF2F2;
}

.form-group {
  margin-bottom: 0;
}

.form-label {
  display: block;
  font-size: .8125rem;
  font-weight: 800;
  color: #374151;
  margin-bottom: 6px;
}

.feedback {
  padding: 12px 14px;
  border-radius: 14px;
  margin-bottom: 12px;
  font-size: .875rem;
  font-weight: 700;
}

.success {
  background: #DCFCE7;
  color: #166534;
}

.error {
  background: #FEE2E2;
  color: #991B1B;
}

.btn-save {
  width: 100%;
  padding: 15px;
  background: linear-gradient(135deg, #1A3D28, #2D7A4A);
  color: #fff;
  font-size: .9375rem;
  font-weight: 900;
  border: none;
  border-radius: 16px;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(30,86,49,.35);
  font-family: inherit;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: opacity .2s, transform .15s;
  margin-bottom: 16px;
}

.btn-save:not(:disabled):hover {
  transform: translateY(-1px);
}

.btn-save:disabled {
  opacity: .65;
  cursor: not-allowed;
}

.sec-title {
  font-size: .9rem;
  font-weight: 900;
  color: #111827;
  margin: 4px 0 10px;
}

.status-box {
  padding: 12px;
  border-radius: 12px;
  font-size: .875rem;
  margin-bottom: 10px;
}

.status-loading {
  background: #fff;
  color: #374151;
}

.empty-state {
  text-align: center;
  color: #6B7280;
  padding: 20px 10px;
  font-size: .875rem;
  background: #fff;
  border-radius: 16px;
}

.pesaje-row {
  background: #fff;
  border-radius: 16px;
  padding: 14px;
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,.05);
}

.pr-info {
  flex: 1;
}

.pr-animal {
  font-weight: 800;
  color: #111827;
  font-size: .9rem;
}

.pr-fecha {
  font-size: .75rem;
  color: #6B7280;
  margin-top: 2px;
}

.pr-right {
  text-align: right;
}

.pr-peso {
  font-weight: 900;
  color: #166534;
  font-size: .95rem;
}

.pr-fuente {
  font-size: .6875rem;
  color: #9CA3AF;
  margin-top: 2px;
}

.modal-tab-row {
  display: flex;
  background: #F2F5F3;
  border-bottom: 1px solid #E5E7EB;
}

.modal-tab {
  flex: 1;
  padding: 13px 8px;
  border: none;
  background: transparent;
  font-size: .8125rem;
  font-weight: 800;
  color: #6B7280;
  cursor: pointer;
  font-family: inherit;
  border-bottom: 2px solid transparent;
  transition: color .15s, border-color .15s, background .15s;
}

.modal-tab.active {
  color: #1E5631;
  border-bottom-color: #1E5631;
  background: #fff;
}

.search-box {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #fff;
  border: 1.5px solid #E5E7EB;
  border-radius: 14px;
  padding: 10px 14px;
  margin-bottom: 12px;
}

.search-label {
  font-size: .75rem;
  font-weight: 900;
  color: #1E5631;
  text-transform: uppercase;
  letter-spacing: .07em;
}

.search-input {
  padding: 4px 0;
  border: none;
  background: transparent;
  border-radius: 0;
}

.nf-field {
  margin-bottom: 14px;
}

.nf-label {
  display: block;
  font-size: .8125rem;
  font-weight: 800;
  color: #374151;
  margin-bottom: 6px;
}

.req {
  color: #EF4444;
}

.nf-input {
  width: 100%;
  padding: 12px 14px;
  border: 1.5px solid #E5E7EB;
  border-radius: 14px;
  font-size: .875rem;
  color: #111827;
  background: #F9FAFB;
  outline: none;
  font-family: inherit;
  transition: border-color .15s, background .15s;
  box-sizing: border-box;
}

.nf-input:focus {
  border-color: #1E5631;
  background: #fff;
}

.nf-select {
  appearance: none;
  cursor: pointer;
}

.nf-hint {
  font-size: .75rem;
  color: #6B7280;
  margin-top: 6px;
}

.nf-link {
  color: #1E5631;
  font-weight: 800;
  cursor: pointer;
  text-decoration: underline;
}

.nf-optional {
  font-weight: 400;
  color: #9CA3AF;
  font-size: .75rem;
}

.nf-divider {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 6px 0 14px;
  color: #9CA3AF;
  font-size: .6875rem;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: .07em;
}

.nf-divider::before,
.nf-divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: #E5E7EB;
}

.nf-peso-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.nf-peso-wrap .nf-input {
  padding-right: 42px;
}

.nf-peso-unit {
  position: absolute;
  right: 14px;
  font-size: .875rem;
  font-weight: 800;
  color: #6B7280;
  pointer-events: none;
}

.date-field-wrap {
  display: flex;
  gap: 8px;
  align-items: center;
}

.date-input-real {
  flex: 1;
}

.date-picker-btn {
  height: 42px;
  padding: 0 14px;
  border: none;
  border-radius: 12px;
  background: #1E5631;
  color: #fff;
  font-size: .75rem;
  font-weight: 900;
  font-family: inherit;
  cursor: pointer;
  white-space: nowrap;
}

.date-picker-btn:hover {
  opacity: .94;
}
</style>