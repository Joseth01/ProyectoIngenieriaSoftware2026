<template>
  <ion-page>
    <ion-content :fullscreen="true" class="page-bg">

      <!-- App bar -->
      <div class="app-bar">
        <button class="back-btn" @click="router.back()">‹</button>
        <div class="app-bar-center">
          <div class="page-title">Recordatorios de pesaje</div>
          <div class="page-sub">Te avisamos cuándo volver a pesar</div>
        </div>
        <div style="width:34px"></div>
      </div>

      <div class="body-pad">

        <!-- Configuración -->
        <div class="config-card">
          <div class="config-row">
            <div>
              <div class="config-title">Activar recordatorios</div>
              <div class="config-sub">Programa avisos para volver a pesar cada animal</div>
            </div>
            <div
              class="toggle"
              :class="{ 'toggle-off': !config.activo }"
              @click="alternarActivo"
            ></div>
          </div>

          <div class="config-divider"></div>

          <div class="config-field">
            <label class="config-label">Pesar cada</label>
            <div class="interval-input">
              <input
                v-model.number="config.intervaloDias"
                type="number"
                min="1"
                max="365"
                @change="onIntervaloChange"
              />
              <span>días</span>
            </div>
          </div>

          <div class="quick-pills">
            <button
              v-for="d in [15, 30, 60, 90]"
              :key="d"
              class="pill"
              :class="{ 'pill-active': config.intervaloDias === d }"
              @click="setIntervalo(d)"
            >
              {{ d }} días
            </button>
          </div>
        </div>

        <!-- Aviso plataforma web -->
        <div v-if="!soportaNotif" class="web-note">
          ℹ️ Las notificaciones automáticas funcionan en la app instalada (Android/iOS).
          En el navegador puedes ver la lista de próximos pesajes aquí abajo.
        </div>

        <!-- Feedback -->
        <div v-if="mensaje" class="feedback" :class="mensajeOk ? 'feedback-ok' : 'feedback-err'">
          {{ mensaje }}
        </div>

        <!-- Resumen -->
        <div v-if="config.activo" class="resumen-row">
          <div class="resumen-card resumen-venc">
            <div class="resumen-num">{{ vencidos.length }}</div>
            <div class="resumen-lbl">Vencidos</div>
          </div>
          <div class="resumen-card resumen-prox">
            <div class="resumen-num">{{ proximos.length }}</div>
            <div class="resumen-lbl">Próximos 7 días</div>
          </div>
        </div>

        <!-- Estado de carga -->
        <div v-if="loading" class="status-box">Cargando animales…</div>

        <!-- Lista de recordatorios -->
        <template v-else-if="config.activo">
          <div class="sec-title">Próximos pesajes</div>

          <div v-if="recordatorios.length === 0" class="empty-state">
            No hay animales activos para programar.
          </div>

          <div
            v-for="r in recordatorios"
            :key="r.animalId"
            class="rec-item"
            :class="{ 'rec-vencido': r.vencido }"
            @click="irAPesar"
          >
            <div class="rec-ico">{{ r.vencido ? '⚠️' : '🐄' }}</div>
            <div class="rec-body">
              <div class="rec-name">{{ r.animalNombre }}</div>
              <div class="rec-meta">
                Arete {{ r.arete }} ·
                <template v-if="r.sinPesajes">sin pesajes aún</template>
                <template v-else>último: {{ formatFecha(r.ultimoPesaje!) }}</template>
              </div>
            </div>
            <div class="rec-right">
              <div class="rec-fecha">{{ formatFecha(r.proximaFecha) }}</div>
              <div class="rec-dias" :class="r.vencido ? 'dias-venc' : 'dias-ok'">
                {{ etiquetaDias(r) }}
              </div>
            </div>
          </div>
        </template>

        <div v-else class="empty-state">
          Activa los recordatorios para ver los próximos pesajes.
        </div>

      </div>

    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { IonPage, IonContent, onIonViewWillEnter } from '@ionic/vue';
