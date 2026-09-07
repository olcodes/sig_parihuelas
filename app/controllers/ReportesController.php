<?php
// Importar clases de PHPSpreadsheet para exportaciÃƒÂ³n Excel
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\IOFactory;

// Incluir helper para exportaciÃƒÂ³n de Excel
require_once __DIR__ . '/../helpers/excel_export.php';

class ReportesController extends Controller {
    // MÃƒÂ©todo index para la ruta base /reportes
    public function index() {
        $this->view('reportes/despachosinternos');
    }
    // Vista principal del reporte
    public function despachosinternos() {
        $this->view('reportes/despachosinternos');
    }
    
    // Vista principal del reporte de recepciones internas
    public function recepcionesinternas() {
        $this->view('reportes/recepcionesinternas');
    }
    
    // Vista principal del reporte de despachos externos
    public function despachosexternos() {
        $this->view('reportes/despachosexternos');
    }
    
    // Vista principal del reporte de recepciones externas
    public function recepcionesexternas() {
        // Si tiene el parÃƒÂ¡metro view=ext, cargar vista plana
        if (isset($_GET['view']) && $_GET['view'] === 'ext') {
            $this->view('reportes/recepcionesext');
        } else {
            // Vista completa con jerarquÃƒÂ­a y modo ediciÃƒÂ³n
            $this->view('reportes/recepcionesexternas');
        }
    }
    
    // Vista principal del reporte consolidado
    public function consolidado() {
        $this->view('reportes/consolidado');
    }
    
    // Vista principal del reporte de picking
    public function picking() {
        $this->view('reportes/picking');
    }

    public function recepcionesexternasliquidadas() {
        $this->view('reportes/recepcionesexternasliquidadas');
    }
    
