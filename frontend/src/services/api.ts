const API_BASE = 'http://127.0.0.1:8000/api';

export interface ApiResponse<T> {
  exito: boolean;
  mensaje: string;
  datos: T;
  errores?: any;
}

export interface UsuarioDto {
  id: number;
  name: string;
  email: string;
  rol?: string;
}

export interface LoginDto {
  email: string;
  password: string;
}

export interface RegistroDto {
  name: string;
  email: string;
  password: string;
  password_confirmation?: string;
  rol?: string;
}

export type RolUsuario = 'ganadero' | 'veterinario';

export interface UsuarioDto {
  id: number;
  name: string;
  email: string;
  rol: RolUsuario;
}

export interface RazaDto {
  id: number;
  nombre: string;
  created_at?: string;
  updated_at?: string;
}

export interface FuentePesajeDto {
  id: number;
  nombre: string;
  descripcion?: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface FincaDto {
  id: number;
  nombre: string;
  ubicacion?: string | null;
  descripcion?: string | null;
  user_id?: number;
  created_at?: string;
  updated_at?: string;
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
  peso_real?: number | string | null;
  fecha: string;
  fuente_id?: number | null;
  created_at?: string;
  updated_at?: string;
  animal?: AnimalDto | null;
  fuente?: FuentePesajeDto | null;
}

export interface CrearPesajeDto {
  animal_id: number;
  peso_estimado?: number;
  peso_real?: number | null;
  fecha: string;
  fuente_id?: number | null;
  metodo_estimacion?: 'yolov8' | 'regresion' | 'tabla';
  raza?: string;
  edad_meses?: number;
  largo_corporal_cm?: number;
  perimetro_toracico_cm?: number;
  peso_referencia?: number;
}

export interface ImagenDto {
  id: number;
  pesaje_id: number;
  url: string;
  procesada: boolean | number;
  fecha: string;
  created_at?: string;
  updated_at?: string;
}

export interface ReporteDto {
  id: number;
  tipo: string;
  fecha: string;
  archivo_url?: string | null;
  user_id?: number;
  finca_id?: number | null;
  created_at?: string;
  updated_at?: string;
}

// Backend wraps all responses in { exito, datos, mensaje }
export interface ApiResponse<T> {
  exito: boolean;
  datos: T;
  mensaje?: string;
}

// Legacy alias used by DashboardPage and ReportesPage for pesajes
export interface PesajesResponse extends ApiResponse<PesajeDto[]> {}

export interface EstimacionPesoDto {
  peso_estimado: number;
  confianza?: number;
  condicion_corporal?: number | null;
  alzada_estimada?: number | null;
  mensaje?: string;
}

async function fetchJson<T>(path: string, options?: RequestInit): Promise<T> {
  const res = await fetch(`${API_BASE}${path}`, {
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...options?.headers,
    },
    ...options,
  });

  // Always parse body first — needed for both success and error handling
  const body = await res.json().catch(() => null);

  if (!res.ok) {
    // 1. Mensaje propio del backend (ApiResponse::error)
    // 2. Laravel validation errors (422): toma el primer error de campo
    // 3. Mensaje genérico de Laravel (message)
    // 4. Fallback con el status code
    type ErrBody = { mensaje?: string; message?: string; errors?: Record<string, string[]> };
    const b = body as ErrBody | null;
    const validationMsg = b?.errors
      ? Object.values(b.errors).flat()[0]
      : undefined;
    const msg = b?.mensaje ?? validationMsg ?? b?.message ?? `Error ${res.status}`;
    throw new Error(msg);
  }

  return body as T;
}

// ─── Razas ─────────────────────────────────────────────────────────────────

export const getRazas = (): Promise<RazaDto[]> =>
  fetchJson<ApiResponse<RazaDto[]>>('/razas').then(r => r.datos ?? []);

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

  if (!response.ok) {
    const mensaje =
      data?.mensaje ||
      data?.message ||
      data?.error ||
      'Ocurrió un error al comunicarse con el servidor.';

    throw new Error(mensaje);
  }

