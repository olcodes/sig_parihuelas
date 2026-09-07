<?php
/**
 * Script de diagnóstico para ver qué valor devuelve PhpSpreadsheet
 * al leer celdas con formato de hora.
 */
ini_set('memory_limit', '1024M');
set_time_limit(120);

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

$archivo = __DIR__ . '/datos_importacion.xlsx';
echo "Archivo: $archivo\n";

// Cargar solo como lectura de datos
$spreadsheet = IOFactory::load($archivo);
$spreadsheet->setReadDataOnly(true);

// Buscar la hoja Despachos_Internos
$sheet = null;
foreach ($spreadsheet->getAllSheets() as $s) {
    if (stripos($s->getTitle(), 'Despachos_Internos') !== false) {
        $sheet = $s;
        break;
    }
}

if (!$sheet) {
    echo "Hoja no encontrada\n";
    exit;
}

echo "Hoja: " . $sheet->getTitle() . "\n";
echo "Filas: " . $sheet->getHighestDataRow() . "\n";
echo "Columnas: " . $sheet->getHighestDataColumn() . "\n\n";

// Leer encabezados (fila 2)
$headers = [];
$maxCol = $sheet->getHighestDataColumn();
$colIdx = 1;
while (Coordinate::stringFromColumnIndex($colIdx) <= $maxCol) {
    $cellRef = Coordinate::stringFromColumnIndex($colIdx) . '2';
    $h = trim((string)$sheet->getCell($cellRef)->getValue());
    if ($h !== '') {
        $headers[$colIdx] = $h;
    }
    $colIdx++;
}

echo "Encabezados (" . count($headers) . "):\n";
foreach ($headers as $c => $h) {
    echo "  Col $c: '$h'\n";
}

// Buscar columna Hora
$horaCol = null;
foreach ($headers as $c => $h) {
    if (stripos($h, 'Hora') !== false) {
        $horaCol = $c;
        break;
    }
}

if (!$horaCol) {
    echo "\nNo se encontró columna Hora\n";
    exit;
}

echo "\nColumna Hora encontrada en columna: $horaCol\n\n";

// Probar las primeras 20 filas con datos (desde fila 3)
echo "Valores de Hora en primeras filas:\n";
$count = 0;
for ($r = 3; $r <= 100; $r++) {
    $cell = $sheet->getCellByColumnAndRow($horaCol, $r);
    $val = $cell->getValue();
    $type = gettype($val);
    
    // Saltar filas vacías
    if ($val === null || (is_string($val) && trim($val) === '')) {
        continue;
    }
    
    $count++;
    echo "  Fila $r: tipo=$type";
    
    if ($val instanceof DateTimeInterface) {
        echo " | DateTime: " . $val->format('Y-m-d H:i:s');
    } elseif (is_numeric($val)) {
        echo " | numérico=$val";
        if ($val >= 0 && $val < 1) {
            $seconds = round((float)$val * 86400);
            $h = floor($seconds / 3600);
            $m = floor(($seconds % 3600) / 60);
            $s = $seconds % 60;
            echo " => " . sprintf('%02d:%02d:%02d', $h, $m, $s);
        } elseif ($val > 1) {
            // Fecha serial + hora
            $whole = floor($val);
            $frac = $val - $whole;
            echo " => fecha_serial=$whole, fraccion=$frac";
            if ($frac > 0) {
                $seconds = round($frac * 86400);
                $h = floor($seconds / 3600);
                $m = floor(($seconds % 3600) / 60);
                $s = $seconds % 60;
                echo " => hora=" . sprintf('%02d:%02d:%02d', $h, $m, $s);
            }
        }
    } elseif (is_string($val)) {
        echo " | string='$val'";
        // Intentar parsear
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?/', $val, $m)) {
            echo " => " . sprintf('%02d:%02d:%02d', $m[1], $m[2], isset($m[3]) ? $m[3] : 0);
        }
    } else {
        echo " | valor=" . json_encode($val);
    }
    
    echo "\n";
    
    if ($count >= 10) break;
}

echo "\n---\nHecho.\n";
