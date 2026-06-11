<template>
  <ion-page>
    <ion-content :fullscreen="true" class="page-bg">

      <!-- ── App bar ── -->
      <div class="app-bar">
        <div>
          <div class="page-title">Mis Fincas</div>
          <div class="page-sub">{{ fincas.length }} registrada{{ fincas.length !== 1 ? 's' : '' }}</div>
        </div>
        <button class="btn-add" @click="abrirModal()">
          <span>＋</span> Nueva finca
        </button>
      </div>

      <div class="body-pad">

        <!-- ── Loading ── -->
        <div v-if="loading" class="status-box status-loading">Cargando fincas…</div>

        <!-- ── Error ── -->
        <div v-else-if="errorGlobal" class="status-box status-error">{{ errorGlobal }}</div>

        <!-- ── Empty state ── -->
        <div v-else-if="fincas.length === 0" class="empty-state">
          <div class="empty-ico">🏡</div>
          <div class="empty-title">Sin fincas registradas</div>
          <div class="empty-sub">Crea tu primera finca para comenzar a gestionar tus animales.</div>
          <button class="btn-add-empty" @click="abrirModal()">＋ Crear finca</button>
        </div>

        <!-- ── Lista de fincas ── -->
        <div v-else>
          <div
            v-for="finca in fincas"
            :key="finca.id"
            class="finca-card"
          >
            <div class="fc-header">
              <div class="fc-ico">🏡</div>
              <div class="fc-info">
                <div class="fc-nombre">{{ finca.nombre }}</div>
                <div class="fc-ubicacion">📍 {{ finca.ubicacion ?? 'Sin ubicación' }}</div>
              </div>
              <div class="fc-actions">
                <button class="fc-btn fc-btn-edit" @click="abrirEditar(finca)" title="Editar">✏️</button>
                <button class="fc-btn fc-btn-del"  @click="confirmarEliminar(finca)" title="Eliminar">🗑️</button>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- ════════════════════════════════════════════
           MODAL: Crear / Editar finca
           ════════════════════════════════════════════ -->
      <ion-modal :is-open="mostrarModal" @didDismiss="cerrarModal">
        <ion-content class="page-bg">

          <div class="app-bar">
            <div class="page-title" style="font-size:1.125rem">
              {{ editando ? 'Editar finca' : 'Nueva finca' }}
            </div>
            <button class="icon-btn" @click="cerrarModal">✕</button>
          </div>

          <div class="body-pad">

            <!-- Nombre -->
            <div class="nf-field">
              <label class="nf-label">Nombre de la finca <span class="req">*</span></label>
              <input
                v-model="form.nombre"
                type="text"
                class="nf-input"
                placeholder="Ej. Finca La Esperanza"
                @keyup.enter="guardar"
              />
            </div>

            <!-- Ubicación -->
            <div class="nf-field">
              <label class="nf-label">Ubicación <span class="req">*</span></label>
              <input
                v-model="form.ubicacion"
                type="text"
                class="nf-input"
                placeholder="Ej. San Carlos, Alajuela"
                @keyup.enter="guardar"
              />
            </div>

            <!-- Error / Éxito -->
            <div v-if="errorForm" class="feedback error">{{ errorForm }}</div>
            <div v-if="successForm" class="feedback success">{{ successForm }}</div>

            <!-- Botón guardar -->
            <button class="btn-save" :disabled="guardando" @click="guardar">
              <span v-if="guardando">{{ editando ? 'Guardando…' : 'Creando…' }}</span>
              <span v-else>{{ editando ? '💾 Guardar cambios' : '🏡 Crear finca' }}</span>
            </button>

          </div>
        </ion-content>
      </ion-modal>

      <!-- ════════════════════════════════════════════
           MODAL: Confirmar eliminación
           ════════════════════════════════════════════ -->
      <ion-modal :is-open="mostrarConfirm" @didDismiss="mostrarConfirm = false" class="confirm-modal">
        <ion-content class="page-bg">
          <div class="confirm-box">
            <div class="confirm-ico">⚠️</div>
            <div class="confirm-title">¿Eliminar finca?</div>
            <div class="confirm-sub">
              Se eliminará <strong>{{ fincaAEliminar?.nombre }}</strong> y todos sus datos asociados. Esta acción no se puede deshacer.
            </div>
            <div class="confirm-btns">
              <button class="confirm-cancel" @click="mostrarConfirm = false">Cancelar</button>
              <button class="confirm-del" :disabled="eliminando" @click="eliminar">
                {{ eliminando ? 'Eliminando…' : 'Sí, eliminar' }}
              </button>
            </div>
          </div>
        </ion-content>
      </ion-modal>

    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { IonPage, IonContent, IonModal } from '@ionic/vue';
import { getFincasByUsuario, crearFinca, actualizarFinca, eliminarFinca, FincaDto } from '@/services/api';

// ── Estado general ─────────────────────────────────────────────────────────
const fincas      = ref<FincaDto[]>([]);
const loading     = ref(true);
const errorGlobal = ref('');

