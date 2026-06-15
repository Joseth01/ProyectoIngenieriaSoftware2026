<template>
  <ion-page>
    <ion-content :fullscreen="true" class="page-bg">

      <div class="header">
        <div>
          <h1 class="title">Mis animales</h1>
          <p class="subtitle">
            {{ animalesFiltrados.length }} registrados
          </p>
        </div>

        <button class="reload-btn" @click="abrirCrearAnimal">
          Agregar animal
        </button>
      </div>

      <div class="container">

        <div class="search-box">
          <input
            v-model="busqueda"
            type="text"
            placeholder="Buscar por nombre o arete..."
            class="search-input"
          />
        </div>

        <div class="filters">
          <button
            class="filter-btn"
            :class="{ active: filtro === 'todos' }"
            @click="filtro = 'todos'"
          >
            Todos
          </button>

          <button
            class="filter-btn"
            :class="{ active: filtro === 'activo' }"
            @click="filtro = 'activo'"
          >
            Activos
          </button>

          <button
            class="filter-btn"
            :class="{ active: filtro === 'inactivo' }"
            @click="filtro = 'inactivo'"
          >
            Inactivos
          </button>
        </div>

        <div v-if="loading" class="status loading">
          Cargando animales...
        </div>

        <div v-else-if="error" class="status error">
          {{ error }}
        </div>

        <div v-else-if="animalesFiltrados.length === 0" class="status empty">
          No hay animales registrados.
        </div>

        <div
          v-else
          v-for="animal in animalesFiltrados"
          :key="animal.id"
          class="animal-card"
        >
          <div class="card-top">
            <div class="avatar">
              {{ inicialAnimal(animal) }}
            </div>

            <div class="info">
              <div class="animal-name">
                {{ animal.nombre || 'Sin nombre' }}
              </div>

              <div class="animal-meta">
                Arete: {{ animal.numero_arete || 'No registrado' }}
              </div>

              <div class="animal-meta">
                {{ animal.raza?.nombre || 'Sin raza' }} · {{ calcularEdad(animal.fecha_nacimiento) }}
              </div>

              <div class="animal-meta">
                Finca: {{ obtenerNombreFinca(animal) }}
              </div>
            </div>

            <div class="card-right">
              <span class="tag" :class="estadoTag(animal).cls">
                {{ estadoTag(animal).txt }}
              </span>

              <div
                class="ultimo-peso"
                :class="{ 'peso-alerta': ultimoPesoEsInvalido(animal) }"
              >
                {{ ultimoPeso(animal) }}
              </div>

              <div class="ultimo-label">
                Último peso
              </div>
            </div>
          </div>

          <div class="card-actions">
            <button class="action-btn secondary" @click="abrirHistorial(animal)">
              Ver historial
            </button>

            <button class="action-btn primary" @click="abrirEditorAnimal(animal)">
              Editar animal
            </button>

            <button class="action-btn warning" @click="abrirSolicitudVeterinaria(animal)">
              Solicitar revisión
            </button>
          </div>
        </div>

      </div>

      <ion-modal :is-open="modalAbierto" @didDismiss="cerrarModal">
        <ion-content class="modal-content">

          <div class="modal-header">
            <div>
              <h2 class="modal-title">
                {{ animalSel?.nombre || 'Animal' }}
              </h2>

              <div class="modal-sub">
                Arete {{ animalSel?.numero_arete || 'No registrado' }}
              </div>
            </div>

            <button class="close-btn" @click="cerrarModal">
              ×
            </button>
          </div>

          <div class="modal-tabs">
            <button
              class="modal-tab"
              :class="{ active: modalModo === 'historial' }"
              @click="cambiarModoModal('historial')"
            >
              Historial
            </button>

            <button
              class="modal-tab"
              :class="{ active: modalModo === 'editar' }"
              @click="cambiarModoModal('editar')"
            >
              Editar animal
            </button>
          </div>

          <div class="modal-body">

            <template v-if="modalModo === 'historial'">
              <div class="sec-title">
                Historial de pesajes
              </div>

              <div v-if="loadingHistorial" class="status-box status-loading">
                Cargando historial...
              </div>

              <div v-else-if="historialCalculado.length === 0" class="empty-state">
                Sin pesajes registrados.
              </div>

              <div v-else>

                <div v-if="graficaPeso.tieneGrafica" class="chart-card">
                  <div class="chart-head">
                    <span class="chart-title">Evolución de peso</span>
                    <span
                      class="chart-trend"
                      :class="graficaPeso.cambioTotal >= 0 ? 'trend-up' : 'trend-dn'"
                    >
                      {{ graficaPeso.cambioTotal >= 0 ? '▲ +' : '▼ ' }}{{ graficaPeso.cambioTotal }} kg
                    </span>
                  </div>

                  <svg
                    class="chart-svg"
                    :viewBox="`0 0 ${graficaPeso.W} ${graficaPeso.H}`"
                    preserveAspectRatio="none"
                    role="img"
                    aria-label="Gráfica de evolución de peso del animal"
                  >
                    <defs>
                      <linearGradient id="pesoFill" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#3A9E61" stop-opacity="0.28" />
                        <stop offset="100%" stop-color="#3A9E61" stop-opacity="0" />
                      </linearGradient>
                    </defs>

                    <polygon :points="graficaPeso.area" fill="url(#pesoFill)" />
                    <polyline
                      :points="graficaPeso.linea"
                      fill="none"
                      stroke="#1E5631"
                      stroke-width="2.5"
                      stroke-linejoin="round"
                      stroke-linecap="round"
                    />
                    <circle
                      v-for="(c, i) in graficaPeso.coords"
                      :key="i"
                      :cx="c.x"
                      :cy="c.y"
                      :r="i === graficaPeso.coords.length - 1 ? 4 : 2.5"
                      :fill="i === graficaPeso.coords.length - 1 ? '#1E5631' : '#3A9E61'"
                    />
                  </svg>

                  <div class="chart-foot">
                    <span>{{ graficaPeso.primera }}</span>
                    <span class="chart-range">{{ graficaPeso.min }}–{{ graficaPeso.max }} kg</span>
                    <span>{{ graficaPeso.ultima }}</span>
                  </div>
                </div>

                <div class="hist-list">
                  <div
                    v-for="item in historialCalculado"
                    :key="item.pesaje.id"
                    class="hist-row-wrap"
                  >
                    <div
                      class="hist-row"
                      :class="{ 'hist-row-invalid': item.fueraRango }"
                    >
                      <div
                        class="hist-dot"
                        :class="{ 'hist-dot-first': item.esUltimo }"
                      ></div>

                      <div class="hist-main">
                        <div class="hist-date">
                          {{ formatFecha(item.pesaje.fecha) }} · {{ item.pesaje.fuente?.nombre || 'Sin fuente' }}
                        </div>

                        <div
                          v-if="item.fueraRango"
                          class="hist-warning"
                        >
                          Peso fuera de rango. Revisa este registro.
                        </div>
                      </div>

                      <div class="hist-values">
                        <div class="hist-w">
                          {{ item.peso.toFixed(0) }} kg
                        </div>

                        <div
                          v-if="item.diferencia !== null"
                          class="hist-d"
                          :class="item.diferencia >= 0 ? 'diff-up' : 'diff-dn'"
                        >
                          {{ item.diferencia >= 0 ? '+' : '' }}{{ item.diferencia.toFixed(0) }} kg
                        </div>

                        <div v-else class="hist-d hist-d-empty">
                          Inicial
                        </div>
                      </div>

                      <button
                        v-if="item.esUltimo"
                        class="edit-mini-btn"
                        @click.stop="activarEdicionPesaje(item.pesaje)"
                      >
                        Editar
                      </button>
                    </div>

                    <div
                      v-if="editandoPesajeId === item.pesaje.id"
                      class="edit-box"
                    >
                      <div class="edit-title">
                        Editar último pesaje
                      </div>

                      <div class="edit-field">
                        <label>Peso registrado</label>

                        <div class="input-with-unit" :class="{ 'field-error': pesoEditadoFueraRango }">
                          <input
                            v-model.number="editPesajeForm.peso_estimado"
                            type="number"
                            min="50"
                            max="1200"
                            step="0.1"
                            placeholder="Ej. 420"
                          />

                          <span>kg</span>
                        </div>
                      </div>

                      <div class="edit-field">
                        <label>Peso real opcional</label>

                        <div class="input-with-unit" :class="{ 'field-error': pesoRealEditadoFueraRango }">
                          <input
                            v-model.number="editPesajeForm.peso_real"
                            type="number"
                            min="50"
                            max="1200"
                            step="0.1"
                            placeholder="Opcional"
                          />

                          <span>kg</span>
                        </div>

                        <div class="field-hint">
                          Si este campo tiene valor, será el peso principal mostrado.
                        </div>
                      </div>

                      <div class="edit-field">
                        <label>Fecha</label>

                        <div class="date-field-wrap">
                          <input
                            ref="fechaPesajeInput"
                            v-model="editPesajeForm.fecha"
                            type="date"
                            class="edit-date date-input-real"
                            :max="hoy"
                            @click="abrirCalendarioPesaje"
                            @focus="abrirCalendarioPesaje"
                          />

                          <button
                            type="button"
                            class="date-picker-btn"
                            @click="abrirCalendarioPesaje"
                          >
                            Seleccionar
                          </button>
                        </div>
                      </div>

                      <div v-if="editPesajeError" class="feedback error">
                        {{ editPesajeError }}
                      </div>

                      <div class="edit-actions">
                        <button
                          class="btn-outline"
                          :disabled="guardandoPesaje"
                          @click="cancelarEdicionPesaje"
                        >
                          Cancelar
                        </button>

                        <button
                          class="btn-primary small"
                          :disabled="guardandoPesaje"
                          @click="guardarEdicionPesaje"
                        >
                          {{ guardandoPesaje ? 'Guardando...' : 'Guardar cambio' }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <button class="btn-primary" @click="registrarPeso">
                Registrar nuevo pesaje
              </button>
            </template>

            <template v-else>
              <div class="sec-title">
                Datos del animal
              </div>

              <div class="edit-box animal-edit-box">
                <div class="edit-field">
                  <label>Número de arete</label>

                  <input
                    v-model.trim="editAnimalForm.numero_arete"
                    type="text"
                    class="edit-date"
                    placeholder="Número de arete"
                  />
                </div>

                <div class="edit-field">
                  <label>Nombre del animal</label>

                  <input
                    v-model.trim="editAnimalForm.nombre"
                    type="text"
                    class="edit-date"
                    placeholder="Nombre"
                  />
                </div>

                <div class="edit-field">
                  <label>Raza</label>

                  <select
                    v-model="editAnimalForm.raza_id"
                    class="edit-date"
                  >
                    <option value="" disabled>
                      Selecciona una raza
                    </option>

                    <option
                      v-for="raza in razas"
                      :key="raza.id"
                      :value="raza.id"
                    >
                      {{ raza.nombre }}
                    </option>
                  </select>
                </div>

                <div class="edit-field">
                  <label>Finca</label>

                  <select
                    v-model="editAnimalForm.finca_id"
                    class="edit-date"
                  >
                    <option value="" disabled>
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

                <div class="edit-field">
                  <label>Fecha de nacimiento</label>

                  <div class="date-field-wrap">
                    <input
                      ref="fechaNacimientoInput"
                      v-model="editAnimalForm.fecha_nacimiento"
                      type="date"
                      class="edit-date date-input-real"
                      :max="hoy"
                      @click="abrirCalendarioFechaNacimiento"
                      @focus="abrirCalendarioFechaNacimiento"
                    />

                    <button
                      type="button"
                      class="date-picker-btn"
                      @click="abrirCalendarioFechaNacimiento"
                    >
                      Seleccionar
                    </button>
                  </div>

                  <div class="field-hint">
                    Selecciona la fecha desde el calendario para evitar formatos incorrectos.
                  </div>
                </div>

                <div class="edit-field">
                  <label>Estado</label>

                  <select
                    v-model="editAnimalForm.estado"
                    class="edit-date"
                  >
                    <option value="activo">
                      Activo
                    </option>

                    <option value="inactivo">
                      Inactivo
                    </option>
                  </select>

                  <div class="field-hint">
                    Usa inactivo si el animal fue vendido, murió o ya no pertenece a la finca.
                  </div>
                </div>

                <div v-if="editAnimalError" class="feedback error">
                  {{ editAnimalError }}
                </div>

                <div v-if="editAnimalSuccess" class="feedback success">
                  {{ editAnimalSuccess }}
                </div>

                <div class="edit-actions">
                  <button
                    class="btn-outline"
                    :disabled="guardandoAnimal"
                    @click="cerrarModal"
                  >
                    Cancelar
                  </button>

                  <button
                    class="btn-primary small"
                    :disabled="guardandoAnimal"
                    @click="guardarEdicionAnimal"
                  >
                    {{ guardandoAnimal ? 'Guardando...' : 'Guardar animal' }}
                  </button>
                </div>
              </div>
            </template>

          </div>

        </ion-content>
      </ion-modal>

      <ion-modal :is-open="modalCrearAbierto" @didDismiss="cerrarCrearAnimal">
        <ion-content class="modal-content">

          <div class="modal-header">
            <div>
              <h2 class="modal-title">
                Nuevo animal
              </h2>

              <div class="modal-sub">
                Registro sin peso inicial
              </div>
            </div>

            <button class="close-btn" @click="cerrarCrearAnimal">
              ×
            </button>
          </div>

          <div class="modal-body">
            <div class="edit-box animal-edit-box">
              <div class="sec-title">
                Datos básicos
              </div>

              <div class="edit-field">
                <label>Número de arete</label>

                <input
                  v-model.trim="nuevoAnimalForm.numero_arete"
                  type="text"
                  class="edit-date"
                  placeholder="Ej. CR-001"
                />
              </div>

              <div class="edit-field">
                <label>Nombre del animal</label>

                <input
                  v-model.trim="nuevoAnimalForm.nombre"
                  type="text"
                  class="edit-date"
                  placeholder="Ej. Canela"
                />
              </div>

              <div class="edit-field">
                <label>Raza</label>

                <select
                  v-model="nuevoAnimalForm.raza_id"
                  class="edit-date"
                >
                  <option value="" disabled>
                    Selecciona una raza
                  </option>

                  <option
                    v-for="raza in razas"
                    :key="raza.id"
                    :value="raza.id"
                  >
                    {{ raza.nombre }}
                  </option>
                </select>
              </div>

              <div class="edit-field">
                <label>Finca</label>

                <select
                  v-model="nuevoAnimalForm.finca_id"
                  class="edit-date"
                >
                  <option value="" disabled>
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

                <div v-if="fincas.length === 0" class="field-hint">
                  No tienes fincas registradas. Crea una finca desde el perfil antes de agregar animales.
                </div>
              </div>

              <div class="edit-field">
                <label>Fecha de nacimiento</label>

                <div class="date-field-wrap">
                  <input
                    ref="fechaNacimientoNuevoInput"
                    v-model="nuevoAnimalForm.fecha_nacimiento"
                    type="date"
                    class="edit-date date-input-real"
                    :max="hoy"
                    @click="abrirCalendarioNuevoNacimiento"
                    @focus="abrirCalendarioNuevoNacimiento"
                  />

                  <button
                    type="button"
                    class="date-picker-btn"
                    @click="abrirCalendarioNuevoNacimiento"
                  >
                    Seleccionar
                  </button>
                </div>

                <div class="field-hint">
                  El animal se registrará sin pesaje inicial.
                </div>
              </div>

              <div v-if="crearAnimalError" class="feedback error">
                {{ crearAnimalError }}
              </div>

              <div v-if="crearAnimalSuccess" class="feedback success">
                {{ crearAnimalSuccess }}
              </div>

              <div class="edit-actions">
                <button
                  class="btn-outline"
                  :disabled="guardandoNuevoAnimal"
                  @click="cerrarCrearAnimal"
                >
                  Cancelar
                </button>

                <button
                  class="btn-primary small"
                  :disabled="guardandoNuevoAnimal || fincas.length === 0"
                  @click="guardarNuevoAnimal"
                >
                  {{ guardandoNuevoAnimal ? 'Guardando...' : 'Agregar animal' }}
                </button>
              </div>
            </div>
          </div>

        </ion-content>
      </ion-modal>

      <ion-modal :is-open="modalSolicitudAbierto" @didDismiss="cerrarSolicitudVeterinaria">
        <ion-content class="modal-content">

          <div class="modal-header">
            <div>
              <h2 class="modal-title">
                Solicitar revisión
              </h2>

              <div class="modal-sub">
                {{ animalSel?.nombre || 'Animal' }} · Arete {{ animalSel?.numero_arete || 'No registrado' }}
              </div>
            </div>

            <button class="close-btn" @click="cerrarSolicitudVeterinaria">
              ×
            </button>
          </div>

          <div class="modal-body">
            <div class="edit-box animal-edit-box">
              <div class="sec-title">
                Solicitud veterinaria
              </div>

              <div class="request-animal-card" v-if="animalSel">
                <div class="request-avatar">
                  {{ inicialAnimal(animalSel) }}
                </div>

                <div>
                  <strong>{{ animalSel.nombre || 'Sin nombre' }}</strong>
                  <p>
                    Finca: {{ obtenerNombreFinca(animalSel) }}
                  </p>
                  <p>
                    Último peso: {{ ultimoPeso(animalSel) }}
                  </p>
                </div>
              </div>

              <div class="edit-field">
                <label>Veterinario</label>

                <select
                  v-model="solicitudForm.veterinario_id"
                  class="edit-date"
                  :disabled="cargandoVeterinarios || enviandoSolicitud"
                >
                  <option value="" disabled>
                    Selecciona un veterinario
                  </option>

                  <option
                    v-for="veterinario in veterinariosDisponibles"
                    :key="veterinario.id"
                    :value="veterinario.id"
                  >
                    {{ veterinario.name }} · {{ veterinario.email }}
                  </option>
                </select>

                <div v-if="cargandoVeterinarios" class="field-hint">
                  Cargando veterinarios disponibles...
                </div>

                <div v-else-if="veterinariosDisponibles.length === 0" class="field-hint">
                  No hay veterinarios activos disponibles.
                </div>
              </div>

              <div class="edit-field">
                <label>Motivo de la solicitud</label>

                <textarea
                  v-model.trim="solicitudForm.motivo"
                  class="edit-textarea"
                  rows="5"
                  placeholder="Ej. El animal bajó de peso y necesito una revisión."
                  :disabled="enviandoSolicitud"
                ></textarea>

                <div class="field-hint">
                  Explica brevemente por qué necesitas la revisión.
                </div>
              </div>

              <div v-if="solicitudError" class="feedback error">
                {{ solicitudError }}
              </div>

              <div v-if="solicitudSuccess" class="feedback success">
                {{ solicitudSuccess }}
              </div>

              <div class="edit-actions">
                <button
                  class="btn-outline"
                  :disabled="enviandoSolicitud"
                  @click="cerrarSolicitudVeterinaria"
                >
                  Cancelar
                </button>

                <button
                  class="btn-primary small"
                  :disabled="enviandoSolicitud || cargandoVeterinarios || veterinariosDisponibles.length === 0"
                  @click="enviarSolicitudVeterinaria"
                >
                  {{ enviandoSolicitud ? 'Enviando...' : 'Enviar solicitud' }}
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
  onMounted,
} from 'vue';

