<template>
  <ion-page>
    <!-- MENÚ MANUAL -->
    <div v-if="isMenuOpen" class="manual-overlay">
      <div class="manual-backdrop" @click="isMenuOpen = false"></div>
      
      <!-- Se ajustó el ancho de la sidebar aquí -->
      <div class="manual-sidebar">
        <div class="sidebar-header">
          <div class="profile-frame">
            <img src="https://i.imgur.com/39M8vU4.png" class="avatar" />
          </div>
          <h2 class="title">{{ userName }}</h2>
          <p class="email">{{ userEmail }}</p>
        </div>

        <div class="sidebar-list">
          <!-- PANEL DE CONTROL -->
          <div class="s-item" :class="{ 'active-s': currentRoute === '/tabs/dashboard' }" @click="navegar('/tabs/dashboard')">
            <ion-icon :icon="gridOutline" class="m-icon"></ion-icon>
            <span>Panel de Control</span>
          </div>
          
          <!-- CONFIGURACIÓN DE FINCA -->
          <div class="s-item" 
              :class="{ 'active-s': currentRoute === '/tabs/finca' }" 
              @click="navegar('/tabs/finca')">
            <ion-icon :icon="settingsOutline" class="m-icon"></ion-icon>
            <span>Configuración de la Finca</span>
          </div>
          
          <!-- SECCIONES OCULTAS (COMENTADAS) -->
          <!-- 
          <div class="s-item" :class="{ 'active-s': currentRoute === '/tabs/personal' }" @click="navegar('/tabs/personal')">
            <ion-icon :icon="peopleOutline" class="m-icon"></ion-icon>
            <span>Gestión de Personal</span>
          </div>

          
          <!-- CERTIFICADOS -->
          <div class="s-item" :class="{ 'active-s': currentRoute === '/tabs/reportes' }" @click="navegar('/tabs/reportes')">
            <ion-icon :icon="documentTextOutline" class="m-icon"></ion-icon>
            <span>Reportes</span>
          </div>
          
          <!-- AYUDA -->
          <div class="s-item" :class="{ 'active-s': currentRoute === '/tabs/ayuda' }" @click="navegar('/tabs/ayuda')">
            <ion-icon :icon="helpCircleOutline" class="m-icon"></ion-icon>
            <span>Centro de Ayuda</span>
          </div>

          <!-- CERRAR SESIÓN -->
          <div class="s-item logout" @click="cerrarSesion">
            <ion-icon :icon="logOutOutline" class="m-icon"></ion-icon>
            <span>Cerrar Sesión</span>
          </div>
        </div>
      </div>
    </div>

    <!-- TABS -->
    <ion-tabs>
      <ion-router-outlet></ion-router-outlet>
      
      <ion-tab-bar slot="bottom" style="--background: #ffffff; height: 55px;">
        <ion-tab-button @click="isMenuOpen = !isMenuOpen">
          <ion-label style="color: #2e7d32; font-weight: bold;">MENÚ</ion-label>
        </ion-tab-button>
        
        <ion-tab-button tab="animales" href="/tabs/animales">
          <ion-label>ANIMALES</ion-label>
        </ion-tab-button>
        
        <ion-tab-button tab="perfil" href="/tabs/perfil">
          <ion-label>PERFIL</ion-label>
        </ion-tab-button>
      </ion-tab-bar>
    </ion-tabs>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { 
  IonPage, IonTabs, IonRouterOutlet, IonTabBar, IonTabButton, IonLabel, IonIcon 
} from '@ionic/vue';
import { 
  gridOutline, settingsOutline, peopleOutline, briefcaseOutline, 
  documentTextOutline, helpCircleOutline, logOutOutline 
} from 'ionicons/icons';

const isMenuOpen = ref(false);
const router = useRouter();
const route = useRoute();

// 1. Iniciamos los valores vacíos para que no muestren nombres previos
const userName = ref('');
const userEmail = ref('');

const currentRoute = computed(() => route.path);

const navegar = (ruta: string) => {
  router.push(ruta);
  isMenuOpen.value = false;
};

const cerrarSesion = () => {
  localStorage.removeItem('user');
  router.push('/login');
};

onMounted(() => {
  // 2. Obtenemos los datos del usuario logueado
  const userData = localStorage.getItem('user');
  
  if (userData) {
    const user = JSON.parse(userData);
    // 3. Asignamos el nombre y correo del objeto 'user' que guardaste al registrar/loguear
    userName.value = user.name || 'Usuario'; 
    userEmail.value = user.email || '';
  } else {
    // Si no hay datos, redirigimos al login por seguridad
    router.push('/login');
  }
});
</script> 

<style scoped>
.manual-overlay {
  position: fixed;
  top: 0; left: 0; width: 100%; height: 100%;
  z-index: 9999; display: flex;
}
.manual-backdrop {
  position: absolute; width: 100%; height: 100%; background: rgba(0,0,0,0.4);
}
/* CAMBIO: Ancho reducido de 300px a 260px */
.manual-sidebar {
  position: relative; 
  width: 240px; 
  height: 100%; 
  background: white;
  display: flex; 
  flex-direction: column; 
  padding-top: 30px;
  border-radius: 0 20px 20px 0;
}
/* CAMBIO: Ajuste de padding horizontal para el nuevo ancho */
.sidebar-header { padding: 20px 25px; }
.profile-frame {
  width: 60px; height: 60px; border: 2px solid #2e7d32;
  border-radius: 12px; padding: 4px; margin-bottom: 10px;
}
.avatar { width: 100%; height: 100%; border-radius: 8px; object-fit: cover; }
.title { font-weight: 800; color: #1a3a34; margin: 0; font-size: 1.1rem; }
.email { color: #718096; font-size: 0.75rem; margin: 0; }

.sidebar-list { margin-top: 15px; flex: 1; overflow-y: auto; }
.s-item {
  display: flex;
  align-items: center;
  padding: 12px 25px;
  font-weight: 600;
  color: #4a5568;
  gap: 12px;
  cursor: pointer;
  transition: background 0.2s;
  font-size: 0.95rem;
}
.s-item:active {
  background: #f7fafc;
}
.m-icon {
  font-size: 20px;
}
.active-s {
  background: #f0fdf4; color: #166534; border-left: 4px solid #166534;
}
.logout {
  margin-top: auto;
  border-top: 1px solid #f1f5f9;
  padding: 20px 25px;
  color: #e53e3e;
}
</style> 