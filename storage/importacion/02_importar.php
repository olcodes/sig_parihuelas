<?php
/**
 * IMPORTADOR DE DATOS HISTÓRICOS - CARGA MASIVA
 * ================================================
 * Ejecutar DESPUÉS de llenar la plantilla Excel:
 *   php 02_importar.php
 *
 * ANTES DE EJECUTAR:
 *   1. Colocar el archivo "datos_importacion.xlsx" en esta misma carpeta.
 *   2. Hacer un BACKUP completo de la base de datos.
 *   3. Revisar las configuraciones al inicio de este script.
 *
 * El script genera un log detallado en "importacion_log.txt"
 * ================================================
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

// Usuario que se registrará como "creado_por" en todos los registros
define('IMPORT_USER_ID', 1);       // Cambiar al ID del admin en la BD

// IP que se registrará en ip_creacion
define('IMPORT_IP', '127.0.0.1');

// Si hay un texto que no coincide con ninguna fila maestra (ej: Turno "MEDIODIA")
// true  = lo crea automáticamente en la tabla maestra
// false = lo deja como NULL y lo reporta en el log
define('AUTO_CREATE_MASTERS', true);

// Valor por defecto para Hora cuando la celda está vacía
// La BD tiene la columna Hora como NOT NULL, así que usamos este valor
define('DEFAULT_HORA', '00:00:00');

// Valor por defecto para Fecha cuando la celda está vacía
// La BD tiene la columna Fecha como NOT NULL, así que usamos este valor
define('DEFAULT_FECHA', '0000-00-00');

// Archivo de datos a importar
define('IMPORT_FILE', __DIR__ . '/datos_importacion.xlsx');

// Archivo de log de resultados
define('LOG_FILE', __DIR__ . '/importacion_log.txt');

// Hoja que se procesará (null = todas)
// Opciones: 'Despachos_Internos', 'Recepciones_Internas', 'Despachos_Externos', 'Recepciones_Externas', null
define('ONLY_SHEET', 'Recepciones_Externas'); // Solo procesar Recepciones Externas

// Hojas que se SALTARÁN (no se procesarán)
define('SKIP_SHEETS', []);

// ============================================================
//  CONFIGURACIÓN DE LOTES (BATCHES) - Para Recepciones Externas
//  Como son ~1874 filas, procesamos en lotes de 500 para evitar timeout
//  Después de cada lote, vuelve a ejecutar el script y continúa automáticamente.
// ============================================================

// Cantidad de filas a procesar por lote.
// 0 = procesar todas (sin lotes).
define('BATCH_SIZE', 150);

// Archivo de checkpoint: guarda la última fila procesada para reanudar
define('CHECKPOINT_FILE', __DIR__ . '/checkpoint_recepciones_externas.txt');

// Fila desde la cual empezar (0 = desde el inicio, o usa checkpoint automático)
// Si el archivo checkpoint existe, se usa ese valor en lugar de este.
// Para forzar un inicio específico, borra el checkpoint y pon el número aquí.
define('BATCH_START_ROW', 0);

// ============================================================
//  INICIALIZACIÓN
// ============================================================

$logLines   = [];
$startTime  = microtime(true);

function logLine(string $line): void {
    global $logLines;
    $logLines[] = date('[H:i:s] ') . $line;
    echo ($line) . "\n";
}

function logSection(string $title): void {
    logLine('');
    logLine(str_repeat('=', 70));
    logLine("  $title");
    logLine(str_repeat('=', 70));
}

logSection('INICIO IMPORTACIÓN CARGA MASIVA');
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
    // Usar el singleton de Database y obtener la conexión PDO
    $db = Database::getInstance()->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // SOBRESCRIBIR timeout de la conexión (el singleton usa 30s, necesitamos más)
    // Forzar una nueva conexión PDO con timeouts altos
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
            PDO::ATTR_TIMEOUT => 28800,      // 8 horas para timeout de conexión
            PDO::ATTR_PERSISTENT => false
        ]
    );
    
    // Aumentar timeouts de MySQL para evitar "MySQL server has gone away"
    @$db->exec("SET SESSION wait_timeout = 28800");
    @$db->exec("SET SESSION interactive_timeout = 28800");
    @$db->exec("SET SESSION net_read_timeout = 3600");
    @$db->exec("SET SESSION net_write_timeout = 3600");
    // Permitir '0000-00-00' como fecha (desactivar NO_ZERO_DATE en esta sesión)
    @$db->exec("SET SESSION sql_mode = REPLACE(REPLACE(@@SESSION.sql_mode, 'NO_ZERO_DATE', ''), 'NO_ZERO_IN_DATE', '')");
    logLine('Conexión a BD: OK');
} catch (Exception $e) {
    logLine('ERROR FATAL: No se pudo conectar a la BD: ' . $e->getMessage());
    file_put_contents(LOG_FILE, implode("\n", $logLines));
    exit(1);
}

/**
 * Verifica que la conexión PDO siga viva; si no, la reconecta.
 * Ayuda a evitar "MySQL server has gone away" en ejecuciones largas.
 */
function ping(PDO &$db): bool {
    try {
        $db->query("SELECT 1");
        return false; // No hubo reconexión
    } catch (PDOException $e) {
        // Reconectar - Database::reconnect() crea una NUEVA conexión PDO
        // (el singleton retorna la misma conexión muerta, por eso necesitamos reconnect())
        try {
            $db = Database::getInstance()->reconnect();
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->exec("SET SESSION wait_timeout = 28800");
            $db->exec("SET SESSION interactive_timeout = 28800");
            $db->exec("SET SESSION net_read_timeout = 3600");
            $db->exec("SET SESSION net_write_timeout = 3600");
            $db->exec("SET SESSION sql_mode = REPLACE(REPLACE(@@SESSION.sql_mode, 'NO_ZERO_DATE', ''), 'NO_ZERO_IN_DATE', '')");
            logLine('  [PING] Reconexión exitosa.');
            return true; // Hubo reconexión
        } catch (Exception $e2) {
            logLine('ERROR FATAL: No se pudo reconectar a la BD: ' . $e2->getMessage());
            throw $e2;
        }
    }
}

// ============================================================
//  CACHÉ DE TABLAS MAESTRAS
//  Para no hacer SELECT en cada fila, pre-cargamos las maestras
//  en memoria con clave normalizada (lowercase sin espacios extra)
// ============================================================

function normalizeKey(string $value): string {
    return mb_strtolower(trim($value));
}

function loadMaster(PDO $db, string $table, string $nameCol, string $idCol = 'Id'): array {
    $stmt = $db->query("SELECT {$idCol}, {$nameCol} FROM {$table}");
    $map  = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $map[normalizeKey((string)$row[$nameCol])] = (int)$row[$idCol];
    }
    return $map;
}

logSection('CARGANDO TABLAS MAESTRAS');