import { useRouter } from 'vue-router';

import {
  IonPage,
  IonContent,
  IonModal,
  onIonViewWillEnter,
} from '@ionic/vue';

import {
  getAnimales,
  getHistorialAnimal,
  getRazas,
  getFincasByUsuario,
  getVeterinariosDisponibles,
  crearSolicitudVeterinaria,
  pesoNumerico,
  formatFecha,
  type AnimalDto,
  type PesajeDto,
  type RazaDto,
  type FincaDto,
  type UsuarioDto,
} from '@/services/api';

const PESO_MINIMO = 50;
const PESO_MAXIMO = 1200;
const FUENTE_MANUAL = 3;

const API_URL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api';

const router = useRouter();

const animales = ref<AnimalDto[]>([]);
const razas = ref<RazaDto[]>([]);
const fincas = ref<FincaDto[]>([]);

const veterinariosDisponibles = ref<UsuarioDto[]>([]);

const loading = ref(true);
const error = ref('');
const busqueda = ref('');
const filtro = ref('todos');

const animalSel = ref<AnimalDto | null>(null);
const modalAbierto = ref(false);
const modalCrearAbierto = ref(false);
const modalSolicitudAbierto = ref(false);
const modalModo = ref<'historial' | 'editar'>('historial');

const historialAnimal = ref<PesajeDto[]>([]);
const historialesPorAnimal = ref<Record<number, PesajeDto[]>>({});
const loadingHistorial = ref(false);

