<template>
  <ion-page>
    <ion-content :fullscreen="true" class="page-bg">

      <!-- App bar -->
      <div class="app-bar">
        <div>
          <div class="page-title">Mis Animales</div>
          <div class="page-sub">{{ animalesFiltrados.length }} registros</div>
        </div>
        <button class="icon-btn" @click="recargar">🔄</button>
      </div>

      <!-- Search -->
      <div class="body-pad">
        <div class="search-box">
          <span class="search-ico">🔍</span>
          <input
            v-model="busqueda"
            class="search-input"
            type="search"
            placeholder="Buscar por nombre o arete…"
          />
        </div>

        <!-- Filter chips -->
        <div class="chips">
          <button
            v-for="f in filtros"
            :key="f.key"
            class="chip"
            :class="{ 'chip-active': filtroActivo === f.key }"
            @click="filtroActivo = f.key"
          >{{ f.label }}</button>
        </div>

        <!-- Loading / Error -->
        <div v-if="loading" class="status-box status-loading">Cargando animales…</div>
        <div v-else-if="error" class="status-box status-error">{{ error }}</div>

        <!-- Empty -->
        <div v-else-if="animalesFiltrados.length === 0" class="empty-state">
          No se encontraron animales con ese criterio.
        </div>

        <!-- Animal cards -->
        <div
          v-for="animal in animalesFiltrados"
          :key="animal.id"
          class="animal-card"
          @click="seleccionar(animal)"
        >
          <div class="ac-top">
            <div class="ac-avatar">🐄</div>
            <div class="ac-info">
              <div class="ac-id">#{{ animal.numero_arete }} · {{ animal.raza?.nombre ?? 'Sin raza' }}</div>
              <div class="ac-name">{{ animal.nombre ?? `Animal ${animal.numero_arete}` }}</div>
              <div class="ac-tags">
                <span class="tag" :class="estadoTag(animal).cls">{{ estadoTag(animal).txt }}</span>
              </div>
            </div>
            <div class="ac-right">
              <div class="ac-w">{{ ultimoPeso(animal) }}</div>
              <div class="ac-chg">{{ animal.estado }}</div>
            </div>
          </div>
          <div class="ac-metrics">
            <div class="acm"><div class="acm-val">{{ edad(animal) }}</div><div class="acm-lbl">Edad</div></div>
            <div class="acm-div"></div>
            <div class="acm"><div class="acm-val">{{ animal.finca?.nombre ?? '—' }}</div><div class="acm-lbl">Finca</div></div>
            <div class="acm-div"></div>
            <div class="acm"><div class="acm-val">{{ animal.estado }}</div><div class="acm-lbl">Estado</div></div>
          </div>
        </div>

      </div>

      <!-- Detail modal -->
      <ion-modal :is-open="!!animalSel" @didDismiss="animalSel = null">
        <ion-content class="page-bg" v-if="animalSel">
          <div class="modal-hero">
            <button class="back-btn" @click="animalSel = null">‹ Volver</button>
            <div class="modal-id">#{{ animalSel.numero_arete }} · {{ animalSel.raza?.nombre ?? 'Sin raza' }}</div>
            <div class="modal-name">{{ animalSel.nombre ?? `Animal ${animalSel.numero_arete}` }}</div>
            <div class="modal-tags">
              <span class="detail-tag">{{ animalSel.estado }}</span>
              <span class="detail-tag">{{ animalSel.finca?.nombre ?? 'Sin finca' }}</span>
            </div>
          </div>

          <div class="body-pad" style="padding-top:24px">
            <div class="float-metrics" style="margin-bottom:20px">
              <div class="fm-card"><div class="fm-val">{{ ultimoPeso(animalSel) }}</div><div class="fm-lbl">Último peso</div></div>
              <div class="fm-card"><div class="fm-val">{{ edad(animalSel) }}</div><div class="fm-lbl">Edad</div></div>
              <div class="fm-card"><div class="fm-val">{{ historialAnimal.length }}</div><div class="fm-lbl">Pesajes</div></div>
            </div>

            <div class="sec-title" style="margin-bottom:10px">Historial de pesajes</div>
            <div v-if="loadingHistorial" class="status-box status-loading">Cargando historial…</div>
            <div v-else-if="historialAnimal.length === 0" class="empty-state">Sin pesajes registrados.</div>
            <div v-else class="hist-list card">
              <div v-for="(p, i) in historialAnimal" :key="p.id" class="hist-row">
                <div class="hist-dot" :class="{ 'hist-dot-first': i === 0 }"></div>
                <div class="hist-date">{{ formatFecha(p.fecha) }} · {{ p.fuente?.nombre ?? 'Manual' }}</div>
                <div class="hist-w">{{ pesoNumerico(p).toFixed(0) }} kg</div>
                <div class="hist-d" :class="i < historialAnimal.length-1 ? diffClass(p, historialAnimal[i+1]) : ''">
                  {{ i < historialAnimal.length-1 ? diff(p, historialAnimal[i+1]) : '' }}
                </div>
              </div>
            </div>

            <button class="btn-primary" @click="registrarPeso">⚖️ Registrar nuevo pesaje</button>
          </div>
        </ion-content>
      </ion-modal>

    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { IonPage, IonContent, IonModal } from '@ionic/vue';
