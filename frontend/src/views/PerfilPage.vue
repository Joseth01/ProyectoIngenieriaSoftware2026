<template>
  <ion-page>
    <ion-content :fullscreen="true" class="page-bg">

      <!-- ══════════ VISTA: MENÚ PRINCIPAL ══════════ -->
      <template v-if="vista === 'menu'">

        <!-- App bar -->
        <div class="app-bar">
          <div class="app-logo">
            <div class="logo-dot">🐄</div>
            <span class="logo-txt">BovWeight CR</span>
          </div>
          <button class="icon-btn" @click="router.push('/tabs/dashboard')">⚙</button>
        </div>

        <!-- Profile hero -->
        <div class="profile-hero">
          <div class="avatar-wrap">
            <div class="avatar-circle">{{ iniciales }}</div>
            <div class="avatar-badge">✓</div>
          </div>
          <div class="profile-name">{{ user.name }}</div>
          <div class="profile-role">{{ user.email }}</div>
          <div class="profile-stats">
            <div class="ps-card">
              <div class="ps-ico">🐄</div>
              <div class="ps-lbl">Cabezas</div>
              <div class="ps-val">{{ totalCabezas }}</div>
            </div>
            <div class="ps-card">
              <div class="ps-ico">⚖️</div>
              <div class="ps-lbl">Fincas</div>
              <div class="ps-val">{{ fincas.length }}</div>
            </div>
          </div>
        </div>

        <div class="body-pad">
          <!-- Rol badge -->
          <div style="margin-bottom:14px">
            <span class="rol-badge" :class="`rol-${user.rol}`">{{ rolLabel }}</span>
          </div>

          <!-- Menu items -->
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
            <div class="toggle" :class="{ 'toggle-off': !notifOn }" @click="notifOn = !notifOn"></div>
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

          <button class="btn-logout" @click="cerrarSesion">↪ Cerrar Sesión</button>
        </div>
      </template>

      <!-- ══════════ VISTA: INFORMACIÓN PERSONAL ══════════ -->
      <template v-else-if="vista === 'personal'">
        <div class="sub-bar">
          <button class="back-btn" @click="vista = 'menu'; editando = false">‹ Perfil</button>
          <div class="sub-bar-title">{{ editando ? 'Editar Perfil' : 'Información Personal' }}</div>
        </div>

        <div class="body-pad">
          <!-- Card top -->
          <div class="profile-card-top">
            <div class="pcb"></div>
            <div class="avatar-centered">
              <div class="avatar-lg">{{ iniciales }}</div>
            </div>
            <div class="pct-name">{{ user.name }}</div>
            <button v-if="!editando" class="btn-edit" @click="editando = true">✏ Editar Perfil</button>
          </div>

          <!-- Fields -->
          <div class="info-card">
            <div class="ic-title">DATOS DE LA CUENTA</div>

            <div class="field-group">
              <label class="field-label">Nombre Completo</label>
              <div class="field-box" :class="{ 'field-readonly': !editando }">
                <span class="field-ico">👤</span>
                <input v-if="editando" v-model="user.name" class="field-input" type="text" />
                <span v-else class="field-val">{{ user.name }}</span>
              </div>
            </div>

            <div class="field-group">
              <label class="field-label">Correo Electrónico</label>
              <div class="field-box" :class="{ 'field-readonly': !editando }">
                <span class="field-ico">✉</span>
                <input v-if="editando" v-model="user.email" class="field-input" type="email" />
                <span v-else class="field-val">{{ user.email }}</span>
              </div>
            </div>

            <div class="field-group">
              <label class="field-label">Teléfono</label>
              <div class="field-box" :class="{ 'field-readonly': !editando }">
                <span class="field-ico">📱</span>
                <input v-if="editando" v-model="user.phone" class="field-input" type="tel" />
                <span v-else class="field-val">{{ user.phone || 'No registrado' }}</span>
              </div>
            </div>
          </div>

          <template v-if="editando">
            <button class="btn-primary" @click="guardarCambios">💾 Guardar Cambios</button>
            <button class="btn-outline" @click="editando = false">Cancelar</button>
          </template>
          <template v-else>
            <button class="btn-outline" @click="vista = 'menu'">‹ Volver al Perfil</button>
          </template>
        </div>
      </template>

      <!-- ══════════ VISTA: HACIENDA ══════════ -->
      <template v-else-if="vista === 'hacienda'">
        <div class="sub-bar">
          <button class="back-btn" @click="vista = 'menu'">‹ Perfil</button>
          <div class="sub-bar-title">Detalles de la Hacienda</div>
        </div>

        <div class="body-pad">
          <!-- Metrics -->
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

          <div class="sec-title" style="margin:16px 0 10px">Mis Fincas</div>

          <div
            v-for="(f, i) in fincas"
            :key="i"
            class="finca-row"
            @click="fincaSel = f; vista = 'finca-detalle'"
          >
            <div class="fr-info">
              <div class="fr-name">{{ f.nombre }}</div>
              <div class="fr-loc">📍 {{ f.cabezas }} cabezas</div>
            </div>
            <span class="chev">›</span>
          </div>

          <button class="btn-primary" style="margin-top:12px" @click="mostrarModal = true">
            ➕ Vincular Nueva Finca
          </button>
          <button class="btn-outline" @click="vista = 'menu'">‹ Volver al Perfil</button>
        </div>
      </template>

      <!-- ══════════ VISTA: DETALLE FINCA ══════════ -->
      <template v-else-if="vista === 'finca-detalle' && fincaSel">
        <div class="sub-bar">
          <button class="back-btn" @click="vista = 'hacienda'">‹ Fincas</button>
          <div class="sub-bar-title">{{ fincaSel.nombre }}</div>
        </div>

        <div class="body-pad">
          <div class="info-card">
            <div class="ic-title">EXPEDIENTE DE LA FINCA</div>
            <div class="field-group">
              <label class="field-label">Nombre</label>
              <div class="field-box field-readonly">
                <span class="field-ico">🏡</span>
                <span class="field-val">{{ fincaSel.nombre }}</span>
              </div>
            </div>
            <div class="field-group">
              <label class="field-label">Cabezas</label>
              <div class="field-box field-readonly">
                <span class="field-ico">🐄</span>
                <span class="field-val">{{ fincaSel.cabezas }}</span>
              </div>
            </div>
          </div>
          <button class="btn-outline" @click="vista = 'hacienda'">‹ Volver a Fincas</button>
        </div>
      </template>

      <!-- ══════════ MODAL: NUEVA FINCA ══════════ -->
      <ion-modal :is-open="mostrarModal" @didDismiss="mostrarModal = false">
        <ion-content class="page-bg">
          <div class="sub-bar">
            <div class="sub-bar-title">Nueva Finca</div>
            <button class="icon-btn" @click="mostrarModal = false">✕</button>
          </div>
          <div class="body-pad">
            <div class="info-card">
              <div class="field-group">
                <label class="field-label">Nombre de la Finca</label>
                <div class="field-box">
                  <input v-model="nuevaFinca.nombre" class="field-input" placeholder="Ej: Hacienda Santa Cruz" />
                </div>
              </div>
              <div class="field-group">
                <label class="field-label">Número de Cabezas</label>
                <div class="field-box">
                  <input v-model.number="nuevaFinca.cabezas" class="field-input" type="number" placeholder="0" />
                </div>
              </div>
            </div>
            <div style="display:flex;gap:10px">
              <button class="btn-outline" style="flex:1" @click="mostrarModal = false">Cancelar</button>
              <button class="btn-primary" style="flex:2" @click="vincularFinca">Confirmar</button>
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
import { IonPage, IonContent, IonModal, toastController } from '@ionic/vue';
import { getFincasByUsuario, getFincas, getAnimales, type FincaDto, type RolUsuario } from '@/services/api';

