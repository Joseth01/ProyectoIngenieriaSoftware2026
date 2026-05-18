<template>
  <ion-page>
    <ion-content :fullscreen="true" class="vivo-bg">

      <!-- ═══ App bar ═══ -->
      <div class="app-bar">
        <button class="back-btn" @click="router.back()">‹</button>
        <div class="app-bar-center">
          <div class="page-title">Pesaje con IA</div>
          <div class="page-sub">Estimación inteligente de peso</div>
        </div>
        <div class="ai-badge">🤖 IA</div>
      </div>

      <div class="body-pad">

        <!-- ═══ Animal selector ═══ -->
        <div class="animal-card" @click="mostrarSelector = true">
          <div class="anim-emo">{{ animalSel ? '🐄' : '➕' }}</div>
          <div class="anim-info">
            <div class="anim-name">{{ animalSel ? (animalSel.nombre ?? `Arete ${animalSel.numero_arete}`) : 'Selecciona el animal' }}</div>
            <div class="anim-sub">{{ animalSel ? `#${animalSel.numero_arete} · ${animalSel.raza?.nombre ?? 'Sin raza'}` : 'Toca para elegir' }}</div>
          </div>
          <span class="chev">›</span>
        </div>

        <!-- ═══ Viewfinder ═══ -->
        <div class="viewfinder-wrap">
          <!-- Camera feed placeholder — getUserMedia will be wired here -->
          <div class="viewfinder">

            <!-- Scanning overlay (shown when not capturing) -->
            <div v-if="estado === 'idle'" class="vf-idle">
              <div class="vf-corners">
                <div class="corner tl"></div>
                <div class="corner tr"></div>
                <div class="corner bl"></div>
                <div class="corner br"></div>
              </div>
              <div class="vf-hint">
                <span class="vf-ico">📷</span>
                <span>Encuadra el animal en el visor</span>
              </div>
            </div>

            <!-- Analyzing animation -->
            <div v-else-if="estado === 'analizando'" class="vf-analyzing">
              <div class="scan-line"></div>
              <div class="analyze-msg">
                <div class="spinner"></div>
                <span>Analizando imagen…</span>
              </div>
            </div>

            <!-- Result overlay -->
            <div v-else-if="estado === 'resultado'" class="vf-result-overlay">
              <div class="vf-corners vf-corners-green">
                <div class="corner tl"></div>
                <div class="corner tr"></div>
                <div class="corner bl"></div>
                <div class="corner br"></div>
              </div>
              <div class="result-chip">✓ Animal detectado</div>
            </div>

          </div>

          <!-- AI model label -->
          <div class="model-label">
            <span class="model-dot"></span>
            BovAI · Modelo morfométrico v1.0
          </div>
        </div>

        <!-- ═══ Result card (slides in after capture) ═══ -->
        <Transition name="slide-up">
          <div v-if="estado === 'resultado'" class="result-card">
            <div class="result-header">
              <span class="result-title">Estimación de peso</span>
              <span class="confidence-badge">{{ confianza }}% confianza</span>
            </div>
            <div class="result-weight-row">
              <div class="result-weight">{{ pesoEstimado }}<span class="result-unit">kg</span></div>
              <div class="result-range">± {{ margen }} kg</div>
            </div>
            <div class="result-metrics">
              <div class="rm-item">
                <span class="rm-lbl">Condición corporal</span>
                <span class="rm-val">{{ cc }}/5</span>
              </div>
              <div class="rm-item">
                <span class="rm-lbl">Alzada estimada</span>
                <span class="rm-val">{{ alzada }} cm</span>
              </div>
            </div>
          </div>
        </Transition>

        <!-- ═══ Capture / Shutter button ═══ -->
        <div class="shutter-area" v-if="estado !== 'resultado'">
          <div class="shutter-hint" v-if="estado === 'idle'">
            {{ animalSel ? 'Toca para capturar y analizar' : 'Primero selecciona un animal' }}
          </div>
          <button
            class="shutter-btn"
            :class="{ 'shutter-disabled': !animalSel || estado === 'analizando' }"
            :disabled="!animalSel || estado === 'analizando'"
            @click="capturar"
          >
            <div class="shutter-inner">
              <span v-if="estado === 'analizando'" class="shutter-spinner"></span>
              <span v-else>📸</span>
            </div>
          </button>
        </div>

        <!-- ═══ Action buttons after result ═══ -->
        <div v-if="estado === 'resultado'" class="action-area">
          <button class="btn-retry" @click="reiniciar">
            🔄 Volver a capturar
          </button>
          <button class="btn-save" :disabled="saving" @click="guardar">
            <span v-if="saving">Guardando…</span>
            <span v-else>💾 Guardar pesaje</span>
          </button>
        </div>

        <!-- ═══ Feedback ═══ -->
        <div v-if="feedbackMsg" class="feedback" :class="feedbackOk ? 'feedback-ok' : 'feedback-err'">
          {{ feedbackMsg }}
        </div>

        <!-- ═══ Info cards ═══ -->
        <div class="info-section">
          <div class="sec-title">¿Cómo funciona?</div>
          <div class="info-card">
            <div class="info-step">
              <div class="step-num">1</div>
              <div>
                <div class="step-title">Selecciona el animal</div>
                <div class="step-sub">Elige de tu inventario o crea uno nuevo</div>
              </div>
            </div>
            <div class="info-step">
              <div class="step-num">2</div>
              <div>
                <div class="step-title">Encuadra y captura</div>
                <div class="step-sub">Ubica al bovino de perfil a 2–4 metros</div>
              </div>
            </div>
            <div class="info-step">
              <div class="step-num">3</div>
              <div>
                <div class="step-title">Obtén el peso estimado</div>
                <div class="step-sub">La IA analiza proporciones corporales en segundos</div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- ═══ Modal selector de animal ═══ -->
      <ion-modal :is-open="mostrarSelector" @didDismiss="mostrarSelector = false">
        <ion-content class="vivo-bg">
          <div class="app-bar">
            <button class="back-btn" @click="mostrarSelector = false">✕</button>
            <div class="app-bar-center">
              <div class="page-title" style="font-size:1.125rem">Seleccionar animal</div>
            </div>
            <div style="width:34px"></div>
          </div>
          <div class="body-pad">
            <div class="search-box">
              <span>🔍</span>
              <input v-model="busqueda" class="search-input" placeholder="Buscar por nombre o arete…" />
            </div>
            <div v-if="loadingAnimales" class="status-loading">Cargando animales…</div>
            <div
              v-for="a in animalesFiltrados"
              :key="a.id"
              class="animal-opt"
              @click="elegirAnimal(a)"
            >
              <div class="anim-emo">🐄</div>
              <div>
                <div class="opt-name">{{ a.nombre ?? `Arete ${a.numero_arete}` }}</div>
                <div class="opt-sub">#{{ a.numero_arete }} · {{ a.raza?.nombre ?? 'Sin raza' }}</div>
              </div>
            </div>
            <div v-if="!loadingAnimales && animalesFiltrados.length === 0" class="empty-state">Sin resultados.</div>
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
import { getAnimales, crearPesaje, AnimalDto } from '@/services/api';