const editandoPesajeId = ref<number | null>(null);
const guardandoPesaje = ref(false);
const editPesajeError = ref('');

const guardandoAnimal = ref(false);
const editAnimalError = ref('');
const editAnimalSuccess = ref('');

const guardandoNuevoAnimal = ref(false);
const crearAnimalError = ref('');
const crearAnimalSuccess = ref('');

const cargandoVeterinarios = ref(false);
const enviandoSolicitud = ref(false);
const solicitudError = ref('');
const solicitudSuccess = ref('');

const hoy = new Date().toISOString().slice(0, 10);

const fechaPesajeInput = ref<HTMLInputElement | null>(null);
const fechaNacimientoInput = ref<HTMLInputElement | null>(null);
const fechaNacimientoNuevoInput = ref<HTMLInputElement | null>(null);

const editPesajeForm = ref<{
  peso_estimado: number | null;
  peso_real: number | null;
  fecha: string;
  fuente_id: number;
}>({
  peso_estimado: null,
  peso_real: null,
  fecha: hoy,
  fuente_id: FUENTE_MANUAL,
});

const editAnimalForm = ref<{
  numero_arete: string;
  nombre: string;
  raza_id: number | '';
  finca_id: number | '';
  fecha_nacimiento: string;
  estado: 'activo' | 'inactivo';
}>({
  numero_arete: '',
  nombre: '',
  raza_id: '',
  finca_id: '',
  fecha_nacimiento: hoy,
  estado: 'activo',
});