interface FincaLocal { nombre: string; cabezas: number; ubicacion?: string | null; }

const router       = useRouter();
const vista        = ref<'menu'|'personal'|'hacienda'|'finca-detalle'>('menu');
const editando     = ref(false);
const notifOn      = ref(true);
const mostrarModal = ref(false);
const fincaSel     = ref<FincaLocal | null>(null);

const rawUser = JSON.parse(localStorage.getItem('user') || '{}');
const user = ref({
  id:    rawUser.id   as number | undefined,
  name:  rawUser.name  || 'Usuario',
  email: rawUser.email || '',
  rol:  (rawUser.rol   || 'ganadero') as RolUsuario,
  phone: '',
});

const iniciales = computed(() =>
  user.value.name.split(' ').slice(0, 2).map((w: string) => w[0]).join('').toUpperCase() || 'U'
);

const rolLabel = computed(() =>
  user.value.rol === 'veterinario' ? '🩺 Veterinario' : '🐄 Ganadero'
);

// Fincas y cabezas desde API
const fincas       = ref<FincaLocal[]>([]);
const totalCabezas = ref(0);

const nuevaFinca = ref<FincaLocal>({ nombre: '', cabezas: 0 });

onMounted(async () => {
  try {
    const [fincasRaw, animalesRaw] = await Promise.all([
      getFincas(),
      getAnimales(),
    ]);

    const todasFincas = Array.isArray(fincasRaw) ? fincasRaw : (fincasRaw as any).datos ?? [];
    const todosAnimales = Array.isArray(animalesRaw) ? animalesRaw : (animalesRaw as any).datos ?? [];

    // Filtrar solo las fincas del usuario logueado
    const userId = rawUser.id;
    const fincasData = userId
      ? todasFincas.filter((f: FincaDto) => f.user_id === userId)
      : todasFincas;

    fincas.value = fincasData.map((f: FincaDto) => ({
      nombre:    f.nombre,
      ubicacion: f.ubicacion,
      cabezas:   todosAnimales.filter((a: any) => a.finca_id === f.id).length,
    }));

    totalCabezas.value = fincas.value.reduce((s: number, f: FincaLocal) => s + f.cabezas, 0);

  } catch (e) {
    console.error('Error cargando perfil:', e);
  }
});

