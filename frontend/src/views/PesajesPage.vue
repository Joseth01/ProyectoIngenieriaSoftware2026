<template>
  <ion-page>
    <ion-content :fullscreen="true" class="page-bg">

      <!-- Header -->
      <div class="app-bar">
        <div>
          <div class="page-title">Registrar Peso</div>
          <div class="page-sub">Nueva medición</div>
        </div>

        <button class="icon-btn" @click="resetForm">
          ↺
        </button>
      </div>

      <div class="body-pad">

        <!-- IA -->
        <div
          class="ia-banner"
          @click="router.push('/tabs/pesaje-vivo')"
        >
          <div class="ia-left">
            <span class="ia-ico">🤖</span>

            <div>
              <div class="ia-title">
                Usar IA para estimar peso
              </div>

              <div class="ia-sub">
                Apunta la cámara al animal
              </div>
            </div>
          </div>

          <span class="ia-chev">›</span>
        </div>

        <!-- Animal -->
        <div class="form-section">
          <div class="form-sec-title">Animal</div>
          <div class="animal-sel" @click="abrirModal">
            <div class="anim-emo">{{ animalSel ? '🐄' : '➕' }}</div>
            <div class="anim-sel-info">
              <div class="anim-sel-name">{{ animalSel ? (animalSel.nombre ?? `Arete ${animalSel.numero_arete}`) : 'Seleccionar o crear animal' }}</div>
              <div class="anim-sel-id">{{ animalSel ? `#${animalSel.numero_arete} · ${animalSel.raza?.nombre ?? 'Sin raza'}` : 'Toca para elegir' }}</div>
            </div>

            <span class="chev">›</span>

          </div>

        </div>

        <!-- Peso -->
        <div class="form-section">

          <div class="form-sec-title">
            Peso registrado
          </div>

          <div class="weight-wrap">

            <input
              v-model.number="peso"
              type="number"
              class="weight-input"
              placeholder="0"
            />

            <span class="weight-unit">
              kg
            </span>

          </div>

          <div class="adjust-row">

            <button class="adj" @click="peso = Math.max(0, peso - 5)">
              −5
            </button>

            <button class="adj" @click="peso = Math.max(0, peso - 1)">
              −1
            </button>

            <button class="adj" @click="peso += 1">
              +1
            </button>

            <button class="adj" @click="peso += 5">
              +5
            </button>

          </div>

        </div>

        <!-- Peso real -->
        <div class="form-section">

          <div class="form-sec-title">
            Peso real (opcional)
          </div>

          <div class="field-wrap">

            <input
              v-model.number="pesoReal"
              type="number"
              class="field-input"
              placeholder="Si usaste báscula"
            />

            <span class="field-suffix">
              kg
            </span>

          </div>

        </div>

        <!-- Fecha -->
        <div class="form-section">

          <div class="form-sec-title">
            Detalles
          </div>

          <div class="form-group">

            <label class="form-label">
              Fecha de medición
            </label>

            <input
              v-model="fecha"
              type="date"
              class="field-input"
            />

          </div>

        </div>

        <!-- Feedback -->
        <div
          v-if="success"
          class="feedback success"
        >
          ✓ Pesaje registrado exitosamente
        </div>

        <div
          v-if="errorMsg"
          class="feedback error"
        >
          {{ errorMsg }}
        </div>

        <!-- Guardar -->
        <button
          class="btn-save"
          :disabled="saving"
          @click="guardar"
        >

          <span v-if="saving">
            Guardando...
          </span>

          <span v-else>
            💾 Guardar pesaje
          </span>

        </button>

        <!-- Historial -->
        <div class="sec-title">
          Pesajes recientes
        </div>

        <div
          v-if="loadingPesajes"
          class="status-box status-loading"
        >
          Cargando...
        </div>

        <div
          v-else-if="pesajes.length === 0"
          class="empty-state"
        >
          No hay pesajes registrados.
        </div>

        <div
          v-for="p in pesajes"
          :key="p.id"
          class="pesaje-row"
        >

          <div class="pr-dot"></div>

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

          <div class="pr-peso">
            {{ pesoNumerico(p).toFixed(0) }} kg
          </div>

        </div>

      </div>

      <!-- ════════════════════════════════════════════
           MODAL: Seleccionar / Crear animal
           ════════════════════════════════════════════ -->
      <ion-modal :is-open="mostrarSelector" @didDismiss="cerrarModal">
        <ion-content class="page-bg">

          <!-- header del modal -->
          <div class="app-bar">
            <div class="page-title" style="font-size:1.125rem">
              {{ modoModal === 'seleccionar' ? 'Seleccionar animal' : 'Nuevo animal' }}
            </div>
            <button class="icon-btn" @click="cerrarModal">✕</button>
          </div>

          <!-- tabs -->
          <div class="modal-tab-row">
            <button
              class="modal-tab"
              :class="{ active: modoModal === 'seleccionar' }"
              @click="modoModal = 'seleccionar'"
            >
              🔍 Seleccionar
            </button>
            <button
              class="modal-tab"
              :class="{ active: modoModal === 'crear' }"
              @click="modoModal = 'crear'"
            >
              ➕ Nuevo animal
            </button>
          </div>

          <div class="body-pad">

            <!-- ── TAB: Seleccionar ───────────────────── -->
            <template v-if="modoModal === 'seleccionar'">
              <div class="search-box" style="margin-bottom:12px">
                <span>🔍</span>
                <input v-model="busqueda" class="search-input" placeholder="Buscar por nombre o arete…" />
              </div>

              <div
                v-for="a in animalesFiltrados"
                :key="a.id"
                class="animal-sel-opt"
                @click="elegirAnimal(a)"
              >
                <div class="anim-emo">🐄</div>
                <div>
                  <div style="font-size:.875rem;font-weight:600;color:#111827">{{ a.nombre ?? `Arete ${a.numero_arete}` }}</div>
                  <div style="font-size:.6875rem;color:#6B7280">#{{ a.numero_arete }} · {{ a.raza?.nombre ?? 'Sin raza' }}</div>
                </div>
              </div>

              <div v-if="animalesFiltrados.length === 0" class="empty-state">
                Sin resultados. Usa la pestaña "Nuevo animal" para registrar uno.
              </div>
            </template>

            <!-- ── TAB: Crear animal ──────────────────── -->
            <template v-else>

              <!-- Número de arete -->
              <div class="nf-field">
                <label class="nf-label">Número de arete <span class="req">*</span></label>
                <input
                  v-model="nuevoArete"
                  type="text"
                  class="nf-input"
                  placeholder="Ej. 001-2025"
                />
              </div>

              <!-- Nombre -->
              <div class="nf-field">
                <label class="nf-label">Nombre del animal <span class="req">*</span></label>
                <input
                  v-model="nuevoNombre"
                  type="text"
                  class="nf-input"
                  placeholder="Ej. Canela"
                />
              </div>

              <!-- Raza -->
              <div class="nf-field">
                <label class="nf-label">Raza <span class="req">*</span></label>
                <select v-model="nuevoRazaId" class="nf-input nf-select">
                  <option value="" disabled>Selecciona una raza</option>
                  <option
                    v-for="r in razas"
                    :key="r.id"
                    :value="r.id"
                  >
                    {{ r.nombre }}
                  </option>
                </select>
              </div>

              <!-- Fecha de nacimiento -->
              <div class="nf-field">
                <label class="nf-label">Fecha de nacimiento <span class="req">*</span></label>
                <input
                  v-model="nuevoFechaNacimiento"
                  type="date"
                  class="nf-input"
                  :max="hoy"
                />
              </div>

              <!-- Finca -->
              <div class="nf-field">
                <label class="nf-label">Finca <span class="req">*</span></label>
                <select v-model="nuevoFincaId" class="nf-input nf-select" :disabled="fincas.length === 1">
                  <option value="" disabled>Selecciona una finca</option>
                  <option v-for="f in fincas" :key="f.id" :value="f.id">{{ f.nombre }}</option>
                </select>
                <p v-if="fincas.length === 0" class="nf-hint">
                  No tienes fincas registradas.
                  <a @click="cerrarModal(); router.push('/tabs/finca')" class="nf-link">Crea una primero →</a>
                </p>
              </div>

              <!-- Divider peso ingreso -->
              <div class="nf-divider">
                <span>Peso de ingreso a la finca</span>
              </div>

              <!-- Peso de ingreso -->
              <div class="nf-field">
                <label class="nf-label">Peso al ingresar <span class="nf-optional">(opcional)</span></label>
                <div class="nf-peso-wrap">
                  <input
                    v-model.number="nuevoPesoIngreso"
                    type="number"
                    class="nf-input"
                    placeholder="Ej. 320"
                    min="0"
                    step="0.1"
                  />
                  <span class="nf-peso-unit">kg</span>
                </div>
              </div>

              <!-- Fecha de ingreso -->
              <div v-if="nuevoPesoIngreso" class="nf-field">
                <label class="nf-label">Fecha de ingreso</label>
                <input
                  v-model="nuevoFechaIngreso"
                  type="date"
                  class="nf-input"
                  :max="hoy"
                />
              </div>

              <!-- Error / Éxito creación -->
              <div v-if="errorCrear" class="feedback error" style="margin-bottom:12px">{{ errorCrear }}</div>
              <div v-if="successCrear" class="feedback success" style="margin-bottom:12px">{{ successCrear }}</div>

              <!-- Botón guardar animal -->
              <button class="btn-save" :disabled="guardandoAnimal || fincas.length === 0" @click="crearAnimal">
                <span v-if="guardandoAnimal">Creando animal…</span>
                <span v-else>🐄 Crear y seleccionar</span>
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
  getAnimales, getPesajes, crearPesaje, crearAnimal as apiCrearAnimal,
  getRazas, getFincasByUsuario,
  pesoNumerico, formatFecha,
  AnimalDto, PesajeDto, RazaDto, FincaDto,
} from '@/services/api';