const nuevoAnimalForm = ref<{
  numero_arete: string;
  nombre: string;
  raza_id: number | '';
  finca_id: number | '';
  fecha_nacimiento: string;
}>({
  numero_arete: '',
  nombre: '',
  raza_id: '',
  finca_id: '',
  fecha_nacimiento: hoy,
});

const solicitudForm = ref<{
  veterinario_id: number | '';
  motivo: string;
}>({
  veterinario_id: '',
  motivo: '',
});

let userId: number | undefined;

const animalesFiltrados = computed(() =>
  animales.value.filter(animal => {
    const texto = `${animal.nombre || ''} ${animal.numero_arete || ''}`
      .toLowerCase()
      .includes(busqueda.value.toLowerCase());

    const estado =
      filtro.value === 'todos' || animal.estado === filtro.value;

    return texto && estado;
  })
);

const historialCalculado = computed(() => {
  const ascendente = [...historialAnimal.value]
    .sort((a, b) => ordenarPesajesAsc(a, b));

  const calculado = ascendente.map((pesaje, index) => {
    const peso = pesoNumerico(pesaje);
    const anterior = ascendente[index - 1];
    const pesoAnterior = anterior ? pesoNumerico(anterior) : null;

    const pesoActualValido = pesoEnRango(peso);
    const pesoAnteriorValido = pesoAnterior !== null && pesoEnRango(pesoAnterior);

    const diferencia =
      index === 0 || !pesoActualValido || !pesoAnteriorValido
        ? null
        : peso - Number(pesoAnterior);

    return {
      pesaje,
      peso,
      diferencia,
      fueraRango: !pesoActualValido,
      esUltimo: false,
    };
  });

  const descendente = calculado.reverse();

  if (descendente.length > 0) {
    descendente[0].esUltimo = true;
  }

  return descendente;
});

const graficaPeso = computed(() => {
  const serie = [...historialAnimal.value]
    .sort((a, b) => ordenarPesajesAsc(a, b))
    .map(p => ({ peso: pesoNumerico(p), fecha: p.fecha }))
    .filter(p => pesoEnRango(p.peso));

  if (serie.length < 2) {
    return { tieneGrafica: false } as const;
  }

  const pesos = serie.map(s => s.peso);
  const min = Math.min(...pesos);
  const max = Math.max(...pesos);
  const rango = max - min || 1;

  const W = 300;
  const H = 90;
  const padX = 12;
  const padY = 14;
  const n = serie.length;

  const coords = serie.map((s, i) => {
    const x = padX + (i / (n - 1)) * (W - padX * 2);
    const y = padY + (1 - (s.peso - min) / rango) * (H - padY * 2);
    return { x: Number(x.toFixed(1)), y: Number(y.toFixed(1)), peso: s.peso };
  });

  const linea = coords.map(c => `${c.x},${c.y}`).join(' ');
  const area = `${linea} ${coords[n - 1].x},${H} ${coords[0].x},${H}`;

  const cambioTotal = serie[n - 1].peso - serie[0].peso;

  return {
    tieneGrafica: true,
    W,
    H,
    linea,
    area,
    coords,
    min: Math.round(min),
    max: Math.round(max),
    primera: formatFecha(serie[0].fecha),
    ultima: formatFecha(serie[n - 1].fecha),
    cambioTotal: Math.round(cambioTotal),
    puntos: n,
  } as const;
});

const pesoEditadoFueraRango = computed(() => {
  if (editPesajeForm.value.peso_estimado === null || editPesajeForm.value.peso_estimado === undefined) {
    return false;
  }

  return !pesoEnRango(Number(editPesajeForm.value.peso_estimado));
});

const pesoRealEditadoFueraRango = computed(() => {
  if (editPesajeForm.value.peso_real === null || editPesajeForm.value.peso_real === undefined) {
    return false;
  }

  return !pesoEnRango(Number(editPesajeForm.value.peso_real));
});

function abrirCalendarioPesaje() {
  fechaPesajeInput.value?.showPicker?.();
}

function abrirCalendarioFechaNacimiento() {
  fechaNacimientoInput.value?.showPicker?.();
}

function abrirCalendarioNuevoNacimiento() {
  fechaNacimientoNuevoInput.value?.showPicker?.();
}

function obtenerToken(): string | null {
  return localStorage.getItem('token');
}

function obtenerUsuarioLocal(): any {
  try {
    return JSON.parse(localStorage.getItem('user') || '{}');
  } catch {
    return {};
  }
}

function limpiarDatosLocalesSesion() {
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  localStorage.removeItem('perfil');
  localStorage.removeItem('animalSel');
  localStorage.removeItem('fincaSel');
  localStorage.removeItem('pesajeSel');
  sessionStorage.clear();
}

function cerrarSesionLocal() {
  limpiarDatosLocalesSesion();
  router.replace('/login');
}

function pesoEnRango(valor: number): boolean {
  return Number.isFinite(valor) &&
    valor >= PESO_MINIMO &&
    valor <= PESO_MAXIMO;
}

function fechaParaInput(fecha: string | null | undefined): string {
  if (!fecha) {
    return hoy;
  }

  if (/^\d{4}-\d{2}-\d{2}$/.test(fecha)) {
    return fecha;
  }

  const fechaConvertida = new Date(fecha);

  if (Number.isNaN(fechaConvertida.getTime())) {
    return hoy;
  }

  return fechaConvertida.toISOString().slice(0, 10);
}

function fechaTiempoSeguro(valor: string | null | undefined): number {
  if (!valor) {
    return 0;
  }

  const fecha = new Date(valor);

  if (Number.isNaN(fecha.getTime())) {
    return 0;
  }

  return fecha.getTime();
}

function ordenarPesajesAsc(a: PesajeDto, b: PesajeDto): number {
  const fechaA = fechaTiempoSeguro(a.fecha);
  const fechaB = fechaTiempoSeguro(b.fecha);

  if (fechaA !== fechaB) {
    return fechaA - fechaB;
  }

  const creadoA = fechaTiempoSeguro((a as any).created_at);
  const creadoB = fechaTiempoSeguro((b as any).created_at);

  if (creadoA !== creadoB) {
    return creadoA - creadoB;
  }

  return Number(a.id) - Number(b.id);
}

function ordenarPesajesDesc(a: PesajeDto, b: PesajeDto): number {
  return ordenarPesajesAsc(b, a);
}

function inicialAnimal(animal: AnimalDto): string {
  const base = animal.nombre || animal.numero_arete || 'AN';

  return base
    .trim()
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map(p => p[0])
    .join('')
    .toUpperCase() || 'AN';
}

function estadoTag(a: AnimalDto) {
  if (a.estado === 'activo') {
    return {
      cls: 'tag-g',
      txt: 'Activo',
    };
  }

  return {
    cls: 'tag-r',
    txt: 'Inactivo',
  };
}

function obtenerNombreFinca(animal: AnimalDto): string {
  const fincaObjeto = (animal as any).finca;
  const fincaId = Number((animal as any).finca_id || fincaObjeto?.id);
  const finca = fincas.value.find(f => Number(f.id) === fincaId);

  return fincaObjeto?.nombre || finca?.nombre || 'Sin finca asignada';
}

