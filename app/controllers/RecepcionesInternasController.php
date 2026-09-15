<?php
class RecepcionesInternasController extends Controller
{
    // Obtener el siguiente correlativo de vale (AJAX)
    public function siguienteVale() {
        $recepcionModel = $this->model('RecepcionInterna');
        $siguiente = $recepcionModel->obtenerSiguienteVale();
        echo json_encode(['success' => true, 'correlativo' => str_pad($siguiente, 6, '0', STR_PAD_LEFT)]);
        exit;
    }
    public function index()
    {
        // Verificar que el usuario esté logueado
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        
        // Medios de transporte para el combo
        $medioTransporteModel = $this->model('MedioTransporte');
        $mediosTransporte = $medioTransporteModel->getAll();
        // Responsables para despachador y verificador
        $responsableModel = $this->model('Responsable');
        $responsables = $responsableModel->getAll();
        // Productos para el combo
        $productoModel = $this->model('Producto');
        $productos = $productoModel->getAllForSelect(); // Ordenado por Producto ASC

        $turnoModel = $this->model('Turno');
        $turnos = $turnoModel->getAll();
        $horaActual = date('H:i:s');

        $subareaModel = $this->model('Subarea');
        $subareas = $subareaModel->getAll('', 1000, 0); // Trae todas las subáreas con su área

        $this->view('recepcionesinternas/index', [
            'titulo' => 'Recepciones Internas',
            'turnos' => $turnos,
            'horaActual' => $horaActual,
            'subareas' => $subareas,
            'productos' => $productos,
            'responsables' => $responsables,
            'mediosTransporte' => $mediosTransporte
        ]);
    }

