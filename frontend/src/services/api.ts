export const API_BASE = '/api';

export interface AnimalDto {
  id: number;
  numero_arete: string;
  nombre: string;
  raza_id: number | null;
  fecha_nacimiento: string | null;
  estado: string;
  finca_id: number | null;
}

export interface PesajeDto {
  id: number;
  animal_id: number;
  peso_estimado: number | string;
  peso_real: number | string | null;
  fecha: string;
  fuente_id: number | null;
  animal?: AnimalDto;
}

export interface PesajesResponse {
  exito: boolean;
  datos: PesajeDto[];
}

async function fetchJson<T>(path: string): Promise<T> {
  const response = await fetch(`${API_BASE}${path}`, {
    headers: {
      Accept: 'application/json'
    }
  });

  if (!response.ok) {
    throw new Error(`API error ${response.status}`);
  }

  return response.json() as Promise<T>;
}

export function getAnimales(): Promise<AnimalDto[]> {
  return fetchJson<AnimalDto[]>('/animales');
}

export function getPesajes(): Promise<PesajesResponse> {
  return fetchJson<PesajesResponse>('/pesajes');
}
