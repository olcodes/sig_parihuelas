
<?php
require_once __DIR__ . '/../../core/Model.php';
class DespachoExterno extends Model {
    /**
     * Normaliza un valor de hora para guardar en la BD.
     * Devuelve una cadena en formato 'HH:MM:SS' o NULL si el valor no es válido/está vacío.
     * Acepta formatos comunes: 'HH:MM', 'HH:MM:SS', con puntos como separador, y formatos con AM/PM.
     */
    private function normalizeTimeForDb($time)
    {
        if ($time === null) return null;
        $time = trim((string)$time);
        if ($time === '') return null;

        // Reemplazar puntos por dos puntos (p. ej. 08.30 -> 08:30)
        $time = str_replace('.', ':', $time);

        // Intentar varios formatos controlados
        $formats = ['H:i:s', 'H:i', 'G:i', 'g:i A', 'g:i:s A', 'h:i A', 'h:i:s A'];
        foreach ($formats as $fmt) {
            $d = DateTime::createFromFormat($fmt, $time);
            if ($d !== false) {
                return $d->format('H:i:s');
            }
        }

        // Intentar strtotime como fallback
        $ts = strtotime($time);
        if ($ts !== false) {
            return date('H:i:s', $ts);
        }

        // Si no pudimos parsear, devolver NULL para evitar enviar cadena vacía o inválida a MySQL
        return null;
    }

    /**
     * Sanitiza un valor que debe ser entero (id FK) para la BD.
     * Devuelve int si es numérico, o NULL si está vacío o no es válido.
     */
    private function sanitizeIntOrNull($val)
    {
        if ($val === null) return null;
        $v = is_string($val) ? trim($val) : $val;
        if ($v === '') return null;
        if (is_numeric($v)) return (int)$v;
        return null;
    }

    /**
     * Intenta resolver un valor de destino (que podría ser texto/nombre de empresa)
     * a su ID numérico en la tabla destino.
     * Si no se puede resolver, devuelve el valor original (podría ser texto).
     */
    private function resolveDestinoToId($destino)
    {
        if ($destino === null) return null;
        if (is_numeric($destino)) return (int)$destino;
        $v = trim((string)$destino);
        if ($v === '') return null;
        if (is_numeric($v)) return (int)$v;

        // Es texto (nombre de empresa), intentar buscar el ID en la tabla destino
        try {
            // Intentar detectar la tabla real primero
            $detectedTable = null;
            try {
                $stmtDetect = $this->db->prepare("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND (table_name LIKE '%destin%' OR table_name LIKE '%cliente%') LIMIT 1");
                $stmtDetect->execute();
                $detectedTable = $stmtDetect->fetchColumn();
            } catch (Exception $__) {}
            $tablesToTry = $detectedTable ? [$detectedTable] : ['destino', 'destinos', 'clientes_externos', 'clientesexternos'];
            foreach ($tablesToTry as $table) {
                try {
                    $stmt = $this->db->prepare("SELECT Id FROM $table WHERE LOWER(Empresa) = LOWER(?) OR LOWER(Empresa) LIKE LOWER(?) LIMIT 1");
                    $stmt->execute([$v, '%' . $v . '%']);
                    $id = $stmt->fetchColumn();
                    if ($id && is_numeric($id)) {
                        error_log('[resolveDestinoToId] Resuelto texto "' . $v . '" -> ID=' . $id . ' (tabla: ' . $table . ')');
                        return (int)$id;
                    }
                } catch (Exception $eInner) {
                    // Tabla no existe, intentar siguiente
                    continue;
                }
            }
        } catch (Exception $e) {
            error_log('[resolveDestinoToId] Error: ' . $e->getMessage());
        }

        error_log('[resolveDestinoToId] No se pudo resolver texto a ID: "' . $v . '". Se usará el valor original.');
        return $v; // Devolver el texto original como fallback
    }

    /**
     * Sanitiza un valor de texto para la BD: trim y '' -> NULL
     */
    private function sanitizeStringOrNull($val)
    {
        if ($val === null) return null;
        $v = trim((string)$val);
        return $v === '' ? null : $v;
    }

    /**
     * Parsea un nombre completo (NombresApellidos) para extraer Nombres y ApellidoPaterno.
     * Estrategia: si hay 2+ palabras, la primera palabra va a Nombres y la(s) última(s) a ApellidoPaterno.
     * Si hay 3+ palabras, divide a la mitad: primera mitad a Nombres, segunda mitad a ApellidoPaterno.
     * @param string $fullName
     * @return array ['nombres' => string, 'apellido_paterno' => string]
     */
    private function parseNombreCompleto($fullName)
    {
        $fullName = trim((string)$fullName);
        if ($fullName === '') {
            return ['nombres' => '', 'apellido_paterno' => ''];
        }
        $parts = preg_split('/\s+/', $fullName);
        $count = count($parts);

        if ($count === 1) {
            // Solo una palabra: todo va a Nombres
            return ['nombres' => $parts[0], 'apellido_paterno' => ''];
        } elseif ($count === 2) {
            // "Nombre Apellido"
            return ['nombres' => $parts[0], 'apellido_paterno' => $parts[1]];
        } else {
            // 3+ palabras: dividir a la mitad
            $mid = (int)ceil($count / 2);
            $nombres = implode(' ', array_slice($parts, 0, $mid));
            $apellido = implode(' ', array_slice($parts, $mid));
            return ['nombres' => $nombres, 'apellido_paterno' => $apellido];
        }
    }