    // Registrar recepción interna
    public function guardar() {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                if (stripos($contentType, 'application/json') !== false) {
                    $data = json_decode(file_get_contents('php://input'), true);
                } else {
                    $data = $_POST;
                }
                // Validación de sesión y usuario
                if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
                    echo json_encode(['success' => false, 'message' => 'Sesión expirada o usuario no autenticado. Por favor, vuelva a iniciar sesión.']);
                    return;
                }
                $data['creado_por'] = $_SESSION['user']['id'];
                $data['ip_creacion'] = $_SERVER['REMOTE_ADDR'] ?? null;
                // Limpiar el correlativo para dejar solo el número
                if (!empty($data['correlativoVale'])) {
                    $data['NVale'] = ltrim(preg_replace('/^VRI-/', '', $data['correlativoVale']), '0');
                } else {
                    $data['NVale'] = null;
                }
                $recepcionModel = $this->model('RecepcionInterna');
                $productoModel = $this->model('RecepcionInternaProducto');
                $productos = $data['productos'] ?? [];
                @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', print_r($data, true) . "\n", FILE_APPEND);
                // Si viene Id, modificar; si no, registrar
                // FIX: Pasar $data directamente a modificarConDatos() para evitar
                // que vuelva a leer php://input (el stream se agota tras la primera lectura)
                if (!empty($data['Id'])) {
                    @file_put_contents(__DIR__.'/../../logs/debug_edit_entry.log', "[DEBUG-GUARDAR] ".date('Y-m-d H:i:s')." - guardar() recibe Id={$data['Id']}, llama a modificarConDatos() con data pasada directamente. DATA=".json_encode($data, JSON_UNESCAPED_UNICODE)."\n", FILE_APPEND);
                    $this->modificarConDatos($data);
                    return;
                } else {
                    // Registrar solo la recepción y obtener el ID
                    $recepcionData = [
                        'NVale' => $data['NVale'],
                        'Fecha' => $data['fecha'] ?? null,
                        'Hora' => date('H:i:s'),
                        'Turno' => $data['turno'] ?? null,
                        'Area' => $data['area'] ?? null,
                        'Subarea' => $data['subarea'] ?? null,
                        'Emisor' => $data['creado_por'] ?? null,
                        'Despachador' => $data['despachador'] ?? null,
                        'MedioTransporte' => $data['medioTransporte'] ?? null,
                        'Verificador' => $data['verificador'] ?? null,
                        // En la base de datos el campo se llama 'NLiquidacion'
                        'NLiquidacion' => $data['NLiquidacion'] ?? null,
                        // Mantener compatibilidad con código que pudiera enviar 'Liquidacion'
                        'Liquidacion' => $data['Liquidacion'] ?? null,
                        'creado_por' => $data['creado_por'],
                        'ip_creacion' => $data['ip_creacion'] ?? null
                    ];
                    @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "ANTES registrarRecepcion\n", FILE_APPEND);
                    $recepcionId = $recepcionModel->registrarRecepcion($recepcionData);
                    @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "DESPUES registrarRecepcion\n", FILE_APPEND);
                    @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "RECEPCION ID: " . print_r($recepcionId, true) . "\n", FILE_APPEND);
                    @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "PRODUCTOS ARRAY: " . print_r($productos, true) . "\n", FILE_APPEND);
                    $resultados = [];
                    @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "PRODUCTOS RECIBIDOS: " . print_r($productos, true) . "\n", FILE_APPEND);
                    if ($recepcionId && is_array($productos) && count($productos) > 0) {
                        foreach ($productos as $i => $prod) {
                            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "PRODUCTO #$i: " . print_r($prod, true) . "\n", FILE_APPEND);
                            $productoData = [
                                'DespachoId' => $recepcionId,
                                'CodigoProducto' => isset($prod['codigo']) ? $prod['codigo'] : '',
                                'DescripcionProducto' => isset($prod['producto']) ? $prod['producto'] : '',
                                'UnidadMedida' => isset($prod['unidadMedida']) ? $prod['unidadMedida'] : '',
                                'Cantidad' => isset($prod['cantidad']) && is_numeric($prod['cantidad']) ? $prod['cantidad'] : 0,
                                'Comentarios' => isset($prod['comentarios']) ? $prod['comentarios'] : ''
                            ];
                            try {
                                $productoModel->registrar($productoData);
                                $resultados[] = true;
                            } catch (Exception $e) {
                                @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "ERROR PRODUCTO: " . $e->getMessage() . "\n", FILE_APPEND);
                                $resultados[] = false;
                            }
                        }
                    } else {
                        @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "ERROR: recepcionId o productos no válidos\n", FILE_APPEND);
                    }
                }
                if (count($resultados) && !in_array(false, $resultados)) {
                    echo json_encode(['success' => true, 'id' => $recepcionId, 'message' => 'Recepción registrada correctamente.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error al registrar productos.']);
                }
            }
        } catch (Exception $e) {
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "ERROR GENERAL: " . $e->getMessage() . "\n", FILE_APPEND);
            echo json_encode(['success' => false, 'message' => 'Error inesperado: ' . $e->getMessage()]);
            exit;
        }
    }

    // Modificar recepción interna con validación de límite
    // FIX: Acepta $data opcional para evitar doble lectura de php://input
    // cuando es llamado desde guardar() que ya leyó los datos.
    public function modificarConDatos($data = null) {
        // LOG DE DEPURACIÓN: Rastrear entrada al método
        $debugLog = __DIR__.'/../../logs/debug_edit_entry.log';
        $traceId = uniqid('req_', true);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // FIX: Si ya recibimos $data como parámetro (desde guardar()), usarlo directamente.
            // Si no, leer de php://input (flujo normal desde /modificar).
            if ($data === null) {
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                
                // LOG: Verificar si php://input está disponible
                $rawInput = @file_get_contents('php://input');
                file_put_contents($debugLog, "[{$traceId}] modificarConDatos() - leyendo php://input: " . ($rawInput ? 'OK (' . strlen($rawInput) . ' bytes)' : 'VACIO') . "\n", FILE_APPEND);
                
                if (stripos($contentType, 'application/json') !== false) {
                    $data = json_decode($rawInput, true);
                } else {
                    $data = $_POST;
                }
            } else {
                file_put_contents($debugLog, "[{$traceId}] modificarConDatos() - usando datos pasados como parámetro (evita doble lectura php://input)\n", FILE_APPEND);
            }
            
            file_put_contents($debugLog, "[{$traceId}] Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'N/A') . " - data decoded: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
            file_put_contents($debugLog, "[{$traceId}] _SERVER[REQUEST_URI]: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n", FILE_APPEND);
            
            // Corregir el número de vale igual que en guardar
            if (!empty($data['correlativoVale'])) {
                $data['NVale'] = ltrim(preg_replace('/^VRI-/', '', $data['correlativoVale']), '0');
            }
            
            if (!isset($data['Id']) || empty($data['Id'])) {
                file_put_contents($debugLog, "[{$traceId}] ERROR: ID de recepción no recibido en data\n", FILE_APPEND);
                echo json_encode(['success' => false, 'message' => 'ID de recepción no recibido']);
                return;
            }
            
            $recepcionModel = $this->model('RecepcionInterna');
            $productoModel = $this->model('RecepcionInternaProducto');
            
            // Verificar si puede modificar
            $validacion = $recepcionModel->puedeModificar($data['Id']);
            if (!$validacion['puede']) {
                file_put_contents($debugLog, "[{$traceId}] LIMITE ALCANZADO para Id={$data['Id']}: {$validacion['mensaje']}\n", FILE_APPEND);
                echo json_encode([
                    'success' => false,
                    'message' => $validacion['mensaje'],
                    'limite_alcanzado' => true
                ]);
                return;
            }
            
            $productos = $data['productos'] ?? [];
            $numProductos = is_array($productos) ? count($productos) : 0;
            file_put_contents($debugLog, "[{$traceId}] VA A MODIFICAR Id={$data['Id']} con {$numProductos} productos. IDs productos: " . json_encode(array_map(function($p){return $p['codigo']??'N/A';}, is_array($productos)?$productos:[])) . "\n", FILE_APPEND);
            
            try {
                // FIX: Usar transacción para garantizar atomicidad del DELETE+INSERT
                // Esto evita duplicación en caso de peticiones concurrentes
                $db = \Database::getInstance()->getConnection();
                $db->beginTransaction();
                
                file_put_contents($debugLog, "[{$traceId}] TRANSACCIÓN INICIADA para Id={$data['Id']}\n", FILE_APPEND);
                
                $ok = $recepcionModel->actualizarRecepcion($data['Id'], $data);
                $productoModel->eliminarPorRecepcion($data['Id']);
                file_put_contents($debugLog, "[{$traceId}] DESPUES de DELETE para Id={$data['Id']}\n", FILE_APPEND);
                
                foreach ($productos as $prod) {
                    $productoData = [
                        'DespachoId' => $data['Id'],
                        'CodigoProducto' => $prod['codigo'] ?? null,
                        'DescripcionProducto' => $prod['producto'] ?? null,
                        'UnidadMedida' => $prod['unidadMedida'] ?? '',
                        // Validar cantidad numérica (igual que en guardar()) para evitar
                        // que un string vacío '' rompa la columna numérica Cantidad
                        'Cantidad' => isset($prod['cantidad']) && is_numeric($prod['cantidad']) ? $prod['cantidad'] : 0,
                        'Comentarios' => $prod['comentarios'] ?? ''
                    ];
                    $productoModel->registrar($productoData);
                    file_put_contents($debugLog, "[{$traceId}] INSERT producto: " . json_encode($productoData, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
                }
                
                $db->commit();
                file_put_contents($debugLog, "[{$traceId}] TRANSACCIÓN CONFIRMADA para Id={$data['Id']}\n", FILE_APPEND);
                
                if ($ok) {
                    $usuarioId = $_SESSION['user']['id'] ?? null;
                    try { $recepcionModel->registrarModificacion($data['Id'], $usuarioId, $_SERVER['REMOTE_ADDR'] ?? null); } catch (Exception $eLog) {
                        error_log('Error al registrar modificacion interna: ' . $eLog->getMessage());
                    }
                }

                file_put_contents($debugLog, "[{$traceId}] OPERACION COMPLETADA - success={$ok}, id={$data['Id']}\n", FILE_APPEND);

                echo json_encode([
                    'success' => $ok,
                    'id' => $data['Id'],
                    'message' => $ok ? 'Recepción modificada correctamente.' : 'Error al modificar recepción.',
                    'modificaciones_restantes' => $validacion['restantes'] - 1
                ]);
            } catch (Exception $e) {
                // FIX: Rollback en caso de error
                if (isset($db) && $db->inTransaction()) {
                    $db->rollBack();
                    file_put_contents($debugLog, "[{$traceId}] ROLLBACK ejecutado por excepción\n", FILE_APPEND);
                }
                file_put_contents($debugLog, "[{$traceId}] EXCEPCION: " . $e->getMessage() . "\n", FILE_APPEND);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
    }
    
    // Modificar recepción interna (método público que llama a modificarConDatos)
    public function modificar() {
        $this->modificarConDatos();
    }

    // Anular recepción interna
    public function anular() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (stripos($contentType, 'application/json') !== false) {
                $data = json_decode(file_get_contents('php://input'), true);
                $id = $data['Id'] ?? $data['id'] ?? null;
                $motivo = $data['motivo'] ?? '';
            } else {
                $id = $_POST['Id'] ?? $_POST['id'] ?? null;
                $motivo = $_POST['motivo'] ?? '';
            }
            $anulado_por = $_SESSION['user']['id'] ?? null;
            $recepcionModel = $this->model('RecepcionInterna');
            try {
                $recepcionModel->anular($id, $anulado_por, $motivo);
                echo json_encode(['success' => true, 'message' => 'Recepción anulada correctamente.']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
    }

    // Reactivar recepción interna
    public function reactivar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['Id'] ?? $_POST['id'] ?? null;
            $reactivado_por = $_SESSION['user']['id'] ?? null;
            $recepcionModel = $this->model('RecepcionInterna');
            try {
                $recepcionModel->reactivar($id, $reactivado_por);
                echo json_encode(['success' => true, 'message' => 'Recepción reactivada correctamente.']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
    }

    // Obtener datos de una recepción para edición avanzada
    public function getRecepcion() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['Id'] ?? null;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
                return;
            }
            $recepcionModel = $this->model('RecepcionInterna');
            $data = $recepcionModel->getById($id);
            if ($data) {
                echo json_encode(['success' => true, 'data' => $data]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Recepción no encontrada']);
            }
        }
    }
    
    // Vista de edición de vales
    public function edicion() {
        // Verificar que el usuario esté logueado
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        
        $turnoModel = $this->model('Turno');
        $areaModel = $this->model('Area');
        $subareaModel = $this->model('Subarea');
        $responsableModel = $this->model('Responsable');
        $medioTransporteModel = $this->model('MedioTransporte');
        $productoModel = $this->model('Producto');
        
        // Usar métodos disponibles en los modelos (getAll / getAllForSelect)
        $turnos = method_exists($turnoModel, 'getAll') ? $turnoModel->getAll() : [];
        $areas = method_exists($areaModel, 'getAllForSelect') ? $areaModel->getAllForSelect() : (method_exists($areaModel, 'getAll') ? $areaModel->getAll() : []);
        $subareas = method_exists($subareaModel, 'getAllForSelect') ? $subareaModel->getAllForSelect() : (method_exists($subareaModel, 'getAll') ? $subareaModel->getAll() : []);
        $responsables = method_exists($responsableModel, 'getAll') ? $responsableModel->getAll() : [];
        $medios = method_exists($medioTransporteModel, 'getAll') ? $medioTransporteModel->getAll() : [];
        $productos = method_exists($productoModel, 'getAllForSelect') ? $productoModel->getAllForSelect() : [];
        $horaActual = date('H:i:s');
        
        $this->view('recepcionesinternas/edicion', [
            'titulo' => 'Edición de Vale - Recepciones Internas',
            'turnos' => $turnos,
            'areas' => $areas,
            'subareas' => $subareas,
            'responsables' => $responsables,
            'medios' => $medios,
            'productos' => $productos,
            'horaActual' => $horaActual,
            'fechaHoy' => date('Y-m-d')
        ]);
    }
    
    // Verificar estado de modificaciones
    public function verificarModificaciones() {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                if (stripos($contentType, 'application/json') !== false) {
                    $data = json_decode(file_get_contents('php://input'), true);
                    $id = $data['id'] ?? $data['Id'] ?? null;
                } else {
                    $id = $_POST['id'] ?? $_POST['Id'] ?? null;
                }
            } else {
                // Aceptar también GET desde el frontend
                $id = $_GET['id'] ?? $_GET['Id'] ?? null;
            }
            
            if (!$id) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID no proporcionado'
                ]);
                return;
            }
            
            $recepcionModel = $this->model('RecepcionInterna');
            $validacion = $recepcionModel->puedeModificar($id);
            
            echo json_encode([
                'success' => true,
                'puede_modificar' => $validacion['puede'],
                'modificaciones' => $validacion['modificaciones'],
                'restantes' => $validacion['restantes'],
                'mensaje' => $validacion['mensaje']
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // Buscar vales por número o fecha (acepta GET y POST)
    public function buscarVales() {
        header('Content-Type: application/json');
        try {
            // Aceptar parámetros desde GET o POST (compatibilidad con llamadas fetch GET)
            $nvale = $_GET['nvale'] ?? $_POST['nvale'] ?? '';
            $fecha = $_GET['fecha'] ?? $_POST['fecha'] ?? '';

                 $sql = "SELECT ri.Id, ri.NVale, ri.Fecha, ri.Hora, 
                          t.Turno as Turno, 
                          COALESCE(a.Area, '') AS AreaNombre, 
                          COALESCE(sa.Subarea, '') AS SubareaNombre,
                          ri.modificaciones_count
                      FROM recepciones_internas ri
                      LEFT JOIN turnos t ON ri.Turno = t.Id
                      LEFT JOIN areas a ON ri.Area = a.Id
                      LEFT JOIN subareas sa ON ri.Subarea = sa.Id
                      WHERE COALESCE(LOWER(ri.estado), '') != 'anulado'";

            $params = [];

            if (!empty($nvale)) {
                // Normalizar a solo números como hacen otros controladores
                $nvaleRaw = preg_replace('/[^0-9]/', '', $nvale);
                $nvaleClean = ltrim($nvaleRaw, '0');
                if ($nvaleClean === '') $nvaleClean = '0';
                if (!empty($nvaleRaw)) {
                    $sql .= " AND ri.NVale = ?";
                    $params[] = $nvaleClean;
                }
            }

            if (!empty($fecha)) {
                $sql .= " AND ri.Fecha = ?";
                $params[] = $fecha;
            }

            $sql .= " ORDER BY ri.Id DESC LIMIT 50";

            // Usar la conexión estándar
            $db = \Database::getInstance()->getConnection();
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $vales = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'vales' => $vales
            ]);
        } catch (Exception $e) {
            error_log('Error al buscar vales (RecepcionesInternas): ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error al buscar vales: ' . $e->getMessage()
            ]);
        }
    }
    
    // Obtener vale completo con productos
    public function obtenerVale() {
        // Aceptar GET o POST para compatibilidad con llamadas desde el cliente
        $id = null;
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (stripos($contentType, 'application/json') !== false) {
                $data = json_decode(file_get_contents('php://input'), true);
                $id = $data['id'] ?? $data['Id'] ?? null;
            } else {
                $id = $_POST['id'] ?? $_POST['Id'] ?? null;
            }
        } else {
            // GET
            $id = $_GET['id'] ?? $_GET['Id'] ?? null;
        }

        if (!$id) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'ID no proporcionado'
            ]);
            return;
        }

        try {
            $db = \Database::getInstance()->getConnection();

            // Obtener datos de la recepción con todas las relaciones
            $sql = "SELECT ri.*, 
                           t.Turno as TurnoNombre,
                           a.Area as AreaNombre, 
                           sa.Subarea as SubareaNombre,
                           e.NombresApellidos as EmisorNombre,
                           d.NombresApellidos as DespachadorNombre,
                           mt.MedioTransporte as MedioTransporteNombre,
                           v.NombresApellidos as VerificadorNombre
                    FROM recepciones_internas ri
                    LEFT JOIN turnos t ON ri.Turno = t.Id
                    LEFT JOIN areas a ON ri.Area = a.Id
                    LEFT JOIN subareas sa ON ri.Subarea = sa.Id
                    LEFT JOIN responsables e ON ri.Emisor = e.Id
                    LEFT JOIN responsables d ON ri.Despachador = d.Id
                    LEFT JOIN medio_transporte mt ON ri.MedioTransporte = mt.Id
                    LEFT JOIN responsables v ON ri.Verificador = v.Id
                    WHERE ri.Id = ?";

            $stmt = $db->prepare($sql);
            $stmt->execute([$id]);
            $recepcion = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$recepcion) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Recepción no encontrada'
                ]);
                return;
            }

                // Obtener productos (incluir el Id del producto maestro cuando exista)
                // Usamos subconsultas con LIMIT 1 para evitar duplicados si el maestro tiene varias filas con el mismo código
                $sqlProductos = "SELECT rip.*, 
                                          (SELECT p.Id FROM productos p WHERE p.Codigo = rip.CodigoProducto LIMIT 1) AS ProductoId,
                                          (SELECT p.Codigo FROM productos p WHERE p.Codigo = rip.CodigoProducto LIMIT 1) AS ProductoCodigo
                                      FROM recepciones_internas_productos rip
                                      WHERE rip.DespachoId = ?";
                $stmtProductos = $db->prepare($sqlProductos);
                $stmtProductos->execute([$id]);
                $productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

            $recepcion['productos'] = $productos;

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'vale' => $recepcion
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener vale: ' . $e->getMessage()
            ]);
        }
    }

    // Obtener datos completos para vista previa (usado desde reportes)
    public function getById() {
        header('Content-Type: application/json');
        try {
            $rawInput = file_get_contents('php://input');
            $params = json_decode($rawInput, true);
            $id = $params['id'] ?? $_GET['id'] ?? $_POST['id'] ?? null;
            
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
                return;
            }
            
            $model = $this->model('RecepcionInterna');
            $recepcion = $model->getById($id);
            
            if ($recepcion) {
                echo json_encode([
                    'success' => true,
                    'data' => $recepcion
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'message' => 'Recepción no encontrada']);
            }
        } catch (Exception $e) {
            error_log('Error en RecepcionInterna getById: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
}