import { getAnimales, getHistorialAnimal, pesoNumerico, formatFecha, AnimalDto, PesajeDto } from '@/services/api';

const router           = useRouter();
const animales         = ref<AnimalDto[]>([]);
const loading          = ref(true);
const error            = ref('');
const busqueda         = ref('');
const filtroActivo     = ref('todos');
const animalSel        = ref<AnimalDto | null>(null);
const historialAnimal  = ref<PesajeDto[]>([]);
const loadingHistorial = ref(false);

const filtros = [
  { key: 'todos',  label: 'Todos' },
  { key: 'activo', label: 'Activos' },
  { key: 'otro',   label: 'Inactivos' },
];

const animalesFiltrados = computed(() => {
  let list = animales.value;
  if (busqueda.value.trim()) {
    const q = busqueda.value.toLowerCase();
    list = list.filter(a =>
      (a.nombre ?? '').toLowerCase().includes(q) ||
      a.numero_arete.toLowerCase().includes(q)
    );
  }
  if (filtroActivo.value !== 'todos') {
    list = list.filter(a =>
      filtroActivo.value === 'activo' ? a.estado === 'activo' : a.estado !== 'activo'
    );
  }
  return list;
});

function estadoTag(a: AnimalDto) {
  if (a.estado === 'activo') return { cls: 'tag-g', txt: '✓ Activo' };
  return { cls: 'tag-r', txt: '✗ Inactivo' };
}

function ultimoPeso(a: AnimalDto): string {
  const pesajes = a.pesajes;
  if (!pesajes || pesajes.length === 0) return '— kg';
  const ultimo = [...pesajes].sort(
    (x, y) => new Date(y.fecha).getTime() - new Date(x.fecha).getTime()
  )[0];
  return `${pesoNumerico(ultimo).toFixed(0)} kg`;
}

function edad(a: AnimalDto): string {
  if (!a.fecha_nacimiento) return '—';
  const ms = Date.now() - new Date(a.fecha_nacimiento).getTime();
  const años = Math.floor(ms / (1000 * 60 * 60 * 24 * 365));
  return años > 0 ? `${años} año${años > 1 ? 's' : ''}` : '< 1 año';
}

function diff(actual: PesajeDto, anterior: PesajeDto): string {
  const d = pesoNumerico(actual) - pesoNumerico(anterior);
  return (d >= 0 ? '+' : '') + d.toFixed(0) + ' kg';
}

function diffClass(actual: PesajeDto, anterior: PesajeDto): string {
  return pesoNumerico(actual) >= pesoNumerico(anterior) ? 'diff-up' : 'diff-dn';
}

