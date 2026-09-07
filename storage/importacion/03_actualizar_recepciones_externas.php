<?php
/**
 * ACTUALIZADOR DE CAMPOS FALTANTES - RECEPCIONES EXTERNAS
 * ========================================================
 * Este script NO inserta nuevos registros.
 * Solo ACTUALIZA (UPDATE) los campos que quedaron pendientes
 * en recepciones_externas_guias y recepciones_externas_productos.
 *
 * ANTES DE EJECUTAR:
 *   1. Asegúrate de haber agregado la columna "Texto_Observaciones_Producto"
 *      en el Excel (hoja Recepciones_Externas) con los textos de observación
 *      a nivel producto (ej: "ADICIONAL 1 UND DEL CODIGO 19003730 GUIA T510-2222222").
 *   2. Colocar el archivo "datos_importacion.xlsx" en esta misma carpeta.
 *   3. Hacer un BACKUP completo de la base de datos.
 *
 * El script genera un log detallado en "actualizar_log.txt"
 * ========================================================
 */

// Aumentar límites de memoria y tiempo para archivos Excel grandes
ini_set('memory_limit', '2048M');
ini_set('max_execution_time', 0);
set_time_limit(0);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlsDate;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// ============================================================
//  CONFIGURACIÓN
// ============================================================

define('IMPORT_USER_ID', 1);
define('IMPORT_IP', '127.0.0.1');
define('IMPORT_FILE', __DIR__ . '/datos_importacion.xlsx');
define('LOG_FILE', __DIR__ . '/actualizar_log.txt');
define('ONLY_SHEET', 'Recepciones_Externas');
define('SKIP_SHEETS', []);
define('BATCH_SIZE', 2500);
define('CHECKPOINT_FILE', __DIR__ . '/checkpoint_actualizar_recepciones_externas.txt');
define('BATCH_START_ROW', 0);

// ============================================================
//  INICIALIZACIÓN
// ============================================================

$logLines   = [];
$startTime  = microtime(true);

function logLine(string $line): void {
    global $logLines;
    $logLines[] = date('[H:i:s] ') . $line;
    echo $line . "\n";
}

function logSection(string $title): void {
    logLine('');
    logLine(str_repeat('=', 70));
    logLine("  $title");
    logLine(str_repeat('=', 70));
}

logSection('INICIO ACTUALIZACIÓN DE CAMPOS FALTANTES');
logLine('Fecha: ' . date('d/m/Y H:i:s'));
logLine('Archivo: ' . IMPORT_FILE);

if (!file_exists(IMPORT_FILE)) {
    logLine('ERROR FATAL: No se encuentra el archivo ' . IMPORT_FILE);
    file_put_contents(LOG_FILE, implode("\n", $logLines));
    exit(1);
}

// ============================================================
//  CONEXIÓN A LA BASE DE DATOS
// ============================================================

try {
    $dbHost = '204.93.224.230';
    $dbName = 'lavorope_dblavoro';
    $dbUser = 'lavorope_adm';
    $dbPass = '7Jb4TcRpX120';

    $db = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            PDO::ATTR_TIMEOUT => 28800,
            PDO::ATTR_PERSISTENT => false
        ]
    );

    @$db->exec("SET SESSION wait_timeout = 28800");
    @$db->exec("SET SESSION interactive_timeout = 28800");
    @$db->exec("SET SESSION net_read_timeout = 3600");
    @$db->exec("SET SESSION net_write_timeout = 3600");
    @$db->exec("SET SESSION sql_mode = REPLACE(REPLACE(@@SESSION.sql_mode, 'NO_ZERO_DATE', ''), 'NO_ZERO_IN_DATE', '')");
    logLine('Conexión a BD: OK');
} catch (Exception $e) {
    logLine('ERROR FATAL: No se pudo conectar a la BD: ' . $e->getMessage());
    file_put_contents(LOG_FILE, implode("\n", $logLines));
    exit(1);
}

/**
 * Verifica que la conexión PDO siga viva; si no, la reconecta.
 */
function ping(PDO &$db): bool {
    try {
        $db->query("SELECT 1");
        return false;
    } catch (PDOException $e) {
        try {
            $db = Database::getInstance()->reconnect();
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->exec("SET SESSION wait_timeout = 28800");
            $db->exec("SET SESSION interactive_timeout = 28800");
            $db->exec("SET SESSION net_read_timeout = 3600");
            $db->exec("SET SESSION net_write_timeout = 3600");
            $db->exec("SET SESSION sql_mode = REPLACE(REPLACE(@@SESSION.sql_mode, 'NO_ZERO_DATE', ''), 'NO_ZERO_IN_DATE', '')");
            logLine('  [PING] Reconexión exitosa.');
            return true;
        } catch (Exception $e2) {
            logLine('ERROR FATAL: No se pudo reconectar a la BD: ' . $e2->getMessage());
            throw $e2;
        }
    }
}

