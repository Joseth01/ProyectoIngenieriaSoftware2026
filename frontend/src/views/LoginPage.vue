<template>
  <ion-page>
    <ion-content :fullscreen="true" class="login-content">
      <div class="splash">
        <!-- decorative circles -->
        <div class="deco deco-1"></div>
        <div class="deco deco-2"></div>

        <!-- logo -->
        <div class="logo-wrap">
          <svg viewBox="0 0 50 50" fill="none" class="logo-svg">
            <ellipse cx="25" cy="32" rx="18" ry="11" fill="rgba(255,255,255,.18)"/>
            <circle cx="25" cy="20" r="10" fill="white"/>
            <circle cx="20.5" cy="17" r="3.5" fill="rgba(26,61,40,.5)"/>
            <circle cx="29.5" cy="17" r="3.5" fill="rgba(26,61,40,.5)"/>
            <path d="M10 22 Q8 17 11 14" stroke="rgba(255,255,255,.7)" stroke-width="2.5" stroke-linecap="round" fill="none"/>
            <path d="M40 22 Q42 17 39 14" stroke="rgba(255,255,255,.7)" stroke-width="2.5" stroke-linecap="round" fill="none"/>
          </svg>
        </div>

        <h1 class="brand">Bov<span>Weight</span></h1>
        <p class="brand-sub">COSTA RICA</p>
        <p class="brand-desc">Control inteligente de peso para tu ganado bovino</p>

        <!-- form card -->
        <div class="form-card">
          <h2 class="form-title">Iniciar sesión</h2>

          <div class="field">
            <label class="field-label">Correo electrónico</label>
            <div class="field-input-wrap">
              <span class="field-icon">✉</span>
              <input
                v-model="email"
                type="email"
                class="field-input"
                placeholder="tucorreo@ejemplo.com"
                @keyup.enter="login"
              />
            </div>
          </div>

          <div class="field">
            <label class="field-label">Contraseña</label>
            <div class="field-input-wrap">
              <span class="field-icon">🔒</span>
              <input
                v-model="password"
                type="password"
                class="field-input"
                placeholder="••••••••"
                @keyup.enter="login"
              />
            </div>
          </div>

          <p v-if="errorMsg" class="error-msg">{{ errorMsg }}</p>

          <button class="btn-login" :disabled="loading" @click="login">
            <span v-if="loading">Ingresando…</span>
            <span v-else>Ingresar</span>
          </button>
        </div>

        <p class="footer-note">BovWeight CR · Sistema ganadero inteligente</p>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { IonPage, IonContent } from '@ionic/vue';

const router = useRouter();
const email    = ref('');
const password = ref('');
const loading  = ref(false);
const errorMsg = ref('');

const login = async () => {
  if (!email.value) {
    errorMsg.value = 'Ingresa tu correo electrónico.';
    return;
  }
  errorMsg.value = '';
  loading.value = true;

  // Simula autenticación: en producción conectar con /api/login (Sanctum)
  await new Promise(r => setTimeout(r, 600));

  const name = email.value.split('@')[0]
    .replace(/[._]/g, ' ')
    .replace(/\b\w/g, c => c.toUpperCase());

  localStorage.setItem('user', JSON.stringify({ name, email: email.value }));
  loading.value = false;
  router.push('/tabs/dashboard');
};

onMounted(() => {
  if (localStorage.getItem('user')) {
    router.push('/tabs/dashboard');
  }
});
</script>

<style scoped>
.login-content {
  --background: transparent;
}

.splash {
  min-height: 100vh;
  background: linear-gradient(165deg, #0D2B1A 0%, #1E5631 55%, #3A9E61 100%);
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 48px 24px 32px;
  position: relative;
  overflow: hidden;
}

.deco {
  position: absolute;
  border-radius: 50%;
  background: rgba(255,255,255,.05);
  pointer-events: none;
}
.deco-1 { width: 280px; height: 280px; top: -80px; right: -60px; }
.deco-2 { width: 200px; height: 200px; bottom: 80px; left: -60px; }

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
.logo-svg { width: 50px; height: 50px; }

.brand {
  font-size: 2.25rem;
  font-weight: 900;
  color: #fff;
  letter-spacing: -1px;
  margin: 0;
}
.brand span { color: #74C69D; }

.brand-sub {
  font-size: .6875rem;
  letter-spacing: .18em;
  color: rgba(255,255,255,.5);
  margin: 4px 0 10px;
  text-transform: uppercase;
}

.brand-desc {
  font-size: .9rem;
  color: rgba(255,255,255,.7);
  text-align: center;
  margin: 0 0 32px;
  line-height: 1.5;
}

/* Form card */
.form-card {
  width: 100%;
  max-width: 420px;
  background: #fff;
  border-radius: 24px;
  padding: 28px 24px;
  box-shadow: 0 20px 60px rgba(0,0,0,.25);
}

.form-title {
  font-size: 1.25rem;
  font-weight: 800;
  color: #1A3D28;
  margin: 0 0 22px;
}

.field { margin-bottom: 16px; }

.field-label {
  display: block;
  font-size: .8125rem;
  font-weight: 700;
  color: #374151;
  margin-bottom: 6px;
}

.field-input-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #F2F5F3;
  border: 1.5px solid #E5E7EB;
  border-radius: 12px;
  padding: 11px 14px;
  transition: border-color .2s;
}
.field-input-wrap:focus-within {
  border-color: #1E5631;
  background: #fff;
}

.field-icon { font-size: 16px; flex-shrink: 0; }

.field-input {
  flex: 1;
  border: none;
  background: transparent;
  outline: none;
  font-size: .9375rem;
  color: #111827;
  font-family: inherit;
}
.field-input::placeholder { color: #9CA3AF; }

.error-msg {
  font-size: .8125rem;
  color: #EF4444;
  background: #FEE2E2;
  border-radius: 8px;
  padding: 8px 12px;
  margin: 0 0 14px;
}

.btn-login {
  width: 100%;
  padding: 15px;
  background: linear-gradient(135deg, #1A3D28, #2D7A4A);
  color: #fff;
  font-size: .9375rem;
  font-weight: 700;
  border: none;
  border-radius: 14px;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(30,86,49,.35);
  transition: opacity .2s, transform .2s;
  font-family: inherit;
  margin-top: 4px;
}
.btn-login:disabled { opacity: .65; }
.btn-login:not(:disabled):hover { transform: translateY(-1px); }

.footer-note {
  margin-top: 24px;
  font-size: .6875rem;
  color: rgba(255,255,255,.4);
  letter-spacing: .04em;
}
</style>