const router = useRouter();

type Estado = 'idle' | 'analizando' | 'resultado';

const animales       = ref<AnimalDto[]>([]);
const animalSel      = ref<AnimalDto | null>(null);
const mostrarSelector = ref(false);
const busqueda       = ref('');
const loadingAnimales = ref(true);
const estado         = ref<Estado>('idle');
const saving         = ref(false);
const feedbackMsg    = ref('');
const feedbackOk     = ref(false);

// Mock AI result values — will be replaced by real model output
const pesoEstimado = ref(0);
const margen       = ref(0);
const confianza    = ref(0);
const cc           = ref('');
const alzada       = ref(0);

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

const capturar = () => {
  if (!animalSel.value || estado.value === 'analizando') return;
  estado.value = 'analizando';

  // Simulate AI processing delay — real model call replaces this
  setTimeout(() => {
    pesoEstimado.value = Math.round(280 + Math.random() * 180);
    margen.value       = Math.round(8 + Math.random() * 12);
    confianza.value    = Math.round(82 + Math.random() * 13);
    cc.value           = (2.5 + Math.random() * 2).toFixed(1);
    alzada.value       = Math.round(120 + Math.random() * 30);
    estado.value       = 'resultado';
  }, 2200);
};

const reiniciar = () => {
  estado.value  = 'idle';
  feedbackMsg.value = '';
};