// ============================================================
//  UTILIDADES DE PARSEO DE CELDAS
// ============================================================

function cellStr($cell): string {
    if ($cell === null) return '';
    $val = $cell->getValue();
    if ($val === null) return '';
    return trim((string)$val);
}

function cellNum($cell): float {
    $v = cellStr($cell);
    return is_numeric($v) ? (float)$v : 0;
}

function cellDate($cell): ?string {
    if ($cell === null) return null;
    $val = $cell->getValue();
    if ($val === null || trim((string)$val) === '') return null;

    if (is_numeric($val) && $val > 1) {
        try {
            $dateObj = XlsDate::excelToDateTimeObject($val);
            return $dateObj->format('Y-m-d');
        } catch (Exception $e) { /* continúa */ }
    }

    $str = trim((string)$val);

    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $str, $m)) {
        return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    }
    if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $str, $m)) {
        return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    }
    if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $str, $m)) {
        return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
    }

    return null;
}

function cellTime($cell): ?string {
    if ($cell === null) return null;
    $val = $cell->getValue();
    if ($val === null) return null;

    if ($val instanceof DateTimeInterface) {
        return $val->format('H:i:s');
    }

    $strVal = trim((string)$val);
    if ($strVal === '') return null;

    if (is_numeric($val) && (float)$val >= 0 && (float)$val < 1) {
        $seconds = round((float)$val * 86400);
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }
    if (is_numeric($val) && (float)$val == 0) {
        return '00:00:00';
    }
    if (is_numeric($val) && (float)$val > 1) {
        $frac = (float)$val - floor((float)$val);
        if ($frac > 0) {
            $seconds = round($frac * 86400);
            $h = floor($seconds / 3600);
            $m = floor(($seconds % 3600) / 60);
            $s = $seconds % 60;
            return sprintf('%02d:%02d:%02d', $h, $m, $s);
        }
        return '00:00:00';
    }

    $str = str_replace('.', ':', $strVal);

    if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?/', $str, $m)) {
        return sprintf('%02d:%02d:%02d', $m[1], $m[2], isset($m[3]) ? $m[3] : 0);
    }

    return null;
}

// ============================================================
//  LECTURA DEL EXCEL
// ============================================================

logSection('LEYENDO ARCHIVO EXCEL');
try {
    $reader = IOFactory::createReaderForFile(IMPORT_FILE);
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load(IMPORT_FILE);
    logLine('Archivo cargado correctamente.');
} catch (Exception $e) {
    logLine('ERROR FATAL: No se pudo leer el archivo Excel: ' . $e->getMessage());
    file_put_contents(LOG_FILE, implode("\n", $logLines));
    exit(1);
}

// ============================================================
//  FUNCIÓN PARA LEER FILAS DE UNA HOJA
// ============================================================

function readSheet(object $spreadsheet, string $sheetName): array {
    $sheet = null;
    foreach ($spreadsheet->getAllSheets() as $s) {
        if (stripos($s->getTitle(), $sheetName) !== false) {
            $sheet = $s;
            break;
        }
    }
    if (!$sheet) {
        logLine("  Hoja '{$sheetName}' no encontrada — se omite.");
        return [];
    }

    $maxRow = $sheet->getHighestDataRow();
    $maxCol = $sheet->getHighestDataColumn();

    // Leer encabezados (fila 2)
    $headers = [];
    $colIdx  = 1;
    while (Coordinate::stringFromColumnIndex($colIdx) <= $maxCol) {
        $cellRef = Coordinate::stringFromColumnIndex($colIdx) . '2';
        $h = trim((string)$sheet->getCell($cellRef)->getValue());
        if ($h !== '') $headers[$colIdx] = $h;
        $colIdx++;
    }

    if (empty($headers)) {
        logLine("  Hoja '{$sheetName}': no se encontraron encabezados en fila 2 — se omite.");
        return [];
    }

    // Mostrar encabezados encontrados
    logLine("  Encabezados encontrados: " . implode(', ', $headers));

    // Leer datos desde fila 3
    $rows = [];
    for ($r = 3; $r <= $maxRow; $r++) {
        $rowData = [];
        foreach ($headers as $c => $hName) {
            $cell = $sheet->getCellByColumnAndRow($c, $r);
            $rowData[$hName] = $cell;
        }
        // Saltar filas completamente vacías
        $allEmpty = true;
        foreach ($rowData as $cell) {
            if (trim((string)$cell->getValue()) !== '') { $allEmpty = false; break; }
        }
        if ($allEmpty) continue;

        $rows[] = ['rowNum' => $r, 'data' => $rowData];
    }

    logLine("  Hoja '{$sheetName}': " . count($rows) . " filas de datos encontradas.");
    return $rows;
}