const router = useRouter();

// ── Datos generales ────────────────────────────────────────────────────────
const animales       = ref<AnimalDto[]>([]);
const pesajes        = ref<PesajeDto[]>([]);
const razas          = ref<RazaDto[]>([]);
const fincas         = ref<FincaDto[]>([]);

// ── Estado del formulario de pesaje ───────────────────────────────────────
const animalSel      = ref<AnimalDto | null>(null);
const peso           = ref(0);
const pesoReal       = ref<number | null>(null);
const fecha          = ref(new Date().toISOString().slice(0, 10));
const saving         = ref(false);
const loadingPesajes = ref(true);

const success = ref(false);

const errorMsg = ref('');

// ── Modal ──────────────────────────────────────────────────────────────────
const mostrarSelector = ref(false);
const modoModal       = ref<'seleccionar' | 'crear'>('seleccionar');
const busqueda        = ref('');

// ── Formulario nuevo animal ────────────────────────────────────────────────
const nuevoArete          = ref('');
const nuevoNombre         = ref('');
const nuevoRazaId         = ref<number | ''>('');
const nuevoFechaNacimiento = ref('');
const nuevoFincaId        = ref<number | ''>('');
const guardandoAnimal      = ref(false);
const errorCrear           = ref('');
const successCrear         = ref('');
const nuevoPesoIngreso     = ref<number | null>(null);
const nuevoFechaIngreso    = ref(new Date().toISOString().slice(0, 10));
const hoy                  = new Date().toISOString().slice(0, 10);

