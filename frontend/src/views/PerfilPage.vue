<template>
  <ion-page>
    <ion-content :fullscreen="true" class="page-bg">

      <template v-if="vista === 'menu'">
        <div class="app-bar">
          <div class="app-logo">
            <div class="logo-dot">🐄</div>
            <span class="logo-txt">BovWeight CR</span>
          </div>

          <button class="icon-btn" @click="router.push('/tabs/dashboard')">
            ⚙
          </button>
        </div>

        <div v-if="cargandoPerfil" class="loading-box">
          Cargando perfil...
        </div>

        <div v-else class="profile-hero">
          <div class="avatar-wrap">
            <div class="avatar-circle">{{ iniciales }}</div>
            <div class="avatar-badge">✓</div>
          </div>

          <div class="profile-name">{{ user.name || 'Usuario' }}</div>
          <div class="profile-role">{{ user.email || 'Correo no registrado' }}</div>

          <div class="profile-stats">
            <div class="ps-card">
              <div class="ps-ico">🐄</div>
              <div class="ps-lbl">Cabezas</div>
              <div class="ps-val">{{ totalCabezas }}</div>
            </div>

            <div class="ps-card">
              <div class="ps-ico">🏡</div>
              <div class="ps-lbl">Fincas</div>
              <div class="ps-val">{{ fincas.length }}</div>
            </div>
          </div>
        </div>

        <div class="body-pad">
          <div style="margin-bottom:14px">
            <span class="rol-badge" :class="`rol-${user.rol}`">
              {{ rolLabel }}
            </span>
          </div>

          <div class="list-item" @click="vista = 'personal'">
            <div class="li-ico">👤</div>
            <div class="li-info">
              <div class="li-title">Información Personal</div>
            </div>
            <span class="chev">›</span>
          </div>

          <div class="list-item" @click="vista = 'hacienda'">
            <div class="li-ico">🏡</div>
            <div class="li-info">
              <div class="li-title">Detalles de la Hacienda</div>
            </div>
            <span class="chev">›</span>
          </div>

          <div class="list-item" style="cursor:default">
            <div class="li-ico">🔔</div>
            <div class="li-info">
              <div class="li-title">Notificaciones</div>
            </div>

            <div
              class="toggle"
              :class="{ 'toggle-off': !notifOn }"
              @click.stop="notifOn = !notifOn"
            ></div>
          </div>

          <div class="list-item">
            <div class="li-ico">🌐</div>
            <div class="li-info">
              <div class="li-title">Idioma</div>
              <div class="li-sub">Español</div>
            </div>
            <span class="chev">›</span>
          </div>

          <div class="list-item" @click="router.push('/tabs/ayuda')">
            <div class="li-ico">❓</div>
            <div class="li-info">
              <div class="li-title">Centro de Ayuda</div>
            </div>
            <span class="chev">›</span>
          </div>

          <div class="list-item">
            <div class="li-ico">🔒</div>
            <div class="li-info">
              <div class="li-title">Privacidad y Términos</div>
            </div>
            <span class="chev">›</span>
          </div>

          <button class="btn-logout" @click="cerrarSesion">
            ↪ Cerrar Sesión
          </button>
        </div>
      </template>

      <template v-else-if="vista === 'personal'">
        <div class="sub-bar">
          <button class="back-btn" @click="volverAlMenu">
            ‹ Perfil
          </button>

          <div class="sub-bar-title">
            {{ editando ? 'Editar Perfil' : 'Información Personal' }}
          </div>
        </div>

        <div class="body-pad">
          <div class="profile-card-top">
            <div class="pcb"></div>

            <div class="avatar-centered">
              <div class="avatar-lg">{{ iniciales }}</div>
            </div>

            <div class="pct-name">{{ user.name || 'Usuario' }}</div>

            <button
              v-if="!editando"
              class="btn-edit"
              @click="activarEdicion"
            >
              ✏ Editar Perfil
            </button>
          </div>

          <div class="info-card">
            <div class="ic-title">DATOS DE LA CUENTA</div>

            <div class="field-group">
              <label class="field-label">Nombre Completo</label>

              <div class="field-box" :class="{ 'field-readonly': !editando }">
                <span class="field-ico">👤</span>

                <input
                  v-if="editando"
                  v-model.trim="form.name"
                  class="field-input"
                  type="text"
                  placeholder="Nombre completo"
                />

                <span v-else class="field-val">
                  {{ user.name || 'No registrado' }}
                </span>
              </div>
            </div>

            <div class="field-group">
              <label class="field-label">Correo Electrónico</label>

              <div class="field-box" :class="{ 'field-readonly': !editando }">
                <span class="field-ico">✉</span>

                <input
                  v-if="editando"
                  v-model.trim="form.email"
                  class="field-input"
                  type="email"
                  placeholder="correo@ejemplo.com"
                />

                <span v-else class="field-val">
                  {{ user.email || 'No registrado' }}
                </span>
              </div>
            </div>

            <div class="field-group">
              <label class="field-label">Rol</label>

              <div class="field-box field-readonly">
                <span class="field-ico">🏷️</span>
                <span class="field-val">{{ rolLabel }}</span>
              </div>
            </div>
          </div>

          <template v-if="editando">
            <button
              class="btn-primary"
              :disabled="guardandoPerfil"
              @click="guardarCambios"
            >
              {{ guardandoPerfil ? 'Guardando...' : '💾 Guardar Cambios' }}
            </button>

            <button
              class="btn-outline"
              :disabled="guardandoPerfil"
              @click="cancelarEdicion"
            >
              Cancelar
            </button>
          </template>

          <template v-else>
            <button class="btn-outline" @click="vista = 'menu'">
              ‹ Volver al Perfil
            </button>
          </template>
        </div>
      </template>

      <template v-else-if="vista === 'hacienda'">
        <div class="sub-bar">
          <button class="back-btn" @click="vista = 'menu'">
            ‹ Perfil
          </button>

          <div class="sub-bar-title">Detalles de la Hacienda</div>
        </div>

        <div class="body-pad">
          <div class="hac-metrics">
            <div class="hm-card">
              <div class="hm-ico">🐄</div>
              <div class="hm-val">{{ totalCabezas }}</div>
              <div class="hm-lbl">CABEZAS</div>
            </div>

            <div class="hm-card">
              <div class="hm-ico">🏡</div>
              <div class="hm-val">{{ fincas.length }}</div>
              <div class="hm-lbl">FINCAS</div>
            </div>
          </div>

          <div class="sec-title" style="margin:16px 0 10px">
            Mis Fincas
          </div>

          <div
            v-if="fincas.length === 0"
            class="empty-box"
          >
            No hay fincas registradas para este usuario.
          </div>

          <div
            v-for="(f, i) in fincas"
            :key="f.id || i"
            class="finca-row"
            @click="abrirDetalleFinca(f)"
          >
            <div class="fr-info">
              <div class="fr-name">{{ f.nombre }}</div>

              <div class="fr-loc">
                📍 {{ f.ubicacion || 'Sin ubicación registrada' }}
              </div>

              <div class="fr-loc">
                {{ f.cabezas }} cabezas
              </div>
            </div>

            <span class="chev">›</span>
          </div>

          <button
            class="btn-primary"
            style="margin-top:12px"
            @click="abrirModalFinca"
          >
            ➕ Vincular Nueva Finca
          </button>

          <button class="btn-outline" @click="vista = 'menu'">
            ‹ Volver al Perfil
          </button>
        </div>
      </template>

      <template v-else-if="vista === 'finca-detalle' && fincaSel">
        <div class="sub-bar">
          <button class="back-btn" @click="volverAFincas">
            ‹ Fincas
          </button>

          <div class="sub-bar-title">{{ fincaSel.nombre }}</div>
        </div>

        <div class="body-pad">
          <div class="info-card">
            <div class="ic-title">
              {{ editandoFinca ? 'EDITAR FINCA' : 'EXPEDIENTE DE LA FINCA' }}
            </div>

            <template v-if="!editandoFinca">
              <div class="field-group">
                <label class="field-label">Nombre</label>

                <div class="field-box field-readonly">
                  <span class="field-ico">🏡</span>
                  <span class="field-val">{{ fincaSel.nombre }}</span>
                </div>
              </div>

              <div class="field-group">
                <label class="field-label">Ubicación</label>

                <div class="field-box field-readonly">
                  <span class="field-ico">📍</span>
                  <span class="field-val">
                    {{ fincaSel.ubicacion || 'Sin ubicación registrada' }}
                  </span>
                </div>
              </div>

              <div class="field-group">
                <label class="field-label">Cabezas</label>

                <div class="field-box field-readonly">
                  <span class="field-ico">🐄</span>
                  <span class="field-val">{{ fincaSel.cabezas }}</span>
                </div>
              </div>
            </template>

            <template v-else>
              <div class="field-group">
                <label class="field-label">Nombre de la finca</label>

                <div class="field-box">
                  <input
                    v-model.trim="formFinca.nombre"
                    class="field-input"
                    type="text"
                    placeholder="Nombre de la finca"
                  />
                </div>
              </div>

              <div class="field-group">
                <label class="field-label">Ubicación</label>

                <div class="field-box">
                  <input
                    v-model.trim="formFinca.ubicacion"
                    class="field-input"
                    type="text"
                    placeholder="Ej: Nicoya, Guanacaste"
                  />
                </div>
              </div>

              <div v-if="errorEdicionFinca" class="feedback error">
                {{ errorEdicionFinca }}
              </div>
            </template>
          </div>

          <template v-if="!editandoFinca">
            <button class="btn-primary" @click="activarEdicionFinca">
              Editar finca
            </button>

            <button class="btn-outline" @click="vista = 'hacienda'">
              ‹ Volver a Fincas
            </button>
          </template>

          <template v-else>
            <button
              class="btn-primary"
              :disabled="guardandoEdicionFinca"
              @click="guardarEdicionFinca"
            >
              {{ guardandoEdicionFinca ? 'Guardando...' : 'Guardar cambios' }}
            </button>

            <button
              class="btn-outline"
              :disabled="guardandoEdicionFinca"
              @click="cancelarEdicionFinca"
            >
              Cancelar
            </button>
          </template>
        </div>
      </template>

      <ion-modal :is-open="mostrarModal" @didDismiss="cerrarModalFinca">
        <ion-content class="page-bg">
          <div class="sub-bar">
            <div class="sub-bar-title">Nueva Finca</div>
            <button class="icon-btn" @click="cerrarModalFinca">✕</button>
          </div>

          <div class="body-pad">
            <div class="info-card">
              <div class="field-group">
                <label class="field-label">Nombre de la Finca</label>

                <div class="field-box">
                  <input
                    v-model.trim="nuevaFinca.nombre"
                    class="field-input"
                    placeholder="Ej: Hacienda Santa Cruz"
                  />
                </div>
              </div>

              <div class="field-group">
                <label class="field-label">Ubicación</label>

                <div class="field-box">
                  <input
                    v-model.trim="nuevaFinca.ubicacion"
                    class="field-input"
                    type="text"
                    placeholder="Ej: Nicoya, Guanacaste"
                  />
                </div>
              </div>
            </div>

            <div v-if="errorFinca" class="feedback error">
              {{ errorFinca }}
            </div>

            <div style="display:flex;gap:10px">
              <button
                class="btn-outline"
                style="flex:1"
                :disabled="guardandoFinca"
                @click="cerrarModalFinca"
              >
                Cancelar
              </button>

              <button
                class="btn-primary"
                style="flex:2"
                :disabled="guardandoFinca"
                @click="vincularFinca"
              >
                {{ guardandoFinca ? 'Guardando...' : 'Confirmar' }}
              </button>
            </div>
          </div>
        </ion-content>
      </ion-modal>

    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import {
  IonPage,
  IonContent,
  IonModal,
  toastController,
} from '@ionic/vue';