const vincularFinca = async () => {
  if (!nuevaFinca.value.nombre) {
    const t = await toastController.create({ message: 'Ingresa el nombre de la finca.', duration: 2000, color: 'warning' });
    await t.present(); return;
  }
  fincas.value.push({ ...nuevaFinca.value });
  mostrarModal.value = false;
  nuevaFinca.value = { nombre: '', cabezas: 0 };
  const t = await toastController.create({ message: 'Finca vinculada exitosamente.', duration: 2500, color: 'success' });
  await t.present();
};

const guardarCambios = async () => {
  localStorage.setItem('user', JSON.stringify({ ...rawUser, name: user.value.name, email: user.value.email }));
  editando.value = false;
  const t = await toastController.create({ message: 'Perfil actualizado correctamente.', duration: 2000, color: 'success' });
  await t.present();
};

const cerrarSesion = () => {
  localStorage.removeItem('user');
  router.push('/login');
};
</script>

<style scoped>
.page-bg { --background: #F2F5F3; }

/* App bar */
.app-bar {
  background: #fff; padding: 12px 18px 10px;
  display: flex; align-items: center; justify-content: space-between;
  border-bottom: 1px solid #E5E7EB;
}
.app-logo { display: flex; align-items: center; gap: 8px; }
.logo-dot {
  width: 32px; height: 32px; border-radius: 10px;
  background: linear-gradient(135deg, #1A3D28, #3A9E61);
  display: flex; align-items: center; justify-content: center; font-size: 14px;
}
.logo-txt { font-size: .9375rem; font-weight: 800; color: #1E5631; }
.icon-btn {
  width: 34px; height: 34px; border-radius: 10px;
  background: #F2F5F3; border: none; cursor: pointer; font-size: 15px;
}

/* Sub bar (for sub-views) */
.sub-bar {
  background: #fff; padding: 12px 18px 10px;
  display: flex; align-items: center; gap: 10px;
  border-bottom: 1px solid #E5E7EB;
}
.back-btn {
  background: none; border: none; cursor: pointer;
  color: #2D7A4A; font-size: .875rem; font-weight: 700;
  padding: 0; font-family: inherit; flex-shrink: 0;
}
.sub-bar-title { font-size: 1rem; font-weight: 800; color: #1A3D28; flex: 1; }

/* Profile hero */
.profile-hero {
  background: #fff; padding: 24px 18px 20px;
  display: flex; flex-direction: column; align-items: center;
  border-bottom: 1px solid #E5E7EB;
}
.avatar-wrap { position: relative; margin-bottom: 14px; }
.avatar-circle {
  width: 84px; height: 84px; border-radius: 50%;
  background: linear-gradient(135deg, #2D7A4A, #52B788);
  display: flex; align-items: center; justify-content: center;
  font-size: 2rem; font-weight: 800; color: #fff;
  border: 3px solid #B7E5CC;
}
.avatar-badge {
  width: 26px; height: 26px; border-radius: 50%;
  background: #1E5631; border: 2.5px solid #fff;
  position: absolute; bottom: 2px; right: 2px;
  display: flex; align-items: center; justify-content: center;
  font-size: .6875rem; color: #fff;
}
.profile-name { font-size: 1.375rem; font-weight: 900; color: #111827; text-align: center; letter-spacing: -.3px; }
.profile-role { font-size: .8125rem; color: #6B7280; text-align: center; margin-top: 4px; }
.profile-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 16px; width: 100%; }
.ps-card { background: #F2F5F3; border-radius: 12px; padding: 12px; text-align: center; }
.ps-ico { font-size: 16px; margin-bottom: 4px; }
.ps-lbl { font-size: .6rem; text-transform: uppercase; letter-spacing: .06em; color: #9CA3AF; font-weight: 700; }
.ps-val { font-size: 1.25rem; font-weight: 900; color: #111827; }

/* Body */
.body-pad { padding: 16px 18px 32px; }

/* Rol badge */
.rol-badge {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 4px 12px; border-radius: 9999px;
  font-size: .6875rem; font-weight: 800; color: #fff;
  letter-spacing: .02em;
}
.rol-ganadero    { background: linear-gradient(90deg, #1E5631, #3A9E61); }
.rol-veterinario { background: linear-gradient(90deg, #1E3A5F, #2D7AB5); }

/* List items */
.list-item {
  display: flex; align-items: center; gap: 12px;
  background: #fff; border-radius: 14px; padding: 13px 14px;
  margin-bottom: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.06);
  cursor: pointer; transition: box-shadow .15s;
}
.list-item:hover { box-shadow: 0 2px 8px rgba(0,0,0,.08); }
.li-ico {
  width: 38px; height: 38px; border-radius: 10px;
  background: #EEF9F2; display: flex; align-items: center; justify-content: center;
  font-size: 17px; color: #2D7A4A; flex-shrink: 0;
}
.li-info { flex: 1; }
.li-title { font-size: .875rem; font-weight: 600; color: #111827; }
.li-sub   { font-size: .75rem; color: #6B7280; margin-top: 1px; }
.chev { color: #9CA3AF; font-size: 18px; }

/* Toggle */
.toggle {
  width: 46px; height: 26px; border-radius: 13px;
  background: #1E5631; position: relative; cursor: pointer; flex-shrink: 0;
  transition: background .2s;
}
.toggle::after {
  content: ''; width: 20px; height: 20px; border-radius: 50%; background: #fff;
  position: absolute; right: 3px; top: 3px;
  box-shadow: 0 1px 4px rgba(0,0,0,.2); transition: transform .2s;
}
.toggle-off { background: #D1D5DB; }
.toggle-off::after { transform: translateX(-20px); }

/* Buttons */
.btn-logout {
  width: 100%; padding: 14px;
  background: #FEE2E2; color: #DC2626;
  font-size: .875rem; font-weight: 700;
  border: none; border-radius: 14px; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 8px;
  margin-top: 8px; font-family: inherit; transition: background .15s;
}
.btn-logout:hover { background: #FECACA; }

.btn-primary {
  width: 100%; padding: 14px;
  background: linear-gradient(135deg, #1E5631, #3A9E61); color: #fff;
  font-size: .9375rem; font-weight: 700; border: none; border-radius: 14px;
  cursor: pointer; box-shadow: 0 4px 14px rgba(30,86,49,.3);
  font-family: inherit; display: flex; align-items: center; justify-content: center;
  gap: 6px; margin-bottom: 10px;
}
.btn-outline {
  width: 100%; padding: 13px;
  background: transparent; color: #6B7280;
  font-size: .875rem; font-weight: 700;
  border: 1.5px solid #E5E7EB; border-radius: 14px;
  cursor: pointer; font-family: inherit; display: flex;
  align-items: center; justify-content: center; gap: 6px;
  margin-bottom: 10px;
}
.btn-edit {
  background: none; border: none; cursor: pointer;
  font-size: .8125rem; font-weight: 700; color: #2D7A4A;
  padding: 4px 0; font-family: inherit; margin-top: 6px;
}

/* Info card */
.info-card {
  background: #fff; border-radius: 16px; padding: 18px;
  box-shadow: 0 1px 3px rgba(0,0,0,.06); margin-bottom: 12px;
}
.ic-title {
  font-size: .6875rem; font-weight: 800; color: #9CA3AF;
  text-transform: uppercase; letter-spacing: .08em; margin-bottom: 16px;
}
.field-group { margin-bottom: 14px; }
.field-group:last-child { margin-bottom: 0; }
.field-label { display: block; font-size: .75rem; font-weight: 700; color: #374151; margin-bottom: 6px; }
.field-box {
  display: flex; align-items: center; gap: 10px;
  background: #F2F5F3; border: 1.5px solid #E5E7EB;
  border-radius: 11px; padding: 11px 13px; transition: border-color .15s;
}
.field-box:focus-within { border-color: #1E5631; background: #fff; }
.field-readonly { background: transparent; border-color: transparent; padding-left: 0; }
.field-ico { font-size: 15px; flex-shrink: 0; color: #9CA3AF; }
.field-input { flex: 1; border: none; background: transparent; outline: none; font-size: .875rem; color: #111827; font-family: inherit; }
.field-val { font-size: .875rem; font-weight: 600; color: #374151; }

/* Profile card top (personal view) */
.profile-card-top {
  background: #fff; border-radius: 16px; margin-bottom: 12px; overflow: hidden;
  box-shadow: 0 1px 3px rgba(0,0,0,.06);
}
.pcb { background: #EEF9F2; height: 80px; }
.avatar-centered { display: flex; justify-content: center; margin-top: -40px; }
.avatar-lg {
  width: 80px; height: 80px; border-radius: 50%;
  background: linear-gradient(135deg, #2D7A4A, #52B788);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.75rem; font-weight: 800; color: #fff;
  border: 4px solid #fff;
}
.pct-name { font-size: 1.125rem; font-weight: 800; color: #111827; text-align: center; margin-top: 8px; padding-bottom: 4px; }

/* Hacienda */
.hac-metrics { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 4px; }
.hm-card {
  background: #fff; border-radius: 14px; padding: 20px 10px;
  display: flex; flex-direction: column; align-items: center;
  box-shadow: 0 1px 3px rgba(0,0,0,.06);
}
.hm-ico { font-size: 22px; margin-bottom: 8px; }
.hm-val { font-size: 2rem; font-weight: 900; color: #1A3D28; }
.hm-lbl { font-size: .625rem; font-weight: 800; color: #6B7280; text-transform: uppercase; letter-spacing: .06em; }
.sec-title { font-size: .875rem; font-weight: 700; color: #111827; }
.finca-row {
  display: flex; align-items: center; gap: 12px;
  background: #fff; border-radius: 14px; padding: 14px;
  margin-bottom: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.06);
  cursor: pointer; transition: box-shadow .15s;
}
.finca-row:hover { box-shadow: 0 2px 8px rgba(0,0,0,.08); }
.fr-info { flex: 1; }
.fr-name { font-size: .9375rem; font-weight: 700; color: #111827; }
.fr-loc  { font-size: .6875rem; color: #6B7280; margin-top: 2px; }
</style>
