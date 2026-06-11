<template>
  <ion-page>
    <ion-content :fullscreen="true" class="page-bg">

      <!-- App bar -->
      <div class="app-bar">
        <div class="app-logo">
          <div class="logo-dot">🐄</div>
          <span class="logo-txt">BovWeight CR</span>
        </div>

        <button class="icon-btn" @click="recargar">
          🔄
        </button>
      </div>

      <div class="body-pad">

        <div class="page-title">
          Reportes
        </div>

        <div class="page-sub">
          Visualiza y exporta el rendimiento de tu hato
        </div>

        <!-- Bar chart card -->
        <div class="chart-card" style="margin-top:16px">

          <div class="chart-header">
            <span class="chart-title">
              Histórico de Peso
            </span>

            <span class="badge-pill">
              6 meses
            </span>
          </div>

          <div
            v-if="loading"
            class="status-box status-loading"
            style="margin:0"
          >
            Cargando…
          </div>

          <div v-else>

            <div class="bars-wrap">

              <div
                v-for="(b, i) in barras"
                :key="i"
                class="bar-col"
              >

                <div
                  class="bar-fill"
                  :class="{ 'bar-active': i === barras.length - 1 }"
                  :style="{ height: b.pct + '%' }"
                ></div>

                <div
                  class="bar-lbl"
                  :class="{ 'bar-lbl-active': i === barras.length - 1 }"
                >
                  {{ b.mes }}
                </div>

              </div>

            </div>

            <div class="chart-meta">

              <div class="cm-item">
                <span class="cm-ico">📈</span>

                <div>
                  <div class="cm-val">
                    {{ crecimientoStr }}
                  </div>

                  <div class="cm-lbl">
                    Crecimiento
                  </div>
                </div>
              </div>

              <div class="cm-item">
                <span class="cm-ico">📊</span>

                <div>
                  <div class="cm-val">
                    {{ promedioStr }}
                  </div>

                  <div class="cm-lbl">
                    Promedio
                  </div>
                </div>
              </div>

            </div>

          </div>

        </div>

        <!-- Reportes disponibles -->
        <div
          class="sec-title"
          style="margin-top:20px;margin-bottom:10px"
        >
          Reportes Disponibles
        </div>

        <div
          v-for="r in tiposReporte"
          :key="r.key"
          class="rpt-item"
          @click="generarReporte(r.key)"
        >

          <div class="rpt-ico">
            {{ r.ico }}
          </div>

          <div class="rpt-info">

            <div class="rpt-title">
              {{ r.titulo }}
            </div>

            <div class="rpt-sub">
              {{ r.sub }}
            </div>

          </div>

          <span class="chev">›</span>

        </div>

        <!-- PDF button -->
        <button class="btn-pdf" @click="descargarPDF">
          <span>📄</span>
          Descargar Reporte PDF
        </button>

        <div
          v-if="pdfMsg"
          class="feedback"
          :class="pdfOk ? 'feedback-ok' : 'feedback-err'"
        >
          {{ pdfMsg }}
        </div>

        <!-- Historial de reportes -->
        <div v-if="reportes.length > 0">

          <div
            class="sec-title"
            style="margin-top:20px;margin-bottom:10px"
          >
            Reportes generados
          </div>

          <div
            v-for="rep in reportes"
            :key="rep.id"
            class="rpt-hist-row"
          >

            <div class="rh-ico">
              📋
            </div>

            <div class="rh-info">

              <div class="rh-tipo">
                {{ rep.tipo }}
              </div>

              <div class="rh-fecha">
                {{ formatFecha(rep.fecha) }}
              </div>

            </div>

            <a
              v-if="rep.archivo_url"
              :href="rep.archivo_url"
              target="_blank"
              class="rh-link"
            >
              Ver
            </a>

          </div>

        </div>

        <div
          v-else-if="!loading"
          class="empty-state"
        >
          No hay reportes generados todavía.
        </div>

      </div>

    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">

import {
  ref,
  computed,
  onMounted
} from 'vue';

import {
  IonPage,
  IonContent
} from '@ionic/vue';

import {
  getPesajes,
  getReportes,
  crearReporte,
  pesoNumerico,
  formatFecha,
  getUsuarioLocal,
  type PesajeDto,
  type ReporteDto
} from '@/services/api';

const pesajes = ref<PesajeDto[]>([]);
const reportes = ref<ReporteDto[]>([]);

