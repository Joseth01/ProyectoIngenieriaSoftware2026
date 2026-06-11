<template>
  <ion-page>
    <ion-content :fullscreen="true" class="page-bg">

      <!-- HEADER -->
      <div class="header">
        <div>
          <h1 class="title">Mis animales</h1>
          <p class="subtitle">
            {{ animalesFiltrados.length }} registrados
          </p>
        </div>
        <button class="reload-btn" @click="cargarAnimales">🔄</button>
      </div>

      <!-- SEARCH + FILTROS -->
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
          >Todos</button>
          <button
            class="filter-btn"
            :class="{ active: filtro === 'activo' }"
            @click="filtro = 'activo'"
          >Activos</button>
          <button
            class="filter-btn"
            :class="{ active: filtro === 'inactivo' }"
            @click="filtro = 'inactivo'"
          >Inactivos</button>
        </div>

        <!-- LOADING -->
        <div v-if="loading" class="status loading">Cargando animales...</div>

        <!-- ERROR -->
        <div v-else-if="error" class="status error">{{ error }}</div>

        <!-- EMPTY -->
        <div v-else-if="animalesFiltrados.length === 0" class="status empty">
          No hay animales registrados.
        </div>

        <!-- CARDS -->
        <div
          v-else
          v-for="animal in animalesFiltrados"
          :key="animal.id"
          class="animal-card"
          @click="abrirAnimal(animal)"
        >
          <div class="card-top">
            <div class="avatar">🐄</div>
            <div class="info">
              <div class="animal-name">{{ animal.nombre }}</div>
              <div class="animal-meta">Arete: {{ animal.numero_arete }}</div>
              <div class="animal-meta">{{ animal.raza?.nombre ?? 'Sin raza' }} · {{ calcularEdad(animal.fecha_nacimiento) }}</div>
            </div>
            <div class="card-right">
              <span class="tag" :class="estadoTag(animal).cls">{{ estadoTag(animal).txt }}</span>
              <div class="ultimo-peso">{{ ultimoPeso(animal) }}</div>
            </div>
          </div>
        </div>

      </div>

      <!-- MODAL HISTORIAL -->
      <ion-modal :is-open="animalSel !== null" @did-dismiss="animalSel = null">
        <ion-content class="modal-content">

          <div class="modal-header">
            <h2 class="modal-title">{{ animalSel?.nombre }}</h2>
            <button class="close-btn" @click="animalSel = null">✕</button>
          </div>

          <div class="modal-body">
            <div class="sec-title">Historial de pesajes</div>

            <div v-if="loadingHistorial" class="status-box status-loading">Cargando historial…</div>

            <div v-else-if="historialAnimal.length === 0" class="empty-state">
              Sin pesajes registrados.
            </div>

            <div v-else class="hist-list">
              <div v-for="(p, i) in historialAnimal" :key="p.id" class="hist-row">
                <div class="hist-dot" :class="{ 'hist-dot-first': i === 0 }"></div>
                <div class="hist-date">{{ formatFecha(p.fecha) }} · {{ p.fuente?.nombre ?? 'Manual' }}</div>
                <div class="hist-w">{{ pesoNumerico(p).toFixed(0) }} kg</div>
                <div
                  class="hist-d"
                  :class="i < historialAnimal.length - 1 ? diffClass(p, historialAnimal[i + 1]) : ''"
                >
                  {{ i < historialAnimal.length - 1 ? diff(p, historialAnimal[i + 1]) : '' }}
                </div>
              </div>
            </div>

            <button class="btn-primary" @click="registrarPeso">⚖️ Registrar nuevo pesaje</button>
          </div>

        </ion-content>
      </ion-modal>

    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { IonPage, IonContent, IonModal } from '@ionic/vue';
import {
  getAnimales,
  getHistorialAnimal,
  pesoNumerico,
  formatFecha,
  AnimalDto,
  PesajeDto
} from '@/services/api';

const router           = useRouter();
const animales         = ref<AnimalDto[]>([]);
const loading          = ref(true);
const error            = ref('');
const busqueda         = ref('');
const filtro           = ref('todos');
const animalSel        = ref<AnimalDto | null>(null);
const historialAnimal  = ref<PesajeDto[]>([]);
const loadingHistorial = ref(false);

const animalesFiltrados = computed(() =>
  animales.value.filter(animal => {
    const texto = (animal.nombre + ' ' + animal.numero_arete)
      .toLowerCase()
      .includes(busqueda.value.toLowerCase());
    const estado =
      filtro.value === 'todos' || animal.estado === filtro.value;
    return texto && estado;
  })
);

function estadoTag(a: AnimalDto) {
  if (a.estado === 'activo') return { cls: 'tag-g', txt: '✓ Activo' };
  return { cls: 'tag-r', txt: '✗ Inactivo' };
}

function ultimoPeso(a: AnimalDto): string {
  const pesajes = a.pesajes;
  if (!pesajes || pesajes.length === 0) return '— kg';
  const ultimo = [...pesajes].sort(
    (x, y) => new Date(y.fecha).getTime() - new Date(x.fecha).getTime()
  )[0];
  return `${pesoNumerico(ultimo).toFixed(0)} kg`;
}

function calcularEdad(fecha: string | null | undefined): string {
  if (!fecha) return '---';
  const nacimiento = new Date(fecha);
  const hoy = new Date();
  const meses =
    (hoy.getFullYear() - nacimiento.getFullYear()) * 12 +
    (hoy.getMonth() - nacimiento.getMonth());
  if (meses < 12) return `${meses} mes(es)`;
  return `${Math.floor(meses / 12)} año(s)`;
}