import {
  getFincas,
  getAnimales,
  type FincaDto,
  type RolUsuario,
} from '@/services/api';

interface UsuarioPerfil {
  id?: number;
  name: string;
  email: string;
  rol: RolUsuario | 'admin';
}

interface FincaLocal {
  id?: number;
  nombre: string;
  ubicacion?: string | null;
  cabezas: number;
}

interface NuevaFincaForm {
  nombre: string;
  ubicacion: string;
}

const router = useRouter();

const API_URL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api';

const vista = ref<'menu' | 'personal' | 'hacienda' | 'finca-detalle'>('menu');
const editando = ref(false);
const notifOn = ref(true);
const mostrarModal = ref(false);
const fincaSel = ref<FincaLocal | null>(null);

const cargandoPerfil = ref(false);
const guardandoPerfil = ref(false);
const guardandoFinca = ref(false);
const errorFinca = ref('');

const editandoFinca = ref(false);
const guardandoEdicionFinca = ref(false);
const errorEdicionFinca = ref('');

const rawUser = obtenerUsuarioLocal();

const user = ref<UsuarioPerfil>({
  id: rawUser.id,
  name: rawUser.name || 'Usuario',
  email: rawUser.email || '',
  rol: (rawUser.rol || 'ganadero') as RolUsuario,
});