const seleccionar = async (a: AnimalDto) => {
  animalSel.value = a;
  historialAnimal.value = [];
  loadingHistorial.value = true;
  try {
    historialAnimal.value = await getHistorialAnimal(a.id);
  } catch { /* sin historial */ }
  finally { loadingHistorial.value = false; }
};

const registrarPeso = () => {
  animalSel.value = null;
  router.push('/tabs/pesajes');
};

const recargar = async () => {
  loading.value = true;
  error.value = '';
  try {
    animales.value = await getAnimales();
  } catch {
    error.value = 'No se pudo cargar la lista de animales.';
  } finally {
    loading.value = false;
  }
};

onMounted(recargar);
</script>

<style scoped>
.page-bg { --background: #F2F5F3; }

.app-bar {
  background: #fff; padding: 14px 18px 12px;
  display: flex; align-items: center; justify-content: space-between;
  border-bottom: 1px solid #E5E7EB;
}
.page-title { font-size: 1.5rem; font-weight: 900; color: #1A3D28; letter-spacing: -.3px; }
.page-sub   { font-size: .75rem; color: #6B7280; margin-top: 1px; }
.icon-btn {
  width: 34px; height: 34px; border-radius: 10px;
  background: #F2F5F3; border: none; cursor: pointer;
  display: flex; align-items: center; justify-content: center; font-size: 15px;
}

.body-pad { padding: 16px 18px 24px; }

/* Search */
.search-box {
  display: flex; align-items: center; gap: 10px;
  background: #fff; border: 1.5px solid #E5E7EB;
  border-radius: 14px; padding: 10px 14px; margin-bottom: 12px;
}
.search-ico { font-size: 15px; color: #9CA3AF; flex-shrink: 0; }
.search-input {
  flex: 1; border: none; background: transparent;
  outline: none; font-size: .875rem; color: #111827; font-family: inherit;
}
.search-input::placeholder { color: #9CA3AF; }

/* Filter chips */
.chips { display: flex; gap: 8px; margin-bottom: 14px; overflow-x: auto; padding-bottom: 2px; }
.chip {
  flex-shrink: 0; padding: 6px 14px;
  border-radius: 9999px; font-size: .75rem; font-weight: 600;
  border: 1.5px solid #E5E7EB; background: #fff; color: #6B7280;
  cursor: pointer; font-family: inherit; transition: all .15s;
}
.chip-active { background: #1E5631; border-color: #1E5631; color: #fff; }

/* Status */
.status-box { padding: 12px 14px; border-radius: 12px; font-size: .875rem; font-weight: 600; margin-bottom: 12px; }
.status-loading { background: #F2F5F3; color: #374151; border: 1px solid #E5E7EB; }
.status-error   { background: #FEE2E2; color: #B91C1C; border: 1px solid #FECACA; }
.empty-state { text-align: center; color: #6B7280; font-size: .875rem; padding: 32px 0; }

/* Animal card */
.animal-card {
  background: #fff; border-radius: 16px; padding: 14px;
  margin-bottom: 10px; box-shadow: 0 1px 3px rgba(0,0,0,.06);
  cursor: pointer; transition: box-shadow .2s;
  border-left: 3px solid transparent;
}
.animal-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,.08); border-left-color: #3A9E61; }
.ac-top { display: flex; align-items: flex-start; gap: 12px; }
.ac-avatar {
  width: 52px; height: 52px; border-radius: 12px;
  background: linear-gradient(135deg, #EEF9F2, #D8F3DC);
  border: 1.5px solid #D8F3DC;
  display: flex; align-items: center; justify-content: center;
  font-size: 24px; flex-shrink: 0;
}
.ac-info { flex: 1; min-width: 0; }
.ac-id   { font-size: .6875rem; color: #6B7280; font-weight: 500; margin-bottom: 2px; }
.ac-name { font-size: .9375rem; font-weight: 700; color: #111827; }
.ac-tags { margin-top: 4px; }
.tag {
  display: inline-flex; align-items: center;
  padding: 2px 8px; border-radius: 9999px;
  font-size: .625rem; font-weight: 700;
}
.tag-g { background: #D8F3DC; color: #1E5631; }
.tag-r { background: #FEE2E2; color: #B91C1C; }
.ac-right { text-align: right; flex-shrink: 0; }
.ac-w   { font-size: 1rem; font-weight: 800; color: #1E5631; }
.ac-chg { font-size: .6875rem; color: #6B7280; }
.ac-metrics {
  display: flex; gap: 8px; margin-top: 10px;
  padding-top: 10px; border-top: 1px solid #F3F4F6;
}
.acm { flex: 1; text-align: center; }
.acm-val { font-size: .75rem; font-weight: 700; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.acm-lbl { font-size: .5625rem; color: #6B7280; margin-top: 1px; text-transform: uppercase; }
.acm-div { width: 1px; background: #F3F4F6; }

/* Modal */
.modal-hero {
  background: linear-gradient(155deg, #1A3D28 0%, #2D7A4A 100%);
  padding: 16px 18px 28px;
  position: relative; overflow: hidden;
}
.modal-hero::before { content:'🐄'; font-size: 90px; position: absolute; right: -8px; top: -12px; opacity:.1; }
.back-btn {
  background: none; border: none; color: rgba(255,255,255,.75);
  font-size: .8125rem; font-weight: 500; cursor: pointer;
  margin-bottom: 12px; padding: 0; display: flex; align-items: center; gap: 4px;
  font-family: inherit;
}
.modal-id   { font-size: .75rem; color: rgba(255,255,255,.55); margin-bottom: 3px; }
.modal-name { font-size: 1.5rem; font-weight: 900; color: #fff; letter-spacing: -.4px; }
.modal-tags { display: flex; gap: 6px; margin-top: 10px; flex-wrap: wrap; }
.detail-tag {
  padding: 3px 10px; border-radius: 9999px;
  background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.22);
  font-size: .6875rem; font-weight: 600; color: rgba(255,255,255,.9);
}

.float-metrics { display: grid; grid-template-columns: repeat(3,1fr); gap: 8px; }
.fm-card {
  background: #fff; border-radius: 12px; padding: 11px 8px;
  text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,.08);
}
.fm-val { font-size: 1rem; font-weight: 800; color: #111827; }
.fm-lbl { font-size: .5625rem; color: #6B7280; text-transform: uppercase; letter-spacing: .04em; margin-top: 2px; }

.sec-title { font-size: .875rem; font-weight: 700; color: #111827; }

.card { background: #fff; border-radius: 14px; padding: 14px; box-shadow: 0 1px 3px rgba(0,0,0,.06); margin-bottom: 12px; }

.hist-row {
  display: flex; align-items: center; gap: 10px;
  padding: 8px 0; border-bottom: 1px solid #F3F4F6;
}
.hist-row:last-child { border-bottom: none; }
.hist-dot { width: 9px; height: 9px; border-radius: 50%; background: #74C69D; flex-shrink: 0; }
.hist-dot-first { background: #1E5631; width: 11px; height: 11px; }
.hist-date { font-size: .6875rem; color: #6B7280; flex: 1; }
.hist-w    { font-size: .8125rem; font-weight: 700; color: #111827; }
.hist-d    { font-size: .6875rem; font-weight: 600; width: 48px; text-align: right; }
.diff-up { color: #2D7A4A; }
.diff-dn { color: #EF4444; }

.btn-primary {
  width: 100%; padding: 15px;
  background: linear-gradient(135deg, #1E5631, #3A9E61);
  color: #fff; font-size: .9375rem; font-weight: 700;
  border: none; border-radius: 14px; cursor: pointer;
  box-shadow: 0 4px 14px rgba(30,86,49,.3);
  font-family: inherit; margin-top: 4px;
  display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-primary:hover { opacity: .92; }
</style>
