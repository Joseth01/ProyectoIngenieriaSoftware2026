<template>
  <ion-page>

    <!-- ═══════════════ DRAWER OVERLAY ═══════════════ -->
    <Transition name="drawer">
      <div v-if="drawerOpen" class="drawer-overlay" @click.self="drawerOpen = false">
        <div class="drawer-panel">

          <!-- User header -->
          <div class="drawer-user">
            <div class="drawer-avatar-row">
              <div class="drawer-avatar">
                {{ userInitials }}
                <span class="drawer-cr">CR</span>
              </div>
              <div class="drawer-user-info">
                <div class="drawer-name">{{ userName }}</div>
                <div class="drawer-email">{{ userEmail }}</div>
              </div>
            </div>
            <span class="premium-badge">⭐ PREMIUM</span>
          </div>

          <!-- Nav items -->
          <div class="drawer-scroll">
            <div class="drawer-sec">GENERAL</div>

            <div
              class="drawer-item"
              :class="{ 'drawer-item-active': currentRoute === '/tabs/dashboard' }"
              @click="navTo('/tabs/dashboard')"
            >
              <div class="di-ico">🏠</div>
              <span class="di-lbl">Panel de Control</span>
            </div>

            <div
              class="drawer-item"
              :class="{ 'drawer-item-active': currentRoute === '/tabs/finca' }"
              @click="navTo('/tabs/finca')"
            >
              <div class="di-ico">🏡</div>
              <span class="di-lbl">Configuración de Finca</span>
            </div>

            <div
              class="drawer-item"
              :class="{ 'drawer-item-active': currentRoute === '/tabs/animales' }"
              @click="navTo('/tabs/animales')"
            >
              <div class="di-ico">🐄</div>
              <span class="di-lbl">Animales</span>
            </div>

            <div
              class="drawer-item"
              :class="{ 'drawer-item-active': currentRoute === '/tabs/reportes' }"
              @click="navTo('/tabs/reportes')"
            >
              <div class="di-ico">📊</div>
              <span class="di-lbl">Reportes</span>
            </div>

            <div class="drawer-sec" style="margin-top:8px">SOPORTE</div>

            <div
              class="drawer-item"
              :class="{ 'drawer-item-active': currentRoute === '/tabs/ayuda' }"
              @click="navTo('/tabs/ayuda')"
            >
              <div class="di-ico">❓</div>
              <span class="di-lbl">Centro de Ayuda</span>
            </div>
          </div>

          <!-- Logout -->
          <div class="drawer-footer">
            <button class="btn-logout" @click="logout">
              <span>↪</span> Cerrar Sesión
            </button>
          </div>

        </div>
      </div>
    </Transition>

    <!-- ═══════════════ TABS ═══════════════ -->
    <ion-tabs>
      <ion-router-outlet></ion-router-outlet>

      <ion-tab-bar slot="bottom">

        <!-- Inicio -->
        <ion-tab-button
          :class="{ 'tab-active': currentRoute === '/tabs/dashboard' }"
          @click="navTo('/tabs/dashboard')"
        >
          <ion-icon :icon="homeOutline" />
          <ion-label>Inicio</ion-label>
        </ion-tab-button>

        <!-- Animales -->
        <ion-tab-button
          :class="{ 'tab-active': currentRoute === '/tabs/animales' }"
          @click="navTo('/tabs/animales')"
        >
          <ion-icon :icon="pawOutline" />
          <ion-label>Animales</ion-label>
        </ion-tab-button>

        <!-- FAB central → Pesajes -->
        <ion-tab-button class="tab-fab" @click="navTo('/tabs/pesajes')">
          <div class="fab-btn">＋</div>
        </ion-tab-button>

        <!-- Reportes -->
        <ion-tab-button
          :class="{ 'tab-active': currentRoute === '/tabs/reportes' }"
          @click="navTo('/tabs/reportes')"
        >
          <ion-icon :icon="barChartOutline" />
          <ion-label>Reportes</ion-label>
        </ion-tab-button>

        <!-- Menú / Drawer -->
        <ion-tab-button @click="drawerOpen = true">
          <ion-icon :icon="menuOutline" />
          <ion-label>Menú</ion-label>
        </ion-tab-button>

      </ion-tab-bar>
    </ion-tabs>

  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import {
  IonPage, IonTabs, IonRouterOutlet, IonTabBar, IonTabButton,
  IonLabel, IonIcon,
} from '@ionic/vue';
import {
  homeOutline, pawOutline, barChartOutline, menuOutline,
} from 'ionicons/icons';

const router     = useRouter();
const route      = useRoute();
const drawerOpen = ref(false);
const userName   = ref('Usuario');
const userEmail  = ref('');

const currentRoute = computed(() => route.path);
const userInitials = computed(() => {
  return userName.value
    .split(' ')
    .slice(0, 2)
    .map(w => w[0])
    .join('')
    .toUpperCase() || 'U';
});

