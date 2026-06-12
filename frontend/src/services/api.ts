const API_BASE = 'http://127.0.0.1:8000/api';

export type RolUsuario = 'ganadero' | 'veterinario';

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
  nombre: string;
  raza_id: number;
  finca_id: number;
  fecha_nacimiento: string;
  estado?: string;
  created_at?: string;
  updated_at?: string;
  raza?: RazaDto | null;
  finca?: FincaDto | null;
  pesajes?: PesajeDto[];
}

export interface CrearAnimalDto {
  numero_arete: string;
  nombre: string;
  raza_id: number;
  fecha_nacimiento: string;
  finca_id: number;
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

export interface CrearReporteDto {
  tipo: string;
  fecha: string;
  archivo_url?: string | null;
  user_id?: number;
  finca_id?: number | null;
}

export interface PerfilCompletoDto {
  usuario: UsuarioDto;
  fincas: FincaDto[];
  total_animales: number;
  total_fincas: number;
}

export interface EstimacionPesoDto {
  peso_estimado: number;
  confianza?: number;
  condicion_corporal?: number | null;
  alzada_estimada?: number | null;
  mensaje?: string;
}

export interface ResultadoPesajeIADto {
  estimacion: EstimacionPesoDto;
  pesaje: PesajeDto;
  imagen: ImagenDto;
}

export interface ConfirmarPesajeIAParams {
  imagen: Blob;
  animal_id: number;
  peso_estimado: number;
  peso_real?: number | null;
  fecha: string;
  fuente_id?: number | null;
}

export function getToken(): string | null {
  return localStorage.getItem('token');
}

export function getUsuarioLocal(): UsuarioDto | null {
  const user = localStorage.getItem('user');

  if (!user) {
    return null;
  }

  try {
    return JSON.parse(user) as UsuarioDto;
  } catch {
    return null;
  }
}

export function guardarSesion(
  token: string,
  user: UsuarioDto
): void {
  localStorage.setItem('token', token);
  localStorage.setItem('user', JSON.stringify(user));
}

export function limpiarSesion(): void {
  localStorage.removeItem('token');
  localStorage.removeItem('user');
}

async function fetchJson<T>(
  endpoint: string,
  options: RequestInit = {}
): Promise<T> {
  const token = getToken();

  const isFormData =
    options.body instanceof FormData;

  const headers: HeadersInit = {
    Accept: 'application/json',
    ...(isFormData ? {} : { 'Content-Type': 'application/json' }),
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...(options.headers || {})
  };

  const response = await fetch(
    `${API_BASE}${endpoint}`,
    {
      ...options,
      headers
    }
  );

  let data: any = null;

  try {
    data = await response.json();
  } catch {
    data = null;
  }

  if (!response.ok) {
    const mensaje =
      data?.mensaje ||
      data?.message ||
      data?.error ||
      'Ocurrió un error al comunicarse con el servidor.';

    throw new Error(mensaje);
  }

  return data as T;
}

/* =========================
   USUARIOS / AUTENTICACIÓN
========================= */

export const loginUsuario = async (
  data: LoginDto
): Promise<UsuarioDto> => {
  const payload = {
    email: data.email?.trim(),
    password: data.password
  };

  const response =
    await fetchJson<ApiResponse<{
      token: string;
      usuario: UsuarioDto;
    }>>(
      '/usuarios/login',
      {
        method: 'POST',
        body: JSON.stringify(payload)
      }
    );

  guardarSesion(
    response.datos.token,
    response.datos.usuario
  );

  return response.datos.usuario;
};

export const registrarUsuario = async (
  data: RegistroDto
): Promise<UsuarioDto> => {
  const response =
    await fetchJson<ApiResponse<{
      token: string;
      usuario: UsuarioDto;
    }>>(
      '/usuarios/registro',
      {
        method: 'POST',
        body: JSON.stringify(data)
      }
    );

  guardarSesion(
    response.datos.token,
    response.datos.usuario
  );

  return response.datos.usuario;
};

export const getPerfil = () =>
  fetchJson<ApiResponse<UsuarioDto>>(
    '/usuarios/perfil'
  );
  export const actualizarPerfil = (
  data: {
    name: string;
    email: string;
  }
) =>
  fetchJson<ApiResponse<UsuarioDto>>(
    '/usuarios/perfil',
    {
      method: 'PUT',
      body: JSON.stringify(data)
    }
  );

export const getPerfilCompleto = () =>
  fetchJson<ApiResponse<PerfilCompletoDto>>(
    '/usuarios/perfil-completo'
  );

export const logoutUsuario = async () => {
  try {
    await fetchJson<ApiResponse<null>>(
      '/usuarios/logout',
      {
        method: 'POST'
      }
    );
  } finally {
    limpiarSesion();
  }
};

/* =========================
   ANIMALES / CATÁLOGOS
========================= */

export const getAnimales = () =>
  fetchJson<ApiResponse<AnimalDto[]>>(
    '/animales'
  );

export const getRazas = () =>
  fetchJson<ApiResponse<RazaDto[]>>(
    '/animales/razas'
  );

export const getAnimal = (
  id: number
) =>
  fetchJson<ApiResponse<AnimalDto>>(
    `/animales/${id}`
  );

export const buscarPorArete = (
  arete: string
) =>
  fetchJson<ApiResponse<AnimalDto>>(
    `/animales/arete/${encodeURIComponent(arete)}`
  );

export const buscarAnimalPorArete = buscarPorArete;

export const getHistorialAnimal = (
  id: number
) =>
  fetchJson<ApiResponse<AnimalDto>>(
    `/animales/${id}/historial`
  );

export const crearAnimal = (
  data: CrearAnimalDto
) =>
  fetchJson<ApiResponse<AnimalDto>>(
    '/animales',
    {
      method: 'POST',
      body: JSON.stringify(data)
    }
  );

export const crearAnimalRapido = (
  data: CrearAnimalDto
) =>
  crearAnimal(data);

export const actualizarAnimal = (
  id: number,
  data: Partial<CrearAnimalDto> & {
    estado?: string;
  }
) =>
  fetchJson<ApiResponse<AnimalDto>>(
    `/animales/${id}`,
    {
      method: 'PUT',
      body: JSON.stringify(data)
    }
  );

export const eliminarAnimal = (
  id: number
) =>
  fetchJson<ApiResponse<null>>(
    `/animales/${id}`,
    {
      method: 'DELETE'
    }
  );

/* =========================
   PESAJES
========================= */

export const getPesajes = () =>
  fetchJson<ApiResponse<PesajeDto[]>>(
    '/pesajes'
  );

export const getPesaje = (
  id: number
) =>
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
  data: CrearPesajeDto
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
  data: Partial<CrearPesajeDto>
) =>
  fetchJson<ApiResponse<PesajeDto>>(
    `/pesajes/${id}`,
    {
      method: 'PUT',
      body: JSON.stringify(data)
    }
  );

