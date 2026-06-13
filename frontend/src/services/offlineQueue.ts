/**
 * Cola offline de capturas de pesaje.
 *
 * Cuando el usuario está en el potrero sin señal, la foto y los datos
 * del animal se guardan localmente en IndexedDB. Al recuperar conexión,
 * el usuario puede analizar las capturas pendientes desde PesajeVivoPage.
 *
 * Se usa IndexedDB (no localStorage) porque las fotos son Blobs de varios
 * MB y localStorage tiene un límite de ~5 MB y solo acepta strings.
 */

const DB_NAME    = 'bovweight-offline';
const DB_VERSION = 1;
const STORE      = 'capturas';

export interface CapturaPendiente {
  id: string;            // uuid simple
  animalId: number;
  animalNombre: string;  // para mostrar en la lista sin consultar la API
  fecha: string;         // yyyy-mm-dd del día en que se tomó la foto
  creadoEn: number;      // timestamp para ordenar
  imagen: Blob;
}

function abrirDB(): Promise<IDBDatabase> {
  return new Promise((resolve, reject) => {
    const req = indexedDB.open(DB_NAME, DB_VERSION);

    req.onupgradeneeded = () => {
      const db = req.result;
      if (!db.objectStoreNames.contains(STORE)) {
        db.createObjectStore(STORE, { keyPath: 'id' });
      }
    };

    req.onsuccess = () => resolve(req.result);
    req.onerror   = () => reject(req.error);
  });
}

function tx<T>(
  modo: IDBTransactionMode,
  operacion: (store: IDBObjectStore) => IDBRequest<T>
): Promise<T> {
  return abrirDB().then(
    (db) =>
      new Promise<T>((resolve, reject) => {
        const transaccion = db.transaction(STORE, modo);
        const req = operacion(transaccion.objectStore(STORE));
        req.onsuccess = () => resolve(req.result);
        req.onerror   = () => reject(req.error);
        transaccion.oncomplete = () => db.close();
      })
  );
}

export function guardarCaptura(captura: CapturaPendiente): Promise<IDBValidKey> {
  return tx('readwrite', (store) => store.put(captura));
}

export async function listarCapturas(): Promise<CapturaPendiente[]> {
  const capturas = await tx<CapturaPendiente[]>('readonly', (store) => store.getAll());
  return capturas.sort((a, b) => a.creadoEn - b.creadoEn);
}

export function eliminarCaptura(id: string): Promise<undefined> {
  return tx('readwrite', (store) => store.delete(id));
}

export function generarIdCaptura(): string {
  return `cap-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
}
