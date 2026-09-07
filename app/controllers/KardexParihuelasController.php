<?php
/**
 * Controlador KardexParihuelas - Módulo de Kardex de Inventario
 * Parihuelas Estándar (producto 19003730)
 */
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/Turno.php';
require_once __DIR__ . '/../../config/database.php';

class KardexParihuelasController extends Controller
{
    public function index()
    {
        try {
            if (!isset($_SESSION['user'])) {
                redirect('/login');
                return;
            }
            $this->requirePrivilegio('ver_kardex_parihuelas');

            $turnoModel = new Turno();
            $turnos = $turnoModel->getAll();
            $horaActual = date('H:i');
            $fechaHoy = date('Y-m-d');

            // Fecha de inicio del kardex (cambiar en KardexParihuela.php)
            require_once __DIR__ . '/../models/KardexParihuela.php';
            $fechaInicioKardex = KardexParihuela::FECHA_INICIO;

            $this->view('kardexparihuelas/index', [
                'titulo' => 'Kardex de Parihuelas Estándar',
                'turnos' => $turnos,
                'horaActual' => $horaActual,
                'fechaHoy' => $fechaHoy,
                'fechaInicioKardex' => $fechaInicioKardex
            ]);
        } catch (Exception $e) {
            error_log("Error en KardexParihuelasController::index - " . $e->getMessage());
            echo "Error al cargar la página: " . htmlspecialchars($e->getMessage());
        }
    }

    /**
     * AJAX: Cargar datos del kardex para una fecha y turno
     * GET /kardexparihuelas/cargarDatos?fecha=YYYY-MM-DD&turno=N
     */
    public function cargarDatos()
    {
        header('Content-Type: application/json');
        try {
            $fecha = $_GET['fecha'] ?? '';
            $turno = $_GET['turno'] ?? '';

            if (empty($fecha) || empty($turno)) {
                echo json_encode(['success' => false, 'error' => 'Fecha y turno requeridos']);
                return;
            }

            require_once __DIR__ . '/../models/KardexParihuela.php';
            $model = new KardexParihuela();

            // Buscar si ya existe un kardex para esta fecha+turno
            $kardexExistente = $model->getByFechaTurno($fecha, $turno);

            if ($kardexExistente) {
                // ✅ CORRECCIÓN: Recalcular el stock inicial desde el turno anterior
                // para detectar si hubo modificaciones posteriores en el kardex
                // del día/turno anterior que afecten el stock inicial de este registro.
                $stockInicialActual = $model->getStockFinalTurnoAnterior($fecha, $turno);

                // Comparar si el si_total guardado difiere del actual (vivo) del turno anterior
                $siTotalGuardado = (int)($kardexExistente['si_total'] ?? 0);
                $siTotalActual = (int)($stockInicialActual['si_total'] ?? 0);

                if ($siTotalGuardado !== $siTotalActual) {
                    // Hay discrepancia: actualizar los si_* en la BD
                    $actualizado = $model->actualizarStockInicial($kardexExistente['Id'], $stockInicialActual);

                    if ($actualizado) {
                        // Reflejar los cambios en el objeto que se devolverá al frontend
                        $kardexExistente['si_asperjadas'] = $stockInicialActual['si_asperjadas'] ?? 0;
                        $kardexExistente['si_aptas'] = $stockInicialActual['si_aptas'] ?? 0;
                        $kardexExistente['si_danadas'] = $stockInicialActual['si_danadas'] ?? 0;
                        $kardexExistente['si_sucias'] = $stockInicialActual['si_sucias'] ?? 0;
                        $kardexExistente['si_por_seleccionar'] = $stockInicialActual['si_por_seleccionar'] ?? 0;
                        $kardexExistente['si_lavadas'] = $stockInicialActual['si_lavadas_secadas'] ?? 0;
                        $kardexExistente['si_secas'] = $stockInicialActual['si_secas'] ?? 0;
                        $kardexExistente['si_total'] = $stockInicialActual['si_total'] ?? 0;

                        error_log("[KardexParihuelas] Stock inicial actualizado para kardex ID {$kardexExistente['Id']}: {$siTotalGuardado} → {$siTotalActual}");
                    }
                }

                // Devolver datos (con si_* actualizados si hubo cambio)
                echo json_encode([
                    'success' => true,
                    'existente' => true,
                    'kardex' => $kardexExistente
                ]);
                return;
            }

            // No existe, calcular stock inicial del turno anterior
            $stockInicial = $model->getStockFinalTurnoAnterior($fecha, $turno);

            // Obtener recepciones y despachos de la BD
            $recepciones = $model->getRecepcionesParaKardex($fecha, $turno);
            $despachos = $model->getDespachosParaKardex($fecha, $turno);

            // Calcular totales
            $totalRecepcionado = array_sum(array_column($recepciones, 'total_recepcionado'));
            $totalDespachado = array_sum(array_column($despachos, 'total_despachado'));

            echo json_encode([
                'success' => true,
                'existente' => false,
                'stockInicial' => $stockInicial,
                'recepciones' => $recepciones,
                'despachos' => $despachos,
                'totalRecepcionado' => $totalRecepcionado,
                'totalDespachado' => $totalDespachado
            ]);
        } catch (Exception $e) {
            error_log("Error en cargarDatos: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * AJAX: Guardar kardex
     * POST /kardexparihuelas/guardar
     */
    public function guardar()
    {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'error' => 'Método no permitido']);
                return;
            }

            if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
                echo json_encode(['success' => false, 'error' => 'Sesión expirada']);
                return;
            }