function historialDeAnimal(animalId: number): PesajeDto[] {
  return historialesPorAnimal.value[animalId] || [];
}

function ultimoPesajeAnimal(animal: AnimalDto): PesajeDto | null {
  const historial = historialDeAnimal(animal.id);

  if (!historial.length) {
    return null;
  }

  return [...historial].sort((a, b) => ordenarPesajesDesc(a, b))[0];
}

function ultimoPeso(animal: AnimalDto): string {
  const ultimo = ultimoPesajeAnimal(animal);

  if (!ultimo) {
    return 'Sin pesajes';
  }

  const peso = pesoNumerico(ultimo);

  if (!pesoEnRango(peso)) {
    return 'Revisar peso';
  }

  return `${peso.toFixed(0)} kg`;
}

function ultimoPesoEsInvalido(animal: AnimalDto): boolean {
  const ultimo = ultimoPesajeAnimal(animal);

  if (!ultimo) {
    return false;
  }

  return !pesoEnRango(pesoNumerico(ultimo));
}

function calcularEdad(fecha: string | null | undefined): string {
  if (!fecha) {
    return 'Edad no registrada';
  }

  const nacimiento = new Date(fecha);
  const ahora = new Date();

  if (Number.isNaN(nacimiento.getTime())) {
    return 'Edad no registrada';
  }

  let meses =
    (ahora.getFullYear() - nacimiento.getFullYear()) * 12 +
    (ahora.getMonth() - nacimiento.getMonth());

  if (ahora.getDate() < nacimiento.getDate()) {
    meses -= 1;
  }

  if (meses < 0) {
    return 'Fecha inválida';
  }

  if (meses < 12) {
    return `${meses} mes(es)`;
  }

  return `${Math.floor(meses / 12)} año(s)`;
}

function extraerHistorial(res: any): PesajeDto[] {
  if (Array.isArray(res?.datos)) {
    return res.datos;
  }

  if (Array.isArray(res?.datos?.pesajes)) {
    return res.datos.pesajes;
  }

  return [];
}

function extraerErrores(data: any, mensajeDefault: string): string {
  if (data?.errores) {
    return Object.values(data.errores).flat().join(' ');
  }

  if (data?.errors) {
    return Object.values(data.errors).flat().join(' ');
  }

  return data?.mensaje || data?.message || mensajeDefault;
}

async function cargarHistorialesParaLista(listaAnimales: AnimalDto[]) {
  const pares = await Promise.all(
    listaAnimales.map(async (animal) => {
      try {
        const res = await getHistorialAnimal(animal.id);
        const historial = extraerHistorial(res);

        return [animal.id, historial] as const;
      } catch {
        return [animal.id, []] as const;
      }
    })
  );

  historialesPorAnimal.value = Object.fromEntries(pares);
}

async function cargarVeterinariosDisponibles() {
  cargandoVeterinarios.value = true;
  solicitudError.value = '';

  try {
    const res = await getVeterinariosDisponibles();
    veterinariosDisponibles.value = res.datos || [];

    if (
      veterinariosDisponibles.value.length > 0 &&
      !solicitudForm.value.veterinario_id
    ) {
      solicitudForm.value.veterinario_id = veterinariosDisponibles.value[0].id;
    }
  } catch (e: any) {
    solicitudError.value = e.message || 'No se pudieron cargar los veterinarios.';
    veterinariosDisponibles.value = [];
  } finally {
    cargandoVeterinarios.value = false;
  }
}

async function abrirSolicitudVeterinaria(animal: AnimalDto) {
  animalSel.value = animal;
  modalSolicitudAbierto.value = true;
  solicitudError.value = '';
  solicitudSuccess.value = '';

  solicitudForm.value = {
    veterinario_id: '',
    motivo: '',
  };

  if (veterinariosDisponibles.value.length === 0) {
    await cargarVeterinariosDisponibles();
  } else {
    solicitudForm.value.veterinario_id = veterinariosDisponibles.value[0]?.id || '';
  }
}

function cerrarSolicitudVeterinaria() {
  modalSolicitudAbierto.value = false;
  solicitudError.value = '';
  solicitudSuccess.value = '';

  solicitudForm.value = {
    veterinario_id: '',
    motivo: '',
  };

  animalSel.value = null;
}

function validarSolicitudVeterinaria(): string | null {
  if (!animalSel.value) {
    return 'No se seleccionó un animal para la solicitud.';
  }

  if (!solicitudForm.value.veterinario_id) {
    return 'Selecciona un veterinario.';
  }

  if (!solicitudForm.value.motivo.trim()) {
    return 'Escribe el motivo de la solicitud.';
  }

  if (solicitudForm.value.motivo.trim().length < 5) {
    return 'El motivo debe tener al menos 5 caracteres.';
  }

  return null;
}

async function enviarSolicitudVeterinaria() {
  const errorValidacion = validarSolicitudVeterinaria();

  if (errorValidacion) {
    solicitudError.value = errorValidacion;
    solicitudSuccess.value = '';
    return;
  }

  if (!animalSel.value) {
    return;
  }

  enviandoSolicitud.value = true;
  solicitudError.value = '';
  solicitudSuccess.value = '';

  try {
    await crearSolicitudVeterinaria({
      animal_id: animalSel.value.id,
      veterinario_id: Number(solicitudForm.value.veterinario_id),
      motivo: solicitudForm.value.motivo.trim(),
    });

    solicitudSuccess.value = 'Solicitud enviada correctamente al veterinario.';

    setTimeout(() => {
      cerrarSolicitudVeterinaria();
    }, 850);
  } catch (e: any) {
    solicitudError.value = e.message || 'No se pudo enviar la solicitud veterinaria.';
  } finally {
    enviandoSolicitud.value = false;
  }
}

async function abrirHistorial(animal: AnimalDto) {
  animalSel.value = animal;
  modalModo.value = 'historial';
  modalAbierto.value = true;
  loadingHistorial.value = true;
  historialAnimal.value = [];
  editandoPesajeId.value = null;
  editPesajeError.value = '';

  try {
    const res = await getHistorialAnimal(animal.id);
    const historial = extraerHistorial(res);

    historialAnimal.value = historial;

    historialesPorAnimal.value = {
      ...historialesPorAnimal.value,
      [animal.id]: historial,
    };
  } catch {
    historialAnimal.value = [];
  } finally {
    loadingHistorial.value = false;
  }
}

function abrirEditorAnimal(animal: AnimalDto) {
  animalSel.value = animal;
  modalModo.value = 'editar';
  modalAbierto.value = true;
  editAnimalError.value = '';
  editAnimalSuccess.value = '';

  const fincaObjeto = (animal as any).finca;

  editAnimalForm.value = {
    numero_arete: animal.numero_arete || '',
    nombre: animal.nombre || '',
    raza_id: Number((animal as any).raza_id || animal.raza?.id || '') || '',
    finca_id: Number((animal as any).finca_id || fincaObjeto?.id || '') || '',
    fecha_nacimiento: fechaParaInput(animal.fecha_nacimiento),
    estado: (animal.estado === 'inactivo' ? 'inactivo' : 'activo'),
  };
}

async function cambiarModoModal(modo: 'historial' | 'editar') {
  if (!animalSel.value) {
    return;
  }

  if (modo === 'historial') {
    await abrirHistorial(animalSel.value);
    return;
  }

  abrirEditorAnimal(animalSel.value);
}

