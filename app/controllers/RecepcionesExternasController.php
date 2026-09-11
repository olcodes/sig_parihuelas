<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/Turno.php';
require_once __DIR__ . '/../../config/database.php';

class RecepcionesExternasController extends Controller {
    
    public function index() {
        try {
            // Cargar turnos para el select
            $turnoModel = new Turno();
            $turnos = $turnoModel->getAll();
            
            // Cargar orígenes
            $origenes = $this->obtenerOrigenes();
            
            // Cargar transportistas (empresa y RUC)
            $transportistas = $this->obtenerTransportistas();
            
            // Cargar choferes
            $choferes = $this->obtenerChoferes();
            
            // Hora actual del sistema
            $horaActual = date('H:i');
            
            // Datos a pasar a la vista
            $datos = [
                'titulo' => 'Recepciones Externas',
                'turnos' => $turnos,
                'origenes' => $origenes,
                'transportistas' => $transportistas,
                'choferes' => $choferes,
                'horaActual' => $horaActual,
                'fechaHoy' => date('Y-m-d')
            ];
            // Cargar series (para select de serie de guías)
            try {
                $db = Database::getInstance()->getConnection();
                $stmt = $db->query('SELECT Id, Serie FROM series ORDER BY Serie ASC');
                $series = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $series = [];
            }
            $datos['series'] = $series;
            
            $this->view('recepcionesexternas/index', $datos);
        } catch (Exception $e) {
            error_log("Error en RecepcionesExternasController::index - " . $e->getMessage());
            echo "Error al cargar la página: " . htmlspecialchars($e->getMessage());
        }
    }
    
