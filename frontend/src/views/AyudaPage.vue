<template>
  <ion-page>
    <ion-content>
      <div class="main-container ion-padding">
        
        <!-- CUADRO PRINCIPAL -->
        <div class="card-white header-card-special">
          <h1 class="text-green-dark">Centro de Ayuda</h1>
          <p class="text-gray">
            Encuentra respuestas rápidas y soporte especializado para la gestión de tu ganado y operaciones agrícolas.
          </p>
          
          <div class="search-box">
            <ion-icon :icon="searchOutline"></ion-icon>
            <input 
              type="text" 
              v-model="searchQuery" 
              placeholder="Buscar guías, documentación o soporte..." 
            />
          </div>
        </div>

        <!-- TARJETAS DE ACCESO RÁPIDO -->
        <div v-for="(card, index) in filteredCards" :key="card.title" class="card-white">
          <div class="circle-icon blue-bg">
            <ion-icon :icon="card.icon"></ion-icon>
          </div>
          <h3 class="text-black">{{ card.title }}</h3>
          <p class="text-gray">{{ card.description }}</p>
          
          <button 
            :class="card.btnClass" 
            @click="toggleCard(index)"
          >
            {{ expandedCardIndex === index ? 'Cerrar información' : card.btnText }}
          </button>

          <div v-if="expandedCardIndex === index" class="inner-detail ion-padding-top">
            <div class="detail-box">
              <p class="text-gray-dark" style="white-space: pre-wrap;">{{ card.detail }}</p>
            </div>
          </div>
        </div>

        <!-- SECCIÓN FAQ -->
        <div class="section-header" v-if="filteredFaqs.length > 0">
          <h2 class="text-green-dark">Preguntas Frecuentes</h2>
          <span class="text-gray-small">Ver todas</span>
        </div>

        <ion-accordion-group>
          <ion-accordion v-for="(faq, index) in filteredFaqs" :key="index" :value="'q' + index" class="accordion-white">
            <ion-item slot="header" lines="none" class="accordion-header-custom">
              <ion-label class="text-white font-bold">{{ faq.question }}</ion-label>
            </ion-item>
            <div class="faq-text ion-padding" slot="content">
              {{ faq.answer }}
            </div>
          </ion-accordion>
        </ion-accordion-group>

        <!-- FOOTER STRIPS -->
        <div class="strip-footer" @click="showCommunityDetail = !showCommunityDetail">
          <div class="strip-icon-bg">
            <ion-icon :icon="chatboxEllipsesOutline"></ion-icon>
          </div>
          <div class="strip-info">
            <h4 class="text-green-dark">Comunidad AgriManager</h4>
            <p class="text-gray-dark">Conecta con otros ganaderos para compartir mejores prácticas.</p>
            <span class="text-green-link" v-if="!showCommunityDetail">Unirse al foro →</span>
            <p class="text-gray-small ion-padding-top" v-else>
              Próximamente: Un foro exclusivo para clientes de BovWeight CR.
            </p>
          </div>
        </div>

      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { 
  IonPage, IonContent, IonIcon, IonAccordion, IonAccordionGroup, 
  IonItem, IonLabel 
} from '@ionic/vue';
import { 
  searchOutline, headsetOutline, bookOutline, playCircleOutline, 
  chatboxEllipsesOutline 
} from 'ionicons/icons';

const searchQuery = ref('');
const expandedCardIndex = ref<number | null>(null);
const showCommunityDetail = ref(false);

const cards = [
  {
    title: 'Contacto Directo',
    description: 'Habla con nuestro equipo técnico disponible 24/7 para emergencias en el campo.',
    icon: headsetOutline,
    btnText: 'Contactar Soporte',
    btnClass: 'btn-green-fill',
    detail: '• Soporte Telefónico: +506 800-GANADO\n• WhatsApp Empresarial: +506 7000-0000\n• Correo: soporte@bovweight.cr\n\nHorario de atención técnica presencial: L-V 7:00 AM - 5:00 PM.'
  },
  {
    title: 'Guía de Inicio',
    description: 'Aprende los conceptos básicos para configurar tus sectores y cargar inventario inicial.',
    icon: bookOutline,
    btnText: 'Ver Documentación',
    btnClass: 'btn-green-ghost',
    detail: 'Pasos iniciales:\n1. Configura tu perfil de Finca.\n2. Crea tus sectores de pastoreo.\n3. Registra tu primer lote de animales.\n4. Establece metas de peso por temporada.'
  },
  {
    title: 'Video Tutoriales',
    description: 'Aprende visualmente cómo utilizar los reportes avanzados y análisis de datos.',
    icon: playCircleOutline,
    btnText: 'Explorar Videos',
    btnClass: 'btn-green-ghost',
    detail: 'Serie de videos disponibles:\n- Introducción a BovWeight CR (5 min)\n- Gestión de inventario masivo (10 min)\n- Interpretación de gráficos de rendimiento (8 min)\n- Uso de la App sin conexión a internet (4 min)'
  }
];

