<template>
  <ion-page>
    <ion-header translucent>
      <ion-toolbar>
        <ion-title>Iniciar sesión</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content fullscreen class="login-content">
      <div class="login-card">
        <h1>Bienvenido</h1>
        <p>Accede rápido para ver tu finca y registrar pesajes.</p>

        <ion-item>
          <ion-label position="stacked">Correo</ion-label>
          <ion-input v-model="email" type="email" placeholder="tucorreo@ejemplo.com" />
        </ion-item>

        <ion-item>
          <ion-label position="stacked">Contraseña</ion-label>
          <ion-input v-model="password" type="password" placeholder="********" />
        </ion-item>

        <ion-button expand="block" size="large" @click="login">Ingresar</ion-button>
        <p class="login-note">Solo necesitas un usuario para comenzar. La app está pensada para uso rural sencillo.</p>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonItem, IonLabel, IonInput, IonButton } from '@ionic/vue';

const router = useRouter();
const email = ref('');
const password = ref('');

const login = () => {
  const name = email.value ? email.value.split('@')[0] : 'Usuario';
  localStorage.setItem('user', JSON.stringify({ name, email: email.value }));
  router.push('/tabs/dashboard');
};

onMounted(() => {
  const stored = localStorage.getItem('user');
  if (stored) {
    router.push('/tabs/dashboard');
  }
});
</script>

<style scoped>
.login-content {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 24px 18px;
  background: #f7fafc;
}
.login-card {
  width: 100%;
  max-width: 420px;
  background: #ffffff;
  border-radius: 24px;
  padding: 24px;
  box-shadow: 0 16px 45px rgba(15, 23, 42, 0.08);
}
.login-card h1 {
  margin: 0 0 8px;
  font-size: 1.9rem;
  color: #0f172a;
}
.login-card p {
  margin: 0 0 20px;
  color: #475569;
  line-height: 1.6;
}
.login-card ion-item {
  margin-bottom: 16px;
  --background: transparent;
}
.login-card ion-button {
  margin-top: 8px;
  --border-radius: 14px;
}
.login-note {
  margin-top: 16px;
  color: #64748b;
  font-size: 0.92rem;
}
</style>