const form = ref({
  name: user.value.name,
  email: user.value.email,
});

const formFinca = ref({
  nombre: '',
  ubicacion: '',
});

const fincas = ref<FincaLocal[]>([]);
const totalCabezas = ref(0);

const nuevaFinca = ref<NuevaFincaForm>({
  nombre: '',
  ubicacion: '',
});

const iniciales = computed(() => {
  const nombre = user.value.name || 'Usuario';

  return nombre
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((w: string) => w[0])
    .join('')
    .toUpperCase() || 'U';
});

const rolLabel = computed(() => {
  if (user.value.rol === 'admin') {
    return '🛡️ Administrador';
  }

  if (user.value.rol === 'veterinario') {
    return '🩺 Veterinario';
  }

  return '🐄 Ganadero';
});

onMounted(async () => {
  await cargarPerfil();
  await cargarDatosFincas();
});

function obtenerUsuarioLocal(): any {
  try {
    return JSON.parse(localStorage.getItem('user') || '{}');
  } catch {
    return {};
  }
}

function obtenerToken(): string | null {
  return localStorage.getItem('token');
}

async function mostrarToast(
  message: string,
  color: 'success' | 'warning' | 'danger' | 'medium' = 'medium'
) {
  const toast = await toastController.create({
    message,
    duration: 2200,
    color,
  });

  await toast.present();
}

