<template>
  <ion-page class="bovweight-screen">
    <ion-header class="ion-no-border header-clean">
      <div class="top-nav-bar">
        <ion-icon :icon="menuOutline" class="nav-icon-menu"></ion-icon>
        <h1 class="nav-title">Historial</h1>
        <div class="brand-logo-container"><span class="brand-green">BovWeight</span><span class="brand-dark">CR</span></div>
        <img src="https://ionicframework.com/docs/img/demos/avatar.svg" class="user-avatar" alt="Perfil" />
      </div>

      <div class="search-section">
        <div class="search-input-wrapper">
          <ion-icon :icon="searchOutline" class="search-magnifier"></ion-icon>
          <input type="text" placeholder="Buscar por ID o lote..." v-model="searchQuery" @input="filterAnimals" class="clean-input"/>
        </div>
        <div class="pill-filters">
          <button v-for="status in ['Todos', 'Analizado', 'Vendido']" :key="status" :class="['pill-btn', { 'pill-active': filterStatus === status }]" @click="setStatusFilter(status)">{{ status }}</button>
        </div>
      </div>
    </ion-header>

    <ion-content class="scroll-content">
      <div class="cards-stack">
        <div v-for="animal in filteredAnimals" :key="animal.id" class="bovweight-card" @click="verDetallesAnimal(animal)">
          <div class="card-main-row">
            <div class="placeholder-image"><ion-icon :icon="imageOutline" class="img-icon-inside"></ion-icon></div>
            <div class="card-center-body">
              <h2 class="weight-display">{{ animal.peso }} KG</h2>
              <p class="animal-code-text">Bovino #{{ animal.numero_arete }}</p>
              <p v-if="animal.nombre" class="animal-name-sub">📌 {{ animal.nombre }}</p>
              <p class="animal-breed-sub">🐄 Raza: {{ animal.raza }}</p>
            </div>
            <div class="card-right-aside">
              <span :class="['pill-badge', animal.estado.toLowerCase()]">{{ animal.estado.toUpperCase() }}</span>
              <p class="date-display-text">{{ animal.fecha_nacimiento }}</p>
            </div>
          </div>
          
          <div class="card-action-bar">
            <div class="short-buttons-container">
              <button class="btn-action btn-edit" @click.stop="openEditModal(animal)">
                <ion-icon :icon="createOutline" class="btn-icon"></ion-icon>Editar
              </button>
              <button class="btn-action btn-delete" @click.stop="confirmDelete(animal)">
                <ion-icon :icon="trashOutline" class="btn-icon"></ion-icon>Eliminar
              </button>
            </div>
          </div>
        </div>
        
        <div v-if="filteredAnimals.length === 0" class="no-results">
          No se encontraron bovinos.
        </div>
      </div>

      <div class="summary-box-month">
        <div class="summary-text-side">
          <p class="summary-title-label">TOTAL ANALIZADO (MES)</p>
          <h3 class="summary-total-weight">2.783 KG</h3>
        </div>
        <div class="summary-chart-icon-box"><ion-icon :icon="trendingUpOutline" class="chart-mini-icon"></ion-icon></div>
      </div>

      <ion-fab vertical="bottom" horizontal="end" slot="fixed">
        <ion-fab-button class="custom-fab" @click="openCreateModal">
          <ion-icon :icon="add"></ion-icon>
        </ion-fab-button>
      </ion-fab>

      <ion-modal :is-open="isModalOpen" @didDismiss="closeModal" class="form-modal-view">
        <ion-header class="ion-no-border">
          <ion-toolbar class="modal-toolbar">
            <ion-title>{{ isEditing ? 'Editar Datos del Bovino' : 'Registrar Nuevo Animal' }}</ion-title>
            <ion-buttons slot="end">
              <ion-button color="danger" @click="closeModal">Cerrar</ion-button>
            </ion-buttons>
          </ion-toolbar>
        </ion-header>

        <ion-content class="ion-padding modal-form-content">
          <div class="form-container">
            <div class="input-group">
              <label class="form-label">Número de Arete *</label>
              <input type="text" v-model="newAnimal.numero_arete" placeholder="Ej: 8421" class="form-input" />
            </div>

            <div class="input-group">
              <label class="form-label">Nombre / Alias</label>
              <input type="text" v-model="newAnimal.nombre" placeholder="Ej: Mariposa" class="form-input" />
            </div>

            <div class="input-group">
              <label class="form-label">Raza *</label>
              <ion-select v-model="newAnimal.raza" placeholder="Seleccione la raza" class="form-select" interface="popover">
                <ion-select-option value="Brahman">Brahman</ion-select-option>
                <ion-select-option value="Holstein">Holstein</ion-select-option>
                <ion-select-option value="Jersey">Jersey</ion-select-option>
                <ion-select-option value="Pardo Suizo">Pardo Suizo</ion-select-option>
              </ion-select>
            </div>

            <div class="input-group">
              <label class="form-label">Fecha de Nacimiento *</label>
              <ion-datetime-button datetime="datetime" class="custom-date-btn"></ion-datetime-button>
              <ion-modal :keep-contents-mounted="true">
                <ion-datetime id="datetime" presentation="date" v-model="selectedDate" @ionChange="handleDateChange"></ion-datetime>
              </ion-modal>
            </div>

            <button class="save-animal-btn" @click="saveAnimal">
              {{ isEditing ? 'Actualizar Cambios' : 'Guardar en el Hato' }}
            </button>
          </div>
        </ion-content>
      </ion-modal>

      <ion-modal :is-open="isDetailModalOpen" @didDismiss="isDetailModalOpen = false" class="full-screen-modal">
        <ion-header class="ion-no-border header-detail-clean">
          <div class="top-nav-bar-detail">
            <button class="btn-back-clean" @click="isDetailModalOpen = false">
              <ion-icon :icon="arrowBackOutline"></ion-icon>
            </button>
            <div class="brand-logo-container-detail">
              <span class="brand-green">BovWeight</span>
              <span class="brand-dark">CR</span>
            </div>
            <button class="btn-notification-clean">
              <ion-icon :icon="notificationsOutline"></ion-icon>
            </button>
          </div>
        </ion-header>

        <ion-content class="ion-padding detail-content">
          <div class="detail-wrapper-max">
            <div class="animal-image-container">
              <img src="https://images.unsplash.com/photo-1570042225831-d98fa7577f1e?q=80&w=600" alt="Bovino" />
            </div>

            <div class="animal-header-info ion-margin-top">
              <div class="id-status-row">
                <h2>#{{ selectedAnimal?.numero_arete || '1204' }}</h2>
                <span class="status-badge">{{ selectedAnimal?.estado ? selectedAnimal.estado.toUpperCase() : 'ANALIZADO' }}</span>
              </div>
              <p v-if="selectedAnimal?.nombre" class="animal-name-detail">📌 Alias: {{ selectedAnimal.nombre }}</p>
              <p class="animal-breed">{{ selectedAnimal?.raza || 'Brahman' }} (Puro)</p>
            </div>

            <div class="mini-grid ion-margin-vertical">
              <div class="mini-card">
                <span class="mini-label">EDAD</span>
                <span class="mini-value">24 Meses</span>
              </div>
              <div class="mini-card">
                <span class="mini-label">LOTE</span>
                <span class="mini-value">Sector Norte-B</span>
              </div>
            </div>

            <div class="main-weight-card">
              <span class="weight-card-title">PESO ESTIMADO (NO OFICIAL)</span>
              <h1 class="weight-display-centered">
                {{ selectedAnimal?.peso !== '---' ? selectedAnimal?.peso : '482.5' }} <small>kg</small>
              </h1>
              <div class="trend-row-centered">
                <ion-icon :icon="trendingUpOutline"></ion-icon>
                <span>+12kg desde el último pesaje</span>
              </div>
            </div>

            <div class="growth-section ion-margin-vertical">
              <div class="section-header-row">
                <h3>Tendencia de Crecimiento</h3>
                <span class="filter-time-badge">Últimos 6 meses</span>
              </div>
              
              <div class="mock-chart-container-clean">
                <div class="mock-chart-bars-compact">
                  <div class="bar" style="--height: 35%"></div>
                  <div class="bar" style="--height: 48%"></div>
                  <div class="bar" style="--height: 60%"></div>
                  <div class="bar" style="--height: 72%"></div>
                  <div class="bar" style="--height: 85%"></div>
                  <div class="bar active-bar" style="--height: 100%"></div>
                </div>
              </div>
            </div>

            <div class="history-section">
              <h3>Historial de Pesajes</h3>
              <ion-list lines="none" class="transparent-list">
                <ion-item class="history-item-card">
                  <ion-avatar slot="start" class="history-icon-avatar">
                    <ion-icon :icon="calendarOutline"></ion-icon>
                  </ion-avatar>
                  <ion-label>
                    <h2>{{ selectedAnimal?.fecha_nacimiento || '12 Mayo, 2026' }}</h2>
                    <p>Metodología: Estimación Visual AI (177 PT / 198 LT)</p>
                  </ion-label>
                  <slot slot="end">
                    <span class="history-weight-value">
                      {{ selectedAnimal?.peso !== '---' ? selectedAnimal?.peso : '482.5' }} kg
                    </span>
                  </slot>
                </ion-item>
              </ion-list>
            </div>
          </div>
        </ion-content>
      </ion-modal>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { 
  menuOutline, searchOutline, imageOutline, trendingUpOutline, add,
  createOutline, trashOutline, arrowBackOutline, notificationsOutline,
  calendarOutline 
} from 'ionicons/icons';
import { 
  IonPage, IonHeader, IonContent, IonIcon, IonFab, IonFabButton,
  IonModal, IonToolbar, IonTitle, IonButtons, IonButton,
  IonSelect, IonSelectOption, IonDatetime, IonDatetimeButton, alertController,
  IonList, IonItem, IonAvatar, IonLabel
} from '@ionic/vue';