$masterTurnos        = loadMaster($db, 'turnos',          'Turno');
$masterAreas         = loadMaster($db, 'areas',           'Area');
$masterSubareas      = loadMaster($db, 'subareas',        'Subarea');
$masterResponsables  = loadMaster($db, 'responsables',    'NombresApellidos');
$masterRecepcionistas= loadMaster($db, 'recepcionistas',  'NombresApellidos');
$masterMedioTransp   = loadMaster($db, 'medio_transporte','MedioTransporte');
$masterOrigenes      = loadMaster($db, 'origen',          'Origen');
$masterDestinos      = loadMaster($db, 'clientesexternos','Empresa');
$masterTransportistas= loadMaster($db, 'transportistas',  'Empresa');
$masterChoferes      = loadMaster($db, 'choferes',        'ApellidosNombres');

logLine('Turnos cargados: '          . count($masterTurnos));
logLine('Áreas cargadas: '           . count($masterAreas));
logLine('Subáreas cargadas: '        . count($masterSubareas));
logLine('Responsables cargados: '    . count($masterResponsables));
logLine('Recepcionistas cargados: '  . count($masterRecepcionistas));
logLine('Medios Transporte cargados: '. count($masterMedioTransp));
logLine('Orígenes cargados: '        . count($masterOrigenes));
logLine('Destinos cargados: '        . count($masterDestinos));
logLine('Transportistas cargados: '  . count($masterTransportistas));
logLine('Choferes cargados: '        . count($masterChoferes));

// ============================================================
//  FUNCIONES DE RESOLUCIÓN DE CLAVES FORÁNEAS
// ============================================================

$missingMasters = [];   // Acumulador de textos no encontrados (para el log final)

/**
 * Busca el ID de un valor en una tabla maestra.
 * Si AUTO_CREATE_MASTERS=true y no existe, lo crea y devuelve el nuevo ID.
 * Si no se puede resolver, devuelve null y registra la advertencia.
 */
function resolveFK(
    PDO    $db,
    array  &$cache,
    string $table,
    string $nameCol,
    string $rawValue,
    string $context = '',
    array  $extraData = []
): ?int {
    global $missingMasters;

    $rawValue = trim((string)$rawValue);
    if ($rawValue === '' || $rawValue === null) {
        return null;
    }

    $key = normalizeKey($rawValue);
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    if (AUTO_CREATE_MASTERS) {
        // Crear el registro en la tabla maestra
        try {
            $cols   = [$nameCol => $rawValue];
            $cols   = array_merge($cols, $extraData);
            $keys   = array_keys($cols);
            $sql    = "INSERT INTO {$table} (" . implode(', ', $keys) . ") VALUES (:" . implode(', :', $keys) . ")";
            $stmt   = $db->prepare($sql);
            $stmt->execute($cols);
            $newId  = (int)$db->lastInsertId();
            $cache[$key] = $newId;
            logLine("  [CREADO] Maestra {$table}.{$nameCol} = '{$rawValue}' (ID:{$newId}) | Contexto: {$context}");
            return $newId;
        } catch (Exception $e) {
            logLine("  [ERROR] No se pudo crear en {$table}.{$nameCol} = '{$rawValue}': " . $e->getMessage());
            return null;
        }
    }

    // Sin auto-crear: registrar y devolver null
    $missingMasters[] = "Tabla: {$table} | Columna: {$nameCol} | Valor: '{$rawValue}' | {$context}";
    return null;
}

/**
 * Resuelve el Id de una subárea, pasando también el IdArea asociado.
 * Busca el área en el caché de áreas para obtener su ID.
 */
function resolveSubareaConArea(PDO $db, string $subareaVal, string $areaVal, string $ctx): ?int {
    global $masterSubareas, $masterAreas;

    $subareaVal = trim($subareaVal);
    if ($subareaVal === '') return null;

    $key = normalizeKey($subareaVal);
    if (isset($masterSubareas[$key])) {
        return $masterSubareas[$key];
    }

    // Obtener IdArea desde el caché de áreas
    $areaKey = normalizeKey($areaVal);
    $idArea = isset($masterAreas[$areaKey]) ? $masterAreas[$areaKey] : null;

    if ($idArea === null) {
        logLine("  [ERROR] No se puede crear subárea '{$subareaVal}' porque no se encontró el área '{$areaVal}' | Contexto: {$ctx}");
        return null;
    }

    return resolveFK($db, $masterSubareas, 'subareas', 'Subarea', $subareaVal, $ctx, ['IdArea' => $idArea]);
}

// Helpers por tipo de maestra (simplifican la llamada):
function resolveTurno(PDO $db, string $val, string $ctx): ?int {
    global $masterTurnos;
    return resolveFK($db, $masterTurnos, 'turnos', 'Turno', $val, $ctx);
}
function resolveArea(PDO $db, string $val, string $ctx): ?int {
    global $masterAreas;
    return resolveFK($db, $masterAreas, 'areas', 'Area', $val, $ctx);
}
function resolveSubarea(PDO $db, string $val, string $ctx): ?int {
    global $masterSubareas;
    return resolveFK($db, $masterSubareas, 'subareas', 'Subarea', $val, $ctx);
}
function resolveResponsable(PDO $db, string $val, string $ctx): ?int {
    global $masterResponsables;
    if (trim($val) === '') return null;
    // Intentar extraer apellido paterno y nombres del nombre completo
    $parts = explode(' ', trim($val));
    $extra = count($parts) >= 2
        ? ['ApellidoPaterno' => $parts[0], 'Nombres' => implode(' ', array_slice($parts, 1))]
        : ['ApellidoPaterno' => $val,      'Nombres' => ''];
    return resolveFK($db, $masterResponsables, 'responsables', 'NombresApellidos', $val, $ctx, $extra);
}
function resolveRecepcionista(PDO $db, string $val, string $ctx): ?int {
    global $masterRecepcionistas;
    if (trim($val) === '') return null;
    $parts = explode(' ', trim($val));
    $extra = count($parts) >= 2
        ? ['ApellidoPaterno' => $parts[0], 'Nombres' => implode(' ', array_slice($parts, 1))]
        : ['ApellidoPaterno' => $val,      'Nombres' => ''];
    return resolveFK($db, $masterRecepcionistas, 'recepcionistas', 'NombresApellidos', $val, $ctx, $extra);
}
function resolveMedioTransporte(PDO $db, string $val, string $ctx): ?int {
    global $masterMedioTransp;
    return resolveFK($db, $masterMedioTransp, 'medio_transporte', 'MedioTransporte', $val, $ctx);
}
function resolveOrigen(PDO $db, string $val, string $ctx): ?int {
    global $masterOrigenes;
    return resolveFK($db, $masterOrigenes, 'origen', 'Origen', $val, $ctx);
}
function resolveDestino(PDO $db, string $val, string $ctx): ?int {
    global $masterDestinos;
    return resolveFK($db, $masterDestinos, 'clientesexternos', 'Empresa', $val, $ctx);
}
function resolveTransportista(PDO $db, string $val, string $ctx): ?int {
    global $masterTransportistas;
    if (trim($val) === '') return null;
    return resolveFK($db, $masterTransportistas, 'transportistas', 'Empresa', $val, $ctx, ['RUC' => '']);
}
/**
 * Separa un nombre completo "APELLIDO_PATERNO APELLIDO_MATERNO NOMBRES"
 * en partes. Si tiene 2 palabras: asume "ApellidoPaterno Nombres".
 * Si tiene 3+: "ApellidoPaterno ApellidoMaterno Nombres..." (todo lo sobrante como Nombres).
 */