    /**
     * Para los campos enteros (FKs), si el valor proporcionado es NULL,
     * intenta recuperar el valor actual de la fila en la BD y lo devuelve.
     * $fields es un array asociativo [colName => value]
     */
    private function preserveIntsFromDb($id, array $fields)
    {
        // Determinar qué columnas necesitan preservación
        $toFetch = [];
        foreach ($fields as $col => $val) {
            if ($val === null) $toFetch[] = $col;
        }
        if (empty($toFetch)) return $fields;

        // Construir SELECT con las columnas necesarias
        $cols = implode(', ', array_map(function($c){ return $c; }, $toFetch));
        try {
            $stmt = $this->db->prepare("SELECT $cols FROM despachos_externos WHERE Id = ? LIMIT 1");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                foreach ($toFetch as $col) {
                    // Si la BD tiene un valor, úsalo; si no, dejar NULL
                    $fields[$col] = array_key_exists($col, $row) ? $row[$col] : $fields[$col];
                }
            }
        } catch (Exception $e) {
            error_log('Warning: preserveIntsFromDb failed: ' . $e->getMessage());
        }
        return $fields;
    }

    // Obtener el siguiente número de vale disponible para despachos externos
    public function obtenerSiguienteVale() {
        $sql = "SELECT IFNULL(MAX(NVale),0)+1 AS siguiente FROM despachos_externos";
        try {
            error_log('[DEBUG obtenerSiguienteVale model] preparando query...');
            $stmt = $this->db->prepare($sql);
            error_log('[DEBUG obtenerSiguienteVale model] ejecutando query...');
            $stmt->execute();
            error_log('[DEBUG obtenerSiguienteVale model] fetching resultado...');
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $siguiente = $row ? $row['siguiente'] : 1;
            error_log('[DEBUG obtenerSiguienteVale model] siguiente calculado: ' . $siguiente);
            return $siguiente;
        } catch (Exception $e) {
            error_log('[ERROR obtenerSiguienteVale model] Exception: ' . $e->getMessage());
            error_log('[ERROR obtenerSiguienteVale model] Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
    }

    // Guardar despacho externo (esqueleto)
    // Actualmente la funcionalidad completa de persistencia debe implementarse aquí.
    // Para evitar errores fatales cuando el controlador invoque este método, lanzamos
    // una excepción clara que será capturada por el controlador y devuelta como JSON.
    public function guardar($data) {
        // Implementación básica de guardado para despachos externos.
        // Inserta la cabecera en `despachos_externos` y los productos en `despachos_externos_productos`.
        try {
            // Validar entrada
            if (!is_array($data)) {
                throw new Exception('Payload inválido: se esperaba un array.');
            }

            // Preparar valores
            $correlativo = isset($data['correlativoVale']) ? $data['correlativoVale'] : '';
            // Limpiar a solo dígitos y quitar ceros a la izquierda
            $nvale = preg_replace('/[^0-9]/', '', (string)$correlativo);
            $nvale = ltrim($nvale, '0');
            if ($nvale === '') $nvale = null;

            $fecha = $data['fecha'] ?? date('Y-m-d');
            $hora = $data['hora'] ?? date('H:i:s');
            $turno = $data['turno'] ?? null;
            $destino = $data['destino'] ?? null;
            $ruc = $data['ruc'] ?? null;
            $direccion = $data['direccion'] ?? null;
            $despachador = $data['despachador'] ?? null;
            $chofer = $data['chofer'] ?? null;
            $licencia = $data['brevete'] ?? $data['licencia'] ?? null;
            $transportista = $data['transportista'] ?? null;
            $ruc_transportista = $data['ruc_transportista'] ?? null;
            $placa_tracto = $data['Placa_Tracto'] ?? $data['placa'] ?? null;
            $placa_carreta = $data['Placa_Carreta'] ?? null;
            $constancia_inscripcion = $data['Constancia_Inscripcion'] ?? null;
            $constancia_inscripcion_2 = $data['Constancia_Inscripcion_2'] ?? null;
            $gr = $data['guiaRemision'] ?? $data['GR'] ?? null; // Guía Remisión (campo opcional en la tabla)
            $creado_por = $_SESSION['user']['id'] ?? null;

            // Normalizar hora a formato HH:MM:SS o NULL
            $hora = $this->normalizeTimeForDb($hora);

            // Sanitizar ids/enteros para evitar enviar cadenas vacías a columnas INT
            $turno = $this->sanitizeIntOrNull($turno);
            $destinoSanitized = $this->sanitizeIntOrNull($destino);
            // Si el destino no es numérico después de sanitizar, intentar resolver texto a ID
            if ($destinoSanitized === null && $destino !== null && $destino !== '') {
                $destino = $this->resolveDestinoToId($destino);
            } else {
                $destino = $destinoSanitized;
            }
            // NOTE: despachador puede venir como nombre, id o vacío. Conservamos el valor original
            // en $despachadorRaw y manejamos el caso más abajo para mapear nombres a Ids.
            $despachadorRaw = $despachador;
            $chofer = $this->sanitizeIntOrNull($chofer);
            $transportista = $this->sanitizeIntOrNull($transportista);
            // Evitar enviar NULL a la columna Transportista si la columna no acepta NULL en la BD.
            // Usamos 0 como valor por defecto cuando no se proporciona transportista.
            if ($transportista === null) {
                $transportista = 0;
            }

            // Sanitizar strings ('' -> NULL)
            $ruc = $this->sanitizeStringOrNull($ruc);
            $direccion = $this->sanitizeStringOrNull($direccion);
            $licencia = $this->sanitizeStringOrNull($licencia);
            $ruc_transportista = $this->sanitizeStringOrNull($ruc_transportista);

            // Si no llegó RUC_Transportista, intentar recuperarlo desde la tabla transportistas
            // cuando se haya proporcionado un transportista válido. Si no se encuentra, usar
            // cadena vacía como fallback para evitar violaciones NOT NULL en la BD.
            if ($ruc_transportista === null) {
                if (!empty($transportista) && is_numeric($transportista) && $transportista > 0) {
                    try {
                        $stmtRuc = $this->db->prepare("SELECT RUC FROM transportistas WHERE Id = ? LIMIT 1");
                        $stmtRuc->execute([(int)$transportista]);
                        $rucFromDb = $stmtRuc->fetchColumn();
                        if ($rucFromDb !== false && $rucFromDb !== null) {
                            $ruc_transportista = trim((string)$rucFromDb);
                        } else {
                            $ruc_transportista = '';
                        }
                    } catch (Exception $e) {
                        error_log('Warning: no se pudo recuperar RUC del transportista: ' . $e->getMessage());
                        $ruc_transportista = '';
                    }
                } else {
                    $ruc_transportista = '';
                }
            }
            $placa_tracto = $this->sanitizeStringOrNull($placa_tracto);
            $placa_carreta = $this->sanitizeStringOrNull($placa_carreta);
            $constancia_inscripcion = $this->sanitizeStringOrNull($constancia_inscripcion);
            // Asegurar que no sea NULL para evitar violaciones NOT NULL en la BD
            if ($constancia_inscripcion === null) {
                $constancia_inscripcion = '';
            }
            // Sanitizar y asegurar que no sea NULL para evitar violaciones NOT NULL en la BD
            $constancia_inscripcion_2 = $this->sanitizeStringOrNull($constancia_inscripcion_2);
            if ($constancia_inscripcion_2 === null) {
                $constancia_inscripcion_2 = '';
            }
            $gr = $this->sanitizeStringOrNull($gr);

            // Normalizar/decidir valor de Despachador:
            // - Si no se proporcionó, usar al usuario logueado
            // - Si es numérico, usar ese id
            // - Si es nombre, intentar mapear a Id en `responsables`
            // - Si no se encuentra en responsables: buscarlo por username o partes del nombre
            // - Como último recurso: auto-registrar el usuario en responsables y usar ese Id
            error_log('[DIAG_DESPACHADOR_guardar] despachadorRaw=' . var_export($despachadorRaw, true) . ' | session[NombresApellidos]=' . var_export($_SESSION['user']['NombresApellidos'] ?? null, true) . ' | session[username]=' . var_export($_SESSION['user']['username'] ?? null, true) . ' | session[id]=' . var_export($_SESSION['user']['id'] ?? null, true));
            if ($despachadorRaw === null || $despachadorRaw === '') {
                error_log('[DIAG_DESPACHADOR_guardar] BRANCH 1: despachadorRaw es null/vacio');
                // Si no se envió despachador, buscar por session user id en responsables
                $stmtCheck = $this->db->prepare("SELECT Id FROM responsables WHERE Id = ? LIMIT 1");
                $stmtCheck->execute([$creado_por]);
                $existe = $stmtCheck->fetchColumn();
                if ($existe) {
                    $despachador = (int)$creado_por;
                } else {
                    // Intentar buscar por username en session
                    $username = $_SESSION['user']['username'] ?? null;
                    if ($username) {
                        $stmtU = $this->db->prepare("SELECT Id FROM responsables WHERE LOWER(NombresApellidos) LIKE LOWER(?) OR LOWER(username) = LOWER(?) LIMIT 1");
                        $stmtU->execute(["%$username%", $username]);
                        $foundU = $stmtU->fetchColumn();
                        if ($foundU) {
                            $despachador = (int)$foundU;
                        } else {
                            // No existe en responsables: auto-registrar
                            $nombreCompleto = $_SESSION['user']['NombresApellidos'] ?? $despachadorRaw ?? 'DESPACHADOR';
                            $parsed = $this->parseNombreCompleto($nombreCompleto);
                            error_log('[DIAG_DESPACHADOR_guardar] BRANCH1.1 Auto-register: nombreCompleto=' . var_export($nombreCompleto, true) . ' | parsed[nombres]=' . var_export($parsed['nombres'], true) . ' | parsed[apellido]=' . var_export($parsed['apellido_paterno'], true));
                            $stmtIns = $this->db->prepare("INSERT INTO responsables (ApellidoPaterno, Nombres, NombresApellidos) VALUES (?, ?, ?)");
                            $stmtIns->execute([$parsed['apellido_paterno'], $parsed['nombres'], $nombreCompleto]);
                            $despachador = (int)$this->db->lastInsertId();
                            error_log('[DESPACHADOR] Auto-registrado en responsables: ' . $nombreCompleto . ' -> Id=' . $despachador);
                        }
                    } else {
                        $nombreCompleto = $_SESSION['user']['NombresApellidos'] ?? $despachadorRaw ?? 'DESPACHADOR';
                        $parsed = $this->parseNombreCompleto($nombreCompleto);
                        error_log('[DIAG_DESPACHADOR_guardar] BRANCH1.2 Auto-register (no username): nombreCompleto=' . var_export($nombreCompleto, true) . ' | parsed[nombres]=' . var_export($parsed['nombres'], true));
                        $stmtIns = $this->db->prepare("INSERT INTO responsables (ApellidoPaterno, Nombres, NombresApellidos) VALUES (?, ?, ?)");
                        $stmtIns->execute([$parsed['apellido_paterno'], $parsed['nombres'], $nombreCompleto]);
                        $despachador = (int)$this->db->lastInsertId();
                        error_log('[DESPACHADOR] Auto-registrado en responsables: ' . $nombreCompleto . ' -> Id=' . $despachador);
                    }
                }
            } elseif (is_numeric($despachadorRaw)) {
                $despachador = (int)$despachadorRaw;
            } else {
                // Es texto (nombre completo): buscar en responsables
                error_log('[DIAG_DESPACHADOR_guardar] BRANCH 3: despachadorRaw es TEXTO: ' . var_export($despachadorRaw, true));
                try {
                    $stmtMap = $this->db->prepare("SELECT Id FROM responsables WHERE LOWER(NombresApellidos) = LOWER(?) LIMIT 1");
                    $stmtMap->execute([$despachadorRaw]);
                    $rowMap = $stmtMap->fetch(PDO::FETCH_ASSOC);
                    if ($rowMap && isset($rowMap['Id'])) {
                        $despachador = (int)$rowMap['Id'];
                    } else {
                        // No se encontró exacto: intentar búsqueda flexible por partes del nombre
                        $partes = preg_split('/\s+/', trim($despachadorRaw));
                        $foundFlex = false;
                        if (count($partes) > 1) {
                            // Buscar por el apellido (última parte) o nombre (primera parte)
                            $apellido = end($partes);
                            $nombre = reset($partes);
                            $stmtFlex = $this->db->prepare("SELECT Id FROM responsables WHERE LOWER(NombresApellidos) LIKE LOWER(?) OR LOWER(NombresApellidos) LIKE LOWER(?) LIMIT 1");
                            $stmtFlex->execute(["%$apellido%", "$nombre%"]);
                            $foundFlexId = $stmtFlex->fetchColumn();
                            if ($foundFlexId) {
                                $despachador = (int)$foundFlexId;
                                $foundFlex = true;
                            }
                        }
                        if (!$foundFlex) {
                            // No encontrado en responsables: auto-registrar
                            $nombreCompleto = $_SESSION['user']['NombresApellidos'] ?? $despachadorRaw;
                            $parsed = $this->parseNombreCompleto($nombreCompleto);
                            error_log('[DIAG_DESPACHADOR_guardar] BRANCH3 Auto-register: nombreCompleto=' . var_export($nombreCompleto, true) . ' | parsed[nombres]=' . var_export($parsed['nombres'], true));
                            $stmtIns = $this->db->prepare("INSERT INTO responsables (ApellidoPaterno, Nombres, NombresApellidos) VALUES (?, ?, ?)");
                            $stmtIns->execute([$parsed['apellido_paterno'], $parsed['nombres'], $nombreCompleto]);
                            $despachador = (int)$this->db->lastInsertId();
                            error_log('[DESPACHADOR] Auto-registrado en responsables (texto): ' . $nombreCompleto . ' -> Id=' . $despachador);
                        }
                    }
                } catch (Exception $e) {
                    error_log('[DIAG_DESPACHADOR_guardar] BRANCH3 EXCEPTION: ' . $e->getMessage());
                    // Error de consulta: intentar auto-registrar como último recurso
                    try {
                        $nombreCompleto = $_SESSION['user']['NombresApellidos'] ?? $despachadorRaw ?? 'DESPACHADOR';
                        $parsed = $this->parseNombreCompleto($nombreCompleto);
                        error_log('[DIAG_DESPACHADOR_guardar] BRANCH3 Fallback auto-register: nombreCompleto=' . var_export($nombreCompleto, true) . ' | parsed[nombres]=' . var_export($parsed['nombres'], true));
                        $stmtIns = $this->db->prepare("INSERT INTO responsables (ApellidoPaterno, Nombres, NombresApellidos) VALUES (?, ?, ?)");
                        $stmtIns->execute([$parsed['apellido_paterno'], $parsed['nombres'], $nombreCompleto]);
                        $despachador = (int)$this->db->lastInsertId();
                        error_log('[DESPACHADOR] Auto-registrado en responsables (exception fallback): ' . $nombreCompleto . ' -> Id=' . $despachador);
                    } catch (Exception $e2) {
                        error_log('[DIAG_DESPACHADOR_guardar] BRANCH3 FALLBACK ALSO FAILED: ' . $e2->getMessage());
                        throw new Exception('No se pudo determinar el Despachador. Error al buscar/crear en responsables: ' . $e2->getMessage());
                    }
                }
            }

            $productos = isset($data['productos']) && is_array($data['productos']) ? $data['productos'] : [];

            // Iniciar transacción
            $this->db->beginTransaction();

            // Insertar cabecera
            $sql = "INSERT INTO despachos_externos
                (NVale, Fecha, Hora, Turno, Destino, RUC, Direccion, Despachador, Chofer, Licencia, Transportista, RUC_Transportista, Placa_Tracto, Placa_Carreta, Constancia_Inscripcion, Constancia_Inscripcion_2, GR, creado_por, estado, ip_creacion)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $estado = 'ACTIVO';
            $ip_creacion = $_SERVER['REMOTE_ADDR'] ?? null;
            $stmt->execute([
                $nvale,
                $fecha,
                $hora,
                $turno,
                $destino,
                $ruc,
                $direccion,
                $despachador,
                $chofer,
                $licencia,
                $transportista,
                $ruc_transportista,
                $placa_tracto,
                $placa_carreta,
                $constancia_inscripcion,
                $constancia_inscripcion_2,
                $gr,
                $creado_por,
                $estado,
                $ip_creacion
            ]);

            $despachoId = $this->db->lastInsertId();

            if (!$despachoId) {
                $this->db->rollBack();
                throw new Exception('No se pudo obtener ID del despacho insertado.');
            }

            // Insertar productos si existen
            if (!empty($productos)) {
                $sqlProd = "INSERT INTO despachos_externos_productos (DespachoId, CodigoProducto, DescripcionProducto, UnidadMedida, Cantidad, Comentarios) VALUES (?, ?, ?, ?, ?, ?)";
                $stmtProd = $this->db->prepare($sqlProd);
                foreach ($productos as $i => $p) {
                    // Mapear nombres (soportar tanto minúsculas como mayúsculas)
                    $codigo = $p['codigo'] ?? $p['Codigo'] ?? '';
                    $descripcion = $p['producto'] ?? $p['Producto'] ?? '';
                    $unidad = $p['unidadMedida'] ?? $p['UnidadMedida'] ?? '';
                    $cantidad = isset($p['cantidad']) ? $p['cantidad'] : (isset($p['Cantidad']) ? $p['Cantidad'] : 0);
                    $comentarios = $p['comentarios'] ?? $p['Comentarios'] ?? '';

                    // Tipos
                    $cantidad = is_numeric($cantidad) ? $cantidad : 0;

                    $stmtProd->execute([
                        $despachoId,
                        $codigo,
                        $descripcion,
                        $unidad,
                        $cantidad,
                        $comentarios
                    ]);
                }
            }

            $this->db->commit();
            return $despachoId;
        } catch (Exception $e) {
            try { $this->db->rollBack(); } catch (Exception $__) {}
            error_log('Error en DespachoExterno::guardar - ' . $e->getMessage());
            throw $e;
        }
    }
    // Actualizar productos de un despacho externo
    public function actualizarProductosDespacho($despachoId, $productos) {
        try {
            // Validar datos de entrada
            if (!is_numeric($despachoId) || $despachoId <= 0) {
                error_log('Error: ID de despacho externo inválido: ' . $despachoId);
                throw new Exception('ID de despacho externo inválido: ' . $despachoId);
            }
            if (!is_array($productos)) {
                error_log('Error: El parámetro productos debe ser un array');
                throw new Exception('El parámetro productos debe ser un array');
            }
            error_log("Actualizando productos para despacho externo ID: $despachoId - Total productos: " . count($productos));
            // Verificar primero si el despacho existe
            $sqlCheck = "SELECT Id FROM despachos_externos WHERE Id = ?";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->execute([$despachoId]);
            $despacho = $stmtCheck->fetch();
            if (!$despacho) {
                error_log("Error: El despacho externo con ID $despachoId no existe en la base de datos");
                throw new Exception("El despacho externo con ID $despachoId no existe");
            }
            // Comenzar transacción sólo si no hay una activa (evita "There is already an active transaction")
            $startedTransaction = false;
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $startedTransaction = true;
                error_log("Transacción iniciada para actualizar productos del despacho externo ID: $despachoId");
            } else {
                error_log("Usando transacción existente para actualizar productos del despacho externo ID: $despachoId");
            }
            try {
                // Eliminar productos existentes
                $sqlDelete = "DELETE FROM despachos_externos_productos WHERE DespachoId = ?";
                $stmtDelete = $this->db->prepare($sqlDelete);
                $stmtDelete->execute([$despachoId]);
                $countDeleted = $stmtDelete->rowCount();
                error_log("Productos eliminados: $countDeleted");
                // Insertar productos actualizados
                if (!empty($productos)) {
                    $sqlInsert = "INSERT INTO despachos_externos_productos (DespachoId, CodigoProducto, DescripcionProducto, UnidadMedida, Cantidad, Comentarios) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmtInsert = $this->db->prepare($sqlInsert);
                    foreach ($productos as $index => $producto) {
                            // Normalizar keys de producto para aceptar distintos formatos desde el frontend
                            $codigo = $producto['Codigo'] ?? $producto['codigo'] ?? $producto['CodigoProducto'] ?? $producto['codigoProducto'] ?? $producto['Codigo_producto'] ?? '';
                            if (empty($codigo)) {
                                error_log("Error: Código de producto faltante en índice $index - producto: " . json_encode($producto));
                                throw new Exception("Código de producto faltante en índice $index");
                            }
                            error_log("Insertando producto externo $index: " . json_encode($producto));
                            // Normalizar otros campos comunes
                            $nombreProducto = $producto['Producto'] ?? $producto['producto'] ?? $producto['Descripcion'] ?? $producto['descripcion'] ?? $producto['DescripcionProducto'] ?? '';
                            $unidadMedida = $producto['UnidadMedida'] ?? $producto['unidadMedida'] ?? $producto['unidad'] ?? '';
                            $cantidadRaw = $producto['Cantidad'] ?? $producto['cantidad'] ?? 0;
                            $cantidad = is_numeric($cantidadRaw) ? (float)$cantidadRaw : 0;
                            $comentarios = isset($producto['Comentarios']) ? strtoupper($producto['Comentarios']) : (isset($producto['comentarios']) ? strtoupper($producto['comentarios']) : '');
                        try {
                            $stmtInsert->execute([
                                $despachoId,
                                $codigo,
                                $nombreProducto,
                                $unidadMedida,
                                $cantidad,
                                $comentarios
                            ]);
                            error_log("Producto externo $index insertado correctamente: $codigo");
                        } catch (PDOException $pdoEx) {
                            error_log("Error al insertar producto externo $index: " . $pdoEx->getMessage());
                            throw new Exception("Error al insertar producto externo $index: " . $pdoEx->getMessage());
                        }
                    }
                } else {
                    error_log("No hay productos para insertar");
                }
                // Confirmar la transacción sólo si la iniciamos aquí
                if ($startedTransaction) {
                    $this->db->commit();
                    error_log("Transacción completada: productos actualizados para despacho externo ID: $despachoId");
                } else {
                    error_log("Productos actualizados dentro de la transacción existente para despacho externo ID: $despachoId");
                }
                return true;
            } catch (Exception $e) {
                // Revertir en caso de error sólo si iniciamos la transacción aquí
                if ($startedTransaction) {
                    $this->db->rollBack();
                    error_log("Error en la transacción, revertida: " . $e->getMessage());
                } else {
                    error_log("Error actualizando productos dentro de transacción existente: " . $e->getMessage());
                }
                throw $e;
            }
        } catch (Exception $e) {
            error_log('Error en actualizarProductosDespacho externo: ' . $e->getMessage());
            throw $e;
        }
    }
    // Reactivar un despacho externo
    public function reactivar($id, $reactivado_por) {
        $sql = "UPDATE despachos_externos SET estado = 'ACTIVO', anulado_por = NULL, anulado_en = NULL, motivo_anulacion = NULL WHERE Id = ? AND estado = 'ANULADO'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) {
            throw new Exception('No se pudo reactivar el despacho (no está anulado o no existe).');
        }
        return true;
    }

    // Anular un despacho externo
    public function anular($id, $anulado_por, $motivo = '') {
        $sql = "UPDATE despachos_externos SET estado = 'ANULADO', anulado_por = ?, anulado_en = NOW(), motivo_anulacion = ? WHERE Id = ? AND LOWER(estado) != 'anulado'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$anulado_por, $motivo, $id]);
        if ($stmt->rowCount() === 0) {
            throw new Exception('No se pudo anular el despacho (ya está anulado o no existe).');
        }
        return true;
    }

    // Obtener un despacho por ID con todos sus datos
    public function getById($id) {
        try {
            // Debug: log incoming id and type to help diagnosticar por qué no se encuentra el registro
            try{ error_log('[DEBUG model getById] called with id=' . var_export($id, true) . ' (is_numeric=' . (is_numeric($id) ? '1' : '0') . ', type=' . gettype($id) . ')'); }catch(Exception $__){}
            
            // Intentar primero con JOINs completos
            $sql = "SELECT de.*, 
                    t.Turno as TurnoTexto, 
                    r1.NombresApellidos as DespachadorTexto,
                    dest.Empresa as DestinoTexto,
                    dest.RUC as DestinoRUC,
                    c.ApellidosNombres as ChoferTexto,
                    tr.Empresa as TransportistaTexto,
                    tr.RUC as TransportistaRUC
                FROM despachos_externos de
                LEFT JOIN turnos t ON de.Turno = t.Id
                LEFT JOIN responsables r1 ON de.Despachador = r1.Id
                LEFT JOIN destino dest ON de.Destino = dest.Id
                LEFT JOIN choferes c ON de.Chofer = c.Id
                LEFT JOIN transportistas tr ON de.Transportista = tr.Id
                WHERE de.Id = ?";
            
            $despacho = null;
            try{
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$id]);
                $despacho = $stmt->fetch(PDO::FETCH_ASSOC);
            }catch(PDOException $pde){
                error_log('[DEBUG model getById] Query with JOINs failed, trying fallback without destino: ' . $pde->getMessage());
                // Fallback: query sin tabla destino (que puede no existir en todas las BD)
                $sqlFallback = "SELECT de.*, 
                        t.Turno as TurnoTexto, 
                        r1.NombresApellidos as DespachadorTexto,
                        c.ApellidosNombres as ChoferTexto,
                        tr.Empresa as TransportistaTexto,
                        tr.RUC as TransportistaRUC
                    FROM despachos_externos de
                    LEFT JOIN turnos t ON de.Turno = t.Id
                    LEFT JOIN responsables r1 ON de.Despachador = r1.Id
                    LEFT JOIN choferes c ON de.Chofer = c.Id
                    LEFT JOIN transportistas tr ON de.Transportista = tr.Id
                    WHERE de.Id = ?";
                try {
                    $stmt = $this->db->prepare($sqlFallback);
                    $stmt->execute([$id]);
                    $despacho = $stmt->fetch(PDO::FETCH_ASSOC);
                } catch(PDOException $pde2) {
                    error_log('[ERROR model getById] Fallback query also failed: ' . $pde2->getMessage());
                    return null;
                }
            }
            
            if (!$despacho) {
                error_log('[DEBUG model getById] no despacho found for id=' . var_export($id, true));
                return null;
            }
            
            $sqlProd = "SELECT * FROM despachos_externos_productos WHERE DespachoId = ?";
            $stmtProd = $this->db->prepare($sqlProd);
            $stmtProd->execute([$id]);
            $productos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
            $despacho['Productos'] = $productos;
            return $despacho;
        } catch (Exception $e) {
            error_log('Error obteniendo despacho por ID: ' . $e->getMessage());
            return null;
        }
    }

    // Actualizar un producto específico de un despacho externo
    public function actualizarProductoEspecifico($despachoId, $codigoProducto, $nombreProducto, $cantidad, $comentarios, $unidadMedida = '')
    {
        try {
            $this->db->beginTransaction();
            error_log("Actualizando producto específico: despachoId=$despachoId, codigo=$codigoProducto, cantidad=$cantidad, unidadMedida=$unidadMedida");
            if (empty($despachoId) || empty($codigoProducto)) {
                error_log("Faltan datos importantes para actualizar el producto");
                throw new Exception("Datos insuficientes para actualizar el producto");
            }
            $stmtCheckDespacho = $this->db->prepare("SELECT Id FROM despachos_externos WHERE Id = :despachoId");
            $stmtCheckDespacho->bindParam(':despachoId', $despachoId);
            $stmtCheckDespacho->execute();
            if ($stmtCheckDespacho->rowCount() === 0) {
                error_log("Despacho con ID $despachoId no encontrado");
                throw new Exception("Despacho no encontrado");
            }
            $stmtCheck = $this->db->prepare(
                "SELECT Id, UnidadMedida FROM despachos_externos_productos 
                WHERE DespachoId = :despachoId AND CodigoProducto = :codigoProducto"
            );
            $stmtCheck->bindParam(':despachoId', $despachoId);
            $stmtCheck->bindParam(':codigoProducto', $codigoProducto);
            $stmtCheck->execute();
            if ($stmtCheck->rowCount() > 0) {
                $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                $productoId = $row['Id'];
                $existingUdm = $row['UnidadMedida'];
                if (empty($unidadMedida) && !empty($existingUdm)) {
                    $unidadMedida = $existingUdm;
                    error_log("Usando unidad de medida existente: $unidadMedida");
                }
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
                $updateSql = "UPDATE despachos_externos_productos 
                              SET Cantidad = :cantidad, 
                                  Comentarios = :comentarios";
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
                error_log("El producto no existe en este despacho, creando nuevo");
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
                    "INSERT INTO despachos_externos_productos 
                     (DespachoId, CodigoProducto, DescripcionProducto, Cantidad, UnidadMedida, Comentarios) 
                     VALUES 
                     (:despachoId, :codigoProducto, :nombreProducto, :cantidad, :unidadMedida, :comentarios)"
                );
                $stmtInsert->bindParam(':despachoId', $despachoId);
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

    // Actualizar un despacho externo
    public function actualizarDespacho($id, $data) {
        // Ajustar actualización para la tabla despachos_externos (columnas reales)
        // Incrementar el contador de modificaciones
        $sql = "UPDATE despachos_externos SET Fecha = ?, Hora = ?, Turno = ?, Destino = ?, RUC = ?, Direccion = ?, Despachador = ?, Chofer = ?, Licencia = ?, Transportista = ?, RUC_Transportista = ?, Placa_Tracto = ?, Placa_Carreta = ?, Constancia_Inscripcion = ?, GR = ?, modificaciones_count = modificaciones_count + 1 WHERE Id = ?";
        $stmt = $this->db->prepare($sql);

        // Normalizar la hora para evitar valores inválidos que provoquen errores MySQL
        $horaInput = $data['Hora'] ?? $data['hora'] ?? null;
        $hora = $this->normalizeTimeForDb($horaInput);

        // Si la hora es NULL y la columna no acepta NULL, preservamos el valor actual de la BD
        if ($hora === null) {
            try {
                $stmtCur = $this->db->prepare("SELECT Hora FROM despachos_externos WHERE Id = ? LIMIT 1");
                $stmtCur->execute([$id]);
                $currentHora = $stmtCur->fetchColumn();
                if ($currentHora === false || $currentHora === null) {
                    // Fallback seguro
                    $hora = '00:00:00';
                } else {
                    $hora = $currentHora;
                }
            } catch (Exception $e) {
                // En caso de error al obtener la hora actual, usar un fallback válido
                error_log('Warning: no se pudo obtener Hora actual, usando fallback 00:00:00 - ' . $e->getMessage());
                $hora = '00:00:00';
            }
        }

        // Preparar y sanitizar campos antes de ejecutar
        $fecha = $this->sanitizeStringOrNull($data['Fecha'] ?? $data['fecha'] ?? null);
        $turno = $this->sanitizeIntOrNull($data['Turno'] ?? $data['turno'] ?? null);
        $destinoRaw = $data['Destino'] ?? $data['destino'] ?? null;
        $destinoSanitized = $this->sanitizeIntOrNull($destinoRaw);
        if ($destinoSanitized === null && $destinoRaw !== null && $destinoRaw !== '') {
            $destino = $this->resolveDestinoToId($destinoRaw);
        } else {
            $destino = $destinoSanitized;
        }
        $ruc = $this->sanitizeStringOrNull($data['RUC'] ?? $data['ruc'] ?? null);
        $direccion = $this->sanitizeStringOrNull($data['Direccion'] ?? $data['direccion'] ?? null);
        $despachador = $this->sanitizeIntOrNull($data['Despachador'] ?? $data['despachador'] ?? null);
        $chofer = $this->sanitizeIntOrNull($data['Chofer'] ?? $data['chofer'] ?? null);
        $licencia = $this->sanitizeStringOrNull($data['Licencia'] ?? $data['licencia'] ?? $data['brevete'] ?? null);
        $transportista = $this->sanitizeIntOrNull($data['Transportista'] ?? $data['transportista'] ?? null);
        $ruc_transportista = $this->sanitizeStringOrNull($data['RUC_Transportista'] ?? $data['ruc_transportista'] ?? null);
        $placa_tracto = $this->sanitizeStringOrNull($data['Placa_Tracto'] ?? $data['Placa'] ?? $data['placa'] ?? null);
        $placa_carreta = $this->sanitizeStringOrNull($data['Placa_Carreta'] ?? null);
        $constancia_inscripcion = $this->sanitizeStringOrNull($data['Constancia_Inscripcion'] ?? null);
        // Asegurar que no sea NULL para evitar violaciones NOT NULL en la BD
        if ($constancia_inscripcion === null) {
            $constancia_inscripcion = '';
        }
        $gr = $this->sanitizeStringOrNull($data['GR'] ?? $data['guiaRemision'] ?? null);

        // Para campos enteros que queden NULL, intentar preservar el valor actual en la BD
        $ints = $this->preserveIntsFromDb($id, [
            'Turno' => $turno,
            'Destino' => $destino,
            'Despachador' => $despachador,
            'Chofer' => $chofer,
            'Transportista' => $transportista
        ]);

        $turno = $ints['Turno'];
        $destino = $ints['Destino'];
        $despachador = $ints['Despachador'];
        $chofer = $ints['Chofer'];
        $transportista = $ints['Transportista'];

        return $stmt->execute([
            $fecha,
            $hora,
            $turno,
            $destino,
            $ruc,
            $direccion,
            $despachador,
            $chofer,
            $licencia,
            $transportista,
            $ruc_transportista,
            $placa_tracto,
            $placa_carreta,
            $constancia_inscripcion,
            $gr,
            $id
        ]);
    }

    // Modificar despacho externo (cabecera + productos)
    public function modificar($data) {
        try {
            if (!is_array($data)) throw new Exception('Payload inválido para modificar despacho externo.');
            $id = $data['Id'] ?? $data['id'] ?? null;
            if (!$id || !is_numeric($id)) throw new Exception('ID de despacho inválido para modificación.');

            // Mapear campos principales
            $fecha = $data['fecha'] ?? $data['Fecha'] ?? null;
            $horaRaw = $data['hora'] ?? $data['Hora'] ?? null;
            $hora = $this->normalizeTimeForDb($horaRaw);
            $turno = $data['turno'] ?? $data['Turno'] ?? null;
            $destino = $data['destino'] ?? $data['Destino'] ?? null;
            $ruc = $data['ruc'] ?? $data['RUC'] ?? null;
            $direccion = $data['direccion'] ?? $data['Direccion'] ?? null;
            $despachador = $data['despachador'] ?? $data['Despachador'] ?? null;
            $chofer = $data['chofer'] ?? $data['Chofer'] ?? null;
            $licencia = $data['brevete'] ?? $data['licencia'] ?? $data['Licencia'] ?? null;
            $transportista = $data['transportista'] ?? $data['Transportista'] ?? null;
            $ruc_transportista = $data['ruc_transportista'] ?? $data['RUC_Transportista'] ?? null;
            $placa_tracto = $data['Placa_Tracto'] ?? $data['placa'] ?? $data['Placa'] ?? null;
            $placa_carreta = $data['Placa_Carreta'] ?? null;
            $constancia_inscripcion = $data['Constancia_Inscripcion'] ?? null;
            // Asegurar que no sea NULL para evitar violaciones NOT NULL en la BD
            if ($constancia_inscripcion === null || $constancia_inscripcion === '') {
                $constancia_inscripcion = '';
            }
            $constancia_inscripcion_2 = $data['Constancia_Inscripcion_2'] ?? null;
            // Asegurar que no sea NULL para evitar violaciones NOT NULL en la BD
            if ($constancia_inscripcion_2 === null || $constancia_inscripcion_2 === '') {
                $constancia_inscripcion_2 = '';
            }
            $gr = $data['guiaRemision'] ?? $data['GR'] ?? null;

            // Normalizar despachador (si llega nombre, intentar mapear a id en responsables)
            // Si no se encuentra en responsables: auto-registrar al usuario como responsable
            $creado_por = $_SESSION['user']['id'] ?? null;
            error_log('[DIAG_DESPACHADOR_modificar] despachador=' . var_export($despachador, true) . ' | session[NombresApellidos]=' . var_export($_SESSION['user']['NombresApellidos'] ?? null, true) . ' | session[username]=' . var_export($_SESSION['user']['username'] ?? null, true) . ' | session[id]=' . var_export($_SESSION['user']['id'] ?? null, true));
            if ($despachador !== null && !is_numeric($despachador)) {
                error_log('[DIAG_DESPACHADOR_modificar] BRANCH A: despachador es texto');
                try {
                    $stmtMap = $this->db->prepare("SELECT Id FROM responsables WHERE LOWER(NombresApellidos) = LOWER(?) LIMIT 1");
                    $stmtMap->execute([$despachador]);
                    $rowMap = $stmtMap->fetch(PDO::FETCH_ASSOC);
                    if ($rowMap && isset($rowMap['Id'])) {
                        $despachador = (int)$rowMap['Id'];
                    } else {
                        // No se encontró exacto: búsqueda flexible por partes del nombre
                        $partes = preg_split('/\s+/', trim($despachador));
                        $foundFlex = false;
                        if (count($partes) > 1) {
                            $apellido = end($partes);
                            $nombre = reset($partes);
                            $stmtFlex = $this->db->prepare("SELECT Id FROM responsables WHERE LOWER(NombresApellidos) LIKE LOWER(?) OR LOWER(NombresApellidos) LIKE LOWER(?) LIMIT 1");
                            $stmtFlex->execute(["%$apellido%", "$nombre%"]);
                            $foundFlexId = $stmtFlex->fetchColumn();
                            if ($foundFlexId) {
                                $despachador = (int)$foundFlexId;
                                $foundFlex = true;
                            }
                        }
                        if (!$foundFlex) {
                            // No encontrado en responsables: auto-registrar
                            $nombreCompleto = $_SESSION['user']['NombresApellidos'] ?? $despachador;
                            $parsed = $this->parseNombreCompleto($nombreCompleto);
                            error_log('[DIAG_DESPACHADOR_modificar] BRANCH A Auto-register: nombreCompleto=' . var_export($nombreCompleto, true) . ' | parsed[nombres]=' . var_export($parsed['nombres'], true));
                            $stmtIns = $this->db->prepare("INSERT INTO responsables (ApellidoPaterno, Nombres, NombresApellidos) VALUES (?, ?, ?)");
                            $stmtIns->execute([$parsed['apellido_paterno'], $parsed['nombres'], $nombreCompleto]);
                            $despachador = (int)$this->db->lastInsertId();
                            error_log('[DESPACHADOR_modificar] Auto-registrado en responsables: ' . $nombreCompleto . ' -> Id=' . $despachador);
                        }
                    }
                } catch (Exception $e) {
                    error_log('[DIAG_DESPACHADOR_modificar] BRANCH A EXCEPTION: ' . $e->getMessage());
                    // Error de consulta: intentar auto-registrar como último recurso
                    try {
                        $nombreCompleto = $_SESSION['user']['NombresApellidos'] ?? $despachador ?? 'DESPACHADOR';
                        $parsed = $this->parseNombreCompleto($nombreCompleto);
                        error_log('[DIAG_DESPACHADOR_modificar] BRANCH A Fallback: nombreCompleto=' . var_export($nombreCompleto, true) . ' | parsed[nombres]=' . var_export($parsed['nombres'], true));
                        $stmtIns = $this->db->prepare("INSERT INTO responsables (ApellidoPaterno, Nombres, NombresApellidos) VALUES (?, ?, ?)");
                        $stmtIns->execute([$parsed['apellido_paterno'], $parsed['nombres'], $nombreCompleto]);
                        $despachador = (int)$this->db->lastInsertId();
                        error_log('[DESPACHADOR_modificar] Auto-registrado en responsables (exception): ' . $nombreCompleto . ' -> Id=' . $despachador);
                    } catch (Exception $e2) {
                        error_log('[DIAG_DESPACHADOR_modificar] BRANCH A FALLBACK ALSO FAILED: ' . $e2->getMessage());
                        throw new Exception('No se pudo determinar el Despachador. Error al crear en responsables: ' . $e2->getMessage());
                    }
                }
            } elseif ($despachador === null) {
                error_log('[DIAG_DESPACHADOR_modificar] BRANCH B: despachador es null');
                // Si es null, buscar por session user id en responsables
                $stmtCheck = $this->db->prepare("SELECT Id FROM responsables WHERE Id = ? LIMIT 1");
                $stmtCheck->execute([$creado_por]);
                $existe = $stmtCheck->fetchColumn();
                if ($existe) {
                    $despachador = (int)$creado_por;
                } else {
                    // Auto-registrar con nombre de sesión
                    $nombreCompleto = $_SESSION['user']['NombresApellidos'] ?? 'DESPACHADOR';
                    $parsed = $this->parseNombreCompleto($nombreCompleto);
                    error_log('[DIAG_DESPACHADOR_modificar] BRANCH B Auto-register: nombreCompleto=' . var_export($nombreCompleto, true) . ' | parsed[nombres]=' . var_export($parsed['nombres'], true));
                    $stmtIns = $this->db->prepare("INSERT INTO responsables (ApellidoPaterno, Nombres, NombresApellidos) VALUES (?, ?, ?)");
                    $stmtIns->execute([$parsed['apellido_paterno'], $parsed['nombres'], $nombreCompleto]);
                    $despachador = (int)$this->db->lastInsertId();
                    error_log('[DESPACHADOR_modificar] Auto-registrado en responsables (null): ' . $nombreCompleto . ' -> Id=' . $despachador);
                }
            }
            // Si es numérico se usa directamente (ya está como int)

            // Iniciar transacción
            $this->db->beginTransaction();

            // Actualizar cabecera e incrementar contador de modificaciones
            $sql = "UPDATE despachos_externos SET Fecha = ?, Hora = ?, Turno = ?, Destino = ?, RUC = ?, Direccion = ?, Despachador = ?, Chofer = ?, Licencia = ?, Transportista = ?, RUC_Transportista = ?, Placa_Tracto = ?, Placa_Carreta = ?, Constancia_Inscripcion = ?, Constancia_Inscripcion_2 = ?, GR = ?, modificaciones_count = modificaciones_count + 1 WHERE Id = ?";
            // Si la hora es null, intentar preservar la existente en la BD (para evitar constraint NOT NULL)
            if ($hora === null) {
                try {
                    $stmtCur = $this->db->prepare("SELECT Hora FROM despachos_externos WHERE Id = ? LIMIT 1");
                    $stmtCur->execute([$id]);
                    $currentHora = $stmtCur->fetchColumn();
                    if ($currentHora === false || $currentHora === null) {
                        $hora = '00:00:00';
                    } else {
                        $hora = $currentHora;
                    }
                } catch (Exception $e) {
                    error_log('Warning: no se pudo obtener Hora actual en modificar(), usando fallback 00:00:00 - ' . $e->getMessage());
                    $hora = '00:00:00';
                }
            }

            // Sanitizar campos antes de ejecutar (evitar '' en columnas int)
            $fecha = $this->sanitizeStringOrNull($fecha);
            $turno = $this->sanitizeIntOrNull($turno);
            $destinoRaw = $destino; // guardar valor original antes de sanitizar
            $destinoSanitized = $this->sanitizeIntOrNull($destino);
            if ($destinoSanitized === null && $destinoRaw !== null && $destinoRaw !== '') {
                $destino = $this->resolveDestinoToId($destinoRaw);
            } else {
                $destino = $destinoSanitized;
            }
            $ruc = $this->sanitizeStringOrNull($ruc);
            if ($ruc === null) $ruc = '';
            $direccion = $this->sanitizeStringOrNull($direccion);
            if ($direccion === null) $direccion = '';
            $despachador = $this->sanitizeIntOrNull($despachador);
            $chofer = $this->sanitizeIntOrNull($chofer);
            $licencia = $this->sanitizeStringOrNull($licencia);
            if ($licencia === null) $licencia = '';
            $transportista = $this->sanitizeIntOrNull($transportista);
            $ruc_transportista = $this->sanitizeStringOrNull($ruc_transportista);
            if ($ruc_transportista === null) $ruc_transportista = '';
            $placa_tracto = $this->sanitizeStringOrNull($placa_tracto);
            if ($placa_tracto === null) $placa_tracto = '';
            $placa_carreta = $this->sanitizeStringOrNull($placa_carreta);
            if ($placa_carreta === null) $placa_carreta = '';
            $constancia_inscripcion = $this->sanitizeStringOrNull($constancia_inscripcion);
            if ($constancia_inscripcion === null) {
                $constancia_inscripcion = '';
            }
            $constancia_inscripcion_2 = $this->sanitizeStringOrNull($constancia_inscripcion_2);
            if ($constancia_inscripcion_2 === null) {
                $constancia_inscripcion_2 = '';
            }
            $gr = $this->sanitizeStringOrNull($gr);
            if ($gr === null) $gr = '';
            // Preservar valores enteros en BD si vinieron como NULL
            $ints = $this->preserveIntsFromDb($id, [
                'Turno' => $turno,
                'Destino' => $destino,
                'Despachador' => $despachador,
                'Chofer' => $chofer,
                'Transportista' => $transportista
            ]);
            $turno = $ints['Turno'];
            $destino = $ints['Destino'];
            $despachador = $ints['Despachador'];
            $chofer = $ints['Chofer'];
            $transportista = $ints['Transportista'];

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $fecha,
                $hora,
                $turno,
                $destino,
                $ruc,
                $direccion,
                $despachador,
                $chofer,
                $licencia,
                $transportista,
                $ruc_transportista,
                $placa_tracto,
                $placa_carreta,
                $constancia_inscripcion,
                $constancia_inscripcion_2,
                $gr,
                $id
            ]);

            // Actualizar productos: eliminar existentes e insertar nuevos (si vienen)
            $productos = isset($data['productos']) && is_array($data['productos']) ? $data['productos'] : [];
            // Reutilizamos el helper ya existente
            $this->actualizarProductosDespacho((int)$id, $productos);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            try { $this->db->rollBack(); } catch (Exception $__) {}
            error_log('Error en DespachoExterno::modificar - ' . $e->getMessage());
            throw $e;
        }
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
                $sql = "SELECT modificaciones_count, creado_en FROM despachos_externos WHERE Id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    $creadoEn = $result['creado_en'] ?? null;
                }
            } catch (Exception $eInner) {
                // Si falla por columna creado_en, intentar sin ella
                $sql = "SELECT modificaciones_count FROM despachos_externos WHERE Id = ?";
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
            error_log('Error en DespachoExterno::puedeModificar: ' . $e->getMessage());
            return [
                'puede' => false,
                'modificaciones' => 0,
                'restantes' => 0,
                'mensaje' => 'Error al verificar modificaciones: ' . $e->getMessage()
            ];
        }
    }

    // Registrar en el historial quién hizo la modificación
    public function registrarModificacion($despachoId, $usuarioId, $ip = null) {
        // Obtener el valor actual de modificaciones_count (ya fue incrementado por modificar())
        $sql = "SELECT modificaciones_count FROM despachos_externos WHERE Id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$despachoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $nModificacion = $row ? (int)$row['modificaciones_count'] : 0;

        $sql = "INSERT INTO despachos_externos_modificaciones (DespachoId, UsuarioId, NModificacion, IpAddress) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$despachoId, $usuarioId, $nModificacion, $ip]);
    }

    // Actualizar productos de un despacho externo

    // Reporte de despachos externos con filtros y paginación
    public function getReporte($filtros = [], $limit = 20, $offset = 0) {
        // Adaptado desde DespachoInterno::getReporte
        $limit = (int)$limit;
        $offset = (int)$offset;
        $filtros = is_array($filtros) ? $filtros : [];

        // Detectar tabla de destino correcta (puede ser destino, destinos, clientes_externos, etc.)
        $destinoTable = 'destino';
        try {
            $stmtDetect = $this->db->prepare("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND (table_name LIKE '%destin%' OR table_name LIKE '%cliente%') LIMIT 1");
            $stmtDetect->execute();
            $detected = $stmtDetect->fetchColumn();
            if ($detected) {
                $destinoTable = $detected;
            }
        } catch (Exception $__) {}
        error_log('[getReporte] Usando tabla destino: ' . $destinoTable);

        $where = [];
        $params = [];

        if (!empty($filtros['N° Vale'])) {
            $where[] = 'de.NVale LIKE ?';
            $params[] = '%' . $filtros['N° Vale'] . '%';
        }
        if (!empty($filtros['fechaDesde'])) {
            // Aceptar 'YYYY-MM-DD' o 'YYYY-MM-DD HH:MM:SS'
            $fd = $filtros['fechaDesde'];
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fd)) {
                $fd = $fd . ' 00:00:00';
            }
            $where[] = 'de.Fecha >= ?';
            $params[] = $fd;
        }
        if (!empty($filtros['fechaHasta'])) {
            // Incluir hasta el final del día si se pasa sólo la fecha
            $fh = $filtros['fechaHasta'];
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fh)) {
                $fh = $fh . ' 23:59:59';
            }
            $where[] = 'de.Fecha <= ?';
            $params[] = $fh;
        }
        if (!empty($filtros['Fecha'])) {
            $fecha = $filtros['Fecha'];
            // La UI puede enviar formato 'dd-mm-yyyy'
            if (strpos($fecha, '-') !== false) {
                $partes = explode('-', $fecha);
                if (count($partes) === 3 && strlen($partes[2]) === 4) {
                    $fecha = "{$partes[2]}-{$partes[1]}-{$partes[0]}";
                }
            }
            // Filtrar por la parte fecha (ignorar la hora) para incluir todos los registros del día
            $where[] = 'DATE(de.Fecha) = ?';
            $params[] = $fecha;
        }
        if (!empty($filtros['Hora'])) {
            $where[] = 'de.Hora LIKE ?';
            $params[] = '%' . $filtros['Hora'] . '%';
        }
        if (!empty($filtros['turnoId'])) {
            $where[] = 'de.Turno = ?';
            $params[] = $filtros['turnoId'];
        }
        if (!empty($filtros['Estado'])) {
            $where[] = 'de.estado LIKE ?';
            $params[] = '%' . $filtros['Estado'] . '%';
        }
        if (!empty($filtros['Destino'])) {
            $where[] = 'de.Destino LIKE ?';
            $params[] = '%' . $filtros['Destino'] . '%';
        }

        $sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        // Contar registros (intentar con JOINs; en caso de error por tablas inexistentes, usar fallback simple)
        $sqlCount = "SELECT COUNT(DISTINCT de.Id) FROM despachos_externos de
            LEFT JOIN turnos t ON de.Turno = t.Id
            LEFT JOIN responsables r1 ON de.Despachador = r1.Id
            LEFT JOIN $destinoTable dest ON de.Destino = dest.Id
            LEFT JOIN choferes ch ON de.Chofer = ch.Id
            LEFT JOIN transportistas trans ON de.Transportista = trans.Id
            $sqlWhere";
        try {
            try { error_log('DespachoExterno::getReporte - SQL COUNT: ' . $sqlCount . ' -- params: ' . json_encode($params)); } catch (Exception $__) {}
            $stmtCount = $this->db->prepare($sqlCount);
            $stmtCount->execute($params);
            $total = (int)$stmtCount->fetchColumn();
        } catch (Exception $eCount) {
            // Fallback: la BD remota puede no tener tablas auxiliares (p.ej. destino), contamos solo desde la tabla principal
            try { error_log('DespachoExterno::getReporte - COUNT fallback por error: ' . $eCount->getMessage()); } catch (Exception $__) {}
            $sqlCountFallback = "SELECT COUNT(*) FROM despachos_externos de $sqlWhere";
            $stmtCount = $this->db->prepare($sqlCountFallback);
            $stmtCount->execute($params);
            $total = (int)$stmtCount->fetchColumn();
        }
        $totalPaginas = ceil($total / max(1, $limit));
        // Si el total es 0, obtener estadísticas rápidas para depuración
        if ($total === 0) {
            try {
                $stmtStats = $this->db->prepare("SELECT COUNT(*) as total_all, MIN(Fecha) as fecha_min, MAX(Fecha) as fecha_max FROM despachos_externos");
                $stmtStats->execute();
                $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
                try { error_log('DespachoExterno::getReporte - stats tabla despachos_externos: ' . json_encode($stats)); } catch (Exception $__) {}
                // Contar por rango usando DATE() para comparar solo fechas
                if (!empty($filtros['fechaDesde']) || !empty($filtros['fechaHasta']) || !empty($filtros['Fecha'])) {
                    $rFrom = $filtros['fechaDesde'] ?? null;
                    $rTo = $filtros['fechaHasta'] ?? null;
                    $rFecha = $filtros['Fecha'] ?? null;
                    if ($rFecha) {
                        // normalizar dd-mm-yyyy a yyyy-mm-dd
                        if (strpos($rFecha,'-') !== false) {
                            $p = explode('-',$rFecha);
                            if (count($p)===3 && strlen($p[2])===4) $rFecha = "{$p[2]}-{$p[1]}-{$p[0]}";
                        }
                        $stmtRange = $this->db->prepare("SELECT COUNT(*) as cnt FROM despachos_externos WHERE DATE(Fecha) = ?");
                        $stmtRange->execute([$rFecha]);
                        $cntRange = $stmtRange->fetch(PDO::FETCH_ASSOC);
                        try { error_log('DespachoExterno::getReporte - count by DATE(Fecha)= '.$rFecha.': ' . json_encode($cntRange)); } catch (Exception $__) {}
                    } else {
                        // normalizar fechas simples (YYYY-MM-DD)
                        $from = $rFrom; $to = $rTo;
                        if ($from && preg_match('/^\d{4}-\d{2}-\d{2}$/',$from)) { $from = $from; }
                        if ($to && preg_match('/^\d{4}-\d{2}-\d{2}$/',$to)) { $to = $to; }
                        $stmtRange2 = $this->db->prepare("SELECT COUNT(*) as cnt FROM despachos_externos WHERE DATE(Fecha) BETWEEN ? AND ?");
                        $stmtRange2->execute([$rFrom ? substr($rFrom,0,10) : '1970-01-01', $rTo ? substr($rTo,0,10) : '2100-01-01']);
                        $cntRange2 = $stmtRange2->fetch(PDO::FETCH_ASSOC);
                        try { error_log('DespachoExterno::getReporte - count by DATE(Fecha) BETWEEN ' . ($rFrom?:'null') . ' AND ' . ($rTo?:'null') . ': ' . json_encode($cntRange2)); } catch (Exception $__) {}
                    }
                }
            } catch (Exception $e) {
                try { error_log('DespachoExterno::getReporte - stats error: ' . $e->getMessage()); } catch (Exception $__) {}
            }
        }

        // Consulta principal
        $sql = "SELECT de.Id, de.NVale, de.Fecha, de.Hora, t.Turno as Turno,
            de.Chofer as ChoferOriginal,
            de.Destino as DestinoOriginal,
            de.Transportista as TransportistaOriginal,
            COALESCE(dest.Empresa, '') as Destino,
            COALESCE(de.RUC, '') as RUC,
            COALESCE(de.Direccion, '') as Direccion,
            COALESCE(r1.NombresApellidos, '') as Despachador,
            COALESCE(ch.ApellidosNombres, de.Chofer, '') as Chofer,
            COALESCE(ch.Brevete, de.Licencia, '') as Brevete,
            COALESCE(trans.Empresa, de.Transportista, '') as Transportista,
            COALESCE(de.RUC_Transportista, '') as RUC_Transportista,
            COALESCE(de.Placa_Tracto, '') as Placa_Tracto,
            COALESCE(de.Placa_Carreta, '') as Placa_Carreta,
            COALESCE(de.Constancia_Inscripcion, '') as Constancia_Inscripcion,
            COALESCE(de.Constancia_Inscripcion_2, '') as Constancia_Inscripcion_2,
            COALESCE(de.GR, '') as GR,
            de.estado as Estado
            FROM despachos_externos de
            LEFT JOIN turnos t ON de.Turno = t.Id
            LEFT JOIN responsables r1 ON de.Despachador = r1.Id
            LEFT JOIN $destinoTable dest ON de.Destino = dest.Id
            LEFT JOIN choferes ch ON de.Chofer = ch.Id
            LEFT JOIN transportistas trans ON de.Transportista = trans.Id
            $sqlWhere
            GROUP BY de.Id
            ORDER BY de.NVale DESC
            LIMIT $limit OFFSET $offset";

        try {
            // Log SQL principal y parámetros para depuración
            try { error_log('DespachoExterno::getReporte - SQL MAIN: ' . $sql . ' -- params: ' . json_encode($params)); } catch (Exception $__) {}
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            try { error_log('DespachoExterno::getReporte - registros obtenidos: ' . count($data)); } catch (Exception $__) {}
        } catch (Exception $eMain) {
            // Si falla por tablas auxiliares faltantes (p. ej. destino), intentar fallback sin JOINs
            try { error_log('DespachoExterno::getReporte - MAIN query failed, fallback sin JOINs: ' . $eMain->getMessage()); } catch (Exception $__) {}
            $sqlFallback = "SELECT de.Id, de.NVale, de.Fecha, de.Hora, de.Turno as Turno,
                de.Chofer as ChoferOriginal,
                de.Destino as DestinoOriginal,
                de.Transportista as TransportistaOriginal,
                COALESCE(de.RUC, '') as RUC,
                COALESCE(de.Direccion, '') as Direccion,
                COALESCE(de.Despachador, '') as Despachador,
                COALESCE(de.Chofer, '') as Chofer,
                COALESCE(de.Licencia, '') as Brevete,
                COALESCE(de.Transportista, '') as Transportista,
                COALESCE(de.RUC_Transportista, '') as RUC_Transportista,
                COALESCE(de.Placa_Tracto, '') as Placa_Tracto,
                COALESCE(de.Placa_Carreta, '') as Placa_Carreta,
                COALESCE(de.Constancia_Inscripcion, '') as Constancia_Inscripcion,
                COALESCE(de.Constancia_Inscripcion_2, '') as Constancia_Inscripcion_2,
                COALESCE(de.GR, '') as GR,
                de.estado as Estado
                FROM despachos_externos de
                $sqlWhere
                GROUP BY de.Id
                ORDER BY de.NVale DESC
                LIMIT $limit OFFSET $offset";
            try {
                $stmt = $this->db->prepare($sqlFallback);
                $stmt->execute($params);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                try { error_log('DespachoExterno::getReporte - fallback registros obtenidos: ' . count($data)); } catch (Exception $__) {}
            } catch (Exception $__fallbackErr) {
                try { error_log('DespachoExterno::getReporte - fallback también falló: ' . $__fallbackErr->getMessage()); } catch (Exception $__) {}
                $data = [];
                $totalPaginas = 1;
            }
        }

        // Obtener productos por despacho y ajustes menores (aplica tanto para resultado normal como para fallback)
        // OPTIMIZACIÓN: Obtener choferes en UNA consulta y productos en UNA consulta
        if (!empty($data)) {
            $ids = array_column($data, 'Id');

            // 1. Batch: choferes
            $choferIds = [];
            foreach ($data as $row) {
                if (isset($row['ChoferOriginal']) && is_numeric($row['ChoferOriginal']) && $row['ChoferOriginal'] > 0) {
                    $choferIds[] = $row['ChoferOriginal'];
                }
            }
            $choferDataMap = [];
            if (!empty($choferIds)) {
                $chPlaceholders = implode(',', array_fill(0, count($choferIds), '?'));
                try {
                    $stmtCh = $this->db->prepare("SELECT Id, ApellidosNombres, Brevete FROM choferes WHERE Id IN ($chPlaceholders)");
                    $stmtCh->execute($choferIds);
                    foreach ($stmtCh->fetchAll(PDO::FETCH_ASSOC) as $ch) {
                        $choferDataMap[$ch['Id']] = $ch;
                    }
                } catch (Exception $__) {}
            }

            // 2. Batch: productos
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sqlProd = "SELECT DespachoId, CodigoProducto as Codigo, DescripcionProducto as Producto, UnidadMedida, Cantidad, Comentarios FROM despachos_externos_productos WHERE DespachoId IN ($placeholders)";
            $stmtProd = $this->db->prepare($sqlProd);
            $stmtProd->execute($ids);
            $todosProductos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
            $productosPorId = [];
            foreach ($todosProductos as $prod) {
                $did = $prod['DespachoId'];
                unset($prod['DespachoId']);
                $productosPorId[$did][] = $prod;
            }

            // 3. Asignar a cada fila
            foreach ($data as &$row) {
                // Asignar datos de chofer
                if (isset($choferDataMap[$row['ChoferOriginal']])) {
                    $chData = $choferDataMap[$row['ChoferOriginal']];
                    $row['Chofer'] = $chData['ApellidosNombres'];
                    if ((empty($row['Brevete']) || $row['Brevete'] === '') && !empty($chData['Brevete'])) {
                        $row['Brevete'] = $chData['Brevete'];
                    }
                }
                $row['Detalles'] = $productosPorId[$row['Id']] ?? [];
            }
            unset($row);
        }

        return ['data' => $data, 'totalPaginas' => $totalPaginas];
    }

    /**
     * Cuenta total de filas planas (vales × productos)
     */
    public function getTotalFlattenedCount($filtros = []) {
        $where = []; $params = [];
        if (!empty($filtros['N° Vale'])) { $where[] = 'de.NVale LIKE ?'; $params[] = '%' . $filtros['N° Vale'] . '%'; }
        if (!empty($filtros['fechaDesde'])) { $where[] = 'de.Fecha >= ?'; $params[] = $filtros['fechaDesde']; }
        if (!empty($filtros['fechaHasta'])) { $where[] = 'de.Fecha <= ?'; $params[] = $filtros['fechaHasta']; }
        if (!empty($filtros['turnoId'])) { $where[] = 'de.Turno = ?'; $params[] = $filtros['turnoId']; }
        $sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        // Usar LEFT JOIN con GROUP BY (mucho más rápido que subconsultas correlacionadas)
        $sql = "SELECT COALESCE(SUM(COALESCE(p.pc, 1)), 0) as total
                FROM despachos_externos de
                LEFT JOIN (SELECT DespachoId, COUNT(*) as pc FROM despachos_externos_productos GROUP BY DespachoId) p ON de.Id = p.DespachoId
                $sqlWhere";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return (int)$stmt->fetchColumn(); }
        catch (Exception $e) { return 0; }
    }

    /**
     * Devuelve estadísticas simples de la tabla despachos_externos
     * Útil para mensajes de ayuda en exportación cuando no hay datos en un rango
     * Retorna array con keys: total_all, fecha_min, fecha_max
     */
    public function getFechaStats() {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as total_all, MIN(Fecha) as fecha_min, MAX(Fecha) as fecha_max FROM despachos_externos");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: ['total_all' => 0, 'fecha_min' => null, 'fecha_max' => null];
        } catch (Exception $e) {
            error_log('DespachoExterno::getFechaStats error: ' . $e->getMessage());
            return ['total_all' => 0, 'fecha_min' => null, 'fecha_max' => null];
        }
    }

    // Métodos para registrar, modificar, obtener por ID, etc. (adaptar procedimientos y tablas)
    // ...existing code...
}
// Cache clear 20251130_215346
