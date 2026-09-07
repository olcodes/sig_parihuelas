<?php
// Importar clases de PHPSpreadsheet y helper para exportación
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
require_once __DIR__ . '/../helpers/excel_export.php';

class ReportesExternosController extends Controller {
    public function index() {
        $this->view('reportes/despachosexternos');
    }
    // Endpoint para obtener despachos externos (AJAX)
    public function obtenerDespachosExternos() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['data' => [], 'totalPaginas' => 1], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $params = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('JSON inválido: ' . json_last_error_msg());
            }
            
            $pagina = isset($params['pagina']) ? max(1, (int)$params['pagina']) : 1;
            $filtros = $params['filtros'] ?? [];
            if (isset($filtros['NVale']) && ($filtros['NVale'] !== '')) {
                $filtros['N° Vale'] = $filtros['NVale'];
            }

            // Paginación plana: 20 filas (productos) por página
            $LIMIT_FLAT = 20;
            $flatOffset = ($pagina - 1) * $LIMIT_FLAT;

            $model = $this->model('DespachoExterno');
            if (!method_exists($model, 'getReporte')) {
                echo json_encode(['data' => [], 'totalPaginas' => 1, 'success' => false, 'error' => 'getReporte no disponible en DespachoExterno'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            // Obtener TODOS los vales que coinciden con filtros para aplanar correctamente
            $result = $model->getReporte($filtros, 10000, 0);
            
            // Reemplazar ids por textos legibles usando consultas BATCH (NO N+1)
            $data = $result['data'] ?? [];
            
            if (!empty($data)) {
                // Obtener la conexión PDO directamente para consultas batch
                $db = \Database::getInstance()->getConnection();
                
                // 1. BATCH: Resolver Destinos
                $destinoIds = array_unique(array_filter(array_map(function($r) {
                    $id = $r['DestinoOriginal'] ?? $r['Destino'] ?? $r['destino'] ?? null;
                    return (is_numeric($id) && $id > 0) ? (int)$id : null;
                }, $data)));
                // También capturar valores de texto (para registros que tienen nombre en lugar de ID)
                $destinoTextValues = [];
                foreach ($data as $r) {
                    $val = $r['DestinoOriginal'] ?? $r['Destino'] ?? $r['destino'] ?? null;
                    if ($val !== null && $val !== '' && !is_numeric($val)) {
                        $destinoTextValues[] = trim((string)$val);
                    }
                }
                $destinoTextValues = array_unique($destinoTextValues);
                // Detectar tabla de destino correcta
                $destinoTable = 'destino';
                try {
                    $stmtDetect = $db->prepare("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND (table_name LIKE '%destin%' OR table_name LIKE '%cliente%') LIMIT 1");
                    $stmtDetect->execute();
                    $detected = $stmtDetect->fetchColumn();
                    if ($detected) $destinoTable = $detected;
                } catch (Exception $__) {}
                error_log('[obtenerDespachosExternos] Tabla destino detectada: ' . $destinoTable);

                $cacheDestino = [];
                // Resolver texto a nombre de empresa
                if (!empty($destinoTextValues)) {
                    $textTables = [$destinoTable, 'destino', 'destinos', 'clientes_externos', 'clientesexternos'];
                    $textTables = array_unique($textTables);
                    foreach ($textTables as $tbl) {
                        try {
                            foreach ($destinoTextValues as $txtVal) {
                                $stmtTxt = $db->prepare("SELECT Id, COALESCE(Empresa, '') as nombre FROM $tbl WHERE LOWER(Empresa) = LOWER(?) OR LOWER(Empresa) LIKE LOWER(?) LIMIT 1");
                                $stmtTxt->execute([$txtVal, '%' . $txtVal . '%', $txtVal, $txtVal]);
                                $rowTxt = $stmtTxt->fetch(PDO::FETCH_ASSOC);
                                if ($rowTxt && !empty($rowTxt['nombre'])) {
                                    $cacheDestino[$txtVal] = $rowTxt['nombre'];
                                    break 2;
                                }
                            }
                        } catch (Exception $__) {}
                    }
                    // Fallback para texto no resuelto: usar el valor original
                    foreach ($destinoTextValues as $txtVal) {
                        if (!isset($cacheDestino[$txtVal])) {
                            $cacheDestino[$txtVal] = $txtVal;
                        }
                    }
                }
                if (!empty($destinoIds)) {
                    $allDestTables = [$destinoTable, 'destino', 'destinos', 'clientes_externos', 'clientesexternos'];
                    $allDestTables = array_unique($allDestTables);
                    foreach ($allDestTables as $tbl) {
                        try {
                            $placeholders = implode(',', array_fill(0, count($destinoIds), '?'));
                            $stmt = $db->prepare("SELECT Id, COALESCE(Empresa, '') as nombre FROM $tbl WHERE Id IN ($placeholders)");
                            $stmt->execute(array_values($destinoIds));
                            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $d) {
                                if (!isset($cacheDestino[$d['Id']])) {
                                    $cacheDestino[$d['Id']] = $d['nombre'] ?: ('Destino #' . $d['Id']);
                                }
                            }
                        } catch (Exception $__) {}
                    }
                    // Fallback para los que aún no se resolvieron
                    foreach ($destinoIds as $id) {
                        if (!isset($cacheDestino[$id])) {
                            $cacheDestino[$id] = 'Destino #' . $id;
                        }
                    }
                }
                
                // 2. BATCH: Resolver Choferes
                $choferIds = array_unique(array_filter(array_map(function($r) {
                    $id = $r['ChoferOriginal'] ?? $r['Chofer'] ?? $r['chofer'] ?? null;
                    return (is_numeric($id) && $id > 0) ? (int)$id : null;
                }, $data)));
                $cacheChofer = [];
                if (!empty($choferIds)) {
                    $placeholders = implode(',', array_fill(0, count($choferIds), '?'));
                    try {
                        $stmt = $db->prepare("SELECT Id, COALESCE(ApellidosNombres, Nombres, '') as nombre FROM choferes WHERE Id IN ($placeholders)");
                        $stmt->execute(array_values($choferIds));
                        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
                            $cacheChofer[$c['Id']] = $c['nombre'] ?: ('Chofer #' . $c['Id']);
                        }
                    } catch (Exception $__) {}
                    foreach ($choferIds as $id) {
                        if (!isset($cacheChofer[$id])) $cacheChofer[$id] = 'Chofer #' . $id;
                    }
                }
                
                // 3. BATCH: Resolver Transportistas
                $transIds = array_unique(array_filter(array_map(function($r) {
                    $id = $r['TransportistaOriginal'] ?? $r['Transportista'] ?? $r['transportista'] ?? null;
                    return (is_numeric($id) && $id > 0) ? (int)$id : null;
                }, $data)));
                $cacheTransportista = [];
                if (!empty($transIds)) {
                    $placeholders = implode(',', array_fill(0, count($transIds), '?'));
                    try {
                        $stmt = $db->prepare("SELECT Id, COALESCE(Empresa, RUC, '') as nombre FROM transportistas WHERE Id IN ($placeholders)");
                        $stmt->execute(array_values($transIds));
                        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $t) {
                            $cacheTransportista[$t['Id']] = $t['nombre'] ?: ('Transportista #' . $t['Id']);
                        }
                    } catch (Exception $__) {}
                    foreach ($transIds as $id) {
                        if (!isset($cacheTransportista[$id])) $cacheTransportista[$id] = 'Transportista #' . $id;
                    }
                }
                
                // 4. BATCH: Resolver Responsables (Despachador)
                $respIds = array_unique(array_filter(array_map(function($r) {
                    $id = $r['DespachadorTexto'] ?? $r['Despachador'] ?? $r['despachador'] ?? null;
                    return (is_numeric($id) && $id > 0) ? (int)$id : null;
                }, $data)));
                $cacheResponsable = [];
                if (!empty($respIds)) {
                    $placeholders = implode(',', array_fill(0, count($respIds), '?'));
                    try {
                        $stmt = $db->prepare("SELECT Id, COALESCE(NombresApellidos, '') as nombre FROM responsables WHERE Id IN ($placeholders)");
                        $stmt->execute(array_values($respIds));
                        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                            $cacheResponsable[$r['Id']] = $r['nombre'] ?: ('Responsable #' . $r['Id']);
                        }
                    } catch (Exception $__) {}
                    foreach ($respIds as $id) {
                        if (!isset($cacheResponsable[$id])) $cacheResponsable[$id] = 'Responsable #' . $id;
                    }
                }
                
                // Asignar valores resueltos a cada fila
                foreach ($data as &$row) {
                    $destKey = $row['DestinoOriginal'] ?? $row['Destino'] ?? $row['destino'] ?? null;
                    if ($destKey !== null && $destKey !== '' && !is_array($destKey)) {
                        $resolved = $cacheDestino[(int)$destKey] ?? $destKey;
                        $row['Destino'] = $resolved;
                        $row['DestinoTexto'] = $resolved;
                    }
                    $chKey = $row['ChoferOriginal'] ?? $row['Chofer'] ?? $row['chofer'] ?? null;
                    if ($chKey !== null && $chKey !== '' && !is_array($chKey)) {
                        $row['Chofer'] = $cacheChofer[(int)$chKey] ?? $chKey;
                    }
                    $trKey = $row['TransportistaOriginal'] ?? $row['Transportista'] ?? $row['transportista'] ?? null;
                    if ($trKey !== null && $trKey !== '' && !is_array($trKey)) {
                        $row['Transportista'] = $cacheTransportista[(int)$trKey] ?? $trKey;
                    }
                    $rpKey = $row['DespachadorTexto'] ?? $row['Despachador'] ?? $row['despachador'] ?? null;
                    if ($rpKey !== null && $rpKey !== '' && !is_array($rpKey)) {
                        $row['Despachador'] = $cacheResponsable[(int)$rpKey] ?? $rpKey;
                    }
                }
                unset($row);
            }

            // Formatear N° Vale como 6 dígitos
            foreach ($data as &$drow) {
                $raw = $drow['NVale'] ?? $drow['nvale'] ?? '';
                $digits = preg_replace('/\D/', '', (string)$raw);
                $drow['NVale'] = $digits !== '' ? str_pad($digits, 6, '0', STR_PAD_LEFT) : (string)$raw;
            }
            unset($drow);

            // FLATTEN: Convertir datos jerárquicos (vale con Detalles[]) a planos (una fila por producto)
            $datosPlanos = [];
            foreach ($data as $row) {
                $detalles = $row['Detalles'] ?? [];
                unset($row['Detalles']);

                if (empty($detalles)) {
                    $row['CodigoProducto'] = '';
                    $row['NombreProducto'] = '';
                    $row['UnidadMedida'] = '';
                    $row['Cantidad'] = '';
                    $row['ComentariosProducto'] = '';
                    $datosPlanos[] = $row;
                } else {
                    foreach ($detalles as $prod) {
                        $filaPlana = $row;
                        $filaPlana['CodigoProducto'] = $prod['Codigo'] ?? '';
                        $filaPlana['NombreProducto'] = $prod['Producto'] ?? '';
                        $filaPlana['UnidadMedida'] = $prod['UnidadMedida'] ?? '';
                        $filaPlana['Cantidad'] = $prod['Cantidad'] ?? '';
                        $filaPlana['ComentariosProducto'] = $prod['Comentarios'] ?? '';
                        $datosPlanos[] = $filaPlana;
                    }
                }
            }

            // COMPUTAR VALORES ÚNICOS para filtros de columna (desde TODOS los datos, antes de filtrar)
            $uniqueValues = [];
            $camposFiltrables = ['NVale','Fecha','Turno','Despachador','Destino','RUC','Direccion','Chofer','Brevete','Transportista','RUC_Transportista','Placa_Tracto','Constancia_Inscripcion','Placa_Carreta','Constancia_Inscripcion_2','GR','CodigoProducto','NombreProducto','UnidadMedida','Cantidad','ComentariosProducto','Estado'];
            try {
                foreach ($datosPlanos as $row) {
                    foreach ($camposFiltrables as $campo) {
                        if (!isset($row[$campo])) continue;
                        $valStr = (string)$row[$campo];
                        if ($valStr !== '') {
                            if (!isset($uniqueValues[$campo])) {
                                $uniqueValues[$campo] = [];
                            }
                            $uniqueValues[$campo][$valStr] = true;
                        }
                    }
                }
                foreach ($uniqueValues as $campo => $valsMap) {
                    $vals = array_keys($valsMap);
                    $vals = array_map('strval', $vals);
                    usort($vals, function($a, $b) {
                        return is_numeric($a) && is_numeric($b) ? ($a - $b) : strcmp($a, $b);
                    });
                    $uniqueValues[$campo] = array_values($vals);
                }
            } catch (Exception $e) {
                error_log('[uniqueValues] Error: ' . $e->getMessage());
                $uniqueValues = [];
            }

            // APLICAR FILTROS DE COLUMNA (tipo Excel) sobre datos planos
            $filtrosColumna = $params['filtrosColumna'] ?? [];
            if (!empty($filtrosColumna) && is_array($filtrosColumna)) {
                $datosPlanos = array_filter($datosPlanos, function($row) use ($filtrosColumna) {
                    foreach ($filtrosColumna as $campo => $valoresSeleccionados) {
                        if (!is_array($valoresSeleccionados)) continue;
                        $valorCelda = (string)($row[$campo] ?? '');
                        if ($campo === 'NVale') {
                            $valorCelda = str_pad(preg_replace('/\D/', '', $valorCelda), 6, '0', STR_PAD_LEFT);
                            $valoresSeleccionados = array_map(function($v) {
                                return str_pad(preg_replace('/\D/', '', (string)$v), 6, '0', STR_PAD_LEFT);
                            }, $valoresSeleccionados);
                        }
                        if (!in_array($valorCelda, $valoresSeleccionados, true)) {
                            return false;
                        }
                    }
                    return true;
                });
                $datosPlanos = array_values($datosPlanos);
            }

            // PAGINACIÓN PLANA: Aplicar offset y límite sobre datos planos
            $totalFlattened = count($datosPlanos);
            $totalPaginas = max(1, ceil($totalFlattened / $LIMIT_FLAT));
            $datosPlanos = array_slice($datosPlanos, $flatOffset, $LIMIT_FLAT);

            echo json_encode([
                'data' => $datosPlanos,
                'totalPaginas' => $totalPaginas,
                'totalRegistros' => $totalFlattened,
                'uniqueValues' => $uniqueValues,
                'success' => true
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            
        } catch (Exception $e) {
            error_log('[ERROR obtenerDespachosExternos] Exception: ' . $e->getMessage());
            echo json_encode(['data' => [], 'totalPaginas' => 1, 'success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // Endpoint para actualizar un despacho externo desde el reporte (edición en grilla)
    public function actualizarDespachoReporte() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        try {
            $raw = file_get_contents('php://input');
            $params = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo json_encode(['success' => false, 'message' => 'JSON inválido: ' . json_last_error_msg()]);
                exit;
            }

            $id = isset($params['id']) ? (int)$params['id'] : null;
            $despacho = $params['despacho'] ?? [];
            $productos = $params['productos'] ?? [];

            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID de despacho requerido']);
                exit;
            }

            $model = $this->model('DespachoExterno');

            // Si vienen datos de cabecera, actualizar
            if (!empty($despacho)) {
                $res = $model->actualizarDespacho($id, $despacho);
                if ($res === false) {
                    echo json_encode(['success' => false, 'message' => 'Error al actualizar cabecera del despacho']);
                    exit;
                }
            }

            // Actualizar productos si vienen
            if (is_array($productos)) {
                try {
                    $ok = $model->actualizarProductosDespacho($id, $productos);
                    if ($ok) {
                        echo json_encode(['success' => true, 'message' => 'Actualización exitosa']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Error al actualizar productos']);
                    }
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => 'Error al actualizar productos: ' . $e->getMessage()]);
                }
            } else {
                // Si no hay productos, solo respondemos éxito si la cabecera fue actualizada
                echo json_encode(['success' => true, 'message' => 'Cabecera actualizada']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // Endpoint para anular un despacho externo (desde el reporte)
    public function anularDespachoExterno() {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        try {
            $raw = file_get_contents('php://input');
            $params = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // aceptar también form-data
                $params = $_POST;
            }
            $id = isset($params['id']) ? (int)$params['id'] : null;
            $motivo = isset($params['motivo']) ? trim($params['motivo']) : '';
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID de despacho requerido'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $userId = $_SESSION['user']['id'] ?? null;
            $model = $this->model('DespachoExterno');
            if (!method_exists($model, 'anular')) {
                echo json_encode(['success' => false, 'message' => 'Operación no soportada en el modelo'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $model->anular($id, $userId, $motivo);
            echo json_encode(['success' => true, 'message' => 'Despacho anulado correctamente'], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // Endpoint para reactivar un despacho externo (desde el reporte)
    public function reactivarDespachoExterno() {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        try {
            $raw = file_get_contents('php://input');
            $params = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $params = $_POST;
            }
            $id = isset($params['id']) ? (int)$params['id'] : null;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID de despacho requerido'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $userId = $_SESSION['user']['id'] ?? null;
            $model = $this->model('DespachoExterno');
            if (!method_exists($model, 'reactivar')) {
                echo json_encode(['success' => false, 'message' => 'Operación no soportada en el modelo'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $model->reactivar($id, $userId);
            echo json_encode(['success' => true, 'message' => 'Despacho reactivado correctamente'], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // Endpoint para obtener listas de selectores (áreas, subáreas, productos, responsables, recepcionistas, turnos)
    public function obtenerDatosSelectores() {
        header('Content-Type: application/json');
        try {
            $areaModel = $this->model('Area');
            $areas = $areaModel->getAllForSelect();

            $subareaModel = $this->model('Subarea');
            $subareas = $subareaModel->getAllForSelect();

            $responsableModel = $this->model('Responsable');
            $responsables = $responsableModel->getAllForSelect();

            $recepcionistaModel = $this->model('Recepcionista');
            $recepcionistas = $recepcionistaModel->getAllForSelect();

            $productoModel = $this->model('Producto');
            $productos = $productoModel->getAllForSelect();

            $turnoModel = $this->model('Turno');
            $turnos = $turnoModel->getAllForSelect();

            // Agregar datos adicionales utilizados por la vista de despachos (destinos, choferes, transportistas)
            $destinoModel = $this->model('Destino');
            // Intentar obtener todos los destinos (limite alto)
            try { $destinos = method_exists($destinoModel,'getPaginated') ? $destinoModel->getPaginated(10000,0) : []; } catch(Exception $e){ $destinos = []; }

            $choferModel = $this->model('Chofer');
            try { $choferes = method_exists($choferModel,'getPaginated') ? $choferModel->getPaginated(10000,0) : (method_exists($choferModel,'getAll') ? $choferModel->getAll() : []); } catch(Exception $e){ $choferes = []; }

            $transportistaModel = $this->model('Transportista');
            try { $transportistas = method_exists($transportistaModel,'getPaginated') ? $transportistaModel->getPaginated(10000,0) : (method_exists($transportistaModel,'getAll') ? $transportistaModel->getAll() : []); } catch(Exception $e){ $transportistas = []; }

            echo json_encode([
                'success' => true,
                'data' => [
                    'areas' => $areas,
                    'subareas' => $subareas,
                    'responsables' => $responsables,
                    'recepcionistas' => $recepcionistas,
                    'productos' => $productos,
                    'destinos' => $destinos,
                    'choferes' => $choferes,
                    'transportistas' => $transportistas,
                    'turnos' => $turnos
                ]
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // Exportar despachos externos a Excel (una fila por producto)
    public function exportarExcelDespachosExternos() {
        try {
            ini_set('memory_limit', '1024M');
            ini_set('max_execution_time', 600);

            require_once __DIR__ . '/../../vendor/autoload.php';

            // Set MySQL timeout
            try {
                $db = \Database::getInstance()->getConnection();
                $db->exec("SET SESSION wait_timeout = 600");
                $db->exec("SET SESSION max_execution_time = 600000");
            } catch (Exception $e) {
                error_log("No se pudieron ajustar timeouts MySQL: " . $e->getMessage());
            }

            // Aceptar tanto GET como POST
            $filtros = [];
            $filtrosColumna = [];
            $paginaActual = 1;
            $exportarTodo = false;
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                $filtros = $input['filtros'] ?? [];
                $filtrosColumna = $input['filtrosColumna'] ?? [];
                $paginaActual = isset($input['paginaActual']) ? max(1, (int)$input['paginaActual']) : 1;
                $exportarTodo = !empty($input['exportarTodo']);
            } else {
                // Fallback GET
                $fechaDesde = !empty($_GET['fechaDesde']) ? $_GET['fechaDesde'] : '';
                $fechaHasta = !empty($_GET['fechaHasta']) ? $_GET['fechaHasta'] : '';
                $fecha = !empty($_GET['Fecha']) ? $_GET['Fecha'] : '';
                $destino = $_GET['Destino'] ?? $_GET['destino'] ?? $_GET['destinoId'] ?? '';
                $chofer = $_GET['Chofer'] ?? $_GET['chofer'] ?? $_GET['choferId'] ?? '';
                $transportista = $_GET['Transportista'] ?? $_GET['transportista'] ?? $_GET['transportistaId'] ?? '';
                $turno = $_GET['Turno'] ?? $_GET['turno'] ?? $_GET['turnoId'] ?? '';

                $filtros['fechaDesde'] = $fechaDesde;
                $filtros['fechaHasta'] = $fechaHasta;
                if (!empty($fecha)) $filtros['Fecha'] = $fecha;
                if ($destino !== '') $filtros['Destino'] = $destino;
                if ($chofer !== '') $filtros['Chofer'] = $chofer;
                if ($transportista !== '') $filtros['Transportista'] = $transportista;
                if ($turno !== '') $filtros['turnoId'] = $turno;

                if (empty($filtros['fechaDesde']) && empty($filtros['fechaHasta']) && empty($filtros['Fecha'])) {
                    $filtros['fechaDesde'] = date('Y-m-01');
                    $filtros['fechaHasta'] = date('Y-m-t');
                }
                $filtros = array_filter($filtros, function($v) { return $v !== ''; });
            }

            $model = $this->model('DespachoExterno');
            if (!method_exists($model, 'getReporte')) {
                throw new Exception('getReporte no disponible en DespachoExterno');
            }

            // Obtener datos según lógica de exportación
            $LIMIT_FLAT = 20;
            if ($exportarTodo) {
                $exportLimit = 50000;
                $exportOffset = 0;
            } else {
                $exportLimit = $LIMIT_FLAT;
                $exportOffset = ($paginaActual - 1) * $LIMIT_FLAT;
            }
            $result = $model->getReporte($filtros, $exportLimit, $exportOffset);
            $datosOriginales = $result['data'] ?? [];

            if (empty($datosOriginales)) {
                throw new Exception('No se encontraron datos para exportar con los filtros seleccionados.');
            }

            // FLATTEN: convertir datos jerárquicos a planos (una fila por producto)
            $datosPlanos = [];
            foreach ($datosOriginales as $row) {
                $detalles = $row['Detalles'] ?? [];
                unset($row['Detalles']);
                if (empty($detalles)) {
                    $row['CodigoProducto'] = '';
                    $row['NombreProducto'] = '';
                    $row['UnidadMedida'] = '';
                    $row['Cantidad'] = '';
                    $row['ComentariosProducto'] = '';
                    $datosPlanos[] = $row;
                } else {
                    foreach ($detalles as $prod) {
                        $filaPlana = $row;
                        $filaPlana['CodigoProducto'] = $prod['Codigo'] ?? '';
                        $filaPlana['NombreProducto'] = $prod['Producto'] ?? '';
                        $filaPlana['UnidadMedida'] = $prod['UnidadMedida'] ?? '';
                        $filaPlana['Cantidad'] = $prod['Cantidad'] ?? '';
                        $filaPlana['ComentariosProducto'] = $prod['Comentarios'] ?? '';
                        $datosPlanos[] = $filaPlana;
                    }
                }
            }

            // APLICAR FILTROS DE COLUMNA
            if (!empty($filtrosColumna) && is_array($filtrosColumna)) {
                $datosPlanos = array_filter($datosPlanos, function($row) use ($filtrosColumna) {
                    foreach ($filtrosColumna as $campo => $valoresSeleccionados) {
                        if (!is_array($valoresSeleccionados)) continue;
                        $valorCelda = (string)($row[$campo] ?? '');
                        if ($campo === 'NVale') {
                            $valorCelda = str_pad(preg_replace('/\D/', '', $valorCelda), 6, '0', STR_PAD_LEFT);
                            $valoresSeleccionados = array_map(function($v) {
                                return str_pad(preg_replace('/\D/', '', (string)$v), 6, '0', STR_PAD_LEFT);
                            }, $valoresSeleccionados);
                        }
                        if (!in_array($valorCelda, $valoresSeleccionados, true)) return false;
                    }
                    return true;
                });
                $datosPlanos = array_values($datosPlanos);
            }

            if (empty($datosPlanos)) {
                throw new Exception('No se encontraron datos para exportar con los filtros seleccionados.');
            }

            // Mapear ids a textos legibles
            try {
                $destinoModel = $this->model('Destino');
                $clienteExternoModel = $this->model('ClienteExterno');
                $choferModel = $this->model('Chofer');
                $transportistaModel = $this->model('Transportista');
                $responsableModel = $this->model('Responsable');
                $turnoModel = $this->model('Turno');
                $cacheDestino = []; $cacheChofer = []; $cacheTransportista = []; $cacheResponsable = []; $cacheTurno = [];
                $allTurnos = null;

                foreach ($datosPlanos as &$row) {
                    $turnoId = $row['Turno'] ?? $row['turno'] ?? null;
                    if ($turnoId !== null && $turnoId !== '' && !is_array($turnoId)) {
                        $kt = (string)$turnoId;
                        if (!isset($cacheTurno[$kt])) {
                            try { if ($allTurnos === null && method_exists($turnoModel,'getAllForSelect')) { $allTurnos = $turnoModel->getAllForSelect(); foreach ($allTurnos as $t) { $cacheTurno[(string)$t['Id']] = $t['Turno']; } } } catch(Exception $__t) {}
                            if (!isset($cacheTurno[$kt])) $cacheTurno[$kt] = (string)$turnoId;
                        }
                        $row['Turno'] = $cacheTurno[$kt];
                    }
                    $destId = $row['Destino'] ?? $row['DestinoOriginal'] ?? null;
                    if ($destId !== null && $destId !== '' && !is_array($destId)) {
                        $key = (string)$destId;
                        if (!isset($cacheDestino[$key])) {
                            if (is_numeric($destId)) { try { $d = $destinoModel->getById((int)$destId); $cacheDestino[$key] = $d ? ($d['Empresa'] ?? $d['Destino'] ?? (string)$destId) : (string)$destId; } catch(Exception $__d) { $cacheDestino[$key] = (string)$destId; } }
                            else { $cacheDestino[$key] = $destId; }
                        }
                        $row['Destino'] = $cacheDestino[$key];
                    }
                    $choferId = $row['Chofer'] ?? null;
                    if ($choferId !== null && $choferId !== '' && !is_array($choferId)) {
                        $k2 = (string)$choferId;
                        if (!isset($cacheChofer[$k2])) { try { if (is_numeric($choferId)) { $c = $choferModel->getById((int)$choferId); $cacheChofer[$k2] = $c ? ($c['ApellidosNombres'] ?? $c['Nombres'] ?? (string)$choferId) : (string)$choferId; } else { $cacheChofer[$k2] = $choferId; } } catch(Exception $__c) { $cacheChofer[$k2] = (string)$choferId; } }
                        $row['Chofer'] = $cacheChofer[$k2];
                    }
                    $transId = $row['Transportista'] ?? null;
                    if ($transId !== null && $transId !== '' && !is_array($transId)) {
                        $kt = (string)$transId;
                        if (!isset($cacheTransportista[$kt])) { try { if (is_numeric($transId)) { $t = $transportistaModel->getById((int)$transId); $cacheTransportista[$kt] = $t ? ($t['Empresa'] ?? $t['RUC'] ?? (string)$transId) : (string)$transId; } else { $cacheTransportista[$kt] = $transId; } } catch(Exception $__t) { $cacheTransportista[$kt] = (string)$transId; } }
                        $row['Transportista'] = $cacheTransportista[$kt];
                    }
                    $respId = $row['Despachador'] ?? $row['DespachadorTexto'] ?? null;
                    if ($respId !== null && $respId !== '' && !is_array($respId)) {
                        $kr = (string)$respId;
                        if (!isset($cacheResponsable[$kr])) { try { if (is_numeric($respId)) { $r = $responsableModel->getById((int)$respId); $cacheResponsable[$kr] = $r ? ($r['NombresApellidos'] ?? '') : (string)$respId; } else { $cacheResponsable[$kr] = $respId; } } catch(Exception $__r) { $cacheResponsable[$kr] = (string)$respId; } }
                        $row['Despachador'] = $cacheResponsable[$kr];
                    }
                }
                unset($row);
            } catch (Exception $__map) { error_log('Error al mapear campos para export: ' . $__map->getMessage()); }

            // Crear Excel con datos planos
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Despachos Externos');

            $cabeceras = [
                'A1' => 'N° VALE', 'B1' => 'FECHA', 'C1' => 'TURNO', 'D1' => 'DESPACHADOR',
                'E1' => 'DESTINO', 'F1' => 'RUC', 'G1' => 'DIRECCIÓN', 'H1' => 'CHOFER',
                'I1' => 'BREVETE', 'J1' => 'TRANSPORTISTA', 'K1' => 'RUC TRANSPORTISTA',
                'L1' => 'PLACA TRACTO', 'M1' => 'CONSTANCIA INSCRIPCION TRACTO',
                'N1' => 'PLACA CARRETA', 'O1' => 'CONSTANCIA INSCRIPCION CARRETA',
                'P1' => 'GUIA REMISION', 'Q1' => 'ESTADO',
                'R1' => 'CÓDIGO', 'S1' => 'PRODUCTO', 'T1' => 'UNIDAD MEDIDA', 'U1' => 'CANTIDAD', 'V1' => 'COMENTARIOS'
            ];
            foreach ($cabeceras as $celda => $valor) { $sheet->setCellValue($celda, $valor); }

            $styleHeader = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ];
            $sheet->getStyle('A1:V1')->applyFromArray($styleHeader);
            $sheet->getRowDimension(1)->setRowHeight(20);

            $filaExcel = 2;
            foreach ($datosPlanos as $row) {
                $fechaFormateada = isset($row['Fecha']) && $row['Fecha'] ? date('d/m/Y', strtotime($row['Fecha'])) : '';
                $nroDigits = preg_replace('/\D/', '', (string)($row['NVale'] ?? ''));
                $nroVale = $nroDigits !== '' ? str_pad($nroDigits, 6, '0', STR_PAD_LEFT) : (string)($row['NVale'] ?? '');
                $sheet->setCellValue('A' . $filaExcel, $nroVale);
                $sheet->setCellValue('B' . $filaExcel, $fechaFormateada);
                $sheet->setCellValue('C' . $filaExcel, $row['Turno'] ?? '');
                $sheet->setCellValue('D' . $filaExcel, $row['Despachador'] ?? '');
                $sheet->setCellValue('E' . $filaExcel, $row['Destino'] ?? '');
                $sheet->setCellValue('F' . $filaExcel, $row['RUC'] ?? '');
                $sheet->setCellValue('G' . $filaExcel, $row['Direccion'] ?? '');
                $sheet->setCellValue('H' . $filaExcel, $row['Chofer'] ?? '');
                $sheet->setCellValue('I' . $filaExcel, $row['Brevete'] ?? '');
                $sheet->setCellValue('J' . $filaExcel, $row['Transportista'] ?? '');
                $sheet->setCellValue('K' . $filaExcel, $row['RUC_Transportista'] ?? '');
                $sheet->setCellValue('L' . $filaExcel, $row['Placa_Tracto'] ?? '');
                $sheet->setCellValue('M' . $filaExcel, $row['Constancia_Inscripcion'] ?? '');
                $sheet->setCellValue('N' . $filaExcel, $row['Placa_Carreta'] ?? '');
                $sheet->setCellValue('O' . $filaExcel, $row['Constancia_Inscripcion_2'] ?? '');
                $sheet->setCellValue('P' . $filaExcel, $row['GR'] ?? '');
                $sheet->setCellValue('Q' . $filaExcel, $row['Estado'] ?? '');
                $sheet->setCellValue('R' . $filaExcel, $row['CodigoProducto'] ?? '');
                $sheet->setCellValue('S' . $filaExcel, $row['NombreProducto'] ?? '');
                $sheet->setCellValue('T' . $filaExcel, $row['UnidadMedida'] ?? '');
                $sheet->setCellValue('U' . $filaExcel, $row['Cantidad'] ?? '');
                $sheet->setCellValue('V' . $filaExcel, $row['ComentariosProducto'] ?? '');
                $filaExcel++;
            }

            foreach (range('A', 'V') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }
            $lastRow = $filaExcel - 1;
            if ($lastRow >= 1) {
                $sheet->getStyle('A1:V' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                for ($row = 2; $row <= $lastRow; $row++) {
                    if ($row % 2 == 0) {
                        $sheet->getStyle('A'.$row.':V'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F5F5F5');
                    }
                }
            }
            $sheet->setShowGridlines(false);

            $writer = new Xlsx($spreadsheet);
            $fechaActual = date('Y-m-d_H-i-s');
            $fileName = 'DespachosExternos_' . $fechaActual . '.xlsx';
            exportExcelFile($writer, $fileName);

        } catch (Exception $e) {
            while (ob_get_level()) { ob_end_clean(); }
            header('Content-Type: text/html; charset=utf-8');
            echo '<div style="padding:20px;background:#f8d7da;border:1px solid #f5c6cb;border-radius:4px;color:#721c24;margin:20px;">';
            echo '<h3>Error al exportar a Excel</h3>';
            echo '<p><strong>Mensaje:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p><strong>Archivo:</strong> ' . htmlspecialchars($e->getFile()) . ' en la línea ' . $e->getLine() . '</p>';
            echo '<a href="javascript:history.back()" style="color:#721c24;text-decoration:underline;">Volver</a></div>';
            error_log('Error en exportarExcelDespachosExternos: ' . $e->getMessage() . ' en ' . $e->getFile() . ' línea ' . $e->getLine());
            exit;
        }
    }
}