const allAnimals = ref([
  { id: 1, numero_arete: '8421', nombre: 'Mariposa', raza: 'Brahman', estado: 'Analizado', peso: '572', fecha_nacimiento: '24 May, 2024' },
  { id: 2, numero_arete: '9011', nombre: 'Clavel', raza: 'Holstein', estado: 'Vendido', peso: '615', fecha_nacimiento: '22 May, 2024' },
  { id: 3, numero_arete: '7721', nombre: '', raza: 'Jersey', estado: 'Analizado', peso: '488', fecha_nacimiento: '19 May, 2024' },
  { id: 4, numero_arete: '8530', nombre: 'Trueno', raza: 'Pardo Suizo', estado: 'Analizado', peso: '594', fecha_nacimiento: '15 May, 2024' },
  { id: 5, numero_arete: '9122', nombre: '', raza: 'Holstein', estado: 'Vendido', peso: '512', fecha_nacimiento: '10 May, 2024' }
]);

const filteredAnimals = ref([...allAnimals.value]);
const searchQuery = ref('');
const filterStatus = ref('Todos');

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingAnimalId = ref<number | null>(null);
const selectedDate = ref(new Date().toISOString());

const newAnimal = ref({ numero_arete: '', nombre: '', raza: '', fecha_nacimiento: '16 May, 2026' });

