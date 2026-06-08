export const API_BASE = 'http://127.0.0.1:8000/api';

// Cuando pruebes en celular físico, cambia temporalmente por la IP de tu PC.
// Ejemplo:
// export const API_BASE = 'http://192.168.1.15:8000/api';

// ─────────────────────────────────────────────
// DTOs GENERALES
// ─────────────────────────────────────────────

export interface ApiResponse<T> {
  exito: boolean;
  mensaje: string;
  datos: T;
  errores?: Record<string, string[]> | string[];
}

export interface UsuarioDto {
  id: number;
  name: string;
  email: string;
  rol: 'admin' | 'ganadero' | 'veterinario' | string;
  created_at?: string;
  updated_at?: string;
}

export interface LoginDto {
  usuario: UsuarioDto;
  token: string;
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
  created_at?: string;
  updated_at?: string;
}

export interface FincaDto {
  id: number;
  nombre: string;
  ubicacion: string | null;
  user_id: number;
  created_at?: string;
  updated_at?: string;
}

export interface AnimalDto {
  id: number;
  numero_arete: string;
  nombre: string | null;

  raza_id: number | null;
  raza?: RazaDto | null;

  fecha_nacimiento: string | null;
  estado: string;

  finca_id: number | null;
  finca?: FincaDto | null;

  created_at?: string;
  updated_at?: string;
}

export interface CrearAnimalDto {
  numero_arete: string;
  nombre: string;
  raza_id: number;
  nombre_raza: string;
  fecha_nacimiento: string;
  finca_id: number;
}

export interface PesajeDto {
  id: number;
  animal_id: number;

  peso_estimado: number | string;
  peso_real: number | string | null;

  fecha: string;
  fuente_id: number | null;

  fuente?: FuentePesajeDto | null;
  animal?: AnimalDto | null;

  created_at?: string;
  updated_at?: string;
}

export interface CrearPesajeDto {
  animal_id: number;
  peso_estimado: number | string;
  peso_real?: number | string | null;
  fecha: string;
  fuente_id?: number | null;
}

export interface ReporteDto {
  id: number;
  user_id: number;
  tipo: string;
  archivo_url: string | null;
  fecha: string;
  created_at?: string;
  updated_at?: string;
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
  condicion_corporal?: number;
  alzada_estimada?: number;
  mensaje?: string;
}

// ─────────────────────────────────────────────
// SESIÓN LOCAL
// ─────────────────────────────────────────────

export function getToken(): string | null {
  return localStorage.getItem('token');
}

export function getUsuarioLocal(): UsuarioDto | null {
  const rawUser = localStorage.getItem('user');

  if (!rawUser) {
    return null;
  }

  try {
    return JSON.parse(rawUser) as UsuarioDto;
  } catch {
    localStorage.removeItem('user');
    return null;
  }
}

export function guardarSesion(datos: LoginDto): void {
  localStorage.setItem('token', datos.token);
  localStorage.setItem('user', JSON.stringify(datos.usuario));
}

export function limpiarSesion(): void {
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  localStorage.removeItem('usuario');
}

// ─────────────────────────────────────────────
// FETCH BASE
// ─────────────────────────────────────────────

async function fetchJson<T>(
  path: string,
  options: RequestInit = {}
): Promise<T> {
  const token = getToken();

  const response = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',

      ...(token
        ? { Authorization: `Bearer ${token}` }
        : {}),

      ...options.headers
    }
  });

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
      `Error ${response.status}`;

    throw new Error(mensaje);
  }

  return data as T;
}

// ─────────────────────────────────────────────
// USUARIOS
// ─────────────────────────────────────────────

export const loginUsuario = (
  email: string,
  password: string
) =>
  fetchJson<ApiResponse<LoginDto>>(
    '/usuarios/login',
    {
      method: 'POST',
      body: JSON.stringify({
        email,
        password
      })
    }
  );

export const registrarUsuario = (
  data: {
    name: string;
    email: string;
    password: string;
    rol: string;
  }
) =>
  fetchJson<ApiResponse<LoginDto>>(
    '/usuarios/registro',
    {
      method: 'POST',
      body: JSON.stringify(data)
    }
  );

export const getPerfil = () =>
  fetchJson<ApiResponse<UsuarioDto>>(
    '/usuarios/perfil'
  );

export const getPerfilCompleto = () =>
  fetchJson<ApiResponse<PerfilCompletoDto>>(
    '/usuarios/perfil-completo'
  );

export const logoutUsuario = () =>
  fetchJson<ApiResponse<void>>(
    '/usuarios/logout',
    {
      method: 'POST'
    }
  );

// ─────────────────────────────────────────────
// ANIMALES
// ─────────────────────────────────────────────

export const getAnimales = () =>
  fetchJson<ApiResponse<AnimalDto[]>>(
    '/animales'
  );

export const getAnimal = (id: number) =>
  fetchJson<ApiResponse<AnimalDto>>(
    `/animales/${id}`
  );

export const buscarPorArete = (arete: string) =>
  fetchJson<ApiResponse<AnimalDto>>(
    `/animales/arete/${encodeURIComponent(arete)}`
  );

export const getHistorialAnimal = (id: number) =>
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
  fetchJson<ApiResponse<PesajeDto[]>>(
    '/pesajes'
  );

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
// IA / ESTIMACIÓN DE PESO
// ─────────────────────────────────────────────

export const estimarPesoPorImagen = async (
  imagen: File | Blob
): Promise<ApiResponse<EstimacionPesoDto>> => {
  const token = getToken();

  const formData = new FormData();

  formData.append(
    'imagen',
    imagen,
    'animal.jpg'
  );

  const response = await fetch(
    `${API_BASE}/pesajes/estimar-peso`,
    {
      method: 'POST',
      headers: {
        Accept: 'application/json',

        ...(token
          ? { Authorization: `Bearer ${token}` }
          : {})
      },
      body: formData
    }
  );

  let data: any = null;

  try {
    data = await response.json();
  } catch {
    data = null;
  }

  if (!response.ok) {
    throw new Error(
      data?.mensaje ||
      data?.message ||
      data?.error ||
      `Error ${response.status}`
    );
  }

  return data as ApiResponse<EstimacionPesoDto>;
};

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
  data: {
    nombre: string;
    ubicacion: string;
    user_id: number;
  }
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

  let edad =
    hoy.getFullYear() - nacimiento.getFullYear();

  const mes =
    hoy.getMonth() - nacimiento.getMonth();

  if (
    mes < 0 ||
    (
      mes === 0 &&
      hoy.getDate() < nacimiento.getDate()
    )
  ) {
    edad--;
  }

  return `${edad} año(s)`;
}