// ── Usuario logueado (disponible en todo el componente) ────────────────────
let userId: number | undefined;

// ── Computadas ─────────────────────────────────────────────────────────────
const animalesFiltrados = computed(() => {

  const q = busqueda.value.toLowerCase();

  if (!q) {
    return animales.value;
  }

  return animales.value.filter(a =>
    (a.nombre || '')
      .toLowerCase()
      .includes(q)
    ||
    a.numero_arete
      .toLowerCase()
      .includes(q)
  );

});

// ── Helpers ────────────────────────────────────────────────────────────────
const limpiarFormAnimal = () => {
  nuevoArete.value = '';
  nuevoNombre.value = '';
  nuevoRazaId.value = '';
  nuevoFechaNacimiento.value = '';
  nuevoPesoIngreso.value = null;
  nuevoFechaIngreso.value = new Date().toISOString().slice(0, 10);
  errorCrear.value = '';
  successCrear.value = '';
  // Finca: si solo hay una, mantenerla seleccionada
  if (fincas.value.length === 1) nuevoFincaId.value = fincas.value[0].id;
};

const abrirModal = async () => {
  modoModal.value = 'seleccionar';
  busqueda.value = '';
  mostrarSelector.value = true;

  // Recargar animales y fincas al abrir — captura creaciones recientes
  try {
    const [aData, fData] = await Promise.all([
      getAnimales(),
      userId ? getFincasByUsuario(userId) : Promise.resolve(fincas.value),
    ]);
    animales.value = aData.datos || [];
    fincas.value   = fData.datos || [];
    if (fincas.value.length === 1) nuevoFincaId.value = fincas.value[0].id;
  } catch { /* mantiene datos previos si falla */ }
};