export const eliminarPesaje = (
  id: number
) =>
  fetchJson<ApiResponse<null>>(
    `/pesajes/${id}`,
    {
      method: 'DELETE'
    }
  );

/* =========================
   IA / ESTIMACIÓN DE PESO
========================= */

/**
 * Solo analiza la imagen con IA.
 * No guarda el pesaje todavía.
 * El usuario decide luego si guarda el peso estimado o lo ajusta.
 */
export const estimarPesoPorImagen = (
  imagen: Blob,
  animalId: number
) => {
  const formData = new FormData();

  formData.append(
    'imagen',
    imagen,
    'animal.jpg'
  );

  formData.append(
    'animal_id',
    String(animalId)
  );

  return fetchJson<ApiResponse<EstimacionPesoDto>>(
    '/pesajes/estimar-peso',
    {
      method: 'POST',
      body: formData
    }
  );
};
/**
 * Guarda el pesaje confirmado por el usuario y guarda la imagen ligada al pesaje.
 * Esta función necesita que en Laravel exista:
 * POST /api/pesajes/confirmar-ia
 */
export const confirmarPesajeIA = (
  data: ConfirmarPesajeIAParams
) => {
  const formData = new FormData();

  formData.append(
    'imagen',
    data.imagen,
    'animal.jpg'
  );

  formData.append(
    'animal_id',
    String(data.animal_id)
  );

  formData.append(
    'peso_estimado',
    String(data.peso_estimado)
  );

  if (
    data.peso_real !== undefined &&
    data.peso_real !== null
  ) {
    formData.append(
      'peso_real',
      String(data.peso_real)
    );
  }

  formData.append(
    'fecha',
    data.fecha
  );

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

export function calcularEdad(
  fechaNacimiento: string | null | undefined
): string {
  if (!fechaNacimiento) {
    return '---';
  }

  const nacimiento = new Date(fechaNacimiento);

  if (Number.isNaN(nacimiento.getTime())) {
    return '---';
  }

  const hoy = new Date();

  let meses =
    (hoy.getFullYear() - nacimiento.getFullYear()) * 12 +
    (hoy.getMonth() - nacimiento.getMonth());

  if (hoy.getDate() < nacimiento.getDate()) {
    meses--;
  }

  if (meses < 0) {
    return '---';
  }

  if (meses < 12) {
    return `${meses} mes${meses === 1 ? '' : 'es'}`;
  }

  const años =
    Math.floor(meses / 12);

  const mesesRestantes =
    meses % 12;

  if (mesesRestantes === 0) {
    return `${años} año${años === 1 ? '' : 's'}`;
  }

  return `${años} año${años === 1 ? '' : 's'} ${mesesRestantes} mes${mesesRestantes === 1 ? '' : 'es'}`;
}