// ── Modal crear/editar ─────────────────────────────────────────────────────
const mostrarModal = ref(false);
const editando     = ref(false);
const fincaEditId  = ref<number | null>(null);
const form         = ref({ nombre: '', ubicacion: '' });
const guardando    = ref(false);
const errorForm    = ref('');
const successForm  = ref('');

// ── Modal confirmar eliminación ────────────────────────────────────────────
const mostrarConfirm  = ref(false);
const fincaAEliminar  = ref<FincaDto | null>(null);
const eliminando      = ref(false);

// ── User id del localStorage ───────────────────────────────────────────────
let userId = 0;

// ── Helpers ────────────────────────────────────────────────────────────────
const cargarFincas = async () => {
  if (!userId) return;
  try {
    fincas.value = (await getFincasByUsuario(userId)).datos || [];
  } catch {
    errorGlobal.value = 'No se pudieron cargar las fincas.';
  }
};

const abrirModal = () => {
  editando.value   = false;
  fincaEditId.value = null;
  form.value        = { nombre: '', ubicacion: '' };
  errorForm.value   = '';
  successForm.value = '';
  mostrarModal.value = true;
};

const abrirEditar = (finca: FincaDto) => {
  editando.value    = true;
  fincaEditId.value = finca.id;
  form.value        = { nombre: finca.nombre, ubicacion: finca.ubicacion ?? '' };
  errorForm.value   = '';
  successForm.value = '';
  mostrarModal.value = true;
};

const cerrarModal = () => {
  mostrarModal.value = false;
};

const guardar = async () => {
  errorForm.value   = '';
  successForm.value = '';

  if (!form.value.nombre.trim())    { errorForm.value = 'El nombre es obligatorio.'; return; }
  if (!form.value.ubicacion.trim()) { errorForm.value = 'La ubicación es obligatoria.'; return; }

  guardando.value = true;
  try {
    if (editando.value && fincaEditId.value) {
      await actualizarFinca(fincaEditId.value, {
        nombre:    form.value.nombre.trim(),
        ubicacion: form.value.ubicacion.trim(),
      });
      successForm.value = '✓ Finca actualizada correctamente.';
    } else {
      await crearFinca({
        nombre:    form.value.nombre.trim(),
        ubicacion: form.value.ubicacion.trim(),
        user_id:   userId,
      });
      successForm.value = '✓ Finca creada correctamente.';
    }

    await cargarFincas();
    setTimeout(() => cerrarModal(), 800);

  } catch (e: unknown) {
    errorForm.value = e instanceof Error ? e.message : 'Error al guardar la finca.';
  } finally {
    guardando.value = false;
  }
};

const confirmarEliminar = (finca: FincaDto) => {
  fincaAEliminar.value = finca;
  mostrarConfirm.value = true;
};

const eliminar = async () => {
  if (!fincaAEliminar.value) return;
  eliminando.value = true;
  try {
    await eliminarFinca(fincaAEliminar.value.id);
    mostrarConfirm.value = false;
    fincaAEliminar.value = null;
    await cargarFincas();
  } catch (e: unknown) {
    errorGlobal.value = e instanceof Error ? e.message : 'Error al eliminar la finca.';
    mostrarConfirm.value = false;
  } finally {
    eliminando.value = false;
  }
};

// ── Montaje ────────────────────────────────────────────────────────────────
onMounted(async () => {
  const raw = localStorage.getItem('user');
  if (raw) userId = (JSON.parse(raw) as { id?: number }).id ?? 0;

  await cargarFincas();
  loading.value = false;
});
</script>

