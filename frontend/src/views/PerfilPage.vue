<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar class="custom-toolbar">
        <ion-buttons slot="start">
          <!-- Botón lateral que cambia según la profundidad de la vista -->
          <ion-button v-if="currentView === 'menu'" color="dark">
            <ion-icon slot="icon-only" :icon="menuOutline"></ion-icon>
          </ion-button>
          <ion-button v-else color="dark" @click="currentView = 'menu'; isEditing = false">
            <ion-icon slot="icon-only" :icon="arrowBackOutline"></ion-icon>
          </ion-button>
        </ion-buttons>
        
        <ion-title class="main-title">
          <div v-if="currentView === 'hacienda'" class="header-hacienda">
            <span class="corp-name">Corporación Ganadera</span>
            <span class="corp-sub"><ion-icon :icon="locationOutline"></ion-icon> Hacienda Principal</span>
          </div>
          <span v-else>{{ currentView === 'menu' ? 'Perfil' : (isEditing ? 'Editar Perfil' : 'Información Personal') }}</span>
        </ion-title>

        <ion-buttons slot="end" v-if="currentView === 'hacienda'">
          <ion-button color="dark">
            <ion-icon slot="icon-only" :icon="ellipsisVertical"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="custom-page">
      <div class="main-container">
        
        <!-- VISTA 1: MENÚ PRINCIPAL -->
        <div v-if="currentView === 'menu'">
          <header class="profile-header ion-padding">
            <div class="avatar-wrapper">
              <img :src="user.avatar" alt="Avatar" class="avatar-img" />
            </div>
            <div class="user-meta">
              <span class="role-label">{{ user.role.toUpperCase() }}</span>
              <h1 class="user-name">{{ user.name }}</h1>
              <div class="location-tag">
                <ion-icon :icon="locationOutline"></ion-icon>
                <span></span>
              </div>
            </div>
          </header>

          <section class="nav-section ion-padding">
            <h2 class="section-title">MIS DATOS</h2>
            <div class="nav-item shadow-soft" @click="currentView = 'personal'">
              <div class="icon-box bg-icon-blue"><ion-icon :icon="personOutline"></ion-icon></div>
              <span class="nav-label">Información Personal</span>
              <ion-icon :icon="chevronForwardOutline" class="chevron"></ion-icon>
            </div>

            <div class="nav-item shadow-soft" @click="currentView = 'hacienda'">
              <div class="icon-box bg-icon-green"><ion-icon :icon="businessOutline"></ion-icon></div>
              <span class="nav-label">Detalles de la Finca</span>
              <ion-icon :icon="chevronForwardOutline" class="chevron"></ion-icon>
            </div>
          </section>

          <div class="ion-padding logout-container">
            <button class="btn-logout" @click="handleLogout">
              <ion-icon :icon="logOutOutline" class="icon-red"></ion-icon> CERRAR SESIÓN
            </button>
          </div>
        </div>

        <!-- VISTA 2: INFORMACIÓN PERSONAL (VER/EDITAR) -->
        <div v-else-if="currentView === 'personal'" class="personal-info-view">
          
          <!-- Tarjeta superior de perfil -->
          <div class="profile-card-top shadow-soft">
            <div class="banner-bg" :class="user.role === 'ganadero' ? 'bg-ganadero' : 'bg-veterinario'"></div>
            
            <div class="avatar-container-centered">
              <div class="avatar-main">
                <img :src="user.avatar || 'https://via.placeholder.com/150'" />
                <div v-if="isEditing" class="camera-badge">
                  <ion-icon :icon="cameraOutline"></ion-icon>
                </div>
              </div>
            </div>

            <div class="name-section ion-text-center">
              <h2 class="display-name">{{ user.name }}</h2>
              
              <div class="role-badge-container">
                <span :class="['role-tag', user.role]">
                  <ion-icon :icon="user.role === 'ganadero' ? businessOutline : medicalOutline"></ion-icon>
                  {{ user.role === 'ganadero' ? 'PROPIETARIO' : 'VETERINARIO' }}
                </span>
              </div>

              <ion-button v-if="!isEditing" fill="clear" size="small" class="edit-toggle-btn" @click="isEditing = true">
                <ion-icon slot="start" :icon="createOutline"></ion-icon>
                Editar Perfil
              </ion-button>
            </div>
          </div> 

          <!-- Tarjeta de campos de datos -->
          <div class="info-card shadow-soft">
            <h3 class="card-title">DATOS DE LA CUENTA</h3>
            <p class="card-subtitle">
              {{ isEditing ? 'Modifique la información necesaria para su registro profesional.' : 'Información actual del usuario en BovWeight CR.' }}
            </p>

            <div class="input-group">
              <label>Nombre Completo</label>
              <div class="custom-input" :class="{'read-only-mode': !isEditing}">
                <ion-icon :icon="personOutline" class="input-icon"></ion-icon>
                <input v-if="isEditing" type="text" v-model="user.name">
                <span v-else class="view-text">{{ user.name }}</span>
              </div>
            </div>

            <div class="input-group">
              <label>Correo Electrónico</label>
              <div class="custom-input" :class="{'read-only-mode': !isEditing}">
                <ion-icon :icon="mailOutline" class="input-icon"></ion-icon>
                <input v-if="isEditing" type="email" v-model="user.email">
                <span v-else class="view-text">{{ user.email }}</span>
              </div>
            </div>

            <div class="input-group">
              <label>Teléfono</label>
              <div class="custom-input" :class="{'read-only-mode': !isEditing}">
                <ion-icon :icon="callOutline" class="input-icon"></ion-icon>
                <input v-if="isEditing" type="tel" v-model="user.phone">
                <span v-else class="view-text">{{ user.phone }}</span>
              </div>
            </div>

            <div class="input-group">
              <label>{{ user.role === 'ganadero' ? 'ID de Productor / CVO' : 'Registro Profesional / Colegiado' }}</label>
              <div class="custom-input" :class="{'read-only-mode': !isEditing}">
                <ion-icon :icon="user.role === 'ganadero' ? businessOutline : medicalOutline" class="input-icon"></ion-icon>
                <input v-if="isEditing" type="text" v-model="user.regProf" :placeholder="user.role === 'ganadero' ? 'Ej: CVO-123' : 'Ej: VET-456'">
                <span v-else class="view-text">{{ user.regProf || 'No registrado' }}</span>
              </div>
            </div>
          </div>

          <!-- SECCIÓN DE BOTONES DINÁMICOS -->
          <div class="ion-padding-horizontal ion-padding-bottom">
            <template v-if="isEditing">
              <button class="btn-save" @click="saveChanges">
                <ion-icon :icon="saveOutline"></ion-icon> Guardar Cambios
              </button>
              <button class="btn-back-outline" @click="isEditing = false">
                Cancelar
              </button>
            </template>
            
            <template v-else>
              <button class="btn-back-outline" @click="currentView = 'menu'">
                <ion-icon :icon="arrowBackOutline"></ion-icon> Volver al Perfil
              </button>
            </template>
          </div>
        </div>

        <!-- VISTA 3: DETALLES DE LA HACIENDA -->
        <div v-else-if="currentView === 'hacienda'" class="hacienda-view ion-padding">
          <div class="metrics-grid">
            <div class="metric-card shadow-soft">
              <ion-icon :icon="people" class="m-icon"></ion-icon>
              <span class="m-value">{{ totalCabezas }}</span>
              <span class="m-label">CABEZAS</span>
            </div>
            <div class="metric-card shadow-soft">
              <ion-icon :icon="leaf" class="m-icon"></ion-icon>
              <span class="m-value">{{ ranchos.length }}</span>
              <span class="m-label">FINCAS</span>
            </div>
          </div>

          <div class="list-header">
            <h2 class="list-title">Ranchos de {{ user.name.split(' ')[0] }}</h2>
          </div>

          <div class="ranchos-container">
            <div v-for="(rancho, index) in ranchos" 
              :key="index" 
              class="rancho-card shadow-soft"
              @click="ranchoSeleccionado = rancho; currentView = 'detalle-rancho'">
              <div class="r-info">
                <div class="r-name-row">
                  <span class="r-name">{{ rancho.nombre }}</span>
                  <ion-icon :icon="rancho.verificado ? checkmarkCircle : helpCircleOutline" 
                            :class="rancho.verificado ? 'icon-verified' : 'icon-pending'"></ion-icon>
                </div>
                <span class="r-loc"><ion-icon :icon="locationOutline"></ion-icon> {{ rancho.sector }}</span>
              </div>
              <div class="r-stats">
                <span class="r-val">{{ rancho.cabezas }}</span>
                <span class="r-unit">CABEZAS</span>
              </div>
            </div>
          </div>

          <button class="btn-vincular shadow-soft" @click="isModalOpen = true">
            <ion-icon :icon="addCircleOutline"></ion-icon> Vincular Nueva Finca
          </button>

          <button class="btn-back-outline shadow-soft" @click="currentView = 'menu'">
            <ion-icon :icon="arrowBackOutline"></ion-icon> Volver al Perfil
          </button>
        </div>
        
        <!-- VISTA 4: DETALLE ESPECÍFICO DE LA FINCA SELECCIONADA -->
        <div v-else-if="currentView === 'detalle-rancho' && ranchoSeleccionado" class="hacienda-view ion-padding">
          <div class="info-card shadow-soft">
            <h3 class="card-title">EXPEDIENTE DE LA FINCA</h3>
            <p class="card-subtitle">Información detallada del registro en BovWeight CR.</p>

            <div class="input-group">
              <label>Nombre del Rancho</label>
              <div class="custom-input read-only-mode">
                <ion-icon :icon="businessOutline" class="input-icon"></ion-icon>
                <span class="view-text">{{ ranchoSeleccionado.nombre }}</span>
              </div>
            </div>

            <div class="input-group">
              <label>Ubicación / Sector</label>
              <div class="custom-input read-only-mode">
                <ion-icon :icon="locationOutline" class="input-icon"></ion-icon>
                <span class="view-text">{{ ranchoSeleccionado.sector }}</span>
              </div>
            </div>

            <div class="input-group">
              <label>Población Bovina</label>
              <div class="custom-input read-only-mode">
                <ion-icon :icon="people" class="input-icon"></ion-icon>
                <span class="view-text">{{ ranchoSeleccionado.cabezas }} Cabezas</span>
              </div>
            </div>

            <div class="input-group">
              <label>Estado de Verificación</label>
              <div class="custom-input read-only-mode">
                <ion-icon :icon="ranchoSeleccionado.verificado ? checkmarkCircle : helpCircleOutline" 
                          :class="ranchoSeleccionado.verificado ? 'icon-verified' : 'icon-pending'"></ion-icon>
                <span class="view-text">
                  {{ ranchoSeleccionado.verificado ? 'Registro Verificado' : 'Pendiente de Auditoría' }}
                </span>
              </div>
            </div>
          </div>

          <button class="btn-back-outline shadow-soft" @click="currentView = 'hacienda'">
            <ion-icon :icon="arrowBackOutline"></ion-icon> Volver a la Lista
          </button>
        </div>

        <!-- MODAL PARA NUEVA FINCA -->
        <ion-modal :is-open="isModalOpen" @didDismiss="isModalOpen = false">
          <div class="info-card" style="margin-top: 50px;">
            <h3 class="card-title">NUEVA FINCA</h3>
            <p class="card-subtitle">Registro para: <strong>{{ user.name }}</strong></p>
            
            <div class="input-group">
              <label>Nombre de la Finca</label>
              <div class="custom-input">
                <input type="text" v-model="nuevoRancho.nombre" placeholder="Ej: Hacienda Santa Cruz">
              </div>
            </div>

            <div class="input-group">
              <label>Sector / Provincia</label>
              <div class="custom-input">
                <input type="text" v-model="nuevoRancho.sector" placeholder="Guanacaste, Alajuela, etc.">
              </div>
            </div>

            <div class="input-group">
              <label>Número de Cabezas</label>
              <div class="custom-input">
                <input type="number" v-model="nuevoRancho.cabezas">
              </div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
              <button class="btn-cancel-modal" @click="isModalOpen = false">Cancelar</button>
              <button class="btn-save" @click="vincularNuevoRancho">Confirmar</button>
            </div>
          </div>
        </ion-modal>

      </div> <!-- CIERRE DE main-container -->
    </ion-content>
  </ion-page>
