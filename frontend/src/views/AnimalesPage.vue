<template>
  <ion-page>
    <ion-header translucent>
      <ion-toolbar>
        <ion-title>Animales</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content fullscreen class="simple-page">
      <div class="page-card">
        <h2>Gestión de animales</h2>
        <p>Registra y consulta tu hato de forma rápida. Aquí podrás ver el listado de animales y su estado.</p>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent } from '@ionic/vue';
</script>

<style scoped>
.simple-page {
  padding: 18px;
  background: #f7fafc;
}
.page-card {
  background: #ffffff;
  border-radius: 20px;
  padding: 22px;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
}
.page-card h2 {
  margin: 0 0 10px;
  color: #0f172a;
}
.page-card p {
  margin: 0;
  color: #475569;
  line-height: 1.6;
}
</style>
