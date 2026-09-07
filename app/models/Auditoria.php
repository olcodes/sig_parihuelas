<?php

class Auditoria extends Model {

    /**
     * Obtiene el log consolidado de CREACIÓN + MODIFICACIONES de todos los módulos.
     * Soporta filtros: modulo, usuario_id, fecha_desde, fecha_hasta, accion.
     */
    public function getLog($filtros = [], $limit = 50, $offset = 0) {
        $params = [];
        $having = $this->buildHaving($filtros, $params);

        $col = 'COLLATE utf8mb4_general_ci';

        $sql = "
            SELECT * FROM (
                -- CREACIONES Despachos Internos
                SELECT
                    CONVERT('Despacho Interno' USING utf8mb4) {$col}   AS modulo,
                    di.Id                                               AS registro_id,
                    CONVERT(CONCAT('VDI-', LPAD(di.NVale, 6, '0')) USING utf8mb4) {$col} AS nvale,
                    CONVERT('CREACIÓN' USING utf8mb4) {$col}           AS accion,
                    di.creado_por                                       AS usuario_id,
                    CONVERT(COALESCE(u.username,'') USING utf8mb4) {$col}        AS username,
                    CONVERT(COALESCE(u.NombresApellidos,'') USING utf8mb4) {$col} AS nombres,
                    di.creado_en                                        AS fecha_hora,
                    CONVERT(COALESCE(di.ip_creacion,'') USING utf8mb4) {$col}   AS ip,
                    NULL                                                AS nmodificacion
                FROM despachos_internos di
                LEFT JOIN usuarios u ON di.creado_por = u.Id

                UNION ALL

                -- MODIFICACIONES Despachos Internos
                SELECT
                    CONVERT('Despacho Interno' USING utf8mb4) {$col},
                    m.DespachoId,
                    CONVERT(CONCAT('VDI-', LPAD(di.NVale, 6, '0')) USING utf8mb4) {$col},
                    CONVERT('MODIFICACIÓN' USING utf8mb4) {$col},
                    m.UsuarioId,
                    CONVERT(COALESCE(u.username,'') USING utf8mb4) {$col},
                    CONVERT(COALESCE(u.NombresApellidos,'') USING utf8mb4) {$col},
                    m.ModificadoEn,
                    CONVERT(COALESCE(m.IpAddress,'') USING utf8mb4) {$col},
                    m.NModificacion
                FROM despachos_internos_modificaciones m
                LEFT JOIN despachos_internos di ON m.DespachoId = di.Id
                LEFT JOIN usuarios u ON m.UsuarioId = u.Id

                UNION ALL

                -- CREACIONES Despachos Externos
                SELECT
                    CONVERT('Despacho Externo' USING utf8mb4) {$col},
                    de.Id,
                    CONVERT(CONCAT('VDE-', LPAD(de.NVale, 6, '0')) USING utf8mb4) {$col},
                    CONVERT('CREACIÓN' USING utf8mb4) {$col},
                    de.creado_por,
                    CONVERT(COALESCE(u.username,'') USING utf8mb4) {$col},
                    CONVERT(COALESCE(u.NombresApellidos,'') USING utf8mb4) {$col},
                    de.creado_en,
                    CONVERT(COALESCE(de.ip_creacion,'') USING utf8mb4) {$col},
                    NULL
                FROM despachos_externos de
                LEFT JOIN usuarios u ON de.creado_por = u.Id

                UNION ALL

                -- MODIFICACIONES Despachos Externos
                SELECT
                    CONVERT('Despacho Externo' USING utf8mb4) {$col},
                    m.DespachoId,
                    CONVERT(CONCAT('VDE-', LPAD(de.NVale, 6, '0')) USING utf8mb4) {$col},
                    CONVERT('MODIFICACIÓN' USING utf8mb4) {$col},
                    m.UsuarioId,
                    CONVERT(COALESCE(u.username,'') USING utf8mb4) {$col},
                    CONVERT(COALESCE(u.NombresApellidos,'') USING utf8mb4) {$col},
                    m.ModificadoEn,
                    CONVERT(COALESCE(m.IpAddress,'') USING utf8mb4) {$col},
                    m.NModificacion
                FROM despachos_externos_modificaciones m
                LEFT JOIN despachos_externos de ON m.DespachoId = de.Id
                LEFT JOIN usuarios u ON m.UsuarioId = u.Id

                UNION ALL

                -- CREACIONES Recepciones Internas
                SELECT
                    CONVERT('Recepción Interna' USING utf8mb4) {$col},
                    ri.Id,
                    CONVERT(CONCAT('VRI-', LPAD(ri.NVale, 6, '0')) USING utf8mb4) {$col},
                    CONVERT('CREACIÓN' USING utf8mb4) {$col},
                    ri.creado_por,
                    CONVERT(COALESCE(u.username,'') USING utf8mb4) {$col},
                    CONVERT(COALESCE(u.NombresApellidos,'') USING utf8mb4) {$col},
                    ri.creado_en,
                    CONVERT(COALESCE(ri.ip_creacion,'') USING utf8mb4) {$col},
                    NULL
                FROM recepciones_internas ri
                LEFT JOIN usuarios u ON ri.creado_por = u.Id

                UNION ALL

                -- MODIFICACIONES Recepciones Internas
                SELECT
                    CONVERT('Recepción Interna' USING utf8mb4) {$col},
                    m.RecepcionId,
                    CONVERT(CONCAT('VRI-', LPAD(ri.NVale, 6, '0')) USING utf8mb4) {$col},
                    CONVERT('MODIFICACIÓN' USING utf8mb4) {$col},
                    m.UsuarioId,
                    CONVERT(COALESCE(u.username,'') USING utf8mb4) {$col},
                    CONVERT(COALESCE(u.NombresApellidos,'') USING utf8mb4) {$col},
                    m.ModificadoEn,
                    CONVERT(COALESCE(m.IpAddress,'') USING utf8mb4) {$col},
                    m.NModificacion
                FROM recepciones_internas_modificaciones m
                LEFT JOIN recepciones_internas ri ON m.RecepcionId = ri.Id
                LEFT JOIN usuarios u ON m.UsuarioId = u.Id

                UNION ALL

                -- CREACIONES Recepciones Externas
                SELECT
                    CONVERT('Recepción Externa' USING utf8mb4) {$col},
                    re.Id,
                    CONVERT(CONCAT('VRE-', LPAD(re.NVale, 6, '0')) USING utf8mb4) {$col},
                    CONVERT('CREACIÓN' USING utf8mb4) {$col},
                    re.creado_por,
                    CONVERT(COALESCE(u.username,'') USING utf8mb4) {$col},
                    CONVERT(COALESCE(u.NombresApellidos,'') USING utf8mb4) {$col},
                    re.creado_en,
                    CONVERT(COALESCE(re.ip_creacion,'') USING utf8mb4) {$col},
                    NULL
                FROM recepciones_externas re
                LEFT JOIN usuarios u ON re.creado_por = u.Id

                UNION ALL

                -- MODIFICACIONES Recepciones Externas
                SELECT
                    CONVERT('Recepción Externa' USING utf8mb4) {$col},
                    m.RecepcionId,
                    CONVERT(CONCAT('VRE-', LPAD(re.NVale, 6, '0')) USING utf8mb4) {$col},
                    CONVERT('MODIFICACIÓN' USING utf8mb4) {$col},
                    m.UsuarioId,
                    CONVERT(COALESCE(u.username,'') USING utf8mb4) {$col},
                    CONVERT(COALESCE(u.NombresApellidos,'') USING utf8mb4) {$col},
                    m.ModificadoEn,
                    CONVERT(COALESCE(m.IpAddress,'') USING utf8mb4) {$col},
                    m.NModificacion
                FROM recepciones_externas_modificaciones m
                LEFT JOIN recepciones_externas re ON m.RecepcionId = re.Id
                LEFT JOIN usuarios u ON m.UsuarioId = u.Id

            ) AS log_auditoria
            WHERE 1=1 {$having}
            ORDER BY fecha_hora DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  (int)$limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Total de registros para paginación (misma lógica, sin LIMIT).
     */
    public function countLog($filtros = []) {
        $params = [];
        $having = $this->buildHaving($filtros, $params);

        $sql = "
            SELECT COUNT(*) AS total FROM (
                SELECT di.creado_en AS fecha_hora, 'CREACIÓN' AS accion, 'Despacho Interno' AS modulo, u.username, u.NombresApellidos AS nombres
                FROM despachos_internos di LEFT JOIN usuarios u ON di.creado_por = u.Id
                UNION ALL
                SELECT m.ModificadoEn, 'MODIFICACIÓN', 'Despacho Interno', u.username, u.NombresApellidos
                FROM despachos_internos_modificaciones m LEFT JOIN usuarios u ON m.UsuarioId = u.Id
                UNION ALL
                SELECT de.creado_en, 'CREACIÓN', 'Despacho Externo', u.username, u.NombresApellidos
                FROM despachos_externos de LEFT JOIN usuarios u ON de.creado_por = u.Id
                UNION ALL
                SELECT m.ModificadoEn, 'MODIFICACIÓN', 'Despacho Externo', u.username, u.NombresApellidos
                FROM despachos_externos_modificaciones m LEFT JOIN usuarios u ON m.UsuarioId = u.Id
                UNION ALL
                SELECT ri.creado_en, 'CREACIÓN', 'Recepción Interna', u.username, u.NombresApellidos
                FROM recepciones_internas ri LEFT JOIN usuarios u ON ri.creado_por = u.Id
                UNION ALL
                SELECT m.ModificadoEn, 'MODIFICACIÓN', 'Recepción Interna', u.username, u.NombresApellidos
                FROM recepciones_internas_modificaciones m LEFT JOIN usuarios u ON m.UsuarioId = u.Id
                UNION ALL
                SELECT re.creado_en, 'CREACIÓN', 'Recepción Externa', u.username, u.NombresApellidos
                FROM recepciones_externas re LEFT JOIN usuarios u ON re.creado_por = u.Id
                UNION ALL
                SELECT m.ModificadoEn, 'MODIFICACIÓN', 'Recepción Externa', u.username, u.NombresApellidos
                FROM recepciones_externas_modificaciones m LEFT JOIN usuarios u ON m.UsuarioId = u.Id
            ) AS log_auditoria
            WHERE 1=1 {$having}
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    /**
     * Historial de un registro específico (para el panel Nivel 2).
     */
    public function getHistorialRegistro($modulo, $registroId) {
        $tablasMod = [
            'despacho_interno'   => ['tabla' => 'despachos_internos_modificaciones',   'fk' => 'DespachoId'],
            'despacho_externo'   => ['tabla' => 'despachos_externos_modificaciones',   'fk' => 'DespachoId'],
            'recepcion_interna'  => ['tabla' => 'recepciones_internas_modificaciones', 'fk' => 'RecepcionId'],
            'recepcion_externa'  => ['tabla' => 'recepciones_externas_modificaciones', 'fk' => 'RecepcionId'],
        ];

        if (!isset($tablasMod[$modulo])) return [];

        $t   = $tablasMod[$modulo]['tabla'];
        $fk  = $tablasMod[$modulo]['fk'];

        $sql = "SELECT m.NModificacion, m.ModificadoEn, m.IpAddress,
                       u.username, u.NombresApellidos
                FROM {$t} m
                LEFT JOIN usuarios u ON m.UsuarioId = u.Id
                WHERE m.{$fk} = :id
                ORDER BY m.ModificadoEn ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => (int)$registroId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lista de usuarios para el filtro del select.
     */
    public function getUsuarios() {
        $stmt = $this->db->query("SELECT Id, username, NombresApellidos FROM usuarios ORDER BY NombresApellidos ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // -----------------------------------------------------------------------
    private function buildHaving($filtros, &$params) {
        $conditions = [];

        if (!empty($filtros['modulo'])) {
            $conditions[] = "modulo = :modulo";
            $params[':modulo'] = $filtros['modulo'];
        }
        if (!empty($filtros['accion'])) {
            $conditions[] = "accion = :accion";
            $params[':accion'] = $filtros['accion'];
        }
        if (!empty($filtros['usuario_id'])) {
            $conditions[] = "usuario_id = :usuario_id";
            $params[':usuario_id'] = (int)$filtros['usuario_id'];
        }
        if (!empty($filtros['fecha_desde'])) {
            $conditions[] = "DATE(fecha_hora) >= :fecha_desde";
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $conditions[] = "DATE(fecha_hora) <= :fecha_hasta";
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }
        if (!empty($filtros['nvale'])) {
            $conditions[] = "nvale LIKE :nvale";
            $params[':nvale'] = '%' . $filtros['nvale'] . '%';
        }

        return $conditions ? 'AND ' . implode(' AND ', $conditions) : '';
    }
}
