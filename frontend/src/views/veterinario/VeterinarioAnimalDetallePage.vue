<template>
  <ion-page>
    <ion-content :fullscreen="true" class="detail-content">
      <div class="page">

        <header class="topbar">
          <button class="back-btn" @click="volver">
            ‹ Volver
          </button>

          <div>
            <p class="eyebrow">Seguimiento veterinario</p>
            <h1>{{ animal?.nombre || 'Detalle del animal' }}</h1>
            <p>
              Historial de peso y datos clínicos de referencia.
            </p>
          </div>
        </header>

        <section v-if="loading" class="state-card">
          <div class="loader"></div>
          <p>Cargando historial del animal…</p>
        </section>

        <section v-else-if="errorMsg" class="state-card error">
          <p>{{ errorMsg }}</p>
          <button class="primary-btn" @click="cargarAnimal">
            Reintentar
          </button>
        </section>

        <template v-else-if="animal">
          <section class="animal-hero">
            <div class="animal-icon">
              🐮
            </div>

            <div class="animal-main">
              <p class="eyebrow">Animal asignado</p>
              <h2>{{ animal.nombre }}</h2>
              <p>
                Arete {{ animal.numero_arete }}
              </p>

              <div class="tag-row">
                <span class="tag">
                  {{ animal.raza?.nombre || 'Raza no registrada' }}
                </span>

                <span class="tag">
                  {{ animal.finca?.nombre || 'Finca no registrada' }}
                </span>

                <span class="tag">
                  {{ animal.estado || 'Sin estado' }}
                </span>
              </div>
            </div>
          </section>

          <section class="stats-grid">
            <article class="stat-card">
              <p>Edad aproximada</p>
              <strong>{{ calcularEdad(animal.fecha_nacimiento) }}</strong>
            </article>

            <article class="stat-card">
              <p>Último peso</p>
              <strong>{{ ultimoPesoTexto }}</strong>
            </article>

            <article class="stat-card">
              <p>Cambio estimado</p>
              <strong>{{ cambioPesoTexto }}</strong>
            </article>
          </section>

          <section class="notice-card">
            <strong>Nota veterinaria</strong>
            <p>
              Los pesos pueden provenir de estimaciones por imagen o registros manuales.
              Para tratamientos sensibles, confirme con báscula cuando sea necesario.
            </p>
          </section>

          <section class="section-card">
            <div class="section-head">
              <div>
                <p class="section-kicker">Historial</p>
                <h3>Pesajes registrados</h3>
              </div>
            </div>

            <div v-if="pesajesOrdenados.length === 0" class="empty">
              Este animal todavía no tiene pesajes registrados.
            </div>

            <div v-else class="timeline">
              <article
                v-for="pesaje in pesajesOrdenados"
                :key="pesaje.id"
                class="timeline-item"
              >
                <div class="dot"></div>

                <div class="timeline-card">
                  <div class="timeline-head">
                    <h4>{{ pesoTexto(pesaje) }}</h4>
                    <span>{{ formatFecha(pesaje.fecha) }}</span>
                  </div>

                  <p>
                    Fuente:
                    <strong>
                      {{ pesaje.fuente?.nombre || 'No registrada' }}
                    </strong>
                  </p>

                  <small v-if="pesaje.peso_real">
                    Peso real registrado: {{ Number(pesaje.peso_real).toFixed(1) }} kg
                  </small>

                  <small v-else>
                    Peso estimado registrado: {{ Number(pesaje.peso_estimado).toFixed(1) }} kg
                  </small>
                </div>
              </article>
            </div>
          </section>
        </template>

      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
  IonPage,
  IonContent,
  onIonViewWillEnter,
} from '@ionic/vue';

import {
  getVeterinarioAnimalDetalle,
  calcularEdad,
  formatFecha,
  pesoNumerico,
  type AnimalDto,
  type PesajeDto,
} from '@/services/api';

const route = useRoute();
const router = useRouter();

const animal = ref<AnimalDto | null>(null);
const loading = ref(true);
const errorMsg = ref('');

const pesajesOrdenados = computed(() => {
  const lista = animal.value?.pesajes || [];

  return [...lista].sort((a, b) => {
    const fechaA = new Date(a.fecha || a.created_at || '').getTime();
    const fechaB = new Date(b.fecha || b.created_at || '').getTime();

    return fechaB - fechaA;
  });
});

const ultimoPesoTexto = computed(() => {
  const ultimo = pesajesOrdenados.value[0];

  if (!ultimo) {
    return '---';
  }

  return `${pesoNumerico(ultimo).toFixed(1)} kg`;
});

const cambioPesoTexto = computed(() => {
  const lista = pesajesOrdenados.value;

  if (lista.length < 2) {
    return 'Sin comparar';
  }

  const ultimo = pesoNumerico(lista[0]);
  const anterior = pesoNumerico(lista[1]);
  const diferencia = ultimo - anterior;

  if (diferencia === 0) {
    return '0.0 kg';
  }

  const signo = diferencia > 0 ? '+' : '';

  return `${signo}${diferencia.toFixed(1)} kg`;
});

