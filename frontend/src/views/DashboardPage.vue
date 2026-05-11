<template>
  <ion-page>
    <ion-header translucent>
      <ion-toolbar>
        <ion-title>Dashboard</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content fullscreen class="dashboard-content">
      <section class="hero-card">
        <div>
          <p class="welcome-label">Bienvenido a tu finca</p>
          <h1>Hola, {{ userName }}</h1>
          <p class="subtitle">Sigue el estado de tus animales y pesajes en una sola pantalla.</p>
        </div>
        <ion-chip class="status-chip">
          <ion-icon :icon="speedometerOutline" />
          <ion-label>Rápido y simple</ion-label>
        </ion-chip>
      </section>

      <section class="summary-grid">
        <ion-card class="summary-card card-green">
          <ion-card-header>
            <ion-card-title>Número de animales</ion-card-title>
            <ion-card-subtitle>{{ animalCount }}</ion-card-subtitle>
          </ion-card-header>
          <ion-card-content>
            Mantén el control de tu hato con un vistazo rápido.
          </ion-card-content>
        </ion-card>

        <ion-card class="summary-card card-blue">
          <ion-card-header>
            <ion-card-title>Últimos pesajes</ion-card-title>
            <ion-card-subtitle>{{ latestPesajes.length }} registros</ion-card-subtitle>
          </ion-card-header>
          <ion-card-content>
            Revisa los pesos recientes y detecta cambios rápidos.
          </ion-card-content>
        </ion-card>
      </section>

      <div v-if="error" class="status-message status-error">{{ error }}</div>
      <div v-else-if="loading" class="status-message status-loading">Cargando datos...</div>

      <section class="section-block">
        <div class="section-header">
          <div>
            <h2>Accesos rápidos</h2>
            <p>Ir directo a las funciones más usadas.</p>
          </div>
          <ion-button fill="outline" size="small" @click="goTo('pesajes')">
            Nuevo pesaje
          </ion-button>
        </div>

        <div class="action-grid">
          <ion-button class="quick-action" expand="block" fill="solid" @click="goTo('animales')">
            <ion-icon :icon="peopleOutline" slot="start" />
            Animales
          </ion-button>
          <ion-button class="quick-action" expand="block" fill="solid" @click="goTo('pesajes')">
            <ion-icon :icon="readerOutline" slot="start" />
            Pesajes
          </ion-button>
          <ion-button class="quick-action" expand="block" fill="solid" @click="goTo('reportes')">
            <ion-icon :icon="documentTextOutline" slot="start" />
            Reportes
          </ion-button>
        </div>
      </section>

      <section class="section-block latest-list">
        <div class="list-header">
          <h2>Últimos pesajes</h2>
          <ion-button fill="clear" size="small" @click="goTo('reportes')">
            Ver todos <ion-icon :icon="chevronForwardOutline" />
          </ion-button>
        </div>

        <ion-list>
          <ion-item v-for="item in latestPesajes" :key="item.id" button @click="goTo('pesajes')">
            <ion-label>
              <h3>{{ item.animal }}</h3>
              <p>{{ item.date }} · {{ item.weight }} kg</p>
            </ion-label>
            <ion-icon :icon="chevronForwardOutline" slot="end" />
          </ion-item>
          <ion-item v-if="latestPesajes.length === 0">
            <ion-label>No hay pesajes recientes. Registra el primero.</ion-label>
          </ion-item>
        </ion-list>
      </section>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonCard, IonCardHeader, IonCardTitle, IonCardSubtitle, IonCardContent, IonChip, IonIcon, IonLabel, IonButton, IonList, IonItem } from '@ionic/vue';
import { peopleOutline, readerOutline, documentTextOutline, speedometerOutline, chevronForwardOutline } from 'ionicons/icons';
import { getAnimales, getPesajes, AnimalDto, PesajeDto } from '@/services/api';

