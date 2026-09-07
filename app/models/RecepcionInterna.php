<?php
class RecepcionInterna extends Model {
    // Obtener una recepción por ID con todos sus datos
    public function getById($id) {
        try {
            // Primero obtenemos los datos principales de la recepción con JOIN a todas las tablas relacionadas
            $sql = "SELECT ri.*, 
                    t.Turno as TurnoTexto, 
                    a.Area as AreaTexto,
                    s.Subarea as SubareaTexto,
                    r1.NombresApellidos as DespachadorTexto,
                    mt.MedioTransporte as MedioTransporteTexto,
                    r2.NombresApellidos as VerificadorTexto
                FROM recepciones_internas ri
                LEFT JOIN turnos t ON ri.Turno = t.Id
                LEFT JOIN areas a ON ri.Area = a.Id
                LEFT JOIN subareas s ON ri.Subarea = s.Id
                LEFT JOIN responsables r1 ON ri.Despachador = r1.Id
                LEFT JOIN medio_transporte mt ON ri.MedioTransporte = mt.Id
                LEFT JOIN responsables r2 ON ri.Verificador = r2.Id
                WHERE ri.Id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $recepcion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$recepcion) {
                return null;
            }
            
            // Luego obtenemos los productos asociados a esta recepción
            $sqlProd = "SELECT * FROM recepciones_internas_productos WHERE DespachoId = ?";
            $stmtProd = $this->db->prepare($sqlProd);
            $stmtProd->execute([$id]);
            $productos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
            
            // Añadimos los productos al resultado
            $recepcion['Productos'] = $productos;
            
            return $recepcion;
        } catch (Exception $e) {
            error_log('Error obteniendo recepción por ID: ' . $e->getMessage());
            return null;
        }
    }
    
    // Actualizar un producto específico de una recepción
    public function actualizarProductoEspecifico($recepcionId, $codigoProducto, $nombreProducto, $cantidad, $comentarios, $unidadMedida = '')
    {
        try {
            $this->db->beginTransaction();

            error_log("Actualizando producto específico: recepcionId=$recepcionId, codigo=$codigoProducto, cantidad=$cantidad, unidadMedida=$unidadMedida");
            
            // Validaciones
            if (empty($recepcionId) || empty($codigoProducto)) {
                error_log("Faltan datos importantes para actualizar el producto");
                throw new Exception("Datos insuficientes para actualizar el producto");
            }
            
            // Validar que la recepción exista
            $stmtCheckRecepcion = $this->db->prepare("SELECT Id FROM recepciones_internas WHERE Id = :recepcionId");
            $stmtCheckRecepcion->bindParam(':recepcionId', $recepcionId);
            $stmtCheckRecepcion->execute();
            
            if ($stmtCheckRecepcion->rowCount() === 0) {
                error_log("Recepción con ID $recepcionId no encontrada");
                throw new Exception("Recepción no encontrada");
            }
            
            // Buscar el ID y la unidad de medida actual para este producto en esta recepción
            $stmtCheck = $this->db->prepare(
                "SELECT Id, UnidadMedida FROM recepciones_internas_productos 
                WHERE DespachoId = :recepcionId AND CodigoProducto = :codigoProducto"
            );
            $stmtCheck->bindParam(':recepcionId', $recepcionId);
            $stmtCheck->bindParam(':codigoProducto', $codigoProducto);
            $stmtCheck->execute();
            
            // Producto existente, actualizar
            if ($stmtCheck->rowCount() > 0) {
                $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                $productoId = $row['Id'];
                $existingUdm = $row['UnidadMedida'];
                
                // Si no se proporcionó unidad de medida, usar la existente
                if (empty($unidadMedida) && !empty($existingUdm)) {
                    $unidadMedida = $existingUdm;
                    error_log("Usando unidad de medida existente: $unidadMedida");
                }
                
                // Si aún no tenemos unidad de medida, intentar buscarla en el maestro de productos
                if (empty($unidadMedida)) {
                    $stmtProduct = $this->db->prepare(
                        "SELECT UnidadMedida FROM productos 
                         WHERE Codigo = :codigo LIMIT 1"
                    );
                    $stmtProduct->bindParam(':codigo', $codigoProducto);
                    $stmtProduct->execute();
                    if ($stmtProduct->rowCount() > 0) {
                        $productUdm = $stmtProduct->fetchColumn();
                        if (!empty($productUdm)) {
                            $unidadMedida = $productUdm;
                            error_log("Recuperada unidad de medida del maestro de productos: $unidadMedida");
                        }
                    }
                }
                
                // Preparar la consulta de actualización
                $updateSql = "UPDATE recepciones_internas_productos 
                              SET Cantidad = :cantidad, 
                                  Comentarios = :comentarios";
                
                // Solo incluir estos campos si se proporcionaron valores
                if (!empty($nombreProducto)) {
                    $updateSql .= ", DescripcionProducto = :descripcion";
                }
                
                if (!empty($unidadMedida)) {
                    $updateSql .= ", UnidadMedida = :unidadMedida";
                }
                
                $updateSql .= " WHERE Id = :id";
                
                $stmtUpdate = $this->db->prepare($updateSql);
                $stmtUpdate->bindParam(':cantidad', $cantidad);
                $stmtUpdate->bindParam(':comentarios', $comentarios);
                
                if (!empty($nombreProducto)) {
                    $stmtUpdate->bindParam(':descripcion', $nombreProducto);
                }
                
                if (!empty($unidadMedida)) {
                    $stmtUpdate->bindParam(':unidadMedida', $unidadMedida);
                }
                
                $stmtUpdate->bindParam(':id', $productoId);
                
                $resultado = $stmtUpdate->execute();
                
                if (!$resultado) {
                    error_log("Error al actualizar el producto: " . implode(", ", $stmtUpdate->errorInfo()));
                    throw new Exception("Error al actualizar el producto en la base de datos");
                }
            } else {
                // El producto no existe, crear nuevo
                error_log("El producto no existe en esta recepción, creando nuevo");
                
                // Si no hay unidad de medida, intentar obtenerla del maestro de productos
                if (empty($unidadMedida)) {
                    $stmtProduct = $this->db->prepare(
                        "SELECT UnidadMedida FROM productos 
                         WHERE Codigo = :codigo LIMIT 1"
                    );
                    $stmtProduct->bindParam(':codigo', $codigoProducto);
                    $stmtProduct->execute();
                    if ($stmtProduct->rowCount() > 0) {
                        $productUdm = $stmtProduct->fetchColumn();
                        if (!empty($productUdm)) {
                            $unidadMedida = $productUdm;
                            error_log("Recuperada unidad de medida del maestro de productos: $unidadMedida");
                        }
                    }
                }
                
                $stmtInsert = $this->db->prepare(
                    "INSERT INTO recepciones_internas_productos 
                     (DespachoId, CodigoProducto, DescripcionProducto, Cantidad, UnidadMedida, Comentarios) 
                     VALUES 
                     (:recepcionId, :codigoProducto, :nombreProducto, :cantidad, :unidadMedida, :comentarios)"
                );
                
                $stmtInsert->bindParam(':recepcionId', $recepcionId);
                $stmtInsert->bindParam(':codigoProducto', $codigoProducto);
                $stmtInsert->bindParam(':nombreProducto', $nombreProducto);
                $stmtInsert->bindParam(':cantidad', $cantidad);
                $stmtInsert->bindParam(':unidadMedida', $unidadMedida);
                $stmtInsert->bindParam(':comentarios', $comentarios);
                
                $resultado = $stmtInsert->execute();
                
                if (!$resultado) {
                    error_log("Error al insertar el producto: " . implode(", ", $stmtInsert->errorInfo()));
                    throw new Exception("Error al insertar el producto en la base de datos");
                }
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error en actualizarProductoEspecifico: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Actualizar una recepción interna
    public function actualizarRecepcion($id, $data) {
        // No actualizamos los campos NVale y Hora
        $sql = "UPDATE recepciones_internas SET Fecha=?, Turno=?, Area=?, Subarea=?, Despachador=?, MedioTransporte=?, Verificador=?, modificaciones_count = modificaciones_count + 1 WHERE Id=?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['Fecha'] ?? $data['fecha'] ?? '',
            $data['Turno'] ?? $data['turno'] ?? '',
            $data['Area'] ?? $data['area'] ?? '',
            $data['Subarea'] ?? $data['subarea'] ?? '',
            $data['Despachador'] ?? $data['despachador'] ?? '',
            $data['MedioTransporte'] ?? $data['medioTransporte'] ?? '',
            $data['Verificador'] ?? $data['verificador'] ?? '',
            $id
        ]);
    }

    // Actualizar productos de una recepción
    public function actualizarProductosRecepcion($recepcionId, $productos) {
        try {
            // Validar datos de entrada
            if (!is_numeric($recepcionId) || $recepcionId <= 0) {
                error_log('Error: ID de recepción inválido: ' . $recepcionId);
                throw new Exception('ID de recepción inválido: ' . $recepcionId);
            }
            
            if (!is_array($productos)) {
                error_log('Error: El parámetro productos debe ser un array');
                throw new Exception('El parámetro productos debe ser un array');
            }
            
            error_log("Actualizando productos para recepción ID: $recepcionId - Total productos: " . count($productos));
            
            // Verificar primero si la recepción existe
            $sqlCheck = "SELECT Id FROM recepciones_internas WHERE Id = ?";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->execute([$recepcionId]);
            $recepcion = $stmtCheck->fetch();
            
            if (!$recepcion) {
                error_log("Error: La recepción con ID $recepcionId no existe en la base de datos");
                throw new Exception("La recepción con ID $recepcionId no existe");
            }
            
            // Comenzar transacción
            $this->db->beginTransaction();
            error_log("Transacción iniciada para actualizar productos de la recepción ID: $recepcionId");

            try {
                // Eliminar productos existentes
                $sqlDelete = "DELETE FROM recepciones_internas_productos WHERE DespachoId = ?";
                $stmtDelete = $this->db->prepare($sqlDelete);
                $stmtDelete->execute([$recepcionId]);
                
                $countDeleted = $stmtDelete->rowCount();
                error_log("Productos eliminados: $countDeleted");
    
                // Insertar productos actualizados
                if (!empty($productos)) {
                    $sqlInsert = "INSERT INTO recepciones_internas_productos (DespachoId, CodigoProducto, DescripcionProducto, UnidadMedida, Cantidad, Comentarios) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmtInsert = $this->db->prepare($sqlInsert);
                    
                    foreach ($productos as $index => $producto) {
                        // Validar cada producto
                        if (!isset($producto['Codigo'])) {
                            error_log("Error: Código de producto faltante en índice $index");
                            throw new Exception("Código de producto faltante en índice $index");
                        }
                        
                        error_log("Insertando producto $index: " . json_encode($producto));
                        
                        // Validar y convertir tipos
                        $codigo = $producto['Codigo'] ?? '';
                        $nombreProducto = $producto['Producto'] ?? '';
                        $unidadMedida = $producto['UnidadMedida'] ?? '';
                        $cantidad = isset($producto['Cantidad']) ? (float)$producto['Cantidad'] : 0;
                        $comentarios = isset($producto['Comentarios']) ? strtoupper($producto['Comentarios']) : '';
                        
                        try {
                            $stmtInsert->execute([
                                $recepcionId,
                                $codigo,
                                $nombreProducto,
                                $unidadMedida,
                                $cantidad,
                                $comentarios
                            ]);
                            
                            error_log("Producto $index insertado correctamente: $codigo");
                        } catch (PDOException $pdoEx) {
                            error_log("Error al insertar producto $index: " . $pdoEx->getMessage());
                            throw new Exception("Error al insertar producto $index: " . $pdoEx->getMessage());
                        }
                    }
                } else {
                    error_log("No hay productos para insertar");
                }
                
                // Confirmar la transacción
                $this->db->commit();
                error_log("Transacción completada: productos actualizados para recepción ID: $recepcionId");
                return true;
            } catch (Exception $e) {
                // Revertir en caso de error
                $this->db->rollBack();
                error_log("Error en la transacción, revertida: " . $e->getMessage());
                throw $e;
            }
        } catch (Exception $e) {
            error_log('Error en actualizarProductosRecepcion: ' . $e->getMessage());
            throw new Exception('Error al actualizar productos: ' . $e->getMessage());
        }
    }
    
    // Registrar solo la recepción y retornar el ID
    public function registrarRecepcion($data) {
        // Nota: la columna antiguamente llamada 'Liquidacion' fue renombrada a 'NLiquidacion'.
        // Aceptamos ambos keys en el array $data para compatibilidad.
        $sql = "INSERT INTO recepciones_internas (NVale, Fecha, Hora, Turno, Area, Subarea, Emisor, Despachador, MedioTransporte, Verificador, NLiquidacion, creado_por, creado_en) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
        try {
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "ANTES INSERT RECEPCION\n", FILE_APPEND);
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['NVale'],
                $data['Fecha'],
                $data['Hora'],
                $data['Turno'],
                $data['Area'],
                $data['Subarea'],
                $data['Emisor'],
                $data['Despachador'],
                $data['MedioTransporte'],
                $data['Verificador'],
                // Preferimos NLiquidacion, pero toleramos el antiguo key 'Liquidacion'
                $data['NLiquidacion'] ?? $data['Liquidacion'] ?? null,
                $data['creado_por']
            ]);
            $id = $this->db->lastInsertId();
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "RECEPCION INSERTADA ID: " . print_r($id, true) . "\n", FILE_APPEND);
            return $id;
        } catch (PDOException $e) {
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "ERROR INSERT RECEPCION: " . $e->getMessage() . "\n", FILE_APPEND);
            throw new Exception('Error MySQL: ' . $e->getMessage());
        }
    }
    
    // Reporte de recepciones internas con filtros y paginación
    public function getReporte($filtros = [], $limit = 20, $offset = 0) {
        // Verificar y registrar la información inicial
        @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "=== INICIO getReporte Recepciones ===\n", FILE_APPEND);
        @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "Filtros recibidos: " . json_encode($filtros) . "\n", FILE_APPEND);
        @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "Limit: $limit, Offset: $offset\n", FILE_APPEND);
        
        // Validar parámetros
        $limit = (int)$limit;
        $offset = (int)$offset;
        $filtros = is_array($filtros) ? $filtros : [];
        
        $where = [];
        $params = [];
        
        // Filtros dinámicos
        if (!empty($filtros['N° Vale'])) {
            $where[] = 'ri.NVale LIKE ?';
            $params[] = '%' . $filtros['N° Vale'] . '%';
        }
        
        // Filtrado por rango de fechas
        if (!empty($filtros['fechaDesde'])) {
            $fechaDesde = $filtros['fechaDesde'];
            $where[] = 'ri.Fecha >= ?';
            $params[] = $fechaDesde;
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "Filtro fechaDesde: $fechaDesde\n", FILE_APPEND);
        }
        
        if (!empty($filtros['fechaHasta'])) {
            $fechaHasta = $filtros['fechaHasta'];
            $where[] = 'ri.Fecha <= ?';
            $params[] = $fechaHasta;
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "Filtro fechaHasta: $fechaHasta\n", FILE_APPEND);
        }
        
        // Filtro antiguo por Fecha (exacta)
        if (!empty($filtros['Fecha'])) {
            $fecha = $filtros['Fecha'];
            if (strpos($fecha, '-') !== false) {
                $partesFecha = explode('-', $fecha);
                if (count($partesFecha) === 3 && strlen($partesFecha[2]) === 4) {
                    $fecha = "{$partesFecha[2]}-{$partesFecha[1]}-{$partesFecha[0]}";
                }
            }
            $where[] = 'ri.Fecha = ?';
            $params[] = $fecha;
        }
        
        if (!empty($filtros['Hora'])) {
            $where[] = 'ri.Hora LIKE ?';
            $params[] = '%' . $filtros['Hora'] . '%';
        }
        
        // Filtros por IDs
        if (!empty($filtros['areaId'])) {
            $where[] = 'ri.Area = ?';
            $params[] = $filtros['areaId'];
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "Filtro areaId: " . $filtros['areaId'] . "\n", FILE_APPEND);
        }
        
        if (!empty($filtros['subareaId'])) {
            $where[] = 'ri.Subarea = ?';
            $params[] = $filtros['subareaId'];
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "Filtro subareaId: " . $filtros['subareaId'] . "\n", FILE_APPEND);
        }
        
        if (!empty($filtros['responsableId'])) {
            $where[] = 'ri.Despachador = ?';
            $params[] = $filtros['responsableId'];
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "Filtro responsableId: " . $filtros['responsableId'] . "\n", FILE_APPEND);
        }
        
        if (!empty($filtros['recepcionistaId'])) {
            $where[] = 'ri.Recepcionista = ?';
            $params[] = $filtros['recepcionistaId'];
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "Filtro recepcionistaId: " . $filtros['recepcionistaId'] . "\n", FILE_APPEND);
        }
        
        // Mantener los filtros originales por texto también para compatibilidad
        if (!empty($filtros['Área'])) {
            $where[] = 'a.Area LIKE ?';
            $params[] = '%' . $filtros['Área'] . '%';
        }
        if (!empty($filtros['Subárea'])) {
            $where[] = 's.Subarea LIKE ?';
            $params[] = '%' . $filtros['Subárea'] . '%';
        }
        if (!empty($filtros['Despachador'])) {
            $where[] = 'r1.NombresApellidos LIKE ?';
            $params[] = '%' . $filtros['Despachador'] . '%';
        }
        if (!empty($filtros['Recepcionista'])) {
            $where[] = 'rec.NombresApellidos LIKE ?';
            $params[] = '%' . $filtros['Recepcionista'] . '%';
        }
        if (!empty($filtros['Verificador'])) {
            $where[] = 'r2.NombresApellidos LIKE ?';
            $params[] = '%' . $filtros['Verificador'] . '%';
        }
        
        // Filtro por ID de medio transporte
        if (!empty($filtros['medioTransporteId'])) {
            $where[] = 'ri.MedioTransporte = ?';
            $params[] = $filtros['medioTransporteId'];
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "Filtro medioTransporteId: " . $filtros['medioTransporteId'] . "\n", FILE_APPEND);
        }
        
        // Mantener filtro original por texto para compatibilidad
        if (!empty($filtros['Medio Transporte'])) {
            $where[] = 'mt.MedioTransporte LIKE ?';
            $params[] = '%' . $filtros['Medio Transporte'] . '%';
        }
        
        // Filtro por ID de turno
        if (!empty($filtros['turnoId'])) {
            $where[] = 'ri.Turno = ?';
            $params[] = $filtros['turnoId'];
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "Filtro turnoId: " . $filtros['turnoId'] . "\n", FILE_APPEND);
        }
        
        // Mantener filtro original por texto para compatibilidad
        if (!empty($filtros['Turno'])) {
            $where[] = 't.Turno LIKE ?';
            $params[] = '%' . $filtros['Turno'] . '%';
        }
        
        if (!empty($filtros['Estado'])) {
            $where[] = 'ri.estado LIKE ?';
            $params[] = '%' . $filtros['Estado'] . '%';
        }
        
        $sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        
        // 1. Primero obtener el total de registros para paginación
        $sqlCount = "SELECT COUNT(DISTINCT ri.Id) FROM recepciones_internas ri 
                    LEFT JOIN turnos t ON ri.Turno = t.Id
                    LEFT JOIN areas a ON ri.Area = a.Id
                    LEFT JOIN subareas s ON ri.Subarea = s.Id
                    LEFT JOIN responsables r1 ON ri.Despachador = r1.Id
                    LEFT JOIN medio_transporte mt ON ri.MedioTransporte = mt.Id
                    LEFT JOIN responsables r2 ON ri.Verificador = r2.Id
                    $sqlWhere";
                    
        $stmtCount = $this->db->prepare($sqlCount);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();
        $totalPaginas = ceil($total / $limit);
        
        @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "Total registros encontrados: $total, Total páginas: $totalPaginas\n", FILE_APPEND);
        
        // 2. Luego construir la consulta principal
        $sql = "SELECT ri.Id, ri.NVale, ri.Fecha, ri.Hora,
            t.Turno as Turno,
            COALESCE(a.Area, '') as Area,
            COALESCE(s.Subarea, '') as Subarea,
            COALESCE(r1.NombresApellidos, '') as Despachador,
            COALESCE(mt.MedioTransporte, '') as MedioTransporte,
            COALESCE(u.NombresApellidos, '') as Asistente,
            COALESCE(r2.NombresApellidos, '') as Verificador,
            UPPER(ri.estado) as Estado,
            UPPER(ELT(DAYOFWEEK(ri.Fecha), 'Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado')) as Dia,
            WEEK(ri.Fecha) as Semana,
            UPPER(ELT(MONTH(ri.Fecha), 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre')) as Mes,
            COALESCE(ri.NLiquidacion, '') as NLiquidacion,
            COALESCE(ri.TipoRecepcion, 'RECEPCIÓN INTERNA') as TipoRecepcion
            FROM recepciones_internas ri
            LEFT JOIN turnos t ON ri.Turno = t.Id
            LEFT JOIN areas a ON ri.Area = a.Id
            LEFT JOIN subareas s ON ri.Subarea = s.Id
            LEFT JOIN responsables r1 ON ri.Despachador = r1.Id
            LEFT JOIN medio_transporte mt ON ri.MedioTransporte = mt.Id
            LEFT JOIN usuarios u ON ri.Emisor = u.Id
            LEFT JOIN responsables r2 ON ri.Verificador = r2.Id
            $sqlWhere
            GROUP BY ri.Id
            ORDER BY ri.NVale DESC
            LIMIT $limit OFFSET $offset";
        
        @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "SQL Final: " . str_replace("\n", " ", $sql) . "\n", FILE_APPEND);
        
        try {
            $stmt = $this->db->prepare($sql);
        } catch (Exception $e) {
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "ERROR EN PREPARACIÓN DE CONSULTA: " . $e->getMessage() . "\n", FILE_APPEND);
            $stmt = $this->db->prepare("SELECT 1");
        }
        
        try {
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "Ejecutando consulta principal\n", FILE_APPEND);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "Consulta principal exitosa, registros obtenidos: " . count($data) . "\n", FILE_APPEND);
            
            // Para cada recepción, obtener los productos como array
            // OPTIMIZACIÓN: Obtener todos los productos en UNA sola consulta
            if (!empty($data)) {
                $ids = array_column($data, 'Id');
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $sqlProd = "SELECT DespachoId, CodigoProducto as Codigo, DescripcionProducto as Producto, UnidadMedida, Cantidad, Comentarios FROM recepciones_internas_productos WHERE DespachoId IN ($placeholders)";
                $stmtProd = $this->db->prepare($sqlProd);
                $stmtProd->execute($ids);
                $todosProductos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
                $productosPorId = [];
                foreach ($todosProductos as $prod) {
                    $did = $prod['DespachoId'];
                    unset($prod['DespachoId']);
                    $productosPorId[$did][] = $prod;
                }
                foreach ($data as &$row) {
                    $row['Detalles'] = $productosPorId[$row['Id']] ?? [];
                }
                unset($row);
            }
            
            if (count($data) > 0) {
                @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "Muestra del primer registro: " . json_encode($data[0]) . "\n", FILE_APPEND);
            }
        } catch (Exception $e) {
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "ERROR SQL: " . $e->getMessage() . "\n", FILE_APPEND);
            $data = [];
            $totalPaginas = 1;
        }
        
        @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "Finalizando getReporte, registros devueltos: " . count($data) . ", totalPaginas: " . $totalPaginas . "\n", FILE_APPEND);
        return [ 'data' => $data, 'totalPaginas' => $totalPaginas ];
    }

    /**
     * Cuenta el total de filas planas (vales × productos)
     */
    public function getTotalFlattenedCount($filtros = []) {
        $where = []; $params = [];
        if (!empty($filtros['N° Vale'])) { $where[] = 'ri.NVale LIKE ?'; $params[] = '%' . $filtros['N° Vale'] . '%'; }
        if (!empty($filtros['fechaDesde'])) { $where[] = 'ri.Fecha >= ?'; $params[] = $filtros['fechaDesde']; }
        if (!empty($filtros['fechaHasta'])) { $where[] = 'ri.Fecha <= ?'; $params[] = $filtros['fechaHasta']; }
        if (!empty($filtros['Hora'])) { $where[] = 'ri.Hora LIKE ?'; $params[] = '%' . $filtros['Hora'] . '%'; }
        if (!empty($filtros['areaId'])) { $where[] = 'ri.Area = ?'; $params[] = $filtros['areaId']; }
        if (!empty($filtros['subareaId'])) { $where[] = 'ri.Subarea = ?'; $params[] = $filtros['subareaId']; }
        if (!empty($filtros['responsableId'])) { $where[] = 'ri.Despachador = ?'; $params[] = $filtros['responsableId']; }
        if (!empty($filtros['turnoId'])) { $where[] = 'ri.Turno = ?'; $params[] = $filtros['turnoId']; }
        $sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT COALESCE(SUM(COALESCE(p.pc, 1)), 0) as total
                FROM recepciones_internas ri
                LEFT JOIN (SELECT RecepcionInternaId, COUNT(*) as pc FROM recepciones_internas_productos GROUP BY RecepcionInternaId) p ON ri.Id = p.RecepcionInternaId
                $sqlWhere";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return (int)$stmt->fetchColumn(); }
        catch (Exception $e) { return 0; }
    }
    
    // Obtener el siguiente número de vale disponible
    public function obtenerSiguienteVale() {
        $sql = "SELECT IFNULL(MAX(NVale),0)+1 AS siguiente FROM recepciones_internas";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['siguiente'] : 1;
    }
    
    // Anular una recepción interna
    public function anular($id, $anulado_por, $motivo = '') {
        try {
            $sql = "UPDATE recepciones_internas SET estado = 'ANULADO', motivo_anulacion = ?, anulado_por = ?, anulado_en = NOW() WHERE Id = ? AND LOWER(estado) != 'anulado'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$motivo, $anulado_por, $id]);
            if ($stmt->rowCount() === 0) {
                throw new Exception('No se pudo anular la recepción (ya está anulada o no existe).');
            }
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    // Reactivar una recepción interna
    public function reactivar($id, $reactivado_por) {
        try {
            $sql = "UPDATE recepciones_internas SET estado = 'ACTIVO', motivo_anulacion = NULL, anulado_por = NULL, anulado_en = NULL WHERE Id = ? AND estado = 'ANULADO'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) {
                throw new Exception('No se pudo reactivar la recepción (no está anulada o no existe).');
            }
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    // Registrar en el historial quién hizo la modificación
    public function registrarModificacion($recepcionId, $usuarioId, $ip = null) {
        // Obtener el valor actual de modificaciones_count (ya fue incrementado por actualizarRecepcion())
        $sql = "SELECT modificaciones_count FROM recepciones_internas WHERE Id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$recepcionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $nModificacion = $row ? (int)$row['modificaciones_count'] : 0;

        $sql = "INSERT INTO recepciones_internas_modificaciones (RecepcionId, UsuarioId, NModificacion, IpAddress) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$recepcionId, $usuarioId, $nModificacion, $ip]);
    }

    // Verifica si la recepción puede ser modificada
    // Ahora considera la configuración por usuario:
    // - permite_multiples = 1 → sin restricciones
    // - ventana_horas definido → validar ventana de tiempo desde creado_en
    // - max_modificaciones personalizado o default 1
    public function puedeModificar($id) {
        try {
            $sql = "SELECT modificaciones_count, creado_en FROM recepciones_internas WHERE Id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                return [
                    'puede' => false,
                    'modificaciones' => 0,
                    'restantes' => 0,
                    'mensaje' => 'Recepción no encontrada'
                ];
            }
            
            $modificaciones = (int)$result['modificaciones_count'];
            $creadoEn = $result['creado_en'] ?? null;
            
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

            $mensaje = $puede
                ? "Puede modificar. Modificaciones realizadas: {$modificaciones}, restantes: {$restantes}"
                : "Este vale ya alcanzo el limite de {$maxModificaciones} modificaciones.";
            
            return [
                'puede' => $puede,
                'modificaciones' => $modificaciones,
                'restantes' => $restantes,
                'mensaje' => $mensaje
            ];
        } catch (Exception $e) {
            error_log('Error en puedeModificar: ' . $e->getMessage());
            return [
                'puede' => false,
                'modificaciones' => 0,
                'restantes' => 0,
                'mensaje' => 'Error al verificar modificaciones: ' . $e->getMessage()
            ];
        }
    }
}