const faqs = [
  {
    question: '¿Cómo añado un nuevo lote de ganado al sistema?',
    answer: "Para registrar un lote: 1. Ve a la sección 'Finca' en el menú principal. 2. Selecciona la pestaña 'Lotes'. 3. Toca el botón flotante (+) en la esquina inferior. 4. Completa la información del grupo (Raza, edad promedio y sector)."
  },
  {
    question: '¿Puedo usar la aplicación sin conexión a internet?',
    answer: "Sí, BovWeight CR permite registrar pesos y movimientos de ganado en modo offline. Los datos se sincronizarán automáticamente cuando recuperes la conexión."
  },
  {
    question: '¿Cómo exporto los reportes de peso a Excel?',
    answer: "Desde el módulo de 'Reportes', selecciona el rango de fechas deseado y presiona el ícono de compartir. Elige 'Exportar como CSV/Excel' para enviar el archivo por correo o WhatsApp."
  },
  {
    question: '¿Es posible compartir mi finca con otros colaboradores?',
    answer: "Desde los ajustes de 'Mi Finca', puedes enviar invitaciones por correo electrónico para añadir administradores o capataces con permisos limitados."
  }
];

// Mejora en el filtro: busca en títulos, descripciones y el texto detallado
const filteredCards = computed(() => {
  const query = searchQuery.value.toLowerCase();
  return cards.filter(c => 
    c.title.toLowerCase().includes(query) || 
    c.description.toLowerCase().includes(query) ||
    c.detail.toLowerCase().includes(query)
  );
});

const filteredFaqs = computed(() => {
  const query = searchQuery.value.toLowerCase();
  return faqs.filter(f => 
    f.question.toLowerCase().includes(query) || 
    f.answer.toLowerCase().includes(query)
  );
});

const toggleCard = (index: number) => {
  expandedCardIndex.value = expandedCardIndex.value === index ? null : index;
};
</script>

<style scoped>
/* CLASES DE TEXTO */
.text-white { color: #ffffff !important; }
.text-black { color: #000000 !important; }
.text-gray { color: #4a5568; font-size: 0.95rem; line-height: 1.5; }
.text-gray-dark { color: #2d3748; font-size: 0.85rem; margin: 2px 0; }
.text-green-dark { color: #1a3a2a; font-weight: 700; font-size: 1.2rem; margin-bottom: 8px; }
.text-green-link { color: #1a3a2a; font-weight: 700; font-size: 0.9rem; }
.text-gray-small { color: #718096; font-size: 0.85rem; }
.font-bold { font-weight: 700; }

ion-content { --background: #f4f9ff; }
.main-container { max-width: 500px; margin: 0 auto; }
.header-card-special { text-align: left !important; border-top: 5px solid #1a3a2a; }

.card-white {
  background: #ffffff;
  border: 1px solid #eef2f6;
  border-radius: 16px;
  padding: 25px;
  text-align: center;
  margin-bottom: 20px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.03);
}

.inner-detail { margin-top: 15px; border-top: 1px dashed #e2e8f0; text-align: left; }
.detail-box { background: #f8fafc; padding: 15px; border-radius: 12px; }

.search-box {
  background: #f1f5f9; border-radius: 12px; display: flex; align-items: center;
  padding: 12px 16px; margin-top: 15px; border: 1px solid #e2e8f0;
}
.search-box input { background: transparent; border: none; outline: none; width: 100%; margin-left: 10px; font-size: 0.95rem; }

.circle-icon { width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; }
.blue-bg { background: #e6f0ff; color: #3182ce; }

.btn-green-fill { background: #064e3b; color: white; border: none; width: 100%; padding: 14px; border-radius: 10px; font-weight: 700; margin-top: 15px; }
.btn-green-ghost { background: #f0fdf4; color: #166534; border: none; width: 100%; padding: 14px; border-radius: 10px; font-weight: 700; margin-top: 15px; }

.section-header { display: flex; justify-content: space-between; align-items: center; margin: 30px 5px 15px; }

/* AJUSTES ESPECÍFICOS PARA EL HEADER OSCURO DEL ACORDEÓN */
.accordion-white {
  background: #ffffff;
  border: 1px solid #eef2f6;
  border-radius: 12px;
  margin-bottom: 10px;
  overflow: hidden;
}

.accordion-header-custom {
  --background: #1e1e1e; /* Fondo oscuro como solicitado */
  --color: #ffffff;      /* Color de la flecha */
}

.faq-text {
  color: #ffffff !important;
  background: #1e1e1e;
  font-size: 0.9rem;
  line-height: 1.4;
}

/* Forzar el color de la flecha de Ionic a blanco */
ion-accordion.accordion-expanding ion-item[slot="header"],
ion-accordion.accordion-expanded ion-item[slot="header"],
ion-accordion ion-item[slot="header"] {
  --ion-color-primary: #ffffff;
}

.strip-footer { background: #ebf8ff; border-radius: 16px; padding: 18px; display: flex; gap: 15px; align-items: center; margin-top: 20px; cursor: pointer; }
.strip-icon-bg { background: white; padding: 10px; border-radius: 10px; font-size: 1.4rem; }
</style> 