/**
 * Generación y descarga de archivos CSV compatibles con Excel.
 *
 * Se antepone un BOM (﻿) para que Excel en español interprete UTF-8
 * y muestre correctamente acentos y la ñ. Los campos se escapan según RFC 4180.
 */

function escaparCampo(valor: unknown): string {
  const texto = valor === null || valor === undefined ? '' : String(valor);

  // Si contiene comilla, coma, salto de línea o punto y coma, se entrecomilla
  if (/["\n\r,;]/.test(texto)) {
    return `"${texto.replace(/"/g, '""')}"`;
  }

  return texto;
}

/**
 * Construye el contenido CSV a partir de encabezados y filas.
 */
export function construirCSV(encabezados: string[], filas: unknown[][]): string {
  const lineas = [
    encabezados.map(escaparCampo).join(','),
    ...filas.map(fila => fila.map(escaparCampo).join(',')),
  ];

  return '﻿' + lineas.join('\r\n');
}

/**
 * Construye el CSV y dispara la descarga en el navegador.
 */
export function descargarCSV(
  nombreArchivo: string,
  encabezados: string[],
  filas: unknown[][]
): void {
  const contenido = construirCSV(encabezados, filas);
  const blob = new Blob([contenido], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);

  const enlace = document.createElement('a');
  enlace.href = url;
  enlace.download = nombreArchivo.endsWith('.csv') ? nombreArchivo : `${nombreArchivo}.csv`;
  document.body.appendChild(enlace);
  enlace.click();
  document.body.removeChild(enlace);

  URL.revokeObjectURL(url);
}