function splitFullName(string $full): array {
    $parts = array_values(array_filter(explode(' ', trim($full)), fn($p) => $p !== ''));
    $paterno = $parts[0] ?? '';
    $materno = $parts[1] ?? '';
    $nombres = implode(' ', array_slice($parts, 2)) ?: ($materno ?: $paterno);
    // Si solo hay 2 palabras: "ApellidoPaterno Nombres" -> materno vacío
    if (count($parts) === 2) {
        $paterno = $parts[0];
        $materno = '';
        $nombres = $parts[1];
    }
    // Si solo hay 1 palabra: todo va a Nombres
    if (count($parts) === 1) {
        $paterno = '';
        $materno = '';
        $nombres = $parts[0];
    }
    return [$paterno, $materno, $nombres];
}

function resolveChofer(PDO $db, string $val, string $ctx): ?int {
    global $masterChoferes;
    if (trim($val) === '') return null;
    // Separar el nombre completo en partes
    [$paterno, $materno, $nombres] = splitFullName($val);
    return resolveFK($db, $masterChoferes, 'choferes', 'ApellidosNombres', $val, $ctx, [
        'DocIdentidad'    => '',
        'Nacionalidad'    => 'PERUANO',
        'ApellidosPaterno' => $paterno,
        'ApellidoMaterno'  => $materno,
        'Nombres'          => $nombres,
        'Brevete'          => ''
    ]);
}
// ============================================================
//  UTILIDADES DE PARSEO DE CELDAS
// ============================================================

function cellStr($cell): string {
    if ($cell === null) return '';
    $val = $cell->getValue();
    if ($val === null) return '';
    // PhpSpreadsheet puede devolver fechas como float (número de serie Excel)
    return trim((string)$val);
}

function cellNum($cell): float {
    $v = cellStr($cell);
    return is_numeric($v) ? (float)$v : 0;
}

/**
 * Parsea fecha desde celda: soporta fecha nativa Excel, DD/MM/YYYY, YYYY-MM-DD, DD-MM-YYYY.
 * Devuelve string 'YYYY-MM-DD' o null.
 */
function cellDate($cell): ?string {
    if ($cell === null) return null;
    $val = $cell->getValue();
    if ($val === null || trim((string)$val) === '') return null;

    // Número de serie de Excel
    if (is_numeric($val) && $val > 1) {
        try {
            $dateObj = XlsDate::excelToDateTimeObject($val);
            return $dateObj->format('Y-m-d');
        } catch (Exception $e) { /* continúa */ }
    }

    $str = trim((string)$val);

    // DD/MM/YYYY
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $str, $m)) {
        return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    }
    // DD-MM-YYYY
    if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $str, $m)) {
        return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    }
    // YYYY-MM-DD
    if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $str, $m)) {
        return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
    }

    return null;
}

/**
 * Parsea hora: HH:MM, HH:MM:SS, HH.MM o número de serie Excel.
 * Devuelve 'HH:MM:SS' o null.
 */