</template> 

<script setup lang="ts">
import { ref, computed } from 'vue';
import { 
  IonPage, IonContent, IonIcon, IonHeader, IonToolbar, IonTitle, IonButtons, IonButton, toastController, IonModal 
} from "@ionic/vue";
import { 
  locationOutline, personOutline, chevronForwardOutline, businessOutline, 
  logOutOutline, menuOutline, arrowBackOutline, cameraOutline, mailOutline, 
  callOutline, checkmarkCircleOutline, saveOutline, medicalOutline, 
  ellipsisVertical, people, leaf, createOutline, checkmarkCircle, 
  helpCircleOutline, addCircleOutline 
} from 'ionicons/icons';

const currentView = ref('menu');
const isEditing = ref(false);
const isModalOpen = ref(false);

const user = ref({
  name: '',
  email: '',
  phone: '',
  role: 'ganadero', // o 'veterinario'
  regProf: '',
  avatar: ''
});

const ranchos = ref([
  { nombre: 'Finca El Progreso', sector: 'Guanacaste', cabezas: 124, verificado: true },
  { nombre: 'Finca Los Olivos', sector: 'Alajuela', cabezas: 85, verificado: false }
]);
const ranchoSeleccionado = ref(null); // Almacenará el objeto de la finca elegida
const nuevoRancho = ref({
  nombre: '',
  sector: '',
  cabezas: 0,
  verificado: false
});

