<?php
/**
 * Test ultra-ligero: solo lee las primeras filas de Despachos_Internos
 * para ver qué valor devuelve getValue() en la columna Hora.
 */
ini_set('memory_limit', '2048M');
set_time_limit(120);

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

$archivo = __DIR__ . '/datos_importacion.xlsx';
echo "<pre>";
echo "Archivo: $archivo\n";

// Cargar con ReadDataOnly
$reader = IOFactory::createReaderForFile($archivo);
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load($archivo);

// Buscar hoja
$sheet = null;
foreach ($spreadsheet->getAllSheets() as $s) {
    if (stripos($s->getTitle(), 'Despachos_Internos') !== false) {
        $sheet = $s;
        break;
    }
}
if (!$sheet) { echo "Hoja no encontrada\n"; exit; }

echo "Hoja: " . $sheet->getTitle() . "\n\n";

// Leer encabezados fila 2
$headers = [];
$maxCol = $sheet->getHighestDataColumn();
$colIdx = 1;
while (Coordinate::stringFromColumnIndex($colIdx) <= $maxCol) {
    $cellRef = Coordinate::stringFromColumnIndex($colIdx) . '2';
    $h = trim((string)$sheet->getCell($cellRef)->getValue());
    if ($h !== '') $headers[$colIdx] = $h;
    $colIdx++;
}

echo "Encabezados:\n";
foreach ($headers as $c => $h) echo "  Col $c: '$h'\n";

// Encontrar columnas NVale, Fecha, Hora
$cols = ['NVale' => null, 'Fecha' => null, 'Hora' => null];
foreach ($headers as $c => $h) {
    foreach ($cols as $key => &$v) {
        if (stripos($h, $key) !== false) $v = $c;
    }
}
unset($v);

echo "\nColumnas: NVale={$cols['NVale']}, Fecha={$cols['Fecha']}, Hora={$cols['Hora']}\n\n";

// Leer filas 3 a 50 y mostrar valores crudos de Hora
echo "Valores de Hora (raw):\n";
$count = 0;
for ($r = 3; $r <= 50; $r++) {
    // Verificar si la fila tiene datos
    $nvaleCell = $sheet->getCellByColumnAndRow($cols['NVale'], $r);
    $nvaleVal = $nvaleCell->getValue();
    if ($nvaleVal === null || trim((string)$nvaleVal) === '') continue;
    
    $horaCell = $sheet->getCellByColumnAndRow($cols['Hora'], $r);
    $horaVal = $horaCell->getValue();
    
    $fechaCell = $sheet->getCellByColumnAndRow($cols['Fecha'], $r);
    $fechaVal = $fechaCell->getValue();
    
    echo "  Fila $r: NVale=" . json_encode($nvaleVal) . " | Fecha=" . json_encode($fechaVal) . " (" . gettype($fechaVal) . ") | Hora=" . json_encode($horaVal) . " (" . gettype($horaVal) . ")";
    
    // Probar cellTime manualmente
    if ($horaVal instanceof DateTimeInterface) {
        echo " => DateTime H:i:s=" . $horaVal->format('H:i:s');
    } elseif (is_numeric($horaVal)) {
        if ($horaVal >= 0 && $horaVal < 1) {
            $sec = round((float)$horaVal * 86400);
            echo " => serial<1: " . sprintf('%02d:%02d:%02d', floor($sec/3600), floor(($sec%3600)/60), $sec%60);
        } elseif ($horaVal == 0) {
            echo " => cero (medianoche)";
        } elseif ($horaVal > 1) {
            $frac = (float)$horaVal - floor((float)$horaVal);
            if ($frac > 0) {
                $sec = round($frac * 86400);
                echo " => serial>1 (fecha+hora): fraccion=$frac hora=" . sprintf('%02d:%02d:%02d', floor($sec/3600), floor(($sec%3600)/60), $sec%60);
            } else {
                echo " => serial>1 sin fraccion (medianoche)";
            }
        }
    } elseif (is_string($horaVal)) {
        echo " => string";
    }
    
    echo "\n";
    $count++;
    if ($count >= 10) break;
}

echo "\n--- Hecho ---\n";
