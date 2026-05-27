export const API_BASE = 'http://127.0.0.1:8000/api';

// ─────────────────────────────────────────────
// DTOs
// ─────────────────────────────────────────────

export interface ApiResponse<T> {
  exito: boolean;
  mensaje: string;
  datos: T;
}

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

// ─────────────────────────────────────────────
// FETCH BASE
// ─────────────────────────────────────────────

async function fetchJson<T>(
  path: string,
  options?: RequestInit
): Promise<T> {

  const token = localStorage.getItem('token');

  const response = await fetch(`${API_BASE}${path}`, {

    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',

      ...(token
        ? { Authorization: `Bearer ${token}` }
        : {}),

      ...options?.headers
    },

    ...options
  });

  const data = await response.json();

  if (!response.ok) {
    throw new Error(
      data?.mensaje ||
      `Error ${response.status}`
    );
  }

  return data as T;
}

// ─────────────────────────────────────────────
// ANIMALES
// ─────────────────────────────────────────────

export const getAnimales = () =>
  fetchJson<ApiResponse<AnimalDto[]>>('/animales');

export const getAnimal = (id: number) =>
  fetchJson<ApiResponse<AnimalDto>>(`/animales/${id}`);

export const buscarPorArete = (arete: string) =>
  fetchJson<ApiResponse<AnimalDto>>(
    `/animales/arete/${encodeURIComponent(arete)}`
  );

export const getHistorialAnimal = (id: number) =>
  fetchJson<ApiResponse<PesajeDto[]>>(
    `/animales/${id}/historial`
  );

export const crearAnimal = (
  data: Partial<AnimalDto>
) =>
  fetchJson<ApiResponse<AnimalDto>>(
    '/animales',
    {
      method: 'POST',
      body: JSON.stringify(data)
    }
  );

export const actualizarAnimal = (
  id: number,
  data: Partial<AnimalDto>
) =>
  fetchJson<ApiResponse<AnimalDto>>(
    `/animales/${id}`,
    {
      method: 'PUT',
      body: JSON.stringify(data)
    }
  );

export const eliminarAnimal = (id: number) =>
  fetchJson<ApiResponse<void>>(
    `/animales/${id}`,
    {
      method: 'DELETE'
    }
  );

// ─────────────────────────────────────────────
// PESAJES
// ─────────────────────────────────────────────

export const getPesajes = () =>
  fetchJson<ApiResponse<PesajeDto[]>>('/pesajes');

export const getPesaje = (id: number) =>
  fetchJson<ApiResponse<PesajeDto>>(
    `/pesajes/${id}`
  );

export const getPesajesByAnimal = (
  animalId: number
) =>
  fetchJson<ApiResponse<PesajeDto[]>>(
    `/pesajes/animal/${animalId}`
  );

export const crearPesaje = (
  data: Partial<PesajeDto>
) =>
  fetchJson<ApiResponse<PesajeDto>>(
    '/pesajes',
    {
      method: 'POST',
      body: JSON.stringify(data)
    }
  );

export const actualizarPesaje = (
  id: number,
  data: Partial<PesajeDto>
) =>
  fetchJson<ApiResponse<PesajeDto>>(
    `/pesajes/${id}`,
    {
      method: 'PUT',
      body: JSON.stringify(data)
    }
  );

export const eliminarPesaje = (id: number) =>
  fetchJson<ApiResponse<void>>(
    `/pesajes/${id}`,
    {
      method: 'DELETE'
    }
  );

// ─────────────────────────────────────────────
// FINCAS
// ─────────────────────────────────────────────

export const getFincas = () =>
  fetchJson<ApiResponse<FincaDto[]>>(
    '/fincas'
  );

export const getFinca = (id: number) =>
  fetchJson<ApiResponse<FincaDto>>(
    `/fincas/${id}`
  );

export const getFincasByUsuario = (
  userId: number
) =>
  fetchJson<ApiResponse<FincaDto[]>>(
    `/fincas/usuario/${userId}`
  );

export const crearFinca = (
  data: Partial<FincaDto>
) =>
  fetchJson<ApiResponse<FincaDto>>(
    '/fincas',
    {
      method: 'POST',
      body: JSON.stringify(data)
    }
  );

export const actualizarFinca = (
  id: number,
  data: Partial<FincaDto>
) =>
  fetchJson<ApiResponse<FincaDto>>(
    `/fincas/${id}`,
    {
      method: 'PUT',
      body: JSON.stringify(data)
    }
  );

export const eliminarFinca = (id: number) =>
  fetchJson<ApiResponse<void>>(
    `/fincas/${id}`,
    {
      method: 'DELETE'
    }
  );

// ─────────────────────────────────────────────
// REPORTES
// ─────────────────────────────────────────────

export const getReportes = () =>
  fetchJson<ApiResponse<ReporteDto[]>>(
    '/reportes'
  );

export const getReporte = (id: number) =>
  fetchJson<ApiResponse<ReporteDto>>(
    `/reportes/${id}`
  );

export const getReportesByUsuario = (
  userId: number
) =>
  fetchJson<ApiResponse<ReporteDto[]>>(
    `/reportes/usuario/${userId}`
  );

export const crearReporte = (
  data: Partial<ReporteDto>
) =>
  fetchJson<ApiResponse<ReporteDto>>(
    '/reportes',
    {
      method: 'POST',
      body: JSON.stringify(data)
    }
  );

// ─────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────

export function pesoNumerico(
  pesaje: PesajeDto
): number {

  return Number(
    pesaje.peso_real ??
    pesaje.peso_estimado ??
    0
  );
}

export function formatFecha(
  value: string
): string {

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return new Intl.DateTimeFormat(
    'es-CR',
    {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    }
  ).format(date);
}