function extraerErrores(data: any, mensajeDefault: string): string {
  if (data?.errores) {
    return Object.values(data.errores).flat().join(' ');
  }

  return data?.mensaje || mensajeDefault;
}

async function cargarPerfil() {
  const token = obtenerToken();

  if (!token) {
    await mostrarToast('No se encontró sesión activa. Inicia sesión nuevamente.', 'warning');
    router.push('/login');
    return;
  }

  cargandoPerfil.value = true;

  try {
    const response = await fetch(`${API_URL}/usuarios/perfil`, {
      method: 'GET',
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
    });

    const data = await response.json();

    if (!response.ok || !data.exito) {
      throw new Error(data.mensaje || 'No se pudo obtener el perfil.');
    }

    const perfil = data.datos;

    user.value = {
      id: perfil.id,
      name: perfil.name || 'Usuario',
      email: perfil.email || '',
      rol: (perfil.rol || 'ganadero') as RolUsuario,
    };

    form.value = {
      name: user.value.name,
      email: user.value.email,
    };

    localStorage.setItem('user', JSON.stringify(user.value));
  } catch (error) {
    console.error('Error cargando perfil:', error);
    await mostrarToast('No se pudo cargar el perfil del usuario.', 'danger');
  } finally {
    cargandoPerfil.value = false;
  }
}

async function cargarDatosFincas() {
  try {
    const [fincasRaw, animalesRaw] = await Promise.all([
      getFincas(),
      getAnimales(),
    ]);

    const todasFincas = Array.isArray(fincasRaw)
      ? fincasRaw
      : (fincasRaw as any).datos ?? [];

    const todosAnimales = Array.isArray(animalesRaw)
      ? animalesRaw
      : (animalesRaw as any).datos ?? [];

    const userId = user.value.id;

    const fincasData = userId
      ? todasFincas.filter((f: FincaDto) => Number(f.user_id) === Number(userId))
      : todasFincas;

    fincas.value = fincasData.map((f: FincaDto) => ({
      id: f.id,
      nombre: f.nombre,
      ubicacion: f.ubicacion,
      cabezas: todosAnimales.filter((a: any) => Number(a.finca_id) === Number(f.id)).length,
    }));

    totalCabezas.value = fincas.value.reduce(
      (s: number, f: FincaLocal) => s + f.cabezas,
      0
    );
  } catch (error) {
    console.error('Error cargando fincas/animales:', error);
    await mostrarToast('No se pudieron cargar las fincas del usuario.', 'warning');
  }
}