function cerrarModal() {
  modalAbierto.value = false;
  animalSel.value = null;
  historialAnimal.value = [];
  editandoPesajeId.value = null;
  editPesajeError.value = '';
  editAnimalError.value = '';
  editAnimalSuccess.value = '';
}

function abrirCrearAnimal() {
  crearAnimalError.value = '';
  crearAnimalSuccess.value = '';

  nuevoAnimalForm.value = {
    numero_arete: '',
    nombre: '',
    raza_id: razas.value[0]?.id || '',
    finca_id: fincas.value[0]?.id || '',
    fecha_nacimiento: hoy,
  };

  modalCrearAbierto.value = true;
}

function cerrarCrearAnimal() {
  modalCrearAbierto.value = false;
  crearAnimalError.value = '';
  crearAnimalSuccess.value = '';

  nuevoAnimalForm.value = {
    numero_arete: '',
    nombre: '',
    raza_id: '',
    finca_id: '',
    fecha_nacimiento: hoy,
  };
}

function validarNuevoAnimal(): string | null {
  if (!nuevoAnimalForm.value.numero_arete.trim()) {
    return 'El número de arete es obligatorio.';
  }

  if (!nuevoAnimalForm.value.nombre.trim()) {
    return 'El nombre del animal es obligatorio.';
  }

  if (!nuevoAnimalForm.value.raza_id) {
    return 'Selecciona una raza.';
  }

  if (!nuevoAnimalForm.value.finca_id) {
    return 'Selecciona una finca.';
  }

  if (!nuevoAnimalForm.value.fecha_nacimiento) {
    return 'Selecciona la fecha de nacimiento.';
  }

  if (nuevoAnimalForm.value.fecha_nacimiento > hoy) {
    return 'La fecha de nacimiento no puede ser futura.';
  }

  return null;
}

async function guardarNuevoAnimal() {
  const errorValidacion = validarNuevoAnimal();

  if (errorValidacion) {
    crearAnimalError.value = errorValidacion;
    crearAnimalSuccess.value = '';
    return;
  }

  const token = obtenerToken();

  if (!token) {
    cerrarSesionLocal();
    return;
  }

  guardandoNuevoAnimal.value = true;
  crearAnimalError.value = '';
  crearAnimalSuccess.value = '';

  try {
    const response = await fetch(`${API_URL}/animales`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify({
        numero_arete: nuevoAnimalForm.value.numero_arete.trim(),
        nombre: nuevoAnimalForm.value.nombre.trim(),
        raza_id: Number(nuevoAnimalForm.value.raza_id),
        finca_id: Number(nuevoAnimalForm.value.finca_id),
        fecha_nacimiento: nuevoAnimalForm.value.fecha_nacimiento,
        estado: 'activo',
      }),
    });

    const data = await response.json();

    if (response.status === 401) {
      cerrarSesionLocal();
      return;
    }

    if (!response.ok || !data.exito) {
      throw new Error(extraerErrores(data, 'No se pudo crear el animal.'));
    }

    crearAnimalSuccess.value = 'Animal agregado correctamente.';

    await cargarTodo();

    setTimeout(() => {
      cerrarCrearAnimal();
    }, 650);
  } catch (e: any) {
    crearAnimalError.value = e.message || 'No se pudo crear el animal.';
    crearAnimalSuccess.value = '';
  } finally {
    guardandoNuevoAnimal.value = false;
  }
}

function registrarPeso() {
  cerrarModal();
  router.push('/tabs/pesajes');
}

function activarEdicionPesaje(pesaje: PesajeDto) {
  editandoPesajeId.value = pesaje.id;
  editPesajeError.value = '';

  editPesajeForm.value = {
    peso_estimado: Number(pesaje.peso_estimado ?? pesoNumerico(pesaje)),
    peso_real: pesaje.peso_real !== null && pesaje.peso_real !== undefined
      ? Number(pesaje.peso_real)
      : null,
    fecha: fechaParaInput(pesaje.fecha),
    fuente_id: pesaje.fuente_id ?? FUENTE_MANUAL,
  };
}

function cancelarEdicionPesaje() {
  editandoPesajeId.value = null;
  editPesajeError.value = '';
}

function validarEdicionPesaje(): string | null {
  if (
    editPesajeForm.value.peso_estimado === null ||
    editPesajeForm.value.peso_estimado === undefined ||
    Number.isNaN(Number(editPesajeForm.value.peso_estimado))
  ) {
    return 'Ingresa el peso registrado.';
  }

  if (!pesoEnRango(Number(editPesajeForm.value.peso_estimado))) {
    return `El peso registrado debe estar entre ${PESO_MINIMO} kg y ${PESO_MAXIMO} kg.`;
  }

  if (
    editPesajeForm.value.peso_real !== null &&
    editPesajeForm.value.peso_real !== undefined &&
    !pesoEnRango(Number(editPesajeForm.value.peso_real))
  ) {
    return `El peso real debe estar entre ${PESO_MINIMO} kg y ${PESO_MAXIMO} kg.`;
  }

  if (!editPesajeForm.value.fecha) {
    return 'Selecciona la fecha del pesaje.';
  }

  if (editPesajeForm.value.fecha > hoy) {
    return 'La fecha del pesaje no puede ser futura.';
  }

  return null;
}

async function guardarEdicionPesaje() {
  if (!editandoPesajeId.value) {
    return;
  }

  const errorValidacion = validarEdicionPesaje();

  if (errorValidacion) {
    editPesajeError.value = errorValidacion;
    return;
  }

  const token = obtenerToken();

  if (!token) {
    cerrarSesionLocal();
    return;
  }

  guardandoPesaje.value = true;
  editPesajeError.value = '';

  try {
    const response = await fetch(`${API_URL}/pesajes/${editandoPesajeId.value}`, {
      method: 'PUT',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify({
        peso_estimado: Number(editPesajeForm.value.peso_estimado),
        peso_real: editPesajeForm.value.peso_real !== null && editPesajeForm.value.peso_real !== undefined
          ? Number(editPesajeForm.value.peso_real)
          : null,
        fecha: editPesajeForm.value.fecha,
        fuente_id: editPesajeForm.value.fuente_id || FUENTE_MANUAL,
      }),
    });

    const data = await response.json();

    if (response.status === 401) {
      cerrarSesionLocal();
      return;
    }

    if (!response.ok || !data.exito) {
      throw new Error(extraerErrores(data, 'No se pudo actualizar el pesaje.'));
    }

    editandoPesajeId.value = null;

    const animalActual = animalSel.value;

    await cargarTodo();

    if (animalActual) {
      const actualizado = animales.value.find(a => Number(a.id) === Number(animalActual.id));
      await abrirHistorial(actualizado || animalActual);
    }
  } catch (e: any) {
    editPesajeError.value = e.message || 'No se pudo actualizar el pesaje.';
  } finally {
    guardandoPesaje.value = false;
  }
}

