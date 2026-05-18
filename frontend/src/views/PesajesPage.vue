<template>
  <ion-page>
    <ion-content :fullscreen="true" class="page-bg">

      <!-- App bar -->
      <div class="app-bar">
        <div>
          <div class="page-title">Registrar Peso</div>
          <div class="page-sub">Nueva medición</div>
        </div>
        <button class="icon-btn" @click="resetForm">↺</button>
      </div>

      <div class="body-pad">

        <!-- ── IA banner ── -->
        <div class="ia-banner" @click="router.push('/tabs/pesaje-vivo')">
          <div class="ia-left">
            <span class="ia-ico">🤖</span>
            <div>
              <div class="ia-title">Usar IA para estimar peso</div>
              <div class="ia-sub">Apunta la cámara al animal</div>
            </div>
          </div>
          <span class="ia-chev">›</span>
        </div>

        <!-- ── Seleccionar animal ── -->
        <div class="form-section">
          <div class="form-sec-title">Animal</div>
          <div class="animal-sel" @click="mostrarSelector = true">
            <div class="anim-emo">{{ animalSel ? '🐄' : '➕' }}</div>
            <div class="anim-sel-info">
              <div class="anim-sel-name">{{ animalSel ? (animalSel.nombre ?? `Arete ${animalSel.numero_arete}`) : 'Seleccionar animal' }}</div>
              <div class="anim-sel-id">{{ animalSel ? `#${animalSel.numero_arete} · ${animalSel.raza?.nombre ?? 'Sin raza'}` : 'Toca para elegir' }}</div>
            </div>
            <span class="chev">›</span>
          </div>
        </div>

        <!-- ── Peso ── -->
        <div class="form-section">
          <div class="form-sec-title">Peso registrado</div>
          <div class="weight-wrap">
            <input
              v-model.number="peso"
              type="number"
              class="weight-input"
              placeholder="0"
              min="0"
              step="0.1"
            />
            <span class="weight-unit">kg</span>
          </div>
          <div class="adjust-row">
            <button class="adj" @click="peso = Math.max(0, peso - 5)">−5</button>
            <button class="adj" @click="peso = Math.max(0, peso - 1)">−1</button>
            <button class="adj" @click="peso += 1">+1</button>
            <button class="adj" @click="peso += 5">+5</button>
          </div>
        </div>

        <!-- ── Peso real (opcional) ── -->
        <div class="form-section">
          <div class="form-sec-title">Peso real (opcional)</div>
          <div class="field-wrap">
            <input v-model.number="pesoReal" type="number" class="field-input" placeholder="Si usaste báscula" min="0" step="0.1" />
            <span class="field-suffix">kg</span>
          </div>
        </div>

        <!-- ── Fecha ── -->
        <div class="form-section">
          <div class="form-sec-title">Detalles</div>
          <div class="form-group">
            <label class="form-label">Fecha de medición</label>
            <input v-model="fecha" type="date" class="field-input" />
          </div>
        </div>

        <!-- ── Feedback ── -->
        <div v-if="success" class="feedback success">✓ Pesaje registrado exitosamente.</div>
        <div v-if="errorMsg" class="feedback error">{{ errorMsg }}</div>

        <!-- ── Guardar ── -->
        <button class="btn-save" :disabled="saving" @click="guardar">
          <span v-if="saving">Guardando…</span>
          <span v-else>💾 Guardar pesaje</span>
        </button>

        <!-- ── Historial reciente ── -->
        <div class="sec-title" style="margin-top:24px;margin-bottom:12px">Pesajes recientes</div>
        <div v-if="loadingPesajes" class="status-box status-loading">Cargando…</div>
        <div v-else-if="pesajes.length === 0" class="empty-state">No hay pesajes registrados aún.</div>

        <div v-for="p in pesajes" :key="p.id" class="pesaje-row">
          <div class="pr-dot"></div>
          <div class="pr-info">
            <div class="pr-animal">{{ p.animal?.nombre ?? `Animal ${p.animal_id}` }}</div>
            <div class="pr-fecha">{{ formatFecha(p.fecha) }}</div>
          </div>
          <div class="pr-peso">{{ pesoNumerico(p).toFixed(0) }} kg</div>
        </div>

      </div>

      <!-- ── Modal selector de animal ── -->
      <ion-modal :is-open="mostrarSelector" @didDismiss="mostrarSelector = false">
        <ion-content class="page-bg">
          <div class="app-bar">
            <div class="page-title" style="font-size:1.125rem">Seleccionar animal</div>
            <button class="icon-btn" @click="mostrarSelector = false">✕</button>
          </div>
          <div class="body-pad">
            <div class="search-box" style="margin-bottom:12px">
              <span>🔍</span>
              <input v-model="busqueda" class="search-input" placeholder="Buscar por nombre o arete…" />
            </div>
            <div
              v-for="a in animalesFiltrados"
              :key="a.id"
              class="animal-sel-opt"
              @click="elegirAnimal(a)"
            >
              <div class="anim-emo">🐄</div>
              <div>
                <div style="font-size:.875rem;font-weight:600;color:#111827">{{ a.nombre ?? `Arete ${a.numero_arete}` }}</div>
                <div style="font-size:.6875rem;color:#6B7280">#{{ a.numero_arete }} · {{ a.raza?.nombre ?? 'Sin raza' }}</div>
              </div>
            </div>
            <div v-if="animalesFiltrados.length === 0" class="empty-state">Sin resultados.</div>
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
import { getAnimales, getPesajes, crearPesaje, pesoNumerico, formatFecha, AnimalDto, PesajeDto } from '@/services/api';