const guardar = async () => {
  if (!animalSel.value) return;
  saving.value = true;
  try {
    await crearPesaje({
      animal_id: animalSel.value.id,
      peso_estimado: pesoEstimado.value,
      fecha: new Date().toISOString().slice(0, 10),
    });
    feedbackMsg.value = '✓ Pesaje guardado exitosamente.';
    feedbackOk.value  = true;
    setTimeout(() => {
      feedbackMsg.value = '';
      reiniciar();
    }, 2000);
  } catch {
    feedbackMsg.value = 'Error al guardar. Verifica la conexión.';
    feedbackOk.value  = false;
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  try {
    animales.value = await getAnimales();
  } catch { /* sin datos */ }
  finally { loadingAnimales.value = false; }
});
</script>

<style scoped>
.vivo-bg { --background: #F2F5F3; }

/* App bar */
.app-bar {
  background: #fff; padding: 12px 16px 10px;
  display: flex; align-items: center; gap: 10px;
  border-bottom: 1px solid #E5E7EB;
}
.back-btn {
  width: 34px; height: 34px; border-radius: 10px;
  background: #F2F5F3; border: none; cursor: pointer;
  font-size: 22px; color: #374151; line-height: 1;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.app-bar-center { flex: 1; }
.page-title { font-size: 1.125rem; font-weight: 800; color: #1A3D28; }
.page-sub   { font-size: .6875rem; color: #6B7280; margin-top: 1px; }
.ai-badge {
  padding: 4px 10px; border-radius: 9999px;
  background: linear-gradient(90deg, #EEF9F2, #D8F3DC);
  border: 1px solid #B7E5CC;
  font-size: .6875rem; font-weight: 800; color: #1E5631;
  flex-shrink: 0;
}

.body-pad { padding: 14px 16px 36px; }

/* Animal card */
.animal-card {
  display: flex; align-items: center; gap: 12px;
  background: #fff; border-radius: 14px; padding: 12px 14px;
  margin-bottom: 14px; box-shadow: 0 1px 4px rgba(0,0,0,.07);
  cursor: pointer; border: 1.5px solid #E5E7EB;
  transition: border-color .15s;
}
.animal-card:hover { border-color: #1E5631; }
.anim-emo {
  width: 42px; height: 42px; border-radius: 11px;
  background: #EEF9F2; border: 1px solid #D8F3DC;
  display: flex; align-items: center; justify-content: center;
  font-size: 20px; flex-shrink: 0;
}
.anim-info { flex: 1; }
.anim-name { font-size: .875rem; font-weight: 700; color: #111827; }
.anim-sub  { font-size: .6875rem; color: #6B7280; margin-top: 1px; }
.chev { color: #9CA3AF; font-size: 20px; }

/* Viewfinder */
.viewfinder-wrap { margin-bottom: 14px; }
.viewfinder {
  width: 100%; aspect-ratio: 4/3;
  background: linear-gradient(160deg, #0D2B1A 0%, #1A3D28 60%, #0a1f12 100%);
  border-radius: 20px; overflow: hidden; position: relative;
  box-shadow: 0 8px 28px rgba(13,43,26,.4);
}

/* Idle state */
.vf-idle { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; flex-direction: column; }
.vf-corners { position: absolute; inset: 20px; }
.corner {
  position: absolute; width: 22px; height: 22px;
  border-color: rgba(255,255,255,.55); border-style: solid;
}
.corner.tl { top: 0; left: 0; border-width: 2.5px 0 0 2.5px; border-radius: 4px 0 0 0; }
.corner.tr { top: 0; right: 0; border-width: 2.5px 2.5px 0 0; border-radius: 0 4px 0 0; }
.corner.bl { bottom: 0; left: 0; border-width: 0 0 2.5px 2.5px; border-radius: 0 0 0 4px; }
.corner.br { bottom: 0; right: 0; border-width: 0 2.5px 2.5px 0; border-radius: 0 0 4px 0; }

.vf-corners-green .corner { border-color: #52B788; }

.vf-hint {
  display: flex; flex-direction: column; align-items: center; gap: 8px;
  color: rgba(255,255,255,.6); font-size: .8125rem; text-align: center;
  padding: 0 20px;
}
.vf-ico { font-size: 2rem; opacity: .55; }

/* Analyzing */
.vf-analyzing { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 16px; }
.scan-line {
  position: absolute; top: 0; left: 0; right: 0; height: 2px;
  background: linear-gradient(90deg, transparent, #52B788, transparent);
  animation: scan 1.8s ease-in-out infinite;
}
@keyframes scan {
  0%   { top: 0%; opacity: 0; }
  10%  { opacity: 1; }
  90%  { opacity: 1; }
  100% { top: 100%; opacity: 0; }
}
.analyze-msg {
  display: flex; align-items: center; gap: 10px;
  background: rgba(0,0,0,.55); backdrop-filter: blur(6px);
  padding: 10px 16px; border-radius: 9999px;
  color: #fff; font-size: .8125rem; font-weight: 600;
}
.spinner {
  width: 16px; height: 16px; border-radius: 50%;
  border: 2px solid rgba(255,255,255,.25);
  border-top-color: #52B788;
  animation: spin .7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Result overlay */
.vf-result-overlay { position: absolute; inset: 0; display: flex; align-items: flex-end; justify-content: center; padding-bottom: 16px; }
.result-chip {
  background: rgba(30,86,49,.85); backdrop-filter: blur(6px);
  padding: 6px 14px; border-radius: 9999px;
  color: #fff; font-size: .75rem; font-weight: 700;
  border: 1px solid rgba(82,183,136,.4);
}

/* Model label */
.model-label {
  display: flex; align-items: center; gap: 6px;
  font-size: .625rem; color: #6B7280; margin-top: 6px;
  padding: 0 4px;
}
.model-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: #52B788; flex-shrink: 0;
  box-shadow: 0 0 5px #52B788;
}

/* Result card */
.result-card {
  background: #fff; border-radius: 18px; padding: 16px;
  box-shadow: 0 4px 18px rgba(0,0,0,.09);
  margin-bottom: 14px;
  border: 1.5px solid #D8F3DC;
}
.result-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 10px;
}
.result-title { font-size: .8125rem; font-weight: 700; color: #374151; }
.confidence-badge {
  padding: 3px 10px; border-radius: 9999px;
  background: #EEF9F2; color: #1E5631;
  font-size: .625rem; font-weight: 800;
  border: 1px solid #D8F3DC;
}
.result-weight-row {
  display: flex; align-items: baseline; gap: 10px; margin-bottom: 12px;
}
.result-weight {
  font-size: 2.75rem; font-weight: 900; color: #1A3D28;
  letter-spacing: -1px; line-height: 1;
}
.result-unit { font-size: 1.125rem; font-weight: 600; color: #6B7280; margin-left: 3px; }
.result-range { font-size: .75rem; color: #9CA3AF; font-weight: 600; }
.result-metrics {
  display: flex; gap: 0;
  border-top: 1px solid #E5E7EB; padding-top: 10px;
}
.rm-item {
  flex: 1; display: flex; flex-direction: column; gap: 2px;
  padding: 0 8px;
}
.rm-item:first-child { padding-left: 0; border-right: 1px solid #E5E7EB; }
.rm-item:last-child  { padding-left: 16px; }
.rm-lbl { font-size: .625rem; color: #9CA3AF; text-transform: uppercase; letter-spacing: .05em; }
.rm-val { font-size: .9375rem; font-weight: 800; color: #111827; }

/* Slide-up transition */
.slide-up-enter-active { transition: all .35s cubic-bezier(.22,1,.36,1); }
.slide-up-enter-from   { transform: translateY(24px); opacity: 0; }

/* Shutter */
.shutter-area {
  display: flex; flex-direction: column; align-items: center; gap: 10px;
  margin-bottom: 14px;
}
.shutter-hint { font-size: .75rem; color: #9CA3AF; font-weight: 600; }
.shutter-btn {
  width: 72px; height: 72px; border-radius: 50%;
  background: linear-gradient(135deg, #1A3D28, #3A9E61);
  border: 4px solid #fff; box-shadow: 0 4px 20px rgba(26,61,40,.4);
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  transition: transform .15s, box-shadow .15s;
}
.shutter-btn:hover:not(.shutter-disabled) { transform: scale(1.06); box-shadow: 0 6px 24px rgba(26,61,40,.5); }
.shutter-btn.shutter-disabled { opacity: .45; cursor: not-allowed; }
.shutter-inner { font-size: 26px; line-height: 1; }
.shutter-spinner {
  width: 24px; height: 24px; border-radius: 50%;
  border: 3px solid rgba(255,255,255,.25);
  border-top-color: #fff;
  animation: spin .7s linear infinite;
  display: block;
}

/* Action area */
.action-area { display: flex; gap: 10px; margin-bottom: 14px; }
.btn-retry {
  flex: 1; padding: 13px;
  background: #F2F5F3; border: 1.5px solid #E5E7EB;
  border-radius: 14px; font-size: .8125rem; font-weight: 700;
  color: #374151; cursor: pointer; font-family: inherit;
  transition: background .15s;
}
.btn-retry:hover { background: #E5E7EB; }
.btn-save {
  flex: 2; padding: 13px;
  background: linear-gradient(135deg, #1E5631, #3A9E61);
  color: #fff; font-size: .9375rem; font-weight: 700;
  border: none; border-radius: 14px; cursor: pointer;
  box-shadow: 0 4px 14px rgba(30,86,49,.3);
  font-family: inherit; transition: opacity .2s;
}
.btn-save:disabled { opacity: .65; }

/* Feedback */
.feedback {
  padding: 10px 14px; border-radius: 10px;
  font-size: .8125rem; font-weight: 600; margin-bottom: 12px;
}
.feedback-ok  { background: #EEF9F2; color: #1E5631; border: 1px solid #D8F3DC; }
.feedback-err { background: #FEE2E2; color: #B91C1C; border: 1px solid #FECACA; }

/* Info section */
.info-section { margin-top: 4px; }
.sec-title { font-size: .875rem; font-weight: 700; color: #111827; margin-bottom: 10px; }
.info-card {
  background: #fff; border-radius: 16px; padding: 4px 0;
  box-shadow: 0 1px 3px rgba(0,0,0,.06);
}
.info-step {
  display: flex; align-items: flex-start; gap: 12px;
  padding: 12px 16px;
}
.info-step:not(:last-child) { border-bottom: 1px solid #F3F4F6; }
.step-num {
  width: 28px; height: 28px; border-radius: 50%;
  background: #EEF9F2; border: 1.5px solid #D8F3DC;
  display: flex; align-items: center; justify-content: center;
  font-size: .75rem; font-weight: 800; color: #1E5631;
  flex-shrink: 0;
}
.step-title { font-size: .875rem; font-weight: 700; color: #111827; }
.step-sub   { font-size: .6875rem; color: #6B7280; margin-top: 2px; }

/* Status */
.status-loading { padding: 16px; text-align: center; color: #6B7280; font-size: .875rem; }
.empty-state    { text-align: center; color: #6B7280; padding: 20px 0; font-size: .875rem; }

/* Modal selector */
.search-box {
  display: flex; align-items: center; gap: 10px;
  background: #fff; border: 1.5px solid #E5E7EB;
  border-radius: 12px; padding: 10px 14px; margin-bottom: 12px;
}
.search-input {
  flex: 1; border: none; background: transparent;
  outline: none; font-size: .875rem; font-family: inherit;
}
.animal-opt {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 14px; background: #fff; border-radius: 12px;
  margin-bottom: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.06);
  cursor: pointer; transition: box-shadow .15s;
}
.animal-opt:hover { box-shadow: 0 2px 8px rgba(0,0,0,.08); }
.opt-name { font-size: .875rem; font-weight: 600; color: #111827; }
.opt-sub  { font-size: .6875rem; color: #6B7280; }
</style>
