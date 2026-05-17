export const API_BASE = '/api';

// ─── DTOs ──────────────────────────────────────────────────────────────────

export interface RazaDto {
  id: number;
  nombre: string;
}

export interface FuentePesajeDto {
  id: number;
  nombre: string;
}

export interface FincaDto {
  id: number;
  nombre: string;
  ubicacion: string | null;
  user_id: number;
}

export interface AnimalDto {
  id: number;
  numero_arete: string;
  nombre: string | null;
  raza_id: number | null;
  raza?: RazaDto;
  fecha_nacimiento: string | null;
  estado: string;
  finca_id: number | null;
  finca?: FincaDto;
}

export interface PesajeDto {
  id: number;
  animal_id: number;
  peso_estimado: number | string;
  peso_real: number | string | null;
  fecha: string;
  fuente_id: number | null;
  fuente?: FuentePesajeDto;
  animal?: AnimalDto;
}

export interface ReporteDto {
  id: number;
  user_id: number;
  tipo: string;
  archivo_url: string | null;
  fecha: string;
}

// Backend wraps some responses in { exito, datos }
export interface ApiResponse<T> {
  exito: boolean;
  datos: T;
  mensaje?: string;
}

// Legacy alias used by DashboardPage
export interface PesajesResponse extends ApiResponse<PesajeDto[]> {}

// ─── Core fetch ────────────────────────────────────────────────────────────

async function fetchJson<T>(path: string, options?: RequestInit): Promise<T> {
  const res = await fetch(`${API_BASE}${path}`, {
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...options?.headers,
    },
    ...options,
  });
  if (!res.ok) {
    throw new Error(`API ${res.status}: ${res.statusText}`);
  }
  return res.json() as Promise<T>;
}

// ─── Animales ──────────────────────────────────────────────────────────────

export const getAnimales = (): Promise<AnimalDto[]> =>
  fetchJson<AnimalDto[]>('/animales');

export const getAnimal = (id: number): Promise<AnimalDto> =>
  fetchJson<AnimalDto>(`/animales/${id}`);

export const buscarPorArete = (arete: string): Promise<AnimalDto> =>
  fetchJson<AnimalDto>(`/animales/arete/${encodeURIComponent(arete)}`);

export const getHistorialAnimal = (id: number): Promise<PesajeDto[]> =>
  fetchJson<PesajeDto[]>(`/animales/${id}/historial`);

export const crearAnimal = (data: Partial<AnimalDto>): Promise<AnimalDto> =>
  fetchJson<AnimalDto>('/animales', { method: 'POST', body: JSON.stringify(data) });

export const actualizarAnimal = (id: number, data: Partial<AnimalDto>): Promise<AnimalDto> =>
  fetchJson<AnimalDto>(`/animales/${id}`, { method: 'PUT', body: JSON.stringify(data) });

export const eliminarAnimal = (id: number): Promise<void> =>
  fetchJson<void>(`/animales/${id}`, { method: 'DELETE' });

// ─── Pesajes ───────────────────────────────────────────────────────────────

export const getPesajes = (): Promise<PesajesResponse> =>
  fetchJson<PesajesResponse>('/pesajes');

export const getPesaje = (id: number): Promise<PesajeDto> =>
  fetchJson<PesajeDto>(`/pesajes/${id}`);

export const getPesajesByAnimal = (animalId: number): Promise<PesajeDto[]> =>
  fetchJson<PesajeDto[]>(`/pesajes/animal/${animalId}`);

export const crearPesaje = (data: Partial<PesajeDto>): Promise<ApiResponse<PesajeDto>> =>
  fetchJson<ApiResponse<PesajeDto>>('/pesajes', { method: 'POST', body: JSON.stringify(data) });

export const actualizarPesaje = (id: number, data: Partial<PesajeDto>): Promise<ApiResponse<PesajeDto>> =>
  fetchJson<ApiResponse<PesajeDto>>(`/pesajes/${id}`, { method: 'PUT', body: JSON.stringify(data) });

export const eliminarPesaje = (id: number): Promise<void> =>
  fetchJson<void>(`/pesajes/${id}`, { method: 'DELETE' });

// ─── Fincas ────────────────────────────────────────────────────────────────

export const getFincas = (): Promise<FincaDto[]> =>
  fetchJson<FincaDto[]>('/fincas');

export const getFinca = (id: number): Promise<FincaDto> =>
  fetchJson<FincaDto>(`/fincas/${id}`);

export const getFincasByUsuario = (userId: number): Promise<FincaDto[]> =>
  fetchJson<FincaDto[]>(`/fincas/usuario/${userId}`);

export const crearFinca = (data: Partial<FincaDto>): Promise<FincaDto> =>
  fetchJson<FincaDto>('/fincas', { method: 'POST', body: JSON.stringify(data) });

export const actualizarFinca = (id: number, data: Partial<FincaDto>): Promise<FincaDto> =>
  fetchJson<FincaDto>(`/fincas/${id}`, { method: 'PUT', body: JSON.stringify(data) });

export const eliminarFinca = (id: number): Promise<void> =>
  fetchJson<void>(`/fincas/${id}`, { method: 'DELETE' });

// ─── Reportes ──────────────────────────────────────────────────────────────

export const getReportes = (): Promise<ReporteDto[]> =>
  fetchJson<ReporteDto[]>('/reportes');

export const getReporte = (id: number): Promise<ReporteDto> =>
  fetchJson<ReporteDto>(`/reportes/${id}`);

export const getReportesByUsuario = (userId: number): Promise<ReporteDto[]> =>
  fetchJson<ReporteDto[]>(`/reportes/usuario/${userId}`);

export const crearReporte = (data: Partial<ReporteDto>): Promise<ReporteDto> =>
  fetchJson<ReporteDto>('/reportes', { method: 'POST', body: JSON.stringify(data) });

// ─── Helpers ───────────────────────────────────────────────────────────────

export function pesoNumerico(pesaje: PesajeDto): number {
  return Number(pesaje.peso_real ?? pesaje.peso_estimado ?? 0);
}

export function formatFecha(value: string): string {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat('es-CR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  }).format(date);
}
