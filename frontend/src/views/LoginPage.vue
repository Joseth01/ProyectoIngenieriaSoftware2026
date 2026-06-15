<template>
  <ion-page>
    <ion-content :fullscreen="true" class="login-content">
      <div class="splash">

        <div class="deco deco-1"></div>
        <div class="deco deco-2"></div>

        <div class="logo-wrap">
          <svg viewBox="0 0 50 50" fill="none" class="logo-svg">
            <ellipse cx="25" cy="32" rx="18" ry="11" fill="rgba(255,255,255,.18)" />
            <circle cx="25" cy="20" r="10" fill="white" />
            <circle cx="20.5" cy="17" r="3.5" fill="rgba(26,61,40,.5)" />
            <circle cx="29.5" cy="17" r="3.5" fill="rgba(26,61,40,.5)" />
            <path
              d="M10 22 Q8 17 11 14"
              stroke="rgba(255,255,255,.7)"
              stroke-width="2.5"
              stroke-linecap="round"
              fill="none"
            />
            <path
              d="M40 22 Q42 17 39 14"
              stroke="rgba(255,255,255,.7)"
              stroke-width="2.5"
              stroke-linecap="round"
              fill="none"
            />
          </svg>
        </div>

        <h1 class="brand">
          Bov<span>Weight</span>
        </h1>

        <p class="brand-sub">COSTA RICA</p>

        <p class="brand-desc">
          Control inteligente de peso para tu ganado bovino
        </p>

        <div class="form-card">

          <div class="tab-row">
            <button
              class="tab-btn"
              :class="{ active: modo === 'login' }"
              @click="cambiarModo('login')"
            >
              Iniciar sesión
            </button>

            <button
              class="tab-btn"
              :class="{ active: modo === 'registro' }"
              @click="cambiarModo('registro')"
            >
              Crear cuenta
            </button>
          </div>

          <div v-if="modo === 'registro'" class="field">
            <label class="field-label">Nombre completo</label>

            <div class="field-input-wrap">
              <span class="field-icon">👤</span>

              <input
                v-model.trim="nombre"
                type="text"
                class="field-input"
                placeholder="Tu nombre"
                autocomplete="name"
                @keyup.enter="submit"
              />
            </div>
          </div>

          <div v-if="modo === 'registro'" class="field">
            <label class="field-label">Soy…</label>

            <div class="rol-grid">
              <button
                type="button"
                class="rol-card"
                :class="{ selected: rol === 'ganadero' }"
                @click="rol = 'ganadero'"
              >
                <span class="rol-ico">🐄</span>
                <span class="rol-name">Ganadero</span>
                <span class="rol-desc">Gestiono mi hato</span>
              </button>

              <button
                type="button"
                class="rol-card"
                :class="{ selected: rol === 'veterinario' }"
                @click="rol = 'veterinario'"
              >
                <span class="rol-ico">🩺</span>
                <span class="rol-name">Veterinario</span>
                <span class="rol-desc">Controlo la salud</span>
              </button>
            </div>
          </div>

          <div class="field">
            <label class="field-label">Correo electrónico</label>

            <div class="field-input-wrap">
              <span class="field-icon">✉</span>

              <input
                v-model.trim="email"
                type="email"
                class="field-input"
                placeholder="tucorreo@ejemplo.com"
                autocomplete="email"
                @keyup.enter="submit"
              />
            </div>
          </div>

          <div class="field">
            <label class="field-label">Contraseña</label>

            <div class="field-input-wrap">
              <span class="field-icon">🔒</span>

              <input
                v-model="password"
                :type="mostrarPassword ? 'text' : 'password'"
                class="field-input"
                placeholder="••••••••"
                :autocomplete="modo === 'login' ? 'current-password' : 'new-password'"
                @keyup.enter="submit"
              />

              <button
                type="button"
                class="toggle-eye"
                @click="mostrarPassword = !mostrarPassword"
              >
                {{ mostrarPassword ? '🙈' : '👁' }}
              </button>
            </div>
          </div>

          <div v-if="modo === 'registro'" class="field">
            <label class="field-label">Confirmar contraseña</label>

            <div class="field-input-wrap">
              <span class="field-icon">🔑</span>

              <input
                v-model="confirmarPassword"
                :type="mostrarPassword ? 'text' : 'password'"
                class="field-input"
                placeholder="••••••••"
                autocomplete="new-password"
                @keyup.enter="submit"
              />
            </div>
          </div>

          <p v-if="errorMsg" class="error-msg">
            {{ errorMsg }}
          </p>

          <p v-if="successMsg" class="success-msg">
            {{ successMsg }}
          </p>

          <button
            class="btn-login"
            :disabled="loading"
            @click="submit"
          >
            <span v-if="loading">
              {{ modo === 'login' ? 'Ingresando…' : 'Creando cuenta…' }}
            </span>

            <span v-else>
              {{ modo === 'login' ? 'Ingresar' : 'Crear cuenta' }}
            </span>
          </button>

        </div>

        <p class="footer-note">
          BovWeight CR · Sistema ganadero inteligente
        </p>

      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import {
  IonPage,
  IonContent,
  onIonViewWillEnter,
} from '@ionic/vue';

