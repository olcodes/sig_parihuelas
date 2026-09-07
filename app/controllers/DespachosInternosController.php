<?php
class DespachosInternosController extends Controller
{
    // Obtener el siguiente correlativo de vale (AJAX)
    public function siguienteVale() {
        $despachoModel = $this->model('DespachoInterno');
        $siguiente = $despachoModel->obtenerSiguienteVale();
        echo json_encode(['success' => true, 'correlativo' => str_pad($siguiente, 6, '0', STR_PAD_LEFT)]);
        exit;
    }
    public function index()
    {
        // Verificar que el usuario esté logueado
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        
        // Recepcionistas para el combo
        $recepcionistaModel = $this->model('Recepcionista');
        $recepcionistas = $recepcionistaModel->getAll();
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

        // Mapping responsable_id => username para firmas de impresión
        $responsablesUsernameMap = [];
        try {
            $dbConn = Database::getInstance()->getConnection();
            $usersStmt = $dbConn->query("SELECT username, NombresApellidos FROM usuarios WHERE username IS NOT NULL AND username != ''");
            $usuarios_list = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($responsables as $resp) {
                $respNombre = strtolower(trim($resp['NombresApellidos'] ?? ''));
                if (!$respNombre) continue;
                $rWords = array_values(array_filter(explode(' ', preg_replace('/\s+/', ' ', $respNombre))));
                foreach ($usuarios_list as $usr) {
                    $usrNombre = strtolower(trim($usr['NombresApellidos'] ?? ''));
                    if (!$usrNombre) continue;
                    if ($respNombre === $usrNombre) {
                        $responsablesUsernameMap[$resp['Id']] = $usr['username'];
                        break;
                    }
                    // Si todas las palabras del nombre corto están en el nombre largo
                    $uWords = array_values(array_filter(explode(' ', preg_replace('/\s+/', ' ', $usrNombre))));
                    $shorter = count($rWords) <= count($uWords) ? $rWords : $uWords;
                    $longer  = count($rWords) <= count($uWords) ? $uWords  : $rWords;
                    if (count(array_intersect($shorter, $longer)) === count($shorter)) {
                        $responsablesUsernameMap[$resp['Id']] = $usr['username'];
                        break;
                    }
                }
            }
        } catch (Exception $e) { /* Si falla, el mapa queda vacío */ }

        $this->view('despachosinternos/index', [
            'titulo' => 'Despachos Internos',
            'turnos' => $turnos,
            'horaActual' => $horaActual,
            'subareas' => $subareas,
            'productos' => $productos,
            'responsables' => $responsables,
            'recepcionistas' => $recepcionistas,
            'responsablesUsernameMap' => $responsablesUsernameMap
        ]);
    }

    public function edicion()
    {
        // Verificar que el usuario esté logueado
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        
        // Recepcionistas para el combo
        $recepcionistaModel = $this->model('Recepcionista');
        $recepcionistas = $recepcionistaModel->getAll();
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
        $subareas = $subareaModel->getAll('', 1000, 0);

        // Mapping responsable_id => username para firmas de impresión
        $responsablesUsernameMap = [];
        try {
            $dbConn = Database::getInstance()->getConnection();
            $usersStmt = $dbConn->query("SELECT username, NombresApellidos FROM usuarios WHERE username IS NOT NULL AND username != ''");
            $usuarios_list = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($responsables as $resp) {
                $respNombre = strtolower(trim($resp['NombresApellidos'] ?? ''));
                if (!$respNombre) continue;
                $rWords = array_values(array_filter(explode(' ', preg_replace('/\s+/', ' ', $respNombre))));
                foreach ($usuarios_list as $usr) {
                    $usrNombre = strtolower(trim($usr['NombresApellidos'] ?? ''));
                    if (!$usrNombre) continue;
                    if ($respNombre === $usrNombre) {
                        $responsablesUsernameMap[$resp['Id']] = $usr['username'];
                        break;
                    }
                    // Si todas las palabras del nombre corto están en el nombre largo
                    $uWords = array_values(array_filter(explode(' ', preg_replace('/\s+/', ' ', $usrNombre))));
                    $shorter = count($rWords) <= count($uWords) ? $rWords : $uWords;
                    $longer  = count($rWords) <= count($uWords) ? $uWords  : $rWords;
                    if (count(array_intersect($shorter, $longer)) === count($shorter)) {
                        $responsablesUsernameMap[$resp['Id']] = $usr['username'];
                        break;
                    }
                }
            }
        } catch (Exception $e) { /* Si falla, el mapa queda vacío */ }

        $this->view('despachosinternos/edicion', [
            'titulo' => 'Edición de Vale - Despachos Internos',
            'turnos' => $turnos,
            'horaActual' => $horaActual,
            'subareas' => $subareas,
            'productos' => $productos,
            'responsables' => $responsables,
            'recepcionistas' => $recepcionistas,
            'responsablesUsernameMap' => $responsablesUsernameMap,
            'fechaHoy' => date('Y-m-d')
        ]);
    }

