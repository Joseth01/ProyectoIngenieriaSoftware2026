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

        <!-- ── Reportes disponibles (acordeón) ── -->
        <div class="sec-title" style="margin-top:20px;margin-bottom:10px">Reportes Disponibles</div>

        <template v-for="r in tiposReporte" :key="r.key">
          <!-- Fila clickeable -->
          <div
            class="rpt-item"
            :class="{ 'rpt-item-active': reporteActivo === r.key }"
            @click="seleccionarReporte(r.key)"
          >
            <div class="rpt-ico">{{ r.ico }}</div>
            <div class="rpt-info">
              <div class="rpt-title">{{ r.titulo }}</div>
              <div class="rpt-sub">{{ r.sub }}</div>
            </div>
            <span class="chev" :class="{ 'chev-open': reporteActivo === r.key }">›</span>
          </div>

          <!-- Panel del reporte (se abre bajo la fila) -->
          <div v-if="reporteActivo === r.key" class="rpt-panel">

            <!-- ── RENDIMIENTO MENSUAL ── -->
            <template v-if="r.key === 'rendimiento'">
              <div class="rpt-stats">
                <div class="rs-card">
                  <div class="rs-val">{{ pesajes.length }}</div>
                  <div class="rs-lbl">Pesajes</div>
                </div>
                <div class="rs-card">
                  <div class="rs-val">{{ promedioStr }}</div>
                  <div class="rs-lbl">Promedio</div>
                </div>
                <div class="rs-card">
                  <div class="rs-val">{{ crecimientoStr }}</div>
                  <div class="rs-lbl">Crecimiento</div>
                </div>
              </div>
              <div class="tbl-wrap">
                <table class="rpt-tbl">
                  <thead><tr><th>Mes</th><th>Pesajes</th><th>Prom.</th><th>Δ ant.</th></tr></thead>
                  <tbody>
                    <tr v-for="m in datosRendimiento" :key="m.mes">
                      <td>{{ m.mes }}</td>
                      <td>{{ m.total }}</td>
                      <td>{{ m.prom > 0 ? m.prom.toFixed(0) + ' kg' : '—' }}</td>
                      <td :class="getDeltaClass(m.delta)">{{ formatDelta(m.delta) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </template>

            <!-- ── INVENTARIO ── -->
            <template v-else-if="r.key === 'inventario'">
              <div class="rpt-stats">
                <div class="rs-card">
                  <div class="rs-val">{{ animales.length }}</div>
                  <div class="rs-lbl">Cabezas</div>
                </div>
                <div class="rs-card">
                  <div class="rs-val">{{ datosInventario.length }}</div>
                  <div class="rs-lbl">Razas</div>
                </div>
              </div>
              <div class="tbl-wrap">
                <table class="rpt-tbl">
                  <thead><tr><th>Raza</th><th>Cant.</th><th>%</th><th>Prom.</th></tr></thead>
                  <tbody>
                    <tr v-for="row in datosInventario" :key="row.raza">
                      <td>{{ row.raza }}</td>
                      <td>{{ row.cantidad }}</td>
                      <td>{{ row.pct }}%</td>
                      <td>{{ row.promedio !== '—' ? row.promedio + ' kg' : '—' }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </template>

            <!-- ── SALUD Y CONDICIÓN ── -->
            <template v-else-if="r.key === 'salud'">
              <div class="rpt-stats">
                <div class="rs-card">
                  <div class="rs-val">{{ datosSalud.filter(a => !a.sinPeso).length }}</div>
                  <div class="rs-lbl">Con registro</div>
                </div>
                <div class="rs-card" :class="{ 'rs-card-warn': datosSalud.filter(a => a.sinPeso).length > 0 }">
                  <div class="rs-val">{{ datosSalud.filter(a => a.sinPeso).length }}</div>
                  <div class="rs-lbl">Sin peso</div>
                </div>
              </div>
              <div class="tbl-wrap">
                <table class="rpt-tbl">
                  <thead><tr><th>Arete</th><th>Nombre</th><th>Último peso</th><th>Fecha</th><th>Estado</th></tr></thead>
                  <tbody>
                    <tr v-for="a in datosSalud" :key="a.arete" :class="{ 'row-warn': a.sinPeso }">
                      <td>{{ a.arete }}</td>
                      <td>{{ a.nombre }}</td>
                      <td>{{ a.ultimoPeso }}</td>
                      <td>{{ a.fechaUltimo }}</td>
                      <td><span class="estado-chip">{{ a.estado }}</span></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </template>

            <!-- ── DISTRIBUCIÓN POR FINCAS ── -->
            <template v-else-if="r.key === 'fincas'">
              <div class="rpt-stats">
                <div class="rs-card">
                  <div class="rs-val">{{ datosFincas.length }}</div>
                  <div class="rs-lbl">Fincas</div>
                </div>
                <div class="rs-card">
                  <div class="rs-val">{{ animales.length }}</div>
                  <div class="rs-lbl">Cabezas</div>
                </div>
              </div>
              <div class="tbl-wrap">
                <table class="rpt-tbl">
                  <thead><tr><th>Finca</th><th>Animales</th><th>Prom.</th><th>Total</th></tr></thead>
                  <tbody>
                    <tr v-for="f in datosFincas" :key="f.finca">
                      <td>{{ f.finca }}</td>
                      <td>{{ f.animales }}</td>
                      <td>{{ f.promedio !== '—' ? f.promedio + ' kg' : '—' }}</td>
                      <td>{{ Number(f.total) > 0 ? f.total + ' kg' : '—' }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </template>

            <!-- Botón descarga individual -->
            <button class="btn-pdf-sm" @click.stop="descargarPDFReporte(r.key)">
              📄 Descargar PDF de este reporte
            </button>
          </div>
        </template>

        <!-- ── Botón PDF completo ── -->
        <button class="btn-pdf" style="margin-top:12px" @click="descargarPDFCompleto">
          <span>📄</span> Descargar Reporte PDF Completo
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
import { ref, computed, onMounted } from 'vue';
import { IonPage, IonContent } from '@ionic/vue';
import { jsPDF } from 'jspdf';
import {
  getPesajes, getAnimales, getReportes, crearReporte,
  pesoNumerico, formatFecha,
  type PesajeDto, type AnimalDto, type ReporteDto,
} from '@/services/api';

// ── Estado ──────────────────────────────────────────────────────────────────
const pesajes       = ref<PesajeDto[]>([]);
const animales      = ref<AnimalDto[]>([]);
const reportes      = ref<ReporteDto[]>([]);
const loading       = ref(true);
const pdfMsg        = ref('');
const pdfOk         = ref(false);
const reporteActivo = ref<string | null>(null);

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

// ── Gráfico de barras ───────────────────────────────────────────────────────
const barras = computed(() => {

  const now = new Date();
  return Array.from({ length: 6 }, (_, i) => {
    const d   = new Date(now.getFullYear(), now.getMonth() - (5 - i), 1);
    const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
    const grupo = pesajes.value.filter(p => p.fecha?.startsWith(key));
    const prom  = grupo.length
      ? grupo.reduce((s, p) => s + pesoNumerico(p), 0) / grupo.length
      : 0;
    return { mes: MESES[d.getMonth()], prom, pct: 0 };
  }).map((b, _, arr) => {
    const max = Math.max(...arr.map(x => x.prom), 1);
    return { ...b, pct: Math.round((b.prom / max) * 90) || 6 };
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

// ── Datos por reporte ───────────────────────────────────────────────────────
const datosRendimiento = computed(() => {
  const now = new Date();
  const meses = Array.from({ length: 6 }, (_, i) => {
    const d   = new Date(now.getFullYear(), now.getMonth() - (5 - i), 1);
    const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
    const label = `${MESES[d.getMonth()]} ${d.getFullYear()}`;
    const grupo = pesajes.value.filter(p => p.fecha?.startsWith(key));
    const prom  = grupo.length ? grupo.reduce((s, p) => s + pesoNumerico(p), 0) / grupo.length : 0;
    return { mes: label, prom, total: grupo.length };
  });
  return meses.map((m, i, arr) => ({
    ...m,
    delta: (i > 0 && arr[i - 1].prom > 0)
      ? ((m.prom - arr[i - 1].prom) / arr[i - 1].prom * 100)
      : null as number | null,
  }));
});

const datosInventario = computed(() => {
  const map = new Map<string, { count: number; pesoTotal: number; conPeso: number }>();
  for (const a of animales.value) {
    const raza = a.raza?.nombre || 'Sin raza';
    if (!map.has(raza)) map.set(raza, { count: 0, pesoTotal: 0, conPeso: 0 });
    const e = map.get(raza)!;
    e.count++;
    const ps = (a.pesajes || []).sort((x, y) => new Date(y.fecha).getTime() - new Date(x.fecha).getTime());
    if (ps.length) { e.pesoTotal += pesoNumerico(ps[0]); e.conPeso++; }
  }
  const total = animales.value.length || 1;
  return Array.from(map.entries())
    .map(([raza, d]) => ({
      raza,
      cantidad: d.count,
      pct: ((d.count / total) * 100).toFixed(1),
      promedio: d.conPeso > 0 ? (d.pesoTotal / d.conPeso).toFixed(0) : '—',
    }))
    .sort((a, b) => b.cantidad - a.cantidad);
});

const datosSalud = computed(() =>
  animales.value.map(a => {
    const ps = (a.pesajes || []).sort((x, y) => new Date(y.fecha).getTime() - new Date(x.fecha).getTime());
    const ultimo = ps[0] ?? null;
    return {
      arete:       a.numero_arete,
      nombre:      a.nombre || '—',
      finca:       a.finca?.nombre || 'Sin finca',
      ultimoPeso:  ultimo ? `${pesoNumerico(ultimo).toFixed(0)} kg` : '— kg',
      fechaUltimo: ultimo ? formatFecha(ultimo.fecha) : '—',
      estado:      a.estado,
      sinPeso:     !ultimo,
    };
  })
);

const datosFincas = computed(() => {
  const map = new Map<string, { animales: number; pesoTotal: number; conPeso: number }>();
  for (const a of animales.value) {
    const finca = a.finca?.nombre || 'Sin finca asignada';
    if (!map.has(finca)) map.set(finca, { animales: 0, pesoTotal: 0, conPeso: 0 });
    const e = map.get(finca)!;
    e.animales++;
    const ps = (a.pesajes || []).sort((x, y) => new Date(y.fecha).getTime() - new Date(x.fecha).getTime());
    if (ps.length) { e.pesoTotal += pesoNumerico(ps[0]); e.conPeso++; }
  }
  return Array.from(map.entries()).map(([finca, d]) => ({
    finca,
    animales: d.animales,
    promedio: d.conPeso > 0 ? (d.pesoTotal / d.conPeso).toFixed(0) : '—',
    total:    d.pesoTotal.toFixed(0),
  }));
});

// ── Helpers de template ─────────────────────────────────────────────────────
const formatDelta = (delta: number | null) => {
  if (delta === null) return '—';
  return (delta >= 0 ? '+' : '') + delta.toFixed(1) + '%';
};
const getDeltaClass = (delta: number | null) => {
  if (delta === null) return '';
  return delta >= 0 ? 'delta-pos' : 'delta-neg';
};

// ── Lista de tipos ──────────────────────────────────────────────────────────
const tiposReporte = [
  { key: 'rendimiento', ico: '📈', titulo: 'Rendimiento Mensual',     sub: 'Eficiencia de engorde y conversión' },
  { key: 'inventario',  ico: '📋', titulo: 'Inventario de Ganado',    sub: 'Resumen por razas y edades' },
  { key: 'salud',       ico: '🏥', titulo: 'Salud y Condición',       sub: 'Estado de peso por animal' },
  { key: 'fincas',      ico: '🗺️', titulo: 'Distribución por Fincas', sub: 'Densidad de carga por sector' },
];

// ── Acordeón ────────────────────────────────────────────────────────────────
const seleccionarReporte = async (tipo: string) => {
  reporteActivo.value = reporteActivo.value === tipo ? null : tipo;
  if (!reporteActivo.value) return;
  try {
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    const nuevo = await crearReporte({ tipo, fecha: new Date().toISOString().slice(0, 10), user_id: user.id ?? 1 });
    reportes.value.unshift(nuevo.datos);
  } catch { /* silencioso */ }
};

// ── Generación de PDF con jsPDF ─────────────────────────────────────────────
const trunc = (text: string, maxLen: number) =>
  text.length > maxLen ? text.slice(0, maxLen - 1) + '…' : text;

interface Seccion {
  titulo: string;
  columnas: string[];
  widths: number[];   // suma debe ser ≤ 180 (ancho usable en A4)
  filas: string[][];
  nota?: string;
}

function buildPDF(titulo: string, subtitulo: string, secciones: Seccion[]): jsPDF {
  const doc = new jsPDF({ orientation: 'p', unit: 'mm', format: 'a4' });
  const W   = doc.internal.pageSize.getWidth();
  const H   = doc.internal.pageSize.getHeight();
  const ML  = 15;
  let y     = 0;

  const addHeader = () => {
    doc.setFillColor(30, 86, 49);
    doc.rect(0, 0, W, 28, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(16);
    doc.setFont('helvetica', 'bold');
    doc.text('BovWeight CR', ML, 13);
    doc.setFontSize(10);
    doc.setFont('helvetica', 'normal');
    doc.text(titulo, ML, 21);
    doc.text(new Date().toLocaleDateString('es-CR'), W - ML, 21, { align: 'right' });
    y = 36;
    doc.setTextColor(80, 80, 80);
    doc.setFontSize(9);
    doc.text(subtitulo, ML, y);
    y += 10;
  };

  const addFooter = () => {
    doc.setFillColor(30, 86, 49);
    doc.rect(0, H - 12, W, 12, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(7);
    doc.text('BovWeight CR — Sistema de gestión ganadera', ML, H - 5);
    doc.text(`Generado: ${new Date().toLocaleString('es-CR')}`, W - ML, H - 5, { align: 'right' });
  };

  addHeader();

  // Aviso legal: los pesos son estimaciones, no sustituyen la báscula oficial
  doc.setFillColor(255, 247, 237);
  doc.setDrawColor(253, 186, 116);
  doc.setLineWidth(0.3);
  doc.roundedRect(ML, y - 4, W - ML * 2, 12, 1.5, 1.5, 'FD');
  doc.setFontSize(7.5);
  doc.setFont('helvetica', 'bold');
  doc.setTextColor(154, 52, 18);
  doc.text(
    'AVISO: Los pesos de este reporte son ESTIMACIONES de apoyo y NO sustituyen la bascula oficial.',
    ML + 3, y + 1
  );
  doc.setFont('helvetica', 'normal');
  doc.text(
    'Para transacciones comerciales o dosificacion critica, verifique con una bascula certificada.',
    ML + 3, y + 5
  );
  y += 16;

  const ROW_H = 7;

  for (const sec of secciones) {
    if (y > H - 50) { addFooter(); doc.addPage(); addHeader(); }

    // Título de sección
    doc.setFontSize(11);
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(30, 86, 49);
    doc.text(sec.titulo, ML, y);
    y += 4;
    doc.setDrawColor(184, 229, 204);
    doc.setLineWidth(0.5);
    doc.line(ML, y, W - ML, y);
    y += 5;

    // Encabezado de tabla
    doc.setFillColor(238, 249, 242);
    doc.rect(ML, y - 4, W - ML * 2, ROW_H, 'F');
    doc.setFontSize(8);
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(30, 86, 49);
    let x = ML;
    sec.columnas.forEach((col, i) => { doc.text(col, x + 2, y); x += sec.widths[i]; });
    y += ROW_H;

    // Filas
    doc.setFont('helvetica', 'normal');
    sec.filas.forEach((fila, ri) => {
      if (y > H - 22) { addFooter(); doc.addPage(); addHeader(); }
      if (ri % 2 === 0) {
        doc.setFillColor(249, 250, 249);
        doc.rect(ML, y - 4, W - ML * 2, ROW_H, 'F');
      }
      doc.setTextColor(30, 30, 30);
      let cx = ML;
      fila.forEach((cell, i) => {
        // Truncar texto según el ancho de columna
        const maxChars = Math.max(5, Math.floor(sec.widths[i] / 2.4));
        doc.text(trunc(String(cell), maxChars), cx + 2, y);
        cx += sec.widths[i];
      });
      y += ROW_H;
    });

    if (sec.nota) {
      y += 2;
      doc.setFontSize(7.5);
      doc.setTextColor(120, 120, 120);
      doc.text(sec.nota, ML, y);
      y += 5;
    }
    y += 10;
  }

  addFooter();
  return doc;
}

// ── PDF individual por tipo ─────────────────────────────────────────────────
const descargarPDFReporte = async (tipo: string) => {
  const fecha = new Date().toLocaleDateString('es-CR');
  let doc: jsPDF;

  if (tipo === 'rendimiento') {
    doc = buildPDF('Rendimiento Mensual', `Análisis de peso — ${fecha}`, [{
      titulo: 'Histórico de Peso (últimos 6 meses)',
      columnas: ['Mes', 'Pesajes', 'Peso prom.', 'Δ vs anterior'],
      widths:   [60, 35, 45, 40],
      filas: datosRendimiento.value.map(m => [
        m.mes, String(m.total),
        m.prom > 0 ? `${m.prom.toFixed(0)} kg` : '—',
        formatDelta(m.delta),
      ]),
    }]);
    doc.save('reporte-rendimiento.pdf');

  } else if (tipo === 'inventario') {
    doc = buildPDF('Inventario de Ganado', `Total: ${animales.value.length} cabezas — ${fecha}`, [{
      titulo: 'Distribución por Raza',
      columnas: ['Raza', 'Cantidad', '% del hato', 'Peso prom.'],
      widths:   [65, 35, 40, 40],
      filas: datosInventario.value.map(r => [
        r.raza, String(r.cantidad), `${r.pct}%`,
        r.promedio !== '—' ? `${r.promedio} kg` : '—',
      ]),
    }]);
    doc.save('reporte-inventario.pdf');

  } else if (tipo === 'salud') {
    doc = buildPDF('Salud y Condición', `Estado del hato — ${fecha}`, [{
      titulo: 'Condición por animal',
      columnas: ['Arete', 'Nombre', 'Último peso', 'Fecha', 'Estado'],
      widths:   [35, 50, 35, 32, 28],
      filas: datosSalud.value.map(a => [
        a.arete, a.nombre, a.ultimoPeso, a.fechaUltimo, a.estado ?? '',
      ]),
    }]);
    doc.save('reporte-salud.pdf');

  } else if (tipo === 'fincas') {
    doc = buildPDF('Distribución por Fincas', `Densidad por sector — ${fecha}`, [{
      titulo: 'Resumen por finca',
      columnas: ['Finca', 'Animales', 'Peso prom.', 'Peso total'],
      widths:   [70, 35, 40, 35],
      filas: datosFincas.value.map(f => [
        f.finca, String(f.animales),
        f.promedio !== '—' ? `${f.promedio} kg` : '—',
        Number(f.total) > 0 ? `${f.total} kg` : '—',
      ]),
    }]);
    doc.save('reporte-fincas.pdf');
  }

  // Registrar en backend
  try {
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    const nuevo = await crearReporte({ tipo, fecha: new Date().toISOString().slice(0, 10), user_id: user.id ?? 1 });
    reportes.value.unshift(nuevo.datos);
  } catch { /* silencioso */ }
};

// ── PDF completo (todas las secciones) ──────────────────────────────────────
const descargarPDFCompleto = async () => {
  const fecha = new Date().toLocaleDateString('es-CR');
  const doc = buildPDF('Reporte General', `Informe completo del hato — ${fecha}`, [
    {
      titulo: '1. Rendimiento Mensual',
      columnas: ['Mes', 'Pesajes', 'Peso prom.', 'Δ vs anterior'],
      widths:   [60, 35, 45, 40],
      filas: datosRendimiento.value.map(m => [
        m.mes, String(m.total),
        m.prom > 0 ? `${m.prom.toFixed(0)} kg` : '—',
        formatDelta(m.delta),
      ]),
    },
    {
      titulo: '2. Inventario por Raza',
      columnas: ['Raza', 'Cantidad', '% hato', 'Peso prom.'],
      widths:   [65, 35, 40, 40],
      filas: datosInventario.value.map(r => [
        r.raza, String(r.cantidad), `${r.pct}%`,
        r.promedio !== '—' ? `${r.promedio} kg` : '—',
      ]),
    },
    {
      titulo: '3. Salud y Condición',
      columnas: ['Arete', 'Nombre', 'Último peso', 'Fecha', 'Estado'],
      widths:   [35, 50, 35, 32, 28],
      filas: datosSalud.value.map(a => [
        a.arete, a.nombre, a.ultimoPeso, a.fechaUltimo, a.estado ?? '',
      ]),
    },
    {
      titulo: '4. Distribución por Fincas',
      columnas: ['Finca', 'Animales', 'Peso prom.', 'Peso total'],
      widths:   [70, 35, 40, 35],
      filas: datosFincas.value.map(f => [
        f.finca, String(f.animales),
        f.promedio !== '—' ? `${f.promedio} kg` : '—',
        Number(f.total) > 0 ? `${f.total} kg` : '—',
      ]),
    },
  ]);
  doc.save('reporte-completo.pdf');

  try {
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    const nuevo = await crearReporte({ tipo: 'PDF General', fecha: new Date().toISOString().slice(0, 10), user_id: user.id ?? 1 });
    reportes.value.unshift(nuevo.datos);
  } catch { /* silencioso */ }

  pdfMsg.value = '¡Reporte completo descargado!';
  pdfOk.value  = true;
  setTimeout(() => { pdfMsg.value = ''; }, 3000);
};

// ── Carga de datos ───────────────────────────────────────────────────────────
const recargar = async () => {

  loading.value = true;

  try {
    const [pData, aData, rData] = await Promise.all([getPesajes(), getAnimales(), getReportes()]);
    pesajes.value   = pData.datos || [];
    animales.value  = aData.datos || [];
    reportes.value  = rData.datos || [];
  } catch { /* sin datos */ }
  finally { loading.value = false; }
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

/* ── Chart ── */
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
  width: 100%; border-radius: 4px 4px 0 0;
  background: #B7E5CC; transition: height .4s ease; min-height: 6px;
}
.bar-fill.bar-active { background: #1E5631; }
.bar-lbl { font-size: .5rem; font-weight: 600; color: #9CA3AF; position: absolute; bottom: 4px; }
.bar-lbl.bar-lbl-active { color: #1E5631; font-weight: 800; }
.chart-meta { display: flex; gap: 16px; padding-top: 10px; border-top: 1px solid #E5E7EB; }
.cm-item { display: flex; align-items: center; gap: 6px; }
.cm-ico  { font-size: 16px; }
.cm-val  { font-size: .8125rem; font-weight: 700; color: #111827; }
.cm-lbl  { font-size: .625rem; color: #6B7280; }
.status-box { padding: 10px 12px; border-radius: 10px; font-size: .875rem; }
.status-loading { background: #F2F5F3; color: #374151; }

/* ── Reportes list ── */
.sec-title { font-size: .875rem; font-weight: 700; color: #111827; }
.rpt-item {
  display: flex; align-items: center; gap: 12px;
  background: #fff; border-radius: 14px; padding: 13px 14px;
  margin-bottom: 4px; box-shadow: 0 1px 3px rgba(0,0,0,.06);
  cursor: pointer; transition: box-shadow .15s, border-color .15s;
  border: 1.5px solid transparent;
}
.rpt-item:hover { box-shadow: 0 2px 8px rgba(0,0,0,.08); }
.rpt-item-active {
  border-color: #3A9E61; border-bottom-left-radius: 0; border-bottom-right-radius: 0;
  border-bottom-color: transparent;
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
.rpt-info { flex: 1; }
.rpt-title { font-size: .875rem; font-weight: 700; color: #111827; }
.rpt-sub   { font-size: .6875rem; color: #6B7280; margin-top: 1px; }
.chev { color: #9CA3AF; font-size: 18px; transition: transform .2s; display: inline-block; }
.chev-open { transform: rotate(90deg); color: #2D7A4A; }

/* ── Panel acordeón ── */
.rpt-panel {
  background: #fff; border: 1.5px solid #3A9E61; border-top: none;
  border-radius: 0 0 14px 14px;
  padding: 14px 14px 10px;
  margin-bottom: 4px;
  box-shadow: 0 2px 8px rgba(58,158,97,.1);
}

/* Stats dentro del panel */
.rpt-stats { display: flex; gap: 8px; margin-bottom: 12px; }
.rs-card {
  flex: 1; background: #EEF9F2; border-radius: 10px;
  padding: 10px 8px; text-align: center;
}
.rs-card-warn { background: #FEF3C7; }
.rs-val { font-size: 1.1rem; font-weight: 900; color: #1A3D28; }
.rs-card-warn .rs-val { color: #92400E; }
.rs-lbl { font-size: .5625rem; text-transform: uppercase; color: #6B7280; font-weight: 700; letter-spacing: .04em; margin-top: 2px; }

/* Tabla */
.tbl-wrap { overflow-x: auto; border-radius: 10px; margin-bottom: 10px; }
.rpt-tbl {
  width: 100%; border-collapse: collapse; font-size: .78rem;
  min-width: 340px;
}
.rpt-tbl thead { background: #EEF9F2; }
.rpt-tbl th {
  padding: 7px 8px; text-align: left;
  font-size: .625rem; font-weight: 800; color: #1E5631;
  text-transform: uppercase; letter-spacing: .05em; white-space: nowrap;
}
.rpt-tbl td { padding: 7px 8px; color: #374151; border-bottom: 1px solid #F3F4F6; }
.rpt-tbl tr:last-child td { border-bottom: none; }
.rpt-tbl tbody tr:nth-child(even) { background: #F9FAF9; }
.row-warn td { color: #92400E !important; background: #FFFBEB; }

.delta-pos { color: #1E5631; font-weight: 700; }
.delta-neg { color: #DC2626; font-weight: 700; }

.estado-chip {
  font-size: .6rem; font-weight: 700; padding: 2px 7px; border-radius: 9999px;
  background: #EEF9F2; color: #1E5631; text-transform: capitalize;
}

/* Botón PDF individual */
.btn-pdf-sm {
  width: 100%; padding: 11px;
  background: #EEF9F2; color: #1E5631;
  font-size: .8125rem; font-weight: 700;
  border: 1.5px solid #D8F3DC; border-radius: 10px; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 6px;
  font-family: inherit; transition: background .15s;
}
.btn-pdf-sm:hover { background: #D8F3DC; }

/* Botón PDF completo */
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
  font-family: inherit; transition: opacity .2s;
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