const router         = useRouter();
const animales       = ref<AnimalDto[]>([]);
const pesajes        = ref<PesajeDto[]>([]);
const animalSel      = ref<AnimalDto | null>(null);
const peso           = ref(0);
const pesoReal       = ref<number | null>(null);
const fecha          = ref(new Date().toISOString().slice(0, 10));
const mostrarSelector = ref(false);
const busqueda       = ref('');
const saving         = ref(false);
const loadingPesajes = ref(true);
const success        = ref(false);
const errorMsg       = ref('');

const animalesFiltrados = computed(() => {
  const q = busqueda.value.toLowerCase();
  if (!q) return animales.value;
  return animales.value.filter(a =>
    (a.nombre ?? '').toLowerCase().includes(q) ||
    a.numero_arete.toLowerCase().includes(q)
  );
});

const elegirAnimal = (a: AnimalDto) => {
  animalSel.value = a;
  mostrarSelector.value = false;
};

const resetForm = () => {
  animalSel.value = null;
  peso.value = 0;
  pesoReal.value = null;
  fecha.value = new Date().toISOString().slice(0, 10);
  success.value = false;
  errorMsg.value = '';
};

const guardar = async () => {
  if (!animalSel.value) { errorMsg.value = 'Selecciona un animal.'; return; }
  if (!peso.value || peso.value <= 0) { errorMsg.value = 'Ingresa un peso válido.'; return; }
  errorMsg.value = '';
  saving.value = true;
  try {
    await crearPesaje({
      animal_id: animalSel.value.id,
      peso_estimado: peso.value,
      peso_real: pesoReal.value ?? undefined,
      fecha: fecha.value,
    });
    success.value = true;
    resetForm();
    // Recargar lista
    const r = await getPesajes();
    pesajes.value = (r.datos || []).slice(0, 10);
  } catch {
    errorMsg.value = 'Error al guardar. Verifica la conexión con el servidor.';
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  try {
    const [aData, pData] = await Promise.all([getAnimales(), getPesajes()]);
    animales.value = aData;
    pesajes.value  = (pData.datos || [])
      .sort((a, b) => new Date(b.fecha).getTime() - new Date(a.fecha).getTime())
      .slice(0, 10);
  } catch { /* sin datos */ }
  finally { loadingPesajes.value = false; }
});
</script>

