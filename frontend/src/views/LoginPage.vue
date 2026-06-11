<template>
  <ion-page>
    <ion-content :fullscreen="true" class="login-content">

      <div class="splash">

        <div class="deco deco-1"></div>
        <div class="deco deco-2"></div>

        <div class="logo-wrap">

          <svg viewBox="0 0 50 50" fill="none" class="logo-svg">

            <ellipse
              cx="25"
              cy="32"
              rx="18"
              ry="11"
              fill="rgba(255,255,255,.18)"
            />

            <circle
              cx="25"
              cy="20"
              r="10"
              fill="white"
            />

            <circle
              cx="20.5"
              cy="17"
              r="3.5"
              fill="rgba(26,61,40,.5)"
            />

            <circle
              cx="29.5"
              cy="17"
              r="3.5"
              fill="rgba(26,61,40,.5)"
            />

          </svg>

        </div>

        <h1 class="brand">
          Bov<span>Weight</span>
        </h1>

        <p class="brand-sub">
          COSTA RICA
        </p>

        <p class="brand-desc">
          Control inteligente de peso para tu ganado bovino
        </p>

        <div class="form-card">

          <h2 class="form-title">
            Iniciar sesión
          </h2>

          <div class="field">

            <label class="field-label">
              Correo electrónico
            </label>

            <div class="field-input-wrap">

              <span class="field-icon">
                ✉️
              </span>

              <input
                v-model.trim="email"
                type="email"
                class="field-input"
                placeholder="admin@bovweight.com"
                autocomplete="email"
                @keyup.enter="login"
              />

            </div>

          </div>

          <div class="field">

            <label class="field-label">
              Contraseña
            </label>

            <div class="field-input-wrap">

              <span class="field-icon">
                🔒
              </span>

              <input
                v-model="password"
                :type="mostrarPassword ? 'text' : 'password'"
                class="field-input"
                placeholder="••••••••"
                autocomplete="current-password"
                @keyup.enter="login"
              />

              <button
                type="button"
                class="toggle-password"
                @click="mostrarPassword = !mostrarPassword"
              >
                {{ mostrarPassword ? '🙈' : '👁️' }}
              </button>

            </div>

          </div>

          <p
            v-if="errorMsg"
            class="error-msg"
          >
            {{ errorMsg }}
          </p>

          <button
            class="btn-login"
            :disabled="loading"
            @click="login"
          >

            <span v-if="loading">
              Ingresando...
            </span>

            <span v-else>
              Ingresar
            </span>

          </button>

          <p class="register-link">
            ¿No tienes cuenta?

            <span @click="irRegistro">
              Crear cuenta
            </span>
          </p>

        </div>

        <p class="footer-note">
          BovWeight CR · Sistema ganadero inteligente
        </p>

      </div>

    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">

import {
  ref,
  onMounted
} from 'vue';

import {
  useRouter
} from 'vue-router';

import {
  IonPage,
  IonContent
} from '@ionic/vue';

import {
  loginUsuario
} from '@/services/api';

const router = useRouter();

const email = ref('');
const password = ref('');

const loading = ref(false);
const errorMsg = ref('');

const mostrarPassword = ref(false);

const login = async () => {
  errorMsg.value = '';

  const correo =
    email.value.trim();

  const clave =
    password.value;

  if (!correo || !clave) {
    errorMsg.value =
      'Completa todos los campos.';

    return;
  }

  loading.value = true;

  try {
    await loginUsuario({
      email: correo,
      password: clave
    });

    router.replace(
      '/tabs/dashboard'
    );

  } catch (error: any) {
    console.error(error);

    errorMsg.value =
      error?.message ??
      'Credenciales incorrectas';

  } finally {
    loading.value = false;
  }
};

const irRegistro = () => {
  router.push('/registro');
};

onMounted(() => {
  const token =
    localStorage.getItem('token');

  if (token) {
    router.replace(
      '/tabs/dashboard'
    );
  }
});

