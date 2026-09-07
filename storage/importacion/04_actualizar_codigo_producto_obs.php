<?php
/**
 * ACTUALIZAR SOLO CodigoProductoObs - RECEPCIONES EXTERNAS
 * ========================================================
 * Script RÁPIDO que solo actualiza el campo CodigoProductoObs
 * en recepciones_externas_guias.
 *
 * No toca productos ni otros campos de guías.
 * Corre en una sola pasada (sin lotes) porque es liviano.
 * ========================================================
 */

ini_set('memory_limit', '2048M');
ini_set('max_execution_time', 0);
set_time_limit(0);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// ============================================================
//  CONFIGURACIÓN
// ============================================================

define('IMPORT_FILE', __DIR__ . '/datos_importacion.xlsx');
define('LOG_FILE', __DIR__ . '/actualizar_log.txt');

// ============================================================
//  INICIALIZACIÓN
// ============================================================

$logLines  = [];
$startTime = microtime(true);

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

logSection('ACTUALIZAR SOLO CodigoProductoObs');
logLine('Fecha: ' . date('d/m/Y H:i:s'));

if (!file_exists(IMPORT_FILE)) {
    logLine('ERROR FATAL: No se encuentra ' . IMPORT_FILE);
    file_put_contents(LOG_FILE, implode("\n", $logLines));
    exit(1);
}

// ============================================================
//  CONEXIÓN BD
// ============================================================

try {
    $dbHost = '204.93.224.230';
    $dbName = 'lavorope_dblavoro';
    $dbUser = 'lavorope_adm';
    $dbPass = '7Jb4TcRpX120';

    $db = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser, $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ]
    );
    logLine('Conexión a BD: OK');
} catch (Exception $e) {
    logLine('ERROR FATAL: ' . $e->getMessage());
    file_put_contents(LOG_FILE, implode("\n", $logLines));
    exit(1);
}

// ============================================================
//  LEER EXCEL
// ============================================================

logSection('LEYENDO EXCEL');
try {
    $reader = IOFactory::createReaderForFile(IMPORT_FILE);
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load(IMPORT_FILE);
    logLine('Archivo cargado correctamente.');
} catch (Exception $e) {
    logLine('ERROR FATAL: ' . $e->getMessage());
    file_put_contents(LOG_FILE, implode("\n", $logLines));
    exit(1);
}

// Buscar hoja Recepciones_Externas
$sheet = null;
foreach ($spreadsheet->getAllSheets() as $s) {
    if (stripos($s->getTitle(), 'Recepciones_Externas') !== false) {
        $sheet = $s;
        break;
    }
}
if (!$sheet) {
    logLine('ERROR: Hoja Recepciones_Externas no encontrada.');
    file_put_contents(LOG_FILE, implode("\n", $logLines));
    exit(1);
}

$maxRow = $sheet->getHighestDataRow();
$maxCol = $sheet->getHighestDataColumn();

// Leer encabezados (fila 2)
$headers = [];
$colIdx = 1;
while (Coordinate::stringFromColumnIndex($colIdx) <= $maxCol) {
    $cellRef = Coordinate::stringFromColumnIndex($colIdx) . '2';
    $h = trim((string)$sheet->getCell($cellRef)->getValue());
    if ($h !== '') $headers[$colIdx] = $h;
    $colIdx++;
}

logLine('Encabezados: ' . implode(', ', $headers));

// Verificar que exista Cod_Producto_Obs
if (!in_array('Cod_Producto_Obs', $headers)) {
    logLine('ERROR: No se encontró la columna "Cod_Producto_Obs" en el Excel.');
    file_put_contents(LOG_FILE, implode("\n", $logLines));
    exit(1);
}

// ============================================================
//  PRECARGAR GUÍAS EXISTENTES
// ============================================================

logSection('PRECARGANDO GUÍAS');
logLine('Cargando guías existentes desde BD...');
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
logLine(count($guiasCache) . ' guías precargadas.');

// Preparar statement UPDATE (solo CodigoProductoObs)
$stmtUpdate = $db->prepare(
    "UPDATE recepciones_externas_guias
     SET CodigoProductoObs = :cod_prod_obs
     WHERE Id = :id"
);

// ============================================================
//  PROCESAR FILAS
// ============================================================

logSection('ACTUALIZANDO CodigoProductoObs');

$actualizadas   = 0;
$noEncontradas  = 0;
$errores        = 0;
$sinDatos       = 0;
$totalFilas     = 0;

for ($r = 3; $r <= $maxRow; $r++) {
    // Leer NVale, Numero_Guia, Cod_Producto_Obs
    $nvale   = '';
    $numGuia = '';
    $codObs  = '';

    foreach ($headers as $c => $hName) {
        $cellVal = trim((string)$sheet->getCellByColumnAndRow($c, $r)->getValue());
        if ($hName === 'NVale') {
            $nvale = $cellVal;
        } elseif ($hName === 'Numero_Guia') {
            $numGuia = $cellVal;
        } elseif ($hName === 'Cod_Producto_Obs') {
            $codObs = $cellVal;
        }
    }

    // Saltar filas completamente vacías
    if ($nvale === '' && $numGuia === '' && $codObs === '') continue;
    if ($nvale === '') continue;

    $totalFilas++;

    // Si Cod_Producto_Obs está vacío, no hay nada que actualizar
    if ($codObs === '') {
        $sinDatos++;
        continue;
    }

    // Buscar guía por NVale + NumeroGuia
    $key = $nvale . '|' . strtoupper($numGuia);
    if (!isset($guiasCache[$key])) {
        $noEncontradas++;
        continue;
    }

    $guiaId = $guiasCache[$key];

    try {
        $stmtUpdate->execute([
            ':id' => $guiaId,
            ':cod_prod_obs' => $codObs
        ]);
        if ($stmtUpdate->rowCount() > 0) {
            $actualizadas++;
        }
    } catch (Exception $e) {
        logLine("  [Fila {$r}] ERROR: " . $e->getMessage());
        $errores++;
    }

    // Progreso cada 5000 filas
    if ($totalFilas % 5000 === 0) {
        logLine("  Progreso: {$totalFilas} filas leídas — {$actualizadas} actualizadas...");
    }
}

// ============================================================
//  RESUMEN
// ============================================================

$elapsed = round(microtime(true) - $startTime, 2);

logSection('RESUMEN');
logLine("Total filas leídas                : {$totalFilas}");
logLine("Guías actualizadas (CodigoProductoObs): {$actualizadas}");
logLine("Filas sin Cod_Producto_Obs (vacío) : {$sinDatos}");
logLine("Guías no encontradas en BD         : {$noEncontradas}");
logLine("Errores                            : {$errores}");
logLine("Tiempo                             : {$elapsed} segundos");

file_put_contents(LOG_FILE, implode("\n", $logLines) . "\n");
echo "\n";