const cerrarModal = () => {
  mostrarSelector.value = false;
  limpiarFormAnimal();
};

const elegirAnimal = (a: AnimalDto) => {
  animalSel.value = a;

  mostrarSelector.value = false;

}

function resetForm() {
  animalSel.value = null;
  peso.value = 0;
  pesoReal.value = null;
  fecha.value = new Date().toISOString().slice(0, 10);
  errorMsg.value = '';
  success.value = false;
}

// ── Crear animal ───────────────────────────────────────────────────────────
const crearAnimal = async () => {
  errorCrear.value = '';
  successCrear.value = '';

  if (!nuevoArete.value.trim()) { errorCrear.value = 'El número de arete es obligatorio.'; return; }
  if (!nuevoNombre.value.trim()) { errorCrear.value = 'El nombre es obligatorio.'; return; }
  if (!nuevoRazaId.value)        { errorCrear.value = 'Selecciona una raza.'; return; }
  if (!nuevoFechaNacimiento.value) { errorCrear.value = 'La fecha de nacimiento es obligatoria.'; return; }
  if (!nuevoFincaId.value)       { errorCrear.value = 'Selecciona una finca.'; return; }

  // Coerción explícita — el v-model de <select> puede entregar string o number
  const razaId  = Number(nuevoRazaId.value);
  const fincaId = Number(nuevoFincaId.value);
  const razaSel = razas.value.find(r => r.id === razaId);

  if (!razaSel) { errorCrear.value = 'Raza no válida. Recarga la página e intenta de nuevo.'; return; }

  guardandoAnimal.value = true;
  try {
    const animal = await apiCrearAnimal({
      numero_arete:     nuevoArete.value.trim(),
      nombre:           nuevoNombre.value.trim(),
      raza_id:          razaId,
      nombre_raza:      razaSel.nombre.toLowerCase(),
      fecha_nacimiento: nuevoFechaNacimiento.value,
      finca_id:         fincaId,
    } as any);

    // Si se ingresó peso de ingreso, registrar el primer pesaje automáticamente.
    // El backend usa el patrón Strategy (metodo_estimacion requerido).
    // Usamos 'tabla' + raza + edad calculada → peso_estimado lo genera el algoritmo.
    // peso_real = peso conocido que el usuario ingresó.
    if (nuevoPesoIngreso.value && nuevoPesoIngreso.value > 0) {
      const nacimiento  = new Date(nuevoFechaNacimiento.value);
      const fechaIngreso = new Date(nuevoFechaIngreso.value);
      const edadMeses   = Math.max(1, Math.floor(
        (fechaIngreso.getTime() - nacimiento.getTime()) / (1000 * 60 * 60 * 24 * 30.44)
      ));

      await crearPesaje({
        animal_id:          animal.id,
        fecha:              nuevoFechaIngreso.value,
        metodo_estimacion:  'tabla',
        raza:               razaSel.nombre.toLowerCase(),
        edad_meses:         edadMeses,
        peso_real:          nuevoPesoIngreso.value,
      } as any);
    }

    // Actualizar lista y autoseleccionar el animal recién creado
    const [aData, pData] = await Promise.all([getAnimales(), getPesajes()]);
    animales.value = aData.datos || [];
    pesajes.value  = (pData.datos || [])
      .sort((a, b) => new Date(b.fecha).getTime() - new Date(a.fecha).getTime())
      .slice(0, 10);

    const msgPeso = nuevoPesoIngreso.value && nuevoPesoIngreso.value > 0
      ? ` y pesaje de ingreso (${nuevoPesoIngreso.value} kg) registrados.`
      : ' creado.';
    successCrear.value = `✓ Animal "${animal.nombre}"${msgPeso}`;

    // Esperar un momento para que el usuario vea el éxito, luego seleccionar y cerrar
    setTimeout(() => {
      elegirAnimal(animal);
      limpiarFormAnimal();
    }, 900);

  } catch (e: unknown) {
    errorCrear.value = e instanceof Error ? e.message : 'Error al crear el animal.';
  } finally {
    guardandoAnimal.value = false;
  }
};