function pesoTexto(pesaje: PesajeDto): string {
  return `${pesoNumerico(pesaje).toFixed(1)} kg`;
}

async function cargarAnimal() {
  loading.value = true;
  errorMsg.value = '';

  const id = Number(route.params.id);

  if (!id) {
    errorMsg.value = 'No se recibió el ID del animal.';
    loading.value = false;
    return;
  }

  try {
    const resp = await getVeterinarioAnimalDetalle(id);
    animal.value = resp.datos;
  } catch (error: unknown) {
    errorMsg.value = error instanceof Error
      ? error.message
      : 'No se pudo cargar el detalle del animal.';
  } finally {
    loading.value = false;
  }
}

function volver() {
  router.replace('/veterinario');
}

onMounted(() => {
  cargarAnimal();
});

onIonViewWillEnter(() => {
  cargarAnimal();
});
</script>

<style scoped>
.detail-content {
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
  padding: 22px;
  display: flex;
  gap: 18px;
  align-items: center;
  box-shadow: 0 18px 45px rgba(18, 50, 31, .25);
}

.back-btn,
.primary-btn {
  border: none;
  cursor: pointer;
  font-family: inherit;
  font-weight: 800;
}

.back-btn {
  background: rgba(255,255,255,.16);
  color: white;
  border: 1px solid rgba(255,255,255,.22);
  border-radius: 14px;
  padding: 10px 14px;
}

.eyebrow,
.section-kicker {
  margin: 0;
  font-size: .72rem;
  text-transform: uppercase;
  letter-spacing: .12em;
  opacity: .7;
  font-weight: 900;
}

.topbar h1 {
  margin: 5px 0;
  font-size: 1.8rem;
}

.topbar p {
  margin: 0;
  color: rgba(255,255,255,.78);
}

.animal-hero,
.section-card,
.notice-card,
.state-card {
  background: white;
  border-radius: 24px;
  padding: 22px;
  margin-top: 18px;
  box-shadow: 0 10px 30px rgba(15, 35, 22, .08);
}

.animal-hero {
  display: flex;
  gap: 18px;
  align-items: center;
}

.animal-icon {
  width: 76px;
  height: 76px;
  border-radius: 24px;
  background: #e9f8ef;
  display: grid;
  place-items: center;
  font-size: 2.1rem;
}

.animal-main h2 {
  margin: 5px 0;
  color: #12321f;
}

.animal-main p {
  margin: 0;
  color: #66736b;
}

.tag-row {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}

.tag {
  background: #e9f8ef;
  color: #1d6b3c;
  padding: 7px 10px;
  border-radius: 999px;
  font-size: .75rem;
  font-weight: 800;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 14px;
  margin-top: 18px;
}

.stat-card {
  background: white;
  border-radius: 22px;
  padding: 18px;
  box-shadow: 0 10px 30px rgba(15, 35, 22, .08);
}

.stat-card p {
  margin: 0 0 8px;
  color: #66736b;
  font-weight: 800;
}

.stat-card strong {
  color: #12321f;
  font-size: 1.2rem;
}

.notice-card {
  background: #fff9e8;
  border: 1px solid #ffe3a3;
}

.notice-card strong {
  color: #7a4d00;
}

.notice-card p {
  margin: 6px 0 0;
  color: #7a4d00;
  line-height: 1.45;
}

.section-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.section-head h3 {
  margin: 4px 0 0;
  color: #12321f;
}

.timeline {
  display: grid;
  gap: 14px;
}

.timeline-item {
  display: grid;
  grid-template-columns: 18px 1fr;
  gap: 10px;
  align-items: flex-start;
}

.dot {
  width: 13px;
  height: 13px;
  margin-top: 18px;
  border-radius: 50%;
  background: #2c7a4a;
  box-shadow: 0 0 0 5px #e9f8ef;
}

.timeline-card {
  border: 1px solid #e6ece8;
  background: #fbfdfb;
  border-radius: 18px;
  padding: 15px;
}

.timeline-head {
  display: flex;
  justify-content: space-between;
  gap: 12px;
}

.timeline-head h4 {
  margin: 0;
  color: #12321f;
  font-size: 1.15rem;
}

.timeline-head span {
  color: #66736b;
  font-size: .85rem;
  font-weight: 800;
}

.timeline-card p {
  margin: 8px 0 4px;
  color: #66736b;
}

.timeline-card small {
  color: #829086;
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

.primary-btn {
  background: linear-gradient(135deg, #12321f, #2c7a4a);
  color: white;
  border-radius: 14px;
  padding: 12px 16px;
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
  .animal-hero,
  .timeline-head {
    flex-direction: column;
    align-items: stretch;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }
}
</style>