import {
  loginUsuario,
  registrarUsuario,
  type RolUsuario,
  type UsuarioDto,
} from '@/services/api';

const router = useRouter();

type Modo = 'login' | 'registro';

const modo = ref<Modo>('login');
const nombre = ref('');
const email = ref('');
const password = ref('');
const confirmarPassword = ref('');
const rol = ref<RolUsuario>('ganadero');
const mostrarPassword = ref(false);
const loading = ref(false);
const errorMsg = ref('');
const successMsg = ref('');

function limpiarMensajes() {
  errorMsg.value = '';
  successMsg.value = '';
}

function limpiarFormulario() {
  nombre.value = '';
  email.value = '';
  password.value = '';
  confirmarPassword.value = '';
  rol.value = 'ganadero';
  mostrarPassword.value = false;
  loading.value = false;
  limpiarMensajes();
}

function limpiarSesionLocal() {
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  localStorage.removeItem('perfil');
  localStorage.removeItem('animalSel');
  localStorage.removeItem('fincaSel');
  localStorage.removeItem('pesajeSel');
  sessionStorage.clear();
}

function prepararLoginSeguro() {
  limpiarSesionLocal();
  limpiarFormulario();
  modo.value = 'login';
}

function cambiarModo(nuevoModo: Modo) {
  modo.value = nuevoModo;
  limpiarMensajes();

  password.value = '';
  confirmarPassword.value = '';
  mostrarPassword.value = false;

  if (nuevoModo === 'login') {
    nombre.value = '';
    rol.value = 'ganadero';
  }
}

function submit() {
  if (loading.value) {
    return;
  }

  if (modo.value === 'login') {
    hacerLogin();
    return;
  }

  hacerRegistro();
}

function emailValido(valor: string): boolean {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valor);
}

function obtenerRutaInicial(usuario: UsuarioDto): string {
  const rolUsuario = String(usuario.rol || '')
    .trim()
    .toLowerCase();

  if (rolUsuario === 'admin') {
    return '/admin/usuarios';
  }

  if (rolUsuario === 'veterinario') {
    return '/veterinario';
  }

  return '/tabs/dashboard';
}

async function hacerLogin() {
  limpiarMensajes();

  if (!email.value.trim() || !password.value) {
    errorMsg.value = 'Ingresa tu correo y contraseña.';
    return;
  }

  if (!emailValido(email.value.trim())) {
    errorMsg.value = 'Ingresa un correo electrónico válido.';
    return;
  }

  limpiarSesionLocal();
  loading.value = true;

  try {
    const usuario = await loginUsuario({
      email: email.value.trim(),
      password: password.value,
    });

    password.value = '';
    confirmarPassword.value = '';

    const rutaInicial = obtenerRutaInicial(usuario);

    router.replace(rutaInicial);
  } catch (e: unknown) {
    limpiarSesionLocal();
    errorMsg.value = e instanceof Error
      ? e.message
      : 'Error al iniciar sesión.';
  } finally {
    loading.value = false;
  }
}

async function hacerRegistro() {
  limpiarMensajes();

  if (
    !nombre.value.trim() ||
    !email.value.trim() ||
    !password.value ||
    !confirmarPassword.value
  ) {
    errorMsg.value = 'Completa todos los campos.';
    return;
  }

  if (!emailValido(email.value.trim())) {
    errorMsg.value = 'Ingresa un correo electrónico válido.';
    return;
  }

  if (password.value.length < 6) {
    errorMsg.value = 'La contraseña debe tener al menos 6 caracteres.';
    return;
  }

  if (password.value !== confirmarPassword.value) {
    errorMsg.value = 'Las contraseñas no coinciden.';
    return;
  }

  limpiarSesionLocal();
  loading.value = true;

  try {
    const usuario = await registrarUsuario({
      name: nombre.value.trim(),
      email: email.value.trim(),
      password: password.value,
      password_confirmation: confirmarPassword.value,
      rol: rol.value,
    });

    password.value = '';
    confirmarPassword.value = '';

    successMsg.value = 'Cuenta creada correctamente. Redirigiendo…';

    setTimeout(() => {
      router.replace(obtenerRutaInicial(usuario));
    }, 900);
  } catch (e: unknown) {
    limpiarSesionLocal();
    errorMsg.value = e instanceof Error
      ? e.message
      : 'Error al crear la cuenta.';
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  prepararLoginSeguro();
});