function cellTime($cell): ?string {
    if ($cell === null) return null;
    $val = $cell->getValue();
    if ($val === null) return null;

    // PhpSpreadsheet puede devolver DateTime para celdas con formato de hora
    if ($val instanceof DateTimeInterface) {
        return $val->format('H:i:s');
    }

    $strVal = trim((string)$val);
    if ($strVal === '') return null;

    // Número de serie Excel de hora (valor menor a 1)
    if (is_numeric($val) && (float)$val >= 0 && (float)$val < 1) {
        $seconds = round((float)$val * 86400);
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }
    // Si es número entero (puede pasar cuando hora es 0.0 = medianoche)
    if (is_numeric($val) && (float)$val == 0) {
        return '00:00:00';
    }

    // Número de serie Excel que incluye fecha + hora (valor > 1)
    // Extraer solo la parte fraccionaria (hora)
    if (is_numeric($val) && (float)$val > 1) {
        $frac = (float)$val - floor((float)$val);
        if ($frac > 0) {
            $seconds = round($frac * 86400);
            $h = floor($seconds / 3600);
            $m = floor(($seconds % 3600) / 60);
            $s = $seconds % 60;
            return sprintf('%02d:%02d:%02d', $h, $m, $s);
        }
        // Si la parte fraccionaria es 0, es medianoche
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
    // Solo leer datos (sin formatos ni fórmulas) para ahorrar memoria
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load(IMPORT_FILE);
    logLine('Archivo cargado correctamente.');
} catch (Exception $e) {
    logLine('ERROR FATAL: No se pudo leer el archivo Excel: ' . $e->getMessage());
    file_put_contents(LOG_FILE, implode("\n", $logLines));
    exit(1);
}

// ============================================================
//  CONTADORES GLOBALES
// ============================================================

$totalImportados = 0;
$totalErrores    = 0;
$totalOmitidos   = 0;
$totalAdvertencias = 0;

// ============================================================
//  FUNCIÓN GENÉRICA PARA LEER FILAS DE UNA HOJA
//  Devuelve array de filas con clave = nombre de columna
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
        logLine("  Hoja '{$sheetName}' no encontrada en el archivo — se omite.");
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

    // Leer datos desde fila 4 (fila 3 es el ejemplo que el usuario debe eliminar,
    // pero lo detectamos y saltamos si NVale parece un ejemplo)
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


/**
 * Libera la hoja procesada de la memoria para evitar agotar el límite
 * cuando hay muchas filas en el Excel.
 */
function freeSheetMemory(object $spreadsheet, string $sheetName): void {
    try {
        foreach ($spreadsheet->getAllSheets() as $idx => $s) {
            if (stripos($s->getTitle(), $sheetName) !== false) {
                $s->disconnectCells();
                logLine("  Memoria liberada: hoja '{$sheetName}'");
                break;
            }
        }
    } catch (Exception $e) {
        // Silencioso, no crítico
    }
}

// ============================================================
//  MÓDULO 1: DESPACHOS INTERNOS
// ============================================================

if ((ONLY_SHEET === null || ONLY_SHEET === 'Despachos_Internos') && !in_array('Despachos_Internos', SKIP_SHEETS)) {
    logSection('PROCESANDO: Despachos Internos');

    $rows = readSheet($spreadsheet, 'Despachos_Internos');
    $valesInsertados  = 0;
    $productosInsertados = 0;
    $errores = 0;

    // Precargar todos los vales existentes en BD de una sola vez
    logLine("  Precargando vales existentes en BD...");
    $valesCache = []; // NVale => despachoId
    $stmtAll = $db->query("SELECT Id, NVale FROM despachos_internos");
    while ($row = $stmtAll->fetch(PDO::FETCH_ASSOC)) {
        $valesCache[$row['NVale']] = (int)$row['Id'];
    }
    logLine("  " . count($valesCache) . " vales existentes precargados.");

    // Preparar statements una sola vez (reutilizables)
    $stmtInsertVale = $db->prepare(
        "INSERT INTO despachos_internos
         (NVale, Fecha, Hora, Turno, Area, Subarea, Emisor, Despachador, Recepcionista, Verificador,
          estado, creado_por, creado_en, ip_creacion)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVO', ?, NOW(), ?)"
    );
    $stmtInsertProd = $db->prepare(
        "INSERT INTO despachos_internos_productos
         (DespachoId, CodigoProducto, DescripcionProducto, UnidadMedida, Cantidad, Comentarios)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    $startTime = time();
    $totalRows = count($rows);

    foreach ($rows as $i => $rowInfo) {
        $r   = $rowInfo['rowNum'];
        $d   = $rowInfo['data'];
        $ctx = "Fila {$r}";

        // Log de progreso cada 500 filas
        if ($i > 0 && $i % 500 === 0) {
            $elapsed = time() - $startTime;
            $rate = $i / max($elapsed, 1);
            $remaining = $totalRows - $i;
            $eta = $remaining / max($rate, 1);
            logLine("  [PROGRESO] {$i}/{$totalRows} filas (" . round($rate, 1) . " filas/s) - ETA: {$eta}s.");
            // Verificar que MySQL siga conectado
            ping($db);
        }

        $nvale    = cellStr($d['NVale'] ?? null);
        $codProd  = cellStr($d['Cod_Producto'] ?? null) ?: 'S/C';
        $descProd = cellStr($d['Descripcion_Producto'] ?? null) ?: 'S/D';
        $cantidad = cellNum($d['Cantidad'] ?? null);

        if ($nvale === '') {
            logLine("  [{$ctx}] OMITIDA: NVale vacío.");
            $totalOmitidos++;
            continue;
        }

        try {
            $db->beginTransaction();

            // ¿Ya fue insertado este vale en esta ejecución?
            if (!isset($valesCache[$nvale])) {
                $fecha    = cellDate($d['Fecha'] ?? null);
                $hora     = cellTime($d['Hora'] ?? null) ?: DEFAULT_HORA;
                $turnoId  = resolveTurno($db, cellStr($d['Turno'] ?? null), $ctx);
                $areaId   = resolveArea($db, cellStr($d['Area'] ?? null), $ctx);
                $subareaId= resolveSubareaConArea($db, cellStr($d['Subarea'] ?? null), cellStr($d['Area'] ?? null), $ctx);
                // Emisor es FK a usuarios (no a responsables), usamos el usuario admin de importación
                $emisorId = IMPORT_USER_ID;
                $despId   = resolveResponsable($db, cellStr($d['Despachador'] ?? null), $ctx);
                $recepId  = resolveRecepcionista($db, cellStr($d['Recepcionista'] ?? null), $ctx);
                $verifId  = resolveResponsable($db, cellStr($d['Verificador'] ?? null), $ctx);

                // ADVERTENCIAS (no bloqueantes): datos inconsistentes se registran igual
                // Para columnas NOT NULL (Turno, Area, Subarea) usamos 1 como fallback
                if (!$turnoId) {
                    logLine("  [{$ctx}] [ADVERTENCIA] Turno no encontrado para vale '{$nvale}' — se registra con ID 1 (por defecto).");
                    $totalAdvertencias++;
                    $turnoId = 1;
                }
                if (!$areaId) {
                    logLine("  [{$ctx}] [ADVERTENCIA] Area no encontrada para vale '{$nvale}' — se registra con ID 1 (por defecto).");
                    $totalAdvertencias++;
                    $areaId = 1;
                }
                if (!$subareaId) {
                    logLine("  [{$ctx}] [ADVERTENCIA] Subarea no encontrada para vale '{$nvale}' — se registra con ID 1 (por defecto).");
                    $totalAdvertencias++;
                    $subareaId = 1;
                }
                if (!$despId) {
                    logLine("  [{$ctx}] [ADVERTENCIA] Despachador no encontrado para vale '{$nvale}' — se registra con NULL.");
                    $totalAdvertencias++;
                }
                if (!$recepId) {
                    logLine("  [{$ctx}] [ADVERTENCIA] Recepcionista no encontrado para vale '{$nvale}' — se registra con NULL.");
                    $totalAdvertencias++;
                }
                if (!$verifId) {
                    logLine("  [{$ctx}] [ADVERTENCIA] Verificador no encontrado para vale '{$nvale}' — se registra con NULL.");
                    $totalAdvertencias++;
                }

                if (!$fecha) {
                    logLine("  [{$ctx}] [ADVERTENCIA] Fecha inválida (vacía) para vale '{$nvale}' — se registra con '0000-00-00'.");
                    $totalAdvertencias++;
                    $fecha = DEFAULT_FECHA;
                }

                $stmtInsertVale->execute([
                    $nvale, $fecha, $hora, $turnoId, $areaId, $subareaId,
                    $emisorId, $despId, $recepId, $verifId,
                    IMPORT_USER_ID, IMPORT_IP
                ]);
                $despachoId = (int)$db->lastInsertId();
                $valesCache[$nvale] = $despachoId;
                $valesInsertados++;
            } else {
                $despachoId = $valesCache[$nvale];
            }

            // Insertar producto
            $stmtInsertProd->execute([
                $despachoId,
                $codProd,
                $descProd,
                cellStr($d['Unidad_Medida'] ?? null),
                $cantidad,
                cellStr($d['Comentarios'] ?? null),
            ]);
            $productosInsertados++;
            $db->commit();

        } catch (Exception $e) {
            $db->rollBack();
            logLine("  [{$ctx}] ERROR: " . $e->getMessage());
            $errores++;
            $totalErrores++;
        }
    }

    logLine("  Vales insertados: {$valesInsertados} | Productos: {$productosInsertados} | Errores: {$errores}");
    $totalImportados += $valesInsertados + $productosInsertados;
    freeSheetMemory($spreadsheet, 'Despachos_Internos');
}

// ============================================================
//  MÓDULO 2: RECEPCIONES INTERNAS
// ============================================================

if ((ONLY_SHEET === null || ONLY_SHEET === 'Recepciones_Internas') && !in_array('Recepciones_Internas', SKIP_SHEETS)) {
    logSection('PROCESANDO: Recepciones Internas');

    $rows = readSheet($spreadsheet, 'Recepciones_Internas');
    $valesInsertados  = 0;
    $productosInsertados = 0;
    $errores = 0;

    // Precargar vales existentes
    logLine("  Precargando vales existentes en BD...");
    $valesCache = [];
    $stmtAll = $db->query("SELECT Id, NVale FROM recepciones_internas");
    while ($row = $stmtAll->fetch(PDO::FETCH_ASSOC)) {
        $valesCache[$row['NVale']] = (int)$row['Id'];
    }
    logLine("  " . count($valesCache) . " vales existentes precargados.");

    // Preparar statements
    $stmtInsertVale = $db->prepare(
        "INSERT INTO recepciones_internas
         (NVale, Fecha, Hora, Turno, Area, Subarea, Emisor, Despachador, MedioTransporte,
          Verificador, NLiquidacion, creado_por, creado_en)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
    );
    $stmtInsertProd = $db->prepare(
        "INSERT INTO recepciones_internas_productos
         (DespachoId, CodigoProducto, DescripcionProducto, UnidadMedida, Cantidad, Comentarios)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    $startTime = time();
    $totalRows = count($rows);

    foreach ($rows as $i => $rowInfo) {
        $r   = $rowInfo['rowNum'];
        $d   = $rowInfo['data'];
        $ctx = "Fila {$r}";

        if ($i > 0 && $i % 500 === 0) {
            $elapsed = time() - $startTime;
            $rate = $i / max($elapsed, 1);
            $remaining = $totalRows - $i;
            $eta = $remaining / max($rate, 1);
            logLine("  [PROGRESO] {$i}/{$totalRows} filas (" . round($rate, 1) . " filas/s) - ETA: {$eta}s.");
            // Verificar que MySQL siga conectado
            ping($db);
        }

        $nvale    = cellStr($d['NVale'] ?? null);
        $codProd  = cellStr($d['Cod_Producto'] ?? null) ?: 'S/C';
        $descProd = cellStr($d['Descripcion_Producto'] ?? null) ?: 'S/D';
        $cantidad = cellNum($d['Cantidad'] ?? null);

        if ($nvale === '') { logLine("  [{$ctx}] OMITIDA: NVale vacío."); $totalOmitidos++; continue; }

        try {
            $db->beginTransaction();
if (!isset($valesCache[$nvale])) {
    $fecha     = cellDate($d['Fecha'] ?? null);
    $hora      = cellTime($d['Hora'] ?? null) ?: DEFAULT_HORA;
    $turnoId   = resolveTurno($db, cellStr($d['Turno'] ?? null), $ctx);
    $areaId    = resolveArea($db, cellStr($d['Area'] ?? null), $ctx);
    $subareaId = resolveSubareaConArea($db, cellStr($d['Subarea'] ?? null), cellStr($d['Area'] ?? null), $ctx);
    // Emisor en recepciones_internas FK -> usuarios.Id (igual que en despachos_internos)
    $emisorId  = IMPORT_USER_ID;
    // Despachador: si no se encuentra en responsables, usar IMPORT_USER_ID como fallback
    $despId    = resolveResponsable($db, cellStr($d['Despachador'] ?? null), $ctx) ?? IMPORT_USER_ID;
    // MedioTransporte es int(11) NOT NULL (sin FK) - si el valor parece nombre de persona,
    // es dato desalineado del Excel, usamos 0 como fallback
    $medioVal  = cellStr($d['Medio_Transporte'] ?? null);
    if ($medioVal !== '' && preg_match('/^[A-ZÁÉÍÓÚÑ]+[a-záéíóúñ]+\s+[A-ZÁÉÍÓÚÑ]+[a-záéíóúñ]+/', $medioVal)) {
        $medioId = 0; // Dato desalineado, no crear registro basura en medio_transporte
    } else {
        $medioId = resolveMedioTransporte($db, $medioVal, $ctx) ?? 0;
    }
    // Verificador: si no se encuentra, usar IMPORT_USER_ID como fallback
    $verifId   = resolveResponsable($db, cellStr($d['Verificador'] ?? null), $ctx) ?? IMPORT_USER_ID;
    $nLiquid   = cellStr($d['N_Liquidacion'] ?? null) ?: null;

    // ADVERTENCIAS (no bloqueantes): datos inconsistentes se registran igual
    // Para columnas NOT NULL (Turno, Area, Subarea) usamos 1 como fallback
    if (!$turnoId) {
        logLine("  [{$ctx}] [ADVERTENCIA] Turno no encontrado para vale '{$nvale}' — se registra con ID 1 (por defecto).");
        $totalAdvertencias++;
        $turnoId = 1;
    }
    if (!$areaId) {
        logLine("  [{$ctx}] [ADVERTENCIA] Area no encontrada para vale '{$nvale}' — se registra con ID 1 (por defecto).");
        $totalAdvertencias++;
        $areaId = 1;
    }
    if (!$subareaId) {
        logLine("  [{$ctx}] [ADVERTENCIA] Subarea no encontrada para vale '{$nvale}' — se registra con ID 1 (por defecto).");
        $totalAdvertencias++;
        $subareaId = 1;
    }
    if ($medioVal !== '' && !$medioId) {
        logLine("  [{$ctx}] [ADVERTENCIA] MedioTransporte no encontrado para vale '{$nvale}' — se registra con 0.");
        $totalAdvertencias++;
    }

    if (!$fecha) {
        logLine("  [{$ctx}] [ADVERTENCIA] Fecha inválida (vacía) para vale '{$nvale}' — se registra con '0000-00-00'.");
        $totalAdvertencias++;
        $fecha = DEFAULT_FECHA;
    }


                $stmtInsertVale->execute([
                    $nvale, $fecha, $hora, $turnoId, $areaId, $subareaId,
                    $emisorId, $despId, $medioId, $verifId, $nLiquid, IMPORT_USER_ID
                ]);
                $valesCache[$nvale] = (int)$db->lastInsertId();
                $valesInsertados++;
            }

            $recepcionId = $valesCache[$nvale];

            $stmtInsertProd->execute([
                $recepcionId, $codProd, $descProd,
                cellStr($d['Unidad_Medida'] ?? null),
                $cantidad,
                cellStr($d['Comentarios'] ?? null),
            ]);
            $productosInsertados++;
            $db->commit();

        } catch (Exception $e) {
            $db->rollBack();
            logLine("  [{$ctx}] ERROR: " . $e->getMessage());
            $errores++; $totalErrores++;
        }
    }

    logLine("  Vales insertados: {$valesInsertados} | Productos: {$productosInsertados} | Errores: {$errores}");
    $totalImportados += $valesInsertados + $productosInsertados;
    freeSheetMemory($spreadsheet, 'Recepciones_Internas');
}

// ============================================================
//  MÓDULO 3: DESPACHOS EXTERNOS
// ============================================================

if ((ONLY_SHEET === null || ONLY_SHEET === 'Despachos_Externos') && !in_array('Despachos_Externos', SKIP_SHEETS)) {
    logSection('PROCESANDO: Despachos Externos');

    $rows = readSheet($spreadsheet, 'Despachos_Externos');
    $valesInsertados  = 0;
    $productosInsertados = 0;
    $errores = 0;

    // Precargar vales existentes
    logLine("  Precargando vales existentes en BD...");
    $valesCache = [];
    $stmtAll = $db->query("SELECT Id, NVale FROM despachos_externos");
    while ($row = $stmtAll->fetch(PDO::FETCH_ASSOC)) {
        $valesCache[$row['NVale']] = (int)$row['Id'];
    }
    logLine("  " . count($valesCache) . " vales existentes precargados.");

    // Preparar statements
    $stmtInsertVale = $db->prepare(
        "INSERT INTO despachos_externos
         (NVale, Fecha, Hora, Turno, Destino, RUC, Direccion, Despachador,
          Chofer, Licencia, Transportista, RUC_Transportista,
          Placa_Tracto, Placa_Carreta, Constancia_Inscripcion, Constancia_Inscripcion_2,
          GR, creado_por, estado, ip_creacion)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'ACTIVO',?)"
    );
    $stmtInsertProd = $db->prepare(
        "INSERT INTO despachos_externos_productos
         (DespachoId, CodigoProducto, DescripcionProducto, UnidadMedida, Cantidad, Comentarios)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    $startTime = time();
    $totalRows = count($rows);

    foreach ($rows as $i => $rowInfo) {
        $r   = $rowInfo['rowNum'];
        $d   = $rowInfo['data'];
        $ctx = "Fila {$r}";

        if ($i > 0 && $i % 500 === 0) {
            $elapsed = time() - $startTime;
            $rate = $i / max($elapsed, 1);
            $remaining = $totalRows - $i;
            $eta = $remaining / max($rate, 1);
            logLine("  [PROGRESO] {$i}/{$totalRows} filas (" . round($rate, 1) . " filas/s) - ETA: {$eta}s.");
            // Verificar que MySQL siga conectado
            ping($db);
        }

        $nvale    = cellStr($d['NVale'] ?? null);
        $codProd  = cellStr($d['Cod_Producto'] ?? null) ?: 'S/C';
        $descProd = cellStr($d['Descripcion_Producto'] ?? null) ?: 'S/D';
        $cantidad = cellNum($d['Cantidad'] ?? null);

        if ($nvale === '') { logLine("  [{$ctx}] OMITIDA: NVale vacío."); $totalOmitidos++; continue; }

        try {
            $db->beginTransaction();

            if (!isset($valesCache[$nvale])) {
                $fecha      = cellDate($d['Fecha'] ?? null);
                $hora       = cellTime($d['Hora'] ?? null) ?: DEFAULT_HORA;
                $turnoId    = resolveTurno($db, cellStr($d['Turno'] ?? null), $ctx);
                $despId     = resolveResponsable($db, cellStr($d['Despachador'] ?? null), $ctx) ?? IMPORT_USER_ID;
                $transId    = resolveTransportista($db, cellStr($d['Transportista'] ?? null), $ctx) ?? 0;
                $choferId   = resolveChofer($db, cellStr($d['Chofer'] ?? null), $ctx) ?? 0;

                // ADVERTENCIAS (no bloqueantes): datos inconsistentes se registran igual
                // Turno es NOT NULL, usar 1 como fallback
                if (!$turnoId) {
                    logLine("  [{$ctx}] [ADVERTENCIA] Turno no encontrado para vale '{$nvale}' — se registra con ID 1 (por defecto).");
                    $totalAdvertencias++;
                    $turnoId = 1;
                }
                if (!$choferId) {
                    logLine("  [{$ctx}] [ADVERTENCIA] Chofer no encontrado para vale '{$nvale}' — se registra con 0.");
                    $totalAdvertencias++;
                }
                if (!$transId) {
                    logLine("  [{$ctx}] [ADVERTENCIA] Transportista no encontrado para vale '{$nvale}' — se registra con 0.");
                    $totalAdvertencias++;
                }

                // ============================================================
                // IMPORTANTE: Columnas de despachos_externos
                // ============================================================
                // Destino, RUC, Direccion, Licencia, RUC_Transportista,
                // Placa_Tracto, Placa_Carreta, Constancia_Inscripcion,
                // Constancia_Inscripcion_2, GR son TODAS varchar NOT NULL
                // (texto directo, NO foreign keys).
                //
                // Chofer es int(11) NOT NULL (FK a choferes).
                // ============================================================

                // Texto directo del Excel (NO resolver como FK)
                $destinoTexto  = cellStr($d['Destino'] ?? null) ?: 'S/D';
                // RUC es varchar(11) - truncar si excede
                $ruc           = substr(cellStr($d['RUC_Destino'] ?? null) ?: '', 0, 11);
                $direccion     = cellStr($d['Direccion_Destino'] ?? null) ?: '';
                $licencia      = cellStr($d['Licencia'] ?? null) ?: '';
                // RUC_Transportista es varchar(11) - truncar si excede
                $rucTransp     = substr(cellStr($d['RUC_Transportista'] ?? null) ?: '', 0, 11);
                $placaTracto   = cellStr($d['Placa_Tracto'] ?? null) ?: '';
                $placaCarreta  = cellStr($d['Placa_Carreta'] ?? null) ?: '';
                $const1        = cellStr($d['Constancia_1'] ?? null) ?: '';
                $const2        = cellStr($d['Constancia_2'] ?? null) ?: '';
                $gr            = cellStr($d['GR'] ?? null) ?: '';

                if (!$fecha) {
                    logLine("  [{$ctx}] [ADVERTENCIA] Fecha inválida (vacía) para vale '{$nvale}' — se registra con '0000-00-00'.");
                    $totalAdvertencias++;
                    $fecha = DEFAULT_FECHA;
                }

                $stmtInsertVale->execute([
                    $nvale, $fecha, $hora, $turnoId, $destinoTexto, $ruc, $direccion, $despId,
                    $choferId, $licencia, $transId, $rucTransp,
                    $placaTracto, $placaCarreta, $const1, $const2,
                    $gr, IMPORT_USER_ID, IMPORT_IP
                ]);
                $valesCache[$nvale] = (int)$db->lastInsertId();
                $valesInsertados++;
            }

            $despachoId = $valesCache[$nvale];

            $stmtInsertProd->execute([
                $despachoId, $codProd, $descProd,
                cellStr($d['Unidad_Medida'] ?? null),
                $cantidad,
                cellStr($d['Comentarios'] ?? null),
            ]);
            $productosInsertados++;
            $db->commit();

        } catch (Exception $e) {
            $db->rollBack();
            logLine("  [{$ctx}] ERROR: " . $e->getMessage());
            $errores++; $totalErrores++;
        }
    }

    logLine("  Vales insertados: {$valesInsertados} | Productos: {$productosInsertados} | Errores: {$errores}");
    $totalImportados += $valesInsertados + $productosInsertados;
    freeSheetMemory($spreadsheet, 'Despachos_Externos');
}

// ============================================================
//  MÓDULO 4: RECEPCIONES EXTERNAS
//  Flujo: recepcion -> guia -> producto
//  Clave compuesta de caché: NVale + "|" + NumeroGuia
// ============================================================

if ((ONLY_SHEET === null || ONLY_SHEET === 'Recepciones_Externas') && !in_array('Recepciones_Externas', SKIP_SHEETS)) {
    logSection('PROCESANDO: Recepciones Externas');

    $rows = readSheet($spreadsheet, 'Recepciones_Externas');
    $valesInsertados  = 0;
    $guiasInsertadas  = 0;
    $productosInsertados = 0;
    $errores = 0;

    // ---- Sistema de lotes (batches) ----
    // Determinar desde qué fila empezar
    $batchStart = BATCH_START_ROW;
    if (file_exists(CHECKPOINT_FILE)) {
        $saved = (int)trim(file_get_contents(CHECKPOINT_FILE));
        if ($saved > 0) {
            $batchStart = $saved;
            logLine("  Checkpoint encontrado: reanudando desde fila #{$batchStart}.");
        }
    }
    // Si BATCH_SIZE es 0, procesar todo
    $batchSize = (BATCH_SIZE > 0) ? BATCH_SIZE : count($rows);
    $batchEnd = $batchStart + $batchSize;
    // No pasarse del total
    if ($batchEnd > count($rows)) {
        $batchEnd = count($rows);
    }
    $totalEnLote = $batchEnd - $batchStart;
    logLine("  Lote: filas {$batchStart} a {$batchEnd} (total en este lote: {$totalEnLote})");
    // ---- Fin sistema de lotes ----

    // Precargar vales existentes
    logLine("  Precargando vales existentes en BD...");
    $valesCache = [];  // NVale => recepcionId
    $stmtAll = $db->query("SELECT Id, NVale FROM recepciones_externas");
    while ($row = $stmtAll->fetch(PDO::FETCH_ASSOC)) {
        $valesCache[$row['NVale']] = (int)$row['Id'];
    }
    logLine("  " . count($valesCache) . " vales existentes precargados.");

    // Precargar guías existentes
    logLine("  Precargando guías existentes en BD...");
    $guiasCache = [];  // "NVale|NumGuia" => guiaId
    $stmtAllG = $db->query(
        "SELECT g.Id, g.RecepcionExternaId, g.NumeroGuia, r.NVale
         FROM recepciones_externas_guias g
         JOIN recepciones_externas r ON r.Id = g.RecepcionExternaId"
    );
    while ($row = $stmtAllG->fetch(PDO::FETCH_ASSOC)) {
        $key = $row['NVale'] . '|' . $row['NumeroGuia'];
        $guiasCache[$key] = (int)$row['Id'];
    }
    logLine("  " . count($guiasCache) . " guías existentes precargadas.");

    // Función para preparar/regenerar statements (se llama al inicio y después de cada reconexión)
    $prepareStatements = function(PDO $db) use (&$stmtInsertVale, &$stmtInsertGuia, &$stmtInsertProd) {
        $stmtInsertVale = $db->prepare(
            "INSERT INTO recepciones_externas
             (NVale, Fecha, Hora, Turno, Origen, Recepcionista, Empresa, RUC,
              Chofer, Brevete, Comentarios, creado_por, creado_en, estado, ip_creacion)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW(),'activo',?)"
        );
        $stmtInsertGuia = $db->prepare(
            "INSERT INTO recepciones_externas_guias
             (RecepcionExternaId, NumeroGuia, NumeroDocRef, Observacion, CodigoProductoObs,
              CantidadObservada, Orden, TextoObservaciones)
             VALUES (?, ?, ?, ?, ?, ?, 1, ?)"
        );
        $stmtInsertProd = $db->prepare(
            "INSERT INTO recepciones_externas_productos
             (GuiaId, CodigoProducto, DescripcionProducto, UnidadMedida, Cantidad,
              ColumnaProducto, Observacion, CantidadObservada)
             VALUES (?, ?, ?, ?, ?, 1, ?, ?)"
        );
    };

    // Preparar statements iniciales
    $prepareStatements($db);

    $startTime = time();
    $totalRows = count($rows);

    // Iterar SOLO el rango del lote actual
    for ($i = $batchStart; $i < $batchEnd; $i++) {
        $rowInfo = $rows[$i];
        $r   = $rowInfo['rowNum'];
        $d   = $rowInfo['data'];
        $ctx = "Fila {$r}";

        // Progreso basado en el total del lote, no del archivo completo
        $filasEnLote = $i - $batchStart;
        if ($filasEnLote > 0 && $filasEnLote % 500 === 0) {
            $elapsed = time() - $startTime;
            $rate = $filasEnLote / max($elapsed, 1);
            $remaining = $totalEnLote - $filasEnLote;
            $eta = $remaining / max($rate, 1);
            logLine("  [LOTE] {$filasEnLote}/{$totalEnLote} filas (" . round($rate, 1) . " filas/s) - ETA: {$eta}s.");
        }
        // Verificar conexión MySQL cada 100 filas para evitar "MySQL server has gone away"
        // Si ping() retorna true (hubo reconexión), regenerar los prepared statements
        // porque los anteriores están asociados a la conexión muerta
        if ($filasEnLote > 0 && $filasEnLote % 100 === 0) {
            if (ping($db)) {
                $prepareStatements($db);
            }
        }

        $nvale    = cellStr($d['NVale'] ?? null);
        $numGuia  = cellStr($d['Numero_Guia'] ?? null);
        $codProd  = cellStr($d['Cod_Producto'] ?? null) ?: 'S/C';
        $descProd = cellStr($d['Descripcion_Producto'] ?? null) ?: 'S/D';
        $cantidad = cellNum($d['Cantidad'] ?? null);

        if ($nvale === '') { logLine("  [{$ctx}] OMITIDA: NVale vacío."); $totalOmitidos++; continue; }

        try {
            $db->beginTransaction();

            // ---- Nivel 1: Recepción (vale) ----
            if (!isset($valesCache[$nvale])) {
                $fecha      = cellDate($d['Fecha'] ?? null);
                $hora       = cellTime($d['Hora'] ?? null) ?: DEFAULT_HORA;
                $turnoId    = resolveTurno($db, cellStr($d['Turno'] ?? null), $ctx);
                $origenId   = resolveOrigen($db, cellStr($d['Origen'] ?? null), $ctx);
                $recepId    = resolveRecepcionista($db, cellStr($d['Recepcionista'] ?? null), $ctx);
                // Empresa es int(11) FK a transportistas.Id
                $transId    = resolveTransportista($db, cellStr($d['Empresa_Transportista'] ?? null), $ctx) ?? 0;
                $rucTransp  = cellStr($d['RUC_Transportista'] ?? null) ?: '';
                $chofer     = resolveChofer($db, cellStr($d['Chofer'] ?? null), $ctx) ?? 0;
                $brevete    = cellStr($d['Brevete'] ?? null) ?: '';
                $comentarios= cellStr($d['Comentarios_Vale'] ?? null) ?: '';

                // ADVERTENCIAS (no bloqueantes): datos inconsistentes se registran igual
                // Turno es NOT NULL, usar 1 como fallback
                if (!$turnoId) {
                    logLine("  [{$ctx}] [ADVERTENCIA] Turno no encontrado para vale '{$nvale}' — se registra con ID 1 (por defecto).");
                    $totalAdvertencias++;
                    $turnoId = 1;
                }
                if (!$origenId) {
                    logLine("  [{$ctx}] [ADVERTENCIA] Origen no encontrado para vale '{$nvale}' — se registra con NULL.");
                    $totalAdvertencias++;
                }
                if (!$recepId) {
                    logLine("  [{$ctx}] [ADVERTENCIA] Recepcionista no encontrado para vale '{$nvale}' — se registra con NULL.");
                    $totalAdvertencias++;
                }
                if (!$transId) {
                    logLine("  [{$ctx}] [ADVERTENCIA] Transportista (Empresa) no encontrado para vale '{$nvale}' — se registra con 0.");
                    $totalAdvertencias++;
                }
                if (!$chofer) {
                    logLine("  [{$ctx}] [ADVERTENCIA] Chofer no encontrado para vale '{$nvale}' — se registra con 0.");
                    $totalAdvertencias++;
                }

                if (!$fecha) {
                    logLine("  [{$ctx}] [ADVERTENCIA] Fecha inválida (vacía) para vale '{$nvale}' — se registra con '0000-00-00'.");
                    $totalAdvertencias++;
                    $fecha = DEFAULT_FECHA;
                }

                $stmtInsertVale->execute([
                    $nvale, $fecha, $hora, $turnoId, $origenId, $recepId,
                    $transId, $rucTransp, $chofer, $brevete, $comentarios,
                    IMPORT_USER_ID, IMPORT_IP
                ]);
                $valesCache[$nvale] = (int)$db->lastInsertId();
                $valesInsertados++;
            }

            $recepcionId = $valesCache[$nvale];
            $guiaCacheKey = $nvale . '|' . $numGuia;

            // ---- Nivel 2: Guía ----
            if (!isset($guiasCache[$guiaCacheKey])) {
                $numDocRef      = cellStr($d['Num_Doc_Ref'] ?? null) ?: null;
                // Observacion en recepciones_externas_guias es int(11) FK a observaciones.Id
                // Si el valor no es numérico, usar 0
                $obsGuiaVal     = cellStr($d['Obs_Guia'] ?? null);
                $obsGuia        = (is_numeric($obsGuiaVal) && $obsGuiaVal !== '') ? (int)$obsGuiaVal : 0;
                $codProdObs     = cellStr($d['Cod_Producto_Obs'] ?? null) ?: null;
                $cantObsGuia    = cellNum($d['Cant_Observada_Guia'] ?? null);
                $textoObs       = cellStr($d['Texto_Observaciones'] ?? null) ?: null;

                $stmtInsertGuia->execute([
                    $recepcionId, strtoupper($numGuia), $numDocRef, $obsGuia,
                    $codProdObs, $cantObsGuia, $textoObs
                ]);
                $guiasCache[$guiaCacheKey] = (int)$db->lastInsertId();
                $guiasInsertadas++;
            }

            $guiaId = $guiasCache[$guiaCacheKey];

            // ---- Nivel 3: Producto ----
            // Observacion en recepciones_externas_productos es int(11)
            // Si el valor no es numérico (ej: 'ANULADO'), usar 0
            $obsProdVal = cellStr($d['Obs_Producto'] ?? null);
            $obsProd    = (is_numeric($obsProdVal) && $obsProdVal !== '') ? (int)$obsProdVal : 0;
            $stmtInsertProd->execute([
                $guiaId, $codProd, $descProd,
                cellStr($d['Unidad_Medida'] ?? null),
                $cantidad,
                $obsProd,
                cellNum($d['Cant_Obs_Producto'] ?? null),
            ]);
            $productosInsertados++;
            $db->commit();

        } catch (Exception $e) {
            // Intentar rollback; si MySQL se fue, reconectar primero
            try {
                $db->rollBack();
            } catch (Exception $rollbackErr) {
                // Si el rollback falla (MySQL gone away), reconectar y regenerar statements
                if (ping($db)) {
                    $prepareStatements($db);
                }
            }
            // Limpiar cache si el vale se insertó en este try pero falló después
            if (isset($valesCache[$nvale]) && $valesInsertados > 0) {
                unset($valesCache[$nvale]);
                $valesInsertados--;
            }
            logLine("  [{$ctx}] ERROR: " . $e->getMessage());
            $errores++; $totalErrores++;
        }
    }

    // ---- Guardar checkpoint para el siguiente lote ----
    $siguienteFila = $batchEnd;
    if ($siguienteFila >= count($rows)) {
        // Lote final: borrar checkpoint
        if (file_exists(CHECKPOINT_FILE)) {
            unlink(CHECKPOINT_FILE);
        }
        logLine("  ¡LOTE FINAL COMPLETADO! Todas las filas procesadas.");
    } else {
        file_put_contents(CHECKPOINT_FILE, (string)$siguienteFila);
        logLine("  Checkpoint guardado: fila #{$siguienteFila}. Vuelve a ejecutar el script para continuar desde aquí.");
    }
    // ---- Fin checkpoint ----

    logLine("  Vales: {$valesInsertados} | Guías: {$guiasInsertadas} | Productos: {$productosInsertados} | Errores: {$errores}");
    $totalImportados += $valesInsertados + $guiasInsertadas + $productosInsertados;

    // ---- Resumen parcial del lote ----
    $elapsedLote = round(microtime(true) - $startTime, 2);
    logLine('');
    logLine(str_repeat('-', 50));
    logLine("  RESUMEN DEL LOTE (filas {$batchStart} a {$batchEnd})");
    logLine(str_repeat('-', 50));
    logLine("  Vales insertados      : {$valesInsertados}");
    logLine("  Guías insertadas      : {$guiasInsertadas}");
    logLine("  Productos insertados  : {$productosInsertados}");
    logLine("  Errores               : {$errores}");
    logLine("  Advertencias (lote)   : {$totalAdvertencias}");
    logLine("  Tiempo del lote       : {$elapsedLote} segundos");
    logLine(str_repeat('-', 50));
    if ($siguienteFila < count($rows)) {
        $pendientes = count($rows) - $siguienteFila;
        logLine("  Filas procesadas      : {$batchEnd}");
        logLine("  Filas pendientes      : {$pendientes}");
        logLine("  Progreso total        : " . round($batchEnd / count($rows) * 100, 1) . "%");
        logLine("  Para continuar, ejecuta el script nuevamente.");
    } else {
        logLine("  ¡TODAS LAS FILAS PROCESADAS!");
    }
    logLine(str_repeat('-', 50));

    // Guardar log parcial inmediatamente (por si el script se corta)
    file_put_contents(LOG_FILE, implode("\n", $logLines) . "\n");
    logLine("  Log parcial guardado en: " . LOG_FILE);

    freeSheetMemory($spreadsheet, 'Recepciones_Externas');
}

// ============================================================
//  RESUMEN FINAL
// ============================================================

$elapsed = round(microtime(true) - $startTime, 2);

logSection('RESUMEN FINAL');
logLine("Total registros importados    : {$totalImportados}");
logLine("Total filas con errores       : {$totalErrores}");
logLine("Total filas omitidas          : {$totalOmitidos}");
logLine("Total advertencias (no críticas): {$totalAdvertencias}");
logLine("Tiempo de ejecución           : {$elapsed} segundos");

if (!empty($missingMasters)) {
    logLine('');
    logLine('--- VALORES MAESTROS NO ENCONTRADOS (si AUTO_CREATE_MASTERS=false) ---');
    foreach (array_unique($missingMasters) as $m) {
        logLine('  ' . $m);
    }
}

logLine('');
logLine('Log guardado en: ' . LOG_FILE);

file_put_contents(LOG_FILE, implode("\n", $logLines) . "\n");
echo "\n";