    // Obtener orígenes desde la base de datos
    private function obtenerOrigenes() {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query('SELECT Id, Origen FROM origen ORDER BY Origen ASC');
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error al obtener orígenes: " . $e->getMessage());
            return [];
        }
    }
    
    // Obtener transportistas desde la base de datos
    private function obtenerTransportistas() {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query('SELECT Id, RUC, Empresa FROM transportistas ORDER BY Empresa ASC');
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error al obtener transportistas: " . $e->getMessage());
            return [];
        }
    }
    
    // Obtener choferes desde la base de datos
    private function obtenerChoferes() {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query('SELECT Id, ApellidosNombres, Brevete FROM choferes ORDER BY ApellidosNombres ASC');
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error al obtener choferes: " . $e->getMessage());
            return [];
        }
    }
    
    // Método para obtener siguiente correlativo
    public function siguienteVale() {
        header('Content-Type: application/json');
        try {
            require_once __DIR__ . '/../models/RecepcionExterna.php';
            $recepcionModel = new RecepcionExterna();
            $siguienteNumero = $recepcionModel->obtenerSiguienteVale();
            echo json_encode(['success' => true, 'correlativo' => 'VRE-' . str_pad($siguienteNumero, 6, '0', STR_PAD_LEFT)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    // Guardar recepción externa (vale + guías + productos)
    public function guardar() {
        header('Content-Type: application/json');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                return;
            }
            
            // Leer datos JSON
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (stripos($contentType, 'application/json') !== false) {
                $data = json_decode(file_get_contents('php://input'), true);
            } else {
                $data = $_POST;
            }
            
            // Validar sesión
            if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
                echo json_encode(['success' => false, 'message' => 'Sesión expirada. Por favor, vuelva a iniciar sesión.']);
                return;
            }
            
            // Log para depuración
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar_externas.txt', "DATOS RECIBIDOS: " . print_r($data, true) . "\n", FILE_APPEND);
            
            // ===== DIAGNÓSTICO: ¿Es modificación? =====
            $esModificacion = !empty($data['Id']);
            error_log("[RECEPCIONES_EXT_DIAG] guardar() - esModificacion=$esModificacion, Id=" . ($data['Id'] ?? 'NO-ID') . ", modo=" . ($data['modo'] ?? 'no-enviado'));
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar_externas.txt', "DIAGNÓSTICO: esModificacion=" . ($esModificacion ? 'true' : 'false') . ", Id=" . ($data['Id'] ?? 'NO PRESENTE') . "\n", FILE_APPEND);
            
            // ===== PROTECCIÓN ANTI-DUPLICACIÓN (BACKEND) =====
            // Generar un hash único basado en los datos del formulario
            // Si se recibe una solicitud duplicada en menos de 3 segundos, se rechaza
            $requestHash = '';
            if (!empty($data['fecha']) && !empty($data['turno']) && !empty($data['empresa']) && !empty($data['chofer'])) {
                // Excluir campos que varían (como correlativoVale o Id)
                $hashData = [
                    'fecha' => $data['fecha'] ?? '',
                    'turno' => $data['turno'] ?? '',
                    'origen' => $data['origen'] ?? '',
                    'empresa' => $data['empresa'] ?? '',
                    'chofer' => $data['chofer'] ?? '',
                    'guias_count' => isset($data['guias']) ? count($data['guias']) : 0
                ];
                $requestHash = md5(serialize($hashData));
                
                // Inicializar almacén de hashes en sesión si no existe
                if (!isset($_SESSION['_request_hashes'])) {
                    $_SESSION['_request_hashes'] = [];
                }
                
                // Limpiar hashes mayores a 10 segundos
                $now = time();
                foreach ($_SESSION['_request_hashes'] as $hash => $time) {
                    if ($now - $time > 10) {
                        unset($_SESSION['_request_hashes'][$hash]);
                    }
                }
                
                // Verificar si este hash ya se procesó en los últimos segundos
                if (isset($_SESSION['_request_hashes'][$requestHash])) {
                    $logDuplicado = sprintf(
                        "[%s] SOLICITUD DUPLICADA DETECTADA (BACKEND) - Hash=%s - NVale=%s - Id=%s\n",
                        date('Y-m-d H:i:s'),
                        $requestHash,
                        $data['correlativoVale'] ?? 'sin-correlativo',
                        $data['Id'] ?? 'sin-id'
                    );
                    @file_put_contents(__DIR__.'/../../logs/debug_duplicacion_vales.txt', $logDuplicado, FILE_APPEND);
                    error_log("[RECEPCIONES_EXT_DIAG] *** DUPLICADO DETECTADO *** Hash=$requestHash, Id=" . ($data['Id'] ?? 'sin-id'));
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Recepción externa registrada correctamente',
                        'prevenido_duplicado' => true
                    ]);
                    return;
                }
                
                // Registrar este hash
                $_SESSION['_request_hashes'][$requestHash] = $now;
            }
            // ===== FIN PROTECCIÓN ANTI-DUPLICACIÓN =====
            
            // Detectar si es modificación o nuevo registro
            $esModificacion = !empty($data['Id']);
            error_log("[RECEPCIONES_EXT_DIAG] guardar() - después anti-duplicación, esModificacion=$esModificacion");
            
            // Validar datos obligatorios del vale
            $camposObligatorios = [
                'fecha' => 'Fecha',
                'hora' => 'Hora',
                'turno' => 'Turno',
                'origen' => 'Origen',
                'empresa' => 'Empresa',
                'ruc' => 'RUC',
                'chofer' => 'Chofer',
                'brevete' => 'Brevete'
            ];
            
            $faltantes = [];
            foreach ($camposObligatorios as $campo => $etiqueta) {
                if (empty($data[$campo])) {
                    $faltantes[] = $etiqueta;
                }
            }
            
            if (!empty($faltantes)) {
                echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios: ' . implode(', ', $faltantes)]);
                return;
            }
            
            // Validar que haya al menos una guía
            if (!isset($data['guias']) || !is_array($data['guias']) || count($data['guias']) === 0) {
                echo json_encode(['success' => false, 'message' => 'Debe ingresar al menos una guía']);
                return;
            }
            
            // Si es modificación, llamar al método modificar pasando los datos
            if ($esModificacion) {
                error_log("[RECEPCIONES_EXT_DIAG] guardar() - llamando a modificar() con Id=" . $data['Id']);
                $this->modificar($data);
                return;
            }
            
            error_log("[RECEPCIONES_EXT_DIAG] guardar() - NO es modificación, procesando como NUEVO registro");
            
            // Cargar modelos
            require_once __DIR__ . '/../models/RecepcionExterna.php';
            require_once __DIR__ . '/../models/RecepcionExternaGuia.php';
            require_once __DIR__ . '/../models/RecepcionExternaProducto.php';
            
            $recepcionModel = new RecepcionExterna();
            $guiaModel = new RecepcionExternaGuia();
            $productoModel = new RecepcionExternaProducto();
            
            // Limpiar el correlativo para dejar solo el número
            $nVale = null;
            if (!empty($data['correlativoVale'])) {
                $nVale = ltrim(preg_replace('/^VRE-/', '', $data['correlativoVale']), '0');
            }
            
            if (empty($nVale)) {
                $nVale = $recepcionModel->obtenerSiguienteVale();
            }
            
            // Iniciar transacción
            $db = Database::getInstance()->getConnection();
            $db->beginTransaction();
            
            try {
                // 1. Insertar recepción externa (vale)
                $recepcionData = [
                    'NVale' => $nVale,
                    'Fecha' => $data['fecha'],
                    'Hora' => $data['hora'] ?? date('H:i:s'),
                    'Turno' => $data['turno'],
                    'Origen' => $data['origen'],
                    'Recepcionista' => $_SESSION['user']['id'],
                    'Empresa' => $data['empresa'], // ID de la empresa (transportista)
                    'RUC' => $data['ruc'], // RUC del transportista
                    'Chofer' => $data['chofer'], // ID del chofer
                    'Brevete' => $data['brevete'], // Número de brevete
                    'Comentarios' => isset($data['comentarios']) ? mb_strtoupper($data['comentarios']) : null,
                    'Observaciones' => isset($data['observaciones']) ? mb_strtoupper($data['observaciones']) : null,
                    'creado_por' => $_SESSION['user']['id'],
                    'ip_creacion' => $_SERVER['REMOTE_ADDR'] ?? null
                ];
                
                @file_put_contents(__DIR__.'/../../logs/depurar_guardar_externas.txt', "RECEPCION DATA: " . print_r($recepcionData, true) . "\n", FILE_APPEND);
                
                $recepcionId = $recepcionModel->registrarRecepcion($recepcionData);
                
                if (!$recepcionId) {
                    throw new Exception('Error al registrar el vale');
                }
                
                @file_put_contents(__DIR__.'/../../logs/depurar_guardar_externas.txt', "RECEPCION ID: $recepcionId\n", FILE_APPEND);
                
                // 2. Insertar guías y sus productos
                $orden = 1;
                foreach ($data['guias'] as $guiaData) {
                    @file_put_contents(__DIR__.'/../../logs/depurar_guardar_externas.txt', "GUIA #$orden: " . print_r($guiaData, true) . "\n", FILE_APPEND);
                    
                    // Validar número de guía
                    if (empty($guiaData['numeroGuia'])) {
                        throw new Exception("La guía #$orden no tiene número de guía");
                    }
                    
                    // Insertar guía
                    $guiaInsertData = [
                        'RecepcionExternaId' => $recepcionId,
                        'NumeroGuia' => isset($guiaData['numeroGuia']) ? strtoupper($guiaData['numeroGuia']) : ($guiaData['NumeroGuia'] ?? null),
                        'NumeroDocRef' => !empty($guiaData['numeroDocRef']) ? $guiaData['numeroDocRef'] : null,
                        'Observacion' => !empty($guiaData['observacion']) ? $guiaData['observacion'] : null,
                        'TextoObservaciones' => !empty($guiaData['textoObservaciones']) ? $guiaData['textoObservaciones'] : null,
                        'CodigoProductoObs' => !empty($guiaData['codigoProductoObs']) ? $guiaData['codigoProductoObs'] : null,
                        'CantidadObservada' => isset($guiaData['cantidadObservada']) ? $guiaData['cantidadObservada'] : 0,
                        'Orden' => $orden
                    ];
                    
                    $guiaId = $guiaModel->registrar($guiaInsertData);
                    
                    if (!$guiaId) {
                        throw new Exception("Error al registrar la guía #$orden");
                    }
                    
                    @file_put_contents(__DIR__.'/../../logs/depurar_guardar_externas.txt', "GUIA ID: $guiaId\n", FILE_APPEND);
                    
                    // 3. Insertar productos de esta guía
                    if (isset($guiaData['productos']) && is_array($guiaData['productos'])) {
                        foreach ($guiaData['productos'] as $productoData) {
                            @file_put_contents(__DIR__.'/../../logs/depurar_guardar_externas.txt', "PRODUCTO: " . print_r($productoData, true) . "\n", FILE_APPEND);
                            
                            $cantidad = $productoData['cantidad'] ?? 0;
                            $cantidadObservada = $productoData['cantidadObservada'] ?? 0;
                            
                            // Validar que tenga cantidad mayor a 0 o cantidad observada mayor a 0
                            if ((empty($cantidad) || $cantidad <= 0) && (empty($cantidadObservada) || $cantidadObservada <= 0)) {
                                continue; // Saltar productos sin cantidad ni observación
                            }
                            
                            $productoInsertData = [
                                'GuiaId' => $guiaId,
                                'CodigoProducto' => $productoData['codigo'] ?? '',
                                'DescripcionProducto' => $productoData['descripcion'] ?? '',
                                'UnidadMedida' => $productoData['unidadMedida'] ?? '',
                                'Cantidad' => $cantidad,
                                'ColumnaProducto' => $productoData['columna'] ?? 1,
                                'Observacion' => $productoData['observacion'] ?? null,
                                'PtSubtipo' => $productoData['ptSubtipo'] ?? null,
                                'CantidadObservada' => $cantidadObservada,
                                'TextoObservaciones' => $productoData['textoObservaciones'] ?? null
                            ];
                            
                            $productoModel->registrar($productoInsertData);
                        }
                    }
                    
                    $orden++;
                }
                
                // Commit de la transacción
                $db->commit();
                
                @file_put_contents(__DIR__.'/../../logs/depurar_guardar_externas.txt', "GUARDADO EXITOSO - ID: $recepcionId\n", FILE_APPEND);
                
                echo json_encode([
                    'success' => true, 
                    'id' => $recepcionId, 
                    'nVale' => 'VRE-' . str_pad($nVale, 6, '0', STR_PAD_LEFT),
                    'message' => 'Recepción externa registrada correctamente'
                ]);
                
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar_externas.txt', "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
            echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }
    
    // Modificar recepción externa existente
    public function modificar($datosRecibidos = null) {
        header('Content-Type: application/json');
        
        try {
            // Si no se pasaron datos, leerlos del request
            if ($datosRecibidos === null) {
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                if (stripos($contentType, 'application/json') !== false) {
                    $data = json_decode(file_get_contents('php://input'), true);
                } else {
                    $data = $_POST;
                }
            } else {
                $data = $datosRecibidos;
            }
            
            error_log("[RECEPCIONES_EXT_DIAG] modificar() - Id=" . ($data['Id'] ?? 'NO-ID') . ", guias_count=" . (isset($data['guias']) ? count($data['guias']) : 0));
            
            if (!isset($data['Id']) || empty($data['Id'])) {
                error_log("[RECEPCIONES_EXT_DIAG] modificar() - ERROR: ID no recibido");
                echo json_encode(['success' => false, 'message' => 'ID de recepción no recibido']);
                return;
            }
            
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar_externas.txt', "MODIFICAR - DATOS RECIBIDOS: " . print_r($data, true) . "\n", FILE_APPEND);
            
            // Validar datos obligatorios del vale antes de modificar
            $camposObligatorios = [
                'fecha' => 'Fecha',
                'hora' => 'Hora',
                'turno' => 'Turno',
                'origen' => 'Origen',
                'empresa' => 'Empresa',
                'ruc' => 'RUC',
                'chofer' => 'Chofer',
                'brevete' => 'Brevete'
            ];
            
            $faltantes = [];
            foreach ($camposObligatorios as $campo => $etiqueta) {
                if (empty($data[$campo])) {
                    $faltantes[] = $etiqueta;
                }
            }
            
            if (!empty($faltantes)) {
                echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios: ' . implode(', ', $faltantes)]);
                return;
            }
            
            // Cargar modelos
            require_once __DIR__ . '/../models/RecepcionExterna.php';
            require_once __DIR__ . '/../models/RecepcionExternaGuia.php';
            require_once __DIR__ . '/../models/RecepcionExternaProducto.php';
            
            $recepcionModel = new RecepcionExterna();
            $guiaModel = new RecepcionExternaGuia();
            $productoModel = new RecepcionExternaProducto();
            
            $recepcionId = $data['Id'];
            
            // Verificar límite de modificaciones ANTES de iniciar transacción
            $validacion = $recepcionModel->puedeModificar($recepcionId);
            if (!$validacion['puede']) {
                echo json_encode([
                    'success' => false,
                    'message' => $validacion['mensaje'],
                    'limite_alcanzado' => true
                ]);
                return;
            }
            
            // Iniciar transacción
            $db = Database::getInstance()->getConnection();
            $db->beginTransaction();
            
            try {
                // 1. Actualizar datos del vale
                $recepcionData = [
                    'Fecha' => $data['fecha'],
                    'Hora' => $data['hora'] ?? date('H:i:s'),
                    'Turno' => $data['turno'],
                    'Origen' => $data['origen'],
                    'Recepcionista' => $_SESSION['user']['id'],
                    'Empresa' => $data['empresa'],
                    'RUC' => $data['ruc'],
                    'Chofer' => $data['chofer'],
                    'Brevete' => $data['brevete'],
                    'Comentarios' => isset($data['comentarios']) ? mb_strtoupper($data['comentarios'], 'UTF-8') : null,
                    'Observaciones' => isset($data['observaciones']) ? mb_strtoupper($data['observaciones'], 'UTF-8') : null
                ];
                
                $recepcionModel->actualizarRecepcion($recepcionId, $recepcionData);
                
                // 2. Eliminar todas las guías existentes (CASCADE eliminará los productos)
                $guiaModel->eliminarPorRecepcion($recepcionId);
                
                // 3. Insertar las nuevas guías y productos
                $orden = 1;
                foreach ($data['guias'] as $guiaData) {
                    if (empty($guiaData['numeroGuia'])) {
                        continue;
                    }
                    
                    // Insertar guía
                    $guiaInsertData = [
                        'RecepcionExternaId' => $recepcionId,
                        'NumeroGuia' => isset($guiaData['numeroGuia']) ? strtoupper($guiaData['numeroGuia']) : ($guiaData['NumeroGuia'] ?? null),
                        'NumeroDocRef' => !empty($guiaData['numeroDocRef']) ? $guiaData['numeroDocRef'] : null,
                        'Observacion' => !empty($guiaData['observacion']) ? $guiaData['observacion'] : null,
                        'TextoObservaciones' => !empty($guiaData['textoObservaciones']) ? $guiaData['textoObservaciones'] : null,
                        'CodigoProductoObs' => !empty($guiaData['codigoProductoObs']) ? $guiaData['codigoProductoObs'] : null,
                        'CantidadObservada' => isset($guiaData['cantidadObservada']) ? $guiaData['cantidadObservada'] : 0,
                        'Orden' => $orden
                    ];
                    
                    $guiaId = $guiaModel->registrar($guiaInsertData);
                    
                    // Insertar productos de esta guía
                    if (isset($guiaData['productos']) && is_array($guiaData['productos'])) {
                        foreach ($guiaData['productos'] as $productoData) {
                            $cantidad = $productoData['cantidad'] ?? 0;
                            $cantidadObservada = $productoData['cantidadObservada'] ?? 0;
                            
                            // Saltar solo si no hay ni cantidad GR ni cantidad observada
                            if ((empty($cantidad) || $cantidad <= 0) && (empty($cantidadObservada) || $cantidadObservada <= 0)) {
                                continue;
                            }
                            
                            $productoInsertData = [
                                'GuiaId' => $guiaId,
                                'CodigoProducto' => $productoData['codigo'] ?? '',
                                'DescripcionProducto' => $productoData['descripcion'] ?? '',
                                'UnidadMedida' => $productoData['unidadMedida'] ?? '',
                                'Cantidad' => $cantidad,
                                'ColumnaProducto' => $productoData['columna'] ?? 1,
                                'Observacion' => $productoData['observacion'] ?? null,
                                'PtSubtipo' => $productoData['ptSubtipo'] ?? null,
                                'CantidadObservada' => $cantidadObservada,
                                'TextoObservaciones' => $productoData['textoObservaciones'] ?? null
                            ];
                            
                            $productoModel->registrar($productoInsertData);
                        }
                    }
                    
                    $orden++;
                }
                
                // Commit de la transacción
                $db->commit();

                $usuarioId = $_SESSION['user']['id'] ?? null;
                try { $recepcionModel->registrarModificacion($recepcionId, $usuarioId, $_SERVER['REMOTE_ADDR'] ?? null); } catch (Exception $eLog) {
                    error_log('Error al registrar modificacion externa: ' . $eLog->getMessage());
                }
                
                @file_put_contents(__DIR__.'/../../logs/depurar_guardar_externas.txt', "MODIFICACIÓN EXITOSA - ID: $recepcionId\n", FILE_APPEND);
                
                // Obtener el número de vale para devolverlo
                $recepcion = $recepcionModel->getById($recepcionId);
                $nVale = 'VRE-' . str_pad($recepcion['NVale'], 6, '0', STR_PAD_LEFT);
                
                // Verificar estado actual de modificaciones para informar al usuario
                $validacion = $recepcionModel->puedeModificar($recepcionId);
                
                echo json_encode([
                    'success' => true,
                    'id' => $recepcionId,
                    'nVale' => $nVale,
                    'message' => 'Recepción externa actualizada correctamente',
                    'modificaciones_realizadas' => $validacion['modificaciones'],
                    'modificaciones_restantes' => $validacion['restantes']
                ]);
                
            } catch (Exception $e) {
                $db->rollBack();
                error_log("[RECEPCIONES_EXT_DIAG] modificar() - TRANSACCIÓN FALLÓ (rollback): " . $e->getMessage());
                throw $e;
            }
            
        } catch (Exception $e) {
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar_externas.txt', "ERROR MODIFICAR: " . $e->getMessage() . "\n", FILE_APPEND);
            error_log("[RECEPCIONES_EXT_DIAG] modificar() - ERROR GENERAL: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al modificar: ' . $e->getMessage()]);
        }
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
                $id = $_GET['id'] ?? $_GET['Id'] ?? null;
            }
            
            if (!$id) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID no proporcionado'
                ]);
                return;
            }
            
            require_once __DIR__ . '/../models/RecepcionExterna.php';
            $recepcionModel = new RecepcionExterna();
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
    
    // Obtener productos para los selects de guías
    public function obtenerProductos() {
        header('Content-Type: application/json');
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query('SELECT Id, Codigo, Producto, UnidadMedida, Abreviatura FROM productos ORDER BY Producto ASC');
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'productos' => $productos]);
        } catch (Exception $e) {
            error_log("Error al obtener productos: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    // Obtener observaciones para los selects de guías
    public function obtenerObservaciones() {
        header('Content-Type: application/json');
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query('SELECT Id, Item, Observaciones FROM observaciones ORDER BY Item ASC');
            $observaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'observaciones' => $observaciones]);
        } catch (Exception $e) {
            error_log("Error al obtener observaciones: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    // Vista para edición de vales existentes
    public function edicion() {
        try {
            // Cargar turnos para el select
            $turnoModel = new Turno();
            $turnos = $turnoModel->getAll();
            
            // Cargar orígenes
            $origenes = $this->obtenerOrigenes();
            
            // Cargar transportistas (empresa y RUC)
            $transportistas = $this->obtenerTransportistas();
            
            // Cargar choferes
            $choferes = $this->obtenerChoferes();
            
            // Hora actual del sistema
            $horaActual = date('H:i');
            
            // Datos a pasar a la vista
            $datos = [
                'titulo' => 'Edición de Vale - Recepciones Externas',
                'turnos' => $turnos,
                'origenes' => $origenes,
                'transportistas' => $transportistas,
                'choferes' => $choferes,
                'horaActual' => $horaActual,
                'fechaHoy' => date('Y-m-d')
            ];
            // Cargar series (para select de serie de guías)
            try {
                $db = Database::getInstance()->getConnection();
                $stmt = $db->query('SELECT Id, Serie FROM series ORDER BY Serie ASC');
                $series = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $series = [];
            }
            $datos['series'] = $series;
            
            $this->view('recepcionesexternas/edicion', $datos);
        } catch (Exception $e) {
            error_log("Error en RecepcionesExternasController::edicion - " . $e->getMessage());
            echo "Error al cargar la página: " . htmlspecialchars($e->getMessage());
        }
    }
    
    // Verificar si una guía (serie + correlativo) ya existe en la BD
    // Retorna si existe, si bloquea el registro y el detalle de observaciones previas.
    // Lógica de bloqueo:
    //   - Sin observación (recibida plena) o con obs R (regularizada) → bloqueado = true
    //   - Con obs P / LE / DE / A (pendientes/parciales)              → bloqueado = false (permite registrar, muestra aviso)
    public function verificarGuia() {
        header('Content-Type: application/json');
        try {
            $serie       = isset($_GET['serie'])       ? strtoupper(trim($_GET['serie']))  : '';
            $correlativo = isset($_GET['correlativo'])  ? trim($_GET['correlativo'])        : '';

            if ($serie === '' || $correlativo === '') {
                echo json_encode(['success' => true, 'existe' => false]);
                return;
            }

            $correlativoNum = preg_replace('/\D/', '', $correlativo);
            $correlativoPad = str_pad($correlativoNum, 7, '0', STR_PAD_LEFT);
            $numeroGuia     = strtoupper($serie . '-' . $correlativoPad);

            $db = Database::getInstance()->getConnection();

            // Traer todos los registros históricos de esta guía con contexto de obs, producto y vale
            // NOTA: Se cambió el JOIN a productos para que NO filtre por CodigoProductoObs,
            // sino que traiga TODOS los productos con observaciones (Observacion IS NOT NULL AND CantidadObservada > 0).
            // Luego se agrupan por guía en PHP para generar el sub-array 'productos'.
            $stmt = $db->prepare(
                "SELECT
                    reg.Id               AS regId,
                    reg.Observacion      AS obsId,
                    obs.Item             AS obsItem,
                    reg.CodigoProductoObs,
                    reg.CantidadObservada,
                    reg.TextoObservaciones,
                    re.Fecha,
                    tu.Turno             AS TurnoNombre,
                    rep.CodigoProducto   AS prodCodigo,
                    rep.Cantidad         AS prodCantidad,
                    rep.Observacion      AS prodObsId,
                    obsProd.Item         AS prodObsItem,
                    rep.CantidadObservada AS prodCantObs,
                    rep.TextoObservaciones AS prodTextoObs
                FROM recepciones_externas_guias reg
                INNER JOIN recepciones_externas re ON reg.RecepcionExternaId = re.Id
                LEFT JOIN  observaciones obs        ON reg.Observacion = obs.Id
                LEFT JOIN  turnos tu               ON re.Turno = tu.Id
                LEFT JOIN  recepciones_externas_productos rep
                           ON rep.GuiaId = reg.Id
                           AND rep.Observacion IS NOT NULL
                           AND rep.CantidadObservada > 0
                LEFT JOIN  observaciones obsProd    ON rep.Observacion = obsProd.Id
                WHERE UPPER(reg.NumeroGuia) = ?
                ORDER BY re.Fecha DESC, re.Hora DESC, reg.Id ASC, rep.ColumnaProducto ASC"
            );
            $stmt->execute([$numeroGuia]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                echo json_encode(['success' => true, 'existe' => false, 'numeroGuia' => $numeroGuia]);
                return;
            }

            // ── NUEVA LÓGICA: bloqueo a nivel de PRODUCTO ────────────────────────
            // Se considera un producto "cerrado" cuando la suma de sus regularizaciones (R)
            // es >= la suma de sus cantidades observadas pendientes (P/LE/DE/A).
            // La guía se bloquea SOLO cuando TODOS los productos están cerrados.
            $OBS_PERMITIDAS = ['P', 'LE', 'DE', 'A', 'PT'];
            $grupos     = []; // Agrupar por regId para consolidar productos por guía
            $prodBalance = []; // ['codigo' => ['pendiente'=>float, 'regularizado'=>float]]

            foreach ($rows as $row) {
                $regId = $row['regId'];

                // Inicializar grupo si no existe
                if (!isset($grupos[$regId])) {
                    // Formatear fecha DD/MM/YYYY
                    $fechaFmt = '';
                    if (!empty($row['Fecha'])) {
                        $partes = explode('-', $row['Fecha']);
                        $fechaFmt = count($partes) === 3
                            ? $partes[2] . '/' . $partes[1] . '/' . $partes[0]
                            : $row['Fecha'];
                    }

                    $obsItem = strtoupper(trim((string)($row['obsItem'] ?? '')));
                    $grupos[$regId] = [
                        'obsItem'            => $obsItem,
                        'codigoProductoObs'  => $row['CodigoProductoObs']   ?? '',
                        'cantObs'            => (float)($row['CantidadObservada'] ?? 0),
                        'textoObservaciones' => $row['TextoObservaciones']   ?? '',
                        'fecha'              => $fechaFmt,
                        'turno'              => $row['TurnoNombre']          ?? '',
                        'productos'          => []
                    ];
                }

                // Agregar producto individual (si tiene datos)
                if (!empty($row['prodCodigo'])) {
                    $cod = $row['prodCodigo'];
                    $cantObs = (float)($row['prodCantObs'] ?? 0);
                    $prodObsItem = strtoupper(trim((string)($row['prodObsItem'] ?? '')));

                    $grupos[$regId]['productos'][] = [
                        'codigo'   => $cod,
                        'cantidad' => (float)($row['prodCantidad'] ?? 0),
                        'obsItem'  => $prodObsItem,
                        'cantObs'  => $cantObs
                    ];

                    // Acumular en el balance por producto
                    if (!isset($prodBalance[$cod])) {
                        $prodBalance[$cod] = ['pendiente' => 0, 'regularizado' => 0];
                    }
                    if ($prodObsItem === 'R') {
                        $prodBalance[$cod]['regularizado'] += $cantObs;
                    } elseif (in_array($prodObsItem, $OBS_PERMITIDAS)) {
                        $prodBalance[$cod]['pendiente'] += $cantObs;
                    }
                }
            }

            // Determinar si TODOS los productos están cerrados
            $todosCerrados = true;
            if (!empty($prodBalance)) {
                foreach ($prodBalance as $cod => $bal) {
                    if ($bal['regularizado'] < $bal['pendiente']) {
                        $todosCerrados = false;
                        break;
                    }
                }
            } else {
                // No hay productos con obs en la BD (guía sin observaciones a nivel producto)
                // Usar guía-level obsItem como fallback
                $todosCerrados = false;
                foreach ($rows as $r) {
                    $obsItem = strtoupper(trim((string)($r['obsItem'] ?? '')));
                    // Sin obs (recibida plena) o R (regularizada) → cerrado
                    if ($obsItem === '' || $obsItem === 'R') {
                        $todosCerrados = true;
                    } else {
                        $todosCerrados = false;
                        break;
                    }
                }
            }

            // Convertir grupos a array indexado
            $registros = array_values($grupos);

            echo json_encode([
                'success'   => true,
                'existe'    => true,
                'bloqueado' => $todosCerrados,
                'numeroGuia'=> $numeroGuia,
                'registros' => $registros,
            ]);
        } catch (Exception $e) {
            error_log('RecepcionesExternasController::verificarGuia - ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // Buscar vales existentes (para el modal de búsqueda)
    public function buscarVales() {
        header('Content-Type: application/json');
        try {
            $db = Database::getInstance()->getConnection();
            
            // Construir query con filtros opcionales
            $sql = "SELECT re.Id, re.NVale, re.Fecha, re.Hora, 
                           o.Origen, t.Empresa, c.ApellidosNombres as Chofer,
                           tu.Turno
                    FROM recepciones_externas re
                    LEFT JOIN origen o ON re.Origen = o.Id
                    LEFT JOIN transportistas t ON re.Empresa = t.Id
                    LEFT JOIN choferes c ON re.Chofer = c.Id
                    LEFT JOIN turnos tu ON re.Turno = tu.Id
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
                    $sql .= " AND re.NVale = :nvale";
                    $params[':nvale'] = $nvale;
                }
            }
            if (!empty($_GET['fecha'])) {
                $sql .= " AND re.Fecha = :fecha";
                $params[':fecha'] = $_GET['fecha'];
            }
            
            $sql .= " ORDER BY re.Id DESC LIMIT 50";
            
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
            $sql = "SELECT re.*, o.Origen as OrigenNombre, t.Empresa as EmpresaNombre, t.RUC, 
                           c.ApellidosNombres as ChoferNombre, c.Brevete,
                           tu.Turno as TurnoNombre,
                           re.Empresa as EmpresaId, re.Chofer as ChoferId
                    FROM recepciones_externas re
                    LEFT JOIN origen o ON re.Origen = o.Id
                    LEFT JOIN transportistas t ON re.Empresa = t.Id
                    LEFT JOIN choferes c ON re.Chofer = c.Id
                    LEFT JOIN turnos tu ON re.Turno = tu.Id
                    WHERE re.Id = :id";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $valeId]);
            $vale = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$vale) {
                error_log("Vale no encontrado con ID: " . $valeId);
                echo json_encode(['success' => false, 'message' => 'Vale no encontrado']);
                return;
            }
            
            error_log("Vale encontrado: " . print_r($vale, true));
            
            // Obtener guías del vale
            $sqlGuias = "SELECT reg.*
                         FROM recepciones_externas_guias reg
                         WHERE reg.RecepcionExternaId = :id
                         ORDER BY reg.Orden, reg.Id";
            
            $stmtGuias = $db->prepare($sqlGuias);
            $stmtGuias->execute([':id' => $valeId]);
            $guias = $stmtGuias->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Guías encontradas: " . count($guias));
            
            // Para cada guía, obtener sus productos
            foreach ($guias as &$guia) {
                $sqlProductos = "SELECT rep.*
                                FROM recepciones_externas_productos rep
                                WHERE rep.GuiaId = :idGuia
                                ORDER BY rep.ColumnaProducto, rep.Id";
                
                $stmtProductos = $db->prepare($sqlProductos);
                $stmtProductos->execute([':idGuia' => $guia['Id']]);
                $guia['productos'] = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);
                
                error_log("Guia ID " . $guia['Id'] . " tiene " . count($guia['productos']) . " productos");
                if (count($guia['productos']) > 0) {
                    error_log("Primer producto: " . print_r($guia['productos'][0], true));
                }
            }
            
            $vale['guias'] = $guias;
            
            error_log("Vale completo con guías y productos preparado");
            echo json_encode(['success' => true, 'vale' => $vale]);
        } catch (Exception $e) {
            error_log("Error al obtener vale: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            echo json_encode(['success' => false, 'error' => $e->getMessage(), 'message' => 'Error al cargar el vale']);
        }
    }

    // Anular vale de recepción externa
    public function anularVale() {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['id'])) {
                echo json_encode(['success' => false, 'message' => 'ID de vale no proporcionado']);
                return;
            }
            
            if (empty($input['motivo'])) {
                echo json_encode(['success' => false, 'message' => 'Debe proporcionar un motivo de anulación']);
                return;
            }
            
            $valeId = $input['id'];
            $motivo = trim($input['motivo']);
            $usuarioId = $_SESSION['user']['id'] ?? null;
            
            if (!$usuarioId) {
                echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
                return;
            }
            
            $db = Database::getInstance()->getConnection();
            
            // Verificar que el vale existe y no está anulado
            $sqlCheck = "SELECT Id, estado FROM recepciones_externas WHERE Id = :id";
            $stmtCheck = $db->prepare($sqlCheck);
            $stmtCheck->execute([':id' => $valeId]);
            $vale = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            if (!$vale) {
                echo json_encode(['success' => false, 'message' => 'Vale no encontrado']);
                return;
            }
            
            if ($vale['estado'] === 'anulado') {
                echo json_encode(['success' => false, 'message' => 'El vale ya está anulado']);
                return;
            }
            
            // Anular el vale
            $sqlAnular = "UPDATE recepciones_externas 
                         SET estado = 'anulado',
                             anulado_por = :usuario_id,
                             anulado_en = CURRENT_TIMESTAMP,
                             motivo_anulacion = :motivo
                         WHERE Id = :id";
            
            $stmtAnular = $db->prepare($sqlAnular);
            $resultado = $stmtAnular->execute([
                ':usuario_id' => $usuarioId,
                ':motivo' => $motivo,
                ':id' => $valeId
            ]);
            
            if ($resultado) {
                error_log("Vale ID $valeId anulado por usuario ID $usuarioId. Motivo: $motivo");
                echo json_encode(['success' => true, 'message' => 'Vale anulado exitosamente']);
            } else {
                error_log("Error al anular vale ID $valeId");
                echo json_encode(['success' => false, 'message' => 'Error al ejecutar la anulación']);
            }
            
        } catch (Exception $e) {
            error_log("Error al anular vale: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            echo json_encode(['success' => false, 'error' => $e->getMessage(), 'message' => 'Error al anular el vale']);
        }
    }
}