    public function actualizarProductoEspecifico()
    {
        header('Content-Type: application/json');

        try {
            $rawInput = file_get_contents('php://input');
            error_log("Datos recibidos en actualizarProductoEspecifico: " . $rawInput);
            
            $params = json_decode($rawInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Error de JSON: " . json_last_error_msg());
                echo json_encode(['success' => false, 'message' => 'Error en el formato de los datos: ' . json_last_error_msg()], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $despachoId = $params['despachoId'] ?? null;
            $producto = $params['producto'] ?? null;
            
            // DepuraciÃƒÂ³n detallada
            error_log("actualizarProductoEspecifico: JSON recibido: " . $rawInput);
            error_log("actualizarProductoEspecifico: despachoId=" . $despachoId . ", producto=" . json_encode($producto));
            
            // Obtener cÃƒÂ³digo original si existe (para cuando se cambia el producto)
            $codigoOriginal = $producto['CodigoOriginal'] ?? $producto['Codigo'];
            error_log("actualizarProductoEspecifico: CodigoOriginal=" . $codigoOriginal . ", CodigoNuevo=" . ($producto['Codigo'] ?? ''));
            
            // DepuraciÃƒÂ³n especÃƒÂ­fica para la unidad de medida
            $unidadMedida = $producto['UnidadMedida'] ?? '';
            error_log("actualizarProductoEspecifico: UNIDAD DE MEDIDA RECIBIDA = " . $unidadMedida);
            
            // Si no se recibiÃƒÂ³ unidad de medida, intentar recuperarla del producto existente
            if (empty($unidadMedida)) {
                $modelProducto = $this->model('Producto');
                $productoData = $modelProducto->buscarPorCodigo($producto['Codigo']);
                if ($productoData && isset($productoData['UnidadMedida']) && !empty($productoData['UnidadMedida'])) {
                    $unidadMedida = $productoData['UnidadMedida'];
                    $producto['UnidadMedida'] = $unidadMedida;
                    error_log("actualizarProductoEspecifico: UNIDAD DE MEDIDA RECUPERADA DE DB = " . $unidadMedida);
                }
            }
            
            // ValidaciÃƒÂ³n mÃƒÂ¡s detallada para ayudar a depurar
            if (!$despachoId) {
                error_log("ERROR: despachoId no definido");
                echo json_encode(['success' => false, 'message' => 'ID de despacho no definido'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            if (!$producto) {
                error_log("ERROR: datos de producto no definidos");
                echo json_encode(['success' => false, 'message' => 'Datos del producto no definidos'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Validar datos del producto
            if (!isset($producto['Codigo'])) {
                error_log("ERROR: CÃƒÂ³digo del producto no definido");
                echo json_encode(['success' => false, 'message' => 'CÃƒÂ³digo del producto no definido'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            if (!isset($producto['Cantidad'])) {
                error_log("ERROR: Cantidad del producto no definida");
                echo json_encode(['success' => false, 'message' => 'Cantidad del producto no definida'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Actualizar el producto en la base de datos
            $model = $this->model('DespachoInterno');
            
            try {
                $resultado = $model->actualizarProductoEspecifico(
                    $despachoId, 
                    $codigoOriginal,          // Usar cÃƒÂ³digo original para buscar
                    $producto['Codigo'],       // CÃƒÂ³digo nuevo (podrÃƒÂ­a ser el mismo)
                    $producto['Producto'] ?? '',
                    $producto['Cantidad'],
                    $producto['Comentarios'] ?? '',
                    $producto['UnidadMedida'] ?? ''
                );
                
                if ($resultado) {
                    echo json_encode(['success' => true, 'message' => 'Producto actualizado correctamente'], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el producto'], JSON_UNESCAPED_UNICODE);
                }
            } catch (Exception $e) {
                error_log("Error al actualizar producto: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
            
        } catch (Exception $e) {
            error_log("Error general en actualizarProductoEspecifico: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error del servidor'], JSON_UNESCAPED_UNICODE);
        }
    }

    // Endpoint para obtener los registros filtrados y paginados
    public function obtenerDespachosInternos() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['data' => [], 'totalPaginas' => 1], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $params = json_decode(file_get_contents('php://input'), true);
        $pagina = isset($params['pagina']) ? max(1, (int)$params['pagina']) : 1;
        $filtros = $params['filtros'] ?? [];
        
        // PaginaciÃƒÂ³n plana: 20 filas (productos) por pÃƒÂ¡gina
        $LIMIT_FLAT = 20;
        $flatOffset = ($pagina - 1) * $LIMIT_FLAT;

        try {
            $model = $this->model('DespachoInterno');
            // Obtener TODOS los vales que coinciden con filtros para aplanar correctamente
            $result = $model->getReporte($filtros, 10000, 0);

            // FLATTEN: Convertir datos jerÃƒÂ¡rquicos (vale con Detalles[]) a planos (una fila por producto)
            $datosOriginales = $result['data'] ?? [];
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

            // COMPUTAR VALORES ÃƒÅ¡NICOS para filtros de columna (desde TODOS los datos, antes de filtrar)
            $uniqueValues = [];
            $camposFiltrables = ['NVale','Fecha','Hora','Turno','Area','Subarea','Emisor','Despachador','Recepcionista','Verificador','CodigoProducto','NombreProducto','UnidadMedida','Cantidad','ComentariosProducto','Estado'];
            try {
                $dataForUnique = $datosPlanos; // Copia para no modificar el original
                // TambiÃƒÂ©n incluir datos del modelo original si es necesario
                foreach ($dataForUnique as $row) {
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

            // PAGINACIÃƒâ€œN PLANA: Aplicar offset y lÃƒÂ­mite sobre datos planos
            $totalFlattened = count($datosPlanos);
            $totalPaginas = max(1, ceil($totalFlattened / $LIMIT_FLAT));
            $datosPlanos = array_slice($datosPlanos, $flatOffset, $LIMIT_FLAT);

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'data' => $datosPlanos,
                'totalPaginas' => $totalPaginas,
                'totalRegistros' => $totalFlattened,
                'uniqueValues' => $uniqueValues,
                'success' => true
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
        } catch (Exception $e) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'data' => [],
                'totalPaginas' => 1,
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        exit;
    }

    // Endpoint para actualizar un despacho interno desde el reporte
    public function actualizarDespachoReporte() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'MÃƒÂ©todo no permitido']);
            exit;
        }

        try {
            // Registrar los datos recibidos para depuraciÃƒÂ³n
            $rawInput = file_get_contents('php://input');
            error_log("Datos recibidos en actualizarDespachoReporte: " . $rawInput);
            
            $params = json_decode($rawInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Error de JSON: " . json_last_error_msg());
                echo json_encode(['success' => false, 'message' => 'Error en el formato de los datos: ' . json_last_error_msg()], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $id = $params['id'] ?? null;
            $despacho = $params['despacho'] ?? [];
            $productos = $params['productos'] ?? [];
            
            error_log("Datos procesados: id=" . $id . ", despacho=" . json_encode($despacho));
            error_log("Productos a actualizar: " . json_encode($productos));

            if (!$id) {
                error_log("Error: ID de despacho requerido");
                echo json_encode(['success' => false, 'message' => 'ID de despacho requerido'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Convertir el ID a entero para asegurarnos de que es un nÃƒÂºmero vÃƒÂ¡lido
            $id = (int)$id;
            if ($id <= 0) {
                error_log("Error: ID de despacho invÃƒÂ¡lido: " . $id);
                echo json_encode(['success' => false, 'message' => 'ID de despacho invÃƒÂ¡lido'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Verificamos si hay productos
            if (!is_array($productos)) {
                error_log("Error: Los productos no son un array");
                echo json_encode(['success' => false, 'message' => 'Formato de productos invÃƒÂ¡lido'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            if (empty($productos)) {
                error_log("Advertencia: No se encontraron productos para actualizar");
                // No salimos, permitimos actualizar sin productos en casos especiales
            }

            $model = $this->model('DespachoInterno');
            
            // Verificar si hay datos del despacho para actualizar
            if (!empty($despacho)) {
                error_log("Actualizando datos del despacho ID: " . $id);
                // Actualizar datos del despacho
                $resultado = $model->actualizarDespacho($id, $despacho);
                
                if (!$resultado) {
                    error_log("Error al actualizar el despacho ID: " . $id);
                    echo json_encode(['success' => false, 'message' => 'Error al actualizar el despacho'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                
                error_log("Despacho ID: " . $id . " actualizado correctamente");
            } else {
                error_log("No hay datos del despacho para actualizar");
            }
            
            // Actualizar productos (siempre, incluso si no hay actualizaciÃƒÂ³n del despacho)
            try {
                error_log("Actualizando productos para el despacho ID: " . $id . ", cantidad: " . count($productos));
                
                // Verificar estructura de productos antes de actualizar
                foreach ($productos as $index => $producto) {
                    if (!isset($producto['Codigo'])) {
                        error_log("Error: Producto en ÃƒÂ­ndice " . $index . " no tiene Codigo");
                        echo json_encode(['success' => false, 'message' => 'Producto #' . ($index + 1) . ' no tiene cÃƒÂ³digo'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    
                    // Asegurarse de que la cantidad es un nÃƒÂºmero
                    if (isset($producto['Cantidad'])) {
                        $productos[$index]['Cantidad'] = (float)$producto['Cantidad'];
                    } else {
                        $productos[$index]['Cantidad'] = 0;
                    }
                    
                    // Asegurarse de que comentarios es string
                    if (!isset($producto['Comentarios'])) {
                        $productos[$index]['Comentarios'] = '';
                    }
                    
                    error_log("Producto " . ($index + 1) . ": " . json_encode($productos[$index]));
                }
                
                // Ahora actualizar los productos con los datos validados
                $actualizacionProductos = $model->actualizarProductosDespacho($id, $productos);
                
                if ($actualizacionProductos) {
                    error_log("Productos actualizados correctamente para despacho ID: " . $id);
                    echo json_encode(['success' => true, 'message' => 'ActualizaciÃƒÂ³n exitosa'], JSON_UNESCAPED_UNICODE);
                } else {
                    error_log("Error al actualizar productos para despacho ID: " . $id);
                    echo json_encode(['success' => false, 'message' => 'Error al actualizar los productos'], JSON_UNESCAPED_UNICODE);
                }
            } catch (Exception $e) {
                error_log("ExcepciÃƒÂ³n al actualizar productos: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error al actualizar los productos: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            error_log("ExcepciÃƒÂ³n general en actualizarDespachoReporte: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // Endpoint para obtener un despacho por ID
    public function obtenerDespachoId() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'MÃƒÂ©todo no permitido'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        try {
            $params = json_decode(file_get_contents('php://input'), true);
            $id = $params['id'] ?? null;
            
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID no proporcionado'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $model = $this->model('DespachoInterno');
            $despacho = $model->getById($id);
            
            if ($despacho) {
                echo json_encode([
                    'success' => true,
                    'data' => $despacho
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'message' => 'Despacho no encontrado'], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // Endpoint para obtener listas de datos para selectores
    public function obtenerDatosSelectores() {
        header('Content-Type: application/json');
        try {
            // Obtener ÃƒÂ¡reas
            $areaModel = $this->model('Area');
            $areas = $areaModel->getAllForSelect();

            // Obtener subareas
            $subareaModel = $this->model('Subarea');
            $subareas = $subareaModel->getAllForSelect();

            // Obtener responsables
            $responsableModel = $this->model('Responsable');
            $responsables = $responsableModel->getAllForSelect();

            // Obtener recepcionistas
            $recepcionistaModel = $this->model('Recepcionista');
            $recepcionistas = $recepcionistaModel->getAllForSelect();

            // Obtener productos
            $productoModel = $this->model('Producto');
            $productos = $productoModel->getAllForSelect();

            // Obtener turnos
            $turnoModel = $this->model('Turno');
            $turnos = $turnoModel->getAllForSelect();

            echo json_encode([
                'success' => true,
                'data' => [
                    'areas' => $areas,
                    'subareas' => $subareas,
                    'responsables' => $responsables,
                    'recepcionistas' => $recepcionistas,
                    'productos' => $productos,
                    'turnos' => $turnos
                ]
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    // Exportar despachos internos a Excel
    public function exportarExcelDespachosInternos() {
        try {
            // Configurar entorno para exportaciÃƒÂ³n
            ini_set('memory_limit', '1024M');
            ini_set('max_execution_time', 600);
            
            // Cargar autoloader para PHPSpreadsheet
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
                // Fallback: mÃƒÂ©todo GET original
                if (!empty($_GET['fechaDesde'])) $filtros['fechaDesde'] = $_GET['fechaDesde'];
                if (!empty($_GET['fechaHasta'])) $filtros['fechaHasta'] = $_GET['fechaHasta'];
                if (!empty($_GET['areaId'])) $filtros['areaId'] = $_GET['areaId'];
                if (!empty($_GET['subareaId'])) $filtros['subareaId'] = $_GET['subareaId'];
                if (!empty($_GET['responsableId'])) $filtros['responsableId'] = $_GET['responsableId'];
                if (!empty($_GET['recepcionistaId'])) $filtros['recepcionistaId'] = $_GET['recepcionistaId'];
                if (!empty($_GET['turnoId'])) $filtros['turnoId'] = $_GET['turnoId'];
            }
            
            // Obtener datos segÃƒÂºn lÃƒÂ³gica de exportaciÃƒÂ³n
            // - Sin filtros (exportarTodo=false): exportar solo la pÃƒÂ¡gina actual
            // - Con filtros (exportarTodo=true): exportar todos los registros filtrados
            $LIMIT_FLAT = 20;
            if ($exportarTodo) {
                $exportLimit = 50000;
                $exportOffset = 0;
            } else {
                $exportLimit = $LIMIT_FLAT;
                $exportOffset = ($paginaActual - 1) * $LIMIT_FLAT;
            }
            $model = $this->model('DespachoInterno');
            $result = $model->getReporte($filtros, $exportLimit, $exportOffset);
            $datosOriginales = $result['data'] ?? [];
            
            // FLATTEN: convertir datos jerÃƒÂ¡rquicos a planos (una fila por producto)
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
                        if (!in_array($valorCelda, $valoresSeleccionados, true)) {
                            return false;
                        }
                    }
                    return true;
                });
                $datosPlanos = array_values($datosPlanos);
            }
            
            if (empty($datosPlanos)) {
                throw new Exception('No se encontraron datos para exportar con los filtros seleccionados.');
            }
            
            // Crear documento Excel
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Despachos Internos');
            $sheet->getSheetView()->setZoomScale(80);
            
            // Cabeceras
            $cabeceras = [
                'A1' => 'NRO VALE', 'B1' => 'FECHA', 'C1' => 'HORA', 'D1' => 'TURNO',
                'E1' => 'ÃƒÂREA', 'F1' => 'SUBÃƒÂREA', 'G1' => 'EMISOR', 'H1' => 'DESPACHADOR', 'I1' => 'RECEPCIONISTA',
                'J1' => 'VERIFICADOR', 'K1' => 'CÃƒâ€œDIGO', 'L1' => 'PRODUCTO', 'M1' => 'UNIDAD DE MEDIDA',
                'N1' => 'CANTIDAD', 'O1' => 'COMENTARIOS', 'P1' => 'DIA', 'Q1' => 'SEMANA', 'R1' => 'MES',
                'S1' => 'NÃ‚Â° LIQUIDACION', 'T1' => 'TIPO DESPACHO'
            ];
            foreach ($cabeceras as $celda => $valor) {
                $sheet->setCellValue($celda, $valor);
            }
            
            // Estilo de cabeceras
            $styleHeader = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ];
            $sheet->getStyle('A1:T1')->applyFromArray($styleHeader);
            $sheet->getStyle('A1:T1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->freezePane('A2');
            $sheet->getRowDimension(1)->setRowHeight(20);
            
            // Procesar datos planos (una fila por registro ya aplanado)
            $filaExcel = 2;
            foreach ($datosPlanos as $row) {
                $fechaFormateada = isset($row['Fecha']) && $row['Fecha'] ? date('d/m/Y', strtotime($row['Fecha'])) : '';
                $sheet->setCellValue('A' . $filaExcel, $row['NVale'] ?? '');
                $sheet->setCellValue('B' . $filaExcel, $fechaFormateada);
                $sheet->setCellValue('C' . $filaExcel, $row['Hora'] ?? '');
                $sheet->setCellValue('D' . $filaExcel, $row['Turno'] ?? '');
                $sheet->setCellValue('E' . $filaExcel, $row['Area'] ?? '');
                $sheet->setCellValue('F' . $filaExcel, $row['Subarea'] ?? '');
                $sheet->setCellValue('G' . $filaExcel, $row['Emisor'] ?? '');
                $sheet->setCellValue('H' . $filaExcel, $row['Despachador'] ?? '');
                $sheet->setCellValue('I' . $filaExcel, $row['Recepcionista'] ?? '');
                $sheet->setCellValue('J' . $filaExcel, $row['Verificador'] ?? '');
                $sheet->setCellValue('K' . $filaExcel, $row['CodigoProducto'] ?? '');
                $sheet->setCellValue('L' . $filaExcel, $row['NombreProducto'] ?? '');
                $sheet->setCellValue('M' . $filaExcel, $row['UnidadMedida'] ?? '');
                $sheet->setCellValue('N' . $filaExcel, $row['Cantidad'] ?? '');
                $sheet->setCellValue('O' . $filaExcel, $row['ComentariosProducto'] ?? '');
                $sheet->setCellValue('P' . $filaExcel, $row['Dia'] ?? '');
                $sheet->setCellValue('Q' . $filaExcel, $row['Semana'] ?? '');
                $sheet->setCellValue('R' . $filaExcel, $row['Mes'] ?? '');
                $sheet->setCellValue('S' . $filaExcel, $row['NLiquidacion'] ?? '');
                $sheet->setCellValue('T' . $filaExcel, $row['TipoDespacho'] ?? 'DESPACHO INTERNO');
                $filaExcel++;
            }
            
            // Auto-ajustar columnas
            foreach (range('A', 'T') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Quitar lÃƒÂ­neas de cuadrÃƒÂ­cula
            $sheet->setShowGridlines(false);
            $lastRow = $filaExcel - 1;
            if ($lastRow >= 1) {
                $sheet->getStyle('A1:T' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                // Filas alternas
                for ($row = 2; $row <= $lastRow; $row++) {
                    if ($row % 2 == 0) {
                        $sheet->getStyle('A'.$row.':T'.$row)->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('F5F5F5');
                    }
                }
            }
            
            // Crear writer y exportar usando nuestro helper
            $writer = new Xlsx($spreadsheet);
            $fechaActual = date('Y-m-d_H-i-s');
            $fileName = 'DespachoInternos_' . $fechaActual . '.xlsx';
            
            exportExcelFile($writer, $fileName);
            
        } catch (Exception $e) {
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            header('Content-Type: text/html; charset=utf-8');
            echo '<div style="padding: 20px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24; margin: 20px;">';
            echo '<h3>Error al exportar a Excel</h3>';
            echo '<p><strong>Mensaje:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p><strong>Archivo:</strong> ' . htmlspecialchars($e->getFile()) . ' en la lÃƒÂ­nea ' . $e->getLine() . '</p>';
            echo '<a href="javascript:history.back()" style="color: #721c24; text-decoration: underline;">Volver</a>';
            echo '</div>';
            
            error_log('Error en exportarExcelDespachosInternos: ' . $e->getMessage() . ' en ' . $e->getFile() . ' lÃƒÂ­nea ' . $e->getLine());
            exit;
        }
    }

    // Exportar recepciones internas a Excel
    public function exportarExcelRecepcionesInternas() {
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
                if (!empty($_GET['fechaDesde'])) $filtros['fechaDesde'] = $_GET['fechaDesde'];
                if (!empty($_GET['fechaHasta'])) $filtros['fechaHasta'] = $_GET['fechaHasta'];
                if (!empty($_GET['areaId'])) $filtros['areaId'] = $_GET['areaId'];
                if (!empty($_GET['subareaId'])) $filtros['subareaId'] = $_GET['subareaId'];
                if (!empty($_GET['responsableId'])) $filtros['responsableId'] = $_GET['responsableId'];
                if (!empty($_GET['recepcionistaId'])) $filtros['recepcionistaId'] = $_GET['recepcionistaId'];
                if (!empty($_GET['turnoId'])) $filtros['turnoId'] = $_GET['turnoId'];
                if (empty($filtros['fechaDesde']) && empty($filtros['fechaHasta'])) {
                    $filtros['fechaDesde'] = date('Y-m-01');
                    $filtros['fechaHasta'] = date('Y-m-t');
                }
            }

            // Obtener datos segÃƒÂºn lÃƒÂ³gica de exportaciÃƒÂ³n
            $LIMIT_FLAT = 20;
            if ($exportarTodo) {
                $exportLimit = 50000;
                $exportOffset = 0;
            } else {
                $exportLimit = $LIMIT_FLAT;
                $exportOffset = ($paginaActual - 1) * $LIMIT_FLAT;
            }
            $model = $this->model('RecepcionInterna');
            $result = $model->getReporte($filtros, $exportLimit, $exportOffset);
            $datosOriginales = $result['data'] ?? [];

            // FLATTEN: convertir datos jerÃƒÂ¡rquicos a planos (una fila por producto)
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

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Recepciones Internas');
            $sheet->getSheetView()->setZoomScale(80);

            // Cabeceras
            $cabeceras = [
                'A1' => 'NRO VALE', 'B1' => 'FECHA', 'C1' => 'HORA', 'D1' => 'TURNO', 'E1' => 'ÃƒÂREA',
                'F1' => 'SUBÃƒÂREA', 'G1' => 'DESPACHADOR', 'H1' => 'MEDIO TRANSPORTE', 'I1' => 'ASISTENTE',
                'J1' => 'VERIFICADOR', 'K1' => 'COD_PRODUCTO', 'L1' => 'DESC_PRODUCTO', 'M1' => 'UNIDAD MEDIDA',
                'N1' => 'CANTIDAD', 'O1' => 'COMENTARIOS', 'P1' => 'ESTADO', 'Q1' => 'DIA', 'R1' => 'SEMANA',
                'S1' => 'MES', 'T1' => 'NÃ‚Â° LIQUIDACION', 'U1' => 'TIPO RECEPCION'
            ];
            foreach ($cabeceras as $celda => $valor) {
                $sheet->setCellValue($celda, $valor);
            }

            $styleHeader = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ];
            $sheet->getStyle('A1:U1')->applyFromArray($styleHeader);
            $sheet->getStyle('A1:U1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->freezePane('A2');
            $sheet->getRowDimension(1)->setRowHeight(20);

            // Procesar datos planos
            $filaExcel = 2;
            foreach ($datosPlanos as $row) {
                $fechaFormateada = isset($row['Fecha']) && $row['Fecha'] ? date('d/m/Y', strtotime($row['Fecha'])) : '';
                $sheet->setCellValue('A' . $filaExcel, $row['NVale'] ?? '');
                $sheet->setCellValue('B' . $filaExcel, $fechaFormateada);
                $sheet->setCellValue('C' . $filaExcel, $row['Hora'] ?? '');
                $sheet->setCellValue('D' . $filaExcel, $row['Turno'] ?? '');
                $sheet->setCellValue('E' . $filaExcel, $row['Area'] ?? '');
                $sheet->setCellValue('F' . $filaExcel, $row['Subarea'] ?? '');
                $sheet->setCellValue('G' . $filaExcel, $row['Despachador'] ?? '');
                $sheet->setCellValue('H' . $filaExcel, $row['MedioTransporte'] ?? '');
                $sheet->setCellValue('I' . $filaExcel, $row['Asistente'] ?? '');
                $sheet->setCellValue('J' . $filaExcel, $row['Verificador'] ?? '');
                $sheet->setCellValue('K' . $filaExcel, $row['CodigoProducto'] ?? '');
                $sheet->setCellValue('L' . $filaExcel, $row['NombreProducto'] ?? '');
                $sheet->setCellValue('M' . $filaExcel, $row['UnidadMedida'] ?? '');
                $sheet->setCellValue('N' . $filaExcel, $row['Cantidad'] ?? '');
                $sheet->setCellValue('O' . $filaExcel, $row['ComentariosProducto'] ?? '');
                $sheet->setCellValue('P' . $filaExcel, $row['Estado'] ?? '');
                $sheet->setCellValue('Q' . $filaExcel, $row['Dia'] ?? '');
                $sheet->setCellValue('R' . $filaExcel, $row['Semana'] ?? '');
                $sheet->setCellValue('S' . $filaExcel, $row['Mes'] ?? '');
                $sheet->setCellValue('T' . $filaExcel, $row['NLiquidacion'] ?? '');
                $sheet->setCellValue('U' . $filaExcel, $row['TipoRecepcion'] ?? 'RECEPCIÃƒâ€œN INTERNA');
                $filaExcel++;
            }

            // Auto-ajustar columnas
            foreach (range('A', 'U') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $lastRow = $filaExcel - 1;
            if ($lastRow >= 1) {
                $sheet->getStyle('A1:U' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                for ($row = 2; $row <= $lastRow; $row++) {
                    if ($row % 2 == 0) {
                        $sheet->getStyle('A'.$row.':U'.$row)->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('F5F5F5');
                    }
                }
            }
            
            $sheet->setShowGridlines(false);

            $writer = new Xlsx($spreadsheet);
            $fechaActual = date('Y-m-d_H-i-s');
            $fileName = 'RecepcionesInternas_' . $fechaActual . '.xlsx';
            exportExcelFile($writer, $fileName);

        } catch (Exception $e) {
            while (ob_get_level()) { ob_end_clean(); }
            header('Content-Type: text/html; charset=utf-8');
            echo '<div style="padding: 20px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24; margin: 20px;">';
            echo '<h3>Error al exportar a Excel</h3>';
            echo '<p><strong>Mensaje:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p><strong>Archivo:</strong> ' . htmlspecialchars($e->getFile()) . ' en la lÃƒÂ­nea ' . $e->getLine() . '</p>';
            echo '<a href="javascript:history.back()" style="color: #721c24; text-decoration: underline;">Volver</a>';
            echo '</div>';
            error_log('Error en exportarExcelRecepcionesInternas: ' . $e->getMessage() . ' en ' . $e->getFile() . ' lÃƒÂ­nea ' . $e->getLine());
            exit;
        }
    }
    
    // ============================================
    // MÃƒâ€°TODOS PARA RECEPCIONES INTERNAS
    // ============================================
    
    public function actualizarProductoEspecificoRecepcion()
    {
        header('Content-Type: application/json');

        try {
            $rawInput = file_get_contents('php://input');
            error_log("Datos recibidos en actualizarProductoEspecificoRecepcion: " . $rawInput);
            
            $params = json_decode($rawInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Error de JSON: " . json_last_error_msg());
                echo json_encode(['success' => false, 'message' => 'Error en el formato de los datos: ' . json_last_error_msg()], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $recepcionId = $params['recepcionId'] ?? null;
            $producto = $params['producto'] ?? null;
            
            // DepuraciÃƒÂ³n detallada
            error_log("actualizarProductoEspecificoRecepcion: JSON recibido: " . $rawInput);
            error_log("actualizarProductoEspecificoRecepcion: recepcionId=" . $recepcionId . ", producto=" . json_encode($producto));
            
            // DepuraciÃƒÂ³n especÃƒÂ­fica para la unidad de medida
            $unidadMedida = $producto['UnidadMedida'] ?? '';
            error_log("actualizarProductoEspecificoRecepcion: UNIDAD DE MEDIDA RECIBIDA = " . $unidadMedida);
            
            // Si no se recibiÃƒÂ³ unidad de medida, intentar recuperarla del producto existente
            if (empty($unidadMedida)) {
                $modelProducto = $this->model('Producto');
                $productoData = $modelProducto->buscarPorCodigo($producto['Codigo']);
                if ($productoData && isset($productoData['UnidadMedida']) && !empty($productoData['UnidadMedida'])) {
                    $unidadMedida = $productoData['UnidadMedida'];
                    $producto['UnidadMedida'] = $unidadMedida;
                    error_log("actualizarProductoEspecificoRecepcion: UNIDAD DE MEDIDA RECUPERADA DE DB = " . $unidadMedida);
                }
            }
            
            // ValidaciÃƒÂ³n mÃƒÂ¡s detallada para ayudar a depurar
            if (!$recepcionId) {
                error_log("ERROR: recepcionId no definido");
                echo json_encode(['success' => false, 'message' => 'ID de recepciÃƒÂ³n no definido'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            if (!$producto) {
                error_log("ERROR: datos de producto no definidos");
                echo json_encode(['success' => false, 'message' => 'Datos del producto no definidos'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Validar datos del producto
            if (!isset($producto['Codigo'])) {
                error_log("ERROR: CÃƒÂ³digo del producto no definido");
                echo json_encode(['success' => false, 'message' => 'CÃƒÂ³digo del producto no definido'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            if (!isset($producto['Cantidad'])) {
                error_log("ERROR: Cantidad del producto no definida");
                echo json_encode(['success' => false, 'message' => 'Cantidad del producto no definida'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Actualizar el producto en la base de datos
            $model = $this->model('RecepcionInterna');
            
            try {
                $resultado = $model->actualizarProductoEspecifico(
                    $recepcionId, 
                    $producto['Codigo'],
                    $producto['Producto'] ?? '',
                    $producto['Cantidad'],
                    $producto['Comentarios'] ?? '',
                    $producto['UnidadMedida'] ?? ''
                );
                
                if ($resultado) {
                    echo json_encode(['success' => true, 'message' => 'Producto actualizado correctamente'], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el producto'], JSON_UNESCAPED_UNICODE);
                }
            } catch (Exception $e) {
                error_log("Error al actualizar producto: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
            
        } catch (Exception $e) {
            error_log("Error general en actualizarProductoEspecificoRecepcion: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error del servidor'], JSON_UNESCAPED_UNICODE);
        }
    }
    
    public function obtenerRecepcionesInternas() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['data' => [], 'totalPaginas' => 1], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $params = json_decode(file_get_contents('php://input'), true);
        $pagina = isset($params['pagina']) ? max(1, (int)$params['pagina']) : 1;
        $filtros = $params['filtros'] ?? [];
        
        // PaginaciÃƒÂ³n plana: 20 filas (productos) por pÃƒÂ¡gina
        $LIMIT_FLAT = 20;
        $flatOffset = ($pagina - 1) * $LIMIT_FLAT;

        try {
            $model = $this->model('RecepcionInterna');
            // Obtener TODOS los vales que coinciden con filtros para aplanar correctamente
            $result = $model->getReporte($filtros, 10000, 0);

            // FLATTEN: Convertir datos jerÃƒÂ¡rquicos a planos (una fila por producto)
            $datosOriginales = $result['data'] ?? [];
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

            // COMPUTAR VALORES ÃƒÅ¡NICOS para filtros de columna (desde TODOS los datos, antes de filtrar)
            $uniqueValues = [];
            $camposFiltrables = ['NVale','Fecha','Hora','Turno','Area','Subarea','Despachador','MedioTransporte','Verificador','CodigoProducto','NombreProducto','UnidadMedida','Cantidad','ComentariosProducto','Estado'];
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

            // PAGINACIÃƒâ€œN PLANA: Aplicar offset y lÃƒÂ­mite sobre datos planos
            $totalFlattened = count($datosPlanos);
            $totalPaginas = max(1, ceil($totalFlattened / $LIMIT_FLAT));
            $datosPlanos = array_slice($datosPlanos, $flatOffset, $LIMIT_FLAT);

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'data' => $datosPlanos,
                'totalPaginas' => $totalPaginas,
                'totalRegistros' => $totalFlattened,
                'uniqueValues' => $uniqueValues,
                'success' => true
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
        } catch (Exception $e) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'data' => [],
                'totalPaginas' => 1,
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        exit;
    }
    
    public function actualizarRecepcionReporte() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'MÃƒÂ©todo no permitido']);
            exit;
        }

        try {
            // Registrar los datos recibidos para depuraciÃƒÂ³n
            $rawInput = file_get_contents('php://input');
            error_log("Datos recibidos en actualizarRecepcionReporte: " . $rawInput);
            
            $params = json_decode($rawInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Error de JSON: " . json_last_error_msg());
                echo json_encode(['success' => false, 'message' => 'Error en el formato de los datos: ' . json_last_error_msg()], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $id = $params['id'] ?? null;
            $recepcion = $params['recepcion'] ?? [];
            $productos = $params['productos'] ?? [];
            
            error_log("Datos procesados: id=" . $id . ", recepcion=" . json_encode($recepcion));
            error_log("Productos a actualizar: " . json_encode($productos));

            if (!$id) {
                error_log("Error: ID de recepciÃƒÂ³n requerido");
                echo json_encode(['success' => false, 'message' => 'ID de recepciÃƒÂ³n requerido'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Convertir el ID a entero para asegurarnos de que es un nÃƒÂºmero vÃƒÂ¡lido
            $id = (int)$id;
            if ($id <= 0) {
                error_log("Error: ID de recepciÃƒÂ³n invÃƒÂ¡lido: " . $id);
                echo json_encode(['success' => false, 'message' => 'ID de recepciÃƒÂ³n invÃƒÂ¡lido'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Verificamos si hay productos
            if (!is_array($productos)) {
                error_log("Error: Los productos no son un array");
                echo json_encode(['success' => false, 'message' => 'Formato de productos invÃƒÂ¡lido'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            if (empty($productos)) {
                error_log("Advertencia: No se encontraron productos para actualizar");
                // No salimos, permitimos actualizar sin productos en casos especiales
            }

            $model = $this->model('RecepcionInterna');
            
            // Verificar si hay datos de la recepciÃƒÂ³n para actualizar
            if (!empty($recepcion)) {
                error_log("Actualizando datos de la recepciÃƒÂ³n ID: " . $id);
                // Actualizar datos de la recepciÃƒÂ³n
                $resultado = $model->actualizarRecepcion($id, $recepcion);
                
                if (!$resultado) {
                    error_log("Error al actualizar la recepciÃƒÂ³n ID: " . $id);
                    echo json_encode(['success' => false, 'message' => 'Error al actualizar la recepciÃƒÂ³n'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                
                error_log("RecepciÃƒÂ³n ID: " . $id . " actualizada correctamente");
            } else {
                error_log("No hay datos de la recepciÃƒÂ³n para actualizar");
            }
            
            // Actualizar productos (siempre, incluso si no hay actualizaciÃƒÂ³n de la recepciÃƒÂ³n)
            try {
                error_log("Actualizando productos para la recepciÃƒÂ³n ID: " . $id . ", cantidad: " . count($productos));
                
                // Verificar estructura de productos antes de actualizar
                foreach ($productos as $index => $producto) {
                    if (!isset($producto['Codigo'])) {
                        error_log("Error: Producto en ÃƒÂ­ndice " . $index . " no tiene Codigo");
                        echo json_encode(['success' => false, 'message' => 'Producto #' . ($index + 1) . ' no tiene cÃƒÂ³digo'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    
                    // Asegurarse de que la cantidad es un nÃƒÂºmero
                    if (isset($producto['Cantidad'])) {
                        $productos[$index]['Cantidad'] = (float)$producto['Cantidad'];
                    } else {
                        $productos[$index]['Cantidad'] = 0;
                    }
                    
                    // Asegurarse de que comentarios es string
                    if (!isset($producto['Comentarios'])) {
                        $productos[$index]['Comentarios'] = '';
                    }
                    
                    error_log("Producto " . ($index + 1) . ": " . json_encode($productos[$index]));
                }
                
                // Ahora actualizar los productos con los datos validados
                $actualizacionProductos = $model->actualizarProductosRecepcion($id, $productos);
                
                if ($actualizacionProductos) {
                    error_log("Productos actualizados correctamente para recepciÃƒÂ³n ID: " . $id);
                    echo json_encode(['success' => true, 'message' => 'ActualizaciÃƒÂ³n exitosa'], JSON_UNESCAPED_UNICODE);
                } else {
                    error_log("Error al actualizar productos para recepciÃƒÂ³n ID: " . $id);
                    echo json_encode(['success' => false, 'message' => 'Error al actualizar los productos'], JSON_UNESCAPED_UNICODE);
                }
            } catch (Exception $e) {
                error_log("ExcepciÃƒÂ³n al actualizar productos: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error al actualizar los productos: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            error_log("ExcepciÃƒÂ³n general en actualizarRecepcionReporte: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    public function obtenerRecepcionId() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'MÃƒÂ©todo no permitido'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        try {
            $params = json_decode(file_get_contents('php://input'), true);
            $id = $params['id'] ?? null;
            
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID no proporcionado'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $model = $this->model('RecepcionInterna');
            $recepcion = $model->getById($id);
            
            if ($recepcion) {
                echo json_encode([
                    'success' => true,
                    'data' => $recepcion
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'message' => 'RecepciÃƒÂ³n no encontrada'], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    public function obtenerDatosSelectoresRecepciones() {
        header('Content-Type: application/json');
        try {
            // Obtener ÃƒÂ¡reas
            $areaModel = $this->model('Area');
            $areas = $areaModel->getAllForSelect();

            // Obtener subareas
            $subareaModel = $this->model('Subarea');
            $subareas = $subareaModel->getAllForSelect();

            // Obtener responsables
            $responsableModel = $this->model('Responsable');
            $responsables = $responsableModel->getAllForSelect();

            // Obtener medios de transporte
            $medioTransporteModel = $this->model('MedioTransporte');
            $mediosTransporte = $medioTransporteModel->getAll();

            // Obtener productos
            $productoModel = $this->model('Producto');
            $productos = $productoModel->getAllForSelect();

            // Obtener turnos
            $turnoModel = $this->model('Turno');
            $turnos = $turnoModel->getAllForSelect();

            echo json_encode([
                'success' => true,
                'data' => [
                    'areas' => $areas,
                    'subareas' => $subareas,
                    'responsables' => $responsables,
                    'mediosTransporte' => $mediosTransporte,
                    'productos' => $productos,
                    'turnos' => $turnos
                ]
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    // ===============================================
    // MÃƒâ€°TODOS PARA REPORTE DE RECEPCIONES EXTERNAS
    // ===============================================
    
    public function obtenerRecepcionesExternas() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['data' => [], 'totalPaginas' => 1], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $params = json_decode(file_get_contents('php://input'), true);
        $pagina = isset($params['pagina']) ? max(1, (int)$params['pagina']) : 1;
        $filtros = $params['filtros'] ?? [];
        $agrupar = !empty($params['agrupar']); // Flag para reporte EXT (agrupacion cliente-side)
        
        // PaginaciÃƒÂ³n plana: 20 filas (productos) por pÃƒÂ¡gina
        $LIMIT_FLAT = 20;
        $flatOffset = ($pagina - 1) * $LIMIT_FLAT;
        
        // PaginaciÃƒÂ³n por lotes de vales (para navegar entre todos los registros)
        $LIMIT_VALES = 500;
        $offsetVales = isset($params['offsetVales']) ? max(0, (int)$params['offsetVales']) : 0;

        try {
            $model = $this->model('RecepcionExterna');
            
            // Cargar lote de vales con offset para navegaciÃƒÂ³n entre lotes
            $result = $model->getReporte($filtros, $LIMIT_VALES, $offsetVales);
            $datosOriginales = $result['data'] ?? [];
            
            // Obtener total de vales y calcular datos de navegaciÃƒÂ³n por lotes
            $totalVales = (int)($result['total'] ?? 0);
            $totalLotes = $totalVales > 0 ? (int)ceil($totalVales / $LIMIT_VALES) : 1;
            $loteActual = $totalVales > 0 ? (int)floor($offsetVales / $LIMIT_VALES) + 1 : 1;
            $hayMasRegistros = $totalVales > ($offsetVales + $LIMIT_VALES);
            
            // FLATTEN 3 NIVELES: Vale Ã¢â€ â€™ GuÃƒÂ­as Ã¢â€ â€™ Productos
            $datosPlanos = [];
            foreach ($datosOriginales as $row) {
                $guias = $row['Guias'] ?? [];
                unset($row['Guias']);
                
                // Guardar el ID original del vale antes de mergear con guÃƒÂ­a
                $valeIdOriginal = $row['Id'];
                
                if (empty($guias)) {
                    $row['NumeroGuia'] = '';
                    $row['NumeroDocRef'] = '';
                    $row['ObservacionTexto'] = '';
                    $row['CodigoProductoObs'] = '';
                    $row['CantidadObservada'] = '';
                    $row['CodigoProducto'] = '';
                    $row['DescripcionProducto'] = '';
                    $row['CantidadProducto'] = '';
                    $row['UnidadMedidaProducto'] = '';
                    $row['CantidadObsProducto'] = 0;
                    $row['ObservacionProducto'] = '';
                    $row['TextoObservacionesProducto'] = '';
                    $row['TotalProducto'] = 0;
                    $row['Id'] = $valeIdOriginal; // Preservar ID del vale
                    $datosPlanos[] = $row;
                } else {
                    foreach ($guias as $guia) {
                        $productos = $guia['Productos'] ?? [];
                        unset($guia['Productos']);
                        
                        if (empty($productos)) {
                            $filaPlana = array_merge($row, $guia);
                            $filaPlana['Id'] = $valeIdOriginal; // Preservar ID del vale
                            $filaPlana['CodigoProducto'] = '';
                            $filaPlana['DescripcionProducto'] = '';
                            $filaPlana['CantidadProducto'] = '';
                            $filaPlana['UnidadMedidaProducto'] = '';
                            $filaPlana['CantidadObsProducto'] = 0;
                            $filaPlana['ObservacionProducto'] = '';
                            $filaPlana['TextoObservacionesProducto'] = '';
                            $filaPlana['TotalProducto'] = 0;
                            $datosPlanos[] = $filaPlana;
                        } else {
                            foreach ($productos as $prod) {
                                $filaPlana = array_merge($row, $guia);
                                $filaPlana['Id'] = $valeIdOriginal; // Preservar ID del vale
                                $filaPlana['CodigoProducto'] = $prod['CodigoProducto'] ?? '';
                                $filaPlana['DescripcionProducto'] = $prod['DescripcionProducto'] ?? '';
                                $filaPlana['CantidadProducto'] = $prod['Cantidad'] ?? '';
                                $filaPlana['UnidadMedidaProducto'] = $prod['UnidadMedida'] ?? '';
                                $filaPlana['CantidadObsProducto'] = $prod['CantidadObservada'] ?? 0;
                                $filaPlana['ObservacionProducto'] = $prod['Observacion'] ?? '';
                                $filaPlana['TextoObservacionesProducto'] = $prod['TextoObservaciones'] ?? '';
                                $filaPlana['TotalProducto'] = $prod['Total'] ?? 0;
                                $datosPlanos[] = $filaPlana;
                            }
                        }
                    }
                }
            }

            // COMPUTAR VALORES ÃƒÅ¡NICOS para filtros de columna (desde TODOS los datos)
            $uniqueValues = [];
            $camposFiltrables = ['NVale','Fecha','Hora','Turno','Origen','Recepcionista','Empresa','RUC','Chofer','Brevete','NumeroGuia','NumeroDocRef','CodigoProducto','DescripcionProducto','CantidadProducto','TextoObservaciones','CantidadObsProducto','TotalProducto','TextoObservacionesProducto','Comentarios','Estado'];
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

            // APLICAR FILTROS DE COLUMNA sobre datos planos
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

            // Si es modo agrupar (reporte EXT), NO paginar en el servidor -
            // el cliente hara su propia agrupacion y paginacion
            if ($agrupar) {
                $totalFlattened = count($datosPlanos);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'data' => $datosPlanos,
                    'totalPaginas' => 1,
                    'totalRegistros' => $totalFlattened,
                    'uniqueValues' => $uniqueValues,
                    'success' => true,
                    'hayMasRegistros' => $hayMasRegistros,
                    'totalVales' => $totalVales,
                    'totalLotes' => $totalLotes,
                    'loteActual' => $loteActual,
                    'offsetVales' => $offsetVales
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            } else {
                // PAGINACIÃƒâ€œN PLANA (modo normal)
                $totalFlattened = count($datosPlanos);
                $totalPaginas = max(1, ceil($totalFlattened / $LIMIT_FLAT));
                $datosPlanos = array_slice($datosPlanos, $flatOffset, $LIMIT_FLAT);

                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'data' => $datosPlanos,
                    'totalPaginas' => $totalPaginas,
                    'totalRegistros' => $totalFlattened,
                    'uniqueValues' => $uniqueValues,
                    'success' => true,
                    'hayMasRegistros' => $hayMasRegistros,
                    'totalVales' => $totalVales,
                    'totalLotes' => $totalLotes,
                    'loteActual' => $loteActual,
                    'offsetVales' => $offsetVales
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            }
        } catch (Exception $e) {
            error_log('[ReportesController] Error: ' . $e->getMessage());
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'data' => [],
                'totalPaginas' => 1,
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        exit;
    }
    
    public function obtenerRecepcionExternaId() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'MÃƒÂ©todo no permitido'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        try {
            $params = json_decode(file_get_contents('php://input'), true);
            $id = $params['id'] ?? null;
            
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID no proporcionado'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $model = $this->model('RecepcionExterna');
            $recepcion = $model->getById($id);
            
            if ($recepcion) {
                echo json_encode([
                    'success' => true,
                    'data' => $recepcion
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'message' => 'RecepciÃƒÂ³n externa no encontrada'], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    public function actualizarRecepcionExternaReporte() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'MÃƒÂ©todo no permitido']);
            exit;
        }

        try {
            $rawInput = file_get_contents('php://input');
            error_log("Datos recibidos en actualizarRecepcionExternaReporte: " . $rawInput);
            
            $params = json_decode($rawInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Error de JSON: " . json_last_error_msg());
                echo json_encode(['success' => false, 'message' => 'Error en el formato de los datos: ' . json_last_error_msg()], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $id = $params['id'] ?? null;
            $recepcion = $params['recepcion'] ?? [];
            $guias = $params['guias'] ?? [];
            
            error_log("Datos procesados: id=" . $id . ", recepcion=" . json_encode($recepcion));
            error_log("GuÃƒÂ­as a actualizar: " . json_encode($guias));

            if (!$id) {
                error_log("Error: ID de recepciÃƒÂ³n requerido");
                echo json_encode(['success' => false, 'message' => 'ID de recepciÃƒÂ³n requerido'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $id = (int)$id;
            if ($id <= 0) {
                error_log("Error: ID de recepciÃƒÂ³n invÃƒÂ¡lido: " . $id);
                echo json_encode(['success' => false, 'message' => 'ID de recepciÃƒÂ³n invÃƒÂ¡lido'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            if (!is_array($guias)) {
                error_log("Error: Las guÃƒÂ­as no son un array");
                echo json_encode(['success' => false, 'message' => 'Formato de guÃƒÂ­as invÃƒÂ¡lido'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $model = $this->model('RecepcionExterna');
            
            // Actualizar datos de la recepciÃƒÂ³n si hay cambios
            if (!empty($recepcion)) {
                error_log("Actualizando datos de la recepciÃƒÂ³n ID: " . $id);
                $resultado = $model->actualizarRecepcion($id, $recepcion);
                
                if (!$resultado) {
                    error_log("Error al actualizar la recepciÃƒÂ³n ID: " . $id);
                    echo json_encode(['success' => false, 'message' => 'Error al actualizar la recepciÃƒÂ³n'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                
                error_log("RecepciÃƒÂ³n ID: " . $id . " actualizada correctamente");
            }
            
            // Actualizar guÃƒÂ­as y productos
            try {
                error_log("Actualizando guÃƒÂ­as para la recepciÃƒÂ³n ID: " . $id . ", cantidad: " . count($guias));
                
                $actualizacionGuias = $model->actualizarGuiasYProductos($id, $guias);
                
                if ($actualizacionGuias) {
                    error_log("GuÃƒÂ­as actualizadas correctamente para recepciÃƒÂ³n ID: " . $id);
                    echo json_encode(['success' => true, 'message' => 'ActualizaciÃƒÂ³n exitosa'], JSON_UNESCAPED_UNICODE);
                } else {
                    error_log("Error al actualizar guÃƒÂ­as para recepciÃƒÂ³n ID: " . $id);
                    echo json_encode(['success' => false, 'message' => 'Error al actualizar las guÃƒÂ­as'], JSON_UNESCAPED_UNICODE);
                }
            } catch (Exception $e) {
                error_log("ExcepciÃƒÂ³n al actualizar guÃƒÂ­as: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error al actualizar las guÃƒÂ­as: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            error_log("ExcepciÃƒÂ³n general en actualizarRecepcionExternaReporte: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    public function actualizarGuiaRecepcionExterna() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'MÃƒÂ©todo no permitido']);
            exit;
        }

        try {
            $rawInput = file_get_contents('php://input');
            error_log("Datos recibidos en actualizarGuiaRecepcionExterna: " . $rawInput);
            
            $params = json_decode($rawInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Error de JSON: " . json_last_error_msg());
                echo json_encode(['success' => false, 'message' => 'Error en el formato de los datos: ' . json_last_error_msg()], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $guiaId = $params['id'] ?? null;
            $numeroGuia = $params['numeroGuia'] ?? null;
            $observacion = $params['observacion'] ?? null;
            $codigoProductoObs = $params['codigoProductoObs'] ?? null;
            $cantidadObservada = $params['cantidadObservada'] ?? 0;
            
            error_log("Actualizando guÃƒÂ­a ID: " . $guiaId);

            if (!$guiaId) {
                error_log("Error: ID de guÃƒÂ­a requerido");
                echo json_encode(['success' => false, 'message' => 'ID de guÃƒÂ­a requerido'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            if (empty($numeroGuia)) {
                error_log("Error: NÃƒÂºmero de guÃƒÂ­a requerido");
                echo json_encode(['success' => false, 'message' => 'NÃƒÂºmero de guÃƒÂ­a requerido'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $model = $this->model('RecepcionExternaGuia');
            
            // Actualizar la guÃƒÂ­a con todos los campos
            $datos = [
                'numeroGuia' => $numeroGuia,
                'observacion' => $observacion,
                'codigoProductoObs' => $codigoProductoObs,
                'cantidadObservada' => $cantidadObservada
            ];
            
            $resultado = $model->actualizar($guiaId, $datos);
            
            if ($resultado) {
                error_log("GuÃƒÂ­a ID: " . $guiaId . " actualizada correctamente");
                echo json_encode(['success' => true, 'message' => 'GuÃƒÂ­a actualizada correctamente'], JSON_UNESCAPED_UNICODE);
            } else {
                error_log("Error al actualizar la guÃƒÂ­a ID: " . $guiaId);
                echo json_encode(['success' => false, 'message' => 'Error al actualizar la guÃƒÂ­a'], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            error_log("ExcepciÃƒÂ³n en actualizarGuiaRecepcionExterna: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    public function actualizarProductoRecepcionExterna() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'MÃƒÂ©todo no permitido']);
            exit;
        }

        try {
            $rawInput = file_get_contents('php://input');
            error_log("Datos recibidos en actualizarProductoRecepcionExterna: " . $rawInput);
            
            $params = json_decode($rawInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Error de JSON: " . json_last_error_msg());
                echo json_encode(['success' => false, 'message' => 'Error en el formato de los datos: ' . json_last_error_msg()], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $productoId = $params['id'] ?? null;
            $codigoProducto = $params['codigoProducto'] ?? null;
            $cantidad = $params['cantidad'] ?? 0;
            
            error_log("Actualizando producto ID: " . $productoId);

            if (!$productoId) {
                error_log("Error: ID de producto requerido");
                echo json_encode(['success' => false, 'message' => 'ID de producto requerido'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            if (empty($codigoProducto)) {
                error_log("Error: CÃƒÂ³digo de producto requerido");
                echo json_encode(['success' => false, 'message' => 'CÃƒÂ³digo de producto requerido'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            if ($cantidad <= 0) {
                error_log("Error: Cantidad debe ser mayor a 0");
                echo json_encode(['success' => false, 'message' => 'Cantidad debe ser mayor a 0'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $model = $this->model('RecepcionExternaProducto');
            
            // Actualizar el producto
            $datos = [
                'codigoProducto' => $codigoProducto,
                'cantidad' => $cantidad
            ];
            
            $resultado = $model->actualizar($productoId, $datos);
            
            if ($resultado) {
                error_log("Producto ID: " . $productoId . " actualizado correctamente");
                echo json_encode(['success' => true, 'message' => 'Producto actualizado correctamente'], JSON_UNESCAPED_UNICODE);
            } else {
                error_log("Error al actualizar el producto ID: " . $productoId);
                echo json_encode(['success' => false, 'message' => 'Error al actualizar el producto'], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            error_log("ExcepciÃƒÂ³n en actualizarProductoRecepcionExterna: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    public function anularRecepcionExterna() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'MÃƒÂ©todo no permitido']);
            exit;
        }
        
        try {
            $params = json_decode(file_get_contents('php://input'), true);
            $id = $params['id'] ?? null;
            $motivo = $params['motivo'] ?? '';
            
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID de recepciÃƒÂ³n requerido'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            if (empty($motivo)) {
                echo json_encode(['success' => false, 'message' => 'Motivo de anulaciÃƒÂ³n requerido'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $model = $this->model('RecepcionExterna');
            $usuarioId = $_SESSION['usuario_id'] ?? null;
            
            $resultado = $model->anular($id, $usuarioId, $motivo);
            
            if ($resultado) {
                echo json_encode(['success' => true, 'message' => 'RecepciÃƒÂ³n anulada correctamente'], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al anular la recepciÃƒÂ³n'], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // ============================================================
    // ENDPOINTS PARA PREFERENCIAS DE COLUMNAS (OCULTAR/MOSTRAR)
    // ============================================================

    /**
     * Obtener preferencias de columnas visibles para el usuario actual
     * GET o POST: reporte=reportes/obtenerPreferenciasColumnas
     * Body esperado: { report_code: "recepciones_externas" }
     */
    public function obtenerPreferenciasColumnas() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $userId = $_SESSION['user']['id'] ?? null;
            if (!$userId) {
                echo json_encode(['success' => false, 'error' => 'Usuario no autenticado'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $params = json_decode(file_get_contents('php://input'), true);
            $reportCode = $params['report_code'] ?? $_GET['report_code'] ?? '';

            if (empty($reportCode)) {
                echo json_encode(['success' => false, 'error' => 'report_code requerido'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $db = \Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT preferences FROM user_report_preferences WHERE user_id = ? AND report_code = ? LIMIT 1");
            $stmt->execute([$userId, $reportCode]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && !empty($row['preferences'])) {
                $preferences = json_decode($row['preferences'], true);
                echo json_encode([
                    'success' => true,
                    'preferences' => $preferences ?: []
                ], JSON_UNESCAPED_UNICODE);
            } else {
                // Sin preferencias guardadas: retornar objeto vacÃƒÂ­o (cliente usarÃƒÂ¡ defaults)
                echo json_encode([
                    'success' => true,
                    'preferences' => []
                ], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            error_log('[obtenerPreferenciasColumnas] Error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /**
     * Guardar preferencias de columnas visibles para el usuario actual
     * POST: reporte=reportes/guardarPreferenciasColumnas
     * Body esperado: { report_code: "recepciones_externas", preferences: { "NVale": true, "Fecha": false, ... } }
     */
    public function guardarPreferenciasColumnas() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'error' => 'MÃƒÂ©todo no permitido'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $userId = $_SESSION['user']['id'] ?? null;
            if (!$userId) {
                echo json_encode(['success' => false, 'error' => 'Usuario no autenticado'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $params = json_decode(file_get_contents('php://input'), true);
            $reportCode = $params['report_code'] ?? '';
            $preferences = $params['preferences'] ?? [];

            if (empty($reportCode)) {
                echo json_encode(['success' => false, 'error' => 'report_code requerido'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if (!is_array($preferences)) {
                echo json_encode(['success' => false, 'error' => 'preferences debe ser un objeto JSON'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $preferencesJson = json_encode($preferences, JSON_UNESCAPED_UNICODE);

            $db = \Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                "INSERT INTO user_report_preferences (user_id, report_code, preferences, created_at, updated_at)
                 VALUES (?, ?, ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE preferences = VALUES(preferences), updated_at = NOW()"
            );
            $stmt->execute([$userId, $reportCode, $preferencesJson]);

            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log('[guardarPreferenciasColumnas] Error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    public function exportarExcelRecepcionesExternas() {
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
                $columnasVisibles = $input['columnasVisibles'] ?? [];
            } else {
                // Fallback GET
                if (!empty($_GET['NVale'])) $filtros['NVale'] = $_GET['NVale'];
                if (!empty($_GET['Fecha'])) $filtros['Fecha'] = $_GET['Fecha'];
                if (!empty($_GET['Hora'])) $filtros['Hora'] = $_GET['Hora'];
                if (!empty($_GET['Turno'])) $filtros['Turno'] = $_GET['Turno'];
                if (!empty($_GET['Origen'])) $filtros['Origen'] = $_GET['Origen'];
                if (!empty($_GET['Empresa'])) $filtros['Empresa'] = $_GET['Empresa'];
                if (!empty($_GET['RUC'])) $filtros['RUC'] = $_GET['RUC'];
                if (!empty($_GET['Chofer'])) $filtros['Chofer'] = $_GET['Chofer'];
                if (!empty($_GET['Brevete'])) $filtros['Brevete'] = $_GET['Brevete'];
                if (!empty($_GET['Estado'])) $filtros['Estado'] = $_GET['Estado'];
            }

            // Obtener datos: cargar vales con lÃƒÂ­mite controlado (3 niveles vale/guias/productos)
            $model = $this->model('RecepcionExterna');
            $result = $model->getReporte($filtros, 500, 0);
            $recepciones = $result['data'];

            if (empty($recepciones)) {
                throw new Exception('No se encontraron datos para exportar con los filtros seleccionados.');
            }

            // FLATTEN: convertir Vale -> GuÃƒÂ­as -> Productos a filas planas (igual que obtenerRecepcionesExternas)
            $datosPlanos = [];
            foreach ($recepciones as $row) {
                $guias = $row['Guias'] ?? [];
                unset($row['Guias']);
                $valeIdOriginal = $row['Id'];

                if (empty($guias)) {
                    $row['NumeroGuia'] = '';
                    $row['NumeroDocRef'] = '';
                    $row['ObservacionTexto'] = '';
                    $row['CodigoProductoObs'] = '';
                    $row['CantidadObservada'] = '';
                    $row['CodigoProducto'] = '';
                    $row['DescripcionProducto'] = '';
                    $row['CantidadProducto'] = '';
                    $row['UnidadMedidaProducto'] = '';
                    $row['CantidadObsProducto'] = 0;
                    $row['ObservacionProducto'] = '';
                    $row['TextoObservacionesProducto'] = '';
                    $row['TotalProducto'] = 0;
                    $row['Id'] = $valeIdOriginal;
                    $datosPlanos[] = $row;
                } else {
                    foreach ($guias as $guia) {
                        $productos = $guia['Productos'] ?? [];
                        unset($guia['Productos']);

                        if (empty($productos)) {
                            $filaPlana = array_merge($row, $guia);
                            $filaPlana['Id'] = $valeIdOriginal;
                            $filaPlana['CodigoProducto'] = '';
                            $filaPlana['DescripcionProducto'] = '';
                            $filaPlana['CantidadProducto'] = '';
                            $filaPlana['UnidadMedidaProducto'] = '';
                            $filaPlana['CantidadObsProducto'] = 0;
                            $filaPlana['ObservacionProducto'] = '';
                            $filaPlana['TextoObservacionesProducto'] = '';
                            $filaPlana['TotalProducto'] = 0;
                            $datosPlanos[] = $filaPlana;
                        } else {
                            foreach ($productos as $prod) {
                                $filaPlana = array_merge($row, $guia);
                                $filaPlana['Id'] = $valeIdOriginal;
                                $filaPlana['CodigoProducto'] = $prod['CodigoProducto'] ?? '';
                                $filaPlana['DescripcionProducto'] = $prod['DescripcionProducto'] ?? '';
                                $filaPlana['CantidadProducto'] = $prod['Cantidad'] ?? '';
                                $filaPlana['UnidadMedidaProducto'] = $prod['UnidadMedida'] ?? '';
                                $filaPlana['CantidadObsProducto'] = $prod['CantidadObservada'] ?? 0;
                                $filaPlana['ObservacionProducto'] = $prod['Observacion'] ?? '';
                                $filaPlana['TextoObservacionesProducto'] = $prod['TextoObservaciones'] ?? '';
                                $filaPlana['TotalProducto'] = $prod['Total'] ?? 0;
                                $datosPlanos[] = $filaPlana;
                            }
                        }
                    }
                }
            }

            // APLICAR FILTROS DE COLUMNA (filtros tipo Excel en cabecera)
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

            // APLICAR PAGINACIÃƒâ€œN PLANA sobre los datos aplanados
            // - Sin filtros (exportarTodo=false): exportar solo la pÃƒÂ¡gina actual (20 filas)
            // - Con filtros (exportarTodo=true): exportar todos los registros filtrados
            $LIMIT_FLAT = 20;
            if (!$exportarTodo) {
                $flatOffset = ($paginaActual - 1) * $LIMIT_FLAT;
                $datosPlanos = array_slice($datosPlanos, $flatOffset, $LIMIT_FLAT);
            }

            if (empty($datosPlanos)) {
                throw new Exception('No se encontraron datos para exportar con los filtros seleccionados.');
            }

            // ============================================================
            // DEFINICIÃƒâ€œN DE COLUMNAS DEL EXCEL (solo las que estÃƒÂ¡n en el reporte)
            // Mapea directamente con las columnas del <thead> de la vista
            // ============================================================
            $columnDefs = [
                ['label' => 'N° VALE',       'field' => null,              'dataField' => 'NVale',                'calc' => function($row) { return isset($row['NVale']) ? str_pad($row['NVale'], 6, '0', STR_PAD_LEFT) : ''; }],
                ['label' => 'FECHA',          'field' => null,              'dataField' => 'Fecha',                'calc' => function($row) { return isset($row['Fecha']) && $row['Fecha'] ? date('d/m/Y', strtotime($row['Fecha'])) : ''; }],
                ['label' => 'HORA',           'field' => 'Hora',            'dataField' => 'Hora',                 'calc' => null],
                ['label' => 'TURNO',          'field' => 'Turno',           'dataField' => 'Turno',                'calc' => null],
                ['label' => 'ORIGEN',         'field' => 'Origen',          'dataField' => 'Origen',               'calc' => null],
                ['label' => 'RECEPCIONISTA',  'field' => 'Recepcionista',   'dataField' => 'Recepcionista',        'calc' => null],
                ['label' => 'EMPRESA',        'field' => 'Empresa',         'dataField' => 'Empresa',              'calc' => null],
                ['label' => 'RUC',            'field' => 'RUC',             'dataField' => 'RUC',                  'calc' => null],
                ['label' => 'CHOFER',         'field' => 'Chofer',          'dataField' => 'Chofer',               'calc' => null],
                ['label' => 'BREVETE',        'field' => 'Brevete',         'dataField' => 'Brevete',              'calc' => null],
                ['label' => 'N° GUÍA',        'field' => 'NumeroGuia',      'dataField' => 'NumeroGuia',           'calc' => null],
                ['label' => 'N° DOC. REF.',   'field' => 'NumeroDocRef',    'dataField' => 'NumeroDocRef',         'calc' => null],
                ['label' => 'CÓDIGO PRODUCTO','field' => 'CodigoProducto',  'dataField' => 'CodigoProducto',       'calc' => null],
                ['label' => 'DESCRIPCIÓN',    'field' => 'DescripcionProducto','dataField' => 'DescripcionProducto','calc' => null],
                ['label' => 'CANTIDAD',       'field' => null,              'dataField' => 'CantidadProducto',     'calc' => function($row) { return floatval($row['CantidadProducto'] ?? 0); }],
                ['label' => 'TIPO OBS',       'field' => 'ObservacionTexto','dataField' => 'TextoObservaciones',   'calc' => null],
                ['label' => 'CANT OBS',       'field' => 'CantidadObservada','dataField' => 'CantidadObsProducto', 'calc' => null],
                ['label' => 'TOTAL',          'field' => null,              'dataField' => 'TotalProducto',        'calc' => function($row) { return floatval($row['TotalProducto'] ?? floatval($row['CantidadProducto'] ?? 0)); }],
                ['label' => 'OBSERVACIÓN',    'field' => '__OBS_DETALLE__', 'dataField' => 'TextoObservacionesProducto','calc' => null],
                ['label' => 'COMENTARIOS',    'field' => 'Comentarios',      'dataField' => 'Comentarios',          'calc' => null],
            ];

            // Filtrar columnas ocultas segÃƒÂºn preferencias del usuario
            $visibleColumns = [];
            foreach ($columnDefs as $idx => $colDef) {
                $df = $colDef['dataField'];
                // Si no tiene dataField, siempre se muestra (no togglable)
                if ($df === null) {
                    $visibleColumns[] = $colDef;
                } else {
                    // Si hay preferencia y es false, se oculta
                    $visible = true;
                    if (is_array($columnasVisibles) && isset($columnasVisibles[$df])) {
                        $visible = !empty($columnasVisibles[$df]);
                    }
                    if ($visible) {
                        $visibleColumns[] = $colDef;
                    }
                }
            }

            // Crear libro Excel
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Recepciones Externas');
            $sheet->getSheetView()->setZoomScale(80);
            $sheet->setShowGridlines(false);

            // Escribir cabeceras con ÃƒÂ­ndice de columna dinÃƒÂ¡mico
            $totalCols = count($visibleColumns);
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER]
            ];
            $ultimaLetra = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalCols);
            foreach ($visibleColumns as $colIdx => $colDef) {
                $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1) . '1';
                $sheet->setCellValue($cell, $colDef['label']);
            }
            $sheet->getStyle('A1:' . $ultimaLetra . '1')->applyFromArray($headerStyle);
            $sheet->freezePane('A2');
            $sheet->getRowDimension(1)->setRowHeight(20);

            // Escribir datos
            $filaExcel = 2;
            foreach ($datosPlanos as $row) {
                // Calcular valores derivados una vez por fila
                $obsTexto = strtolower(trim($row['ObservacionTexto'] ?? ''));
                $cantObsRow = floatval($row['CantidadObservada'] ?? 0);
                $codigoObsRow = $row['CodigoProductoObs'] ?? '';
                $pendienteRow = 0;
                $adicionalRow = 0;
                $obsDetalle = '';

                if ($cantObsRow > 0 && $obsTexto && $codigoObsRow) {
                    if ($obsTexto === 'p' || strpos($obsTexto, 'pend') !== false) {
                        $pendienteRow = $cantObsRow;
                        $obsDetalle = "PENDIENTE {$cantObsRow} UND DEL CODIGO {$codigoObsRow} GUIA " . ($row['NumeroGuia'] ?? '');
                    } elseif (strpos($obsTexto, 'regular') !== false || $obsTexto === 'r') {
                        $adicionalRow = $cantObsRow;
                        $obsDetalle = "REGULARIZA {$cantObsRow} UND DEL CODIGO {$codigoObsRow} GUIA " . ($row['NumeroGuia'] ?? '');
                    } elseif ($obsTexto === 'de' || strpos($obsTexto, 'deja') !== false) {
                        $pendienteRow = $cantObsRow;
                        $obsDetalle = "DEJA {$cantObsRow} UND DEL CODIGO {$codigoObsRow} GUIA " . ($row['NumeroGuia'] ?? '');
                    } elseif ($obsTexto === 'le' || strpos($obsTexto, 'lleva') !== false) {
                        $pendienteRow = $cantObsRow;
                        $obsDetalle = "LLEVA {$cantObsRow} UND DEL CODIGO {$codigoObsRow} GUIA " . ($row['NumeroGuia'] ?? '');
                    } elseif ($obsTexto === 'a' || strpos($obsTexto, 'adici') !== false) {
                        $adicionalRow = $cantObsRow;
                        $obsDetalle = "TRAE ADICIONAL {$cantObsRow} UND DEL CODIGO {$codigoObsRow} GUIA " . ($row['NumeroGuia'] ?? '');
                    }
                }

                foreach ($visibleColumns as $colIdx => $colDef) {
                    $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1) . $filaExcel;
                    $valor = '';

                    $field = $colDef['field'] ?? null;
                    // Valores calculados especiales
                    if ($field === '__PENDIENTE__') {
                        $valor = $pendienteRow > 0 ? $pendienteRow : '';
                    } elseif ($field === '__ADICIONAL__') {
                        $valor = $adicionalRow > 0 ? $adicionalRow : '';
                    } elseif ($field === '__OBS_DETALLE__') {
                        $valor = $obsDetalle;
                    } elseif ($colDef['calc'] !== null) {
                        $valor = $colDef['calc']($row);
                    } elseif ($field !== null) {
                        $valor = $row[$field] ?? '';
                    }

                    $sheet->setCellValue($cell, $valor);
                }
                $filaExcel++;
            }

            // Auto-ajustar solo columnas visibles
            for ($i = 1; $i <= $totalCols; $i++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            }

            $lastRow = $filaExcel - 1;
            if ($lastRow >= 1 && $totalCols > 0) {
                $range = 'A1:' . $ultimaLetra . $lastRow;
                $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                for ($r = 2; $r <= $lastRow; $r++) {
                    if ($r % 2 == 0) {
                        $sheet->getStyle('A'.$r.':'.$ultimaLetra.$r)->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('F5F5F5');
                    }
                }
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $fechaActual = date('Y-m-d_H-i-s');
            $fileName = 'RecepcionesExternas_' . $fechaActual . '.xlsx';
            exportExcelFile($writer, $fileName);

        } catch (Exception $e) {
            while (ob_get_level()) { ob_end_clean(); }
            header('Content-Type: text/html; charset=utf-8');
            echo '<div style="padding: 20px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24; margin: 20px;">';
            echo '<h3>Error al exportar a Excel</h3>';
            echo '<p><strong>Mensaje:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p><strong>Archivo:</strong> ' . htmlspecialchars($e->getFile()) . ' en la lÃƒÂ­nea ' . $e->getLine() . '</p>';
            echo '<a href="javascript:history.back()" style="color: #721c24; text-decoration: underline;">Volver</a>';
            echo '</div>';
            error_log('Error en exportarExcelRecepcionesExternas: ' . $e->getMessage() . ' en ' . $e->getFile() . ' lÃƒÂ­nea ' . $e->getLine());
            exit;
        }
    }

    public function obtenerDatosSelectoresRecepcionesExternas() {
        header('Content-Type: application/json');
        try {
            error_log("[ReportesController] Iniciando carga de selectores...");
            
            // Obtener orÃƒÂ­genes
            $origenModel = $this->model('Origen');
            $origenes = $origenModel->getAllForSelect();
            error_log("[ReportesController] OrÃƒÂ­genes cargados: " . count($origenes));

            // Obtener transportistas/empresas
            $transportistaModel = $this->model('Transportista');
            $transportistas = $transportistaModel->getAllForSelect();
            error_log("[ReportesController] Transportistas cargados: " . count($transportistas));
            
            // Empresas son los mismos transportistas
            $empresas = $transportistas;
            error_log("[ReportesController] Empresas (transportistas) cargados: " . count($empresas));

            // Obtener choferes
            $choferModel = $this->model('Chofer');
            $choferes = $choferModel->getAllForSelect();
            error_log("[ReportesController] Choferes cargados: " . count($choferes));

            // Obtener productos
            $productoModel = $this->model('Producto');
            $productos = $productoModel->getAllForSelect();
            error_log("[ReportesController] Productos cargados: " . count($productos));

            // Obtener turnos
            $turnoModel = $this->model('Turno');
            $turnos = $turnoModel->getAllForSelect();
            error_log("[ReportesController] Turnos cargados: " . count($turnos));
            
            // Obtener observaciones
            error_log("[ReportesController] Intentando cargar observaciones...");
            $observacionModel = $this->model('Observacion');
            $observaciones = $observacionModel->getAllForSelect();
            error_log("[ReportesController] Observaciones cargadas: " . count($observaciones));

            $response = [
                'success' => true,
                'data' => [
                    'origenes' => $origenes,
                    'transportistas' => $transportistas,
                    'empresas' => $empresas,
                    'choferes' => $choferes,
                    'productos' => $productos,
                    'turnos' => $turnos,
                    'observaciones' => $observaciones
                ]
            ];
            
            error_log("[ReportesController] Respuesta preparada correctamente");
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("[ReportesController] ERROR en selectores: " . $e->getMessage());
            error_log("[ReportesController] Stack trace: " . $e->getTraceAsString());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    // ===============================================
    // MÃƒâ€°TODOS PARA REPORTE DE PICKING
    // ===============================================
    
    public function obtenerPicking() {
        // Suprimir errores y limpiar buffers para garantizar JSON limpio
        error_reporting(0);
        while (ob_get_level()) { ob_end_clean(); }
        ob_start();
        
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['data' => [], 'totalPaginas' => 1], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $params = json_decode(file_get_contents('php://input'), true);
        $pagina = isset($params['pagina']) ? max(1, (int)$params['pagina']) : 1;
        $filtros = $params['filtros'] ?? [];
        $exportar = isset($params['exportar']) && $params['exportar'] === true;
        
        // Si es exportaciÃƒÂ³n, no usar lÃƒÂ­mite. Por defecto mostrar 20 registros por pÃƒÂ¡gina en la UI
        $limit = $exportar ? 10000 : 20;
        $offset = ($pagina - 1) * $limit;

        try {
            $model = $this->model('RecepcionExterna');
            $result = $model->getReporte($filtros, $limit, $offset);
            
            error_log('[ReportesController - Picking] Datos obtenidos: ' . count($result['data']) . ' registros');
            
            // Limpiar buffer antes del JSON
            ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'data' => $result['data'],
                'totalPaginas' => $result['totalPaginas'],
                'success' => true
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
        } catch (Exception $e) {
            error_log('[ReportesController - Picking] Error: ' . $e->getMessage());
            ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'data' => [],
                'totalPaginas' => 1,
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        exit;
    }
    
    public function exportarExcelPicking() {
        try {
            ini_set('memory_limit', '512M');
            ini_set('max_execution_time', 300);
            require_once __DIR__ . '/../../vendor/autoload.php';

            // Obtener filtros (solo los campos visibles en la grilla)
            $filtros = [];
            if (!empty($_GET['Fecha'])) $filtros['Fecha'] = $_GET['Fecha'];
            if (!empty($_GET['NVale'])) $filtros['NVale'] = $_GET['NVale'];
            if (!empty($_GET['NumeroGuia'])) $filtros['NumeroGuia'] = $_GET['NumeroGuia'];
            if (!empty($_GET['Origen'])) $filtros['Origen'] = $_GET['Origen'];
            if (!empty($_GET['Observaciones'])) $filtros['Observaciones'] = $_GET['Observaciones'];
            if (!empty($_GET['Turno'])) $filtros['Turno'] = $_GET['Turno'];

            // Obtener datos
            $model = $this->model('RecepcionExterna');
            $result = $model->getReporte($filtros, 10000, 0);
            $recepciones = $result['data'];

            if (empty($recepciones)) {
                throw new Exception('No se encontraron datos para exportar con los filtros seleccionados.');
            }

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Picking');
            $sheet->getSheetView()->setZoomScale(80);
            
            // Desactivar lÃƒÂ­neas de cuadrÃƒÂ­cula
            $sheet->setShowGridlines(false);

            // Cabeceras: Fecha, N Vale, N Doc Referencia, N Guia, Origen, Observaciones, Turno
            $cabeceras = [
                'A1' => 'FECHA',
                'B1' => 'NÃ‚Â° VALE',
                'C1' => 'NÃ‚Â° DOC. REFERENCIA',
                'D1' => 'NÃ‚Â° GUÃƒÂA',
                'E1' => 'ORIGEN',
                'F1' => 'OBSERVACIONES',
                'G1' => 'TURNO'
            ];
            foreach ($cabeceras as $celda => $valor) {
                $sheet->setCellValue($celda, $valor);
            }

            // Estilo cabecera
            $styleHeader = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563eb']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER]
            ];
            $sheet->getStyle('A1:G1')->applyFromArray($styleHeader);
            $sheet->getStyle('A1:G1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->freezePane('A2');
            $sheet->getRowDimension(1)->setRowHeight(20);

            $filaExcel = 2;
            foreach ($recepciones as $recepcion) {
                $fechaFormateada = isset($recepcion['Fecha']) && $recepcion['Fecha'] ? date('d/m/Y', strtotime($recepcion['Fecha'])) : '';

                if (isset($recepcion['Guias']) && is_array($recepcion['Guias']) && count($recepcion['Guias']) > 0) {
                    foreach ($recepcion['Guias'] as $guia) {
                        // Una fila por guÃƒÂ­a (no por producto)
                        $sheet->setCellValue('A' . $filaExcel, $fechaFormateada);
                        $sheet->setCellValue('B' . $filaExcel, $recepcion['NVale'] ?? '');
                        $sheet->setCellValue('C' . $filaExcel, $guia['NumeroDocRef'] ?? ($guia['NumeroDocReferencia'] ?? ''));
                        $sheet->setCellValue('D' . $filaExcel, $guia['NumeroGuia'] ?? '');
                        $sheet->setCellValue('E' . $filaExcel, $recepcion['Origen'] ?? '');
                        $sheet->setCellValue('F' . $filaExcel, $guia['ObservacionTexto'] ?? '');
                        $sheet->setCellValue('G' . $filaExcel, $recepcion['Turno'] ?? '');
                        $filaExcel++;
                    }
                } else {
                    $sheet->setCellValue('A' . $filaExcel, $fechaFormateada);
                    $sheet->setCellValue('B' . $filaExcel, $recepcion['NVale'] ?? '');
                    $sheet->setCellValue('C' . $filaExcel, '');
                    $sheet->setCellValue('D' . $filaExcel, '');
                    $sheet->setCellValue('E' . $filaExcel, $recepcion['Origen'] ?? '');
                    $sheet->setCellValue('F' . $filaExcel, 'Sin guÃƒÂ­as');
                    $sheet->setCellValue('G' . $filaExcel, $recepcion['Turno'] ?? '');
                    $filaExcel++;
                }
            }

            // Auto-ajustar columnas
            foreach (range('A', 'G') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Aplicar bordes
            $lastRow = $filaExcel - 1;
            if ($lastRow >= 1) {
                $sheet->getStyle('A1:G' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            }

            // Exportar
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="Reporte_Picking_' . date('Ymd_His') . '.xlsx"');
            header('Cache-Control: max-age=0');

            foreach ($spreadsheet->getAllSheets() as $s) {
                try { $s->getSheetView()->setZoomScale(80); } catch (Exception $__e) { }
            }
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        } catch (Exception $e) {
            echo '<div style="background:#f8d7da; color:#721c24; padding:20px; border:1px solid #f5c6cb; border-radius:4px; font-family:Arial,sans-serif;">';
            echo '<h2 style="margin-top:0;">Error al exportar Excel</h2>';
            echo '<p><strong>Mensaje:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p><strong>Archivo:</strong> ' . htmlspecialchars($e->getFile()) . ' en la lÃƒÂ­nea ' . $e->getLine() . '</p>';
            echo '<a href="javascript:history.back()" style="color: #721c24; text-decoration: underline;">Volver</a>';
            echo '</div>';
            error_log('Error en exportarExcelPicking: ' . $e->getMessage() . ' en ' . $e->getFile() . ' lÃƒÂ­nea ' . $e->getLine());
            exit;
        }
    }
    
    public function exportarExcelRecepcionesExt() {
        try {
            ini_set('memory_limit', '1024M');
            ini_set('max_execution_time', 600);
            require_once __DIR__ . '/../../vendor/autoload.php';

            // Leer datos enviados desde el cliente (datos ya filtrados y pivoteados)
            $input = [];
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
            }

            $datosCliente = $input['datos'] ?? [];
            $paginaActual = isset($input['paginaActual']) ? max(1, (int)$input['paginaActual']) : 1;
            $exportarTodo = !empty($input['exportarTodo']);
            $LIMIT_PAGINA = 20;

            if (empty($datosCliente)) {
                throw new Exception('No se encontraron datos para exportar.');
            }

            // Aplicar paginaciÃƒÂ³n:
            // - Si hay filtros activos (exportarTodo=true) Ã¢â€ â€™ exportar todos los datos
            // - Si no hay filtros Ã¢â€ â€™ exportar solo la pÃƒÂ¡gina actual (20 filas)
            if (!$exportarTodo) {
                $offset = ($paginaActual - 1) * $LIMIT_PAGINA;
                $filasPivot = array_slice($datosCliente, $offset, $LIMIT_PAGINA);
            } else {
                $filasPivot = $datosCliente;
            }

            if (empty($filasPivot)) {
                throw new Exception('No se encontraron datos para exportar en la pÃƒÂ¡gina actual.');
            }

            // ============================================================
            // CREAR EXCEL CON ESTILO PROFESIONAL
            // ============================================================
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Recepciones Ext');
            $sheet->getSheetView()->setZoomScale(80);
            $sheet->setShowGridlines(false);

            // Cabeceras (mismo orden que el reporte)
            $cabeceras = [
                'A1' => 'FECHA', 'B1' => 'TURNO', 'C1' => 'NÃ‚Â° VALE',
                'D1' => 'NÃ‚Â° GUIA', 'E1' => 'NÃ‚Â° DOC. REF.', 'F1' => 'ORIGEN',
                'G1' => 'TRANSPORTISTA', 'H1' => 'CHOFER', 'I1' => 'OBSERVACIONES',
                'J1' => '19003031', 'K1' => '19002924', 'L1' => '19003730',
                'M1' => '19003521', 'N1' => 'TOTAL GRAL.'
            ];
            foreach ($cabeceras as $celda => $valor) {
                $sheet->setCellValue($celda, $valor);
            }

            // Estilo de cabecera
            $styleHeader = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER]
            ];
            $sheet->getStyle('A1:N1')->applyFromArray($styleHeader);
            $sheet->freezePane('A2');
            $sheet->getRowDimension(1)->setRowHeight(20);

            // Escribir datos
            $filaExcel = 2;
            foreach ($filasPivot as $row) {
                $fechaFormateada = !empty($row['Fecha']) ? date('d/m/Y', strtotime($row['Fecha'])) : '';
                $nroVale = isset($row['NVale']) ? str_pad(preg_replace('/\D/', '', (string)$row['NVale']), 6, '0', STR_PAD_LEFT) : '';

                $sheet->setCellValue('A' . $filaExcel, $fechaFormateada);
                $sheet->setCellValue('B' . $filaExcel, $row['Turno'] ?? '');
                $sheet->setCellValue('C' . $filaExcel, $nroVale);
                $sheet->setCellValue('D' . $filaExcel, $row['NumeroGuia'] ?? '');
                $sheet->setCellValue('E' . $filaExcel, $row['NumeroDocRef'] ?? '');
                $sheet->setCellValue('F' . $filaExcel, $row['Origen'] ?? '');
                $sheet->setCellValue('G' . $filaExcel, $row['Transportista'] ?? $row['Empresa'] ?? '');
                $sheet->setCellValue('H' . $filaExcel, $row['Chofer'] ?? '');
                $sheet->setCellValue('I' . $filaExcel, $row['Observaciones'] ?? $row['ObservacionTexto'] ?? '');
                $sheet->setCellValue('J' . $filaExcel, $row['Prod19003031'] ?? 0);
                $sheet->setCellValue('K' . $filaExcel, $row['Prod19002924'] ?? 0);
                $sheet->setCellValue('L' . $filaExcel, $row['Prod19003730'] ?? 0);
                $sheet->setCellValue('M' . $filaExcel, $row['Prod19003521'] ?? 0);
                $sheet->setCellValue('N' . $filaExcel, $row['TotalGeneral'] ?? 0);
                $filaExcel++;
            }

            // Auto-ajustar columnas
            foreach (range('A', 'N') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Bordes y estilos de datos
            $lastRow = $filaExcel - 1;
            if ($lastRow >= 1) {
                $range = 'A1:N' . $lastRow;
                $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                // Filas alternadas
                for ($r = 2; $r <= $lastRow; $r++) {
                    if ($r % 2 == 0) {
                        $sheet->getStyle('A'.$r.':N'.$r)->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('F5F5F5');
                    }
                    // Centrar columnas de productos y total
                    $sheet->getStyle('J'.$r)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('K'.$r)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('L'.$r)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('M'.$r)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('N'.$r)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    // Negrita para Total
                    $sheet->getStyle('N'.$r)->getFont()->setBold(true);
                }
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $fechaActual = date('Y-m-d_H-i-s');
            $fileName = 'Reporte_Recepciones_Ext_' . $fechaActual . '.xlsx';
            exportExcelFile($writer, $fileName);

        } catch (Exception $e) {
            while (ob_get_level()) { ob_end_clean(); }
            header('Content-Type: text/html; charset=utf-8');
            echo '<div style="padding: 20px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24; margin: 20px;">';
            echo '<h3>Error al exportar a Excel</h3>';
            echo '<p><strong>Mensaje:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p><strong>Archivo:</strong> ' . htmlspecialchars($e->getFile()) . ' en la lÃƒÂ­nea ' . $e->getLine() . '</p>';
            echo '<a href="javascript:history.back()" style="color: #721c24; text-decoration: underline;">Volver</a>';
            echo '</div>';
            error_log('Error en exportarExcelRecepcionesExt: ' . $e->getMessage() . ' en ' . $e->getFile() . ' lÃƒÂ­nea ' . $e->getLine());
            exit;
        }
    }
    
    public function exportarConsolidado() {
        try {
            // Limpiar completamente cualquier salida previa
            while (ob_get_level()) {
                ob_end_clean();
            }
            ob_start();
            
            // ConfiguraciÃƒÂ³n agresiva de lÃƒÂ­mites
            ini_set('memory_limit', '2048M');
            ini_set('max_execution_time', 900);
            ini_set('mysql.connect_timeout', '300');
            set_time_limit(900);
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
            
            require_once __DIR__ . '/../../vendor/autoload.php';

            // Aumentar timeout de MySQL
            try {
                $db = Database::getInstance()->getConnection();
                $db->exec("SET SESSION wait_timeout = 600");
                $db->exec("SET SESSION interactive_timeout = 600");
                $db->exec("SET SESSION max_execution_time = 600000");
            } catch (Exception $e) {
                error_log("No se pudieron ajustar timeouts MySQL: " . $e->getMessage());
            }

            // CARGAR PLANTILLA
            $plantillaPath = __DIR__ . '/../../storage/templates/plantilla_consolidado.xlsx';
            
            if (!file_exists($plantillaPath)) {
                throw new Exception('No se encontrÃƒÂ³ la plantilla en: ' . $plantillaPath);
            }
            
            // PASO 1: Extraer XMLs de tablas dinÃƒÂ¡micas y segmentadores ANTES de cargar con PhpSpreadsheet
            $pivotFiles = [];
            $pivotCacheFiles = [];
            $slicerFiles = [];
            $slicerCacheFiles = [];
            
            $zip = new ZipArchive();
            if ($zip->open($plantillaPath) === true) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    if (strpos($filename, 'xl/pivotTables/') === 0 || 
                        strpos($filename, 'xl/pivotCache/') === 0 ||
                        strpos($filename, 'xl/slicers/') === 0 ||
                        strpos($filename, 'xl/slicerCaches/') === 0) {
                        $content = $zip->getFromIndex($i);
                        if ($content !== false) {
                            if (strpos($filename, 'xl/pivotTables/') === 0) {
                                $pivotFiles[$filename] = $content;
                            } elseif (strpos($filename, 'xl/pivotCache/') === 0) {
                                $pivotCacheFiles[$filename] = $content;
                            } elseif (strpos($filename, 'xl/slicers/') === 0) {
                                $slicerFiles[$filename] = $content;
                            } elseif (strpos($filename, 'xl/slicerCaches/') === 0) {
                                $slicerCacheFiles[$filename] = $content;
                            }
                            error_log("Preservando: $filename");
                        }
                    }
                }
                $zip->close();
                error_log('Tablas dinÃƒÂ¡micas encontradas: ' . count($pivotFiles));
                error_log('Segmentadores encontrados: ' . count($slicerFiles));
                error_log('CachÃƒÂ©s de segmentadores encontrados: ' . count($slicerCacheFiles));
            }
            
            // PASO 2: Crear copia temporal
            $tempPath = __DIR__ . '/../../storage/temp/consolidado_' . uniqid() . '.xlsx';
            $tempDir = dirname($tempPath);
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            if (!copy($plantillaPath, $tempPath)) {
                throw new Exception('No se pudo crear copia temporal de la plantilla');
            }
            
            // PASO 3: Usar PhpSpreadsheet para insertar datos
            $reader = IOFactory::createReader('Xlsx');
            $spreadsheet = $reader->load($tempPath);
            unset($reader);
            gc_collect_cycles();
            
            error_log('Insertando datos en Despachos Internos');
            $this->insertarDatosDespachosInternos($spreadsheet);
            gc_collect_cycles();
            
            error_log('Insertando datos en Despachos Externos');
            $this->insertarDatosDespachosExternos($spreadsheet);
            gc_collect_cycles();
            
            error_log('Insertando datos en Recepciones Internas');
            $this->insertarDatosRecepcionesInternas($spreadsheet);
            gc_collect_cycles();
            
            error_log('Insertando datos en Recepciones Externas');
            $this->insertarDatosRecepcionesExternas($spreadsheet);
            gc_collect_cycles();

            // Insertar hoja Picking (registros equivalentes a exportarPicking)
            error_log('Insertando datos en Picking (hoja adicional)');
            $this->insertarDatosPicking($spreadsheet);
            gc_collect_cycles();

            // Insertar hoja Recepciones Ext. (hoja adicional con los registros del acceso "Reporte Recepciones Ext.")
            error_log('Insertando datos en Recepciones Ext. (hoja adicional)');
            $this->insertarDatosRecepcionesExt($spreadsheet);
            gc_collect_cycles();

            $spreadsheet->setActiveSheetIndex(0);
            
            // Asegurar zoom en todas las hojas antes de guardar
            foreach ($spreadsheet->getAllSheets() as $s) {
                try { $s->getSheetView()->setZoomScale(80); } catch (Exception $__e) { }
            }
            // Guardar con PhpSpreadsheet
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempPath);
            
            // Liberar memoria
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            unset($writer);
            gc_collect_cycles();
            
            error_log('Datos insertados con PhpSpreadsheet');
            
            // PASO 4: Reinyectar los archivos de tablas dinÃƒÂ¡micas y segmentadores preservados
            if (count($pivotFiles) > 0 || count($pivotCacheFiles) > 0 || count($slicerFiles) > 0 || count($slicerCacheFiles) > 0) {
                $zip = new ZipArchive();
                if ($zip->open($tempPath) === true) {
                    // Restaurar archivos de tablas dinÃƒÂ¡micas
                    foreach ($pivotFiles as $filename => $content) {
                        if ($zip->locateName($filename) !== false) {
                            $zip->deleteName($filename);
                        }
                        $zip->addFromString($filename, $content);
                        error_log("Restaurando tabla dinÃƒÂ¡mica: $filename");
                    }

                    // Restaurar cachÃƒÂ©s de tablas dinÃƒÂ¡micas
                    foreach ($pivotCacheFiles as $filename => $content) {
                        if ($zip->locateName($filename) !== false) {
                            $zip->deleteName($filename);
                        }
                        $zip->addFromString($filename, $content);
                        error_log("Restaurando cachÃƒÂ©: $filename");
                    }

                    // Restaurar archivos de segmentadores
                    foreach ($slicerFiles as $filename => $content) {
                        if ($zip->locateName($filename) !== false) {
                            $zip->deleteName($filename);
                        }
                        $zip->addFromString($filename, $content);
                        error_log("Restaurando segmentador: $filename");
                    }

                    // Restaurar cachÃƒÂ©s de segmentadores
                    foreach ($slicerCacheFiles as $filename => $content) {
                        if ($zip->locateName($filename) !== false) {
                            $zip->deleteName($filename);
                        }
                        $zip->addFromString($filename, $content);
                        error_log("Restaurando cachÃƒÂ© de segmentador: $filename");
                    }
                    
                    $zip->close();
                    error_log('Tablas dinÃƒÂ¡micas y segmentadores restaurados exitosamente');
                }
            }
            
            // PASO 4.5: Asegurar que la hoja 'Despachos Externos' tenga pane/selection (freeze) DESPUÃƒâ€°S de restaurar pivots
            $zip = new \ZipArchive();
            if ($zip->open($tempPath) === true) {
                $sheetPath = 'xl/worksheets/sheet2.xml';
                $content = $zip->getFromName($sheetPath);
                if ($content !== false) {
                    error_log('Inyectando freeze pane en Despachos Externos (DOM)...');
                    libxml_use_internal_errors(true);
                    $doc = new \DOMDocument();
                    $loaded = $doc->loadXML($content, LIBXML_NONET);
                    if ($loaded) {
                        $ns = $doc->documentElement->namespaceURI;
                        $sheetViews = $doc->getElementsByTagName('sheetView');
                        if ($sheetViews->length > 0) {
                            foreach ($sheetViews as $sv) {
                                // Eliminar nodos pane y selection existentes
                                $toRemove = [];
                                foreach ($sv->childNodes as $child) {
                                    if ($child->nodeType === XML_ELEMENT_NODE && in_array($child->localName, ['pane','selection'])) {
                                        $toRemove[] = $child;
                                    }
                                }
                                foreach ($toRemove as $n) {
                                    $sv->removeChild($n);
                                }

                                // Crear pane y selection con el mismo namespace si existe
                                if ($ns) {
                                    $pane = $doc->createElementNS($ns, 'pane');
                                    $selection = $doc->createElementNS($ns, 'selection');
                                } else {
                                    $pane = $doc->createElement('pane');
                                    $selection = $doc->createElement('selection');
                                }
                                $pane->setAttribute('ySplit', '1');
                                $pane->setAttribute('topLeftCell', 'A2');
                                $pane->setAttribute('activePane', 'bottomLeft');
                                $pane->setAttribute('state', 'frozen');

                                $selection->setAttribute('pane', 'bottomLeft');
                                $selection->setAttribute('activeCell', 'A2');
                                $selection->setAttribute('sqref', 'A2');

                                // Insertar pane y selection al final de sheetView
                                $sv->appendChild($pane);
                                $sv->appendChild($selection);
                            }

                            $newContent = $doc->saveXML();
                            if ($zip->locateName($sheetPath) !== false) {
                                $zip->deleteName($sheetPath);
                            }
                            $zip->addFromString($sheetPath, $newContent);
                            error_log('Ã¢Å“â€œ Freeze pane inyectado exitosamente en sheet2.xml (DOM)');
                        } else {
                            error_log('Ã¢Å“â€” No se encontrÃƒÂ³ <sheetView> en sheet2.xml (DOM)');
                        }
                    } else {
                        $errors = libxml_get_errors();
                        foreach ($errors as $err) {
                            error_log('XML error al cargar sheet2.xml: ' . trim($err->message));
                        }
                        libxml_clear_errors();
                        error_log('Ã¢Å“â€” No se pudo parsear sheet2.xml con DOMDocument');
                    }
                    libxml_use_internal_errors(false);
                } else {
                    error_log('Ã¢Å“â€” No se pudo leer sheet2.xml del archivo');
                }
                $zip->close();
            } else {
                error_log('Ã¢Å“â€” No se pudo abrir el archivo temporal para inyectar pane');
            }
            
            // Liberar memoria de arrays de XMLs
            unset($pivotFiles);
            unset($pivotCacheFiles);
            unset($slicerFiles);
            unset($slicerCacheFiles);
            
            // PASO 5: Enviar archivo al navegador
            ob_end_clean();

                        $filename = 'Reporte de RecepciÃƒÂ³n y Despachos al ' . date('d-m-Y') . '.xlsx';

                        // Preparar Content-Disposition con soporte UTF-8 (RFC 5987) y fallback
                        $utf8Filename = $filename;
                        // Intentar fallback a ISO-8859-1 para navegadores antiguos
                        $fallback = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $utf8Filename);
                        if ($fallback === false || trim($fallback) === '') {
                            // Reemplazar caracteres no-ASCII si iconv falla
                            $fallback = preg_replace('/[^\x20-\x7E]/', '_', $utf8Filename);
                        }
                        // Evitar comillas problemÃƒÂ¡ticas
                        $fallback = str_replace('"', "'", $fallback);

                        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                        header("Content-Disposition: attachment; filename=\"{$fallback}\"; filename*=UTF-8''" . rawurlencode($utf8Filename));
                        header('Content-Transfer-Encoding: binary');
                        header('Accept-Ranges: bytes');
                        header('Cache-Control: max-age=0');
                        header('Cache-Control: max-age=1');
                        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
                        header('Cache-Control: cache, must-revalidate');
                        header('Pragma: public');
                        header('Content-Length: ' . filesize($tempPath));
            
            readfile($tempPath);
            
            // Limpiar archivos temporales
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
            
            exit;
            
        } catch (Exception $e) {
            error_log('Error en exportarConsolidado: ' . $e->getMessage() . ' en ' . $e->getFile() . ' lÃƒÂ­nea ' . $e->getLine());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            if (isset($tempPath) && file_exists($tempPath)) {
                unlink($tempPath);
            }
            
            header('Content-Type: text/html; charset=utf-8');
            die('<h1>Error al generar el reporte consolidado</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>');
        }
    }
    
    private function generarHojaDespachosInternos($spreadsheet, $sheetIndex) {
        if ($sheetIndex > 0) {
            $sheet = $spreadsheet->createSheet($sheetIndex);
        } else {
            $sheet = $spreadsheet->getActiveSheet();
        }
        $sheet->setTitle('Despachos Internos');
        $sheet->getSheetView()->setZoomScale(80);
        
        // Desactivar lÃƒÂ­neas de cuadrÃƒÂ­cula
        $sheet->setShowGridlines(false);
        
        // Obtener datos
        $model = $this->model('DespachoInterno');
        $result = $model->getReporte([], 10000, 0);
        $despachos = $result['data'];
        
        error_log('Despachos Internos - Total registros: ' . count($despachos));
        if (!empty($despachos)) {
            error_log('Despachos Internos - Primer registro: ' . json_encode($despachos[0]));
        }
        
        // Cabeceras
        $cabeceras = [
            'A1' => 'NRO VALE', 'B1' => 'FECHA', 'C1' => 'HORA', 'D1' => 'TURNO',
            'E1' => 'ÃƒÂREA', 'F1' => 'SUBÃƒÂREA', 'G1' => 'DESPACHADOR', 'H1' => 'RECEPCIONISTA',
            'I1' => 'VERIFICADOR', 'J1' => 'CÃƒâ€œDIGO', 'K1' => 'PRODUCTO', 'L1' => 'UNIDAD DE MEDIDA',
            'M1' => 'CANTIDAD', 'N1' => 'COMENTARIOS'
        ];
        foreach ($cabeceras as $celda => $valor) {
            $sheet->setCellValue($celda, $valor);
        }
        
        $styleHeader = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
        ];
        $sheet->getStyle('A1:N1')->applyFromArray($styleHeader);
        $sheet->getStyle('A1:N1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->freezePane('A2');
        $sheet->getRowDimension(1)->setRowHeight(20);
        
        $filaExcel = 2;
        if (!empty($despachos)) {
            foreach ($despachos as $despacho) {
                $fechaFormateada = isset($despacho['Fecha']) ? date('d/m/Y', strtotime($despacho['Fecha'])) : '';
                $productos = isset($despacho['Detalles']) && is_array($despacho['Detalles']) ? $despacho['Detalles'] : [];
                
                error_log('Despacho ' . ($despacho['NVale'] ?? 'N/A') . ' - Productos: ' . count($productos));
                
                if (count($productos) > 0) {
                    foreach ($productos as $producto) {
                        $sheet->setCellValue('A' . $filaExcel, $despacho['NVale'] ?? '');
                        $sheet->setCellValue('B' . $filaExcel, $fechaFormateada);
                        $sheet->setCellValue('C' . $filaExcel, $despacho['Hora'] ?? '');
                        $sheet->setCellValue('D' . $filaExcel, $despacho['Turno'] ?? '');
                        $sheet->setCellValue('E' . $filaExcel, $despacho['Area'] ?? '');
                        $sheet->setCellValue('F' . $filaExcel, $despacho['Subarea'] ?? '');
                        $sheet->setCellValue('G' . $filaExcel, $despacho['Despachador'] ?? '');
                        $sheet->setCellValue('H' . $filaExcel, $despacho['Recepcionista'] ?? '');
                        $sheet->setCellValue('I' . $filaExcel, $despacho['Verificador'] ?? '');
                        $sheet->setCellValue('J' . $filaExcel, $producto['Codigo'] ?? '');
                        $sheet->setCellValue('K' . $filaExcel, $producto['Producto'] ?? '');
                        $sheet->setCellValue('L' . $filaExcel, $producto['UnidadMedida'] ?? '');
                        $sheet->setCellValue('M' . $filaExcel, $producto['Cantidad'] ?? '');
                        $sheet->setCellValue('N' . $filaExcel, $producto['Comentarios'] ?? '');
                        $filaExcel++;
                    }
                }
            }
        }
        
        error_log('Despachos Internos - Filas escritas: ' . ($filaExcel - 2));
        
        // Ajustar columnas
        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
    
    private function generarHojaDespachosExternos($spreadsheet) {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Despachos Externos');
        $sheet->getSheetView()->setZoomScale(80);
        
        // Desactivar lÃƒÂ­neas de cuadrÃƒÂ­cula
        $sheet->setShowGridlines(false);
        
        // Obtener datos
        $model = $this->model('DespachoExterno');
        $result = $model->getReporte([], 10000, 0);
        $despachos = $result['data'] ?? [];
        
        // Cabeceras en el orden solicitado
        $cabeceras = [
            'A1' => 'NÃ‚Â° VALE', 'B1' => 'FECHA', 'C1' => 'HORA', 'D1' => 'TURNO', 'E1' => 'DESPACHADOR',
            'F1' => 'DESTINO', 'G1' => 'RUC', 'H1' => 'DIRECCIÃƒâ€œN', 'I1' => 'CHOFER', 'J1' => 'BREVETE',
            'K1' => 'TRANSPORTISTA', 'L1' => 'RUC TRANSPORTISTA', 'M1' => 'PLACA TRACTO', 'N1' => 'CONSTANCIA INSC. TRACTO',
            'O1' => 'PLACA CARRETA', 'P1' => 'CONSTANCIA INSC. CARRETA', 'Q1' => 'GUÃƒÂA REMISIÃƒâ€œN',
            'R1' => 'CÃƒâ€œDIGO', 'S1' => 'PRODUCTO', 'T1' => 'UNIDAD MEDIDA', 'U1' => 'CANTIDAD', 'V1' => 'COMENTARIOS', 'W1' => 'ESTADO'
        ];
        foreach ($cabeceras as $celda => $valor) {
            $sheet->setCellValue($celda, $valor);
        }
        
        $styleHeader = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
        ];
        $sheet->getStyle('A1:W1')->applyFromArray($styleHeader);
        $sheet->getStyle('A1:W1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->freezePane('A2');
        $sheet->getRowDimension(1)->setRowHeight(20);
        
        $filaExcel = 2;
        if (!empty($despachos)) {
            foreach ($despachos as $despacho) {
                $fechaFormateada = isset($despacho['Fecha']) ? date('d/m/Y', strtotime($despacho['Fecha'])) : '';
                $productos = isset($despacho['Detalles']) && is_array($despacho['Detalles']) ? $despacho['Detalles'] : [];
                
                if (count($productos) > 0) {
                    foreach ($productos as $producto) {
                        $sheet->setCellValue('A' . $filaExcel, $despacho['NVale'] ?? '');
                        $sheet->setCellValue('B' . $filaExcel, $fechaFormateada);
                        $sheet->setCellValue('C' . $filaExcel, $despacho['Hora'] ?? '');
                        $sheet->setCellValue('D' . $filaExcel, $despacho['Turno'] ?? '');
                        $sheet->setCellValue('E' . $filaExcel, $despacho['Despachador'] ?? '');
                        $sheet->setCellValue('F' . $filaExcel, $despacho['Destino'] ?? '');
                        $sheet->setCellValue('G' . $filaExcel, $despacho['RUC'] ?? '');
                        $sheet->setCellValue('H' . $filaExcel, $despacho['Direccion'] ?? '');
                        $sheet->setCellValue('I' . $filaExcel, $despacho['Chofer'] ?? '');
                        $sheet->setCellValue('J' . $filaExcel, $despacho['Brevete'] ?? '');
                        $sheet->setCellValue('K' . $filaExcel, $despacho['Transportista'] ?? '');
                        $sheet->setCellValue('L' . $filaExcel, $despacho['RUC_Transportista'] ?? '');
                        $sheet->setCellValue('M' . $filaExcel, $despacho['Placa_Tracto'] ?? '');
                        $sheet->setCellValue('N' . $filaExcel, $despacho['Constancia_Inscripcion'] ?? '');
                        $sheet->setCellValue('O' . $filaExcel, $despacho['Placa_Carreta'] ?? '');
                        $sheet->setCellValue('P' . $filaExcel, $despacho['Constancia_Inscripcion_2'] ?? '');
                        $sheet->setCellValue('Q' . $filaExcel, $despacho['GR'] ?? '');
                        $sheet->setCellValue('R' . $filaExcel, $producto['Codigo'] ?? '');
                        $sheet->setCellValue('S' . $filaExcel, $producto['Producto'] ?? '');
                        $sheet->setCellValue('T' . $filaExcel, $producto['UnidadMedida'] ?? '');
                        $sheet->setCellValue('U' . $filaExcel, $producto['Cantidad'] ?? '');
                        $sheet->setCellValue('V' . $filaExcel, $producto['Comentarios'] ?? '');
                        $sheet->setCellValue('W' . $filaExcel, $despacho['Estado'] ?? '');
                        $filaExcel++;
                    }
                }
            }
        }
        
        // Ajustar columnas
        foreach (range('A', 'W') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        // Asegurar cabecera fijada al terminar de insertar y ajustar columnas
        $sheet->freezePane('A2');
        // Forzar que la celda seleccionada sea A2 (ayuda a que Excel muestre la cabecera congelada al abrir)
        try {
            $sheet->setSelectedCell('A2');
        } catch (Exception $__sel) { /* no bloquear si falla en versiones antiguas */ }
    }
    
    private function generarHojaRecepcionesInternas($spreadsheet) {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Recepciones Internas');
        $sheet->getSheetView()->setZoomScale(80);
        
        // Desactivar lÃƒÂ­neas de cuadrÃƒÂ­cula
        $sheet->setShowGridlines(false);
        
        // Obtener datos
        $model = $this->model('RecepcionInterna');
        $result = $model->getReporte([], 10000, 0);
        $recepciones = $result['data'];
        
        // Cabeceras
        $cabeceras = [
            'A1' => 'NRO VALE', 'B1' => 'FECHA', 'C1' => 'HORA', 'D1' => 'TURNO', 'E1' => 'ÃƒÂREA',
            'F1' => 'SUBÃƒÂREA', 'G1' => 'DESPACHADOR', 'H1' => 'MEDIO TRANSPORTE', 'I1' => 'VERIFICADOR',
            'J1' => 'CÃƒâ€œDIGO', 'K1' => 'PRODUCTO', 'L1' => 'UNIDAD MEDIDA', 'M1' => 'CANTIDAD', 'N1' => 'COMENTARIOS', 'O1' => 'ESTADO'
        ];
        foreach ($cabeceras as $celda => $valor) {
            $sheet->setCellValue($celda, $valor);
        }
        
        $styleHeader = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
        ];
        $sheet->getStyle('A1:O1')->applyFromArray($styleHeader);
        $sheet->getStyle('A1:O1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->freezePane('A2');
        $sheet->getRowDimension(1)->setRowHeight(20);
        
        $filaExcel = 2;
        if (!empty($recepciones)) {
            foreach ($recepciones as $recepcion) {
                $fechaFormateada = isset($recepcion['Fecha']) ? date('d/m/Y', strtotime($recepcion['Fecha'])) : '';
                $productos = isset($recepcion['Detalles']) && is_array($recepcion['Detalles']) ? $recepcion['Detalles'] : [];
                
                if (count($productos) > 0) {
                    foreach ($productos as $producto) {
                        $sheet->setCellValue('A' . $filaExcel, $recepcion['NVale'] ?? '');
                        $sheet->setCellValue('B' . $filaExcel, $fechaFormateada);
                        $sheet->setCellValue('C' . $filaExcel, $recepcion['Hora'] ?? '');
                        $sheet->setCellValue('D' . $filaExcel, $recepcion['Turno'] ?? '');
                        $sheet->setCellValue('E' . $filaExcel, $recepcion['Area'] ?? '');
                        $sheet->setCellValue('F' . $filaExcel, $recepcion['Subarea'] ?? '');
                        $sheet->setCellValue('G' . $filaExcel, $recepcion['Despachador'] ?? '');
                        $sheet->setCellValue('H' . $filaExcel, $recepcion['MedioTransporte'] ?? '');
                        $sheet->setCellValue('I' . $filaExcel, $recepcion['Verificador'] ?? '');
                        $sheet->setCellValue('J' . $filaExcel, $producto['Codigo'] ?? '');
                        $sheet->setCellValue('K' . $filaExcel, $producto['Producto'] ?? '');
                        $sheet->setCellValue('L' . $filaExcel, $producto['UnidadMedida'] ?? '');
                        $sheet->setCellValue('M' . $filaExcel, $producto['Cantidad'] ?? '');
                        $sheet->setCellValue('N' . $filaExcel, $producto['Comentarios'] ?? '');
                        $sheet->setCellValue('O' . $filaExcel, $recepcion['Estado'] ?? '');
                        $filaExcel++;
                    }
                }
            }
        }
        
        // Ajustar columnas
        foreach (range('A', 'O') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
    
    private function generarHojaRecepcionesExternas($spreadsheet) {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Recepciones Externas');
        $sheet->getSheetView()->setZoomScale(80);
        $sheet->setShowGridlines(false);
        
        // Obtener datos
        $model = $this->model('RecepcionExterna');
        $result = $model->getReporte([], 10000, 0);
        $recepciones = $result['data'];
        
        // Cabeceras
        $cabeceras = [
            'A1' => 'NÃ‚Â° VALE', 'B1' => 'FECHA', 'C1' => 'HORA', 'D1' => 'TURNO', 'E1' => 'ORIGEN',
            'F1' => 'EMPRESA', 'G1' => 'RUC', 'H1' => 'CHOFER', 'I1' => 'BREVETE',
            'J1' => 'NÃ‚Â° GUÃƒÂA', 'K1' => 'OBSERVACIÃƒâ€œN', 'L1' => 'CÃƒâ€œD. OBS', 'M1' => 'CANT. OBS',
            'N1' => 'CÃƒâ€œDIGO PRODUCTO', 'O1' => 'DESCRIPCIÃƒâ€œN', 'P1' => 'CANTIDAD', 'Q1' => 'UNIDAD', 'R1' => 'ESTADO'
        ];
        foreach ($cabeceras as $celda => $valor) {
            $sheet->setCellValue($celda, $valor);
        }
        
        $styleHeader = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
        ];
        $sheet->getStyle('A1:R1')->applyFromArray($styleHeader);
        $sheet->getStyle('A1:R1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->freezePane('A2');
        $sheet->getRowDimension(1)->setRowHeight(20);
        
        $filaExcel = 2;
        if (!empty($recepciones)) {
            foreach ($recepciones as $recepcion) {
                $fechaFormateada = isset($recepcion['Fecha']) && $recepcion['Fecha'] ? date('d/m/Y', strtotime($recepcion['Fecha'])) : '';
                
                if (isset($recepcion['Guias']) && is_array($recepcion['Guias']) && count($recepcion['Guias']) > 0) {
                    foreach ($recepcion['Guias'] as $guia) {
                        if (isset($guia['Productos']) && is_array($guia['Productos']) && count($guia['Productos']) > 0) {
                            foreach ($guia['Productos'] as $producto) {
                                $sheet->setCellValue('A' . $filaExcel, $recepcion['NVale'] ?? '');
                                $sheet->setCellValue('B' . $filaExcel, $fechaFormateada);
                                $sheet->setCellValue('C' . $filaExcel, $recepcion['Hora'] ?? '');
                                $sheet->setCellValue('D' . $filaExcel, $recepcion['Turno'] ?? '');
                                $sheet->setCellValue('E' . $filaExcel, $recepcion['Origen'] ?? '');
                                $sheet->setCellValue('F' . $filaExcel, $recepcion['Empresa'] ?? '');
                                $sheet->setCellValue('G' . $filaExcel, $recepcion['RUC'] ?? '');
                                $sheet->setCellValue('H' . $filaExcel, $recepcion['Chofer'] ?? '');
                                $sheet->setCellValue('I' . $filaExcel, $recepcion['Brevete'] ?? '');
                                $sheet->setCellValue('J' . $filaExcel, $guia['NumeroGuia'] ?? '');
                                $sheet->setCellValue('K' . $filaExcel, $guia['ObservacionTexto'] ?? '');
                                $sheet->setCellValue('L' . $filaExcel, $guia['CodigoProductoObs'] ?? '');
                                $sheet->setCellValue('M' . $filaExcel, $guia['CantidadObservada'] ?? '');
                                $sheet->setCellValue('N' . $filaExcel, $producto['CodigoProducto'] ?? '');
                                $sheet->setCellValue('O' . $filaExcel, $producto['DescripcionProducto'] ?? '');
                                $sheet->setCellValue('P' . $filaExcel, $producto['Cantidad'] ?? '');
                                $sheet->setCellValue('Q' . $filaExcel, $producto['UnidadMedida'] ?? '');
                                $sheet->setCellValue('R' . $filaExcel, $recepcion['Estado'] ?? '');
                                $filaExcel++;
                            }
                        }
                    }
                }
            }
        }
        
        // Ajustar columnas
        foreach (range('A', 'R') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
    
    // ==================== MÃƒâ€°TODOS DE INSERCIÃƒâ€œN DE DATOS (CON PLANTILLA) ====================
    
    private function insertarDatosDespachosInternos($spreadsheet) {
        $sheet = $spreadsheet->getSheetByName('Despachos Internos');
        if (!$sheet) {
            throw new Exception('No se encontrÃƒÂ³ la hoja "Despachos Internos" en la plantilla');
        }
        
        // Limpiar datos anteriores
        $highestRow = $sheet->getHighestRow();
        if ($highestRow > 1) {
            $sheet->removeRow(2, $highestRow - 1);
        }
        
        // Reconectar a base de datos para evitar timeout
        try {
            $db = Database::getInstance()->getConnection();
            $db->exec("SET SESSION wait_timeout = 600");
        } catch (Exception $e) {
            error_log("Advertencia MySQL: " . $e->getMessage());
        }
        
        // Obtener datos y liberar modelo inmediatamente
        $model = $this->model('DespachoInterno');
        $result = $model->getReporte([], 10000, 0);
        $despachos = $result['data'];
        unset($model);
        unset($result);
        
        error_log('Insertando ' . count($despachos) . ' despachos internos');
        
        // Insertar datos desde fila 2
        $filaExcel = 2;
        if (!empty($despachos)) {
            foreach ($despachos as $despacho) {
                $fechaFormateada = isset($despacho['Fecha']) ? date('d/m/Y', strtotime($despacho['Fecha'])) : '';
                $productos = isset($despacho['Detalles']) && is_array($despacho['Detalles']) ? $despacho['Detalles'] : [];
                
                if (count($productos) > 0) {
                    foreach ($productos as $producto) {
                        $sheet->setCellValue('A' . $filaExcel, $despacho['NVale'] ?? '');
                        $sheet->setCellValue('B' . $filaExcel, $fechaFormateada);
                        $sheet->setCellValue('C' . $filaExcel, $despacho['Hora'] ?? '');
                        $sheet->setCellValue('D' . $filaExcel, $despacho['Turno'] ?? '');
                        $sheet->setCellValue('E' . $filaExcel, $despacho['Area'] ?? '');
                        $sheet->setCellValue('F' . $filaExcel, $despacho['Subarea'] ?? '');
                        $sheet->setCellValue('G' . $filaExcel, $despacho['Despachador'] ?? '');
                        $sheet->setCellValue('H' . $filaExcel, $despacho['Recepcionista'] ?? '');
                        $sheet->setCellValue('I' . $filaExcel, $despacho['Verificador'] ?? '');
                        $sheet->setCellValue('J' . $filaExcel, $producto['Codigo'] ?? '');
                        $sheet->setCellValue('K' . $filaExcel, $producto['Producto'] ?? '');
                        $sheet->setCellValue('L' . $filaExcel, $producto['UnidadMedida'] ?? '');
                        $sheet->setCellValue('M' . $filaExcel, $producto['Cantidad'] ?? '');
                        $sheet->setCellValue('N' . $filaExcel, $producto['Comentarios'] ?? '');
                        $filaExcel++;
                    }
                }
            }
        }
        
        // Liberar memoria
        unset($despachos);
        
        error_log('Despachos Internos - Filas insertadas: ' . ($filaExcel - 2));
        
        // Ajustar columnas
        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
    
    private function insertarDatosDespachosExternos($spreadsheet) {
        $sheet = $spreadsheet->getSheetByName('Despachos Externos');
        if (!$sheet) {
            throw new Exception('No se encontrÃƒÂ³ la hoja "Despachos Externos" en la plantilla');
        }
        
        // Limpiar datos anteriores
        $highestRow = $sheet->getHighestRow();
        if ($highestRow > 1) {
            $sheet->removeRow(2, $highestRow - 1);
        }
        
        // Reconectar a base de datos para evitar timeout
        try {
            $db = Database::getInstance()->getConnection();
            $db->exec("SET SESSION wait_timeout = 600");
        } catch (Exception $e) {
            error_log("Advertencia MySQL: " . $e->getMessage());
        }
        
        // Obtener datos y liberar modelo inmediatamente
        $model = $this->model('DespachoExterno');
        $result = $model->getReporte([], 10000, 0);
        $despachos = $result['data'];
        // Mapear ids a textos legibles para la hoja consolidada (Turno, Despachador, Destino, Chofer, Transportista)
        try {
            $destinoModel = $this->model('Destino');
            $clienteExternoModel = $this->model('ClienteExterno');
            $choferModel = $this->model('Chofer');
            $transportistaModel = $this->model('Transportista');
            $responsableModel = $this->model('Responsable');
            $turnoModel = $this->model('Turno');

            $cacheDestino = [];
            $cacheChofer = [];
            $cacheTransportista = [];
            $cacheResponsable = [];
            $cacheTurno = [];

            foreach ($despachos as &$row) {
                // Turno
                $turnoId = $row['Turno'] ?? $row['turno'] ?? $row['turnoId'] ?? null;
                if ($turnoId !== null && $turnoId !== '' && !is_array($turnoId)) {
                    $kt = (string)$turnoId;
                    if (!isset($cacheTurno[$kt])) {
                        try { $t = $turnoModel->getAllForSelect(); foreach ($t as $_t) { $cacheTurno[(string)$_t['Id']] = $_t['Turno']; } } catch(Exception $__t) { }
                        if (!isset($cacheTurno[$kt])) $cacheTurno[$kt] = (string)$turnoId;
                    }
                    $row['Turno'] = $cacheTurno[$kt];
                }

                // Destino
                $destId = $row['Destino'] ?? $row['DestinoOriginal'] ?? $row['destino'] ?? null;
                if ($destId !== null && $destId !== '' && !is_array($destId)) {
                    $key = (string)$destId;
                    if (!isset($cacheDestino[$key])) {
                        if (is_numeric($destId)) {
                            try { $d = method_exists($destinoModel,'getByIdFlexible') ? $destinoModel->getByIdFlexible((int)$destId) : $destinoModel->getById((int)$destId); $cacheDestino[$key] = $d ? ($d['Empresa'] ?? $d['Destino'] ?? ($d['Nombre'] ?? (string)$destId)) : (string)$destId; } catch(Exception $__d) { $cacheDestino[$key] = (string)$destId; }
                        } else {
                            $cacheDestino[$key] = $destId;
                        }
                    }
                    if ((string)$cacheDestino[$key] === (string)$destId) {
                        $resolved = null;
                        try { if (method_exists($clienteExternoModel,'getById')) { $ce = $clienteExternoModel->getById((int)$destId); if ($ce && !empty($ce['Empresa'])) $resolved = $ce['Empresa']; elseif ($ce && !empty($ce['RazonSocial'])) $resolved = $ce['RazonSocial']; } } catch(Exception $__ce) {}
                        if ($resolved === null) { $alt = ''; if (!empty($row['RUC'])) $alt .= $row['RUC']; if (!empty($row['Direccion'])) { $addr = trim($row['Direccion']); if ($addr !== '') { if ($alt !== '') $alt .= ' - '; $alt .= (strlen($addr) > 120) ? substr($addr,0,120) . '...' : $addr; } } if ($alt !== '') $resolved = $alt; }
                        if ($resolved === null) $resolved = 'Destino #' . (string)$destId;
                        $cacheDestino[$key] = $resolved;
                    }
                    $row['Destino'] = $cacheDestino[$key];
                    $row['DestinoTexto'] = $cacheDestino[$key];
                }

                // Chofer
                $choferId = $row['Chofer'] ?? $row['chofer'] ?? null;
                if ($choferId !== null && $choferId !== '' && !is_array($choferId)) {
                    $k2 = (string)$choferId;
                    if (!isset($cacheChofer[$k2])) {
                        try { if (is_numeric($choferId)) { $c = $choferModel->getById((int)$choferId); $cacheChofer[$k2] = $c ? ($c['ApellidosNombres'] ?? $c['Nombres'] ?? (string)$choferId) : (string)$choferId; } else { $cacheChofer[$k2] = $choferId; } } catch(Exception $__c) { $cacheChofer[$k2] = (string)$choferId; }
                    }
                    $row['Chofer'] = $cacheChofer[$k2];
                }

                // Transportista
                $transId = $row['Transportista'] ?? $row['transportista'] ?? null;
                if ($transId !== null && $transId !== '' && !is_array($transId)) {
                    $kt = (string)$transId;
                    if (!isset($cacheTransportista[$kt])) {
                        try { if (is_numeric($transId)) { $t = $transportistaModel->getById((int)$transId); $cacheTransportista[$kt] = $t ? ($t['Empresa'] ?? $t['RUC'] ?? (string)$transId) : (string)$transId; } else { $cacheTransportista[$kt] = $transId; } } catch(Exception $__t) { $cacheTransportista[$kt] = (string)$transId; }
                    }
                    $row['Transportista'] = $cacheTransportista[$kt];
                }

                // Despachador
                $respId = $row['Despachador'] ?? $row['DespachadorTexto'] ?? $row['despachador'] ?? null;
                if ($respId !== null && $respId !== '' && !is_array($respId)) {
                    $kr = (string)$respId;
                    if (!isset($cacheResponsable[$kr])) {
                        try { if (is_numeric($respId)) { $r = $responsableModel->getById((int)$respId); $cacheResponsable[$kr] = $r ? ($r['NombresApellidos'] ?? '') : (string)$respId; } else { $cacheResponsable[$kr] = $respId; } } catch(Exception $__r) { $cacheResponsable[$kr] = (string)$respId; }
                    }
                    $row['Despachador'] = $cacheResponsable[$kr];
                }
            }
            unset($row);
        } catch (Exception $__map) { error_log('Error mapear insertarDatosDespachosExternos: ' . $__map->getMessage()); }
        unset($model);
        unset($result);
        
        error_log('Insertando ' . count($despachos) . ' despachos externos');

        // Asegurar encabezados (aÃƒÂ±adir NÃ‚Â° VALE antes de FECHA)
        $cabeceras = [
            'A1' => 'NÃ‚Â° VALE', 'B1' => 'FECHA', 'C1' => 'HORA', 'D1' => 'TURNO', 'E1' => 'DESPACHADOR',
            'F1' => 'DESTINO', 'G1' => 'RUC', 'H1' => 'DIRECCIÃƒâ€œN', 'I1' => 'CHOFER', 'J1' => 'BREVETE',
            'K1' => 'TRANSPORTISTA', 'L1' => 'RUC TRANSPORTISTA', 'M1' => 'PLACA TRACTO', 'N1' => 'CONSTANCIA INSC. TRACTO',
            'O1' => 'PLACA CARRETA', 'P1' => 'CONSTANCIA INSC. CARRETA', 'Q1' => 'GUÃƒÂA REMISIÃƒâ€œN',
            'R1' => 'CÃƒâ€œDIGO', 'S1' => 'PRODUCTO', 'T1' => 'UNIDAD MEDIDA', 'U1' => 'CANTIDAD', 'V1' => 'COMENTARIOS', 'W1' => 'ESTADO'
        ];
        foreach ($cabeceras as $cell => $label) {
            $sheet->setCellValue($cell, $label);
        }

        // Insertar datos desde fila 2
        $filaExcel = 2;
        if (!empty($despachos)) {
            foreach ($despachos as $despacho) {
                $fechaFormateada = isset($despacho['Fecha']) ? date('d/m/Y', strtotime($despacho['Fecha'])) : '';
                $productos = isset($despacho['Detalles']) && is_array($despacho['Detalles']) ? $despacho['Detalles'] : [];
                
                if (count($productos) > 0) {
                    foreach ($productos as $producto) {
                        // Normalizar nombres de campo posibles para evitar valores vacÃƒÂ­os
                        $ruc = $despacho['RUC'] ?? $despacho['RucDestino'] ?? $despacho['Ruc'] ?? '';
                        $direccion = $despacho['Direccion'] ?? $despacho['DireccionEntrega'] ?? $despacho['DireccionDestino'] ?? '';
                        $rucTrans = $despacho['RUC_Transportista'] ?? $despacho['RucTransportista'] ?? $despacho['Ruc_Transportista'] ?? '';
                        $placa_tracto = $despacho['Placa_Tracto'] ?? $despacho['PlacaTracto'] ?? $despacho['Placa'] ?? '';
                        $constancia_tracto = $despacho['Constancia_Inscripcion'] ?? $despacho['ConstanciaTracto'] ?? $despacho['Constancia_Inscripcion_Tracto'] ?? '';
                        $placa_carreta = $despacho['Placa_Carreta'] ?? $despacho['PlacaCarreta'] ?? '';
                        $constancia_carreta = $despacho['Constancia_Inscripcion_2'] ?? $despacho['ConstanciaCarreta'] ?? '';
                        $guia = $despacho['GR'] ?? $despacho['guiaRemision'] ?? $despacho['GuiaRemision'] ?? $despacho['Guia'] ?? '';

                        // Formatear NÃ‚Â° Vale como 6 dÃƒÂ­gitos si es numÃƒÂ©rico
                        $nvaleRaw = $despacho['NVale'] ?? $despacho['nvale'] ?? '';
                        $nvaleDigits = preg_replace('/\D/', '', (string)$nvaleRaw);
                        $nvalePad = $nvaleDigits !== '' ? str_pad($nvaleDigits, 6, '0', STR_PAD_LEFT) : (string)$nvaleRaw;
                        $sheet->setCellValue('A' . $filaExcel, $nvalePad);
                        $sheet->setCellValue('B' . $filaExcel, $fechaFormateada);
                        $sheet->setCellValue('C' . $filaExcel, $despacho['Hora'] ?? '');
                        $sheet->setCellValue('D' . $filaExcel, $despacho['Turno'] ?? '');
                        $sheet->setCellValue('E' . $filaExcel, $despacho['Despachador'] ?? '');
                        $sheet->setCellValue('F' . $filaExcel, $despacho['Destino'] ?? '');
                        $sheet->setCellValue('G' . $filaExcel, $ruc);
                        $sheet->setCellValue('H' . $filaExcel, $direccion);
                        $sheet->setCellValue('I' . $filaExcel, $despacho['Chofer'] ?? '');
                        $sheet->setCellValue('J' . $filaExcel, $despacho['Brevete'] ?? '');
                        $sheet->setCellValue('K' . $filaExcel, $despacho['Transportista'] ?? '');
                        $sheet->setCellValue('L' . $filaExcel, $rucTrans);
                        $sheet->setCellValue('M' . $filaExcel, $placa_tracto);
                        $sheet->setCellValue('N' . $filaExcel, $constancia_tracto);
                        $sheet->setCellValue('O' . $filaExcel, $placa_carreta);
                        $sheet->setCellValue('P' . $filaExcel, $constancia_carreta);
                        $sheet->setCellValue('Q' . $filaExcel, $guia);
                        $sheet->setCellValue('R' . $filaExcel, $producto['Codigo'] ?? '');
                        $sheet->setCellValue('S' . $filaExcel, $producto['Producto'] ?? '');
                        $sheet->setCellValue('T' . $filaExcel, $producto['UnidadMedida'] ?? '');
                        $sheet->setCellValue('U' . $filaExcel, $producto['Cantidad'] ?? '');
                        $sheet->setCellValue('V' . $filaExcel, $producto['Comentarios'] ?? '');
                        $sheet->setCellValue('W' . $filaExcel, $despacho['Estado'] ?? '');
                        $filaExcel++;
                    }
                }
            }
        }
        
        // Liberar memoria
        unset($despachos);
        
        error_log('Despachos Externos - Filas insertadas: ' . ($filaExcel - 2));
        
        // Ajustar columnas
        foreach (range('A', 'W') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
    
    private function insertarDatosRecepcionesInternas($spreadsheet) {
        $sheet = $spreadsheet->getSheetByName('Recepciones Internas');
        if (!$sheet) {
            throw new Exception('No se encontrÃƒÂ³ la hoja "Recepciones Internas" en la plantilla');
        }
        
        // Limpiar datos anteriores
        $highestRow = $sheet->getHighestRow();
        if ($highestRow > 1) {
            $sheet->removeRow(2, $highestRow - 1);
        }
        
        // Reconectar a base de datos para evitar timeout
        try {
            $db = Database::getInstance()->getConnection();
            $db->exec("SET SESSION wait_timeout = 600");
        } catch (Exception $e) {
            error_log("Advertencia MySQL: " . $e->getMessage());
        }
        
        // Obtener datos y liberar modelo inmediatamente
        $model = $this->model('RecepcionInterna');
        $result = $model->getReporte([], 10000, 0);
        $recepciones = $result['data'];
        unset($model);
        unset($result);
        
        error_log('Insertando ' . count($recepciones) . ' recepciones internas');
        
        // Insertar datos desde fila 2
        $filaExcel = 2;
        if (!empty($recepciones)) {
            foreach ($recepciones as $recepcion) {
                $fechaFormateada = isset($recepcion['Fecha']) ? date('d/m/Y', strtotime($recepcion['Fecha'])) : '';
                $productos = isset($recepcion['Detalles']) && is_array($recepcion['Detalles']) ? $recepcion['Detalles'] : [];
                
                if (count($productos) > 0) {
                    foreach ($productos as $producto) {
                        $sheet->setCellValue('A' . $filaExcel, $recepcion['NVale'] ?? '');
                        $sheet->setCellValue('B' . $filaExcel, $fechaFormateada);
                        $sheet->setCellValue('C' . $filaExcel, $recepcion['Hora'] ?? '');
                        $sheet->setCellValue('D' . $filaExcel, $recepcion['Turno'] ?? '');
                        $sheet->setCellValue('E' . $filaExcel, $recepcion['Area'] ?? '');
                        $sheet->setCellValue('F' . $filaExcel, $recepcion['Subarea'] ?? '');
                        $sheet->setCellValue('G' . $filaExcel, $recepcion['Despachador'] ?? '');
                        $sheet->setCellValue('H' . $filaExcel, $recepcion['MedioTransporte'] ?? '');
                        $sheet->setCellValue('I' . $filaExcel, $recepcion['Verificador'] ?? '');
                        $sheet->setCellValue('J' . $filaExcel, $producto['Codigo'] ?? '');
                        $sheet->setCellValue('K' . $filaExcel, $producto['Producto'] ?? '');
                        $sheet->setCellValue('L' . $filaExcel, $producto['UnidadMedida'] ?? '');
                        $sheet->setCellValue('M' . $filaExcel, $producto['Cantidad'] ?? '');
                        $sheet->setCellValue('N' . $filaExcel, $producto['Comentarios'] ?? '');
                        $sheet->setCellValue('O' . $filaExcel, $recepcion['Estado'] ?? '');
                        $filaExcel++;
                    }
                }
            }
        }
        
        // Liberar memoria
        unset($recepciones);
        
        error_log('Recepciones Internas - Filas insertadas: ' . ($filaExcel - 2));
        
        // Ajustar columnas
        foreach (range('A', 'O') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
    
    private function insertarDatosRecepcionesExternas($spreadsheet) {
        $sheet = $spreadsheet->getSheetByName('Recepciones Externas');
        if (!$sheet) {
            throw new Exception('No se encontrÃƒÂ³ la hoja "Recepciones Externas" en la plantilla');
        }
        
        // Limpiar datos anteriores
        $highestRow = $sheet->getHighestRow();
        if ($highestRow > 1) {
            $sheet->removeRow(2, $highestRow - 1);
        }
        
        // Reconectar a base de datos para evitar timeout
        try {
            $db = Database::getInstance()->getConnection();
            $db->exec("SET SESSION wait_timeout = 600");
        } catch (Exception $e) {
            error_log("Advertencia MySQL: " . $e->getMessage());
        }
        
        // Obtener datos y liberar modelo inmediatamente
        $model = $this->model('RecepcionExterna');
        $result = $model->getReporte([], 10000, 0);
        $recepciones = $result['data'];
        unset($model);
        unset($result);
        
        error_log('Insertando ' . count($recepciones) . ' recepciones externas');
        
        // Insertar datos desde fila 2
        $filaExcel = 2;
        if (!empty($recepciones)) {
            foreach ($recepciones as $recepcion) {
                $fechaFormateada = isset($recepcion['Fecha']) ? date('d/m/Y', strtotime($recepcion['Fecha'])) : '';
                
                if (isset($recepcion['Guias']) && is_array($recepcion['Guias']) && count($recepcion['Guias']) > 0) {
                    foreach ($recepcion['Guias'] as $guia) {
                        if (isset($guia['Productos']) && is_array($guia['Productos']) && count($guia['Productos']) > 0) {
                            foreach ($guia['Productos'] as $producto) {
                                $sheet->setCellValue('A' . $filaExcel, $recepcion['NVale'] ?? '');
                                $sheet->setCellValue('B' . $filaExcel, $fechaFormateada);
                                $sheet->setCellValue('C' . $filaExcel, $recepcion['Hora'] ?? '');
                                $sheet->setCellValue('D' . $filaExcel, $recepcion['Turno'] ?? '');
                                $sheet->setCellValue('E' . $filaExcel, $recepcion['Origen'] ?? '');
                                $sheet->setCellValue('F' . $filaExcel, $recepcion['Empresa'] ?? '');
                                $sheet->setCellValue('G' . $filaExcel, $recepcion['RUC'] ?? '');
                                $sheet->setCellValue('H' . $filaExcel, $recepcion['Chofer'] ?? '');
                                $sheet->setCellValue('I' . $filaExcel, $recepcion['Brevete'] ?? '');
                                $sheet->setCellValue('J' . $filaExcel, $guia['NumeroGuia'] ?? '');
                                $sheet->setCellValue('K' . $filaExcel, $guia['ObservacionTexto'] ?? '');
                                $sheet->setCellValue('L' . $filaExcel, $guia['CodigoProductoObs'] ?? '');
                                $sheet->setCellValue('M' . $filaExcel, $guia['CantidadObservada'] ?? '');
                                $sheet->setCellValue('N' . $filaExcel, $producto['CodigoProducto'] ?? '');
                                $sheet->setCellValue('O' . $filaExcel, $producto['DescripcionProducto'] ?? '');
                                $sheet->setCellValue('P' . $filaExcel, $producto['Cantidad'] ?? '');
                                $sheet->setCellValue('Q' . $filaExcel, $producto['UnidadMedida'] ?? '');
                                $sheet->setCellValue('R' . $filaExcel, $recepcion['Estado'] ?? '');
                                $filaExcel++;
                            }
                        }
                    }
                }
            }
        }
        
        // Liberar memoria
        unset($recepciones);
        
        error_log('Recepciones Externas - Filas insertadas: ' . ($filaExcel - 2));
        
        // Ajustar columnas
        foreach (range('A', 'R') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
    
    private function insertarDatosPicking($spreadsheet) {
        $sheet = $spreadsheet->getSheetByName('Picking');
        if (!$sheet) {
            throw new Exception('No se encontrÃƒÂ³ la hoja "Picking" en la plantilla');
        }
        
        // Limpiar datos anteriores (a partir de la fila 2)
        $highestRow = $sheet->getHighestRow();
        if ($highestRow > 1) {
            $sheet->removeRow(2, $highestRow - 1);
        }
        
        // Reconectar a base de datos para evitar timeout
        try {
            $db = Database::getInstance()->getConnection();
            $db->exec("SET SESSION wait_timeout = 600");
        } catch (Exception $e) {
            error_log("Advertencia MySQL: " . $e->getMessage());
        }
        
        // Obtener datos y liberar modelo inmediatamente
        $model = $this->model('RecepcionExterna');
        $result = $model->getReporte([], 10000, 0);
        $recepciones = $result['data'];
        unset($model);
        unset($result);
        
        error_log('Insertando ' . count($recepciones) . ' registros en Picking');
        
        // Insertar datos desde fila 2 (cabeceras en fila 1)
        $filaExcel = 2;
        if (!empty($recepciones)) {
            foreach ($recepciones as $recepcion) {
                $fechaFormateada = isset($recepcion['Fecha']) ? date('d/m/Y', strtotime($recepcion['Fecha'])) : '';

                if (isset($recepcion['Guias']) && is_array($recepcion['Guias']) && count($recepcion['Guias']) > 0) {
                    foreach ($recepcion['Guias'] as $guia) {
                        // Una fila por guÃƒÂ­a (no por producto)
                        $sheet->setCellValue('A' . $filaExcel, $fechaFormateada);
                        $sheet->setCellValue('B' . $filaExcel, $recepcion['NVale'] ?? '');
                        $sheet->setCellValue('C' . $filaExcel, $guia['NumeroDocRef'] ?? ($guia['NumeroDocReferencia'] ?? ''));
                        $sheet->setCellValue('D' . $filaExcel, $guia['NumeroGuia'] ?? '');
                        $sheet->setCellValue('E' . $filaExcel, $recepcion['Origen'] ?? '');
                        $sheet->setCellValue('F' . $filaExcel, $guia['ObservacionTexto'] ?? '');
                        $sheet->setCellValue('G' . $filaExcel, $recepcion['Turno'] ?? '');
                        $filaExcel++;
                    }
                } else {
                    $sheet->setCellValue('A' . $filaExcel, $fechaFormateada);
                    $sheet->setCellValue('B' . $filaExcel, $recepcion['NVale'] ?? '');
                    $sheet->setCellValue('C' . $filaExcel, '');
                    $sheet->setCellValue('D' . $filaExcel, '');
                    $sheet->setCellValue('E' . $filaExcel, $recepcion['Origen'] ?? '');
                    $sheet->setCellValue('F' . $filaExcel, 'Sin guÃƒÂ­as');
                    $sheet->setCellValue('G' . $filaExcel, $recepcion['Turno'] ?? '');
                    $filaExcel++;
                }
            }
        }

        // Liberar memoria
        unset($recepciones);

        error_log('Picking - Filas insertadas: ' . ($filaExcel - 2));

        // Ajustar columnas
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
    
    private function insertarDatosRecepcionesExt($spreadsheet) {
        $sheet = $spreadsheet->getSheetByName('Recepciones Ext.');
        if (!$sheet) {
            throw new Exception('No se encontrÃƒÂ³ la hoja "Recepciones Ext." en la plantilla');
        }

        // Limpiar datos anteriores
        $highestRow = $sheet->getHighestRow();
        if ($highestRow > 1) {
            $sheet->removeRow(2, $highestRow - 1);
        }

        // Reconectar a base de datos para evitar timeout
        try {
            $db = Database::getInstance()->getConnection();
            $db->exec("SET SESSION wait_timeout = 600");
        } catch (Exception $e) {
            error_log("Advertencia MySQL: " . $e->getMessage());
        }

        // Obtener datos y liberar modelo inmediatamente
        $model = $this->model('RecepcionExterna');
        $result = $model->getReporte([], 10000, 0);
        $recepciones = $result['data'];
        unset($model);
        unset($result);

        error_log('Insertando ' . count($recepciones) . ' registros en Recepciones Ext.');

        // Insertar datos desde fila 2
        // Columnas: Turno, NÃ‚Â° Vale, NÃ‚Â° GuÃƒÂ­a, NÃ‚Â° Doc. Ref., Origen, Transportista, Chofer, Observaciones, CÃƒÂ³d. Producto, Cantidad
        $filaExcel = 2;
        if (!empty($recepciones)) {
            foreach ($recepciones as $recepcion) {
                if (isset($recepcion['Guias']) && is_array($recepcion['Guias']) && count($recepcion['Guias']) > 0) {
                    foreach ($recepcion['Guias'] as $guia) {
                        // Si la guÃƒÂ­a tiene productos, generamos una fila por producto; si no, una fila por guÃƒÂ­a
                        if (isset($guia['Productos']) && is_array($guia['Productos']) && count($guia['Productos']) > 0) {
                            foreach ($guia['Productos'] as $producto) {
                                $sheet->setCellValue('A' . $filaExcel, $recepcion['Turno'] ?? '');
                                $sheet->setCellValue('B' . $filaExcel, $recepcion['NVale'] ?? '');
                                $sheet->setCellValue('C' . $filaExcel, $guia['NumeroGuia'] ?? '');
                                $sheet->setCellValue('D' . $filaExcel, $guia['NumeroDocRef'] ?? ($guia['NumeroDocReferencia'] ?? ''));
                                $sheet->setCellValue('E' . $filaExcel, $recepcion['Origen'] ?? '');
                                $sheet->setCellValue('F' . $filaExcel, $recepcion['Empresa'] ?? '');
                                $sheet->setCellValue('G' . $filaExcel, $recepcion['Chofer'] ?? '');
                                $sheet->setCellValue('H' . $filaExcel, $guia['ObservacionTexto'] ?? '');
                                $sheet->setCellValue('I' . $filaExcel, $producto['CodigoProducto'] ?? '');
                                $sheet->setCellValue('J' . $filaExcel, $producto['Cantidad'] ?? '');
                                $filaExcel++;
                            }
                        } else {
                            $sheet->setCellValue('A' . $filaExcel, $recepcion['Turno'] ?? '');
                            $sheet->setCellValue('B' . $filaExcel, $recepcion['NVale'] ?? '');
                            $sheet->setCellValue('C' . $filaExcel, $guia['NumeroGuia'] ?? '');
                            $sheet->setCellValue('D' . $filaExcel, $guia['NumeroDocRef'] ?? ($guia['NumeroDocReferencia'] ?? ''));
                            $sheet->setCellValue('E' . $filaExcel, $recepcion['Origen'] ?? '');
                            $sheet->setCellValue('F' . $filaExcel, $recepcion['Empresa'] ?? '');
                            $sheet->setCellValue('G' . $filaExcel, $recepcion['Chofer'] ?? '');
                            $sheet->setCellValue('H' . $filaExcel, $guia['ObservacionTexto'] ?? '');
                            $sheet->setCellValue('I' . $filaExcel, '');
                            $sheet->setCellValue('J' . $filaExcel, '');
                            $filaExcel++;
                        }
                    }
                }
            }
        }

        // Liberar memoria
        unset($recepciones);

        error_log('Recepciones Ext. - Filas insertadas: ' . ($filaExcel - 2));

        // Ajustar columnas
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    public function exportarExcelRecepcionesExternasLiquidadas() {
        try {
            ini_set('memory_limit', '512M');
            ini_set('max_execution_time', 300);
            require_once __DIR__ . '/../../vendor/autoload.php';

            // Obtener filtros
            $filtros = [];
            if (!empty($_GET['Fecha'])) $filtros['Fecha'] = $_GET['Fecha'];
            if (!empty($_GET['Turno'])) $filtros['Turno'] = $_GET['Turno'];

            // Obtener datos
            $model = $this->model('RecepcionExterna');
            $result = $model->getReporte($filtros, 10000, 0);
            $recepciones = $result['data'];

            if (empty($recepciones)) {
                throw new Exception('No se encontraron datos para exportar con los filtros seleccionados.');
            }

            // Procesar datos (similar al JS procesarDatosLiquidadas)
            $datosLiquidados = [];
            foreach ($recepciones as $recepcion) {
                if (isset($recepcion['Guias']) && is_array($recepcion['Guias'])) {
                    foreach ($recepcion['Guias'] as $guia) {
                        if (isset($guia['Productos']) && is_array($guia['Productos'])) {
                            foreach ($guia['Productos'] as $prod) {
                                $observaciones = [];
                                if (!empty($prod['Observaciones']) && is_array($prod['Observaciones'])) {
                                    foreach ($prod['Observaciones'] as $obs) {
                                        $tipo = strtoupper($obs['TipoObservacion'] ?? '');
                                        $cantidad = $obs['Cantidad'] ?? 0;
                                        $codigo = $obs['CodigoProductoObs'] ?? '';
                                        $numeroGuia = $obs['NumeroGuiaObs'] ?? '';
                                        
                                        $textoObs = "$tipo $cantidad UND DEL CODIGO $codigo GUIA $numeroGuia";
                                        $observaciones[] = $textoObs;
                                    }
                                }

                                $pendiente = 0;
                                $adicional = 0;
                                if (!empty($prod['Observaciones'])) {
                                    foreach ($prod['Observaciones'] as $obs) {
                                        $tipo = strtoupper($obs['TipoObservacion'] ?? '');
                                        $cantidad = intval($obs['Cantidad'] ?? 0);
                                        $codigoObs = $obs['CodigoProductoObs'] ?? '';
                                        $codigoProd = $prod['Codigo'] ?? '';

                                        if ($tipo === 'PENDIENTE' && $codigoObs === $codigoProd) {
                                            $pendiente += $cantidad;
                                        } elseif ($tipo === 'ADICIONAL' && $codigoObs === $codigoProd) {
                                            $adicional += $cantidad;
                                        }
                                    }
                                }

                                $datosLiquidados[] = [
                                    'NumeroGuia' => $guia['NumeroGuia'] ?? '',
                                    'Fecha' => $recepcion['Fecha'] ?? '',
                                    'Observaciones' => implode(' / ', $observaciones),
                                    'Cantidad' => intval($prod['Cantidad'] ?? 0),
                                    'Pendiente' => $pendiente,
                                    'Adicional' => $adicional,
                                    'Total' => intval($prod['Cantidad'] ?? 0) - $pendiente + $adicional
                                ];
                            }
                        }
                    }
                }
            }

            if (empty($datosLiquidados)) {
                throw new Exception('No se generaron filas de datos liquidados.');
            }

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Recepciones Liquidadas');
            $sheet->setShowGridlines(false);

            // Cabeceras
            $cabeceras = [
                'A1' => 'NÃ‚Â° GUÃƒÂA',
                'B1' => 'FECHA',
                'C1' => 'OBSERVACIONES',
                'D1' => 'CANTIDAD',
                'E1' => 'PENDIENTE',
                'F1' => 'ADICIONAL',
                'G1' => 'TOTAL'
            ];
            foreach ($cabeceras as $celda => $valor) {
                $sheet->setCellValue($celda, $valor);
            }

            // Estilo cabecera
            $styleHeader = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563eb']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER]
            ];
            $sheet->getStyle('A1:G1')->applyFromArray($styleHeader);
            $sheet->getStyle('A1:G1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->freezePane('A2');
            $sheet->getRowDimension(1)->setRowHeight(20);

            $filaExcel = 2;
            foreach ($datosLiquidados as $fila) {
                $fechaFormateada = isset($fila['Fecha']) && $fila['Fecha'] ? date('d/m/Y', strtotime($fila['Fecha'])) : '';
                $sheet->setCellValue('A' . $filaExcel, $fila['NumeroGuia']);
                $sheet->setCellValue('B' . $filaExcel, $fechaFormateada);
                $sheet->setCellValue('C' . $filaExcel, $fila['Observaciones']);
                $sheet->setCellValue('D' . $filaExcel, $fila['Cantidad']);
                $sheet->setCellValue('E' . $filaExcel, $fila['Pendiente']);
                $sheet->setCellValue('F' . $filaExcel, $fila['Adicional']);
                $sheet->setCellValue('G' . $filaExcel, $fila['Total']);
                $filaExcel++;
            }

            // Auto-ajustar columnas
            foreach (range('A', 'G') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Aplicar bordes
            $lastRow = $filaExcel - 1;
            if ($lastRow >= 1) {
                $sheet->getStyle('A1:G' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            }

            // Exportar
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="Recepciones_Externas_Liquidadas_' . date('Ymd_His') . '.xlsx"');
            header('Cache-Control: max-age=0');

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        } catch (Exception $e) {
            echo '<div style="background:#f8d7da; color:#721c24; padding:20px; border:1px solid #f5c6cb; border-radius:4px; font-family:Arial,sans-serif;">';
            echo '<h2 style="margin-top:0;">Error al exportar Excel</h2>';
            echo '<p><strong>Mensaje:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p><strong>Archivo:</strong> ' . htmlspecialchars($e->getFile()) . ' en la lÃƒÂ­nea ' . $e->getLine() . '</p>';
            echo '<a href="javascript:history.back()" style="color: #721c24; text-decoration: underline;">Volver</a>';
            echo '</div>';
            error_log('Error en exportarExcelRecepcionesExternasLiquidadas: ' . $e->getMessage() . ' en ' . $e->getFile() . ' lÃƒÂ­nea ' . $e->getLine());
            exit;
        }
    }

    // ============================================
    // REPORTE JABAS (NEGRAS Y BLANCAS)
    // ============================================

    public function jabas() {
        $this->view('reportes/jabas');
    }

    public function obtenerReporteJabas() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'MÃƒÂ©todo no permitido']);
            exit;
        }

        $params = json_decode(file_get_contents('php://input'), true);
        $mes = isset($params['mes']) ? (int)$params['mes'] : (int)date('m');
        $anio = isset($params['anio']) ? (int)$params['anio'] : (int)date('Y');
        $tipo = isset($params['tipo']) ? $params['tipo'] : 'negras';

        $codigoProducto = ($tipo === 'blancas') ? '19002924' : '19003031';
        $primerDia = sprintf('%04d-%02d-01', $anio, $mes);
        $ultimoDia = date('Y-m-t', strtotime($primerDia));
        $diaAnterior = date('Y-m-d', strtotime($primerDia . ' -1 day'));

        try {
            $db = Database::getInstance()->getConnection();

            $sql = "
                (SELECT re.Fecha as fecha, t.Turno as turno, 'RECEPCION' as tipo_mov, COALESCE(SUM(rip.Cantidad),0) as cantidad
                FROM recepciones_internas re JOIN recepciones_internas_productos rip ON re.Id=rip.DespachoId
                LEFT JOIN turnos t ON re.Turno=t.Id
                WHERE rip.CodigoProducto=:cp1 AND re.Fecha BETWEEN :pd1 AND :ud1 AND LOWER(re.estado)='activo'
                GROUP BY re.Fecha,t.Turno)
                UNION ALL
                (SELECT re.Fecha as fecha, t.Turno as turno, 'RECEPCION' as tipo_mov, COALESCE(SUM(rep.Cantidad),0) as cantidad
                FROM recepciones_externas re JOIN recepciones_externas_guias reg ON re.Id=reg.RecepcionExternaId
                JOIN recepciones_externas_productos rep ON reg.Id=rep.GuiaId
                LEFT JOIN turnos t ON re.Turno=t.Id
                WHERE rep.CodigoProducto=:cp2 AND re.Fecha BETWEEN :pd2 AND :ud2 AND LOWER(re.estado)='activo'
                GROUP BY re.Fecha,t.Turno)
                UNION ALL
                (SELECT de.Fecha as fecha, t.Turno as turno, 'DESPACHO' as tipo_mov, COALESCE(SUM(dip.Cantidad),0) as cantidad
                FROM despachos_internos de JOIN despachos_internos_productos dip ON de.Id=dip.DespachoId
                LEFT JOIN turnos t ON de.Turno=t.Id
                WHERE dip.CodigoProducto=:cp3 AND de.Fecha BETWEEN :pd3 AND :ud3 AND LOWER(de.estado)='activo'
                GROUP BY de.Fecha,t.Turno) UNION ALL (SELECT de.Fecha as fecha, t.Turno as turno, 'DESPACHO' as tipo_mov, COALESCE(SUM(dep.Cantidad),0) as cantidad FROM despachos_externos de JOIN despachos_externos_productos dep ON de.Id=dep.DespachoId LEFT JOIN turnos t ON de.Turno=t.Id WHERE dep.CodigoProducto=:cp4 AND de.Fecha BETWEEN :pd4 AND :ud4 AND LOWER(de.estado)='activo' GROUP BY de.Fecha,t.Turno) ORDER BY fecha,turno
            ";

            $sqlObs = "SELECT re.Fecha, GROUP_CONCAT(DISTINCT rep.TextoObservaciones SEPARATOR '; ') as observaciones
                FROM recepciones_externas re
                JOIN recepciones_externas_guias reg ON re.Id=reg.RecepcionExternaId
                JOIN recepciones_externas_productos rep ON reg.Id=rep.GuiaId
                WHERE rep.CodigoProducto=:ocp AND re.Fecha BETWEEN :opd AND :oud
                  AND rep.TextoObservaciones IS NOT NULL AND rep.TextoObservaciones!=''
                  AND LOWER(re.estado)='activo'
                GROUP BY re.Fecha
                ORDER BY re.Fecha";

            $stmtObs = $db->prepare($sqlObs);
            $stmtObs->bindValue(':ocp', $codigoProducto);
            $stmtObs->bindValue(':opd', $primerDia);
            $stmtObs->bindValue(':oud', $ultimoDia);
            $stmtObs->execute();
            $obsData = $stmtObs->fetchAll(PDO::FETCH_ASSOC);
            $observacionesPorFecha = [];
            foreach ($obsData as $o) {
                $observacionesPorFecha[$o['Fecha']] = $o['observaciones'];
            }

            $stmt = $db->prepare($sql);
            foreach (['cp1','cp2','cp3','cp4'] as $p) $stmt->bindValue(":$p", $codigoProducto);
            foreach (['pd1','pd2','pd3','pd4'] as $p) $stmt->bindValue(":$p", $primerDia);
            foreach (['ud1','ud2','ud3','ud4'] as $p) $stmt->bindValue(":$p", $ultimoDia);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $sqlSaldo = "SELECT COALESCE(SUM(CASE WHEN sq.tipo_mov='RECEPCION' THEN sq.cantidad ELSE 0 END),0)-COALESCE(SUM(CASE WHEN sq.tipo_mov='DESPACHO' THEN sq.cantidad ELSE 0 END),0) as saldo_final FROM (
                (SELECT 'RECEPCION' as tipo_mov,COALESCE(SUM(rip.Cantidad),0) as cantidad FROM recepciones_internas re JOIN recepciones_internas_productos rip ON re.Id=rip.DespachoId WHERE rip.CodigoProducto=:scp1 AND re.Fecha<=:sdia1 AND LOWER(re.estado)='activo')
                UNION ALL
                (SELECT 'RECEPCION' as tipo_mov,COALESCE(SUM(rep.Cantidad),0) as cantidad FROM recepciones_externas re JOIN recepciones_externas_guias reg ON re.Id=reg.RecepcionExternaId JOIN recepciones_externas_productos rep ON reg.Id=rep.GuiaId WHERE rep.CodigoProducto=:scp2 AND re.Fecha<=:sdia2 AND LOWER(re.estado)='activo')
                UNION ALL
                (SELECT 'DESPACHO' as tipo_mov,COALESCE(SUM(dip.Cantidad),0) as cantidad FROM despachos_internos de JOIN despachos_internos_productos dip ON de.Id=dip.DespachoId WHERE dip.CodigoProducto=:scp3 AND de.Fecha<=:sdia3 AND LOWER(de.estado)='activo') UNION ALL (SELECT 'DESPACHO' as tipo_mov,COALESCE(SUM(dep.Cantidad),0) as cantidad FROM despachos_externos de JOIN despachos_externos_productos dep ON de.Id=dep.DespachoId WHERE dep.CodigoProducto=:scp4 AND de.Fecha<=:sdia4 AND LOWER(de.estado)='activo') ) sq";

            $stmtSaldo = $db->prepare($sqlSaldo);
            foreach (['scp1','scp2','scp3','scp4'] as $p) $stmtSaldo->bindValue(":$p", $codigoProducto);
            foreach (['sdia1','sdia2','sdia3','sdia4'] as $p) $stmtSaldo->bindValue(":$p", $diaAnterior);
            $stmtSaldo->execute();
            $saldoRow = $stmtSaldo->fetch(PDO::FETCH_ASSOC);
            $saldoInicialMes = (float)($saldoRow['saldo_final'] ?? 0);

            echo json_encode(['success'=>true,'data'=>$data,'saldoInicialMes'=>$saldoInicialMes,'observaciones'=>$observacionesPorFecha,'totalPaginas'=>1], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log('[obtenerReporteJabas] Error: '.$e->getMessage());
            echo json_encode(['success'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    public function exportarExcelJabas() {
        try {
            ini_set('memory_limit','1024M');
            ini_set('max_execution_time',600);
            require_once __DIR__.'/../../vendor/autoload.php';

            $input = $_SERVER['REQUEST_METHOD']==='POST' ? json_decode(file_get_contents('php://input'),true) : [];
            $datos = $input['datos']??[];
            $tipo = $input['tipo']??'negras';
            if (empty($datos)) throw new Exception('No se encontraron datos para exportar.');
            $tituloTipo = ($tipo==='blancas')?'JABAS BLANCAS':'JABAS NEGRAS';

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Jabas');
            $sheet->getSheetView()->setZoomScale(80);
            $sheet->setShowGridlines(false);

            $cabeceras = ['A1'=>'FECHA','B1'=>'SALDO INICIAL','C1'=>'RECEP. MAÑANA','D1'=>'RECEP. TARDE','E1'=>'RECEP. NOCHE','F1'=>'TOTAL RECEPCIÓN','G1'=>'TOTAL SALDO INICIAL','H1'=>'DESP. MAÑANA','I1'=>'DESP. TARDE','J1'=>'DESP. NOCHE','K1'=>'TOTAL DESPACHO','L1'=>'SALDO FINAL','M1'=>'OBSERVACIONES'];
            foreach ($cabeceras as $c=>$t) {
                $sheet->setCellValue($c,$t);
                $sheet->getStyle($c)->applyFromArray(['font'=>['bold'=>true,'color'=>['rgb'=>'FFFFFF'],'size'=>10],'fill'=>['fillType'=>\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,'startColor'=>['rgb'=>'16A34A']],'alignment'=>['horizontal'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,'vertical'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],'borders'=>['allBorders'=>['borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]]);
            }

            $fila=2;
            foreach ($datos as $row) {
                $sheet->setCellValue('A'.$fila, !empty($row['fecha']) ? date('d-m-Y', strtotime($row['fecha'])) : '');
                $sheet->setCellValue('B'.$fila,$row['saldo_inicial']??0);
                $sheet->setCellValue('C'.$fila,$row['recep_maniana']??0);
                $sheet->setCellValue('D'.$fila,$row['recep_tarde']??0);
                $sheet->setCellValue('E'.$fila,$row['recep_noche']??0);
                $sheet->setCellValue('F'.$fila,$row['total_recepcion']??0);
                $sheet->setCellValue('G'.$fila,$row['total_saldo_inicial']??0);
                $sheet->setCellValue('H'.$fila,$row['desp_maniana']??0);
                $sheet->setCellValue('I'.$fila,$row['desp_tarde']??0);
                $sheet->setCellValue('J'.$fila,$row['desp_noche']??0);
                $sheet->setCellValue('K'.$fila,$row['total_despacho']??0);
                $sheet->setCellValue('L'.$fila,$row['saldo_final']??0);
                $sheet->setCellValue('M'.$fila,$row['observaciones']??'');
                foreach (range('A','M') as $col) {
                    $sheet->getStyle($col.$fila)->applyFromArray(['alignment'=>['horizontal'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],'borders'=>['allBorders'=>['borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]]);
                    $sheet->getStyle($col.$fila)->getNumberFormat()->setFormatCode('0');
                }
                $fila++;
            }

            foreach (['A'=>12,'B'=>14,'C'=>14,'D'=>14,'E'=>14,'F'=>16,'G'=>18,'H'=>14,'I'=>14,'J'=>14,'K'=>16,'L'=>14] as $col=>$w) $sheet->getColumnDimension($col)->setWidth($w);
            $sheet->getColumnDimension('M')->setAutoSize(true);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="Reporte_Jabas_'.$tituloTipo.'_'.date('Ymd').'.xlsx"');
            header('Cache-Control: max-age=0');

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        } catch (Exception $e) {
            echo '<div style="background:#f8d7da;color:#721c24;padding:20px;border:1px solid #f5c6cb;border-radius:4px;font-family:Arial,sans-serif;"><h2>Error al exportar Excel</h2><p>'.htmlspecialchars($e->getMessage()).'</p></div>';
            error_log('Error en exportarExcelJabas: '.$e->getMessage());
            exit;
        }
    }

}


