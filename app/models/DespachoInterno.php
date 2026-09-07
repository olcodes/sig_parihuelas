<?php
class DespachoInterno extends Model {
    // Obtener un despacho por ID con todos sus datos
    public function getById($id) {
        try {
            // Primero obtenemos los datos principales del despacho con JOIN a todas las tablas relacionadas
            $sql = "SELECT di.*, 
                    t.Turno as TurnoTexto, 
                    a.Area as AreaTexto,
                    s.Subarea as SubareaTexto,
                    r1.NombresApellidos as DespachadorTexto,
                    rec.NombresApellidos as RecepcionistaTexto,
                    r2.NombresApellidos as VerificadorTexto
                FROM despachos_internos di
                LEFT JOIN turnos t ON di.Turno = t.Id
                LEFT JOIN areas a ON di.Area = a.Id
                LEFT JOIN subareas s ON di.Subarea = s.Id
                LEFT JOIN responsables r1 ON di.Despachador = r1.Id
                LEFT JOIN recepcionistas rec ON di.Recepcionista = rec.Id
                LEFT JOIN responsables r2 ON di.Verificador = r2.Id
                WHERE di.Id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $despacho = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$despacho) {
                return null;
            }
            
            // Luego obtenemos los productos asociados a este despacho
            $sqlProd = "SELECT * FROM despachos_internos_productos WHERE DespachoId = ?";
            $stmtProd = $this->db->prepare($sqlProd);
            $stmtProd->execute([$id]);
            $productos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
            
            // Añadimos los productos al resultado
            $despacho['Productos'] = $productos;
            
            return $despacho;
        } catch (Exception $e) {
            error_log('Error obteniendo despacho por ID: ' . $e->getMessage());
            return null;
        }
    }
    
    // Actualizar un producto específico de un despacho
    public function actualizarProductoEspecifico($despachoId, $codigoProductoOriginal, $codigoProductoNuevo, $nombreProducto, $cantidad, $comentarios, $unidadMedida = '')
    {
        try {
            $this->db->beginTransaction();

            error_log("Actualizando producto específico: despachoId=$despachoId, codigoOriginal=$codigoProductoOriginal, codigoNuevo=$codigoProductoNuevo, cantidad=$cantidad, unidadMedida=$unidadMedida");
            
            // Validaciones
            if (empty($despachoId) || empty($codigoProductoOriginal)) {
                error_log("Faltan datos importantes para actualizar el producto");
                throw new Exception("Datos insuficientes para actualizar el producto");
            }
            
            // Validar que el despacho exista
            $stmtCheckDespacho = $this->db->prepare("SELECT Id FROM despachos_internos WHERE Id = :despachoId");
            $stmtCheckDespacho->bindParam(':despachoId', $despachoId);
            $stmtCheckDespacho->execute();
            
            if ($stmtCheckDespacho->rowCount() === 0) {
                error_log("Despacho con ID $despachoId no encontrado");
                throw new Exception("Despacho no encontrado");
            }
            
            // Buscar el ID y la unidad de medida actual para este producto en este despacho
            // IMPORTANTE: Usamos el código original para encontrar el registro existente
            $stmtCheck = $this->db->prepare(
                "SELECT Id, UnidadMedida FROM despachos_internos_productos 
                WHERE DespachoId = :despachoId AND CodigoProducto = :codigoProducto"
            );
            $stmtCheck->bindParam(':despachoId', $despachoId);
            $stmtCheck->bindParam(':codigoProducto', $codigoProductoOriginal);
            $stmtCheck->execute();
            
            $rowCount = $stmtCheck->rowCount();
            error_log("Búsqueda de producto: DespachoId=$despachoId, CodigoProducto=$codigoProductoOriginal, Filas encontradas=$rowCount");
            
            // Producto existente, actualizar
            if ($rowCount > 0) {
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
                    $stmtProduct->bindParam(':codigo', $codigoProductoNuevo);  // Usar código nuevo
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
                $updateSql = "UPDATE despachos_internos_productos 
                              SET Cantidad = :cantidad, 
                                  Comentarios = :comentarios";
                
                // Actualizar el código del producto si cambió
                if (!empty($codigoProductoNuevo) && $codigoProductoNuevo !== $codigoProductoOriginal) {
                    $updateSql .= ", CodigoProducto = :codigoNuevo";
                }
                
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
                
                if (!empty($codigoProductoNuevo) && $codigoProductoNuevo !== $codigoProductoOriginal) {
                    $stmtUpdate->bindParam(':codigoNuevo', $codigoProductoNuevo);
                }
                
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
                error_log("El producto no existe en este despacho, creando nuevo");
                
                // Si no hay unidad de medida, intentar obtenerla del maestro de productos
                if (empty($unidadMedida)) {
                    $stmtProduct = $this->db->prepare(
                        "SELECT UnidadMedida FROM productos 
                         WHERE Codigo = :codigo LIMIT 1"
                    );
                    $stmtProduct->bindParam(':codigo', $codigoProductoNuevo);  // Usar código nuevo
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
                    "INSERT INTO despachos_internos_productos 
                     (DespachoId, CodigoProducto, DescripcionProducto, Cantidad, UnidadMedida, Comentarios) 
                     VALUES 
                     (:despachoId, :codigoProducto, :nombreProducto, :cantidad, :unidadMedida, :comentarios)"
                );
                
                $stmtInsert->bindParam(':despachoId', $despachoId);
                $stmtInsert->bindParam(':codigoProducto', $codigoProductoNuevo);
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
    
    // Actualizar un despacho interno
    public function actualizarDespacho($id, $data) {
        // No actualizamos los campos NVale y Hora, pero sí incrementamos el contador de modificaciones
        $sql = "UPDATE despachos_internos SET Fecha=?, Turno=?, Area=?, Subarea=?, Despachador=?, Recepcionista=?, Verificador=?, modificaciones_count = modificaciones_count + 1 WHERE Id=?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['Fecha'] ?? $data['fecha'] ?? '',
            $data['Turno'] ?? $data['turno'] ?? '',
            $data['Area'] ?? $data['area'] ?? '',
            $data['Subarea'] ?? $data['subarea'] ?? '',
            $data['Despachador'] ?? $data['despachador'] ?? '',
            $data['Recepcionista'] ?? $data['recepcionista'] ?? '',
            $data['Verificador'] ?? $data['verificador'] ?? '',
            $id
        ]);
    }

    // Registrar en el historial quién hizo la modificación
    public function registrarModificacion($despachoId, $usuarioId, $ip = null) {
        // Obtener el valor actual de modificaciones_count (ya fue incrementado)
        $sql = "SELECT modificaciones_count FROM despachos_internos WHERE Id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$despachoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $nModificacion = $row ? (int)$row['modificaciones_count'] : 0;

        $sql = "INSERT INTO despachos_internos_modificaciones (DespachoId, UsuarioId, NModificacion, IpAddress) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$despachoId, $usuarioId, $nModificacion, $ip]);
    }

    // Validar si el vale puede ser modificado
    // Ahora considera la configuración por usuario:
    // - permite_multiples = 1 → sin restricciones
    // - ventana_horas definido → validar ventana de tiempo desde creado_en
    // - max_modificaciones personalizado o default 1
    public function puedeModificar($id) {
        try {
            // Intentar con creado_en, si la columna no existe se captura la excepción
            $creadoEn = null;
            try {
                $sql = "SELECT modificaciones_count, creado_en FROM despachos_internos WHERE Id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    $creadoEn = $result['creado_en'] ?? null;
                }
            } catch (Exception $eInner) {
                // Si falla por columna creado_en, intentar sin ella
                $sql = "SELECT modificaciones_count FROM despachos_internos WHERE Id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            if (!$result) {
                return ['puede' => false, 'mensaje' => 'Vale no encontrado', 'modificaciones' => 0, 'restantes' => 0];
            }
            
            $modificaciones = (int)$result['modificaciones_count'];
            
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
            error_log('Error en DespachoInterno::puedeModificar: ' . $e->getMessage());
            return [
                'puede' => false,
                'modificaciones' => 0,
                'restantes' => 0,
                'mensaje' => 'Error al verificar modificaciones: ' . $e->getMessage()
            ];
        }
    }

    // Actualizar productos de un despacho
    public function actualizarProductosDespacho($despachoId, $productos) {
        try {
            // Validar datos de entrada
            if (!is_numeric($despachoId) || $despachoId <= 0) {
                error_log('Error: ID de despacho inválido: ' . $despachoId);
                throw new Exception('ID de despacho inválido: ' . $despachoId);
            }
            
            if (!is_array($productos)) {
                error_log('Error: El parámetro productos debe ser un array');
                throw new Exception('El parámetro productos debe ser un array');
            }
            
            error_log("Actualizando productos para despacho ID: $despachoId - Total productos: " . count($productos));
            
            // Verificar primero si el despacho existe
            $sqlCheck = "SELECT Id FROM despachos_internos WHERE Id = ?";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->execute([$despachoId]);
            $despacho = $stmtCheck->fetch();
            
            if (!$despacho) {
                error_log("Error: El despacho con ID $despachoId no existe en la base de datos");
                throw new Exception("El despacho con ID $despachoId no existe");
            }
            
            // Comenzar transacción
            $this->db->beginTransaction();
            error_log("Transacción iniciada para actualizar productos del despacho ID: $despachoId");

            try {
                // Eliminar productos existentes
                $sqlDelete = "DELETE FROM despachos_internos_productos WHERE DespachoId = ?";
                $stmtDelete = $this->db->prepare($sqlDelete);
                $stmtDelete->execute([$despachoId]);
                
                $countDeleted = $stmtDelete->rowCount();
                error_log("Productos eliminados: $countDeleted");
    
                // Insertar productos actualizados
                if (!empty($productos)) {
                    $sqlInsert = "INSERT INTO despachos_internos_productos (DespachoId, CodigoProducto, DescripcionProducto, UnidadMedida, Cantidad, Comentarios) VALUES (?, ?, ?, ?, ?, ?)";
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
                                $despachoId,
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
                error_log("Transacción completada: productos actualizados para despacho ID: $despachoId");
                return true;
            } catch (Exception $e) {
                // Revertir en caso de error
                $this->db->rollBack();
                error_log("Error en la transacción, revertida: " . $e->getMessage());
                throw $e;
            }
        } catch (Exception $e) {
            error_log('Error en actualizarProductosDespacho: ' . $e->getMessage());
            throw new Exception('Error al actualizar productos: ' . $e->getMessage());
        }
    }
        // Reporte de despachos internos con filtros y paginación
        // Registrar solo el despacho y retornar el ID
        public function registrarDespacho($data) {
            // Insertar directamente en la tabla evitando el procedimiento almacenado
            try {
                @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "ANTES INSERT DESPACHO DIRECTO\n", FILE_APPEND);
                $sql = "INSERT INTO despachos_internos 
                        (NVale, Fecha, Hora, Turno, Area, Subarea, Emisor, Despachador, Recepcionista, Verificador, estado, creado_por, creado_en, ip_creacion)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVO', ?, NOW(), ?)";

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
                    $data['Recepcionista'],
                    $data['Verificador'],
                    $data['creado_por'],
                    $data['ip_creacion'] ?? null
                ]);

                $id = $this->db->lastInsertId();
                @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "DESPACHO INSERTADO ID: " . print_r($id, true) . "\n", FILE_APPEND);
                return $id;
            } catch (PDOException $e) {
                @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "ERROR INSERT DESPACHO: " . $e->getMessage() . "\n", FILE_APPEND);
                throw new Exception('Error MySQL: ' . $e->getMessage());
            }
        }
    public function getReporte($filtros = [], $limit = 20, $offset = 0) {
        // Verificar y registrar la información inicial
        @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "=== INICIO getReporte ===\n", FILE_APPEND);
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
            $where[] = 'di.NVale LIKE ?';
            $params[] = '%' . $filtros['N° Vale'] . '%';
        }
        
        // Filtrado por rango de fechas
        if (!empty($filtros['fechaDesde'])) {
            $fechaDesde = $filtros['fechaDesde'];
            $where[] = 'di.Fecha >= ?';
            $params[] = $fechaDesde;
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "Filtro fechaDesde: $fechaDesde\n", FILE_APPEND);
        }
        
        if (!empty($filtros['fechaHasta'])) {
            $fechaHasta = $filtros['fechaHasta'];
            $where[] = 'di.Fecha <= ?';
            $params[] = $fechaHasta;
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "Filtro fechaHasta: $fechaHasta\n", FILE_APPEND);
        }
        
        // Filtro antiguo por Fecha (exacta)
        if (!empty($filtros['Fecha'])) {
            // Asegurarse de que la fecha esté en formato correcto para MySQL (YYYY-MM-DD)
            $fecha = $filtros['Fecha'];
            if (strpos($fecha, '-') !== false) {
                $partesFecha = explode('-', $fecha);
                if (count($partesFecha) === 3 && strlen($partesFecha[2]) === 4) {
                    // Si está en formato DD-MM-YYYY, convertir a YYYY-MM-DD
                    $fecha = "{$partesFecha[2]}-{$partesFecha[1]}-{$partesFecha[0]}";
                }
            }
            $where[] = 'di.Fecha = ?';
            $params[] = $fecha;
        }
        if (!empty($filtros['Hora'])) {
            $where[] = 'di.Hora LIKE ?';
            $params[] = '%' . $filtros['Hora'] . '%';
        }
        // Filtros por IDs en lugar de texto
        if (!empty($filtros['areaId'])) {
            $where[] = 'di.Area = ?';
            $params[] = $filtros['areaId'];
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "Filtro areaId: " . $filtros['areaId'] . "\n", FILE_APPEND);
        }
        
        if (!empty($filtros['subareaId'])) {
            $where[] = 'di.Subarea = ?';
            $params[] = $filtros['subareaId'];
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "Filtro subareaId: " . $filtros['subareaId'] . "\n", FILE_APPEND);
        }
        
        if (!empty($filtros['responsableId'])) {
            $where[] = 'di.Despachador = ?';
            $params[] = $filtros['responsableId'];
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "Filtro responsableId: " . $filtros['responsableId'] . "\n", FILE_APPEND);
        }
        
        if (!empty($filtros['recepcionistaId'])) {
            $where[] = 'di.Recepcionista = ?';
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
        
        // Filtro por ID de turno
        if (!empty($filtros['turnoId'])) {
            $where[] = 'di.Turno = ?';
            $params[] = $filtros['turnoId'];
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "Filtro turnoId: " . $filtros['turnoId'] . "\n", FILE_APPEND);
        }
        
        // Mantener filtro original por texto para compatibilidad
        if (!empty($filtros['Turno'])) {
            $where[] = 't.Turno LIKE ?';
            $params[] = '%' . $filtros['Turno'] . '%';
        }
        
        if (!empty($filtros['Estado'])) {
            $where[] = 'di.estado LIKE ?';
            $params[] = '%' . $filtros['Estado'] . '%';
        }
        
        $sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        // 1. Primero obtener el total de registros para paginación
        $sqlCount = "SELECT COUNT(DISTINCT di.Id) FROM despachos_internos di 
                    LEFT JOIN turnos t ON di.Turno = t.Id
                    LEFT JOIN areas a ON di.Area = a.Id
                    LEFT JOIN subareas s ON di.Subarea = s.Id
                    LEFT JOIN responsables r1 ON di.Despachador = r1.Id
                    LEFT JOIN recepcionistas rec ON di.Recepcionista = rec.Id
                    LEFT JOIN responsables r2 ON di.Verificador = r2.Id
                    $sqlWhere";
                    
        $stmtCount = $this->db->prepare($sqlCount);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();
        $totalPaginas = ceil($total / $limit);
        
        @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "Total registros encontrados: $total, Total páginas: $totalPaginas\n", FILE_APPEND);
        
        // 2. Luego construir la consulta principal
        $sql = "SELECT di.Id, di.NVale, di.Fecha, di.Hora, t.Turno as Turno,
            COALESCE(a.Area, '') as Area,
            COALESCE(s.Subarea, '') as Subarea,
            COALESCE(u.NombresApellidos, '') as Emisor,
            COALESCE(r1.NombresApellidos, '') as Despachador,
            COALESCE(rec.NombresApellidos, '') as Recepcionista,
            COALESCE(r2.NombresApellidos, '') as Verificador,
            di.estado as Estado,
            UPPER(ELT(DAYOFWEEK(di.Fecha), 'Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado')) as Dia,
            WEEK(di.Fecha) as Semana,
            UPPER(ELT(MONTH(di.Fecha), 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre')) as Mes,
            '' as NLiquidacion,
            'DESPACHO INTERNO' as TipoDespacho
            FROM despachos_internos di
            LEFT JOIN turnos t ON di.Turno = t.Id
            LEFT JOIN areas a ON di.Area = a.Id
            LEFT JOIN subareas s ON di.Subarea = s.Id
            LEFT JOIN usuarios u ON di.Emisor = u.Id
            LEFT JOIN responsables r1 ON di.Despachador = r1.Id
            LEFT JOIN recepcionistas rec ON di.Recepcionista = rec.Id
            LEFT JOIN responsables r2 ON di.Verificador = r2.Id
            $sqlWhere
            GROUP BY di.Id
            ORDER BY di.NVale DESC
            LIMIT $limit OFFSET $offset";
        
        @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "SQL Final: " . str_replace("\n", " ", $sql) . "\n", FILE_APPEND);
        
        // Preparar la sentencia para la consulta principal
        try {
            $stmt = $this->db->prepare($sql);
        } catch (Exception $e) {
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "ERROR EN PREPARACIÓN DE CONSULTA: " . $e->getMessage() . "\n", FILE_APPEND);
            // Crear un statement dummy para evitar errores
            $stmt = $this->db->prepare("SELECT 1");
        }
        try {
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "Ejecutando consulta principal\n", FILE_APPEND);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "Consulta principal exitosa, registros obtenidos: " . count($data) . "\n", FILE_APPEND);
            
            // OPTIMIZACIÓN: Obtener todos los productos en UNA sola consulta
            if (!empty($data)) {
                $ids = array_column($data, 'Id');
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $sqlProd = "SELECT DespachoId, CodigoProducto as Codigo, DescripcionProducto as Producto, UnidadMedida, Cantidad, Comentarios FROM despachos_internos_productos WHERE DespachoId IN ($placeholders)";
                $stmtProd = $this->db->prepare($sqlProd);
                $stmtProd->execute($ids);
                $todosProductos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
                // Agrupar productos por DespachoId
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
            
            // Solo logear una muestra para evitar logs muy grandes
            if (count($data) > 0) {
                @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "Muestra del primer registro: " . json_encode($data[0]) . "\n", FILE_APPEND);
            }
        } catch (Exception $e) {
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "ERROR SQL: " . $e->getMessage() . "\n", FILE_APPEND);
            $data = [];
            // Si hay un error, también reiniciar la paginación
            $totalPaginas = 1;
        }
        
        @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "Finalizando getReporte, registros devueltos: " . count($data) . ", totalPaginas: " . $totalPaginas . "\n", FILE_APPEND);
        @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "ANTES DE RETORNAR getReporte\n", FILE_APPEND);
        return [ 'data' => $data, 'totalPaginas' => $totalPaginas ];
    }

    /**
     * Cuenta el total de filas planas (vales × productos) para paginación correcta.
     */
    public function getTotalFlattenedCount($filtros = []) {
        $where = [];
        $params = [];
        // Reconstruir mismos filtros que getReporte
        if (!empty($filtros['N° Vale'])) { $where[] = 'di.NVale LIKE ?'; $params[] = '%' . $filtros['N° Vale'] . '%'; }
        if (!empty($filtros['fechaDesde'])) { $where[] = 'di.Fecha >= ?'; $params[] = $filtros['fechaDesde']; }
        if (!empty($filtros['fechaHasta'])) { $where[] = 'di.Fecha <= ?'; $params[] = $filtros['fechaHasta']; }
        if (!empty($filtros['Hora'])) { $where[] = 'di.Hora LIKE ?'; $params[] = '%' . $filtros['Hora'] . '%'; }
        if (!empty($filtros['areaId'])) { $where[] = 'di.Area = ?'; $params[] = $filtros['areaId']; }
        if (!empty($filtros['subareaId'])) { $where[] = 'di.Subarea = ?'; $params[] = $filtros['subareaId']; }
        if (!empty($filtros['responsableId'])) { $where[] = 'di.Despachador = ?'; $params[] = $filtros['responsableId']; }
        if (!empty($filtros['recepcionistaId'])) { $where[] = 'di.Recepcionista = ?'; $params[] = $filtros['recepcionistaId']; }
        if (!empty($filtros['turnoId'])) { $where[] = 'di.Turno = ?'; $params[] = $filtros['turnoId']; }
        $sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT COALESCE(SUM(COALESCE(p.pc, 1)), 0) as total
                FROM despachos_internos di
                LEFT JOIN (SELECT DespachoId, COUNT(*) as pc FROM despachos_internos_productos GROUP BY DespachoId) p ON di.Id = p.DespachoId
                $sqlWhere";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }
    // Obtener el siguiente número de vale disponible
    public function obtenerSiguienteVale() {
        $sql = "SELECT IFNULL(MAX(NVale),0)+1 AS siguiente FROM despachos_internos";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['siguiente'] : 1;
    }
    
    // Método para contar registros en la tabla
    public function contarRegistros() {
        $sql = "SELECT COUNT(*) as cantidad FROM despachos_internos";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Método para insertar un registro de prueba
    public function insertarRegistroPrueba() {
        try {
            // Verificar que existan las tablas relacionadas necesarias
            $fechaActual = date('Y-m-d');
            $horaActual = date('H:i:s');
            
            $sql = "INSERT INTO despachos_internos 
                    (NVale, Fecha, Hora, Turno, Area, Subarea, Emisor, Despachador, Recepcionista, Verificador, estado) 
                    VALUES (1, ?, ?, 1, 1, 1, 'Usuario de prueba', 1, 1, 1, 'ACTIVO')";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$fechaActual, $horaActual]);
            
            if ($result) {
                $id = $this->db->lastInsertId();
                // También insertar un producto de prueba
                $sqlProducto = "INSERT INTO despachos_internos_productos 
                                (DespachoId, CodigoProducto, DescripcionProducto, Cantidad, Comentarios) 
                                VALUES (?, 'PRU001', 'Producto de prueba', 10, 'Comentario de prueba')";
                $stmtProducto = $this->db->prepare($sqlProducto);
                $stmtProducto->execute([$id]);
                
                return true;
            }
            return false;
        } catch (Exception $e) {
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "ERROR AL INSERTAR REGISTRO DE PRUEBA: " . $e->getMessage() . "\n", FILE_APPEND);
            return false;
        }
    }

    // Anular un despacho interno (marca estado = 'ANULADO')
    public function anular($id, $anulado_por, $motivo = '') {
        try {
            // Actualizar estado, motivo y metadata de anulación
            $sql = "UPDATE despachos_internos SET estado = 'ANULADO', motivo_anulacion = ?, anulado_por = ?, anulado_en = NOW() WHERE Id = ? AND LOWER(estado) != 'anulado'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$motivo, $anulado_por, $id]);
            if ($stmt->rowCount() === 0) {
                throw new Exception('No se pudo anular el despacho (ya está anulado o no existe).');
            }
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    // Reactivar un despacho interno (marca estado = 'ACTIVO' y limpia metadata de anulación)
    public function reactivar($id, $reactivado_por) {
        try {
            $sql = "UPDATE despachos_internos SET estado = 'ACTIVO', motivo_anulacion = NULL, anulado_por = NULL, anulado_en = NULL WHERE Id = ? AND estado = 'ANULADO'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) {
                throw new Exception('No se pudo reactivar el despacho (no está anulado o no existe).');
            }
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }
    // Registrar un despacho interno
    public function registrar($data) {
        // Implementación transaccional: insertar despacho y opcionalmente el primer producto
        try {
            $this->db->beginTransaction();

            $sqlDesp = "INSERT INTO despachos_internos 
                        (NVale, Fecha, Hora, Turno, Area, Subarea, Emisor, Despachador, Recepcionista, Verificador, estado, creado_por, creado_en)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVO', ?, NOW())";

            $stmtDesp = $this->db->prepare($sqlDesp);
            $stmtDesp->execute([
                $data['NVale'] ?? null,
                $data['Fecha'] ?? null,
                $data['Hora'] ?? date('H:i:s'),
                $data['Turno'] ?? null,
                $data['Area'] ?? null,
                $data['Subarea'] ?? null,
                $data['Emisor'] ?? null,
                $data['Despachador'] ?? null,
                $data['Recepcionista'] ?? null,
                $data['Verificador'] ?? null,
                $data['creado_por'] ?? null
            ]);

            $despachoId = $this->db->lastInsertId();

            // Si vienen datos de producto, insertar uno (esta función replicaba el SP que insertaba 1 producto)
            if (!empty($data['CodigoProducto']) || !empty($data['DescripcionProducto']) || !empty($data['Cantidad'])) {
                $sqlProd = "INSERT INTO despachos_internos_productos 
                            (DespachoId, CodigoProducto, DescripcionProducto, UnidadMedida, Cantidad, Comentarios)
                            VALUES (?, ?, ?, ?, ?, ?)";
                $stmtProd = $this->db->prepare($sqlProd);
                $stmtProd->execute([
                    $despachoId,
                    $data['CodigoProducto'] ?? null,
                    $data['DescripcionProducto'] ?? null,
                    $data['UnidadMedida'] ?? ($data['Unidad'] ?? null),
                    $data['Cantidad'] ?? ($data['CantidadProducto'] ?? null),
                    $data['Observaciones'] ?? ($data['Comentarios'] ?? null)
                ]);
            }

            $this->db->commit();
            return $despachoId;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw new Exception('Error MySQL: ' . $e->getMessage());
        }
    }

    // Modificar un despacho interno
    public function modificar($data) {
        $sql = "CALL modificar_despacho_interno(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        try {
            return $this->query($sql, [
                $data['Id'],
                $data['NVale'],
                $data['Fecha'],
                $data['Hora'],
                $data['Turno'],
                $data['Area'],
                $data['Subarea'],
                $data['Emisor'],
                $data['Despachador'],
                $data['Recepcionista'],
                $data['Verificador'],
                $data['CodigoProducto'] ?? null,
                $data['DescripcionProducto'] ?? null,
                $data['Cantidad'] ?? null,
                $data['Observaciones'] ?? null,
                $data['Liquidacion'] ?? null,
                $data['modificado_por']
            ]);
        } catch (PDOException $e) {
            throw new Exception('Error MySQL: ' . $e->getMessage());
        }
    }

    // Obtener datos completos de un despacho por ID
    public function obtenerPorId($id) {
        $sql = "CALL pa_despacho_interno_obtener_por_id(?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $despacho = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$despacho) return null;
        // Obtener productos asociados
        $sqlProd = "CALL pa_despacho_interno_productos_por_id(?)";
        $stmtProd = $this->db->prepare($sqlProd);
        $stmtProd->execute([$id]);
        $productos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
        $despacho['productos'] = $productos;
        return $despacho;
    }
}