const isDetailModalOpen = ref(false);
const selectedAnimal = ref<any>(null);

const openCreateModal = () => {
  isEditing.value = false;
  editingAnimalId.value = null;
  newAnimal.value = { numero_arete: '', nombre: '', raza: '', fecha_nacimiento: '16 May, 2026' };
  isModalOpen.value = true;
};

const openEditModal = (animal: any) => {
  isEditing.value = true;
  editingAnimalId.value = animal.id;
  newAnimal.value = { numero_arete: animal.numero_arete, nombre: animal.nombre, raza: animal.raza, fecha_nacimiento: animal.fecha_nacimiento };
  isModalOpen.value = true;
};

const closeModal = () => { isModalOpen.value = false; };
const verDetallesAnimal = (animal: any) => { selectedAnimal.value = animal; isDetailModalOpen.value = true; };

const handleDateChange = (event: any) => {
  const date = new Date(event.detail.value);
  const options: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'short', year: 'numeric' };
  newAnimal.value.fecha_nacimiento = date.toLocaleDateString('es-ES', options);
};

const saveAnimal = () => {
  if (!newAnimal.value.numero_arete || !newAnimal.value.raza) {
    alert('Por favor complete los campos obligatorios (*)');
    return;
  }
  if (isEditing.value && editingAnimalId.value !== null) {
    const index = allAnimals.value.findIndex(a => a.id === editingAnimalId.value);
    if (index !== -1) {
      allAnimals.value[index].numero_arete = newAnimal.value.numero_arete;
      allAnimals.value[index].nombre = newAnimal.value.nombre;
      allAnimals.value[index].raza = newAnimal.value.raza;
      allAnimals.value[index].fecha_nacimiento = newAnimal.value.fecha_nacimiento;
    }
  } else {
    allAnimals.value.unshift({
      id: allAnimals.value.length + 1,
      numero_arete: newAnimal.value.numero_arete,
      nombre: newAnimal.value.nombre,
      raza: newAnimal.value.raza,
      estado: 'Analizado',
      peso: '---',
      fecha_nacimiento: newAnimal.value.fecha_nacimiento
    });
  }
  filterAnimals();
  closeModal();
};

