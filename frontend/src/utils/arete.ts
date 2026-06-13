/**
 * Validación del número de arete (DIIO) de SENASA Costa Rica.
 *
 * Acepta un prefijo opcional de hasta 3 letras (p.ej. "CR"), seguido de
 * al menos 3 dígitos, con grupos adicionales separados por guion o barra.
 * Cubre el formato numérico oficial de SENASA y los usados en las fincas
 * (ej. "CR005", "001-2026", "188000123456").
 *
 * Debe mantenerse en sincronía con AnimalController::ARETE_REGEX (backend).
 */
const ARETE_REGEX = /^[A-Za-z]{0,3}[0-9]{3,}([-/][0-9]+)*$/;

export const ARETE_MENSAJE_ERROR =
  'El número de arete no tiene un formato válido de SENASA. ' +
  'Debe contener dígitos (prefijo de país y separadores opcionales), ' +
  'ej: CR005, 001-2026 o 188000123456.';

export function areteEsValido(arete: string): boolean {
  return ARETE_REGEX.test(arete.trim());
}