<style scoped>
.page-bg { --background: #F2F5F3; }

.app-bar {
  background: #fff; padding: 14px 18px 12px;
  display: flex; align-items: center; justify-content: space-between;
  border-bottom: 1px solid #E5E7EB;
}
.page-title { font-size: 1.25rem; font-weight: 800; color: #1A3D28; }
.page-sub   { font-size: .75rem; color: #6B7280; }
.icon-btn {
  width: 34px; height: 34px; border-radius: 10px;
  background: #F2F5F3; border: none; cursor: pointer; font-size: 15px;
}

.body-pad { padding: 16px 18px 32px; }

/* IA banner */
.ia-banner {
  display: flex; align-items: center; justify-content: space-between;
  background: linear-gradient(130deg, #1A3D28, #2D7A4A);
  border-radius: 14px; padding: 13px 14px; margin-bottom: 14px;
  cursor: pointer; box-shadow: 0 4px 14px rgba(26,61,40,.28);
  transition: opacity .2s;
}
.ia-banner:hover { opacity: .92; }
.ia-left { display: flex; align-items: center; gap: 12px; }
.ia-ico  { font-size: 22px; }
.ia-title { font-size: .9375rem; font-weight: 800; color: #fff; }
.ia-sub   { font-size: .6875rem; color: rgba(255,255,255,.7); margin-top: 1px; }
.ia-chev  { color: rgba(255,255,255,.6); font-size: 22px; }

/* Form sections */
.form-section {
  background: #fff; border-radius: 14px; padding: 14px;
  margin-bottom: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.06);
}
.form-sec-title {
  font-size: .6875rem; font-weight: 700; color: #9CA3AF;
  text-transform: uppercase; letter-spacing: .06em; margin-bottom: 10px;
}

/* Animal selector */
.animal-sel {
  display: flex; align-items: center; gap: 12px;
  padding: 10px 12px; background: #F2F5F3;
  border: 1.5px solid #E5E7EB; border-radius: 12px; cursor: pointer;
  transition: border-color .15s;
}
.animal-sel:hover { border-color: #1E5631; }
.anim-emo {
  width: 40px; height: 40px; border-radius: 10px;
  background: #EEF9F2; display: flex; align-items: center;
  justify-content: center; font-size: 18px; flex-shrink: 0;
}
.anim-sel-info { flex: 1; }
.anim-sel-name { font-size: .875rem; font-weight: 600; color: #111827; }
.anim-sel-id   { font-size: .6875rem; color: #6B7280; }
.chev { color: #9CA3AF; font-size: 20px; }

/* Weight input */
.weight-wrap {
  display: flex; align-items: center; justify-content: center;
  position: relative; margin-bottom: 12px;
}
.weight-input {
  width: 100%; text-align: center;
  font-size: 2.5rem; font-weight: 900; color: #1E5631;
  border: 2px solid #D8F3DC; background: #EEF9F2;
  border-radius: 14px; padding: 18px 48px 18px 20px;
  outline: none; font-family: inherit;
  transition: border-color .15s;
}
.weight-input:focus { border-color: #2D7A4A; }
.weight-unit {
  position: absolute; right: 16px;
  font-size: 1rem; font-weight: 700; color: #2D7A4A;
}
.adjust-row { display: flex; gap: 6px; }
.adj {
  flex: 1; padding: 8px; border-radius: 8px;
  border: 1.5px solid #D8F3DC; background: #EEF9F2;
  color: #1E5631; font-size: .75rem; font-weight: 700;
  cursor: pointer; font-family: inherit; transition: background .15s;
}
.adj:hover { background: #D8F3DC; }

/* Field */
.field-wrap { display: flex; align-items: center; position: relative; }
.field-input {
  width: 100%; padding: 11px 44px 11px 14px;
  border: 1.5px solid #E5E7EB; border-radius: 12px;
  font-size: .875rem; color: #111827; background: #F2F5F3;
  outline: none; font-family: inherit; transition: border-color .15s;
}
.field-input:focus { border-color: #1E5631; background: #fff; }
.field-suffix { position: absolute; right: 14px; font-size: .8125rem; font-weight: 600; color: #6B7280; }
.form-group { margin-bottom: 0; }
.form-label { display: block; font-size: .8125rem; font-weight: 600; color: #374151; margin-bottom: 6px; }

/* Feedback */
.feedback {
  padding: 10px 14px; border-radius: 10px;
  font-size: .8125rem; font-weight: 600; margin-bottom: 12px;
}
.success { background: #EEF9F2; color: #1E5631; border: 1px solid #D8F3DC; }
.error   { background: #FEE2E2; color: #B91C1C; border: 1px solid #FECACA; }

/* Buttons */
.btn-save {
  width: 100%; padding: 15px;
  background: linear-gradient(135deg, #1E5631, #3A9E61);
  color: #fff; font-size: .9375rem; font-weight: 700;
  border: none; border-radius: 14px; cursor: pointer;
  box-shadow: 0 4px 14px rgba(30,86,49,.3);
  font-family: inherit; display: flex; align-items: center; justify-content: center;
  transition: opacity .2s;
}
.btn-save:disabled { opacity: .65; }

/* Status */
.sec-title { font-size: .875rem; font-weight: 700; color: #111827; }
.status-box { padding: 12px; border-radius: 10px; font-size: .875rem; margin-bottom: 10px; }
.status-loading { background: #F2F5F3; color: #374151; }
.empty-state { text-align: center; color: #6B7280; padding: 20px 0; font-size: .875rem; }

/* Pesaje row */
.pesaje-row {
  display: flex; align-items: center; gap: 10px;
  background: #fff; border-radius: 12px; padding: 11px 13px;
  margin-bottom: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.06);
}
.pr-dot {
  width: 9px; height: 9px; border-radius: 50%;
  background: #74C69D; flex-shrink: 0;
}
.pr-info { flex: 1; min-width: 0; }
.pr-animal { font-size: .875rem; font-weight: 600; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pr-fecha  { font-size: .6875rem; color: #6B7280; }
.pr-peso   { font-size: .9375rem; font-weight: 800; color: #1E5631; flex-shrink: 0; }

/* Selector modal */
.search-box {
  display: flex; align-items: center; gap: 10px;
  background: #fff; border: 1.5px solid #E5E7EB;
  border-radius: 12px; padding: 10px 14px;
}
.search-input {
  flex: 1; border: none; background: transparent;
  outline: none; font-size: .875rem; font-family: inherit;
}
.animal-sel-opt {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 14px; background: #fff; border-radius: 12px;
  margin-bottom: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.06);
  cursor: pointer; transition: box-shadow .15s;
}
.animal-sel-opt:hover { box-shadow: 0 2px 8px rgba(0,0,0,.08); }
</style>