function validarEdicionAnimal(): string | null {
  if (!editAnimalForm.value.numero_arete.trim()) {
    return 'El número de arete es obligatorio.';
  }

  if (!editAnimalForm.value.nombre.trim()) {
    return 'El nombre del animal es obligatorio.';
  }

  if (!editAnimalForm.value.raza_id) {
    return 'Selecciona una raza.';
  }

  if (!editAnimalForm.value.finca_id) {
    return 'Selecciona una finca.';
  }

  if (!editAnimalForm.value.fecha_nacimiento) {
    return 'Selecciona la fecha de nacimiento.';
  }

  if (editAnimalForm.value.fecha_nacimiento > hoy) {
    return 'La fecha de nacimiento no puede ser futura.';
  }

  if (!['activo', 'inactivo'].includes(editAnimalForm.value.estado)) {
    return 'Selecciona un estado válido.';
  }

  return null;
}

async function guardarEdicionAnimal() {
  if (!animalSel.value) {
    return;
  }

  const errorValidacion = validarEdicionAnimal();

  if (errorValidacion) {
    editAnimalError.value = errorValidacion;
    editAnimalSuccess.value = '';
    return;
  }

  const token = obtenerToken();

  if (!token) {
    cerrarSesionLocal();
    return;
  }

  const razaId = Number(editAnimalForm.value.raza_id);
  const razaSel = razas.value.find(r => Number(r.id) === razaId);

  guardandoAnimal.value = true;
  editAnimalError.value = '';
  editAnimalSuccess.value = '';

  try {
    const response = await fetch(`${API_URL}/animales/${animalSel.value.id}`, {
      method: 'PUT',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify({
        numero_arete: editAnimalForm.value.numero_arete.trim(),
        nombre: editAnimalForm.value.nombre.trim(),
        raza_id: razaId,
        nombre_raza: razaSel?.nombre?.toLowerCase() || undefined,
        finca_id: Number(editAnimalForm.value.finca_id),
        fecha_nacimiento: editAnimalForm.value.fecha_nacimiento,
        estado: editAnimalForm.value.estado,
      }),
    });

    const data = await response.json();

    if (response.status === 401) {
      cerrarSesionLocal();
      return;
    }

    if (!response.ok || !data.exito) {
      throw new Error(extraerErrores(data, 'No se pudo actualizar el animal.'));
    }

    editAnimalSuccess.value = 'Animal actualizado correctamente.';

    const idActual = animalSel.value.id;

    await cargarTodo();

    const actualizado = animales.value.find(a => Number(a.id) === Number(idActual));

    if (actualizado) {
      animalSel.value = actualizado;
      abrirEditorAnimal(actualizado);
      editAnimalSuccess.value = 'Animal actualizado correctamente.';
    }
  } catch (e: any) {
    editAnimalError.value = e.message || 'No se pudo actualizar el animal.';
    editAnimalSuccess.value = '';
  } finally {
    guardandoAnimal.value = false;
  }
}

async function cargarTodo() {
  const token = obtenerToken();

  if (!token) {
    cerrarSesionLocal();
    return;
  }

  loading.value = true;
  error.value = '';

  const rawUser = obtenerUsuarioLocal();
  userId = rawUser?.id;

  try {
    const [animalesRes, razasRes] = await Promise.all([
      getAnimales(),
      getRazas(),
    ]);

    animales.value = animalesRes.datos || [];
    razas.value = razasRes.datos || [];

    if (userId) {
      const fincasRes = await getFincasByUsuario(userId);
      fincas.value = fincasRes.datos || [];
    } else {
      fincas.value = [];
    }

    await cargarHistorialesParaLista(animales.value);
  } catch (e: any) {
    if (String(e?.message || '').includes('401')) {
      cerrarSesionLocal();
      return;
    }

    error.value = e.message || 'No se pudieron cargar los animales.';
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  cargarTodo();
});

onIonViewWillEnter(() => {
  cargarTodo();
});
</script>

<style scoped>
.page-bg {
  --background: #F2F5F3;
}

.header {
  padding: 22px 20px 10px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.title {
  margin: 0;
  font-size: 1.8rem;
  font-weight: 900;
  color: #1A3D28;
  letter-spacing: -.04em;
}

.subtitle {
  margin-top: 4px;
  color: #6B7280;
  font-size: .85rem;
}

.reload-btn {
  border: none;
  background: #1E5631;
  color: white;
  height: 40px;
  padding: 0 14px;
  border-radius: 999px;
  font-size: .75rem;
  font-weight: 800;
  font-family: inherit;
  cursor: pointer;
}

.container {
  padding: 16px;
}

.search-box {
  margin-bottom: 14px;
}

.search-input {
  width: 100%;
  padding: 14px;
  border-radius: 14px;
  border: 1.5px solid #D1D5DB;
  background: white;
  color: #111827;
  font-size: .95rem;
  box-sizing: border-box;
  outline: none;
}

.search-input:focus {
  border-color: #1E5631;
}

.filters {
  display: flex;
  gap: 10px;
  margin-bottom: 18px;
}

.filter-btn {
  border: none;
  background: white;
  padding: 10px 16px;
  border-radius: 999px;
  font-weight: 800;
  color: #4B5563;
  font-family: inherit;
  cursor: pointer;
}

.filter-btn.active {
  background: #1E5631;
  color: white;
}

.status {
  padding: 20px;
  text-align: center;
  border-radius: 14px;
}

.loading {
  background: #F3F4F6;
  color: #6B7280;
}

.error {
  background: #FEE2E2;
  color: #991B1B;
}

.empty {
  background: #F3F4F6;
  color: #6B7280;
}

.animal-card {
  background: white;
  border-radius: 16px;
  padding: 16px;
  margin-bottom: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,.06);
}

.card-top {
  display: flex;
  align-items: center;
  gap: 14px;
}

.avatar {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: linear-gradient(135deg, #E8F5EE, #C6E8D4);
  color: #1E5631;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: .8rem;
  font-weight: 900;
  flex-shrink: 0;
}

.info {
  flex: 1;
  min-width: 0;
}

.animal-name {
  font-size: .95rem;
  font-weight: 900;
  color: #111827;
}

.animal-meta {
  font-size: .75rem;
  color: #6B7280;
  margin-top: 2px;
}

.card-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
}

.tag {
  font-size: .6875rem;
  font-weight: 800;
  padding: 4px 9px;
  border-radius: 999px;
}

.tag-g {
  background: #D1FAE5;
  color: #065F46;
}

.tag-r {
  background: #FEE2E2;
  color: #991B1B;
}

.ultimo-peso {
  font-size: .9rem;
  font-weight: 900;
  color: #1E5631;
}

.ultimo-label {
  font-size: .65rem;
  color: #9CA3AF;
  font-weight: 700;
}

.peso-alerta {
  color: #DC2626;
}

.card-actions {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  margin-top: 14px;
}

.action-btn {
  border: none;
  border-radius: 12px;
  padding: 11px;
  font-size: .8rem;
  font-weight: 900;
  font-family: inherit;
  cursor: pointer;
}

.action-btn.primary {
  background: #1E5631;
  color: #fff;
}

.action-btn.secondary {
  background: #EEF9F2;
  color: #1E5631;
}

.action-btn.warning {
  background: #FFF7ED;
  color: #9A3412;
}

.modal-content {
  --background: #F2F5F3;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 20px 10px;
}

.modal-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 900;
  color: #1A3D28;
}

.modal-sub {
  margin-top: 3px;
  font-size: .75rem;
  color: #6B7280;
}

.close-btn {
  border: none;
  background: #E5E7EB;
  color: #374151;
  width: 38px;
  height: 38px;
  border-radius: 50%;
  font-size: 1.25rem;
  cursor: pointer;
}

.modal-tabs {
  display: flex;
  background: #F2F5F3;
  border-top: 1px solid #E5E7EB;
  border-bottom: 1px solid #E5E7EB;
}