const router = useRouter();
const userName = ref('Usuario');
const loading = ref(true);
const error = ref('');
const animales = ref<AnimalDto[]>([]);
const pesajes = ref<PesajeDto[]>([]);

const animalCount = computed(() => animales.value.length);
const latestPesajes = computed(() => {
  return pesajes.value.slice(0, 3).map((item: PesajeDto) => {
    const animalName = item.animal?.nombre ?? `Animal ${item.animal_id}`;
    const rawWeight = item.peso_real ?? item.peso_estimado ?? 0;
    const weight = Number(rawWeight);
    return {
      id: item.id,
      animal: animalName,
      weight,
      date: formatDate(item.fecha)
    };
  });
});

const goTo = (path: string) => {
  router.push(`/tabs/${path}`);
};

function formatDate(value: string): string {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return new Intl.DateTimeFormat('es-CR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  }).format(date);
}

const loadDashboard = async () => {
  loading.value = true;
  error.value = '';

  try {
    const [animalsData, pesajesData] = await Promise.all([
      getAnimales(),
      getPesajes()
    ]);

    animales.value = animalsData;
    pesajes.value = pesajesData.datos || [];
  } catch (err) {
    console.error('Error loading dashboard data:', err);
    error.value = 'No se pudieron cargar los datos del servidor. Verifica la conexión.';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  const userData = localStorage.getItem('user');
  if (userData) {
    const user = JSON.parse(userData);
    userName.value = user.name || 'Usuario';
  }

  loadDashboard();
});
</script>

<style scoped>
.dashboard-content {
  padding: 18px 18px 24px;
  background: #f7fafc;
}
.hero-card {
  background: #ffffff;
  border-radius: 22px;
  padding: 20px;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
  display: flex;
  flex-direction: column;
  gap: 14px;
  margin-bottom: 20px;
}
.welcome-label {
  margin: 0 0 6px;
  font-weight: 700;
  color: #166534;
  letter-spacing: 0.03em;
}
.hero-card h1 {
  margin: 0;
  font-size: 1.85rem;
  line-height: 1.1;
  color: #0f172a;
}
.subtitle {
  margin: 0;
  color: #475569;
  line-height: 1.6;
}
.status-chip {
  align-self: flex-start;
  --background: #dcfce7;
  --color: #166534;
  font-weight: 700;
}
.summary-grid {
  display: grid;
  gap: 14px;
  margin-bottom: 20px;
}
.summary-card {
  border-radius: 18px;
  overflow: hidden;
}
.summary-card ion-card-header {
  padding: 18px;
}
.summary-card ion-card-content {
  padding: 0 18px 18px;
  color: #475569;
  font-size: 0.95rem;
}
.card-green {
  --background: #e9f7ef;
}
.card-blue {
  --background: #eff6ff;
}
.section-block {
  margin-bottom: 20px;
}
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}
.section-header h2 {
  margin: 0;
  font-size: 1.1rem;
  color: #0f172a;
}
.section-header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 0.95rem;
}
.action-grid {
  display: grid;
  gap: 12px;
}
.quick-action {
  --border-radius: 16px;
  font-weight: 700;
  justify-content: flex-start;
}
.latest-list ion-item {
  --inner-padding-start: 18px;
  --inner-padding-end: 18px;
  --background: #ffffff;
  margin-bottom: 8px;
  border-radius: 16px;
}
.list-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}
.list-header h2 {
  margin: 0;
  font-size: 1.1rem;
}
.list-header ion-button {
  color: #166534;
}
.status-message {
  padding: 14px 16px;
  border-radius: 16px;
  margin-bottom: 18px;
  font-weight: 700;
}
.status-loading {
  background: #f8fafc;
  color: #334155;
  border: 1px solid #cbd5e1;
}
.status-error {
  background: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecaca;
}
.latest-list ion-item h3 {
  margin: 0 0 4px;
  font-size: 1rem;
}
.latest-list ion-item p {
  margin: 0;
  color: #64748b;
}

@media (min-width: 640px) {
  .summary-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .action-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}
</style>
