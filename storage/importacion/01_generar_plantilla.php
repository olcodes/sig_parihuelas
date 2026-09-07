<?php
/**
 * GENERADOR DE PLANTILLA EXCEL - CARGA MASIVA
 * =============================================
 * Ejecutar UNA VEZ para generar el archivo plantilla:
 *   php 01_generar_plantilla.php
 * o acceder por browser desde WAMP.
 *
 * El archivo resultante "plantilla_carga_masiva.xlsx" es el que
 * se entrega al encargado para que lo llene con los datos históricos.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// ============================================================
// DEFINICIÓN DE HOJAS Y COLUMNAS
// ============================================================
// Formato: 'NombreColumna' => 'Descripción para el comentario/tooltip'
// Las columnas marcadas con (*) son OBLIGATORIAS
// ============================================================

$sheetsConfig = [

    // ----------------------------------------------------------
    // MÓDULO 1: DESPACHOS INTERNOS
    // Tablas: despachos_internos + despachos_internos_productos
    // ----------------------------------------------------------
    'Despachos_Internos' => [
        'color'   => '1565C0',  // azul oscuro
        'headers' => [
            'NVale'               => '(*) Número de vale. Ej: DI-001, 001, etc.',
            'Fecha'               => '(*) Fecha en formato DD/MM/YYYY. Ej: 15/03/2024',
            'Hora'                => 'Hora de despacho en formato HH:MM. Ej: 08:30',
            'Turno'               => 'Nombre del turno tal como está en el sistema. Ej: MAÑANA',
            'Area'                => 'Nombre del área. Ej: ALMACEN',
            'Subarea'             => 'Nombre del subárea. Ej: ALMACEN NORTE',
            'Emisor'              => 'Nombre completo del emisor. Ej: JUAN PEREZ LOPEZ',
            'Despachador'         => 'Nombre completo del despachador',
            'Recepcionista'       => 'Nombre completo del recepcionista',
            'Verificador'         => 'Nombre completo del verificador',
            'Cod_Producto'        => '(*) Código del producto. Ej: PRD-001',
            'Descripcion_Producto'=> '(*) Descripción/nombre del producto',
            'Unidad_Medida'       => 'Unidad de medida. Ej: UND, KG, LT, BOLSA',
            'Cantidad'            => '(*) Cantidad numérica. Ej: 50',
            'Comentarios'         => 'Comentarios adicionales del producto (opcional)',
        ],
        'example' => [
            'DI-001', '15/03/2024', '08:30', 'MAÑANA', 'ALMACEN', 'ALMACEN NORTE',
            'JUAN PEREZ LOPEZ', 'CARLOS GARCIA RAMOS', 'MARIA TORRES SILVA',
            'PEDRO QUISPE MAMANI', 'PRD-001', 'CEMENTO PORTLAND 42.5', 'BOLSA', '50', '',
        ],
        'note' => 'Una fila por PRODUCTO. Si un vale tiene 3 productos, poner 3 filas con el mismo NVale y mismos datos del vale.',
    ],

    // ----------------------------------------------------------
    // MÓDULO 2: RECEPCIONES INTERNAS
    // Tablas: recepciones_internas + recepciones_internas_productos
    // ----------------------------------------------------------
    'Recepciones_Internas' => [
        'color'   => '1B5E20',  // verde oscuro
        'headers' => [
            'NVale'               => '(*) Número de vale. Ej: RI-001',
            'Fecha'               => '(*) Fecha en formato DD/MM/YYYY. Ej: 15/03/2024',
            'Hora'                => 'Hora en formato HH:MM. Ej: 09:30',
            'Turno'               => 'Nombre del turno. Ej: MAÑANA, TARDE, NOCHE',
            'Area'                => 'Nombre del área',
            'Subarea'             => 'Nombre del subárea',
            'Emisor'              => 'Nombre completo del emisor',
            'Despachador'         => 'Nombre completo del despachador de origen',
            'Medio_Transporte'    => 'Medio de transporte. Ej: CAMIÓN, MOTO, A PIE',
            'Verificador'         => 'Nombre completo del verificador',
            'N_Liquidacion'       => 'Número de liquidación (opcional)',
            'Cod_Producto'        => '(*) Código del producto',
            'Descripcion_Producto'=> '(*) Descripción/nombre del producto',
            'Unidad_Medida'       => 'Unidad de medida. Ej: UND, KG, LT',
            'Cantidad'            => '(*) Cantidad numérica',
            'Comentarios'         => 'Comentarios adicionales (opcional)',
        ],
        'example' => [
            'RI-001', '15/03/2024', '09:30', 'MAÑANA', 'ALMACEN', 'ALMACEN NORTE',
            'JUAN PEREZ LOPEZ', 'CARLOS GARCIA RAMOS', 'CAMIÓN',
            'PEDRO QUISPE MAMANI', '', 'PRD-001', 'CEMENTO PORTLAND 42.5', 'BOLSA', '30', '',
        ],
        'note' => 'Una fila por PRODUCTO. Si un vale tiene varios productos, repetir los datos del vale en cada fila.',
    ],

    // ----------------------------------------------------------
    // MÓDULO 3: DESPACHOS EXTERNOS
    // Tablas: despachos_externos + despachos_externos_productos
    // ----------------------------------------------------------
    'Despachos_Externos' => [
        'color'   => 'B71C1C',  // rojo oscuro
        'headers' => [
            'NVale'               => '(*) Número de vale. Ej: DE-001',
            'Fecha'               => '(*) Fecha en formato DD/MM/YYYY. Ej: 15/03/2024',
            'Hora'                => 'Hora en formato HH:MM. Ej: 07:00',
            'Turno'               => 'Nombre del turno. Ej: MAÑANA',
            'Destino'             => 'Empresa/nombre del destino',
            'RUC_Destino'         => 'RUC del destino (opcional)',
            'Direccion_Destino'   => 'Dirección del destino (opcional)',
            'Despachador'         => 'Nombre completo del despachador',
            'Chofer'              => 'Nombre completo del chofer',
            'Licencia'            => 'Número de licencia/brevete del chofer',
            'Transportista'       => 'Empresa transportista',
            'RUC_Transportista'   => 'RUC de la transportista (opcional)',
            'Placa_Tracto'        => 'Placa del tracto/camión (opcional)',
            'Placa_Carreta'       => 'Placa de la carreta/semirremolque (opcional)',
            'Constancia_1'        => 'N° constancia de inscripción 1 (opcional)',
            'Constancia_2'        => 'N° constancia de inscripción 2 (opcional)',
            'GR'                  => 'Número de guía de remisión (opcional)',
            'Cod_Producto'        => '(*) Código del producto',
            'Descripcion_Producto'=> '(*) Descripción/nombre del producto',
            'Unidad_Medida'       => 'Unidad de medida. Ej: UND, KG, TN',
            'Cantidad'            => '(*) Cantidad numérica',
            'Comentarios'         => 'Comentarios adicionales (opcional)',
        ],
        'example' => [
            'DE-001', '15/03/2024', '07:00', 'MAÑANA', 'EMPRESA CLIENTE SAC',
            '20123456789', 'AV. LIMA 123 - LIMA', 'CARLOS GARCIA RAMOS',
            'JOSE MAMANI QUISPE', 'Q12345678', 'TRANSPORTES RAPIDOS SAC',
            '20987654321', 'ABC-123', 'XYZ-456', '', '', 'GR-001-0001234',
            'PRD-001', 'CEMENTO PORTLAND 42.5', 'BOLSA', '100', '',
        ],
        'note' => 'Una fila por PRODUCTO. Destino, Chofer y Transportista: ingresar el nombre/empresa tal como está en el sistema o como debe quedar.',
    ],

    // ----------------------------------------------------------
    // MÓDULO 4: RECEPCIONES EXTERNAS
    // Tablas: recepciones_externas + recepciones_externas_guias
    //       + recepciones_externas_productos
    // IMPORTANTE: Este módulo tiene 3 tablas.
    //   - Una fila por PRODUCTO.
    //   - Si un vale tiene varias guías, repetir datos del vale
    //     con el número de guía correspondiente.
    //   - Si una guía tiene varios productos, repetir datos del
    //     vale y de la guía por cada producto.
    // ----------------------------------------------------------
    'Recepciones_Externas' => [
        'color'   => 'E65100',  // naranja oscuro
        'headers' => [
            'NVale'                => '(*) Número de vale. Ej: RE-001',
            'Fecha'                => '(*) Fecha en formato DD/MM/YYYY. Ej: 15/03/2024',
            'Hora'                 => 'Hora en formato HH:MM. Ej: 06:00',
            'Turno'                => 'Nombre del turno. Ej: MAÑANA',
            'Origen'               => 'Lugar/ciudad de origen. Ej: LIMA',
            'Recepcionista'        => 'Nombre completo del recepcionista',
            'Empresa_Transportista'=> 'Nombre de la empresa transportista',
            'RUC_Transportista'    => 'RUC de la transportista (se guarda como texto)',
            'Chofer'               => 'Nombre completo del chofer (texto directo)',
            'Brevete'              => 'Número de brevete del chofer',
            'Comentarios_Vale'     => 'Comentarios generales del vale (opcional)',
            'Numero_Guia'          => '(*) Número de guía. Ej: T001-0001234',
            'Num_Doc_Ref'          => 'Número de documento de referencia (opcional)',
            'Obs_Guia'             => 'Observación en la guía: CONFORME, OBSERVADO, etc. (opcional)',
            'Cod_Producto_Obs'     => 'Código del producto observado en la guía (opcional)',
            'Cant_Observada_Guia'  => 'Cantidad observada en la guía. Poner 0 si no aplica',
            'Texto_Observaciones'  => 'Texto libre de observaciones de la guía (opcional)',
            'Cod_Producto'         => '(*) Código del producto recibido',
            'Descripcion_Producto' => '(*) Descripción/nombre del producto',
            'Unidad_Medida'        => 'Unidad de medida. Ej: UND, KG, BOLSA',
            'Cantidad'             => '(*) Cantidad numérica',
            'Obs_Producto'         => 'Observación del producto específico (opcional)',
            'Cant_Obs_Producto'    => 'Cantidad observada de este producto. Poner 0 si no aplica',
        ],
        'example' => [
            'RE-001', '15/03/2024', '06:00', 'MAÑANA', 'LIMA',
            'MARIA TORRES SILVA', 'TRANSPORTES RAPIDOS SAC', '20987654321',
            'JOSE MAMANI QUISPE', 'Q12345678', '',
            'T001-0001234', 'FAC-001-00156', 'CONFORME', '', '0', '',
            'PRD-001', 'CEMENTO PORTLAND 42.5', 'BOLSA', '200', '', '0',
        ],
        'note' => 'IMPORTANTE: Una fila por PRODUCTO. Si un vale tiene 2 guías con 3 productos cada una, son 6 filas. Repetir NVale y datos del vale en cada fila. Repetir NVale, guía y datos de guía para cada producto de esa guía.',
    ],
];

// ============================================================
// GENERACIÓN DEL ARCHIVO EXCEL
// ============================================================

$spreadsheet = new Spreadsheet();
$spreadsheet->removeSheetByIndex(0);

$spreadsheet->getProperties()
    ->setCreator('Sistema SWLavoro')
    ->setTitle('Plantilla Carga Masiva')
    ->setDescription('Plantilla para importación de datos históricos');

foreach ($sheetsConfig as $sheetName => $config) {
    $sheet = $spreadsheet->createSheet();
    $sheet->setTitle($sheetName);

    $headers    = array_keys($config['headers']);
    $numCols    = count($headers);
    $lastColLtr = Coordinate::stringFromColumnIndex($numCols);

    // ---- FILA 1: Bloque de instrucciones ----
    $sheet->mergeCells("A1:{$lastColLtr}1");
    $instruccion = "INSTRUCCIONES: " . $config['note'] . " | "
        . "Los campos marcados con (*) son OBLIGATORIOS. "
        . "NO modificar los encabezados de la fila 2. "
        . "La fila 3 es un ejemplo: ELIMINARLA antes de importar. "
        . "Fechas en formato DD/MM/YYYY.";
    $sheet->getCell('A1')->setValue($instruccion);
    $sheet->getStyle('A1')->applyFromArray([
        'font'      => ['bold' => true, 'color' => ['rgb' => '4E342E'], 'size' => 9],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF8E1']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'wrapText' => true],
    ]);
    $sheet->getRowDimension(1)->setRowHeight(45);

    // ---- FILA 2: Encabezados de columnas ----
    foreach ($headers as $colIdx => $headerName) {
        $colNum  = $colIdx + 1;
        $cellRef = Coordinate::stringFromColumnIndex($colNum) . '2';

        $sheet->getCell($cellRef)->setValue($headerName);
        $sheet->getStyle($cellRef)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $config['color']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ]);

        // Agregar comentario con la descripción de la columna
        $comment = $sheet->getComment($cellRef);
        $comment->getText()->createTextRun($config['headers'][$headerName]);
        $comment->setWidth('200pt');
        $comment->setHeight('50pt');

        // Ancho de columna
        $sheet->getColumnDimensionByColumn($colNum)->setWidth(20);
    }
    $sheet->getRowDimension(2)->setRowHeight(35);

    // ---- FILA 3: Fila de ejemplo (amarillo pálido) ----
    foreach ($config['example'] as $colIdx => $exVal) {
        $colNum  = $colIdx + 1;
        $cellRef = Coordinate::stringFromColumnIndex($colNum) . '3';
        $sheet->getCell($cellRef)->setValue($exVal);
        $sheet->getStyle($cellRef)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF9C4']],
            'font' => ['italic' => true, 'color' => ['rgb' => '888888']],
        ]);
    }
    // Texto indicador al inicio de la fila ejemplo
    $sheet->getComment('A3')->getText()->createTextRun('EJEMPLO - ELIMINAR ESTA FILA ANTES DE IMPORTAR');

    // ---- Congelar filas 1 y 2 ----
    $sheet->freezePane('A3');

    // ---- Resaltar columnas obligatorias (NVale, Fecha, Cod_Producto, Descripcion, Cantidad) ----
    $colIdx = 1;
    foreach ($config['headers'] as $headerName => $desc) {
        if (strpos($desc, '(*)') === 0) {
            $cellRef = Coordinate::stringFromColumnIndex($colIdx) . '2';
            // Subrayar encabezados obligatorios con borde inferior más grueso
            $sheet->getStyle($cellRef)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB('FFCC00');
        }
        $colIdx++;
    }
}

// ============================================================
// GUARDAR ARCHIVO
// ============================================================
$outputPath = __DIR__ . '/plantilla_carga_masiva.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($outputPath);

$msg = "Plantilla generada exitosamente en:\n" . $outputPath . "\n\n"
     . "Entregar este archivo al encargado para que lo llene.\n"
     . "Una vez llenado, renombrarlo a 'datos_importacion.xlsx'\n"
     . "y colocarlo en esta misma carpeta antes de ejecutar 02_importar.php\n";

if (php_sapi_name() === 'cli') {
    echo $msg;
} else {
    echo "<pre style='font-family:monospace;background:#f5f5f5;padding:20px;'>" . htmlspecialchars($msg) . "</pre>";
}