    // Registrar despacho interno
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
                    $data['NVale'] = ltrim(preg_replace('/^VDI-/', '', $data['correlativoVale']), '0');
                } else {
                    $data['NVale'] = null;
                }
                $despachoModel = $this->model('DespachoInterno');
                $productoModel = $this->model('DespachoInternoProducto');
                $productos = $data['productos'] ?? [];
                @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "=== GUARDAR LLAMADO ===\n", FILE_APPEND);
                @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "Id presente: " . (isset($data['Id']) ? 'SI' : 'NO') . "\n", FILE_APPEND);
                @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "Id valor: " . (isset($data['Id']) ? $data['Id'] : 'NULL') . "\n", FILE_APPEND);
                @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "Id empty: " . (empty($data['Id']) ? 'SI' : 'NO') . "\n", FILE_APPEND);
                @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', print_r($data, true) . "\n", FILE_APPEND);
                // Si viene Id, modificar; si no, registrar
                if (!empty($data['Id'])) {
                    $this->modificarConDatos($data);
                    return;
                } else {
                    // Registrar solo el despacho y obtener el ID
                    $despachoData = [
                        'NVale' => $data['NVale'],
                        'Fecha' => $data['fecha'] ?? null,
                        'Hora' => date('H:i:s'),
                        'Turno' => $data['turno'] ?? null,
                        'Area' => $data['area'] ?? null,
                        'Subarea' => $data['subarea'] ?? null,
                        'Emisor' => $data['creado_por'] ?? null,
                        'Despachador' => $data['despachador'] ?? null,
                        'Recepcionista' => $data['recepcionista'] ?? null,
                        'Verificador' => $data['verificador'] ?? null,
                        'Liquidacion' => null,
                        'creado_por' => $data['creado_por'],
                        'ip_creacion' => $data['ip_creacion'] ?? null
                    ];
                    @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "ANTES registrarDespacho\n", FILE_APPEND);
                    $despachoId = $despachoModel->registrarDespacho($despachoData);
                    @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "DESPUES registrarDespacho\n", FILE_APPEND);
                    @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "DESPACHO ID: " . print_r($despachoId, true) . "\n", FILE_APPEND);
                    @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "PRODUCTOS ARRAY: " . print_r($productos, true) . "\n", FILE_APPEND);
                    $resultados = [];
                    @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "PRODUCTOS RECIBIDOS: " . print_r($productos, true) . "\n", FILE_APPEND);
                    if ($despachoId && is_array($productos) && count($productos) > 0) {
                        foreach ($productos as $i => $prod) {
                            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "PRODUCTO #$i: " . print_r($prod, true) . "\n", FILE_APPEND);
                            $productoData = [
                                'DespachoId' => $despachoId,
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
                        @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "ERROR: despachoId o productos no válidos\n", FILE_APPEND);
                    }
                }
                if (count($resultados) && !in_array(false, $resultados)) {
                    echo json_encode(['success' => true, 'id' => $despachoId, 'message' => 'Despacho registrado correctamente.']);
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

    // Modificar despacho interno
    // Nueva función auxiliar que recibe los datos como parámetro
    private function modificarConDatos($data) {
        // Corregir el número de vale igual que en guardar
        if (!empty($data['correlativoVale'])) {
            $data['NVale'] = ltrim(preg_replace('/^VDI-/', '', $data['correlativoVale']), '0');
        }
        if (!isset($data['Id']) || empty($data['Id'])) {
            echo json_encode(['success' => false, 'message' => 'ID de despacho no recibido']);
            return;
        }
        
        $despachoModel = $this->model('DespachoInterno');
        
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
        
        $productoModel = $this->model('DespachoInternoProducto');
        $productos = $data['productos'] ?? [];
        try {
            $ok = $despachoModel->actualizarDespacho($data['Id'], $data);
            $productoModel->eliminarPorDespacho($data['Id']);
            foreach ($productos as $prod) {
                $productoData = [
                    'DespachoId' => $data['Id'],
                    'CodigoProducto' => $prod['codigo'] ?? null,
                    'DescripcionProducto' => $prod['producto'] ?? null,
                    'UnidadMedida' => $prod['unidadMedida'] ?? '',
                    'Cantidad' => $prod['cantidad'] ?? null,
                    'Comentarios' => $prod['comentarios'] ?? ''
                ];
                $productoModel->registrar($productoData);
            }
            
            if ($ok) {
                $usuarioId = $_SESSION['user']['id'] ?? null;
                try { $despachoModel->registrarModificacion($data['Id'], $usuarioId, $_SERVER['REMOTE_ADDR'] ?? null); } catch (Exception $eLog) {
                    error_log('Error al registrar modificacion: ' . $eLog->getMessage());
                }
            }

            echo json_encode([
                'success' => $ok,
                'id' => $data['Id'],
                'message' => $ok ? 'Despacho modificado correctamente.' : 'Error al modificar despacho.'
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function modificar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (stripos($contentType, 'application/json') !== false) {
                $data = json_decode(file_get_contents('php://input'), true);
            } else {
                $data = $_POST;
            }
            // Usar la función auxiliar para evitar duplicación de código
            $this->modificarConDatos($data);
        }
    }

    // Anular despacho interno
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
            $despachoModel = $this->model('DespachoInterno');
            try {
                $despachoModel->anular($id, $anulado_por, $motivo);
                echo json_encode(['success' => true, 'message' => 'Despacho anulado correctamente.']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
    }

    // Reactivar despacho interno
    public function reactivar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['Id'] ?? $_POST['id'] ?? null;
            $reactivado_por = $_SESSION['user']['id'] ?? null;
            $despachoModel = $this->model('DespachoInterno');
            try {
                $despachoModel->reactivar($id, $reactivado_por);
                echo json_encode(['success' => true, 'message' => 'Despacho reactivado correctamente.']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
    }

    // Obtener datos de un despacho para edición avanzada
    public function getDespacho() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['Id'] ?? null;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
                return;
            }
            $despachoModel = $this->model('DespachoInterno');
            $data = $despachoModel->obtenerPorId($id);
            if ($data) {
                echo json_encode(['success' => true, 'data' => $data]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Despacho no encontrado']);
            }
        }
    }

    // Verificar si un vale puede ser modificado (validar límite de modificaciones)
    public function verificarModificaciones() {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                if (stripos($contentType, 'application/json') !== false) {
                    $data = json_decode(file_get_contents('php://input'), true);
                    $id = $data['Id'] ?? $data['id'] ?? null;
                } else {
                    $id = $_POST['Id'] ?? $_POST['id'] ?? null;
                }
            } else {
                // Aceptar también GET desde el frontend
                $id = $_GET['id'] ?? $_GET['Id'] ?? null;
            }
            
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
                return;
            }
            
            $despachoModel = $this->model('DespachoInterno');
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

    // Buscar vales existentes (para el modal de búsqueda en edición)
    public function buscarVales() {
        header('Content-Type: application/json');
        try {
            $db = Database::getInstance()->getConnection();
            
            // Construir query con filtros opcionales
            $sql = "SELECT di.Id, di.NVale, di.Fecha, di.Hora, 
                           a.Area as AreaNombre, sa.Subarea as SubareaNombre,
                           rec.NombresApellidos as RecepcionistaNombre,
                           tu.Turno
                    FROM despachos_internos di
                    LEFT JOIN areas a ON di.Area = a.Id
                    LEFT JOIN subareas sa ON di.Subarea = sa.Id
                    LEFT JOIN recepcionistas rec ON di.Recepcionista = rec.Id
                    LEFT JOIN turnos tu ON di.Turno = tu.Id
                    WHERE 1=1";
            
            $params = [];
            
            // Filtros opcionales desde GET
            if (!empty($_GET['nvale'])) {
                // Limpiar el valor: extraer solo números y normalizar sin ceros a la izquierda
                $nvaleRaw = preg_replace('/[^0-9]/', '', $_GET['nvale']);
                // Quitar ceros a la izquierda; si queda vacío, usar '0'
                $nvale = ltrim($nvaleRaw, '0');
                if ($nvale === '') $nvale = '0';
                if (!empty($nvaleRaw)) {
                    $sql .= " AND di.NVale = :nvale";
                    $params[':nvale'] = $nvale;
                }
            }
            if (!empty($_GET['fecha'])) {
                $sql .= " AND di.Fecha = :fecha";
                $params[':fecha'] = $_GET['fecha'];
            }
            
            $sql .= " ORDER BY di.Id DESC LIMIT 50";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
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
            
            // Obtener datos del vale
            $sql = "SELECT di.*, 
                           a.Area as AreaNombre, 
                           sa.Subarea as SubareaNombre,
                           rec.NombresApellidos as RecepcionistaNombre,
                           resp_desp.NombresApellidos as DespachadorNombre,
                           resp_ver.NombresApellidos as VerificadorNombre,
                           tu.Turno as TurnoNombre,
                           di.Area as AreaId, 
                           di.Subarea as SubareaId
                    FROM despachos_internos di
                    LEFT JOIN areas a ON di.Area = a.Id
                    LEFT JOIN subareas sa ON di.Subarea = sa.Id
                    LEFT JOIN recepcionistas rec ON di.Recepcionista = rec.Id
                    LEFT JOIN responsables resp_desp ON di.Despachador = resp_desp.Id
                    LEFT JOIN responsables resp_ver ON di.Verificador = resp_ver.Id
                    LEFT JOIN turnos tu ON di.Turno = tu.Id
                    WHERE di.Id = :id";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $valeId]);
            $vale = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$vale) {
                error_log("Vale no encontrado con ID: " . $valeId);
                echo json_encode(['success' => false, 'message' => 'Vale no encontrado']);
                return;
            }
            
            error_log("Vale encontrado: " . print_r($vale, true));
            
            // Obtener productos del vale
            $sqlProductos = "SELECT dip.*
                            FROM despachos_internos_productos dip
                            WHERE dip.DespachoId = :id
                            ORDER BY dip.Id";
            
            $stmtProductos = $db->prepare($sqlProductos);
            $stmtProductos->execute([':id' => $valeId]);
            $productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Productos encontrados: " . count($productos));
            
            $vale['productos'] = $productos;
            
            error_log("Vale completo con productos preparado");
            echo json_encode(['success' => true, 'vale' => $vale]);
        } catch (Exception $e) {
            error_log("Error al obtener vale: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            echo json_encode(['success' => false, 'error' => $e->getMessage(), 'message' => 'Error al cargar el vale']);
        }
    }

    // Obtener datos completos de un vale para vista previa (usado desde reportes)
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
            
            $model = $this->model('DespachoInterno');
            $despacho = $model->getById($id);
            
            if ($despacho) {
                // Estructura esperada por el JS del reporte
                echo json_encode([
                    'success' => true,
                    'data' => $despacho
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'message' => 'Vale no encontrado']);
            }
        } catch (Exception $e) {
            error_log('Error en getById: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

}