import {
  getAnimales,
  getPesajes,
  formatFecha,
  type AnimalDto,
  type PesajeDto,
} from '@/services/api';
import {
  type RecordatoriosConfig,
  type RecordatorioCalculado,
  leerConfig,
  guardarConfig,
  notificacionesDisponibles,
  pedirPermiso,
  calcularRecordatorios,
  reprogramarNotificaciones,
  cancelarTodas,
} from '@/services/recordatorios';

const router = useRouter();

const animales = ref<AnimalDto[]>([]);
const pesajes = ref<PesajeDto[]>([]);
const loading = ref(true);

const config = ref<RecordatoriosConfig>(leerConfig());
const soportaNotif = notificacionesDisponibles();

const mensaje = ref('');
const mensajeOk = ref(true);

const recordatorios = computed<RecordatorioCalculado[]>(() => {
  if (!config.value.activo) return [];
  return calcularRecordatorios(animales.value, pesajes.value, config.value.intervaloDias);
});

const vencidos = computed(() => recordatorios.value.filter(r => r.vencido));
const proximos = computed(() =>
  recordatorios.value.filter(r => !r.vencido && r.diasRestantes <= 7)
);

function etiquetaDias(r: RecordatorioCalculado): string {
  if (r.vencido) {
    const d = Math.abs(r.diasRestantes);
    return d === 0 ? 'Hoy' : `Hace ${d} día(s)`;
  }
  return `En ${r.diasRestantes} día(s)`;
}

function mostrarMensaje(texto: string, ok = true) {
  mensaje.value = texto;
  mensajeOk.value = ok;
  setTimeout(() => { mensaje.value = ''; }, 3500);
}

async function sincronizar() {
  guardarConfig(config.value);

  if (!config.value.activo) {
    await cancelarTodas();
    return;
  }

  if (soportaNotif) {
    const ok = await pedirPermiso();
    if (!ok) {
      mostrarMensaje('No se concedió permiso de notificaciones.', false);
      return;
    }
    const n = await reprogramarNotificaciones(recordatorios.value);
    mostrarMensaje(`Recordatorios activos: ${n} notificación(es) programada(s).`);
  } else {
    mostrarMensaje('Recordatorios activos. Verás la lista de próximos pesajes aquí.');
  }
}

async function alternarActivo() {
  config.value.activo = !config.value.activo;
  await sincronizar();
}

function setIntervalo(dias: number) {
  config.value.intervaloDias = dias;
  if (config.value.activo) sincronizar();
}

function onIntervaloChange() {
  if (!config.value.intervaloDias || config.value.intervaloDias < 1) {
    config.value.intervaloDias = 1;
  }
  if (config.value.intervaloDias > 365) {
    config.value.intervaloDias = 365;
  }
  if (config.value.activo) sincronizar();
}

function irAPesar() {
  router.push('/tabs/pesajes');
}

async function cargar() {
  loading.value = true;
  try {
    const [aRes, pRes] = await Promise.all([getAnimales(), getPesajes()]);
    animales.value = aRes.datos || [];
    pesajes.value = pRes.datos || [];

    // Si están activos, reprograma con datos frescos al entrar
    if (config.value.activo && soportaNotif) {
      await reprogramarNotificaciones(recordatorios.value);
    }
  } catch (e) {
    console.error('Error cargando recordatorios:', e);
  } finally {
    loading.value = false;
  }
}

onMounted(cargar);
onIonViewWillEnter(cargar);
</script>

