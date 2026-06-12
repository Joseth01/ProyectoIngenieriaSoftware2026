<template>
  <ion-page>
    <ion-content :fullscreen="true" class="page-bg">

      <template v-if="vista === 'menu'">

        <div class="app-bar">
          <div class="app-logo">
            <div class="logo-dot">🩺</div>
            <span class="logo-txt">BovWeight CR · Médico</span>
          </div>
          <button class="icon-btn">⚙</button>
        </div>

        <div class="profile-hero">
          <div class="avatar-wrap">
            <div class="avatar-circle">{{ iniciales }}</div>
            <div class="avatar-badge">🩺</div>
          </div>
          <div class="profile-name">Dr. {{ user.name }}</div>
          <div class="profile-role">
            <span class="role-pill veterinario">🩺 Veterinario Colegiado</span>
          </div>
          <div class="profile-sub-email">{{ user.email }}</div>

          <div class="profile-stats">
            <div class="ps-card">
              <div class="ps-ico">📋</div>
              <div class="ps-lbl">Casos Médicos</div>
              <div class="ps-val">12</div>
            </div>
            <div class="ps-card">
              <div class="ps-ico">🏡</div>
              <div class="ps-lbl">Fincas Asistidas</div>
              <div class="ps-val">4</div>
            </div>
          </div>
        </div>

        <div class="body-pad">
          <div style="margin-bottom:14px">
            <span class="premium-badge">⭐ VET PROFESIONAL</span>
          </div>

          <div class="list-item" @click="vista = 'personal'">
            <div class="li-ico">👤</div>
            <div class="li-info">
              <div class="li-title">Información Personal y Colegiado</div>
            </div>
            <span class="chev">›</span>
          </div>

          <div class="list-item" @click="vista = 'consultas'">
            <div class="li-ico">🩺</div>
            <div class="li-info">
              <div class="li-title">Control de Visitas y Consultas</div>
            </div>
            <span class="chev">›</span>
          </div>

          <div class="list-item" style="cursor:default">
            <div class="li-ico"><span>🔔</span></div>
            <div class="li-info">
              <div class="li-title">Notificaciones de Urgencias</div>
            </div>
            <div class="toggle" :class="{ 'toggle-off': !notifOn }" @click="notifOn = !notifOn"></div>
          </div>

          <div class="list-item" @click="router.push('/tabs/ayuda')">
            <div class="li-ico">❓</div>
            <div class="li-info">
              <div class="li-title">Centro de Ayuda Técnico</div>
            </div>
            <span class="chev">›</span>
          </div>

          <button class="btn-logout" @click="cerrarSesion">↪ Cerrar Sesión</button>
        </div>
      </template>

      <template v-else-if="vista === 'personal'">
        <div class="sub-bar">
          <button class="back-btn" @click="vista = 'menu'; editando = false">‹ Perfil</button>
          <div class="sub-bar-title">{{ editando ? 'Editar Médico' : 'Información Personal' }}</div>
        </div>

        <div class="body-pad">
          <div class="profile-card-top">
            <div class="pcb" style="background: #EBF5FF;"></div>
            <div class="avatar-centered">
              <div class="avatar-lg" style="background: linear-gradient(135deg, #1E40AF, #3B82F6);">{{ iniciales }}</div>
            </div>
            <div class="pct-name">Dr. {{ user.name }}</div>
            <button v-if="!editando" class="btn-edit" @click="editando = true">✏ Editar Perfil</button>
          </div>

          <div class="info-card">
            <div class="ic-title">DATOS PROFESIONALES</div>

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
              <label class="field-label">Código de Colegiado (CMV)</label>
              <div class="field-box" :class="{ 'field-readonly': !editando }">
                <span class="field-ico">📜</span>
                <input v-if="editando" v-model="user.codigoColegiado" class="field-input" type="text" placeholder="Ej: Vet-1234" />
                <span v-else class="field-val">{{ user.codigoColegiado || 'No registrado' }}</span>
              </div>
            </div>

            <div class="field-group">
              <label class="field-label">Teléfono de Urgencias</label>
              <div class="field-box" :class="{ 'field-readonly': !editando }">
                <span class="field-ico">📱</span>
                <input v-if="editando" v-model="user.phone" class="field-input" type="tel" />
                <span v-else class="field-val">{{ user.phone || 'No registrado' }}</span>
              </div>
            </div>
          </div>

          <template v-if="editando">
            <button class="btn-primary-vet" @click="guardarCambios">💾 Guardar Cambios</button>
            <button class="btn-outline" @click="editando = false">Cancelar</button>
          </template>
          <template v-else>
            <button class="btn-outline" @click="vista = 'menu'">‹ Volver al Perfil</button>
          </template>
        </div>
      </template>

      <template v-else-if="vista === 'consultas'">
        <div class="sub-bar">
          <button class="back-btn" @click="vista = 'menu'">‹ Perfil</button>
          <div class="sub-bar-title">Visitas Veterinarias</div>
        </div>
        <div class="body-pad">
          <div class="info-card">
            <div class="ic-title">PRÓXIMAS CONSULTAS PROGRAMADAS</div>
            
            <div class="finca-row" style="cursor: default;">
              <div class="fr-info">
                <div class="fr-name">Hacienda El Centeno</div>
                <div class="fr-loc">📅 18 de Junio, 2026 · Chequeo de Preñez</div>
              </div>
              <span class="li-ico-vet">🩺</span>
            </div>

            <div class="finca-row" style="cursor: default;">
              <div class="fr-info">
                <div class="fr-name">Finca Santa María</div>
                <div class="fr-loc">📅 22 de Junio, 2026 · Vacunación de Hato</div>
              </div>
              <span class="li-ico-vet">🩺</span>
            </div>
          </div>
          <button class="btn-outline" @click="vista = 'menu'">‹ Volver al Perfil</button>
        </div>
      </template>

    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { IonPage, IonContent, toastController } from '@ionic/vue';

