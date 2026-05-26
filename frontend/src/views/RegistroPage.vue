<template>

  <ion-page>

    <ion-content :fullscreen="true" class="registro-content">

      <div class="registro-wrapper">

        <!-- LOGO -->
        <div class="logo-wrap">

          <h1 class="brand">
            Bov<span>Weight</span>
          </h1>

          <p class="sub">
            Crear nueva cuenta
          </p>

        </div>

        <!-- CARD -->
        <div class="card">

          <!-- NOMBRE -->
          <div class="field">

            <label>Nombre completo</label>

            <input
              v-model="nombre"
              type="text"
              placeholder="Juan Pérez"
            />

          </div>

          <!-- EMAIL -->
          <div class="field">

            <label>Correo electrónico</label>

            <input
              v-model="email"
              type="email"
              placeholder="correo@ejemplo.com"
            />

          </div>

          <!-- PASSWORD -->
          <div class="field">

            <label>Contraseña</label>

            <input
              v-model="password"
              :type="mostrarPassword ? 'text' : 'password'"
              placeholder="••••••••"
            />

          </div>

          <!-- ROL -->
          <div class="field">

            <label>Rol</label>

            <select v-model="rol">

              <option value="ganadero">
                Ganadero
              </option>

              <option value="veterinario">
                Veterinario
              </option>

            </select>

          </div>

          <!-- ERROR -->
          <p
            v-if="errorMsg"
            class="error"
          >
            {{ errorMsg }}
          </p>

          <!-- BOTON -->
          <button
            class="btn"
            @click="registrar"
            :disabled="loading"
          >

            <span v-if="loading">
              Registrando...
            </span>

            <span v-else>
              Crear cuenta
            </span>

          </button>

          <!-- LOGIN -->
          <p class="login-link">

            ¿Ya tienes cuenta?

            <span @click="irLogin">
              Iniciar sesión
            </span>

          </p>

        </div>

      </div>

    </ion-content>

  </ion-page>

</template>

<script setup lang="ts">

import { ref } from 'vue';

import axios from 'axios';

import { useRouter } from 'vue-router';

import {
  IonPage,
  IonContent
} from '@ionic/vue';

const router = useRouter();

const nombre = ref('');

const email = ref('');

const password = ref('');

const rol = ref('ganadero');

const loading = ref(false);

const errorMsg = ref('');

const mostrarPassword = ref(false);

const registrar = async () => {

  errorMsg.value = '';

  if (
    !nombre.value ||
    !email.value ||
    !password.value
  ) {

    errorMsg.value =
      'Completa todos los campos';

    return;

  }

  try {

    loading.value = true;

    const response = await axios.post(

      'http://127.0.0.1:8000/api/usuarios/registro',

      {
        name: nombre.value,
        email: email.value,
        password: password.value,
        rol: rol.value
      }

    );

    const data = response.data.datos;

    localStorage.setItem(
      'token',
      data.token
    );

    localStorage.setItem(
      'user',
      JSON.stringify(data.usuario)
    );

    router.push('/tabs/dashboard');

  }

  catch (error: any) {

  console.error(error);

  // VALIDACIONES DE LARAVEL
  if (error.response?.data?.errores) {

    const errores = error.response.data.errores;

    // Si vienen errores tipo array
    if (typeof errores === 'object') {

      const primerError =
        Object.values(errores)[0] as string[];

      errorMsg.value = Array.isArray(primerError)
        ? primerError[0]
        : String(primerError);

    }

    else {

      errorMsg.value = errores;

    }

  }

  // MENSAJE GENERAL
  else if (error.response?.data?.mensaje) {

    errorMsg.value =
      error.response.data.mensaje;

  }

  else {

    errorMsg.value =
      'Error al registrar usuario';

  }

}

  finally {

    loading.value = false;

  }

};

const irLogin = () => {

  router.push('/login');

};

</script>

<style scoped>

.registro-content {

  --background: linear-gradient(
    160deg,
    #0D2B1A,
    #1E5631,
    #3A9E61
  );
}

.registro-wrapper {

  min-height: 100vh;

  display: flex;

  flex-direction: column;

  justify-content: center;

  align-items: center;

  padding: 24px;
}

.logo-wrap {

  text-align: center;

  margin-bottom: 30px;
}

.brand {

  color: white;

  font-size: 2.5rem;

  font-weight: 900;
}

.brand span {

  color: #74C69D;
}

.sub {

  color: rgba(255,255,255,.7);
}

.card {

  width: 100%;

  max-width: 420px;

  background: white;

  border-radius: 24px;

  padding: 28px;

  box-shadow:
    0 20px 50px rgba(0,0,0,.25);
}

.field {

  margin-bottom: 18px;
}

.field label {

  display: block;

  margin-bottom: 8px;

  font-weight: 700;

  color: #374151;
}

.field input,
.field select {

  width: 100%;

  padding: 14px;

  border-radius: 14px;

  border: 1px solid #D1D5DB;

  background: #F3F4F6;

  color: #111827;

  font-size: 1rem;
}

.field input:focus,
.field select:focus {

  outline: none;

  border-color: #1E5631;
}

.btn {

  width: 100%;

  padding: 15px;

  border: none;

  border-radius: 14px;

  background:
    linear-gradient(
      135deg,
      #1A3D28,
      #2D7A4A
    );

  color: white;

  font-weight: 700;

  font-size: 1rem;

  margin-top: 10px;

  cursor: pointer;
}

.btn:disabled {

  opacity: .7;
}

.error {

  background: #FEE2E2;

  color: #DC2626;

  padding: 12px;

  border-radius: 10px;

  margin-bottom: 12px;
}

.login-link {

  margin-top: 18px;

  text-align: center;

  color: #4B5563;
}

.login-link span {

  color: #1E5631;

  font-weight: 700;

  cursor: pointer;
}

</style>