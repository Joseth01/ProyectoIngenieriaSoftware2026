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
  pesajes?: PesajeDto[];
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

// Backend wraps all responses in { exito, datos, mensaje }
export interface ApiResponse<T> {
  exito: boolean;
  datos: T;
  mensaje?: string;
}

// Legacy alias used by DashboardPage and ReportesPage for pesajes
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
  fetchJson<ApiResponse<AnimalDto[]>>('/animales').then(r => r.datos ?? []);

export const getAnimal = (id: number): Promise<AnimalDto> =>
  fetchJson<ApiResponse<AnimalDto>>(`/animales/${id}`).then(r => r.datos);

export const buscarPorArete = (arete: string): Promise<AnimalDto> =>
  fetchJson<ApiResponse<AnimalDto>>(`/animales/arete/${encodeURIComponent(arete)}`).then(r => r.datos);

// El endpoint devuelve el Animal con su relación pesajes cargada
export const getHistorialAnimal = (id: number): Promise<PesajeDto[]> =>
  fetchJson<ApiResponse<AnimalDto>>(`/animales/${id}/historial`)
    .then(r => r.datos?.pesajes ?? []);

export const crearAnimal = (data: Partial<AnimalDto>): Promise<AnimalDto> =>
  fetchJson<ApiResponse<AnimalDto>>('/animales', { method: 'POST', body: JSON.stringify(data) }).then(r => r.datos);

export const actualizarAnimal = (id: number, data: Partial<AnimalDto>): Promise<AnimalDto> =>
  fetchJson<ApiResponse<AnimalDto>>(`/animales/${id}`, { method: 'PUT', body: JSON.stringify(data) }).then(r => r.datos);

export const eliminarAnimal = (id: number): Promise<void> =>
  fetchJson<void>(`/animales/${id}`, { method: 'DELETE' });

// ─── Pesajes ───────────────────────────────────────────────────────────────

// getPesajes mantiene PesajesResponse para compatibilidad con DashboardPage y ReportesPage
export const getPesajes = (): Promise<PesajesResponse> =>
  fetchJson<PesajesResponse>('/pesajes');

export const getPesaje = (id: number): Promise<PesajeDto> =>
  fetchJson<ApiResponse<PesajeDto>>(`/pesajes/${id}`).then(r => r.datos);

export const getPesajesByAnimal = (animalId: number): Promise<PesajeDto[]> =>
  fetchJson<ApiResponse<PesajeDto[]>>(`/pesajes/animal/${animalId}`).then(r => r.datos ?? []);

export const crearPesaje = (data: Partial<PesajeDto>): Promise<ApiResponse<PesajeDto>> =>
  fetchJson<ApiResponse<PesajeDto>>('/pesajes', { method: 'POST', body: JSON.stringify(data) });

export const actualizarPesaje = (id: number, data: Partial<PesajeDto>): Promise<ApiResponse<PesajeDto>> =>
  fetchJson<ApiResponse<PesajeDto>>(`/pesajes/${id}`, { method: 'PUT', body: JSON.stringify(data) });

export const eliminarPesaje = (id: number): Promise<void> =>
  fetchJson<void>(`/pesajes/${id}`, { method: 'DELETE' });

// ─── Fincas ────────────────────────────────────────────────────────────────

export const getFincas = (): Promise<FincaDto[]> =>
  fetchJson<ApiResponse<FincaDto[]>>('/fincas').then(r => r.datos ?? []);

export const getFinca = (id: number): Promise<FincaDto> =>
  fetchJson<ApiResponse<FincaDto>>(`/fincas/${id}`).then(r => r.datos);

export const getFincasByUsuario = (userId: number): Promise<FincaDto[]> =>
  fetchJson<ApiResponse<FincaDto[]>>(`/fincas/usuario/${userId}`).then(r => r.datos ?? []);

export const crearFinca = (data: Partial<FincaDto>): Promise<FincaDto> =>
  fetchJson<ApiResponse<FincaDto>>('/fincas', { method: 'POST', body: JSON.stringify(data) }).then(r => r.datos);

export const actualizarFinca = (id: number, data: Partial<FincaDto>): Promise<FincaDto> =>
  fetchJson<ApiResponse<FincaDto>>(`/fincas/${id}`, { method: 'PUT', body: JSON.stringify(data) }).then(r => r.datos);

export const eliminarFinca = (id: number): Promise<void> =>
  fetchJson<void>(`/fincas/${id}`, { method: 'DELETE' });

// ─── Reportes ──────────────────────────────────────────────────────────────

export const getReportes = (): Promise<ReporteDto[]> =>
  fetchJson<ApiResponse<ReporteDto[]>>('/reportes').then(r => r.datos ?? []);

export const getReporte = (id: number): Promise<ReporteDto> =>
  fetchJson<ApiResponse<ReporteDto>>(`/reportes/${id}`).then(r => r.datos);

export const getReportesByUsuario = (userId: number): Promise<ReporteDto[]> =>
  fetchJson<ApiResponse<ReporteDto[]>>(`/reportes/usuario/${userId}`).then(r => r.datos ?? []);

export const crearReporte = (data: Partial<ReporteDto>): Promise<ReporteDto> =>
  fetchJson<ApiResponse<ReporteDto>>('/reportes', { method: 'POST', body: JSON.stringify(data) }).then(r => r.datos);

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