const router   = useRouter();
const vista    = ref<'menu'|'personal'|'consultas'>('menu');
const editando = ref(false);
const notifOn  = ref(true);

const rawUser = JSON.parse(localStorage.getItem('user') || '{}');
const user = ref({ 
  name: rawUser.name || 'Médico', 
  email: rawUser.email || '', 
  phone: rawUser.phone || '',
  codigoColegiado: rawUser.codigoColegiado || ''
});

const iniciales = computed(() =>
  user.value.name.split(' ').slice(0, 2).map((w: string) => w[0]).join('').toUpperCase() || 'V'
);

const guardarCambios = async () => {
  localStorage.setItem('user', JSON.stringify({ 
    ...rawUser, 
    name: user.value.name, 
    email: user.value.email, 
    phone: user.value.phone,
    codigoColegiado: user.value.codigoColegiado 
  }));
  editando.value = false;
  const t = await toastController.create({ message: 'Perfil médico actualizado correctamente.', duration: 2000, color: 'success' });
  await t.present();
};

const cerrarSesion = () => {
  localStorage.removeItem('user');
  router.push('/login');
};
</script>

<style scoped>
.page-bg { --background: #F4F6F9; }

/* Estilos de rol de veterinario (Color Azul Clínico para diferenciarlo del ganadero) */
.role-pill.veterinario { background: #EBF5FF; color: #1E40AF; display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; margin-top: 6px; }
.profile-sub-email { font-size: 0.75rem; color: #6B7280; margin-top: 4px; }
.btn-primary-vet { width: 100%; padding: 14px; background: linear-gradient(135deg, #1E40AF, #3B82F6); color: #fff; font-size: .9375rem; font-weight: 700; border: none; border-radius: 14px; cursor: pointer; box-shadow: 0 4px 14px rgba(30,64,175,.3); font-family: inherit; display: flex; align-items: center; justify-content: center; gap: 6px; margin-bottom: 10px; }
.li-ico-vet { width: 38px; height: 38px; border-radius: 10px; background: #EBF5FF; display: flex; align-items: center; justify-content: center; font-size: 17px; color: #1E40AF; flex-shrink: 0; }

/* Estilos base reutilizados para consistencia */
.app-bar { background: #fff; padding: 12px 18px 10px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #E5E7EB; }
.app-logo { display: flex; align-items: center; gap: 8px; }
.logo-dot { width: 32px; height: 32px; border-radius: 10px; background: linear-gradient(135deg, #1E40AF, #3B82F6); display: flex; align-items: center; justify-content: center; font-size: 14px; }
.logo-txt { font-size: .9375rem; font-weight: 800; color: #1E40AF; }
.icon-btn { width: 34px; height: 34px; border-radius: 10px; background: #F4F6F9; border: none; cursor: pointer; font-size: 15px; }
.sub-bar { background: #fff; padding: 12px 18px 10px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #E5E7EB; }
.back-btn { background: none; border: none; cursor: pointer; color: #1E40AF; font-size: .875rem; font-weight: 700; padding: 0; font-family: inherit; flex-shrink: 0; }
.sub-bar-title { font-size: 1rem; font-weight: 800; color: #1E40AF; flex: 1; }
.profile-hero { background: #fff; padding: 24px 18px 20px; display: flex; flex-direction: column; align-items: center; border-bottom: 1px solid #E5E7EB; }
.avatar-wrap { position: relative; margin-bottom: 14px; }
.avatar-circle { width: 84px; height: 84px; border-radius: 50%; background: linear-gradient(135deg, #1E40AF, #3B82F6); display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 800; color: #fff; border: 3px solid #BFDBFE; }
.avatar-badge { width: 26px; height: 26px; border-radius: 50%; background: #1E40AF; border: 2.5px solid #fff; position: absolute; bottom: 2px; right: 2px; display: flex; align-items: center; justify-content: center; font-size: .6875rem; color: #fff; }
.profile-name { font-size: 1.375rem; font-weight: 900; color: #111827; text-align: center; letter-spacing: -.3px; }
.profile-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 16px; width: 100%; }
.ps-card { background: #F4F6F9; border-radius: 12px; padding: 12px; text-align: center; }
.ps-ico { font-size: 16px; margin-bottom: 4px; }
.ps-lbl { font-size: .6rem; text-transform: uppercase; letter-spacing: .06em; color: #9CA3AF; font-weight: 700; }
.ps-val { font-size: 1.25rem; font-weight: 900; color: #111827; }
.body-pad { padding: 16px 18px 32px; }
.premium-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 9999px; background: linear-gradient(90deg, #EFF6FF, #DBEAFE); border: 1px solid #3B82F6; font-size: .6875rem; font-weight: 800; color: #1E40AF; }
.list-item { display: flex; align-items: center; gap: 12px; background: #fff; border-radius: 14px; padding: 13px 14px; margin-bottom: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.06); cursor: pointer; transition: box-shadow .15s; }
.list-item:hover { box-shadow: 0 2px 8px rgba(0,0,0,.08); }
.li-ico { width: 38px; height: 38px; border-radius: 10px; background: #F4F6F9; display: flex; align-items: center; justify-content: center; font-size: 17px; color: #1E40AF; flex-shrink: 0; }
.li-info { flex: 1; }
.li-title { font-size: .875rem; font-weight: 600; color: #111827; }
.chev { color: #9CA3AF; font-size: 18px; }
.toggle { width: 46px; height: 26px; border-radius: 13px; background: #1E40AF; position: relative; cursor: pointer; flex-shrink: 0; transition: background .2s; }
.toggle::after { content: ''; width: 20px; height: 20px; border-radius: 50%; background: #fff; position: absolute; right: 3px; top: 3px; box-shadow: 0 1px 4px rgba(0,0,0,.2); transition: transform .2s; }
.toggle-off { background: #D1D5DB; }
.toggle-off::after { transform: translateX(-20px); }
.btn-logout { width: 100%; padding: 14px; background: #FEE2E2; color: #DC2626; font-size: .875rem; font-weight: 700; border: none; border-radius: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 8px; font-family: inherit; }
.btn-outline { width: 100%; padding: 13px; background: transparent; color: #6B7280; font-size: .875rem; font-weight: 700; border: 1.5px solid #E5E7EB; border-radius: 14px; cursor: pointer; font-family: inherit; display: flex; align-items: center; justify-content: center; gap: 6px; margin-bottom: 10px; }
.btn-edit { background: none; border: none; cursor: pointer; font-size: .8125rem; font-weight: 700; color: #1E40AF; padding: 4px 0; font-family: inherit; margin-top: 6px; }
.info-card { background: #fff; border-radius: 16px; padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,.06); margin-bottom: 12px; }
.ic-title { font-size: .6875rem; font-weight: 800; color: #9CA3AF; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 16px; }
.field-group { margin-bottom: 14px; }
.field-label { display: block; font-size: .75rem; font-weight: 700; color: #374151; margin-bottom: 6px; }
.field-box { display: flex; align-items: center; gap: 10px; background: #F4F6F9; border: 1.5px solid #E5E7EB; border-radius: 11px; padding: 11px 13px; }
.field-readonly { background: transparent; border-color: transparent; padding-left: 0; }
.field-ico { font-size: 15px; flex-shrink: 0; color: #9CA3AF; }
.field-input { flex: 1; border: none; background: transparent; outline: none; font-size: .875rem; color: #111827; }
.field-val { font-size: .875rem; font-weight: 600; color: #374151; }
.profile-card-top { background: #fff; border-radius: 16px; margin-bottom: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
.pcb { height: 80px; }
.avatar-centered { display: flex; justify-content: center; margin-top: -40px; }
.avatar-lg { width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; font-weight: 800; color: #fff; border: 4px solid #fff; }
.pct-name { font-size: 1.125rem; font-weight: 800; color: #111827; text-align: center; margin-top: 8px; padding-bottom: 4px; }
.finca-row { display: flex; align-items: center; gap: 12px; background: #fff; border-radius: 14px; padding: 14px; margin-bottom: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
.fr-info { flex: 1; }
.fr-name { font-size: .9375rem; font-weight: 700; color: #111827; }
.fr-loc  { font-size: .6875rem; color: #6B7280; margin-top: 2px; }
</style>