const loading = ref(true);

const pdfMsg = ref('');
const pdfOk = ref(false);

const MESES = [
  'Ene',
  'Feb',
  'Mar',
  'Abr',
  'May',
  'Jun',
  'Jul',
  'Ago',
  'Sep',
  'Oct',
  'Nov',
  'Dic'
];

const barras = computed(() => {

  const now = new Date();

  return Array.from(
    { length: 6 },
    (_, i) => {

      const d = new Date(
        now.getFullYear(),
        now.getMonth() - (5 - i),
        1
      );

      const key =
        `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;

      const grupo = pesajes.value.filter(
        p => p.fecha?.startsWith(key)
      );

      const prom = grupo.length
        ? grupo.reduce(
            (s, p) => s + pesoNumerico(p),
            0
          ) / grupo.length
        : 0;

      return {
        mes: MESES[d.getMonth()],
        prom,
        pct: 0
      };

    }
  ).map((b, _, arr) => {

    const max = Math.max(
      ...arr.map(x => x.prom),
      1
    );

    return {
      ...b,
      pct: Math.round((b.prom / max) * 90) || 6
    };

  });

});

const promedioStr = computed(() => {

  if (!pesajes.value.length) {
    return '— kg';
  }

  const avg =
    pesajes.value.reduce(
      (s, p) => s + pesoNumerico(p),
      0
    ) / pesajes.value.length;

  return `${avg.toFixed(0)} kg`;

});

const crecimientoStr = computed(() => {

  const arr = barras.value;

  if (
    arr.length < 2 ||
    arr[0].prom === 0
  ) {
    return '—';
  }

  const crec =
    (
      (arr[arr.length - 1].pct - arr[0].pct) /
      Math.max(arr[0].pct, 1)
    ) * 100;

  return `${crec >= 0 ? '+' : ''}${crec.toFixed(1)}%`;

});

const tiposReporte = [
  {
    key: 'rendimiento',
    ico: '📈',
    titulo: 'Rendimiento Mensual',
    sub: 'Eficiencia de engorde y conversión'
  },
  {
    key: 'inventario',
    ico: '📋',
    titulo: 'Inventario de Ganado',
    sub: 'Resumen por razas y edades'
  },
  {
    key: 'salud',
    ico: '🏥',
    titulo: 'Salud y Condición',
    sub: 'Historial de condición corporal'
  },
  {
    key: 'fincas',
    ico: '🗺️',
    titulo: 'Distribución por Fincas',
    sub: 'Densidad de carga por sector'
  }
];

const nombreTipoReporte = (
  key: string
): string => {

  const item = tiposReporte.find(
    tipo => tipo.key === key
  );

  return item?.titulo || key;

};

const generarReporte = async (
  tipo: string
) => {

  try {

    const user =
      getUsuarioLocal();

    if (!user?.id) {
      throw new Error(
        'No se encontró el usuario autenticado.'
      );
    }

    const nuevo =
      await crearReporte({
        tipo: nombreTipoReporte(tipo),
        fecha: new Date()
          .toISOString()
          .slice(0, 10),
        user_id: user.id,
        archivo_url: null
      });

    reportes.value.unshift(
      nuevo.datos
    );

    pdfMsg.value =
      `Reporte "${nombreTipoReporte(tipo)}" generado.`;

    pdfOk.value = true;

  } catch (error: any) {

    console.error(error);

    pdfMsg.value =
      error?.message ||
      'No se pudo generar el reporte.';

    pdfOk.value = false;

  }

  setTimeout(() => {
    pdfMsg.value = '';
  }, 3000);

};

const descargarPDF = () => {
  generarReporte('PDF General');
};

const recargar = async () => {

  loading.value = true;

  try {

    const [
      pData,
      rData
    ] = await Promise.all([
      getPesajes(),
      getReportes()
    ]);

    pesajes.value =
      pData.datos || [];

    reportes.value =
      rData.datos || [];

  } catch (error) {

    console.error(error);

    pesajes.value = [];
    reportes.value = [];

  } finally {

    loading.value = false;

  }

};

onMounted(() => {
  recargar();
});

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

.body-pad {
  padding: 16px 18px 32px;
}

.page-title {
  font-size: 1.625rem;
  font-weight: 900;
  color: #1A3D28;
  letter-spacing: -.4px;
}

.page-sub {
  font-size: .8125rem;
  color: #6B7280;
  margin-top: 3px;
}

.chart-card {
  background: #fff;
  border-radius: 16px;
  padding: 16px;
  box-shadow: 0 1px 3px rgba(0,0,0,.06);
}

.chart-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}

.chart-title {
  font-size: .875rem;
  font-weight: 700;
  color: #111827;
}

.badge-pill {
  padding: 3px 10px;
  border-radius: 9999px;
  background: #EEF9F2;
  color: #1E5631;
  font-size: .625rem;
  font-weight: 700;
}

.bars-wrap {
  display: flex;
  align-items: flex-end;
  gap: 8px;
  height: 110px;
  background: #EEF9F2;
  border-radius: 8px;
  padding: 10px 10px 24px;
  margin-bottom: 12px;
  position: relative;
  overflow: visible;
}

.bar-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  height: 100%;
  justify-content: flex-end;
}

.bar-fill {
  width: 100%;
  border-radius: 4px 4px 0 0;
  background: #B7E5CC;
  transition: height .4s ease;
  min-height: 6px;
}

.bar-fill.bar-active {
  background: #1E5631;
}

.bar-lbl {
  font-size: .5rem;
  font-weight: 600;
  color: #9CA3AF;
  position: absolute;
  bottom: 4px;
}

.bar-lbl.bar-lbl-active {
  color: #1E5631;
  font-weight: 800;
}

.chart-meta {
  display: flex;
  gap: 16px;
  padding-top: 10px;
  border-top: 1px solid #E5E7EB;
}

.cm-item {
  display: flex;
  align-items: center;
  gap: 6px;
}

.cm-ico {
  font-size: 16px;
}

.cm-val {
  font-size: .8125rem;
  font-weight: 700;
  color: #111827;
}

.cm-lbl {
  font-size: .625rem;
  color: #6B7280;
}

.status-box {
  padding: 10px 12px;
  border-radius: 10px;
  font-size: .875rem;
}

.status-loading {
  background: #F2F5F3;
  color: #374151;
}

.sec-title {
  font-size: .875rem;
  font-weight: 700;
  color: #111827;
}

.rpt-item {
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

.rpt-item:hover {
  box-shadow: 0 2px 8px rgba(0,0,0,.08);
}

.rpt-ico {
  width: 40px;
  height: 40px;
  border-radius: 11px;
  background: #EEF9F2;
  border: 1px solid #D8F3DC;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
}

.rpt-info {
  flex: 1;
}

.rpt-title {
  font-size: .875rem;
  font-weight: 700;
  color: #111827;
}

.rpt-sub {
  font-size: .6875rem;
  color: #6B7280;
  margin-top: 1px;
}

.chev {
  color: #9CA3AF;
  font-size: 18px;
}

.btn-pdf {
  width: 100%;
  padding: 15px;
  background: #1A3D28;
  color: #fff;
  font-size: .9375rem;
  font-weight: 700;
  border: none;
  border-radius: 14px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  box-shadow: 0 4px 14px rgba(26,61,40,.35);
  font-family: inherit;
  margin-top: 4px;
  transition: opacity .2s;
}

.btn-pdf:hover {
  opacity: .92;
}

.feedback {
  padding: 10px 14px;
  border-radius: 10px;
  font-size: .8125rem;
  font-weight: 600;
  margin-top: 10px;
}

.feedback-ok {
  background: #EEF9F2;
  color: #1E5631;
  border: 1px solid #D8F3DC;
}

.feedback-err {
  background: #FEE2E2;
  color: #B91C1C;
  border: 1px solid #FECACA;
}

.rpt-hist-row {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #fff;
  border-radius: 12px;
  padding: 11px 13px;
  margin-bottom: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,.06);
}

.rh-ico {
  font-size: 18px;
  flex-shrink: 0;
}

.rh-info {
  flex: 1;
}

.rh-tipo {
  font-size: .875rem;
  font-weight: 600;
  color: #111827;
}

.rh-fecha {
  font-size: .6875rem;
  color: #6B7280;
}

.rh-link {
  font-size: .75rem;
  font-weight: 700;
  color: #2D7A4A;
  text-decoration: none;
  padding: 4px 10px;
  border: 1.5px solid #D8F3DC;
  border-radius: 8px;
}

.empty-state {
  margin-top: 18px;
  text-align: center;
  color: #6B7280;
  font-size: .875rem;
}

</style>