onIonViewWillEnter(() => {
  prepararLoginSeguro();
});
</script>

<style scoped>
.login-content {
  --background: transparent;
}

.splash {
  min-height: 100vh;
  background: linear-gradient(
    165deg,
    #0D2B1A 0%,
    #1E5631 55%,
    #3A9E61 100%
  );
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
  font-size: 2.25rem;
  font-weight: 900;
  color: white;
  letter-spacing: -1px;
  margin: 0;
}

.brand span {
  color: #74C69D;
}

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

.form-card {
  width: 100%;
  max-width: 420px;
  background: white;
  border-radius: 24px;
  padding: 24px 24px 28px;
  box-shadow: 0 20px 60px rgba(0,0,0,.25);
}

.tab-row {
  display: flex;
  background: #F2F5F3;
  border-radius: 12px;
  padding: 4px;
  margin-bottom: 22px;
  gap: 4px;
}

.tab-btn {
  flex: 1;
  padding: 9px 0;
  border: none;
  border-radius: 9px;
  background: transparent;
  font-size: .875rem;
  font-weight: 600;
  color: #6B7280;
  cursor: pointer;
  transition: background .2s, color .2s, box-shadow .2s;
  font-family: inherit;
}

.tab-btn.active {
  background: white;
  color: #1A3D28;
  box-shadow: 0 1px 6px rgba(0,0,0,.12);
}

.field {
  margin-bottom: 14px;
}

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
  transition: border-color .2s, background .2s;
}

.field-input-wrap:focus-within {
  border-color: #1E5631;
  background: white;
}

.field-icon {
  font-size: 16px;
  flex-shrink: 0;
}

.field-input {
  flex: 1;
  border: none;
  background: transparent;
  outline: none;
  font-size: .9375rem;
  color: #111827;
  font-family: inherit;
}

.field-input::placeholder {
  color: #9CA3AF;
}

.toggle-eye {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 16px;
  padding: 0;
  line-height: 1;
  flex-shrink: 0;
  opacity: .6;
  transition: opacity .2s;
}

.toggle-eye:hover {
  opacity: 1;
}

.error-msg {
  font-size: .8125rem;
  color: #EF4444;
  background: #FEE2E2;
  border-radius: 8px;
  padding: 8px 12px;
  margin: 0 0 14px;
}

.success-msg {
  font-size: .8125rem;
  color: #15803D;
  background: #DCFCE7;
  border-radius: 8px;
  padding: 8px 12px;
  margin: 0 0 14px;
}

.btn-login {
  width: 100%;
  padding: 15px;
  background: linear-gradient(135deg, #1A3D28, #2D7A4A);
  color: white;
  font-size: .9375rem;
  font-weight: 700;
  border: none;
  border-radius: 14px;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(30,86,49,.35);
  transition: opacity .2s, transform .2s;
  font-family: inherit;
  margin-top: 6px;
}

.btn-login:disabled {
  opacity: .65;
}

.btn-login:not(:disabled):hover {
  transform: translateY(-1px);
}

.rol-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}

.rol-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  padding: 14px 8px;
  border-radius: 14px;
  border: 2px solid #E5E7EB;
  background: #F9FAFB;
  cursor: pointer;
  font-family: inherit;
  transition: border-color .18s, background .18s, box-shadow .18s;
}

.rol-card:hover {
  border-color: #6EE7A0;
  background: #F0FDF4;
}

.rol-card.selected {
  border-color: #1E5631;
  background: #ECFDF5;
  box-shadow: 0 0 0 3px rgba(30,86,49,.12);
}

.rol-ico {
  font-size: 1.75rem;
  line-height: 1;
}

.rol-name {
  font-size: .875rem;
  font-weight: 700;
  color: #1A3D28;
}

.rol-desc {
  font-size: .6875rem;
  color: #6B7280;
}

.footer-note {
  margin-top: 24px;
  font-size: .6875rem;
  color: rgba(255,255,255,.4);
  letter-spacing: .04em;
}
</style>