// ── Guardar pesaje ─────────────────────────────────────────────────────────
async function guardar() {

  if (!animalSel.value) {

    errorMsg.value =
      'Selecciona un animal';

    return;
  }

  if (peso.value <= 0) {

    errorMsg.value =
      'Ingresa un peso válido';

    return;
  }

  saving.value = true;

  errorMsg.value = '';

  success.value = false;

  try {

    await crearPesaje({
      animal_id:     animalSel.value.id,
      peso_estimado: peso.value,
      peso_real:     pesoReal.value ?? undefined,
      fecha:         fecha.value,
    });

    success.value = true;
    resetForm();
    const r = await getPesajes();

    pesajes.value =
      (r.datos || [])
        .sort(
          (a, b) =>
            new Date(b.fecha).getTime() -
            new Date(a.fecha).getTime()
        )
        .slice(0, 10);

    resetForm();

  } catch (e: any) {

    console.error(e);

    errorMsg.value =
      'Error al guardar el pesaje';

  } finally {

    saving.value = false;

  }

}

// ── Montaje ────────────────────────────────────────────────────────────────
onMounted(async () => {
  // Leer usuario del localStorage para cargar sus fincas
  const raw = localStorage.getItem('user');
  userId = raw ? (JSON.parse(raw) as { id?: number }).id : undefined;

  try {
    const [aData, pData, rData] = await Promise.all([
      getAnimales(),
      getPesajes(),
      getRazas(),
    ]);
    animales.value = aData.datos || [];
    pesajes.value  = (pData.datos || [])
      .sort((a, b) => new Date(b.fecha).getTime() - new Date(a.fecha).getTime())
      .slice(0, 10);
    razas.value = rData.datos || [];

    if (userId) {
      fincas.value = (await getFincasByUsuario(userId)).datos || [];
      // Autoseleccionar si solo hay una finca
      if (fincas.value.length === 1) nuevoFincaId.value = fincas.value[0].id;
    }
  } catch { /* sin datos */ }
  finally { loadingPesajes.value = false; }
});

</script>

<style scoped>

.page-bg {
  --background: #f4f7f5;
}

