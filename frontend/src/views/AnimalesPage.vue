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

              <p class="arete">
                #{{ animal.numero_arete }}
              </p>

              <h2 class="name">
                {{ animal.nombre || 'Sin nombre' }}
              </h2>

              <p class="raza">
                {{ animal.raza?.nombre || 'Sin raza' }}
              </p>

            </div>

            <div
              class="estado"
              :class="animal.estado"
            >
              {{ animal.estado }}
            </div>

          </div>

          <div class="card-bottom">

            <div class="metric">
              <span class="metric-label">
                Finca
              </span>

              <span class="metric-value">
                {{ animal.finca?.nombre || '---' }}
              </span>
            </div>

            <div class="metric">
              <span class="metric-label">
                Edad
              </span>

              <span class="metric-value">
                {{ calcularEdad(animal.fecha_nacimiento) }}
              </span>
            </div>

          </div>

        </div>

      </div>

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
  IonPage,
  IonContent
} from '@ionic/vue';

import {
  getAnimales,
  AnimalDto
} from '@/services/api';

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

.status {
  padding: 16px;
  border-radius: 14px;
  margin-bottom: 16px;
  text-align: center;
  font-weight: 600;
}

.loading {
  background: #e5f3ea;
  color: #1c5c38;
}

.error {
  background: #fee2e2;
  color: #b91c1c;
}

.empty {
  background: white;
  color: #6b7280;
}

.animal-card {
  background: white;
  border-radius: 18px;
  padding: 16px;
  margin-bottom: 14px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.card-top {
  display: flex;
  align-items: center;
}

.avatar {
  width: 60px;
  height: 60px;
  border-radius: 14px;
  background: #e6f4ea;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.8rem;
}

.info {
  flex: 1;
  margin-left: 14px;
}

.arete {
  margin: 0;
  font-size: 0.8rem;
  color: #6b7280;
}

.name {
  margin: 2px 0;
  font-size: 1rem;
  font-weight: 800;
  color: #111827;
}

.raza {
  margin: 0;
  font-size: 0.85rem;
  color: #4b5563;
}

.estado {
  padding: 6px 12px;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 700;
}

.estado.activo {
  background: #dcfce7;
  color: #166534;
}

.estado.inactivo {
  background: #fee2e2;
  color: #991b1b;
}

.card-bottom {
  display: flex;
  justify-content: space-between;
  margin-top: 16px;
  padding-top: 14px;
  border-top: 1px solid #f3f4f6;
}

.metric {
  display: flex;
  flex-direction: column;
}

.metric-label {
  font-size: 0.7rem;
  color: #6b7280;
}

.metric-value {
  font-size: 0.9rem;
  font-weight: 700;
  color: #111827;
}

</style>