// ============================================================
//  CONTADORES GLOBALES
// ============================================================

$totalProcesadas    = 0;
$totalActualizadas  = 0;
$totalErrores       = 0;
$totalOmitidas      = 0;
$totalAdvertencias  = 0;

// ============================================================
//  MÓDULO: ACTUALIZAR RECEPCIONES EXTERNAS
// ============================================================

logSection('ACTUALIZANDO: Recepciones Externas (campos faltantes)');

$rows = readSheet($spreadsheet, 'Recepciones_Externas');

if (empty($rows)) {
    logLine('No hay datos que procesar. Saliendo.');
    file_put_contents(LOG_FILE, implode("\n", $logLines));
    exit(0);
}

$guiasActualizadas     = 0;
$productosActualizados = 0;
$errores = 0;

// ---- Sistema de lotes (batches) ----
$batchStart = BATCH_START_ROW;
if (file_exists(CHECKPOINT_FILE)) {
    $saved = (int)trim(file_get_contents(CHECKPOINT_FILE));
    if ($saved > 0) {
        $batchStart = $saved;
        logLine("  Checkpoint encontrado: reanudando desde fila #{$batchStart}.");
    }
}
$batchSize = (BATCH_SIZE > 0) ? BATCH_SIZE : count($rows);
$batchEnd = $batchStart + $batchSize;
if ($batchEnd > count($rows)) {
    $batchEnd = count($rows);
}
$totalEnLote = $batchEnd - $batchStart;
logLine("  Lote: filas {$batchStart} a {$batchEnd} (total en este lote: {$totalEnLote})");
// ---- Fin sistema de lotes ----

// ============================================================
//  PRECARGAR REGISTROS EXISTENTES EN BD
// ============================================================

logLine("  Precargando recepciones externas existentes...");
$valesCache = []; // NVale => Id
$stmtAll = $db->query("SELECT Id, NVale FROM recepciones_externas");
while ($row = $stmtAll->fetch(PDO::FETCH_ASSOC)) {
    $valesCache[$row['NVale']] = (int)$row['Id'];
}
logLine("  " . count($valesCache) . " recepciones precargadas.");

logLine("  Precargando guías existentes...");
$guiasCache = []; // "NVale|NumeroGuia" => Id
$stmtAllG = $db->query(
    "SELECT g.Id, g.RecepcionExternaId, g.NumeroGuia, r.NVale
     FROM recepciones_externas_guias g
     JOIN recepciones_externas r ON r.Id = g.RecepcionExternaId"
);
while ($row = $stmtAllG->fetch(PDO::FETCH_ASSOC)) {
    $key = $row['NVale'] . '|' . $row['NumeroGuia'];
    $guiasCache[$key] = (int)$row['Id'];
}
logLine("  " . count($guiasCache) . " guías precargadas.");

logLine("  Precargando productos existentes...");
$productosCache = []; // "GuiaId|CodigoProducto" => [Id, DescripcionProducto]
$stmtAllP = $db->query(
    "SELECT Id, GuiaId, CodigoProducto, DescripcionProducto
     FROM recepciones_externas_productos"
);
while ($row = $stmtAllP->fetch(PDO::FETCH_ASSOC)) {
    $key = $row['GuiaId'] . '|' . $row['CodigoProducto'];
    // Si hay múltiples productos con el mismo código en la misma guía,
    // almacenamos todos en un array
    if (!isset($productosCache[$key])) {
        $productosCache[$key] = [];
    }
    $productosCache[$key][] = [
        'Id' => (int)$row['Id'],
        'DescripcionProducto' => $row['DescripcionProducto']
    ];
}
logLine("  " . array_sum(array_map('count', $productosCache)) . " productos precargados.");

// ============================================================
//  PREPARAR STATEMENTS DE ACTUALIZACIÓN
// ============================================================

$prepareStatements = function(PDO $db) use (&$stmtUpdateGuia, &$stmtUpdateProducto) {
    $stmtUpdateGuia = $db->prepare(
        "UPDATE recepciones_externas_guias
         SET Observacion = :obs,
             CodigoProductoObs = :cod_prod_obs,
             CantidadObservada = :cant_obs,
             TextoObservaciones = :texto_obs
         WHERE Id = :id"
    );

    $stmtUpdateProducto = $db->prepare(
        "UPDATE recepciones_externas_productos
         SET UnidadMedida = :unidad_medida,
             Observacion = :obs,
             CantidadObservada = :cant_obs,
             TextoObservaciones = :texto_obs
         WHERE Id = :id"
    );
};

