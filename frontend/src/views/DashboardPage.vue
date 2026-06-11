<template>
  <ion-page>
    <ion-content :fullscreen="true" class="dash-content">

      <!-- ── App bar ── -->
      <div class="app-bar">
        <div class="app-logo">
          <div class="logo-dot">🐄</div>
          <span class="logo-txt">BovWeight CR</span>
        </div>
        <button class="icon-btn" @click="goTo('perfil')">⚙</button>
      </div>

      <!-- ── Hero ── -->
      <div class="hero">
        <div class="hero-row">
          <div>
            <div class="greet-sm">{{ saludoHora }},</div>
            <div class="greet-name">{{ userName }} 👋</div>
          </div>
          <div class="avatar-circle" @click="goTo('perfil')">{{ userInitials }}</div>
        </div>
        <div class="hero-metric">
          <div class="hm-label">Peso promedio del hato</div>
          <div class="hm-value">
            {{ pesoPromedio > 0 ? pesoPromedio.toFixed(0) : '—' }}
            <span class="hm-unit">kg</span>
          </div>
          <div class="hm-sub" v-if="pesoPromedio > 0">Basado en últimas mediciones</div>
        </div>
      </div>

      <!-- ── Floating stats ── -->
      <div class="float-stats">
        <div class="fs-card">
          <div class="fs-ico" style="background:#EEF9F2;color:#2D7A4A">🐄</div>
          <div class="fs-val">{{ animales.length }}</div>
          <div class="fs-lbl">Animales</div>
        </div>
        <div class="fs-card">
          <div class="fs-ico" style="background:#FEF3C7;color:#92400E">⚖️</div>
          <div class="fs-val">{{ pesajesHoy }}</div>
          <div class="fs-lbl">Pesados hoy</div>
        </div>
        <div class="fs-card">
          <div class="fs-ico" style="background:#DBEAFE;color:#1D4ED8">📈</div>
          <div class="fs-val">{{ fincas.length }}</div>
          <div class="fs-lbl">Fincas</div>
        </div>
      </div>

      <!-- ── Loading / Error ── -->
      <div v-if="loading" class="status-box status-loading">Cargando datos…</div>
      <div v-else-if="error" class="status-box status-error">{{ error }}</div>

      <!-- ── Body ── -->
      <div class="body-pad">

        <!-- Quick actions -->
        <div class="sec-title">Acciones rápidas</div>

        <!-- AI Live Weighing banner -->
        <div class="ia-banner" @click="goTo('pesaje-vivo')">
          <div class="ia-banner-left">
            <div class="ia-ico">🤖</div>
            <div>
              <div class="ia-title">Pesaje con IA</div>
              <div class="ia-sub">Estimación inteligente desde la cámara</div>
            </div>
          </div>
          <span class="ia-chev">›</span>
        </div>

        <div class="qa-grid">
          <button class="qa-btn" @click="goTo('pesajes')">
            <div class="qa-ico" style="background:#EEF9F2;color:#2D7A4A">⚖️</div>
            <div class="qa-lbl">Registrar peso</div>
          </button>
          <button class="qa-btn" @click="goTo('animales')">
            <div class="qa-ico" style="background:#DBEAFE;color:#1D4ED8">🐄</div>
            <div class="qa-lbl">Ver animales</div>
          </button>
          <button class="qa-btn" @click="goTo('reportes')">
            <div class="qa-ico" style="background:#FEF3C7;color:#92400E">📊</div>
            <div class="qa-lbl">Reportes</div>
          </button>
          <button class="qa-btn" @click="goTo('finca')">
            <div class="qa-ico" style="background:#F3E8FF;color:#7C3AED">🏡</div>
            <div class="qa-lbl">Mi finca</div>
          </button>
        </div>

        <!-- Recent pesajes -->
        <div class="sec-row">
          <div class="sec-title" style="margin-bottom:0">Últimas mediciones</div>
          <button class="link-btn" @click="goTo('animales')">Ver todo</button>
        </div>

        <div v-if="latestPesajes.length === 0 && !loading" class="empty-state">
          No hay pesajes registrados aún.
        </div>

        <div
          v-for="item in latestPesajes"
          :key="item.id"
          class="anim-row"
          @click="goTo('pesajes')"
        >
          <div class="anim-emo">🐄</div>
          <div class="anim-info">
            <div class="anim-name">{{ item.animal }}</div>
            <div class="anim-meta">{{ item.date }}</div>
          </div>
          <div class="anim-right">
            <div class="anim-w">{{ item.weight }} kg</div>
          </div>
        </div>

      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { IonPage, IonContent } from '@ionic/vue';
