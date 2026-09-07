<?php

/**
 * Utilidades compartidas para exportar e importar datos en formato CSV.
 * Usado por los endpoints de contactos, leads, oportunidades, productos y facturas.
 */

/**
 * Envía un CSV al navegador como descarga y termina la ejecución.
 *
 * @param string $nombreArchivo Nombre sugerido del archivo (sin ruta)
 * @param array  $encabezados   Nombres de columnas (primera fila)
 * @param array  $filas         Array de arrays con los valores de cada fila
 */
function csvDescargar(string $nombreArchivo, array $encabezados, array $filas): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    // BOM UTF-8 para que Excel detecte correctamente los acentos
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $encabezados, ';');
    foreach ($filas as $fila) {
        fputcsv($out, $fila, ';');
    }
    fclose($out);
    exit;
}

/**
 * Lee un archivo CSV subido por el campo indicado y lo transforma en un
 * array de filas asociativas (clave = nombre de columna del encabezado).
 * Soporta CSV separado por ";" o ",".
 *
 * @throws RuntimeException si no hay archivo o no se puede leer
 */
function csvLeerSubida(string $campo = 'archivo'): array
{
    if (!isset($_FILES[$campo]) || $_FILES[$campo]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se ha recibido ningún archivo CSV válido');
    }

    $ruta = $_FILES[$campo]['tmp_name'];
    $h = fopen($ruta, 'r');
    if ($h === false) {
        throw new RuntimeException('No se pudo leer el archivo subido');
    }

    $filas = [];
    $primeraLinea = fgets($h);
    if ($primeraLinea === false) {
        fclose($h);
        return [];
    }
    // Quitar BOM UTF-8 si existe
    $primeraLinea = preg_replace('/^\xEF\xBB\xBF/', '', $primeraLinea);
    $delimitador = substr_count($primeraLinea, ';') >= substr_count($primeraLinea, ',') ? ';' : ',';
    $encabezados = array_map(fn($c) => trim((string) $c), str_getcsv($primeraLinea, $delimitador));

    while (($datos = fgetcsv($h, 0, $delimitador)) !== false) {
        if (count($datos) === 1 && trim((string) ($datos[0] ?? '')) === '') {
            continue; // línea vacía
        }
        $fila = [];
        foreach ($encabezados as $i => $col) {
            if ($col === '') continue;
            $fila[$col] = isset($datos[$i]) ? trim((string) $datos[$i]) : '';
        }
        $filas[] = $fila;
    }
    fclose($h);

    if (count($filas) > 2000) {
        throw new RuntimeException('El archivo supera el límite de 2000 filas por importación');
    }

    return $filas;
}

/**
 * Convierte un valor CSV en booleano flexible (1/0, si/no, true/false, activo/inactivo).
 */
function csvBool(?string $v, bool $default = true): bool
{
    if ($v === null || trim($v) === '') return $default;
    $v = strtolower(trim($v)); // ASCII-safe; sin mbstring en la imagen Docker
    $valoresVerdaderos = ['1', 'si', 'sí', 'sÍ', 'true', 'yes', 'activo'];
    return in_array($v, $valoresVerdaderos, true);
}

/**
 * Convierte un valor CSV en float, aceptando coma decimal.
 */
function csvFloat(?string $v, float $default = 0): float
{
    if ($v === null || trim($v) === '') return $default;
    return (float) str_replace(',', '.', trim($v));
}