const vincularNuevoRancho = async () => {
  if (!nuevoRancho.value.nombre || !nuevoRancho.value.sector) {
    const toast = await toastController.create({
      message: 'Complete los datos requeridos',
      duration: 2000,
      color: 'warning'
    });
    await toast.present();
    return;
  }
  ranchos.value.push({ ...nuevoRancho.value });
  isModalOpen.value = false;
  nuevoRancho.value = { nombre: '', sector: '', cabezas: 0, verificado: false };
  const toast = await toastController.create({
    message: 'Finca vinculada exitosamente',
    duration: 3000,
    color: 'success'
  });
  await toast.present();
};

const totalCabezas = computed(() => {
  return ranchos.value.reduce((acc, r) => acc + (Number(r.cabezas) || 0), 0);
});

const saveChanges = async () => {
  const toast = await toastController.create({
    message: 'Perfil actualizado correctamente',
    duration: 2000,
    color: 'success'
  });
  await toast.present();
  isEditing.value = false;
};

const handleLogout = () => console.log("Sesión finalizada");
</script>

<style scoped>
.custom-page { --background: #f8fbff; }
.custom-toolbar { --background: #f8fbff; --color: #004010; padding-top: 10px; }
.main-title { font-weight: 800; font-size: 18px; color: #004010; }
.main-container { max-width: 500px; margin: 0 auto; padding-bottom: 20px; }
.shadow-soft { box-shadow: 0 10px 30px rgba(0,0,0,0.03); }

/* Perfil y Avatar */
.profile-card-top { background: white; border-radius: 25px; margin: 15px; overflow: hidden; border: 1px solid #eef2f6; }
.banner-bg { background: #e9f0ea; height: 100px; }
.avatar-container-centered { display: flex; justify-content: center; margin-top: -55px; }
.avatar-main { position: relative; width: 110px; height: 110px; }
.avatar-main img { width: 100%; height: 100%; border-radius: 50%; border: 5px solid white; object-fit: cover; }
.camera-badge { position: absolute; bottom: 5px; right: 2px; background: #004010; color: white; padding: 7px; border-radius: 50%; border: 3px solid white; font-size: 16px; display: flex; }
.display-name { color: #004010; font-weight: 800; font-size: 22px; margin-top: 10px; margin-bottom: 5px; }
.display-role { color: #64748b; font-size: 14px; font-weight: 500; margin-bottom: 5px; }
.edit-toggle-btn { --color: #004010; font-weight: 700; font-size: 13px; text-transform: none; }

/* Tarjetas de Información */
.info-card { background: white; border-radius: 25px; margin: 15px; padding: 25px; border: 1px solid #eef2f6; }
.card-title { color: #004010; font-size: 14px; font-weight: 800; letter-spacing: 0.5px; margin-bottom: 8px; }
.card-subtitle { color: #64748b; font-size: 13px; line-height: 1.4; margin-bottom: 20px; }

/* Inputs y Modo Lectura */
.input-group { margin-bottom: 18px; }
.input-group label { font-size: 12px; font-weight: 700; color: #1e293b; margin-left: 4px; display: block; margin-bottom: 8px; }
.custom-input { background: #f1f4f9; border-radius: 14px; padding: 14px 18px; display: flex; align-items: center; gap: 12px; transition: all 0.3s ease; }
.read-only-mode { background: transparent; padding-left: 0; border-bottom: 1px solid #f1f4f9; border-radius: 0; }
.input-icon { color: #94a3b8; font-size: 20px; }
.custom-input input { border: none; background: transparent; width: 100%; outline: none; font-size: 15px; color: #334155; font-weight: 500; }
.view-text { font-size: 15px; color: #334155; font-weight: 600; }

/* Métricas y Ranchos */
.metrics-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px; }
.metric-card { background: white; border-radius: 20px; padding: 25px 10px; display: flex; flex-direction: column; align-items: center; border: 1.5px solid #eef2f6; }
.m-icon { font-size: 26px; color: #003008; margin-bottom: 10px; }
.m-value { font-size: 42px; font-weight: 900; color: #003008; }
.m-label { font-size: 12px; font-weight: 800; color: #64748b; }

.rancho-card { background: white; border-radius: 18px; padding: 18px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; border: 1.5px solid #f1f5f9; }
.r-name { font-size: 17px; font-weight: 700; color: #1e293b; }
.icon-verified { color: #166534; font-size: 18px; }

/* Botones */
.btn-save { width: 100%; background: #004010; color: white; padding: 18px; border-radius: 16px; font-weight: 700; border: none; display: flex; align-items: center; justify-content: center; gap: 10px; }
.btn-back-outline { width: 100%; background: transparent; color: #64748b; padding: 15px; border-radius: 16px; font-weight: 700; border: 2px solid #eef2f6; margin-top: 10px; display: flex; align-items: center; justify-content: center; gap: 8px; }
.btn-cancel-modal { background: #f1f4f9; color: #334155; border-radius: 16px; padding: 15px; font-weight: 700; border: none; flex: 1; }
.btn-logout { width: 100%; background: white; border: 2px solid #fee2e2; color: #dc2626; padding: 15px; border-radius: 16px; font-weight: 800; border: none; display: flex; align-items: center; justify-content: center; gap: 10px; }
.btn-vincular { width: 100%; background: #003008; color: white; padding: 20px; border-radius: 16px; font-weight: 800; border: none; margin-top: 20px; display: flex; align-items: center; justify-content: center; gap: 10px; }

/* Navegación Menú */
.profile-header { display: flex; align-items: center; gap: 15px; }
.avatar-img { width: 80px; height: 80px; border-radius: 20px; object-fit: cover; }
.user-name { font-size: 24px; font-weight: 900; color: #0f1c24; margin: 0; }
.nav-item { background: white; border-radius: 16px; padding: 14px; margin-bottom: 10px; display: flex; align-items: center; cursor: pointer; }
.icon-box { width: 38px; height: 38px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-right: 12px; }
.bg-icon-blue { background: #e0f2fe; color: #0369a1; }
.bg-icon-green { background: #dcfce7; color: #166534; }
.nav-label { flex: 1; font-weight: 700; color: #1e293b; font-size: 14px; }
.section-title { font-size: 12px; font-weight: 800; color: #94a3b8; margin: 20px 0 10px 5px; letter-spacing: 1px; }
</style> 