function activarEdicion() {
  form.value = {
    name: user.value.name,
    email: user.value.email,
  };

  editando.value = true;
}

function cancelarEdicion() {
  form.value = {
    name: user.value.name,
    email: user.value.email,
  };

  editando.value = false;
}

function volverAlMenu() {
  cancelarEdicion();
  vista.value = 'menu';
}

function validarFormulario(): boolean {
  if (!form.value.name.trim()) {
    mostrarToast('El nombre completo es obligatorio.', 'warning');
    return false;
  }

  if (!form.value.email.trim()) {
    mostrarToast('El correo electrónico es obligatorio.', 'warning');
    return false;
  }

  const emailValido = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email);

  if (!emailValido) {
    mostrarToast('Ingresa un correo electrónico válido.', 'warning');
    return false;
  }

  return true;
}

async function guardarCambios() {
  if (!validarFormulario()) {
    return;
  }

  const token = obtenerToken();

  if (!token) {
    await mostrarToast('No se encontró sesión activa. Inicia sesión nuevamente.', 'warning');
    router.push('/login');
    return;
  }

  guardandoPerfil.value = true;

  try {
    const response = await fetch(`${API_URL}/usuarios/perfil`, {
      method: 'PUT',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify({
        name: form.value.name,
        email: form.value.email,
      }),
    });

    const data = await response.json();

    if (!response.ok || !data.exito) {
      throw new Error(extraerErrores(data, 'No se pudo actualizar el perfil.'));
    }

    const perfilActualizado = data.datos;

    user.value = {
      id: perfilActualizado.id,
      name: perfilActualizado.name || form.value.name,
      email: perfilActualizado.email || form.value.email,
      rol: (perfilActualizado.rol || user.value.rol || 'ganadero') as RolUsuario,
    };

    form.value = {
      name: user.value.name,
      email: user.value.email,
    };

    localStorage.setItem('user', JSON.stringify(user.value));

    editando.value = false;

    await mostrarToast('Perfil actualizado correctamente.', 'success');
  } catch (error: any) {
    console.error('Error actualizando perfil:', error);
    await mostrarToast(error.message || 'No se pudo actualizar el perfil.', 'danger');
  } finally {
    guardandoPerfil.value = false;
  }
}

function abrirDetalleFinca(finca: FincaLocal) {
  fincaSel.value = finca;
  editandoFinca.value = false;
  errorEdicionFinca.value = '';
  vista.value = 'finca-detalle';
}

function abrirModalFinca() {
  errorFinca.value = '';
  nuevaFinca.value = {
    nombre: '',
    ubicacion: '',
  };
  mostrarModal.value = true;
}

function cerrarModalFinca() {
  mostrarModal.value = false;
  errorFinca.value = '';
  nuevaFinca.value = {
    nombre: '',
    ubicacion: '',
  };
}

async function vincularFinca() {
  errorFinca.value = '';

  if (!nuevaFinca.value.nombre.trim()) {
    errorFinca.value = 'Ingresa el nombre de la finca.';
    return;
  }

  if (!nuevaFinca.value.ubicacion.trim()) {
    errorFinca.value = 'Ingresa la ubicación de la finca.';
    return;
  }

  const token = obtenerToken();

  if (!token) {
    await mostrarToast('No se encontró sesión activa. Inicia sesión nuevamente.', 'warning');
    router.push('/login');
    return;
  }

  const usuarioLocal = obtenerUsuarioLocal();
  const userId = user.value.id || usuarioLocal.id;

  if (!userId) {
    errorFinca.value = 'No se pudo identificar el usuario actual.';
    return;
  }

  guardandoFinca.value = true;

  try {
    const response = await fetch(`${API_URL}/fincas`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify({
        nombre: nuevaFinca.value.nombre.trim(),
        ubicacion: nuevaFinca.value.ubicacion.trim(),
        user_id: userId,
      }),
    });

    const data = await response.json();

    if (!response.ok || !data.exito) {
      throw new Error(extraerErrores(data, 'No se pudo crear la finca.'));
    }

    cerrarModalFinca();

    await cargarDatosFincas();

    await mostrarToast('Finca registrada correctamente.', 'success');
  } catch (error: any) {
    console.error('Error creando finca:', error);
    errorFinca.value = error.message || 'No se pudo crear la finca.';
  } finally {
    guardandoFinca.value = false;
  }
}