// getPesajes mantiene PesajesResponse para compatibilidad con DashboardPage y ReportesPage
export const getPesajes = (): Promise<PesajesResponse> =>
  fetchJson<PesajesResponse>('/pesajes');

export const getPesaje = (id: number): Promise<PesajeDto> =>
  fetchJson<ApiResponse<PesajeDto>>(`/pesajes/${id}`).then(r => r.datos);

export const getPesajesByAnimal = (animalId: number): Promise<PesajeDto[]> =>
  fetchJson<ApiResponse<PesajeDto[]>>(`/pesajes/animal/${animalId}`).then(r => r.datos ?? []);

  if (data.fuente_id) {
    formData.append(
      'fuente_id',
      String(data.fuente_id)
    );
  }

  return fetchJson<ApiResponse<ResultadoPesajeIADto>>(
    '/pesajes/confirmar-ia',
    {
      method: 'POST',
      body: formData
    }
  );
};

/* =========================
   FINCAS
========================= */

export const getFincas = () =>
  fetchJson<ApiResponse<FincaDto[]>>(
    '/fincas'
  );

export const getFinca = (
  id: number
) =>
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

export const eliminarFinca = (
  id: number
) =>
  fetchJson<ApiResponse<null>>(
    `/fincas/${id}`,
    {
      method: 'DELETE'
    }
  );

/* =========================
   REPORTES
========================= */

export const getReportes = () =>
  fetchJson<ApiResponse<ReporteDto[]>>(
    '/reportes'
  );

export const getReporte = (
  id: number
) =>
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
  data: CrearReporteDto
) =>
  fetchJson<ApiResponse<ReporteDto>>(
    '/reportes',
    {
      method: 'POST',
      body: JSON.stringify(data)
    }
  );

/* =========================
   HELPERS
========================= */

export function pesoNumerico(
  pesaje: PesajeDto
): number {
  const valor =
    pesaje.peso_real ??
    pesaje.peso_estimado ??
    0;

  return Number(valor) || 0;
}

export function formatFecha(
  value: string | null | undefined
): string {
  if (!value) {
    return '---';
  }

  const date = new Date(value);

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

  const hoy = new Date();

  let meses =
    (hoy.getFullYear() - nacimiento.getFullYear()) * 12 +
    (hoy.getMonth() - nacimiento.getMonth());

export const getReportes = (): Promise<ReporteDto[]> =>
  fetchJson<ApiResponse<ReporteDto[]>>('/reportes').then(r => r.datos ?? []);

export const getReporte = (id: number): Promise<ReporteDto> =>
  fetchJson<ApiResponse<ReporteDto>>(`/reportes/${id}`).then(r => r.datos);

export const getReportesByUsuario = (userId: number): Promise<ReporteDto[]> =>
  fetchJson<ApiResponse<ReporteDto[]>>(`/reportes/usuario/${userId}`).then(r => r.datos ?? []);

export const crearReporte = (data: Partial<ReporteDto>): Promise<ReporteDto> =>
  fetchJson<ApiResponse<ReporteDto>>('/reportes', { method: 'POST', body: JSON.stringify(data) }).then(r => r.datos);

// ─── Usuarios ──────────────────────────────────────────────────────────────

export const registrarUsuario = (data: {
  name: string;
  email: string;
  password: string;
  rol: RolUsuario;
}): Promise<UsuarioDto> =>
  fetchJson<ApiResponse<UsuarioDto>>('/usuarios/registro', {
    method: 'POST',
    body: JSON.stringify(data),
  }).then(r => r.datos);

export const loginUsuario = (data: {
  email: string;
  password: string;
}): Promise<UsuarioDto> =>
  fetchJson<ApiResponse<UsuarioDto>>('/usuarios/login', {
    method: 'POST',
    body: JSON.stringify(data),
  }).then(r => r.datos);

  const mesesRestantes =
    meses % 12;

  if (mesesRestantes === 0) {
    return `${años} año${años === 1 ? '' : 's'}`;
  }

  return `${años} año${años === 1 ? '' : 's'} ${mesesRestantes} mes${mesesRestantes === 1 ? '' : 'es'}`;
}