$prepareStatements($db);

$startTimeLote = time();

// Iterar SOLO el rango del lote actual
for ($i = $batchStart; $i < $batchEnd; $i++) {
    $rowInfo = $rows[$i];
    $r   = $rowInfo['rowNum'];
    $d   = $rowInfo['data'];
    $ctx = "Fila {$r}";

    // Progreso
    $filasEnLote = $i - $batchStart;
    if ($filasEnLote > 0 && $filasEnLote % 500 === 0) {
        $elapsed = time() - $startTimeLote;
        $rate = $filasEnLote / max($elapsed, 1);
        $remaining = $totalEnLote - $filasEnLote;
        $eta = $remaining / max($rate, 1);
        logLine("  [LOTE] {$filasEnLote}/{$totalEnLote} filas (" . round($rate, 1) . " filas/s) - ETA: {$eta}s.");
    }
    // Verificar conexión MySQL cada 100 filas
    if ($filasEnLote > 0 && $filasEnLote % 100 === 0) {
        if (ping($db)) {
            $prepareStatements($db);
        }
    }

    $nvale    = cellStr($d['NVale'] ?? null);
    $numGuia  = cellStr($d['Numero_Guia'] ?? null);
    $codProd  = cellStr($d['Cod_Producto'] ?? null) ?: 'S/C';
    $descProd = cellStr($d['Descripcion_Producto'] ?? null) ?: 'S/D';

    if ($nvale === '') {
        logLine("  [{$ctx}] OMITIDA: NVale vacío.");
        $totalOmitidas++;
        continue;
    }

    try {
        // ---- Buscar recepción por NVale ----
        if (!isset($valesCache[$nvale])) {
            logLine("  [{$ctx}] [ADVERTENCIA] No se encontró recepción con NVale='{$nvale}' — se omite.");
            $totalAdvertencias++;
            $totalOmitidas++;
            continue;
        }
        $recepcionId = $valesCache[$nvale];

        // ---- Buscar guía por NVale + NumeroGuia ----
        $guiaCacheKey = $nvale . '|' . strtoupper($numGuia);
        if (!isset($guiasCache[$guiaCacheKey])) {
            logLine("  [{$ctx}] [ADVERTENCIA] No se encontró guía '{$numGuia}' para vale '{$nvale}' — se omite.");
            $totalAdvertencias++;
            $totalOmitidas++;
            continue;
        }
        $guiaId = $guiasCache[$guiaCacheKey];

        // ---- Buscar producto por GuiaId + CodigoProducto ----
        $prodCacheKey = $guiaId . '|' . $codProd;
        if (!isset($productosCache[$prodCacheKey])) {
            logLine("  [{$ctx}] [ADVERTENCIA] No se encontró producto código '{$codProd}' en guía '{$numGuia}' — se omite.");
            $totalAdvertencias++;
            $totalOmitidas++;
            continue;
        }

        // Si hay múltiples productos con el mismo código, buscar por descripción
        $productoId = null;
        foreach ($productosCache[$prodCacheKey] as $prod) {
            if ($prod['DescripcionProducto'] === $descProd) {
                $productoId = $prod['Id'];
                break;
            }
        }
        // Si no encontró por descripción exacta, tomar el primero
        if ($productoId === null) {
            $productoId = $productosCache[$prodCacheKey][0]['Id'];
            logLine("  [{$ctx}] [ADVERTENCIA] No se encontró producto con descripción exacta '{$descProd}', se usa el primero disponible.");
            $totalAdvertencias++;
        }

        // ---- Extraer valores del Excel para actualizar ----
        // Guía
        $obsGuiaVal     = cellStr($d['Obs_Guia'] ?? null);
        $obsGuia        = (is_numeric($obsGuiaVal) && $obsGuiaVal !== '') ? (int)$obsGuiaVal : null;
        $codProdObs     = cellStr($d['Cod_Producto_Obs'] ?? null) ?: null;
        $cantObsGuia    = cellNum($d['Cant_Observada_Guia'] ?? null);
        $textoObsGuia   = cellStr($d['Texto_Observaciones'] ?? null) ?: null;

        // Producto
        $unidadMedida   = cellStr($d['Unidad_Medida'] ?? null) ?: null;
        $obsProdVal     = cellStr($d['Obs_Producto'] ?? null);
        $obsProd        = (is_numeric($obsProdVal) && $obsProdVal !== '') ? (int)$obsProdVal : null;
        $cantObsProd    = cellNum($d['Cant_Obs_Producto'] ?? null);
        $textoObsProd   = cellStr($d['Texto_Observaciones_Producto'] ?? null) ?: null;

        $db->beginTransaction();

        // ---- UPDATE Guía ----
        $paramsGuia = [
            ':id' => $guiaId,
            ':obs' => $obsGuia,
            ':cod_prod_obs' => $codProdObs,
            ':cant_obs' => $cantObsGuia > 0 ? $cantObsGuia : null,
            ':texto_obs' => $textoObsGuia,
        ];
        $stmtUpdateGuia->execute($paramsGuia);
        if ($stmtUpdateGuia->rowCount() > 0) {
            $guiasActualizadas++;
        }

        // ---- UPDATE Producto ----
        $paramsProd = [
            ':id' => $productoId,
            ':unidad_medida' => $unidadMedida,
            ':obs' => $obsProd,
            ':cant_obs' => $cantObsProd > 0 ? $cantObsProd : null,
            ':texto_obs' => $textoObsProd,
        ];
        $stmtUpdateProducto->execute($paramsProd);
        if ($stmtUpdateProducto->rowCount() > 0) {
            $productosActualizados++;
        }

        $db->commit();
        $totalProcesadas++;

    } catch (Exception $e) {
        try {
            $db->rollBack();
        } catch (Exception $rollbackErr) {
            if (ping($db)) {
                $prepareStatements($db);
            }
        }
        logLine("  [{$ctx}] ERROR: " . $e->getMessage());
        $errores++;
        $totalErrores++;
    }
}