function activarEdicionFinca() {
  if (!fincaSel.value) {
    return;
  }

  errorEdicionFinca.value = '';

  formFinca.value = {
    nombre: fincaSel.value.nombre || '',
    ubicacion: fincaSel.value.ubicacion || '',
  };

  editandoFinca.value = true;
}

function cancelarEdicionFinca() {
  editandoFinca.value = false;
  errorEdicionFinca.value = '';

  if (fincaSel.value) {
    formFinca.value = {
      nombre: fincaSel.value.nombre || '',
      ubicacion: fincaSel.value.ubicacion || '',
    };
  }
}

function volverAFincas() {
  editandoFinca.value = false;
  errorEdicionFinca.value = '';
  vista.value = 'hacienda';
}

async function guardarEdicionFinca() {
  if (!fincaSel.value?.id) {
    errorEdicionFinca.value = 'No se pudo identificar la finca seleccionada.';
    return;
  }

  if (!formFinca.value.nombre.trim()) {
    errorEdicionFinca.value = 'Ingresa el nombre de la finca.';
    return;
  }

  if (!formFinca.value.ubicacion.trim()) {
    errorEdicionFinca.value = 'Ingresa la ubicación de la finca.';
    return;
  }

  const token = obtenerToken();

  if (!token) {
    await mostrarToast('No se encontró sesión activa. Inicia sesión nuevamente.', 'warning');
    router.push('/login');
    return;
  }

  const usuarioLocal = obtenerUsuarioLocal();
  const userId = user.value.id || usuarioLocal.id;

  if (!userId) {
    errorEdicionFinca.value = 'No se pudo identificar el usuario actual.';
    return;
  }

  guardandoEdicionFinca.value = true;
  errorEdicionFinca.value = '';

  try {
    const fincaId = fincaSel.value.id;

    const response = await fetch(`${API_URL}/fincas/${fincaId}`, {
      method: 'PUT',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify({
        nombre: formFinca.value.nombre.trim(),
        ubicacion: formFinca.value.ubicacion.trim(),
        user_id: userId,
      }),
    });

    const data = await response.json();

    if (!response.ok || !data.exito) {
      throw new Error(extraerErrores(data, 'No se pudo actualizar la finca.'));
    }

    await cargarDatosFincas();

    const fincaActualizada = fincas.value.find(
      (f) => Number(f.id) === Number(fincaId)
    );

    if (fincaActualizada) {
      fincaSel.value = fincaActualizada;
    }

    editandoFinca.value = false;

    await mostrarToast('Finca actualizada correctamente.', 'success');
  } catch (error: any) {
    console.error('Error actualizando finca:', error);
    errorEdicionFinca.value = error.message || 'No se pudo actualizar la finca.';
  } finally {
    guardandoEdicionFinca.value = false;
  }
}

