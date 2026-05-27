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

          <div class="form-sec-title">
            Animal
          </div>

          <div
            class="animal-sel"
            @click="mostrarSelector = true"
          >

            <div class="anim-emo">
              {{ animalSel ? '🐄' : '➕' }}
            </div>

            <div class="anim-sel-info">

              <div class="anim-sel-name">
                {{
                  animalSel
                    ? (animalSel.nombre || `Arete ${animalSel.numero_arete}`)
                    : 'Seleccionar animal'
                }}
              </div>

              <div class="anim-sel-id">
                {{
                  animalSel
                    ? `#${animalSel.numero_arete}`
                    : 'Toca para elegir'
                }}
              </div>

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

      <!-- MODAL -->
      <ion-modal
        :is-open="mostrarSelector"
        @didDismiss="mostrarSelector = false"
      >

        <ion-content class="page-bg">

          <div class="app-bar">

            <div class="page-title">
              Seleccionar animal
            </div>

            <button
              class="icon-btn"
              @click="mostrarSelector = false"
            >
              ✕
            </button>

          </div>

          <div class="body-pad">

            <div class="search-box">

              <input
                v-model="busqueda"
                class="search-input"
                placeholder="Buscar..."
              />

            </div>

            <div
              v-for="a in animalesFiltrados"
              :key="a.id"
              class="animal-sel-opt"
              @click="elegirAnimal(a)"
            >

              <div class="anim-emo">
                🐄
              </div>

              <div>

                <div class="anim-name">
                  {{ a.nombre || 'Sin nombre' }}
                </div>

                <div class="anim-id">
                  #{{ a.numero_arete }}
                </div>

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

import { useRouter } from 'vue-router';

import {
  IonPage,
  IonContent,
  IonModal
} from '@ionic/vue';

import {
  getAnimales,
  getPesajes,
  crearPesaje,
  pesoNumerico,
  formatFecha,
  AnimalDto,
  PesajeDto
} from '@/services/api';

const router = useRouter();

const animales = ref<AnimalDto[]>([]);

const pesajes = ref<PesajeDto[]>([]);

const animalSel = ref<AnimalDto | null>(null);

const peso = ref(0);

const pesoReal = ref<number | null>(null);

const fecha = ref(
  new Date().toISOString().slice(0, 10)
);

const mostrarSelector = ref(false);

const busqueda = ref('');

const saving = ref(false);

const loadingPesajes = ref(true);

const success = ref(false);

const errorMsg = ref('');

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

function elegirAnimal(a: AnimalDto) {

  animalSel.value = a;

  mostrarSelector.value = false;

}

function resetForm() {

  animalSel.value = null;

  peso.value = 0;

  pesoReal.value = null;

  fecha.value =
    new Date()
      .toISOString()
      .slice(0, 10);

  errorMsg.value = '';

}

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

      animal_id: animalSel.value.id,

      peso: peso.value,

      peso_real:
        pesoReal.value || undefined,

      fecha: fecha.value

    });

    success.value = true;

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

onMounted(async () => {

  try {

    const [aData, pData] =
      await Promise.all([
        getAnimales(),
        getPesajes()
      ]);

    animales.value =
      aData.datos || [];

    pesajes.value =
      (pData.datos || [])
        .sort(
          (a, b) =>
            new Date(b.fecha).getTime() -
            new Date(a.fecha).getTime()
        )
        .slice(0, 10);

  } catch (e) {

    console.error(e);

  } finally {

    loadingPesajes.value = false;

  }

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

.weight-wrap,
.field-wrap {
  position: relative;
}

.weight-unit,
.field-suffix {
  position: absolute;
  right: 14px;
  top: 14px;
}

.adjust-row {
  display: flex;
  gap: 8px;
  margin-top: 10px;
}

.adj {
  flex: 1;
  padding: 10px;
}

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
  width: 100%;
  padding: 16px;
  border: none;
  border-radius: 14px;
  background: #1c5c38;
  color: white;
  font-weight: 700;
}

.sec-title {
  margin-top: 24px;
  margin-bottom: 14px;
  font-weight: 800;
}

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

.pr-info {
  flex: 1;
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

.anim-id {
  font-size: 0.8rem;
  color: #6b7280;
}

.search-box {
  margin-bottom: 14px;
}

.ia-banner {
  background: linear-gradient(135deg, #1c5c38, #2f855a);
  padding: 16px;
  border-radius: 16px;
  margin-bottom: 16px;
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.ia-left {
  display: flex;
  gap: 12px;
  align-items: center;
}

.ia-title {
  font-weight: 800;
}

.ia-sub {
  font-size: 0.8rem;
}

</style>