function diff(actual: PesajeDto, anterior: PesajeDto): string {
  const d = pesoNumerico(actual) - pesoNumerico(anterior);
  return d >= 0 ? `+${d.toFixed(0)}` : `${d.toFixed(0)}`;
}

function diffClass(actual: PesajeDto, anterior: PesajeDto): string {
  return pesoNumerico(actual) >= pesoNumerico(anterior) ? 'diff-up' : 'diff-dn';
}

async function abrirAnimal(animal: AnimalDto) {
  animalSel.value = animal;
  loadingHistorial.value = true;
  historialAnimal.value = [];
  try {
    const res = await getHistorialAnimal(animal.id);
    historialAnimal.value = res.datos?.pesajes ?? [];
  } catch {
    historialAnimal.value = [];
  } finally {
    loadingHistorial.value = false;
  }
}

function registrarPeso() {
  animalSel.value = null;
  router.push('/tabs/pesaje-vivo');
}

async function cargarAnimales() {
  loading.value = true;
  error.value = '';
  try {
    const response = await getAnimales();
    animales.value = response.datos || [];
  } catch (e: any) {
    error.value = e.message || 'No se pudieron cargar los animales';
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  cargarAnimales();
});
</script>

<style scoped>
.page-bg { --background: #f4f7f5; }

.header {
  padding: 22px 20px 10px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.title { margin: 0; font-size: 1.8rem; font-weight: 800; color: #1c3d2a; }
.subtitle { margin-top: 4px; color: #6b7280; font-size: 0.85rem; }
.reload-btn {
  border: none; background: #1c5c38; color: white;
  width: 42px; height: 42px; border-radius: 12px; font-size: 1rem;
}

.container { padding: 16px; }

.search-box { margin-bottom: 14px; }
.search-input {
  width: 100%; padding: 14px; border-radius: 14px;
  border: 1px solid #d1d5db; background: white;
  color: #111827; font-size: 0.95rem;
}

.filters { display: flex; gap: 10px; margin-bottom: 18px; }
.filter-btn {
  border: none; background: white;
  padding: 10px 16px; border-radius: 999px;
  font-weight: 700; color: #4b5563;
}
.filter-btn.active { background: #1c5c38; color: white; }

.status { padding: 20px; text-align: center; border-radius: 14px; }
.loading { background: #f3f4f6; color: #6b7280; }
.error   { background: #fef2f2; color: #dc2626; }
.empty   { background: #f3f4f6; color: #6b7280; }

.animal-card {
  background: white; border-radius: 16px;
  padding: 16px; margin-bottom: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,.06);
  cursor: pointer;
}
.card-top { display: flex; align-items: center; gap: 14px; }
.avatar {
  width: 48px; height: 48px; border-radius: 14px;
  background: linear-gradient(135deg,#e8f5ee,#c6e8d4);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.4rem; flex-shrink: 0;
}
.info { flex: 1; min-width: 0; }
.animal-name { font-size: .9375rem; font-weight: 800; color: #111827; }
.animal-meta { font-size: .75rem; color: #6b7280; margin-top: 2px; }

.card-right { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; }
.tag {
  font-size: .6875rem; font-weight: 700; padding: 3px 8px;
  border-radius: 999px;
}
.tag-g { background: #d1fae5; color: #065f46; }
.tag-r { background: #fee2e2; color: #991b1b; }
.ultimo-peso { font-size: .8125rem; font-weight: 700; color: #1c5c38; }

/* Modal */
.modal-content { --background: #f4f7f5; }
.modal-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 20px 20px 10px;
}
.modal-title { margin: 0; font-size: 1.2rem; font-weight: 800; color: #1c3d2a; }
.close-btn {
  border: none; background: #e5e7eb; color: #374151;
  width: 36px; height: 36px; border-radius: 50%; font-size: 1rem;
}
.modal-body { padding: 0 20px 30px; }
.sec-title { font-size: .875rem; font-weight: 700; color: #6b7280; margin-bottom: 12px; text-transform: uppercase; letter-spacing: .05em; }

.status-box { padding: 16px; border-radius: 12px; text-align: center; }
.status-loading { background: #f3f4f6; color: #6b7280; }
.empty-state { color: #9ca3af; text-align: center; padding: 20px 0; }

.hist-list { background: white; border-radius: 14px; padding: 8px 14px; margin-bottom: 16px; }
.hist-row {
  display: flex; align-items: center; gap: 10px;
  padding: 8px 0; border-bottom: 1px solid #f3f4f6;
}
.hist-row:last-child { border-bottom: none; }
.hist-dot { width: 9px; height: 9px; border-radius: 50%; background: #74c69d; flex-shrink: 0; }
.hist-dot-first { background: #1e5631; width: 11px; height: 11px; }
.hist-date { font-size: .6875rem; color: #6b7280; flex: 1; }
.hist-w    { font-size: .8125rem; font-weight: 700; color: #111827; }
.hist-d    { font-size: .6875rem; font-weight: 600; width: 48px; text-align: right; }
.diff-up { color: #2d7a4a; }
.diff-dn { color: #ef4444; }

.btn-primary {
  width: 100%; padding: 15px;
  background: linear-gradient(135deg,#1e5631,#3a9e61);
  color: #fff; font-size: .9375rem; font-weight: 700;
  border: none; border-radius: 14px; cursor: pointer;
  box-shadow: 0 4px 14px rgba(30,86,49,.3);
  font-family: inherit; margin-top: 4px;
  display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-primary:hover { opacity: .92; }
</style>