const cerrarSesion = async () => {
  const token = obtenerToken();

  try {
    if (token) {
      await fetch(`${API_URL}/usuarios/logout`, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${token}`,
        },
      });
    }
  } catch (error) {
    console.warn('No se pudo cerrar sesión en el servidor:', error);
  } finally {
    localStorage.removeItem('user');
    localStorage.removeItem('token');
    router.push('/login');
  }
};
</script>

<style scoped>
.page-bg {
  --background: #F2F5F3;
}

.app-bar {
  background: #fff;
  padding: 12px 18px 10px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #E5E7EB;
}

.app-logo {
  display: flex;
  align-items: center;
  gap: 8px;
}

.logo-dot {
  width: 32px;
  height: 32px;
  border-radius: 10px;
  background: linear-gradient(135deg, #1A3D28, #3A9E61);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
}

.logo-txt {
  font-size: .9375rem;
  font-weight: 800;
  color: #1E5631;
}

.icon-btn {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  background: #F2F5F3;
  border: none;
  cursor: pointer;
  font-size: 15px;
}

.sub-bar {
  background: #fff;
  padding: 12px 18px 10px;
  display: flex;
  align-items: center;
  gap: 10px;
  border-bottom: 1px solid #E5E7EB;
}

.back-btn {
  background: none;
  border: none;
  cursor: pointer;
  color: #2D7A4A;
  font-size: .875rem;
  font-weight: 700;
  padding: 0;
  font-family: inherit;
  flex-shrink: 0;
}

.sub-bar-title {
  font-size: 1rem;
  font-weight: 800;
  color: #1A3D28;
  flex: 1;
}

.loading-box,
.empty-box {
  background: #fff;
  margin: 16px 18px;
  border-radius: 14px;
  padding: 16px;
  color: #6B7280;
  font-size: .875rem;
  font-weight: 700;
  box-shadow: 0 1px 3px rgba(0,0,0,.06);
}

.profile-hero {
  background: #fff;
  padding: 24px 18px 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  border-bottom: 1px solid #E5E7EB;
}

.avatar-wrap {
  position: relative;
  margin-bottom: 14px;
}

.avatar-circle {
  width: 84px;
  height: 84px;
  border-radius: 50%;
  background: linear-gradient(135deg, #2D7A4A, #52B788);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  font-weight: 800;
  color: #fff;
  border: 3px solid #B7E5CC;
}

.avatar-badge {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: #1E5631;
  border: 2.5px solid #fff;
  position: absolute;
  bottom: 2px;
  right: 2px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: .6875rem;
  color: #fff;
}

.profile-name {
  font-size: 1.375rem;
  font-weight: 900;
  color: #111827;
  text-align: center;
  letter-spacing: -.3px;
}

.profile-role {
  font-size: .8125rem;
  color: #6B7280;
  text-align: center;
  margin-top: 4px;
}

.profile-stats {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-top: 16px;
  width: 100%;
}

.ps-card {
  background: #F2F5F3;
  border-radius: 12px;
  padding: 12px;
  text-align: center;
}

.ps-ico {
  font-size: 16px;
  margin-bottom: 4px;
}

.ps-lbl {
  font-size: .6rem;
  text-transform: uppercase;
  letter-spacing: .06em;
  color: #9CA3AF;
  font-weight: 700;
}

.ps-val {
  font-size: 1.25rem;
  font-weight: 900;
  color: #111827;
}

.body-pad {
  padding: 16px 18px 32px;
}

.rol-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 12px;
  border-radius: 9999px;
  font-size: .6875rem;
  font-weight: 800;
  color: #fff;
  letter-spacing: .02em;
}

.rol-ganadero {
  background: linear-gradient(90deg, #1E5631, #3A9E61);
}

.rol-veterinario {
  background: linear-gradient(90deg, #1E3A5F, #2D7AB5);
}

.rol-admin {
  background: linear-gradient(90deg, #4B5563, #111827);
}

.list-item {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #fff;
  border-radius: 14px;
  padding: 13px 14px;
  margin-bottom: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,.06);
  cursor: pointer;
  transition: box-shadow .15s;
}

.list-item:hover {
  box-shadow: 0 2px 8px rgba(0,0,0,.08);
}

.li-ico {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: #EEF9F2;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 17px;
  color: #2D7A4A;
  flex-shrink: 0;
}

.li-info {
  flex: 1;
}

.li-title {
  font-size: .875rem;
  font-weight: 600;
  color: #111827;
}

.li-sub {
  font-size: .75rem;
  color: #6B7280;
  margin-top: 1px;
}

.chev {
  color: #9CA3AF;
  font-size: 18px;
}

.toggle {
  width: 46px;
  height: 26px;
  border-radius: 13px;
  background: #1E5631;
  position: relative;
  cursor: pointer;
  flex-shrink: 0;
  transition: background .2s;
}

.toggle::after {
  content: '';
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #fff;
  position: absolute;
  right: 3px;
  top: 3px;
  box-shadow: 0 1px 4px rgba(0,0,0,.2);
  transition: transform .2s;
}

.toggle-off {
  background: #D1D5DB;
}

.toggle-off::after {
  transform: translateX(-20px);
}

.btn-logout {
  width: 100%;
  padding: 14px;
  background: #FEE2E2;
  color: #DC2626;
  font-size: .875rem;
  font-weight: 700;
  border: none;
  border-radius: 14px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 8px;
  font-family: inherit;
  transition: background .15s;
}

.btn-logout:hover {
  background: #FECACA;
}

.btn-primary {
  width: 100%;
  padding: 14px;
  background: linear-gradient(135deg, #1E5631, #3A9E61);
  color: #fff;
  font-size: .9375rem;
  font-weight: 700;
  border: none;
  border-radius: 14px;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(30,86,49,.3);
  font-family: inherit;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  margin-bottom: 10px;
}

.btn-primary:disabled,
.btn-outline:disabled {
  opacity: .65;
  cursor: not-allowed;
}

.btn-outline {
  width: 100%;
  padding: 13px;
  background: transparent;
  color: #6B7280;
  font-size: .875rem;
  font-weight: 700;
  border: 1.5px solid #E5E7EB;
  border-radius: 14px;
  cursor: pointer;
  font-family: inherit;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  margin-bottom: 10px;
}

.btn-edit {
  background: none;
  border: none;
  cursor: pointer;
  font-size: .8125rem;
  font-weight: 700;
  color: #2D7A4A;
  padding: 4px 0;
  font-family: inherit;
  margin-top: 6px;
}

.info-card {
  background: #fff;
  border-radius: 16px;
  padding: 18px;
  box-shadow: 0 1px 3px rgba(0,0,0,.06);
  margin-bottom: 12px;
}

.ic-title {
  font-size: .6875rem;
  font-weight: 800;
  color: #9CA3AF;
  text-transform: uppercase;
  letter-spacing: .08em;
  margin-bottom: 16px;
}

.field-group {
  margin-bottom: 14px;
}

.field-group:last-child {
  margin-bottom: 0;
}

.field-label {
  display: block;
  font-size: .75rem;
  font-weight: 700;
  color: #374151;
  margin-bottom: 6px;
}

.field-box {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #F2F5F3;
  border: 1.5px solid #E5E7EB;
  border-radius: 11px;
  padding: 11px 13px;
  transition: border-color .15s;
}

.field-box:focus-within {
  border-color: #1E5631;
  background: #fff;
}

.field-readonly {
  background: transparent;
  border-color: transparent;
  padding-left: 0;
}

.field-ico {
  font-size: 15px;
  flex-shrink: 0;
  color: #9CA3AF;
}

.field-input {
  flex: 1;
  border: none;
  background: transparent;
  outline: none;
  font-size: .875rem;
  color: #111827;
  font-family: inherit;
}

.field-val {
  font-size: .875rem;
  font-weight: 600;
  color: #374151;
}

.feedback {
  padding: 12px 14px;
  border-radius: 12px;
  margin-bottom: 12px;
  font-size: .875rem;
  font-weight: 700;
}

.feedback.error {
  background: #FEE2E2;
  color: #991B1B;
}

.profile-card-top {
  background: #fff;
  border-radius: 16px;
  margin-bottom: 12px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0,0,0,.06);
}

.pcb {
  background: #EEF9F2;
  height: 80px;
}

.avatar-centered {
  display: flex;
  justify-content: center;
  margin-top: -40px;
}

.avatar-lg {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(135deg, #2D7A4A, #52B788);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  font-weight: 800;
  color: #fff;
  border: 4px solid #fff;
}

.pct-name {
  font-size: 1.125rem;
  font-weight: 800;
  color: #111827;
  text-align: center;
  margin-top: 8px;
  padding-bottom: 4px;
}

.hac-metrics {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-bottom: 4px;
}

.hm-card {
  background: #fff;
  border-radius: 14px;
  padding: 20px 10px;
  display: flex;
  flex-direction: column;
  align-items: center;
  box-shadow: 0 1px 3px rgba(0,0,0,.06);
}

.hm-ico {
  font-size: 22px;
  margin-bottom: 8px;
}

.hm-val {
  font-size: 2rem;
  font-weight: 900;
  color: #1A3D28;
}

.hm-lbl {
  font-size: .625rem;
  font-weight: 800;
  color: #6B7280;
  text-transform: uppercase;
  letter-spacing: .06em;
}

.sec-title {
  font-size: .875rem;
  font-weight: 700;
  color: #111827;
}

.finca-row {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #fff;
  border-radius: 14px;
  padding: 14px;
  margin-bottom: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,.06);
  cursor: pointer;
  transition: box-shadow .15s;
}

.finca-row:hover {
  box-shadow: 0 2px 8px rgba(0,0,0,.08);
}

.fr-info {
  flex: 1;
}

.fr-name {
  font-size: .9375rem;
  font-weight: 700;
  color: #111827;
}

.fr-loc {
  font-size: .6875rem;
  color: #6B7280;
  margin-top: 2px;
}
</style>