const confirmDelete = async (animal: any) => {
  const alert = await alertController.create({
    header: 'Confirmar Eliminación',
    message: `¿Está seguro de que desea eliminar al Bovino #${animal.numero_arete}?`,
    buttons: [
      { text: 'Cancelar', role: 'cancel' },
      { text: 'Eliminar', role: 'destructive', handler: () => { allAnimals.value = allAnimals.value.filter(a => a.id !== animal.id); filterAnimals(); } }
    ]
  });
  await alert.present();
};

const setStatusFilter = (status: string) => { filterStatus.value = status; filterAnimals(); };
const filterAnimals = () => {
  filteredAnimals.value = allAnimals.value.filter(animal => {
    return animal.numero_arete.includes(searchQuery.value) && (filterStatus.value === 'Todos' || animal.estado === filterStatus.value);
  });
};
</script>

<style scoped>
/* Estilos Base de la Pantalla */
.bovweight-screen { --background: #f4f8fb !important; background-color: #f4f8fb !important; }
.header-clean { background: #f4f8fb; }
.top-nav-bar { display: flex; align-items: center; background: #ffffff; padding: 14px 18px; justify-content: space-between; }
.nav-icon-menu { font-size: 1.5rem; color: #1b5e20; }
.nav-title { font-size: 1.25rem; font-weight: 700; color: #263238; margin: 0; flex-grow: 1; margin-left: 15px; }
.brand-logo-container { font-size: 1.1rem; font-weight: 800; margin-right: 12px; }
.brand-green { color: #2e7d32; }
.brand-dark { color: #1a1a1a; }
.user-avatar { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; }
.search-section { padding: 14px 18px; background: #f4f8fb; }
.search-input-wrapper { display: flex; align-items: center; background: #eef3f7; border-radius: 12px; padding: 10px 14px; margin-bottom: 14px; }
.search-magnifier { color: #78909c; font-size: 1.2rem; margin-right: 10px; }
.clean-input { border: none; background: transparent; width: 100%; outline: none; font-size: 0.95rem; color: #37474f; }
.pill-filters { display: flex; gap: 10px; }
.pill-btn { border: none; background: #ffffff; color: #546e7a; padding: 9px 18px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
.pill-active { background: #1b5e20 !important; color: #ffffff !important; }
.scroll-content { --background: #f4f8fb; }
.cards-stack { padding: 0 18px; }

/* Tarjetas de la Lista */
.bovweight-card { display: flex; flex-direction: column; background: #ffffff; border-radius: 16px; padding: 14px; margin-bottom: 14px; box-shadow: 0 4px 10px rgba(38, 50, 56, 0.04); cursor: pointer; }
.card-main-row { display: flex; align-items: center; width: 100%; padding-bottom: 10px; }
.placeholder-image { width: 64px; height: 64px; background: #e0e0e0; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.img-icon-inside { color: #9e9e9e; font-size: 1.4rem; }
.card-center-body { flex-grow: 1; padding-left: 14px; }

/* CORREGIDO: Los kilos de la lista principal ya no se ven gigantescos */
.weight-display { font-size: 1.15rem; font-weight: 800; color: #1e3a27; margin: 0 0 3px 0; }
.animal-code-text { font-size: 0.85rem; font-weight: 600; color: #546e7a; margin: 0; }
.animal-name-sub { font-size: 0.8rem; color: #2e7d32; margin: 2px 0 0 0; font-weight: 500; }
.animal-breed-sub { font-size: 0.75rem; color: #78909c; margin: 2px 0 0 0; }
.card-right-aside { text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 6px; justify-content: center; }
.pill-badge { font-size: 0.65rem; font-weight: 800; padding: 4px 10px; border-radius: 10px; }
.pill-badge.analizado { background: #ffccbc; color: #d84315; }
.pill-badge.vendido { background: #cfd8dc; color: #37474f; }
.date-display-text { font-size: 0.75rem; color: #90a4ae; margin: 0; }

/* Barra de Acciones */
.card-action-bar { display: flex; justify-content: flex-end; border-top: 1px solid #f1f3f4; padding-top: 10px; width: 100%; }
.short-buttons-container { display: flex; gap: 8px; }
.btn-action { display: flex; align-items: center; justify-content: center; gap: 6px; border: none; padding: 8px 16px; border-radius: 8px; font-size: 0.85rem; font-weight: 700; cursor: pointer; }
.btn-edit { background-color: #e3f2fd; color: #0288d1; }
.btn-delete { background-color: #ffebee; color: #c62828; }
.btn-icon { font-size: 1rem; }

.summary-box-month { display: flex; background: #e3f2fd; margin: 25px 18px; padding: 16px; border-radius: 14px; align-items: center; justify-content: space-between; }
.summary-title-label { font-size: 0.75rem; font-weight: 700; color: #546e7a; margin: 0 0 4px 0; }
.summary-total-weight { font-size: 1.4rem; font-weight: 800; color: #1b5e20; margin: 0; }
.summary-chart-icon-box { background: #b2dfdb; padding: 10px; border-radius: 10px; display: flex; align-items: center; }
.chart-mini-icon { font-size: 1.3rem; color: #004d40; }
.custom-fab { --background: #1b5e20; }

/* Estilos de Modales Comunes */
.modal-toolbar { --background: #ffffff; --color: #1b5e20; font-weight: bold; }
.modal-form-content { --background: #f4f8fb; }
.form-container { display: flex; flex-direction: column; gap: 16px; margin-top: 10px; }
.input-group { display: flex; flex-direction: column; gap: 6px; }
.form-label { font-size: 0.85rem; font-weight: 700; color: #455a64; }
.form-input { background: #ffffff; border: 1px solid #cfd8dc; padding: 12px; border-radius: 10px; font-size: 0.95rem; outline: none; color: #333; }
.form-select { background: #ffffff; border: 1px solid #cfd8dc; border-radius: 10px; padding: 4px 12px; color: #333; }
.custom-date-btn { align-self: flex-start; --background: #ffffff; --color: #1b5e20; border: 1px solid #cfd8dc; border-radius: 10px; }
.save-animal-btn { background: #1b5e20; color: #ffffff; border: none; padding: 14px; border-radius: 12px; font-size: 1rem; font-weight: 700; margin-top: 10px; cursor: pointer; }

/* ========================================================================= */
/* CORREGIDO: NUEVOS ESTILOS PARA FORZAR PANTALLA COMPLETA REAL EN EL MODAL  */
/* ========================================================================= */
.full-screen-modal {
  --width: 100% !important;
  --height: 100% !important;
  --border-radius: 0px !important;
}

/* CORREGIDO: Barra superior del detalle en fondo blanco con letras verdes */
.header-detail-clean {
  background: #ffffff;
}
.top-nav-bar-detail {
  display: flex;
  align-items: center;
  background: #ffffff;
  padding: 14px 18px;
  justify-content: space-between;
  border-bottom: 1px solid #eef1f0;
}
.btn-back-clean, .btn-notification-clean {
  background: transparent;
  border: none;
  font-size: 1.4rem;
  color: #2e7d32; /* Botones superiores en verde */
  display: flex;
  align-items: center;
  cursor: pointer;
}
.brand-logo-container-detail {
  font-size: 1.1rem;
  font-weight: 800;
}

/* Centrado del contenedor del detalle para que no se estire feo en computadoras */
.detail-content { --background: #f8faf9; }
.detail-wrapper-max {
  max-width: 500px;
  margin: 0 auto;
}

/* Imagen Grande */
.animal-image-container { width: 100%; height: 230px; border-radius: 24px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
.animal-image-container img { width: 100%; height: 100%; object-fit: cover; }

/* Identificación */
.id-status-row { display: flex; align-items: center; gap: 10px; }
.id-status-row h2 { font-size: 1.8rem; font-weight: 800; margin: 0; color: #1e3a27; }
.status-badge { background-color: #cbf3db; color: #0e6231; font-size: 0.75rem; font-weight: 700; padding: 4px 10px; border-radius: 12px; }
.animal-name-detail { margin: 6px 0 0 0; color: #2e7d32; font-weight: 600; font-size: 1rem; }
.animal-breed { margin: 4px 0 0 0; color: #6e7e73; font-weight: 500; }

/* Mini Grid */
.mini-grid { display: flex; gap: 16px; }
.mini-card { flex: 1; background: white; border-radius: 16px; padding: 12px 16px; display: flex; flex-direction: column; gap: 4px; box-shadow: 0 2px 6px rgba(0,0,0,0.01); }
.mini-label { font-size: 0.7rem; color: #90a196; font-weight: 700; }
.mini-value { font-size: 0.95rem; color: #2c3e35; font-weight: 700; }

/* CORREGIDO: Bloque Verde Centrado con Kilos en Blanco Puro */
.main-weight-card { 
  background: #194931; 
  color: #ffffff; 
  border-radius: 20px; 
  padding: 22px; 
  box-shadow: 0 6px 16px rgba(25, 73, 49, 0.15);
  text-align: center; /* Centra el título de la tarjeta */
}
.weight-card-title { font-size: 0.7rem; font-weight: 600; color: #a3c4b4; letter-spacing: 0.8px; }

/* Forzar el color blanco del texto de los kilos */
.weight-display-centered { 
  font-size: 2.6rem; 
  font-weight: 800; 
  color: #ffffff !important; /* Blanco puro absoluto */
  margin: 10px 0;
  text-align: center;
}
.weight-display-centered small { font-size: 1.3rem; font-weight: 500; color: #cbe3d7; }

.trend-row-centered { 
  display: flex; 
  align-items: center; 
  justify-content: center; /* Centra horizontalmente el icono y texto de tendencia */
  gap: 6px; 
  font-size: 0.85rem; 
  color: #cbe3d7; 
}

/* Gráfico de barras compactas */
.section-header-row { display: flex; justify-content: space-between; align-items: center; }
.growth-section h3, .history-section h3 { font-size: 1.1rem; font-weight: 700; color: #1e3a27; }
.filter-time-badge { background: #e2e8e5; color: #556b5c; font-size: 0.75rem; font-weight: 600; padding: 6px 12px; border-radius: 12px; }

/* CORREGIDO: Contenedor y espaciado de barras agrupadas estéticamente */
.mock-chart-container-clean {
  background: white;
  border-radius: 16px;
  padding: 16px;
  margin-top: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.01);
  display: flex;
  justify-content: center; /* Centra el bloque de barras */
}
.mock-chart-bars-compact {
  display: flex;
  align-items: flex-end;
  gap: 12px; /* Reducido la distancia entre las barras */
  height: 90px;
  width: auto;
}
.mock-chart-bars-compact .bar { width: 22px; height: var(--height); background: #d0ded6; border-radius: 4px; transition: height 0.3s; }
.mock-chart-bars-compact .active-bar { background: #194931; }

/* Historial */
.transparent-list { background: transparent; padding: 0; }
.history-item-card { --background: white; margin-bottom: 10px; border-radius: 16px; box-shadow: 0 2px 6px rgba(0,0,0,0.01); }
.history-icon-avatar { background: #eaf2ee; color: #194931; display: flex; align-items: center; justify-content: center; border-radius: 12px; width: 40px; height: 40px; }
.history-item-card h2 { font-size: 0.95rem; font-weight: 700; color: #2c3e35; }
.history-item-card p { font-size: 0.75rem; color: #798e81; }
.history-weight-value { font-size: 1.05rem; font-weight: 700; color: #194931; }
</style>