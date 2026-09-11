<?php
require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/RecepcionExternaGuia.php';
require_once __DIR__ . '/RecepcionExternaProducto.php';

class RecepcionExterna extends Model {
    
    /**
     * Obtener el siguiente número correlativo de vale
     */
    public function obtenerSiguienteVale() {
        try {
            // Manejar casos donde NVale puede incluir prefijo como 'VRE-000123'
            // Usamos REPLACE para eliminar el prefijo conocido y luego casteamos a entero.
            $sql = "SELECT COALESCE(MAX(CAST(REPLACE(NVale, 'VRE-', '') AS UNSIGNED)), 0) + 1 AS siguiente FROM recepciones_externas";
            $stmt = $this->db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['siguiente'] ?? 1;
        } catch (Exception $e) {
            error_log('Error obteniendo siguiente vale: ' . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Registrar una nueva recepción externa (solo datos del vale)
     * Retorna el ID insertado
     */
    public function registrarRecepcion($data) {
        try {
                $sql = "INSERT INTO recepciones_externas
                    (NVale, Fecha, Hora, Turno, Origen, Recepcionista, Empresa, RUC, Chofer, Brevete, Comentarios, Observaciones, creado_por, creado_en, estado, ip_creacion)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'activo', ?)";
             
            $stmt = $this->db->prepare($sql);
            $resultado = $stmt->execute([
                $data['NVale'],
                $data['Fecha'],
                $data['Hora'],
                $data['Turno'],
                $data['Origen'],
                $data['Recepcionista'] ?? null,
                $data['Empresa'],
                $data['RUC'],
                $data['Chofer'],
                $data['Brevete'],
                $data['Comentarios'] ?? null,
                $data['Observaciones'] ?? null,
                $data['creado_por'],
                $data['ip_creacion'] ?? null
            ]);
            
            if ($resultado) {
                return $this->db->lastInsertId();
            }
            
            return false;
        } catch (Exception $e) {
            error_log('Error registrando recepción externa: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Obtener una recepción por ID con sus guías y productos
     */
    public function getById($id) {
        try {
            // Obtener datos principales del vale
            $sql = "SELECT re.*, 
                    t.Turno as TurnoTexto, 
                    o.Origen as OrigenTexto,
                    tr.Empresa as EmpresaTexto
                FROM recepciones_externas re
                LEFT JOIN turnos t ON re.Turno = t.Id
                LEFT JOIN origen o ON re.Origen = o.Id
                LEFT JOIN transportistas tr ON re.Empresa = tr.Id
                WHERE re.Id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $recepcion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$recepcion) {
                return null;
            }
            
            // Obtener guías asociadas
            $sqlGuias = "SELECT g.*, obs.Observaciones as ObservacionTexto
                         FROM recepciones_externas_guias g
                         LEFT JOIN observaciones obs ON g.Observacion = obs.Id
                         WHERE g.RecepcionExternaId = ?
                         ORDER BY g.Orden ASC";
            $stmtGuias = $this->db->prepare($sqlGuias);
            $stmtGuias->execute([$id]);
            $guias = $stmtGuias->fetchAll(PDO::FETCH_ASSOC);
            
            // Para cada guía, obtener sus productos
            foreach ($guias as &$guia) {
                $sqlProds = "SELECT * FROM recepciones_externas_productos 
                            WHERE GuiaId = ? 
                            ORDER BY ColumnaProducto ASC";
                $stmtProds = $this->db->prepare($sqlProds);
                $stmtProds->execute([$guia['Id']]);
                $guia['Productos'] = $stmtProds->fetchAll(PDO::FETCH_ASSOC);
            }
            
            $recepcion['Guias'] = $guias;
            
            return $recepcion;
        } catch (Exception $e) {
            error_log('Error obteniendo recepción externa por ID: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Actualizar una recepción externa
     */
    public function actualizarRecepcion($id, $data) {
        try {
            $sql = "UPDATE recepciones_externas SET
                    Fecha = ?,
                    Hora = ?,
                    Turno = ?,
                    Origen = ?,
                    Recepcionista = ?,
                    Empresa = ?,
                    RUC = ?,
                    Chofer = ?,
                    Brevete = ?,
                    Comentarios = ?,
                    Observaciones = ?,
                    modificaciones_count = COALESCE(modificaciones_count, 0) + 1
                    WHERE Id = ?";
             
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['Fecha'],
                $data['Hora'],
                $data['Turno'],
                $data['Origen'],
                $data['Recepcionista'] ?? null,
                $data['Empresa'],
                $data['RUC'],
                $data['Chofer'],
                $data['Brevete'],
                $data['Comentarios'] ?? null,
                $data['Observaciones'] ?? null,
                $id
            ]);
        } catch (Exception $e) {
            error_log('Error actualizando recepción externa: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Anular una recepción externa
     */
    public function anular($id, $anulado_por, $motivo) {
        try {
            $sql = "UPDATE recepciones_externas SET 
                    estado = 'anulado',
                    anulado_por = ?,
                    anulado_en = NOW(),
                    motivo_anulacion = ?
                    WHERE Id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$anulado_por, $motivo, $id]);
        } catch (Exception $e) {
            error_log('Error anulando recepción externa: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Obtener todas las recepciones (con filtros opcionales)
     */
    public function getAll($filtro = '', $limit = 50, $offset = 0) {
        try {
            $sql = "SELECT re.*, 
                    t.Turno as TurnoTexto,
                    o.Origen as OrigenTexto,
                    tr.Empresa as EmpresaTexto
                FROM recepciones_externas re
                LEFT JOIN turnos t ON re.Turno = t.Id
                LEFT JOIN origen o ON re.Origen = o.Id
                LEFT JOIN transportistas tr ON re.Empresa = tr.Id
                WHERE 1=1";
            
            $params = [];
            
            if (!empty($filtro)) {
                $sql .= " AND (re.NVale LIKE ? OR tr.Empresa LIKE ? OR o.Origen LIKE ?)";
                $filtroLike = "%{$filtro}%";
                $params = [$filtroLike, $filtroLike, $filtroLike];
            }
            
            $sql .= " ORDER BY re.Id DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error obteniendo todas las recepciones externas: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener recepciones para reporte con filtros, paginación y jerarquía completa
     */
    public function getReporte($filtros = [], $limit = 10, $offset = 0) {
        try {
            // Construir SQL con filtros (incluir IDs para edición)
            $sql = "SELECT re.Id, re.NVale, re.Fecha, re.Hora,
                    re.Turno as TurnoId,
                    re.Origen as OrigenId,
                    re.Recepcionista as RecepcionistaId,
                    re.Empresa as EmpresaId,
                    re.Chofer as ChoferId,
                    re.Observaciones,
                    re.Comentarios,
                    t.Turno,
                    o.Origen,
                    COALESCE(u.NombresApellidos, '') as Recepcionista,
                    tr.RUC,
                    tr.Empresa,
                    c.ApellidosNombres as Chofer,
                    c.Brevete,
                    UPPER(re.estado) as Estado,
                    UPPER(ELT(DAYOFWEEK(re.Fecha), 'Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado')) as Dia,
                    WEEK(re.Fecha) as Semana,
                    UPPER(ELT(MONTH(re.Fecha), 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre')) as Mes,
                    COALESCE(re.NLiquidacion, '') as NLiquidacion,
                    COALESCE(re.TipoRecepcion, 'RECEPCIÓN EXTERNA') as TipoRecepcion
                FROM recepciones_externas re
                LEFT JOIN turnos t ON re.Turno = t.Id
                LEFT JOIN origen o ON re.Origen = o.Id
                LEFT JOIN usuarios u ON re.Recepcionista = u.Id
                LEFT JOIN transportistas tr ON re.Empresa = tr.Id
                LEFT JOIN choferes c ON re.Chofer = c.Id
                WHERE 1=1";
            
            $params = [];
            
            // Aplicar filtros
            if (!empty($filtros['NVale'])) {
                $sql .= " AND re.NVale LIKE ?";
                $params[] = "%{$filtros['NVale']}%";
            }
            if (!empty($filtros['Fecha'])) {
                $sql .= " AND re.Fecha = ?";
                $params[] = $filtros['Fecha'];
            }
            // Filtrado por rango de fechas (desde / hasta)
            if (!empty($filtros['fechaDesde'])) {
                $sql .= " AND re.Fecha >= ?";
                $params[] = $filtros['fechaDesde'];
            }
            if (!empty($filtros['fechaHasta'])) {
                $sql .= " AND re.Fecha <= ?";
                $params[] = $filtros['fechaHasta'];
            }
            if (!empty($filtros['Hora'])) {
                $sql .= " AND re.Hora LIKE ?";
                $params[] = "%{$filtros['Hora']}%";
            }
            if (!empty($filtros['Turno'])) {
                $sql .= " AND t.Turno LIKE ?";
                $params[] = "%{$filtros['Turno']}%";
            }
            if (!empty($filtros['Origen'])) {
                $sql .= " AND o.Origen LIKE ?";
                $params[] = "%{$filtros['Origen']}%";
            }
            if (!empty($filtros['Recepcionista'])) {
                $sql .= " AND u.NombresApellidos LIKE ?";
                $params[] = "%{$filtros['Recepcionista']}%";
            }
            if (!empty($filtros['Empresa'])) {
                $sql .= " AND tr.Empresa LIKE ?";
                $params[] = "%{$filtros['Empresa']}%";
            }
            if (!empty($filtros['RUC'])) {
                $sql .= " AND tr.RUC LIKE ?";
                $params[] = "%{$filtros['RUC']}%";
            }
            if (!empty($filtros['Chofer'])) {
                $sql .= " AND c.ApellidosNombres LIKE ?";
                $params[] = "%{$filtros['Chofer']}%";
            }
            if (!empty($filtros['Brevete'])) {
                $sql .= " AND c.Brevete LIKE ?";
                $params[] = "%{$filtros['Brevete']}%";
            }
            if (!empty($filtros['Estado'])) {
                $sql .= " AND re.estado LIKE ?";
                $params[] = "%{$filtros['Estado']}%";
            }
            
            // Contar total de registros
            $sqlCount = preg_replace('/^SELECT.*FROM/s', 'SELECT COUNT(*) as total FROM', $sql);
            $stmtCount = $this->db->prepare($sqlCount);
            $stmtCount->execute($params);
            $total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
            $totalPaginas = ceil($total / $limit);
            
            // Obtener datos con paginación
            $sql .= " ORDER BY re.Id DESC LIMIT ? OFFSET ?";
            $params[] = (int)$limit;
            $params[] = (int)$offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $recepciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // OPTIMIZACIÓN: Cargar guías y productos en lote (NO N+1)
            // En lugar de hacer 1 query por recepción, hacemos 2 queries totales
            if (!empty($recepciones)) {
                $ids = array_column($recepciones, 'Id');
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                
                // 1. Obtener TODAS las guías de TODAS las recepciones en UNA sola query
                $sqlGuias = "SELECT g.Id, g.RecepcionExternaId, g.NumeroGuia, g.NumeroDocRef, g.Observacion,
                             obs.Observaciones as ObservacionTexto,
                             g.CodigoProductoObs, g.CantidadObservada, g.TextoObservaciones,
                             g.Orden
                             FROM recepciones_externas_guias g
                             LEFT JOIN observaciones obs ON g.Observacion = obs.Id
                             WHERE g.RecepcionExternaId IN ($placeholders)
                             ORDER BY g.RecepcionExternaId, g.Orden ASC";
                $stmtGuias = $this->db->prepare($sqlGuias);
                $stmtGuias->execute($ids);
                $guias = $stmtGuias->fetchAll(PDO::FETCH_ASSOC);
                
                // 2. Obtener TODOS los productos de TODAS las guías en UNA sola query
                $guiaIds = array_column($guias, 'Id');
                $guiasPorRecepcion = [];
                foreach ($guias as $guia) {
                    $rid = $guia['RecepcionExternaId'];
                    unset($guia['RecepcionExternaId']);
                    $guiasPorRecepcion[$rid][] = $guia;
                }
                
                if (!empty($guiaIds)) {
                    $guiaPlaceholders = implode(',', array_fill(0, count($guiaIds), '?'));
                    $sqlProds = "SELECT p.Id, p.GuiaId, p.CodigoProducto, p.DescripcionProducto, p.Cantidad, p.UnidadMedida, p.CantidadObservada, p.Observacion, p.PtSubtipo, p.TextoObservaciones, p.Total
                                FROM recepciones_externas_productos p
                                WHERE p.GuiaId IN ($guiaPlaceholders)
                                ORDER BY p.GuiaId, p.ColumnaProducto ASC";
                    $stmtProds = $this->db->prepare($sqlProds);
                    $stmtProds->execute($guiaIds);
                    $productos = $stmtProds->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Agrupar productos por GuiaId
                    $productosPorGuia = [];
                    foreach ($productos as $prod) {
                        $gid = $prod['GuiaId'];
                        unset($prod['GuiaId']);
                        $productosPorGuia[$gid][] = $prod;
                    }
                    
                    // Asignar productos a cada guía
                    foreach ($guiasPorRecepcion as &$guiasDeRecepcion) {
                        foreach ($guiasDeRecepcion as &$guia) {
                            $guia['Productos'] = $productosPorGuia[$guia['Id']] ?? [];
                        }
                        unset($guia);
                    }
                    unset($guiasDeRecepcion);
                } else {
                    // Sin guías, asignar array vacío a cada grupo
                    foreach ($guiasPorRecepcion as &$guiasDeRecepcion) {
                        foreach ($guiasDeRecepcion as &$guia) {
                            $guia['Productos'] = [];
                        }
                        unset($guia);
                    }
                    unset($guiasDeRecepcion);
                }
                
                // Asignar guías a cada recepción
                foreach ($recepciones as &$recepcion) {
                    $recepcion['Guias'] = $guiasPorRecepcion[$recepcion['Id']] ?? [];
                }
                unset($recepcion);
            }
            
            return [
                'data' => $recepciones,
                'totalPaginas' => $totalPaginas,
                'total' => (int)$total
            ];
        } catch (Exception $e) {
            error_log('Error en getReporte: ' . $e->getMessage());
            return ['data' => [], 'totalPaginas' => 1, 'total' => 0];
        }
    }

    /**
     * Cuenta total de filas planas (vale × guías × productos)
     */
    public function getTotalFlattenedCount($filtros = []) {
        $where = []; $params = [];
        if (!empty($filtros['NVale'])) { $where[] = 're.NVale LIKE ?'; $params[] = '%' . $filtros['NVale'] . '%'; }
        if (!empty($filtros['Fecha'])) { $where[] = 're.Fecha = ?'; $params[] = $filtros['Fecha']; }
        $sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT COALESCE(SUM(COALESCE(num_filas, 1)), 0) as total FROM (
            SELECT re.Id, COALESCE(g.total_filas, 1) as num_filas
            FROM recepciones_externas re
            LEFT JOIN (
                SELECT g.RecepcionExternaId, COALESCE(SUM(COALESCE(p.pc, 1)), 1) as total_filas
                FROM recepciones_externas_guias g
                LEFT JOIN (SELECT GuiaId, COUNT(*) as pc FROM recepciones_externas_productos GROUP BY GuiaId) p ON g.Id = p.GuiaId
                GROUP BY g.RecepcionExternaId
            ) g ON re.Id = g.RecepcionExternaId
            $sqlWhere
        ) t";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return (int)$stmt->fetchColumn(); }
        catch (Exception $e) { return 0; }
    }

    /**
     * Obtener guías con sus productos para una recepción
     */
    private function obtenerGuiasConProductos($recepcionId) {
        try {
            error_log("[RecepcionExterna] Buscando guías para recepción ID: " . $recepcionId);
            
            $sqlGuias = "SELECT g.Id, g.NumeroGuia, g.NumeroDocRef, g.Observacion, obs.Observaciones as ObservacionTexto,
                         g.CodigoProductoObs, g.CantidadObservada, g.TextoObservaciones
                         FROM recepciones_externas_guias g
                         LEFT JOIN observaciones obs ON g.Observacion = obs.Id
                         WHERE g.RecepcionExternaId = ?
                         ORDER BY g.Orden ASC";
            $stmtGuias = $this->db->prepare($sqlGuias);
            $stmtGuias->execute([$recepcionId]);
            $guias = $stmtGuias->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("[RecepcionExterna] Guías encontradas: " . count($guias));
            
            // Para cada guía, obtener sus productos
            foreach ($guias as &$guia) {
                error_log("[RecepcionExterna] Buscando productos para guía ID: " . $guia['Id']);
                
                $sqlProds = "SELECT p.Id, p.CodigoProducto, p.DescripcionProducto, p.Cantidad, p.UnidadMedida, p.Observacion, p.PtSubtipo, p.CantidadObservada, p.TextoObservaciones
                            FROM recepciones_externas_productos p
                            WHERE p.GuiaId = ?
                            ORDER BY p.ColumnaProducto ASC";
                $stmtProds = $this->db->prepare($sqlProds);
                $stmtProds->execute([$guia['Id']]);
                $guia['Productos'] = $stmtProds->fetchAll(PDO::FETCH_ASSOC);
                
                error_log("[RecepcionExterna] Productos encontrados: " . count($guia['Productos']));
            }
            
            return $guias;
        } catch (Exception $e) {
            error_log('Error obteniendo guías con productos: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Actualizar guías y productos de una recepción (DELETE + INSERT)
     */
    public function actualizarGuiasYProductos($recepcionId, $guias) {
        try {
            error_log("[RecepcionExterna] Iniciando actualización de guías para recepción: " . $recepcionId);
            $this->db->beginTransaction();
            
            // Eliminar guías y productos existentes (CASCADE eliminará productos automáticamente)
            error_log("[RecepcionExterna] Eliminando guías existentes...");
            $sqlDelete = "DELETE FROM recepciones_externas_guias WHERE RecepcionExternaId = ?";
            $stmtDelete = $this->db->prepare($sqlDelete);
            $stmtDelete->execute([$recepcionId]);
            error_log("[RecepcionExterna] Guías existentes eliminadas");
            
            // Insertar nuevas guías y productos
            error_log("[RecepcionExterna] Creando instancias de modelos...");
            try {
                $guiaModel = new RecepcionExternaGuia();
                error_log("[RecepcionExterna] Modelo RecepcionExternaGuia creado");
                $productoModel = new RecepcionExternaProducto();
                error_log("[RecepcionExterna] Modelo RecepcionExternaProducto creado");
            } catch (Exception $e) {
                error_log("[RecepcionExterna] Error creando modelos: " . $e->getMessage());
                throw $e;
            }
            
            error_log("[RecepcionExterna] Procesando " . count($guias) . " guías");
            foreach ($guias as $orden => $guiaData) {
                error_log("[RecepcionExterna] Procesando guía " . ($orden + 1) . ": " . json_encode($guiaData));
                
                $guiaId = $guiaModel->registrar([
                    'RecepcionExternaId' => $recepcionId,
                    'NumeroGuia' => $guiaData['NumeroGuia'],
                    'Observacion' => $guiaData['Observacion'] ?? null,
                    'CodigoProductoObs' => $guiaData['CodigoProductoObs'] ?? null,
                    'CantidadObservada' => $guiaData['CantidadObservada'] ?? 0,
                    'Orden' => $orden + 1
                ]);
                
                error_log("[RecepcionExterna] Guía registrada con ID: " . $guiaId);
                
                if ($guiaId && isset($guiaData['Productos']) && is_array($guiaData['Productos'])) {
                    error_log("[RecepcionExterna] Procesando " . count($guiaData['Productos']) . " productos para la guía");
                    foreach ($guiaData['Productos'] as $columna => $producto) {
                        $productoModel->registrar([
                            'GuiaId' => $guiaId,
                            'CodigoProducto' => $producto['CodigoProducto'],
                            'DescripcionProducto' => $producto['DescripcionProducto'] ?? '',
                            'Cantidad' => $producto['Cantidad'],
                            'UnidadMedida' => $producto['UnidadMedida'] ?? '',
                            'ColumnaProducto' => $columna + 1
                        ]);
                    }
                    error_log("[RecepcionExterna] Productos registrados para la guía");
                }
            }
            
            $this->db->commit();
            error_log("[RecepcionExterna] Actualización completada exitosamente");
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('[RecepcionExterna] Error actualizando guías y productos: ' . $e->getMessage());
            error_log('[RecepcionExterna] Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
    
    /**
     * Registrar en el historial quién hizo la modificación
     */
    public function registrarModificacion($recepcionId, $usuarioId, $ip = null) {
        // Obtener el valor actual de modificaciones_count (ya fue incrementado por actualizarRecepcion())
        $sql = "SELECT COALESCE(modificaciones_count, 0) as modificaciones_count FROM recepciones_externas WHERE Id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$recepcionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $nModificacion = $row ? (int)$row['modificaciones_count'] : 0;

        $sql = "INSERT INTO recepciones_externas_modificaciones (RecepcionId, UsuarioId, NModificacion, IpAddress) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$recepcionId, $usuarioId, $nModificacion, $ip]);
    }

    /**
     * Verificar si una recepción puede ser modificada
     * Ahora considera la configuración por usuario:
     * - permite_multiples = 1 → sin restricciones
     * - ventana_horas definido → validar ventana de tiempo desde creado_en
     * - max_modificaciones personalizado o default 1
     */
    public function puedeModificar($id) {
        try {
            $sql = "SELECT COALESCE(modificaciones_count, 0) as modificaciones, creado_en
                    FROM recepciones_externas WHERE Id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$resultado) {
                return [
                    'puede' => false,
                    'modificaciones' => 0,
                    'restantes' => 0,
                    'mensaje' => 'Recepción no encontrada'
                ];
            }
            
            $modificaciones = (int)$resultado['modificaciones'];
            $creadoEn = $resultado['creado_en'] ?? null;
            
            // Obtener configuración del usuario logueado
            $usuarioId = $_SESSION['user']['id'] ?? null;
            $config = $this->obtenerConfigUsuarioModificacion($usuarioId);
            
            // Si permite múltiples modificaciones, permitir sin restricción
            if ($config && !empty($config['permite_multiples'])) {
                return [
                    'puede' => true,
                    'modificaciones' => $modificaciones,
                    'restantes' => 999,
                    'mensaje' => 'Tiene permisos especiales para modificar sin restricciones'
                ];
            }
            
            // Determinar max_modificaciones
            $maxModificaciones = ($config && $config['max_modificaciones'] !== null)
                ? (int)$config['max_modificaciones']
                : 1;
            
            // Validar ventana de tiempo
            $ventanaHoras = ($config && $config['ventana_horas'] !== null)
                ? (int)$config['ventana_horas']
                : null;
                
            if ($ventanaHoras !== null && $creadoEn) {
                $fechaLimite = date('Y-m-d H:i:s', strtotime($creadoEn . " + {$ventanaHoras} hours"));
                if (strtotime('now') > strtotime($fechaLimite)) {
                    $fechaCreacion = date('d/m/Y H:i', strtotime($creadoEn));
                    return [
                        'puede' => false,
                        'modificaciones' => $modificaciones,
                        'restantes' => 0,
                        'mensaje' => "La ventana de modificacion de {$ventanaHoras}h ha expirado. " .
                                     "El vale fue creado el {$fechaCreacion}. " .
                                     "Contacte a un administrador si necesita realizar cambios."
                    ];
                }
            }
            
            // Validar límite de modificaciones
            $puede = $modificaciones < $maxModificaciones;
            $restantes = max(0, $maxModificaciones - $modificaciones);
            
            return [
                'puede' => $puede,
                'modificaciones' => $modificaciones,
                'restantes' => $restantes,
                'mensaje' => $puede
                    ? "Puede modificar. Modificaciones realizadas: {$modificaciones}, restantes: {$restantes}"
                    : "Este vale ya alcanzo el limite de {$maxModificaciones} modificaciones."
            ];
        } catch (Exception $e) {
            error_log('Error en puedeModificar: ' . $e->getMessage());
            return [
                'puede' => false,
                'modificaciones' => 0,
                'restantes' => 0,
                'mensaje' => 'Error al verificar el estado de modificaciones'
            ];
        }
    }
}

