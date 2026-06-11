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

        <button class="reload-btn" @click="cargarAnimales">
          🔄
        </button>
      </div>

      <!-- SEARCH -->
      <div class="container">

        <div class="search-box">
          <input
            v-model="busqueda"
            type="text"
            placeholder="Buscar por nombre o arete..."
            class="search-input"
          />
        </div>

        <!-- ESTADOS -->
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

        <!-- LOADING -->
        <div v-if="loading" class="status loading">
          Cargando animales...
        </div>

        <!-- ERROR -->
        <div v-else-if="error" class="status error">
          {{ error }}
        </div>

        <!-- EMPTY -->
        <div
          v-else-if="animalesFiltrados.length === 0"
          class="status empty"
        >
          No hay animales registrados.
        </div>

        <!-- CARDS -->
        <div
          v-for="animal in animalesFiltrados"
          :key="animal.id"
          class="animal-card"
        >

          <div class="card-top">

            <div class="avatar">
              🐄
            </div>

            <div class="info">

            <div class="sec-title" style="margin-bottom:10px">Historial de pesajes</div>
            <div v-if="loadingHistorial" class="status-box status-loading">Cargando historial…</div>
            <div v-else-if="historialAnimal.length === 0" class="empty-state">Sin pesajes registrados.</div>
            <div v-else class="hist-list card">
              <div v-for="(p, i) in historialAnimal" :key="p.id" class="hist-row">
                <div class="hist-dot" :class="{ 'hist-dot-first': i === 0 }"></div>
                <div class="hist-date">{{ formatFecha(p.fecha) }} · {{ p.fuente?.nombre ?? 'Manual' }}</div>
                <div class="hist-w">{{ pesoNumerico(p).toFixed(0) }} kg</div>
                <div class="hist-d" :class="i < historialAnimal.length-1 ? diffClass(p, historialAnimal[i+1]) : ''">
                  {{ i < historialAnimal.length-1 ? diff(p, historialAnimal[i+1]) : '' }}
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
import { getAnimales, getHistorialAnimal, pesoNumerico, formatFecha, AnimalDto, PesajeDto } from '@/services/api';

const router           = useRouter();
const animales         = ref<AnimalDto[]>([]);
const loading          = ref(true);
const error            = ref('');
const busqueda         = ref('');
const filtroActivo     = ref('todos');
const animalSel        = ref<AnimalDto | null>(null);
const historialAnimal  = ref<PesajeDto[]>([]);
const loadingHistorial = ref(false);

const filtros = [
  { key: 'todos',  label: 'Todos' },
  { key: 'activo', label: 'Activos' },
  { key: 'otro',   label: 'Inactivos' },
];

import {
  ref,
  computed,
  onMounted
} from 'vue';

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

const animales = ref<AnimalDto[]>([]);

const loading = ref(true);

const error = ref('');

const busqueda = ref('');

const filtro = ref('todos');

const animalesFiltrados = computed(() => {

  return animales.value.filter(animal => {

    const texto = (
      animal.nombre ||
      animal.numero_arete
    )
      .toLowerCase()
      .includes(busqueda.value.toLowerCase());

    const estado =
      filtro.value === 'todos'
      || animal.estado === filtro.value;

    return texto && estado;
  });

});

async function cargarAnimales() {

  loading.value = true;

  error.value = '';

  try {

    const response = await getAnimales();

    animales.value = response.datos || [];

  } catch (e: any) {

    error.value =
      e.message ||
      'No se pudieron cargar los animales';

  } finally {

    loading.value = false;

  }

}

function calcularEdad(fecha: string | null): string {

  if (!fecha) {
    return '---';
  }

  const nacimiento = new Date(fecha);

  const hoy = new Date();

  const años = hoy.getFullYear() - nacimiento.getFullYear();

  return `${años} año(s)`;
}

onMounted(() => {

  cargarAnimales();

});

</script>

<style scoped>

.page-bg {
  --background: #f4f7f5;
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
  font-weight: 800;
  color: #1c3d2a;
}

.subtitle {
  margin-top: 4px;
  color: #6b7280;
  font-size: 0.85rem;
}

.reload-btn {
  border: none;
  background: #1c5c38;
  color: white;
  width: 42px;
  height: 42px;
  border-radius: 12px;
  font-size: 1rem;
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
  border: 1px solid #d1d5db;
  background: white;
  color: #111827;
  font-size: 0.95rem;
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
  font-weight: 700;
  color: #4b5563;
}

.filter-btn.active {
  background: #1c5c38;
  color: white;
}

.hist-row {
  display: flex; align-items: center; gap: 10px;
  padding: 8px 0; border-bottom: 1px solid #F3F4F6;
}
.hist-row:last-child { border-bottom: none; }
.hist-dot { width: 9px; height: 9px; border-radius: 50%; background: #74C69D; flex-shrink: 0; }
.hist-dot-first { background: #1E5631; width: 11px; height: 11px; }
.hist-date { font-size: .6875rem; color: #6B7280; flex: 1; }
.hist-w    { font-size: .8125rem; font-weight: 700; color: #111827; }
.hist-d    { font-size: .6875rem; font-weight: 600; width: 48px; text-align: right; }
.diff-up { color: #2D7A4A; }
.diff-dn { color: #EF4444; }

.btn-primary {
  width: 100%; padding: 15px;
  background: linear-gradient(135deg, #1E5631, #3A9E61);
  color: #fff; font-size: .9375rem; font-weight: 700;
  border: none; border-radius: 14px; cursor: pointer;
  box-shadow: 0 4px 14px rgba(30,86,49,.3);
  font-family: inherit; margin-top: 4px;
  display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-primary:hover { opacity: .92; }
</style>
