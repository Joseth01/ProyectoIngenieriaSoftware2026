/**
 * Recordatorios de pesaje (Don Iván: "que me avise cuando tengo que volver a pesar").
 *
 * Calcula, para cada animal activo, la fecha del próximo pesaje sugerido
 * (último pesaje + intervalo configurado) y programa notificaciones locales
 * con Capacitor LocalNotifications.
 *
 * En navegador (npm run dev) las notificaciones NO se disparan: el plugin solo
 * funciona en la app instalada (Android/iOS). En ese caso la vista de
 * Recordatorios igual muestra la lista calculada en pantalla.
 */
import { Capacitor } from '@capacitor/core';
import { LocalNotifications } from '@capacitor/local-notifications';
import type { AnimalDto, PesajeDto } from '@/services/api';

const CONFIG_KEY = 'recordatorios_config';

export interface RecordatoriosConfig {
  activo: boolean;
  intervaloDias: number;
}

export interface RecordatorioCalculado {
  animalId: number;
  animalNombre: string;
  arete: string;
  ultimoPesaje: string | null;   // ISO yyyy-mm-dd o null si nunca se pesó
  proximaFecha: string;          // ISO yyyy-mm-dd
  diasRestantes: number;         // negativo si está vencido
  vencido: boolean;
  sinPesajes: boolean;
}

const CONFIG_DEFECTO: RecordatoriosConfig = { activo: false, intervaloDias: 30 };

export function leerConfig(): RecordatoriosConfig {
  try {
    const raw = localStorage.getItem(CONFIG_KEY);
    if (!raw) return { ...CONFIG_DEFECTO };
    const parsed = JSON.parse(raw);
    return {
      activo: Boolean(parsed.activo),
      intervaloDias: Number(parsed.intervaloDias) > 0 ? Number(parsed.intervaloDias) : 30,
    };
  } catch {
    return { ...CONFIG_DEFECTO };
  }
}

export function guardarConfig(config: RecordatoriosConfig): void {
  localStorage.setItem(CONFIG_KEY, JSON.stringify(config));
}

/** Las notificaciones nativas solo existen en la app instalada, no en el navegador. */
export function notificacionesDisponibles(): boolean {
  return Capacitor.isNativePlatform();
}

export async function pedirPermiso(): Promise<boolean> {
  if (!notificacionesDisponibles()) return false;
  const estado = await LocalNotifications.requestPermissions();
  return estado.display === 'granted';
}

function soloFecha(valor: string | null | undefined): string | null {
  if (!valor) return null;
  return valor.slice(0, 10);
}

function sumarDias(fechaIso: string, dias: number): string {
  const d = new Date(fechaIso + 'T00:00:00');
  d.setDate(d.getDate() + dias);
  return d.toISOString().slice(0, 10);
}

function diasEntre(desdeIso: string, hastaIso: string): number {
  const a = new Date(desdeIso + 'T00:00:00').getTime();
  const b = new Date(hastaIso + 'T00:00:00').getTime();
  return Math.round((b - a) / (1000 * 60 * 60 * 24));
}

/**
 * Calcula los recordatorios para los animales activos a partir de sus pesajes.
 * Ordena por urgencia (los más vencidos primero).
 */
export function calcularRecordatorios(
  animales: AnimalDto[],
  pesajes: PesajeDto[],
  intervaloDias: number
): RecordatorioCalculado[] {
  const hoy = new Date().toISOString().slice(0, 10);

  // Último pesaje por animal
  const ultimoPorAnimal = new Map<number, string>();
  for (const p of pesajes) {
    const fecha = soloFecha(p.fecha);
    if (!fecha) continue;
    const previo = ultimoPorAnimal.get(p.animal_id);
    if (!previo || fecha > previo) {
      ultimoPorAnimal.set(p.animal_id, fecha);
    }
  }

  const resultado: RecordatorioCalculado[] = animales
    .filter(a => a.estado !== 'inactivo')
    .map(a => {
      const ultimo = ultimoPorAnimal.get(a.id) ?? null;
      const base = ultimo ?? hoy;
      const proximaFecha = sumarDias(base, intervaloDias);
      const diasRestantes = diasEntre(hoy, proximaFecha);

      return {
        animalId: a.id,
        animalNombre: a.nombre || `Arete ${a.numero_arete}`,
        arete: a.numero_arete,
        ultimoPesaje: ultimo,
        proximaFecha,
        diasRestantes,
        vencido: diasRestantes <= 0,
        sinPesajes: ultimo === null,
      };
    });

  return resultado.sort((x, y) => x.diasRestantes - y.diasRestantes);
}

/**
 * Cancela todas las notificaciones pendientes y programa de nuevo según los
 * recordatorios vigentes (solo las futuras; las vencidas no se programan).
 * Devuelve cuántas notificaciones nativas quedaron programadas.
 */
export async function reprogramarNotificaciones(
  recordatorios: RecordatorioCalculado[]
): Promise<number> {
  if (!notificacionesDisponibles()) return 0;

  // Limpiar pendientes previas
  const pendientes = await LocalNotifications.getPending();
  if (pendientes.notifications.length > 0) {
    await LocalNotifications.cancel({ notifications: pendientes.notifications });
  }

  const aProgramar = recordatorios
    .filter(r => !r.vencido) // solo futuras
    .map(r => ({
      id: r.animalId,
      title: 'Pesaje pendiente 🐄',
      body: `Es momento de volver a pesar a ${r.animalNombre} (arete ${r.arete}).`,
      schedule: { at: new Date(r.proximaFecha + 'T08:00:00') },
    }));

  if (aProgramar.length > 0) {
    await LocalNotifications.schedule({ notifications: aProgramar });
  }

  return aProgramar.length;
}

export async function cancelarTodas(): Promise<void> {
  if (!notificacionesDisponibles()) return;
  const pendientes = await LocalNotifications.getPending();
  if (pendientes.notifications.length > 0) {
    await LocalNotifications.cancel({ notifications: pendientes.notifications });
  }
}