.modal-tab {
  flex: 1;
  padding: 13px 10px;
  border: none;
  background: transparent;
  color: #6B7280;
  font-weight: 900;
  font-family: inherit;
  cursor: pointer;
  border-bottom: 2px solid transparent;
}

.modal-tab.active {
  background: white;
  color: #1E5631;
  border-bottom-color: #1E5631;
}

.modal-body {
  padding: 18px 20px 30px;
}

.sec-title {
  font-size: .875rem;
  font-weight: 900;
  color: #6B7280;
  margin-bottom: 12px;
  text-transform: uppercase;
  letter-spacing: .06em;
}

.status-box {
  padding: 16px;
  border-radius: 12px;
  text-align: center;
}

.status-loading {
  background: #F3F4F6;
  color: #6B7280;
}

.empty-state {
  color: #9CA3AF;
  text-align: center;
  padding: 20px 0;
}

.chart-card {
  background: white;
  border-radius: 16px;
  padding: 14px 16px 10px;
  margin-bottom: 14px;
  box-shadow: 0 1px 3px rgba(0,0,0,.06);
}

.chart-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
}

.chart-title {
  font-size: .8rem;
  font-weight: 900;
  color: #1A3D28;
}

.chart-trend {
  font-size: .72rem;
  font-weight: 900;
  padding: 3px 9px;
  border-radius: 999px;
}

.trend-up {
  background: #DCFCE7;
  color: #166534;
}

.trend-dn {
  background: #FEE2E2;
  color: #991B1B;
}

.chart-svg {
  width: 100%;
  height: 90px;
  display: block;
}

.chart-foot {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 6px;
  font-size: .66rem;
  color: #9CA3AF;
  font-weight: 700;
}

.chart-range {
  color: #1E5631;
  font-weight: 900;
}

.hist-list {
  background: white;
  border-radius: 16px;
  padding: 8px 14px;
  margin-bottom: 16px;
}

.hist-row-wrap {
  border-bottom: 1px solid #F3F4F6;
}

.hist-row-wrap:last-child {
  border-bottom: none;
}

.hist-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 0;
}

.hist-row-invalid {
  background: #FFF7ED;
  margin: 0 -8px;
  padding: 10px 8px;
  border-radius: 10px;
}

.hist-dot {
  width: 9px;
  height: 9px;
  border-radius: 50%;
  background: #74C69D;
  flex-shrink: 0;
}

.hist-dot-first {
  background: #1E5631;
  width: 11px;
  height: 11px;
}

.hist-main {
  flex: 1;
  min-width: 0;
}

.hist-date {
  font-size: .72rem;
  color: #6B7280;
}

.hist-warning {
  font-size: .68rem;
  color: #C2410C;
  font-weight: 800;
  margin-top: 2px;
}

.hist-values {
  text-align: right;
  min-width: 78px;
}

.hist-w {
  font-size: .83rem;
  font-weight: 900;
  color: #111827;
}

.hist-d {
  font-size: .72rem;
  font-weight: 900;
  margin-top: 2px;
}

.hist-d-empty {
  color: #9CA3AF;
  font-weight: 700;
}

.diff-up {
  color: #2D7A4A;
}

.diff-dn {
  color: #EF4444;
}

.edit-mini-btn {
  border: none;
  background: #EEF9F2;
  color: #1E5631;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: .7rem;
  font-weight: 900;
  font-family: inherit;
  cursor: pointer;
}

.edit-box {
  background: #F9FAFB;
  border: 1px solid #E5E7EB;
  border-radius: 14px;
  padding: 14px;
  margin-bottom: 12px;
}

.animal-edit-box {
  background: white;
}

.edit-title {
  font-size: .85rem;
  font-weight: 900;
  color: #1A3D28;
  margin-bottom: 12px;
}

.edit-field {
  margin-bottom: 12px;
}

.edit-field label {
  display: block;
  font-size: .75rem;
  font-weight: 800;
  color: #374151;
  margin-bottom: 6px;
}

.input-with-unit {
  position: relative;
  display: flex;
  align-items: center;
}

.input-with-unit input,
.edit-date {
  width: 100%;
  padding: 11px 42px 11px 12px;
  border: 1.5px solid #E5E7EB;
  border-radius: 12px;
  background: white;
  color: #111827;
  font-size: .875rem;
  outline: none;
  box-sizing: border-box;
  font-family: inherit;
}

select.edit-date {
  padding-right: 12px;
}

.input-with-unit input:focus,
.edit-date:focus,
.edit-textarea:focus {
  border-color: #1E5631;
}

.input-with-unit span {
  position: absolute;
  right: 12px;
  font-size: .8rem;
  color: #6B7280;
  font-weight: 800;
}

.edit-textarea {
  width: 100%;
  padding: 12px;
  border: 1.5px solid #E5E7EB;
  border-radius: 12px;
  background: white;
  color: #111827;
  font-size: .875rem;
  outline: none;
  box-sizing: border-box;
  font-family: inherit;
  resize: vertical;
  min-height: 110px;
}

.field-error input {
  border-color: #EF4444;
  background: #FEF2F2;
}

.field-hint {
  margin-top: 6px;
  font-size: .72rem;
  color: #6B7280;
}

.feedback {
  padding: 11px 12px;
  border-radius: 12px;
  font-size: .8rem;
  font-weight: 800;
  margin-bottom: 12px;
}

.feedback.success {
  background: #DCFCE7;
  color: #166534;
}

.feedback.error {
  background: #FEE2E2;
  color: #991B1B;
}

.edit-actions {
  display: flex;
  gap: 10px;
}

.btn-primary,
.btn-outline {
  width: 100%;
  padding: 14px;
  border-radius: 14px;
  cursor: pointer;
  font-size: .9rem;
  font-weight: 900;
  font-family: inherit;
}

.btn-primary {
  background: linear-gradient(135deg, #1E5631, #3A9E61);
  color: #fff;
  border: none;
  box-shadow: 0 4px 14px rgba(30,86,49,.3);
}

.btn-primary.small {
  padding: 11px;
  font-size: .8rem;
}

.btn-outline {
  background: transparent;
  color: #6B7280;
  border: 1.5px solid #E5E7EB;
  padding: 11px;
  font-size: .8rem;
}

.btn-primary:disabled,
.btn-outline:disabled {
  opacity: .65;
  cursor: not-allowed;
}

.btn-primary:hover {
  opacity: .94;
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

.request-animal-card {
  display: flex;
  gap: 12px;
  align-items: center;
  background: #F2F5F3;
  border-radius: 14px;
  padding: 12px;
  margin-bottom: 14px;
}

.request-avatar {
  width: 44px;
  height: 44px;
  border-radius: 14px;
  background: linear-gradient(135deg, #E8F5EE, #C6E8D4);
  color: #1E5631;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: .78rem;
  font-weight: 900;
  flex-shrink: 0;
}

.request-animal-card strong {
  display: block;
  color: #1A3D28;
  font-size: .9rem;
}

.request-animal-card p {
  margin: 2px 0 0;
  color: #6B7280;
  font-size: .74rem;
}

@media (max-width: 720px) {
  .card-actions {
    grid-template-columns: 1fr;
  }

  .card-top {
    align-items: flex-start;
  }

  .card-right {
    min-width: 82px;
  }

  .edit-actions {
    flex-direction: column;
  }
}
</style>