            // Verificar privilegio de guardado/edición
            $this->requirePrivilegio('guardar_kardex_parihuelas');

            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (stripos($contentType, 'application/json') !== false) {
                $data = json_decode(file_get_contents('php://input'), true);
            } else {
                $data = $_POST;
            }

            $data['creado_por'] = $_SESSION['user']['id'];
            $data['modificado_por'] = $_SESSION['user']['id'];

            $recepciones = $data['recepciones'] ?? [];
            $despachos = $data['despachos'] ?? [];

            require_once __DIR__ . '/../models/KardexParihuela.php';
            $model = new KardexParihuela();

            $kardexId = $model->guardar($data, $recepciones, $despachos);

            // Verificar estado actual de modificaciones para informar al usuario
            $validacion = $model->puedeModificar($kardexId);

            echo json_encode([
                'success' => true,
                'id' => $kardexId,
                'message' => 'Kardex guardado exitosamente',
                'modificaciones_realizadas' => $validacion['modificaciones'],
                'modificaciones_restantes' => $validacion['restantes']
            ]);
        } catch (Exception $e) {
            error_log("Error guardando kardex: " . $e->getMessage());
            // Detectar si es un error de límite de modificaciones
            $mensaje = $e->getMessage();
            $esLimite = (
                strpos($mensaje, 'alcanzo el limite') !== false ||
                strpos($mensaje, 'ventana de modificacion') !== false
            );
            echo json_encode([
                'success' => false,
                'error' => $mensaje,
                'limite_alcanzado' => $esLimite
            ]);
        }
    }

    /**
     * AJAX: Obtener kardex por ID
     * GET /kardexparihuelas/obtener?id=N
     */
    public function obtener()
    {
        header('Content-Type: application/json');
        try {
            $id = $_GET['id'] ?? 0;
            if (empty($id)) {
                echo json_encode(['success' => false, 'error' => 'ID requerido']);
                return;
            }

            require_once __DIR__ . '/../models/KardexParihuela.php';
            $model = new KardexParihuela();
            $kardex = $model->getById($id);

            if ($kardex) {
                echo json_encode(['success' => true, 'kardex' => $kardex]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Kardex no encontrado']);
            }
        } catch (Exception $e) {
            error_log("Error obteniendo kardex: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * AJAX: Verificar estado de modificaciones de un kardex
     * GET /kardexparihuelas/verificarModificaciones?id=N
     */
    public function verificarModificaciones()
    {
        header('Content-Type: application/json');
        try {
            $id = $_GET['id'] ?? 0;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID requerido']);
                return;
            }

            require_once __DIR__ . '/../models/KardexParihuela.php';
            $model = new KardexParihuela();
            $validacion = $model->puedeModificar($id);

            echo json_encode([
                'success' => true,
                'puede_modificar' => $validacion['puede'],
                'modificaciones' => $validacion['modificaciones'],
                'restantes' => $validacion['restantes'],
                'mensaje' => $validacion['mensaje']
            ]);
        } catch (Exception $e) {
            error_log("Error en verificarModificaciones: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Exportar el reporte del kardex a Excel (.xlsx) con PhpSpreadsheet
     * POST /kardexparihuelas/exportarExcel
     * Recibe JSON con todos los datos del formulario
     */
    public function exportarExcel()
    {
        try {
            ini_set('memory_limit', '512M');
            ini_set('max_execution_time', 300);

            require_once __DIR__ . '/../../vendor/autoload.php';

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                throw new Exception('No se recibieron datos para exportar.');
            }

            // Helper para valor seguro
            $v = function($key, $default = '0') use ($input) {
                return isset($input[$key]) ? $input[$key] : $default;
            };
            $n = function($key, $default = 0) use ($input) {
                return intval($input[$key] ?? $default);
            };

            // Extraer datos
            $fecha = $v('fecha', '');
            $fechaFormateada = $fecha ? date('d-m-Y', strtotime($fecha)) : '';
            $turnoNombre = $v('turnoNombre', '');
            $nota = $v('nota', 'Importante: Toda recepción se selecciona de inmediato previo a su ingreso al almacén.');

            // Stock Inicial
            $si = [
                'asperjadas' => $n('si_asperjadas'),
                'aptas' => $n('si_aptas'),
                'danadas' => $n('si_danadas'),
                'sucias' => $n('si_sucias'),
                'por_seleccionar' => $n('si_por_seleccionar'),
                'lavadas_secadas' => $n('si_lavadas'),
                'total' => $n('si_total')
            ];

            // Stock Final
            $sf = [
                'asperjadas' => $n('sf_asperjadas'),
                'aptas' => $n('sf_aptas'),
                'danadas' => $n('sf_danadas'),
                'sucias' => $n('sf_sucias'),
                'por_seleccionar' => $n('sf_por_seleccionar'),
                'lavadas_secadas' => $n('sf_lavadas_secadas'),
                'total' => $n('sf_total')
            ];

            // Ajustes
            $ajustes = [
                'total_parihuelas_lavadas' => $n('total_parihuelas_lavadas'),
                'clasif_aptas' => $n('clasif_aptas'),
                'clasif_danadas' => $n('clasif_danadas'),
                'clasif_relavado' => $n('clasif_relavado'),
                'clasif_total' => $n('clasif_total'),
                'reparadas_aptas' => $n('reparadas_aptas'),
                'reparadas_sucias' => $n('reparadas_sucias'),
                'reparadas_total' => $n('reparadas_total'),
                'clasificadas_aptas' => $n('clasificadas_aptas'),
                'clasificadas_danadas' => $n('clasificadas_danadas'),
                'clasificadas_sucias' => $n('clasificadas_sucias'),
                'clasificadas_total' => $n('clasificadas_total'),
                'reseleccion' => $n('reseleccion'),
                'reparacion' => $n('reparacion'),
                'asperjadas_turno' => $n('asperjadas_turno'),
                'despacho_asperjadas' => $n('despacho_asperjadas'),
                'autoservicios' => $n('autoservicios'),
                'observadas' => $n('observadas')
            ];

            // Recepciones agrupadas
            $recepciones = $input['recepcionesAgrupadas'] ?? [];
            $totalRecAptas = intval($v('totalRecAptas', '0'));
            $totalRecDanadas = intval($v('totalRecDanadas', '0'));
            $totalRecSucias = intval($v('totalRecSucias', '0'));
            $totalRecPorSel = intval($v('totalRecPorSel', '0'));
            $totalRecepcionado = intval($v('total_recepcionado', '0'));

            // Despachos agrupados
            $despachos = $input['despachosAgrupados'] ?? [];
            $totalDespachado = intval($v('total_despachado', '0'));

            // --- Crear el spreadsheet ---
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Kardex Parihuelas');
            $sheet->getSheetView()->setZoomScale(85);
            $sheet->setShowGridlines(false);

            // Helper: aplicar estilo con fill condicional
            $aplicarEstilo = function($celda, $estiloBase, $bgColor = null, $fontColor = null, $bold = null) use ($sheet) {
                $sheet->getStyle($celda)->applyFromArray($estiloBase);
                if ($bgColor) {
                    $sheet->getStyle($celda)->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setRGB($bgColor);
                }
                if ($fontColor) {
                    $sheet->getStyle($celda)->getFont()
                        ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($fontColor));
                }
                if ($bold !== null) {
                    $sheet->getStyle($celda)->getFont()->setBold($bold);
                }
            };

            // Estilos base (sin fill)
            $styleBordered = [
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
                'font' => ['size' => 9, 'name' => 'Arial']
            ];
            $styleBorderedLeft = $styleBordered;
            $styleBorderedLeft['alignment']['horizontal'] = \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT;
            $styleBorderedBold = $styleBordered;
            $styleBorderedBold['font']['bold'] = true;

            $row = 1;

            // ================================================================
            // ENCABEZADO - TÍTULO con borde (simulando el flexbox del modal)
            // ================================================================
            // Todo el bloque tiene border:1px solid #000 (como el modal)

            $sheet->mergeCells('A1:H1');
            $sheet->setCellValue('A1', 'REPORTE DE RECEPCIÓN, DESPACHOS Y STOCKS DE PARIHUELAS ESTÁNDAR');
            $aplicarEstilo('A1', [
                'font' => ['bold' => true, 'size' => 12, 'name' => 'Arial'],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                'borders' => [
                    'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]
                ]
            ]);
            // Aplicar también borde thin a todas las celdas del rango para asegurar visibilidad
            foreach (range('A', 'H') as $col) {
                $sheet->getStyle($col . '1')->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
                ]);
            }
            $sheet->getRowDimension(1)->setRowHeight(24);

            // ================================================================
            // FECHA Y TURNO (debajo del título, centrado)
            // ================================================================
            $row = 2;
            $sheet->setCellValue('A' . $row, 'Fecha: ' . $fechaFormateada);
            $sheet->setCellValue('E' . $row, 'Turno: ' . $turnoNombre);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('E' . $row)->getFont()->setBold(true)->setSize(10);
            $sheet->getRowDimension($row)->setRowHeight(16);

            // ================================================================
            // STOCK INICIAL (con rowspan: celda izquierda fusionada verticalmente)
            // ================================================================
            $row = 4;
            // "STOCK INICIAL" ocupa A(row):A(row+1) fusionado, con borde
            $sheet->mergeCells('A' . $row . ':A' . ($row + 1));
            $sheet->setCellValue('A' . $row, 'STOCK INICIAL');
            $aplicarEstilo('A' . $row, $styleBorderedBold, null, null, true);
            $sheet->getStyle('A' . $row)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle('A' . $row)->getAlignment()->setTextRotation(0);

            $colMap = ['B', 'C', 'D', 'E', 'F', 'G', 'H'];
            $siHeaders = ['Asperjadas', 'Aptas', 'Dañadas', 'Sucias', 'Por Seleccionar', 'Lavadas y Secadas', 'Total'];
            $siBgHeaders = ['D9D9D9', 'FFF3CD', 'FFE0B2', 'BBDEFB', 'C8E6C9', 'D9D9D9', 'D9D9D9'];

            // Fila superior: headers
            foreach ($colMap as $i => $col) {
                $sheet->setCellValue($col . $row, $siHeaders[$i]);
                $aplicarEstilo($col . $row, $styleBorderedBold, $siBgHeaders[$i]);
            }
            $sheet->getRowDimension($row)->setRowHeight(16);

            // Fila inferior: valores
            $row++;
            $siValues = [$si['asperjadas'], $si['aptas'], $si['danadas'], $si['sucias'], $si['por_seleccionar'], $si['lavadas_secadas'], $si['total']];
            $siBgValues = ['', 'FFF3CD', 'FFE0B2', 'BBDEFB', 'C8E6C9', '', 'E8F0FE'];
            foreach ($colMap as $i => $col) {
                $sheet->setCellValue($col . $row, $siValues[$i]);
                $aplicarEstilo($col . $row, $styleBordered, $siBgValues[$i]);
                if ($i === 6) {
                    $sheet->getStyle($col . $row)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1565C0'));
                }
            }
            // También aplicar borde a la celda A fusionada en la segunda fila
            $sheet->getStyle('A' . $row)->applyFromArray($styleBordered);
            $row++;

            // ================================================================
            // RECEPCIONES
            // ================================================================
            $row++; // espacio
            $sheet->mergeCells('A' . $row . ':H' . $row);
            $sheet->setCellValue('A' . $row, 'RESUMEN DE RECEPCIONES DE PARIHUELAS ESTÁNDAR');
            $aplicarEstilo('A' . $row, $styleBorderedBold, '374151', 'FFFFFF');
            $sheet->getRowDimension($row)->setRowHeight(18);
            $row++;

            $colsRec = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
            $recHeaders = ['Item', 'Área Origen', 'Aptas', 'Dañadas', 'Sucias', 'Por Sel.', 'Total Rec.'];
            $recBgHeaders = ['D9D9D9', 'D9D9D9', 'FFF3CD', 'FFE0B2', 'BBDEFB', 'C8E6C9', 'D9D9D9'];

            foreach ($recHeaders as $i => $label) {
                $sheet->setCellValue($colsRec[$i] . $row, $label);
                $aplicarEstilo($colsRec[$i] . $row, $styleBorderedBold, $recBgHeaders[$i]);
            }
            $sheet->getRowDimension($row)->setRowHeight(16);
            $row++;

            $itemRec = 1;
            $recBgData = ['', '', 'FFF3CD', 'FFE0B2', 'BBDEFB', 'C8E6C9', ''];
            foreach ($recepciones as $rec) {
                $vals = [$itemRec, $rec['area'], intval($rec['aptas']), intval($rec['danadas']), intval($rec['sucias']), intval($rec['porSel']), intval($rec['total'])];
                foreach ($vals as $i => $val) {
                    $sheet->setCellValue($colsRec[$i] . $row, $val);
                    $aplicarEstilo($colsRec[$i] . $row, $styleBordered, $recBgData[$i] ?? '');
                }
                $itemRec++;
                $row++;
            }

            // Total
            $totalRecVals = ['TOTAL', '', $totalRecAptas, $totalRecDanadas, $totalRecSucias, $totalRecPorSel, $totalRecepcionado];
            $sheet->mergeCells('A' . $row . ':B' . $row);
            foreach ($totalRecVals as $i => $val) {
                $sheet->setCellValue($colsRec[$i] . $row, $val);
                $aplicarEstilo($colsRec[$i] . $row, $styleBorderedBold, '000000', 'FFFFFF');
            }
            $row += 2;

            // ================================================================
            // DESPACHOS
            // ================================================================
            $sheet->mergeCells('A' . $row . ':H' . $row);
            $sheet->setCellValue('A' . $row, 'RESUMEN DE DESPACHOS DE PARIHUELAS ESTÁNDAR');
            $aplicarEstilo('A' . $row, $styleBorderedBold, '374151', 'FFFFFF');
            $sheet->getRowDimension($row)->setRowHeight(18);
            $row++;

            $desHeaders = ['Item', 'Área Destino', ' ', ' ', ' ', ' ', 'Total Desp.'];
            foreach ($desHeaders as $i => $label) {
                $sheet->setCellValue($colsRec[$i] . $row, $label);
                $aplicarEstilo($colsRec[$i] . $row, $styleBorderedBold, 'D9D9D9');
            }
            $row++;

            $itemDes = 1;
            foreach ($despachos as $des) {
                $vals = [$itemDes, $des['area'], '', '', '', '', intval($des['total'])];
                foreach ($vals as $i => $val) {
                    $sheet->setCellValue($colsRec[$i] . $row, $val);
                    $aplicarEstilo($colsRec[$i] . $row, $styleBordered);
                }
                $itemDes++;
                $row++;
            }

            // Total
            $sheet->mergeCells('A' . $row . ':F' . $row);
            $sheet->setCellValue('A' . $row, 'TOTAL');
            $sheet->setCellValue('G' . $row, $totalDespachado);
            foreach ($colsRec as $i => $col) {
                $aplicarEstilo($col . $row, $styleBorderedBold, '000000', 'FFFFFF');
            }
            $row++;

            // ================================================================
            // NOTA
            // ================================================================
            $row++;
            $sheet->mergeCells('A' . $row . ':H' . $row);
            $sheet->setCellValue('A' . $row, $nota);
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['italic' => true, 'size' => 9, 'name' => 'Arial', 'color' => ['rgb' => '666666']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT]
            ]);
            $row += 2;

            // ================================================================
            // AJUSTES (sin título de sección, igual que en el modal)
            // ================================================================

            // ---- Fila: TOTAL PARIHUELAS LAVADAS ----
            $sheet->mergeCells('A' . $row . ':C' . $row);
            $sheet->setCellValue('A' . $row, 'TOTAL PARIHUELAS LAVADAS:');
            $aplicarEstilo('A' . $row, $styleBorderedLeft);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->mergeCells('D' . $row . ':H' . $row);
            $sheet->setCellValue('D' . $row, $ajustes['total_parihuelas_lavadas']);
            $aplicarEstilo('D' . $row, $styleBordered);
            $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $row++;

            // ---- CLASIFICACIÓN LAV/SEC ----
            $sheet->mergeCells('A' . $row . ':D' . $row);
            $sheet->setCellValue('A' . $row, 'CLASIFICACIÓN DE PARIHUELAS LAVADAS Y SECADAS');
            $aplicarEstilo('A' . $row, $styleBorderedBold, 'D9D9D9');
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            $clasHeaders = ['Aptas', 'Dañadas', 'Relavado', 'Total'];
            $clasCols = ['E', 'F', 'G', 'H'];
            foreach ($clasHeaders as $i => $label) {
                $sheet->setCellValue($clasCols[$i] . $row, $label);
                $aplicarEstilo($clasCols[$i] . $row, $styleBorderedBold, 'D9D9D9');
            }
            $row++;
            // Valores Clasificación
            $sheet->mergeCells('A' . $row . ':D' . $row);
            $sheet->setCellValue('A' . $row, '');
            $aplicarEstilo('A' . $row, $styleBordered);
            $clasVals = [$ajustes['clasif_aptas'], $ajustes['clasif_danadas'], $ajustes['clasif_relavado'], $ajustes['clasif_total']];
            foreach ($clasVals as $i => $val) {
                $sheet->setCellValue($clasCols[$i] . $row, $val);
                $aplicarEstilo($clasCols[$i] . $row, $styleBordered);
                if ($i === 3) {
                    $sheet->getStyle($clasCols[$i] . $row)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1565C0'));
                }
            }
            $row++;

            // ---- REPARADAS ----
            $sheet->mergeCells('A' . $row . ':D' . $row);
            $sheet->setCellValue('A' . $row, 'TOTAL PARIHUELAS REPARADAS');
            $aplicarEstilo('A' . $row, $styleBorderedBold, 'D9D9D9');
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            $repHeaders = ['Aptas', 'Sucias', '', 'Total'];
            foreach ($repHeaders as $i => $label) {
                $sheet->setCellValue($clasCols[$i] . $row, $label);
                $aplicarEstilo($clasCols[$i] . $row, $styleBorderedBold, $i === 0 ? 'D9D9D9' : ($i === 1 ? 'D9D9D9' : ($i === 3 ? 'D9D9D9' : 'D9D9D9')));
            }
            $row++;
            $sheet->mergeCells('A' . $row . ':D' . $row);
            $sheet->setCellValue('A' . $row, '');
            $aplicarEstilo('A' . $row, $styleBordered);
            $repVals = [$ajustes['reparadas_aptas'], $ajustes['reparadas_sucias'], '', $ajustes['reparadas_total']];
            foreach ($repVals as $i => $val) {
                $sheet->setCellValue($clasCols[$i] . $row, $val);
                $aplicarEstilo($clasCols[$i] . $row, $styleBordered);
                if ($i === 3) {
                    $sheet->getStyle($clasCols[$i] . $row)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1565C0'));
                }
            }
            $row++;

            // ---- CLASIFICADAS ----
            $sheet->mergeCells('A' . $row . ':D' . $row);
            $sheet->setCellValue('A' . $row, 'PARIHUELAS CLASIFICADAS (DEL TOTAL POR SELECCIONAR)');
            $aplicarEstilo('A' . $row, $styleBorderedBold, 'D9D9D9');
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            $clasifHeaders = ['Aptas', 'Dañadas', 'Sucias', 'Total'];
            foreach ($clasifHeaders as $i => $label) {
                $sheet->setCellValue($clasCols[$i] . $row, $label);
                $aplicarEstilo($clasCols[$i] . $row, $styleBorderedBold, 'D9D9D9');
            }
            $row++;
            $sheet->mergeCells('A' . $row . ':D' . $row);
            $sheet->setCellValue('A' . $row, '');
            $aplicarEstilo('A' . $row, $styleBordered);
            $clasifVals = [$ajustes['clasificadas_aptas'], $ajustes['clasificadas_danadas'], $ajustes['clasificadas_sucias'], $ajustes['clasificadas_total']];
            foreach ($clasifVals as $i => $val) {
                $sheet->setCellValue($clasCols[$i] . $row, $val);
                $aplicarEstilo($clasCols[$i] . $row, $styleBordered);
                if ($i === 3) {
                    $sheet->getStyle($clasCols[$i] . $row)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1565C0'));
                }
            }
            $row++;

            // ---- Reselección y Reparación ----
            $sheet->mergeCells('A' . $row . ':B' . $row);
            $sheet->setCellValue('A' . $row, 'RESELECCIÓN:');
            $aplicarEstilo('A' . $row, $styleBorderedLeft);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->mergeCells('C' . $row . ':D' . $row);
            $sheet->setCellValue('C' . $row, $ajustes['reseleccion']);
            $aplicarEstilo('C' . $row, $styleBordered);
            $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->mergeCells('E' . $row . ':F' . $row);
            $sheet->setCellValue('E' . $row, 'REPARACIÓN:');
            $aplicarEstilo('E' . $row, $styleBorderedLeft);
            $sheet->getStyle('E' . $row)->getFont()->setBold(true);
            $sheet->mergeCells('G' . $row . ':H' . $row);
            $sheet->setCellValue('G' . $row, $ajustes['reparacion']);
            $aplicarEstilo('G' . $row, $styleBordered);
            $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $row++;

            // ---- Right-side items (Asperjadas, Despacho, Autoservicios, Observadas) ----
            $rightItems = [
                ['ASPERJADAS DEL TURNO:', $ajustes['asperjadas_turno']],
                ['DESPACHO ASPERJADAS:', $ajustes['despacho_asperjadas']],
                ['AUTOSERVICIOS:', $ajustes['autoservicios']],
                ['OBSERVADAS:', $ajustes['observadas']],
            ];
            foreach ($rightItems as $item) {
                $sheet->mergeCells('A' . $row . ':D' . $row);
                $sheet->setCellValue('A' . $row, $item[0]);
                $aplicarEstilo('A' . $row, $styleBorderedLeft);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $sheet->mergeCells('E' . $row . ':H' . $row);
                $sheet->setCellValue('E' . $row, $item[1]);
                $aplicarEstilo('E' . $row, $styleBordered);
                $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $row++;
            }

            $row++;

            // ================================================================
            // STOCK FINAL (igual que Stock Inicial: rowspan izquierda)
            // ================================================================
            $sheet->mergeCells('A' . $row . ':A' . ($row + 1));
            $sheet->setCellValue('A' . $row, 'STOCK FINAL');
            $aplicarEstilo('A' . $row, $styleBorderedBold, 'B71C1C', 'FFFFFF');
            $sheet->getStyle('A' . $row)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension($row)->setRowHeight(16);

            // Headers
            foreach ($colMap as $i => $col) {
                $sheet->setCellValue($col . $row, $siHeaders[$i]);
                $aplicarEstilo($col . $row, $styleBorderedBold, $siBgHeaders[$i]);
            }
            $row++;

            // Values
            $sfValues = [$sf['asperjadas'], $sf['aptas'], $sf['danadas'], $sf['sucias'], $sf['por_seleccionar'], $sf['lavadas_secadas'], $sf['total']];
            $sfBgValues = ['', 'FFF3CD', 'FFE0B2', 'BBDEFB', 'C8E6C9', '', 'FCE4EC'];
            foreach ($colMap as $i => $col) {
                $sheet->setCellValue($col . $row, $sfValues[$i]);
                $aplicarEstilo($col . $row, $styleBordered, $sfBgValues[$i]);
                if ($i === 6) {
                    $sheet->getStyle($col . $row)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('B71C1C'));
                }
            }
            $sheet->getStyle('A' . $row)->applyFromArray($styleBordered);
            $row += 3;

            // ================================================================
            // FIRMA
            // ================================================================
            $sheet->mergeCells('C' . $row . ':F' . $row);
            $sheet->setCellValue('C' . $row, '__________________________________');
            $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $row++;
            $sheet->mergeCells('C' . $row . ':F' . $row);
            $sheet->setCellValue('C' . $row, 'NOMBRES Y FIRMA DEL RESPONSABLE');
            $sheet->getStyle('C' . $row)->applyFromArray([
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                'font' => ['size' => 9, 'name' => 'Arial']
            ]);

            // --- Anchos de columna ---
            $sheet->getColumnDimension('A')->setWidth(14);
            $sheet->getColumnDimension('B')->setWidth(18);
            $sheet->getColumnDimension('C')->setWidth(14);
            $sheet->getColumnDimension('D')->setWidth(14);
            $sheet->getColumnDimension('E')->setWidth(14);
            $sheet->getColumnDimension('F')->setWidth(16);
            $sheet->getColumnDimension('G')->setWidth(14);
            $sheet->getColumnDimension('H')->setWidth(14);

            // --- Exportar ---
            $fechaArchivo = date('Ymd');
            $turnoLimpio = preg_replace('/[^a-zA-Z0-9]/', '_', $turnoNombre);
            $fileName = 'Kardex_Parihuelas_' . $fechaArchivo . '_' . $turnoLimpio . '.xlsx';

            while (ob_get_level()) { ob_end_clean(); }

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Cache-Control: max-age=0');

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;

        } catch (Exception $e) {
            while (ob_get_level()) { ob_end_clean(); }
            header('Content-Type: text/html; charset=utf-8');
            echo '<div style="padding:20px;background:#f8d7da;border:1px solid #f5c6cb;border-radius:4px;color:#721c24;margin:20px;font-family:Arial,sans-serif;">';
            echo '<h3>Error al exportar Excel</h3>';
            echo '<p><strong>Mensaje:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p><strong>Archivo:</strong> ' . htmlspecialchars($e->getFile()) . ' línea ' . $e->getLine() . '</p>';
            echo '</div>';
            error_log('Error en KardexParihuelasController::exportarExcel - ' . $e->getMessage());
            exit;
        }
    }
}