const navTo = (path: string) => {
  router.push(path);
  drawerOpen.value = false;
};

const logout = () => {
  localStorage.removeItem('user');
  drawerOpen.value = false;
  router.push('/login');
};

onMounted(() => {
  const raw = localStorage.getItem('user');
  if (!raw) { router.push('/login'); return; }
  const u = JSON.parse(raw);
  userName.value  = u.name  || 'Usuario';
  userEmail.value = u.email || '';
});
</script>

<style scoped>
/* ── Drawer overlay ── */
.drawer-overlay {
  position: fixed;
  inset: 0;
  z-index: 9000;
  display: flex;
  background: rgba(0,0,0,.35);
  backdrop-filter: blur(2px);
}

.drawer-panel {
  width: 72%;
  max-width: 300px;
  height: 100%;
  background: #fff;
  display: flex;
  flex-direction: column;
  box-shadow: 6px 0 24px rgba(0,0,0,.15);
  overflow: hidden;
}

/* Transition */
.drawer-enter-active, .drawer-leave-active { transition: opacity .22s ease; }
.drawer-enter-from, .drawer-leave-to       { opacity: 0; }
.drawer-enter-active .drawer-panel,
.drawer-leave-active .drawer-panel         { transition: transform .22s ease; }
.drawer-enter-from .drawer-panel,
.drawer-leave-to .drawer-panel             { transform: translateX(-100%); }

/* User header */
.drawer-user {
  padding: 28px 16px 16px;
  border-bottom: 1px solid #E5E7EB;
}
.drawer-avatar-row {
  display: flex;
  align-items: center;
  gap: 11px;
  margin-bottom: 10px;
}
.drawer-avatar {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: linear-gradient(135deg, #2D7A4A, #52B788);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.125rem;
  font-weight: 800;
  color: #fff;
  border: 2.5px solid #B7E5CC;
  flex-shrink: 0;
  position: relative;
}
.drawer-cr {
  position: absolute;
  bottom: -2px;
  right: -2px;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: #1E5631;
  border: 2px solid #fff;
  font-size: .45rem;
  font-weight: 800;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
}
.drawer-user-info { min-width: 0; }
.drawer-name  { font-size: .9375rem; font-weight: 800; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.drawer-email { font-size: .6875rem; color: #6B7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.premium-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 10px;
  border-radius: 9999px;
  background: linear-gradient(90deg, #FEF3C7, #FDE68A);
  border: 1px solid #F59E0B;
  font-size: .6875rem;
  font-weight: 800;
  color: #92400E;
}

/* Nav items */
.drawer-scroll {
  flex: 1;
  overflow-y: auto;
  padding: 6px 0;
}
.drawer-sec {
  font-size: .6rem;
  font-weight: 800;
  color: #9CA3AF;
  text-transform: uppercase;
  letter-spacing: .1em;
  padding: 12px 16px 4px;
}
.drawer-item {
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 12px 16px;
  cursor: pointer;
  transition: background .15s;
}
.drawer-item:hover { background: #F2F5F3; }
.drawer-item-active { background: #EEF9F2; }
.di-ico {
  width: 34px;
  height: 34px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  flex-shrink: 0;
  background: transparent;
  transition: background .15s;
}
.drawer-item-active .di-ico { background: #D8F3DC; }
.di-lbl {
  font-size: .875rem;
  font-weight: 500;
  color: #374151;
}
.drawer-item-active .di-lbl { font-weight: 700; color: #1E5631; }

/* Footer logout */
.drawer-footer {
  padding: 12px 16px 24px;
  border-top: 1px solid #E5E7EB;
}
.btn-logout {
  width: 100%;
  padding: 13px;
  background: #FEE2E2;
  color: #DC2626;
  font-size: .875rem;
  font-weight: 700;
  border: none;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  cursor: pointer;
  font-family: inherit;
  transition: background .15s;
}
.btn-logout:hover { background: #FECACA; }

/* ── Bottom tab bar ── */
ion-tab-bar {
  height: 64px;
  padding-bottom: 8px;
}

ion-tab-button {
  --color: #9CA3AF;
  --color-selected: #1E5631;
  font-size: .5625rem;
  font-weight: 600;
}
ion-tab-button ion-label {
  font-size: .5625rem;
  font-weight: 600;
}

/* Active state override */
.tab-active {
  --color: #1E5631;
}

/* FAB tab button */
.tab-fab {
  --color: transparent !important;
  flex-shrink: 0;
}
.fab-btn {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: linear-gradient(135deg, #1E5631, #3A9E61);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: #fff;
  box-shadow: 0 4px 14px rgba(30,86,49,.4);
  margin-top: -18px;
  border: 3px solid #fff;
  font-weight: 400;
  line-height: 1;
}
</style>