</script>

<style scoped>

.login-content {
  --background: transparent;
}

.splash {
  min-height: 100vh;

  background:
    linear-gradient(
      165deg,
      #0D2B1A 0%,
      #1E5631 55%,
      #3A9E61 100%
    );

  display: flex;
  flex-direction: column;

  align-items: center;

  justify-content: center;

  padding: 48px 24px 32px;

  position: relative;

  overflow: hidden;
}

.deco {
  position: absolute;

  border-radius: 50%;

  background: rgba(255,255,255,.05);
}

.deco-1 {
  width: 280px;
  height: 280px;

  top: -80px;
  right: -60px;
}

.deco-2 {
  width: 200px;
  height: 200px;

  bottom: 80px;
  left: -60px;
}

.logo-wrap {
  width: 84px;
  height: 84px;

  border-radius: 26px;

  background: rgba(255,255,255,.12);

  border: 1.5px solid rgba(255,255,255,.22);

  display: flex;

  align-items: center;
  justify-content: center;

  margin-bottom: 18px;

  backdrop-filter: blur(8px);
}

.logo-svg {
  width: 50px;
  height: 50px;
}

.brand {
  font-size: 2.8rem;

  font-weight: 900;

  color: white;

  margin: 0;
}

.brand span {
  color: #74C69D;
}

.brand-sub {
  font-size: .75rem;

  letter-spacing: .18em;

  color: rgba(255,255,255,.55);

  margin: 4px 0 12px;
}

.brand-desc {
  font-size: .95rem;

  color: rgba(255,255,255,.78);

  text-align: center;

  line-height: 1.5;

  margin-bottom: 34px;
}

.form-card {
  width: 100%;
  max-width: 420px;

  background: white;

  border-radius: 24px;

  padding: 28px 24px;

  box-shadow:
    0 20px 60px rgba(0,0,0,.25);
}

.form-title {
  font-size: 1.9rem;

  font-weight: 800;

  color: #1A3D28;

  margin-bottom: 22px;
}

.field {
  margin-bottom: 18px;
}

.field-label {
  display: block;

  font-size: .9rem;

  font-weight: 700;

  color: #374151;

  margin-bottom: 8px;
}

.field-input-wrap {
  display: flex;

  align-items: center;

  gap: 10px;

  background: #F3F4F6;

  border: 1.5px solid #E5E7EB;

  border-radius: 14px;

  padding: 12px 14px;

  transition: .2s;
}

.field-input-wrap:focus-within {
  border-color: #1E5631;

  background: white;

  box-shadow:
    0 0 0 3px rgba(30,86,49,.08);
}

.field-icon {
  font-size: 16px;
}

.field-input {
  flex: 1;

  border: none;

  outline: none;

  background: transparent;

  font-size: 1rem;

  color: #111827;

  caret-color: #1E5631;
}

.field-input::placeholder {
  color: #9CA3AF;
}

.toggle-password {
  border: none;

  background: transparent;

  cursor: pointer;

  font-size: 1rem;
}

.error-msg {
  background: #FEE2E2;

  color: #DC2626;

  padding: 10px 12px;

  border-radius: 10px;

  font-size: .82rem;

  margin-bottom: 14px;
}

.btn-login {
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

  font-size: 1rem;

  font-weight: 700;

  cursor: pointer;

  transition: .2s;

  box-shadow:
    0 4px 14px rgba(30,86,49,.35);
}

.btn-login:hover {
  transform: translateY(-1px);
}

.btn-login:disabled {
  opacity: .7;
}

.register-link {
  margin-top: 18px;

  text-align: center;

  font-size: .92rem;

  color: #4B5563;
}

.register-link span {
  color: #1E5631;

  font-weight: 700;

  cursor: pointer;
}

.footer-note {
  margin-top: 24px;

  font-size: .72rem;

  color: rgba(255,255,255,.5);

  text-align: center;
}

</style>