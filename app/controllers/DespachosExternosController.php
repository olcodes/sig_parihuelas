<?php
require_once __DIR__ . '/../../core/Controller.php';
class DespachosExternosController extends Controller {
    // Anular despacho externo
    public function anular() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (stripos($contentType, 'application/json') !== false) {
                $data = json_decode(file_get_contents('php://input'), true);
                $id = $data['id'] ?? null;
                $motivo = $data['motivo'] ?? '';
            } else {
                $id = $_POST['Id'] ?? $_POST['id'] ?? null;
                $motivo = $_POST['motivo'] ?? '';
            }
            $anulado_por = $_SESSION['user']['id'] ?? null;
            $despachoModel = $this->model('DespachoExterno');
            try {
                $despachoModel->anular($id, $anulado_por, $motivo);
                echo json_encode(['success' => true, 'message' => 'Despacho anulado correctamente.']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
    }

    // Reactivar despacho externo
    public function reactivar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (stripos($contentType, 'application/json') !== false) {
                $data = json_decode(file_get_contents('php://input'), true);
                $id = $data['id'] ?? null;
            } else {
                $id = $_POST['Id'] ?? $_POST['id'] ?? null;
            }
            $reactivado_por = $_SESSION['user']['id'] ?? null;
            $despachoModel = $this->model('DespachoExterno');
            try {
                $despachoModel->reactivar($id, $reactivado_por);
                echo json_encode(['success' => true, 'message' => 'Despacho reactivado correctamente.']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
    }

    // Obtener el siguiente correlativo de vale (AJAX)
    public function siguienteVale() {
        try {
            header('Content-Type: application/json; charset=utf-8');
            error_log('[DEBUG siguienteVale] INICIO del método');
            
            if (!isset($this)) {
                error_log('[ERROR siguienteVale] $this no existe');
                echo json_encode(['success' => false, 'message' => '$this no existe']);
                exit;
            }
            
            error_log('[DEBUG siguienteVale] $this existe, intentando cargar modelo...');
            $despachoModel = $this->model('DespachoExterno');
            
            if (!$despachoModel) {
                error_log('[ERROR siguienteVale] modelo devolvió null');
                echo json_encode(['success' => false, 'message' => 'No se pudo cargar el modelo DespachoExterno']);
                exit;
            }
            
            error_log('[DEBUG siguienteVale] modelo cargado OK, clase=' . get_class($despachoModel));
            
            if (!method_exists($despachoModel, 'obtenerSiguienteVale')) {
                error_log('[ERROR siguienteVale] método obtenerSiguienteVale no existe en el modelo');
                echo json_encode(['success' => false, 'message' => 'Método obtenerSiguienteVale no existe']);
                exit;
            }
            
            error_log('[DEBUG siguienteVale] llamando obtenerSiguienteVale...');
            $siguiente = $despachoModel->obtenerSiguienteVale();
            error_log('[DEBUG siguienteVale] siguiente vale obtenido: ' . $siguiente);
            
            $resultado = ['success' => true, 'correlativo' => str_pad($siguiente, 6, '0', STR_PAD_LEFT)];
            error_log('[DEBUG siguienteVale] enviando respuesta: ' . json_encode($resultado));
            echo json_encode($resultado);
            
        } catch (Exception $e) {
            error_log('[ERROR siguienteVale] Exception: ' . $e->getMessage());
            error_log('[ERROR siguienteVale] File: ' . $e->getFile() . ' Line: ' . $e->getLine());
            error_log('[ERROR siguienteVale] Stack trace: ' . $e->getTraceAsString());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        } catch (Throwable $t) {
            error_log('[FATAL siguienteVale] Throwable: ' . $t->getMessage());
            error_log('[FATAL siguienteVale] File: ' . $t->getFile() . ' Line: ' . $t->getLine());
            error_log('[FATAL siguienteVale] Stack trace: ' . $t->getTraceAsString());
            echo json_encode(['success' => false, 'message' => 'Error fatal: ' . $t->getMessage()]);
        }
        exit;
    }

    // Guardar nuevo despacho externo
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $despachoModel = $this->model('DespachoExterno');
            try {
                // Si llega un Id, interpretamos como actualización/modificación
                $id = $data['Id'] ?? $data['id'] ?? null;
                if ($id && is_numeric($id)) {
                    // VALIDAR LÍMITE DE MODIFICACIONES
                    $validacion = $despachoModel->puedeModificar($id);
                    if (!$validacion['puede']) {
                        echo json_encode([
                            'success' => false,
                            'message' => $validacion['mensaje'],
                            'limite_alcanzado' => true,
                            'modificaciones' => $validacion['modificaciones']
                        ]);
                        return;
                    }
                    // Llamar al método modificar del modelo
                    $despachoModel->modificar($data);

                    $usuarioId = $_SESSION['user']['id'] ?? null;
                    try { $despachoModel->registrarModificacion($id, $usuarioId, $_SERVER['REMOTE_ADDR'] ?? null); } catch (Exception $eLog) {
                        error_log('Error al registrar modificacion externa (guardar): ' . $eLog->getMessage());
                    }

                    echo json_encode(['success' => true, 'id' => (int)$id, 'message' => 'Despacho actualizado correctamente.']);
                } else {
                    // Nuevo registro
                    $newId = $despachoModel->guardar($data);
                    echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Despacho guardado correctamente.']);
                }
            } catch (Exception $e) {
                // [DEBUG FK] Incluir datos de diagnóstico en la respuesta JSON
                $debugInfo = [
                    'despachador_enviado' => $data['despachador'] ?? $data['Despachador'] ?? '(no enviado)',
                    'session_user_id' => $_SESSION['user']['id'] ?? null,
                    'session_user_nombre' => $_SESSION['user']['NombresApellidos'] ?? null,
                ];
                // Intentar verificar si el valor del despachador existe en responsables
                try {
                    $val = $data['despachador'] ?? $data['Despachador'] ?? null;
                    if ($val !== null) {
                        $db = Database::getInstance()->getConnection();
                        // Si es texto (nombre), ver si existe en responsables
                        if (!is_numeric($val)) {
                            $stmt = $db->prepare("SELECT Id FROM responsables WHERE LOWER(NombresApellidos) = LOWER(?) LIMIT 1");
                            $stmt->execute([$val]);
                            $found = $stmt->fetch(PDO::FETCH_ASSOC);
                            $debugInfo['lookup_nombre_en_responsables'] = $found ? 'ENCONTRADO Id=' . $found['Id'] : 'NO ENCONTRADO';
                        } else {
                            // Si es numérico, ver si ese ID existe en responsables
                            $stmt = $db->prepare("SELECT Id, NombresApellidos FROM responsables WHERE Id = ? LIMIT 1");
                            $stmt->execute([$val]);
                            $found = $stmt->fetch(PDO::FETCH_ASSOC);
                            $debugInfo['lookup_id_en_responsables'] = $found ? 'ENCONTRADO: ' . $found['NombresApellidos'] : 'NO ENCONTRADO (FK violation seguro)';
                        }
                        // También ver el creado_por (session user id) en responsables
                        $uid = $_SESSION['user']['id'] ?? null;
                        if ($uid) {
                            $stmt2 = $db->prepare("SELECT Id, NombresApellidos FROM responsables WHERE Id = ? LIMIT 1");
                            $stmt2->execute([$uid]);
                            $found2 = $stmt2->fetch(PDO::FETCH_ASSOC);
                            $debugInfo['session_user_id_en_responsables'] = $found2 ? 'ENCONTRADO: ' . $found2['NombresApellidos'] : 'NO ENCONTRADO (este es el FALLBACK!)';
                        }
                    }
                } catch (Exception $dbgErr) {
                    $debugInfo['debug_error'] = $dbgErr->getMessage();
                }
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'debug_despachador' => $debugInfo
                ]);
            }
        }
    }

    /**
     * Intentar ejecutar una consulta SQL reemplazando el marcador {TABLE} por
     * varios nombres de tabla hasta que uno funcione. Devuelve el PDOStatement.
     *
     * @param PDO $db
     * @param string $sqlTemplate SQL que contiene la marca {TABLE}
     * @param array $tableNames Nombres de tabla a probar en orden
     * @param array $params Parámetros para execute()
     * @return PDOStatement
     * @throws Exception Si ninguna tabla funciona
     */
    private function tryQueryWithTableFallback($db, $sqlTemplate, $tableNames, $params = []) {
        // Ya NO hacer auto-detección aquí porque causa conflictos
        // La detección específica se hace antes de llamar a este método
        
        foreach ($tableNames as $tbl) {
            $sql = str_replace('{TABLE}', $tbl, $sqlTemplate);
            // Log SQL being attempted for easier debugging
            try {
                error_log("tryQueryWithTableFallback: intentando con tabla '$tbl' SQL: " . preg_replace('/\s+/', ' ', trim($sql)));
            } catch (Exception $__) {}
            try {
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                return $stmt;
            } catch (Exception $e) {
                // Si es error de tabla no existente, intentar siguiente; si es otro error, relanzar
                $msg = $e->getMessage();
                error_log("tryQueryWithTableFallback: fallo con tabla '$tbl' - " . $msg);
                if (strpos($msg, '1146') !== false || stripos($msg, 'doesn') !== false || stripos($msg, 'exist') !== false) {
                    // intentar siguiente tabla
                    continue;
                }
                throw $e;
            }
        }
        throw new Exception('Ninguna de las tablas existió: ' . implode(', ', $tableNames));
    }

    // Modificar despacho externo existente
    public function modificar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['Id']) || empty($data['Id'])) {
                echo json_encode(['success' => false, 'message' => 'ID de despacho no recibido']);
                return;
            }
            
            $despachoModel = $this->model('DespachoExterno');
            
            // VALIDAR LÍMITE DE MODIFICACIONES
            $validacion = $despachoModel->puedeModificar($data['Id']);
            if (!$validacion['puede']) {
                echo json_encode([
                    'success' => false, 
                    'message' => $validacion['mensaje'],
                    'limite_alcanzado' => true,
                    'modificaciones' => $validacion['modificaciones']
                ]);
                return;
            }
            
            try {
                $despachoModel->modificar($data);

                $usuarioId = $_SESSION['user']['id'] ?? null;
                try { $despachoModel->registrarModificacion($data['Id'], $usuarioId); } catch (Exception $eLog) {
                    error_log('Error al registrar modificacion externa: ' . $eLog->getMessage());
                }

                echo json_encode([
                    'success' => true, 
                    'message' => 'Despacho modificado correctamente.',
                    'modificaciones' => $validacion['modificaciones'] + 1,
                    'restantes' => max(0, $validacion['restantes'] - 1)
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
    }
    // Obtener despacho por Id (cabecera + productos) para editar
    public function getById() {
        $id = null;
        // Log request params for debugging (temporal)
        try{
            error_log('[DEBUG getById] REQUEST_METHOD=' . ($_SERVER['REQUEST_METHOD'] ?? 'UNK') . ' _GET=' . var_export($_GET, true) . ' _POST=' . var_export($_POST, true));
        }catch(Exception $__){}
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $id = $_GET['id'] ?? $_GET['Id'] ?? null;
        } else {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? $data['Id'] ?? null;
        }
        $despachoModel = $this->model('DespachoExterno');
        try {
            $result = $despachoModel->getById($id);
            if ($result === null) {
                error_log('[DEBUG getById] model returned NULL for id=' . var_export($id, true));
                // Devolver failure explícito para que el cliente no intente procesar data=null
                echo json_encode(['success' => false, 'message' => 'Despacho no encontrado o error al consultar base de datos.']);
            } else {
                error_log('[DEBUG getById] model returned data for id=' . var_export($id, true));
                echo json_encode(['success' => true, 'data' => $result]);
            }
        } catch (Exception $e) {
            error_log('[ERROR getById] Exception: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // Validar si un despacho puede ser modificado (máximo 3 modificaciones)
    public function puedeModificar() {
        header('Content-Type: application/json');
        try {
            $id = null;
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $id = $_GET['id'] ?? $_GET['Id'] ?? null;
            } else {
                $id = $_POST['Id'] ?? $_POST['id'] ?? null;
            }
            
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
                return;
            }
            
            $despachoModel = $this->model('DespachoExterno');
            $validacion = $despachoModel->puedeModificar($id);
            
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
    
    // Alias de puedeModificar para consistencia con otros módulos
    public function verificarModificaciones() {
        return $this->puedeModificar();
    }

    public function index()
    {
        // Verificar que el usuario esté logueado
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
    // Choferes para el combo
    $choferModel = $this->model('Chofer');
    $choferes = $choferModel->getAll();
    // Transportistas para el combo
    $transportistaModel = $this->model('Transportista');
    $transportistas = $transportistaModel->getAll();
        // Productos para el combo
        $productoModel = $this->model('Producto');
        $productos = $productoModel->getAllForSelect(); // Ordenado por Producto ASC
        // Turnos y hora actual
        $turnoModel = $this->model('Turno');
        $turnos = $turnoModel->getAll();
        $horaActual = date('H:i');
        // Destinos para el combo
        try {
            $destinoModel = $this->model('Destino');
            $destinos = method_exists($destinoModel, 'getAll') ? $destinoModel->getAll() : [];
        } catch (Exception $e) {
            $destinos = [];
        }
        // Placas para los combos
        $placaModel = $this->model('Placa');
        $placas = $placaModel->getAll();
        // Renderizar la vista y pasar los datos
        $this->view('despachos_externos', [
            'choferes' => $choferes,
            'transportistas' => $transportistas,
            'productos' => $productos,
            'turnos' => $turnos,
            'horaActual' => $horaActual,
            'destinos' => $destinos,
            'placas' => $placas,
            'usuarioLogueado' => $_SESSION['user']
        ]);
    }

    public function edicion()
    {
        // Verificar que el usuario esté logueado
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        
        // Choferes para el combo
        $choferModel = $this->model('Chofer');
        $choferes = $choferModel->getAll();
        // Transportistas para el combo
        $transportistaModel = $this->model('Transportista');
        $transportistas = $transportistaModel->getAll();
        // Productos para el combo
        $productoModel = $this->model('Producto');
        $productos = $productoModel->getAllForSelect(); // Ordenado por Producto ASC
        // Turnos y hora actual
        $turnoModel = $this->model('Turno');
        $turnos = $turnoModel->getAll();
        $horaActual = date('H:i');
        // Destinos
        try {
            $destinoModel = $this->model('Destino');
            $destinos = method_exists($destinoModel, 'getAll') ? $destinoModel->getAll() : [];
        } catch (Exception $e) {
            $destinos = [];
        }
        // Placas
        $placaModel = $this->model('Placa');
        $placas = $placaModel->getAll();

        $this->view('despachosexternos/edicion', [
            'titulo' => 'Edición de Vale - Despachos Externos',
            'choferes' => $choferes,
            'transportistas' => $transportistas,
            'productos' => $productos,
            'turnos' => $turnos,
            'horaActual' => $horaActual,
            'destinos' => $destinos,
            'placas' => $placas,
            'fechaHoy' => date('Y-m-d'),
            'usuarioLogueado' => $_SESSION['user']
        ]);
    }

    // Buscar vales existentes (para el modal de búsqueda en edición)
    public function buscarVales() {
        header('Content-Type: application/json');
        try {
            $db = Database::getInstance()->getConnection();
            
                // Construir query con filtros opcionales (usar plantilla para intentar distintas tablas de destino)
                $sqlTemplate = "SELECT de.Id, de.NVale, de.Fecha, de.Hora, 
                       d.Empresa as DestinoNombre,
                       ch.ApellidosNombres as ChoferNombre,
                       tu.Turno
                    FROM despachos_externos de
                    LEFT JOIN {TABLE} d ON de.Destino = d.Id
                    LEFT JOIN choferes ch ON de.Chofer = ch.Id
                    LEFT JOIN turnos tu ON de.Turno = tu.Id
                    WHERE 1=1";

                $params = [];
            $extraWhere = '';
            
            // Filtros opcionales desde GET
            if (!empty($_GET['nvale'])) {
                $nvaleRaw = preg_replace('/[^0-9]/', '', $_GET['nvale']);
                $nvale = ltrim($nvaleRaw, '0');
                if ($nvale === '') $nvale = '0';
                if (!empty($nvaleRaw)) {
                    $extraWhere .= " AND de.NVale = :nvale";
                    $params[':nvale'] = $nvale;
                }
            }
            if (!empty($_GET['fecha'])) {
                $extraWhere .= " AND de.Fecha = :fecha";
                $params[':fecha'] = $_GET['fecha'];
            }
            
            $fullTemplate = $sqlTemplate . $extraWhere . " ORDER BY de.Id DESC LIMIT 50";

            // Detectar tablas de destino disponibles
            $possibleTables = ['destino','destinos','clientes_externos','clientes_externo'];
            try {
                $destTablesStmt = $db->prepare("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND (table_name LIKE '%destin%' OR table_name LIKE '%cliente%')");
                $destTablesStmt->execute();
                $detectedDestTables = $destTablesStmt->fetchAll(PDO::FETCH_COLUMN);
                if ($detectedDestTables && is_array($detectedDestTables)) {
                    $possibleTables = array_values(array_unique(array_merge($detectedDestTables, $possibleTables)));
                }
            } catch (Exception $__) {}
            
            // Intentar ejecutar la consulta probando varios nombres posibles de la tabla destino
            $stmt = $this->tryQueryWithTableFallback($db, $fullTemplate, $possibleTables, $params);
            $vales = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'vales' => $vales]);
        } catch (Exception $e) {
            error_log("Error al buscar vales: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    // Obtener detalle completo de un vale específico
    public function obtenerVale() {
        header('Content-Type: application/json');
        try {
            if (empty($_GET['id']) && empty($_POST['id'])) {
                echo json_encode(['success' => false, 'message' => 'ID de vale no proporcionado']);
                return;
            }
            
            $valeId = $_GET['id'] ?? $_POST['id'];
            $db = Database::getInstance()->getConnection();
            
            error_log("Buscando vale ID: " . $valeId);
            
            // Obtener datos del vale (usar fallback para nombre de tabla destino)
            // Detectar si la tabla `despachos_externos` tiene columnas relacionadas a placa
            $placaSelects = "'' as PlacaNombre,";
            $placaJoins = "";
            try {
                $colStmt = $db->prepare("SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'despachos_externos'");
                $colStmt->execute();
                $cols = $colStmt->fetchAll(PDO::FETCH_COLUMN);
                $cols = array_map('strtolower', $cols ?: []);

                if (in_array('placa', $cols)) {
                    $placaSelects = 'pl.Placa as PlacaNombre,';
                    $placaJoins .= " LEFT JOIN placas pl ON (pl.Id = de.Placa OR pl.Placa COLLATE utf8mb4_general_ci = de.Placa COLLATE utf8mb4_general_ci) ";
                } else {
                    // Si existen campos separados para tracto/carreta, unirlos por separado
                    if (in_array('placa_tracto', $cols)) {
                        $placaSelects .= "plt.Placa as Placa_TractoNombre,";
                        $placaJoins .= " LEFT JOIN placas plt ON (plt.Id = de.Placa_Tracto OR plt.Placa COLLATE utf8mb4_general_ci = de.Placa_Tracto COLLATE utf8mb4_general_ci) ";
                    }
                    if (in_array('placa_carreta', $cols)) {
                        $placaSelects .= "plc.Placa as Placa_CarretaNombre,";
                        $placaJoins .= " LEFT JOIN placas plc ON (plc.Id = de.Placa_Carreta OR plc.Placa COLLATE utf8mb4_general_ci = de.Placa_Carreta COLLATE utf8mb4_general_ci) ";
                    }
                }
            } catch (Exception $e) {
                error_log('Error detectando columnas de despachos_externos: ' . $e->getMessage());
            }

            $sqlTemplate = "SELECT de.*, 
                          d.Id as DestinoIdReal,
                          d.Empresa as DestinoNombre,
                          d.RUC as DestinoRUC,
                          d.Direccion as DestinoDireccion,
                          ch.ApellidosNombres as ChoferNombre,
                          tr.Empresa as TransportistaNombre,
                          " . $placaSelects . "
                          tu.Turno as TurnoNombre
                      FROM despachos_externos de
                      LEFT JOIN {TABLE} d ON (CAST(de.Destino AS CHAR) COLLATE utf8mb4_general_ci = CAST(d.Id AS CHAR) COLLATE utf8mb4_general_ci OR de.Destino COLLATE utf8mb4_general_ci = d.Empresa COLLATE utf8mb4_general_ci)
                      LEFT JOIN choferes ch ON de.Chofer = ch.Id
                      LEFT JOIN transportistas tr ON de.Transportista = tr.Id
                      " . $placaJoins . "
                      LEFT JOIN turnos tu ON de.Turno = tu.Id
                      WHERE de.Id = :id";

            // Log final template and detected placa joins for debugging
            try {
                error_log('obtenerVale: placaSelects=' . trim(preg_replace('/\s+/', ' ', $placaSelects)) . ' placaJoins=' . trim(preg_replace('/\s+/', ' ', $placaJoins)));
            } catch (Exception $__) {}

            // Detectar tablas de destino disponibles
            $possibleTables = ['destino','destinos','clientes_externos','clientes_externo'];
            try {
                $destTablesStmt = $db->prepare("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND (table_name LIKE '%destin%' OR table_name LIKE '%cliente%')");
                $destTablesStmt->execute();
                $detectedDestTables = $destTablesStmt->fetchAll(PDO::FETCH_COLUMN);
                if ($detectedDestTables && is_array($detectedDestTables)) {
                    $possibleTables = array_values(array_unique(array_merge($detectedDestTables, $possibleTables)));
                }
            } catch (Exception $__) {}
            
            $stmt = $this->tryQueryWithTableFallback($db, $sqlTemplate, $possibleTables, [':id' => $valeId]);
            $vale = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$vale) {
                error_log("Vale no encontrado con ID: " . $valeId);
                echo json_encode(['success' => false, 'message' => 'Vale no encontrado']);
                return;
            }
            
            error_log("Vale encontrado: " . print_r($vale, true));
            
            // Obtener productos del vale - usar fallback de nombres de tabla
            $productosTemplate = "SELECT dep.* FROM {TABLE} dep WHERE dep.DespachoId = :id ORDER BY dep.Id";
            $possibleProdTables = ['despachos_externos_productos','despachos_externos_producto','despachos_externos_items','despachos_productos','despachosproductos'];
            
            // Intentar detectar la tabla real
            try {
                $prodTablesStmt = $db->prepare("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE '%despacho%externo%producto%'");
                $prodTablesStmt->execute();
                $detectedProdTables = $prodTablesStmt->fetchAll(PDO::FETCH_COLUMN);
                if ($detectedProdTables && is_array($detectedProdTables)) {
                    error_log("Tablas de productos detectadas: " . implode(', ', $detectedProdTables));
                    $possibleProdTables = array_values(array_unique(array_merge($detectedProdTables, $possibleProdTables)));
                }
            } catch (Exception $__) {}
            
            error_log("Intentando obtener productos con tablas: " . implode(', ', $possibleProdTables));
            try {
                $stmtProductos = $this->tryQueryWithTableFallback($db, $productosTemplate, $possibleProdTables, [':id' => $valeId]);
                $productosRaw = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);
                error_log("Productos obtenidos exitosamente desde tabla. Cantidad: " . count($productosRaw));
            } catch (Exception $e) {
                error_log('No se pudieron obtener productos con los nombres esperados: ' . $e->getMessage());
                error_log('Nombres de tabla intentados: ' . implode(', ', $possibleProdTables));
                $productosRaw = [];
            }

            error_log("Productos encontrados (raw): " . count($productosRaw));

            // Normalizar campos de producto para que el frontend espere siempre las mismas claves
            $productos = [];
            foreach ($productosRaw as $pr) {
                $p = [];
                // Descripción: buscar múltiples nombres posibles
                $p['DescripcionProducto'] = $pr['DescripcionProducto'] ?? $pr['Descripcion'] ?? $pr['Producto'] ?? $pr['Descripcion_producto'] ?? '';
                // Código
                $p['CodigoProducto'] = $pr['CodigoProducto'] ?? $pr['Codigo'] ?? $pr['Codigo_Producto'] ?? $pr['CodigoProductoDespacho'] ?? '';
                // Unidad de medida
                $p['UnidadMedida'] = $pr['UnidadMedida'] ?? $pr['Unidad'] ?? $pr['UM'] ?? $pr['Unidad_Medida'] ?? '';
                // Cantidad
                $p['Cantidad'] = $pr['Cantidad'] ?? $pr['Qty'] ?? $pr['CantidadDespachada'] ?? $pr['Cantidad_Despacho'] ?? 0;
                // Comentarios
                $p['Comentarios'] = $pr['Comentarios'] ?? $pr['Observacion'] ?? $pr['Comentario'] ?? '';
                $productos[] = $p;
            }

            $vale['productos'] = $productos;
            
            error_log("Vale completo con productos preparado. Destino campo=" . ($vale['Destino']??'NULL') . " DestinoIdReal=" . ($vale['DestinoIdReal']??'NULL') . " DestinoNombre=" . ($vale['DestinoNombre']??'NULL') . " Total productos=" . count($productos));
            error_log("Vale JSON completo: " . json_encode($vale));
            echo json_encode(['success' => true, 'vale' => $vale]);
        } catch (Exception $e) {
            error_log("Error al obtener vale: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            echo json_encode(['success' => false, 'error' => $e->getMessage(), 'message' => 'Error al cargar el vale']);
        }
    }
}