<style scoped>
.page-bg { --background: #F2F5F3; }

/* ── App bar ──────────────────────────────────────────────────────────── */
.app-bar {
  background: #fff;
  padding: 14px 18px 12px;
  display: flex; align-items: center; justify-content: space-between;
  border-bottom: 1px solid #E5E7EB;
}
.page-title { font-size: 1.25rem; font-weight: 800; color: #1A3D28; }
.page-sub   { font-size: .75rem; color: #6B7280; margin-top: 1px; }

.btn-add {
  display: flex; align-items: center; gap: 5px;
  padding: 8px 14px;
  background: linear-gradient(135deg, #1E5631, #3A9E61);
  color: #fff; font-size: .8125rem; font-weight: 700;
  border: none; border-radius: 10px; cursor: pointer;
  font-family: inherit;
  box-shadow: 0 2px 8px rgba(30,86,49,.3);
  transition: opacity .2s;
}
.btn-add:hover { opacity: .9; }

.icon-btn {
  width: 34px; height: 34px; border-radius: 10px;
  background: #F2F5F3; border: none; cursor: pointer; font-size: 15px;
}

/* ── Body ─────────────────────────────────────────────────────────────── */
.body-pad { padding: 18px 18px 32px; }

/* ── Status ───────────────────────────────────────────────────────────── */
.status-box {
  padding: 13px 16px; border-radius: 12px;
  font-size: .875rem; font-weight: 600; margin-bottom: 12px;
}
.status-loading { background: #F2F5F3; color: #374151; border: 1px solid #E5E7EB; }
.status-error   { background: #FEE2E2; color: #B91C1C; border: 1px solid #FECACA; }

/* ── Empty state ──────────────────────────────────────────────────────── */
.empty-state {
  display: flex; flex-direction: column; align-items: center;
  padding: 56px 24px 32px; text-align: center;
}
.empty-ico   { font-size: 3.5rem; margin-bottom: 12px; }
.empty-title { font-size: 1.125rem; font-weight: 800; color: #1A3D28; margin-bottom: 6px; }
.empty-sub   { font-size: .875rem; color: #6B7280; line-height: 1.5; margin-bottom: 24px; max-width: 260px; }
.btn-add-empty {
  padding: 13px 28px;
  background: linear-gradient(135deg, #1E5631, #3A9E61);
  color: #fff; font-size: .9375rem; font-weight: 700;
  border: none; border-radius: 14px; cursor: pointer;
  font-family: inherit; box-shadow: 0 4px 14px rgba(30,86,49,.3);
  transition: opacity .2s;
}
.btn-add-empty:hover { opacity: .9; }

/* ── Finca card ───────────────────────────────────────────────────────── */
.finca-card {
  background: #fff; border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,.07);
  margin-bottom: 12px; overflow: hidden;
  transition: box-shadow .2s;
}
.finca-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.1); }

.fc-header {
  display: flex; align-items: center; gap: 12px;
  padding: 16px;
}
.fc-ico {
  width: 48px; height: 48px; border-radius: 13px; flex-shrink: 0;
  background: linear-gradient(135deg, #EEF9F2, #D8F3DC);
  border: 1.5px solid #B7E5CC;
  display: flex; align-items: center; justify-content: center;
  font-size: 22px;
}
.fc-info { flex: 1; min-width: 0; }
.fc-nombre    { font-size: 1rem; font-weight: 800; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.fc-ubicacion { font-size: .75rem; color: #6B7280; margin-top: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.fc-actions { display: flex; gap: 6px; flex-shrink: 0; }
.fc-btn {
  width: 32px; height: 32px; border-radius: 9px;
  border: none; cursor: pointer; font-size: 14px;
  display: flex; align-items: center; justify-content: center;
  transition: background .15s;
}
.fc-btn-edit { background: #EEF9F2; }
.fc-btn-edit:hover { background: #D8F3DC; }
.fc-btn-del  { background: #FEE2E2; }
.fc-btn-del:hover  { background: #FECACA; }

/* ── Modal form ───────────────────────────────────────────────────────── */
.nf-field { margin-bottom: 16px; }
.nf-label {
  display: block; font-size: .8125rem; font-weight: 700;
  color: #374151; margin-bottom: 6px;
}
.req { color: #EF4444; }
.nf-input {
  width: 100%; padding: 12px 14px;
  border: 1.5px solid #E5E7EB; border-radius: 12px;
  font-size: .9375rem; color: #111827; background: #F9FAFB;
  outline: none; font-family: inherit;
  transition: border-color .15s, background .15s;
  box-sizing: border-box;
}
.nf-input:focus { border-color: #1E5631; background: #fff; }

.feedback {
  padding: 10px 14px; border-radius: 10px;
  font-size: .8125rem; font-weight: 600; margin-bottom: 14px;
}
.success { background: #EEF9F2; color: #1E5631; border: 1px solid #D8F3DC; }
.error   { background: #FEE2E2; color: #B91C1C; border: 1px solid #FECACA; }

.btn-save {
  width: 100%; padding: 15px;
  background: linear-gradient(135deg, #1E5631, #3A9E61);
  color: #fff; font-size: .9375rem; font-weight: 700;
  border: none; border-radius: 14px; cursor: pointer;
  font-family: inherit; box-shadow: 0 4px 14px rgba(30,86,49,.3);
  transition: opacity .2s;
}
.btn-save:disabled { opacity: .65; cursor: not-allowed; }

/* ── Confirm modal ────────────────────────────────────────────────────── */
.confirm-box {
  display: flex; flex-direction: column; align-items: center;
  padding: 48px 28px 36px; text-align: center;
}
.confirm-ico   { font-size: 3rem; margin-bottom: 14px; }
.confirm-title { font-size: 1.25rem; font-weight: 800; color: #111827; margin-bottom: 8px; }
.confirm-sub   { font-size: .875rem; color: #6B7280; line-height: 1.55; margin-bottom: 28px; max-width: 280px; }
.confirm-btns  { display: flex; gap: 10px; width: 100%; max-width: 320px; }
.confirm-cancel {
  flex: 1; padding: 13px;
  background: #F2F5F3; border: 1.5px solid #E5E7EB;
  color: #374151; font-size: .9375rem; font-weight: 700;
  border-radius: 14px; cursor: pointer; font-family: inherit;
  transition: background .15s;
}
.confirm-cancel:hover { background: #E5E7EB; }
.confirm-del {
  flex: 1; padding: 13px;
  background: #EF4444; border: none;
  color: #fff; font-size: .9375rem; font-weight: 700;
  border-radius: 14px; cursor: pointer; font-family: inherit;
  transition: opacity .15s;
}
.confirm-del:disabled { opacity: .65; cursor: not-allowed; }
.confirm-del:not(:disabled):hover { opacity: .9; }
</style>