import { getAnimales, getPesajes, getFincas, pesoNumerico, formatFecha, AnimalDto, PesajeDto, FincaDto } from '@/services/api';

const router   = useRouter();
const userName = ref('Usuario');

const animales = ref<AnimalDto[]>([]);
const pesajes  = ref<PesajeDto[]>([]);
const fincas   = ref<FincaDto[]>([]);
const loading  = ref(true);
const error    = ref('');

// ── Saludo según hora del día ───────────────────────────────────────────────
const saludoHora = computed(() => {
  const h = new Date().getHours();
  if (h < 12) return 'Buenos días';
  if (h < 19) return 'Buenas tardes';
  return 'Buenas noches';
});

const userInitials = computed(() =>
  userName.value.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase() || 'U'
);

const pesoPromedio = computed(() => {
  if (!pesajes.value.length) return 0;
  const total = pesajes.value.reduce((s, p) => s + pesoNumerico(p), 0);
  return total / pesajes.value.length;
});

const pesajesHoy = computed(() => {
  const hoy = new Date().toISOString().slice(0, 10);
  return pesajes.value.filter(p => p.fecha?.slice(0, 10) === hoy).length;
});

const latestPesajes = computed(() =>
  [...pesajes.value]
    .sort((a, b) => new Date(b.fecha).getTime() - new Date(a.fecha).getTime())
    .slice(0, 4)
    .map(p => ({
      id: p.id,
      animal: p.animal?.nombre ?? `Arete ${p.animal?.numero_arete ?? p.animal_id}`,
      weight: pesoNumerico(p).toFixed(0),
      date: formatFecha(p.fecha),
    }))
);

const goTo = (path: string) => router.push(`/tabs/${path}`);