<style scoped>
.page-bg { --background: #F2F5F3; }

.app-bar {
  background: #fff; padding: 12px 16px 10px;
  display: flex; align-items: center; gap: 10px;
  border-bottom: 1px solid #E5E7EB;
}
.back-btn {
  width: 34px; height: 34px; border-radius: 10px;
  background: #F2F5F3; border: none; cursor: pointer;
  font-size: 22px; color: #374151; line-height: 1;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.app-bar-center { flex: 1; }
.page-title { font-size: 1.125rem; font-weight: 800; color: #1A3D28; }
.page-sub { font-size: .6875rem; color: #6B7280; margin-top: 1px; }

.body-pad { padding: 16px; }

.config-card {
  background: #fff; border-radius: 16px; padding: 16px;
  box-shadow: 0 1px 3px rgba(0,0,0,.06); margin-bottom: 14px;
}
.config-row {
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
}
.config-title { font-size: .9rem; font-weight: 800; color: #111827; }
.config-sub { font-size: .72rem; color: #6B7280; margin-top: 2px; }
.config-divider { height: 1px; background: #F3F4F6; margin: 14px 0; }
.config-field { display: flex; align-items: center; justify-content: space-between; }
.config-label { font-size: .85rem; font-weight: 700; color: #374151; }
.interval-input {
  display: flex; align-items: center; gap: 6px;
  border: 1.5px solid #E5E7EB; border-radius: 10px; padding: 6px 10px; background: #F9FAFB;
}
.interval-input input {
  width: 56px; border: none; background: transparent; outline: none;
  font-size: .95rem; font-weight: 800; color: #111827; text-align: right; font-family: inherit;
}
.interval-input span { font-size: .8rem; color: #6B7280; font-weight: 700; }

.quick-pills { display: flex; gap: 8px; margin-top: 12px; }
.pill {
  flex: 1; padding: 8px; border-radius: 10px; border: 1.5px solid #E5E7EB;
  background: #fff; color: #6B7280; font-size: .72rem; font-weight: 800;
  cursor: pointer; font-family: inherit;
}
.pill-active { background: #1E5631; color: #fff; border-color: #1E5631; }

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

.web-note {
  background: #EFF6FF; border: 1.5px solid #BFDBFE; color: #1E40AF;
  border-radius: 12px; padding: 10px 14px; font-size: .75rem;
  line-height: 1.4; margin-bottom: 14px;
}

.feedback {
  padding: 10px 14px; border-radius: 10px; font-size: .8rem;
  font-weight: 600; margin-bottom: 14px;
}
.feedback-ok { background: #EEF9F2; color: #1E5631; border: 1px solid #D8F3DC; }
.feedback-err { background: #FEE2E2; color: #B91C1C; border: 1px solid #FECACA; }

.resumen-row { display: flex; gap: 10px; margin-bottom: 16px; }
.resumen-card {
  flex: 1; border-radius: 14px; padding: 14px; text-align: center;
}
.resumen-venc { background: #FEE2E2; }
.resumen-prox { background: #FEF3C7; }
.resumen-num { font-size: 1.6rem; font-weight: 900; color: #111827; }
.resumen-venc .resumen-num { color: #B91C1C; }
.resumen-prox .resumen-num { color: #92400E; }
.resumen-lbl { font-size: .68rem; font-weight: 700; color: #6B7280; margin-top: 2px; }

.sec-title {
  font-size: .8rem; font-weight: 900; color: #6B7280;
  text-transform: uppercase; letter-spacing: .05em; margin-bottom: 10px;
}

.status-box, .empty-state {
  background: #F3F4F6; color: #6B7280; border-radius: 12px;
  padding: 18px; text-align: center; font-size: .85rem;
}

.rec-item {
  display: flex; align-items: center; gap: 12px;
  background: #fff; border-radius: 14px; padding: 12px 14px;
  margin-bottom: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.06); cursor: pointer;
}
.rec-vencido { border: 1.5px solid #FCA5A5; }
.rec-ico {
  width: 38px; height: 38px; border-radius: 11px; background: #EEF9F2;
  display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0;
}
.rec-vencido .rec-ico { background: #FEE2E2; }
.rec-body { flex: 1; min-width: 0; }
.rec-name { font-size: .875rem; font-weight: 800; color: #111827; }
.rec-meta { font-size: .7rem; color: #6B7280; margin-top: 1px; }
.rec-right { text-align: right; flex-shrink: 0; }
.rec-fecha { font-size: .78rem; font-weight: 800; color: #1A3D28; }
.rec-dias { font-size: .68rem; font-weight: 800; margin-top: 1px; }
.dias-ok { color: #2D7A4A; }
.dias-venc { color: #B91C1C; }
</style>