// ---- Guardar checkpoint para el siguiente lote ----
$siguienteFila = $batchEnd;
if ($siguienteFila >= count($rows)) {
    if (file_exists(CHECKPOINT_FILE)) {
        unlink(CHECKPOINT_FILE);
    }
    logLine("  ¡LOTE FINAL COMPLETADO! Todas las filas procesadas.");
} else {
    file_put_contents(CHECKPOINT_FILE, (string)$siguienteFila);
    logLine("  Checkpoint guardado: fila #{$siguienteFila}. Vuelve a ejecutar el script para continuar desde aquí.");
}

logLine("  Guías actualizadas: {$guiasActualizadas} | Productos actualizados: {$productosActualizados} | Errores: {$errores}");
$totalActualizadas = $guiasActualizadas + $productosActualizados;

// ---- Resumen parcial del lote ----
$elapsedLote = round(microtime(true) - $startTime, 2);
logLine('');
logLine(str_repeat('-', 50));
logLine("  RESUMEN DEL LOTE (filas {$batchStart} a {$batchEnd})");
logLine(str_repeat('-', 50));
logLine("  Guías actualizadas      : {$guiasActualizadas}");
logLine("  Productos actualizados  : {$productosActualizados}");
logLine("  Errores                 : {$errores}");
logLine("  Advertencias            : {$totalAdvertencias}");
logLine("  Tiempo del lote         : {$elapsedLote} segundos");
logLine(str_repeat('-', 50));
if ($siguienteFila < count($rows)) {
    $pendientes = count($rows) - $siguienteFila;
    logLine("  Filas procesadas        : {$batchEnd}");
    logLine("  Filas pendientes        : {$pendientes}");
    logLine("  Progreso total          : " . round($batchEnd / count($rows) * 100, 1) . "%");
    logLine("  Para continuar, ejecuta el script nuevamente.");
} else {
    logLine("  ¡TODAS LAS FILAS PROCESADAS!");
}
logLine(str_repeat('-', 50));

// Guardar log parcial
file_put_contents(LOG_FILE, implode("\n", $logLines) . "\n");
logLine("  Log parcial guardado en: " . LOG_FILE);

// ============================================================
//  RESUMEN FINAL
// ============================================================

$elapsed = round(microtime(true) - $startTime, 2);

logSection('RESUMEN FINAL');
logLine("Total filas procesadas           : {$totalProcesadas}");
logLine("Total guías actualizadas         : {$guiasActualizadas}");
logLine("Total productos actualizados     : {$productosActualizados}");
logLine("Total filas con errores          : {$totalErrores}");
logLine("Total filas omitidas             : {$totalOmitidas}");
logLine("Total advertencias (no críticas) : {$totalAdvertencias}");
logLine("Tiempo de ejecución              : {$elapsed} segundos");

logLine('');
logLine('Log guardado en: ' . LOG_FILE);

file_put_contents(LOG_FILE, implode("\n", $logLines) . "\n");
echo "\n";