onMounted(async () => {
  const raw = localStorage.getItem('user');

  if (!raw) {
    // Sin sesión → login
    router.replace('/login');
    return;
  }

  const usuario = JSON.parse(raw) as { id?: number; name?: string; email?: string };

  // Sesión antigua (simulada, sin id) → forzar re-login
  if (!usuario.id) {
    localStorage.removeItem('user');
    router.replace('/login');
    return;
  }

  userName.value = usuario.name || usuario.email?.split('@')[0] || 'Usuario';

  try {
    const [aData, pData, fData] = await Promise.all([
      getAnimales(),
      getPesajes(),
      getFincas(),
    ]);
    animales.value = aData;
    pesajes.value  = pData.datos || [];
    fincas.value   = fData;
  } catch (e) {
    error.value = 'No se pudo conectar con el servidor. Verifica que el backend esté activo.';
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.dash-content { --background: var(--bov-bg, #F2F5F3); }

/* App bar */
.app-bar {
  background: #fff;
  padding: 12px 18px 10px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #E5E7EB;
}
.app-logo { display: flex; align-items: center; gap: 8px; }
.logo-dot {
  width: 32px; height: 32px; border-radius: 10px;
  background: linear-gradient(135deg, #1A3D28, #3A9E61);
  display: flex; align-items: center; justify-content: center;
  font-size: 14px;
}
.logo-txt { font-size: .9375rem; font-weight: 800; color: #1E5631; }
.icon-btn {
  width: 34px; height: 34px; border-radius: 10px;
  background: #F2F5F3; border: none; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px; color: #374151;
}

/* Hero */
.hero {
  background: linear-gradient(130deg, #1A3D28 0%, #2D7A4A 100%);
  padding: 18px 18px 32px;
  position: relative;
  overflow: hidden;
}
.hero::after {
  content: '';
  width: 160px; height: 160px; border-radius: 50%;
  background: rgba(255,255,255,.05);
  position: absolute; right: -30px; top: -40px;
}
.hero-row {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 14px;
}
.greet-sm   { font-size: .75rem; color: rgba(255,255,255,.65); }
.greet-name { font-size: 1.0625rem; font-weight: 700; color: #fff; }
.avatar-circle {
  width: 38px; height: 38px; border-radius: 50%;
  background: rgba(255,255,255,.2);
  display: flex; align-items: center; justify-content: center;
  font-size: .8125rem; font-weight: 800; color: #fff;
  border: 2px solid rgba(255,255,255,.3);
  cursor: pointer;
}
.hero-metric {
  background: rgba(255,255,255,.13);
  border: 1px solid rgba(255,255,255,.2);
  border-radius: 12px;
  padding: 14px 16px;
  backdrop-filter: blur(8px);
}
.hm-label { font-size: .6875rem; color: rgba(255,255,255,.65); text-transform: uppercase; letter-spacing: .06em; }
.hm-value { font-size: 2rem; font-weight: 900; color: #fff; letter-spacing: -.5px; line-height: 1.1; }
.hm-unit  { font-size: 1.125rem; font-weight: 500; color: rgba(255,255,255,.7); }
.hm-sub   { font-size: .75rem; color: #74C69D; font-weight: 600; margin-top: 2px; }

/* Floating stats */
.float-stats {
  display: grid; grid-template-columns: repeat(3,1fr);
  gap: 8px; margin: -20px 16px 0;
  position: relative; z-index: 2;
}
.fs-card {
  background: #fff; border-radius: 12px;
  padding: 11px 8px 10px; text-align: center;
  box-shadow: 0 4px 16px rgba(0,0,0,.10);
}
.fs-ico {
  width: 28px; height: 28px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; margin: 0 auto 5px;
}
.fs-val { font-size: 1.125rem; font-weight: 800; color: #111827; }
.fs-lbl { font-size: .5625rem; color: #6B7280; text-transform: uppercase; letter-spacing: .04em; margin-top: 1px; }

/* Status */
.status-box {
  margin: 16px 18px 0;
  padding: 12px 14px;
  border-radius: 12px;
  font-size: .875rem;
  font-weight: 600;
}
.status-loading { background: #F2F5F3; color: #374151; border: 1px solid #E5E7EB; }
.status-error   { background: #FEE2E2; color: #B91C1C; border: 1px solid #FECACA; }

/* Body */
.body-pad { padding: 22px 18px 24px; }

.sec-title {
  font-size: .875rem; font-weight: 700; color: #111827;
  margin-bottom: 12px;
}
.sec-row {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 12px;
}
.link-btn {
  background: none; border: none; cursor: pointer;
  font-size: .6875rem; color: #2D7A4A; font-weight: 600;
  font-family: inherit;
}

/* AI banner */
.ia-banner {
  display: flex; align-items: center; justify-content: space-between;
  background: linear-gradient(130deg, #1A3D28 0%, #2D7A4A 100%);
  border-radius: 14px; padding: 13px 14px;
  margin-bottom: 10px; cursor: pointer;
  box-shadow: 0 4px 14px rgba(26,61,40,.28);
  transition: opacity .2s;
}
.ia-banner:hover { opacity: .92; }
.ia-banner-left { display: flex; align-items: center; gap: 12px; }
.ia-ico {
  width: 40px; height: 40px; border-radius: 11px;
  background: rgba(255,255,255,.15);
  display: flex; align-items: center; justify-content: center;
  font-size: 20px; flex-shrink: 0;
}
.ia-title { font-size: .9375rem; font-weight: 800; color: #fff; }
.ia-sub   { font-size: .6875rem; color: rgba(255,255,255,.7); margin-top: 1px; }
.ia-chev  { color: rgba(255,255,255,.6); font-size: 22px; }

/* Quick actions */
.qa-grid {
  display: grid; grid-template-columns: repeat(4,1fr);
  gap: 8px; margin-bottom: 22px;
}
.qa-btn {
  background: #fff; border-radius: 12px; border: none;
  padding: 10px 4px; display: flex; flex-direction: column;
  align-items: center; gap: 5px; box-shadow: 0 1px 3px rgba(0,0,0,.06);
  cursor: pointer; transition: box-shadow .2s, transform .2s;
  font-family: inherit;
}
.qa-btn:hover { box-shadow: 0 2px 8px rgba(0,0,0,.08); transform: translateY(-1px); }
.qa-ico {
  width: 36px; height: 36px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px;
}
.qa-lbl { font-size: .5625rem; font-weight: 600; color: #374151; text-align: center; line-height: 1.2; }

/* Animal rows */
.empty-state {
  text-align: center; color: #6B7280; font-size: .875rem;
  padding: 24px 0;
}
.anim-row {
  display: flex; align-items: center; gap: 10px;
  background: #fff; border-radius: 12px;
  padding: 11px 13px; margin-bottom: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,.06);
  cursor: pointer; transition: box-shadow .2s;
}
.anim-row:hover { box-shadow: 0 2px 8px rgba(0,0,0,.08); }
.anim-emo {
  width: 40px; height: 40px; border-radius: 11px;
  background: #EEF9F2; border: 1.5px solid #D8F3DC;
  display: flex; align-items: center; justify-content: center;
  font-size: 18px; flex-shrink: 0;
}
.anim-info { flex: 1; min-width: 0; }
.anim-name { font-size: .875rem; font-weight: 600; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.anim-meta { font-size: .6875rem; color: #6B7280; margin-top: 1px; }
.anim-right { text-align: right; flex-shrink: 0; }
.anim-w { font-size: .9375rem; font-weight: 800; color: #1E5631; }
</style>