.app-bar {
  background: white;
  padding: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.page-title {
  font-size: 1.2rem;
  font-weight: 800;
  color: #1c3d2a;
}

.page-sub {
  font-size: 0.8rem;
  color: #6b7280;
}

.icon-btn {
  width: 40px;
  height: 40px;
  border: none;
  border-radius: 12px;
}

.body-pad {
  padding: 16px;
}

.form-section {
  background: white;
  padding: 16px;
  border-radius: 16px;
  margin-bottom: 14px;
}

.form-sec-title {
  font-size: 0.75rem;
  font-weight: 700;
  margin-bottom: 10px;
  color: #6b7280;
}

.weight-input,
.field-input,
.search-input {
  width: 100%;
  padding: 14px;
  border-radius: 12px;
  border: 1px solid #d1d5db;
  color: #111827;
}
.animal-sel:hover { border-color: #1E5631; }
.anim-emo {
  width: 40px; height: 40px; border-radius: 10px;
  background: #EEF9F2; display: flex; align-items: center;
  justify-content: center; font-size: 18px; flex-shrink: 0;
}
.anim-sel-info { flex: 1; }
.anim-sel-name { font-size: .875rem; font-weight: 600; color: #111827; }
.anim-sel-id   { font-size: .6875rem; color: #6B7280; }
.chev { color: #9CA3AF; font-size: 20px; }

/* Weight input */
.weight-wrap {
  display: flex; align-items: center; justify-content: center;
  position: relative; margin-bottom: 12px;
}
.weight-input {
  width: 100%; text-align: center;
  font-size: 2.5rem; font-weight: 900; color: #1E5631;
  border: 2px solid #D8F3DC; background: #EEF9F2;
  border-radius: 14px; padding: 18px 48px 18px 20px;
  outline: none; font-family: inherit; transition: border-color .15s;
}
.weight-input:focus { border-color: #2D7A4A; }
.weight-unit {
  position: absolute; right: 16px;
  font-size: 1rem; font-weight: 700; color: #2D7A4A;
}
.adjust-row { display: flex; gap: 6px; }
.adj {
  flex: 1; padding: 8px; border-radius: 8px;
  border: 1.5px solid #D8F3DC; background: #EEF9F2;
  color: #1E5631; font-size: .75rem; font-weight: 700;
  cursor: pointer; font-family: inherit; transition: background .15s;
}
.adj:hover { background: #D8F3DC; }

/* Field */
.field-wrap { display: flex; align-items: center; position: relative; }
.field-input {
  width: 100%; padding: 11px 44px 11px 14px;
  border: 1.5px solid #E5E7EB; border-radius: 12px;
  font-size: .875rem; color: #111827; background: #F2F5F3;
  outline: none; font-family: inherit; transition: border-color .15s;
  box-sizing: border-box;
}
.field-input:focus { border-color: #1E5631; background: #fff; }
.field-suffix { position: absolute; right: 14px; font-size: .8125rem; font-weight: 600; color: #6B7280; }
.form-group { margin-bottom: 0; }
.form-label { display: block; font-size: .8125rem; font-weight: 600; color: #374151; margin-bottom: 6px; }

/* Feedback */
.feedback {
  padding: 12px;
  border-radius: 12px;
  margin-bottom: 12px;
}

.success {
  background: #dcfce7;
  color: #166534;
}

.error {
  background: #fee2e2;
  color: #991b1b;
}

.btn-save {
  width: 100%; padding: 15px;
  background: linear-gradient(135deg, #1E5631, #3A9E61);
  color: #fff; font-size: .9375rem; font-weight: 700;
  border: none; border-radius: 14px; cursor: pointer;
  box-shadow: 0 4px 14px rgba(30,86,49,.3);
  font-family: inherit; display: flex; align-items: center; justify-content: center;
  transition: opacity .2s;
}
.btn-save:disabled { opacity: .65; cursor: not-allowed; }

/* Status */
.sec-title { font-size: .875rem; font-weight: 700; color: #111827; }
.status-box { padding: 12px; border-radius: 10px; font-size: .875rem; margin-bottom: 10px; }
.status-loading { background: #F2F5F3; color: #374151; }
.empty-state { text-align: center; color: #6B7280; padding: 20px 0; font-size: .875rem; }

/* Pesaje row */
.pesaje-row {
  background: white;
  border-radius: 14px;
  padding: 14px;
  margin-bottom: 10px;
  display: flex;
  align-items: center;
}

.pr-dot {
  width: 10px;
  height: 10px;
  background: #16a34a;
  border-radius: 50%;
  margin-right: 12px;
}

/* ── Modal tabs ────────────────────────────────────────────────────────── */
.modal-tab-row {
  display: flex;
  background: #F2F5F3;
  border-bottom: 1px solid #E5E7EB;
}
.modal-tab {
  flex: 1; padding: 12px 8px;
  border: none; background: transparent;
  font-size: .8125rem; font-weight: 600; color: #6B7280;
  cursor: pointer; font-family: inherit;
  border-bottom: 2px solid transparent;
  transition: color .15s, border-color .15s;
}
.modal-tab.active {
  color: #1E5631;
  border-bottom-color: #1E5631;
  background: #fff;
}

/* Selector modal */
.search-box {
  display: flex; align-items: center; gap: 10px;
  background: #fff; border: 1.5px solid #E5E7EB;
  border-radius: 12px; padding: 10px 14px;
}

.pr-animal {
  font-weight: 700;
}

.pr-fecha {
  font-size: 0.8rem;
  color: #6b7280;
}

.pr-peso {
  font-weight: 800;
  color: #166534;
}

.animal-sel,
.animal-sel-opt {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #f9fafb;
  padding: 12px;
  border-radius: 12px;
  cursor: pointer;
}

.anim-emo {
  font-size: 1.5rem;
}

.anim-sel-info {
  flex: 1;
}

.anim-name {
  font-weight: 700;
}
.animal-sel-opt:hover { box-shadow: 0 2px 8px rgba(0,0,0,.08); }

/* ── Formulario nuevo animal ──────────────────────────────────────────── */
.nf-field {
  margin-bottom: 14px;
}
.nf-label {
  display: block;
  font-size: .8125rem; font-weight: 700; color: #374151;
  margin-bottom: 6px;
}
.req { color: #EF4444; }
.nf-input {
  width: 100%; padding: 11px 14px;
  border: 1.5px solid #E5E7EB; border-radius: 12px;
  font-size: .875rem; color: #111827; background: #F9FAFB;
  outline: none; font-family: inherit;
  transition: border-color .15s, background .15s;
  box-sizing: border-box;
}
.nf-input:focus { border-color: #1E5631; background: #fff; }
.nf-select { appearance: none; cursor: pointer; }
.nf-hint {
  font-size: .75rem; color: #6B7280; margin-top: 6px;
}
.nf-link {
  color: #1E5631; font-weight: 700; cursor: pointer; text-decoration: underline;
}

.nf-optional {
  font-weight: 400; color: #9CA3AF; font-size: .75rem;
}

.nf-divider {
  display: flex; align-items: center; gap: 10px;
  margin: 6px 0 14px;
  color: #9CA3AF; font-size: .6875rem; font-weight: 700;
  text-transform: uppercase; letter-spacing: .07em;
}
.nf-divider::before,
.nf-divider::after {
  content: ''; flex: 1; height: 1px; background: #E5E7EB;
}

.nf-peso-wrap {
  position: relative; display: flex; align-items: center;
}
.nf-peso-wrap .nf-input { padding-right: 42px; }
.nf-peso-unit {
  position: absolute; right: 14px;
  font-size: .875rem; font-weight: 700; color: #6B7280;
  pointer-events: none;
}
</style>
