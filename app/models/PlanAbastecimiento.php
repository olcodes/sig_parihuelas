<?php
/**
 * Modelo PlanAbastecimiento - Gestión del Plan de Abastecimiento
 */
require_once __DIR__ . '/../../core/Model.php';

class PlanAbastecimiento extends Model
{
    protected $table = 'plan_abastecimiento';
    protected $primaryKey = 'Id';

    /**
     * Listar registros activos, opcionalmente filtrados por mes y año
     * Incluye q_total_despachada desde avance_diario
     * @param int|null $mes
     * @param int|null $anio
     * @return array
     */
    public function listarTodos($mes = null, $anio = null)
    {
        $sql = "SELECT * FROM plan_abastecimiento WHERE estado = 'activo'";
        $params = [];

        if ($mes && $anio) {
            $sql .= " AND MONTH(Fecha) = :mes AND YEAR(Fecha) = :anio";
            $params[':mes'] = (int)$mes;
            $params[':anio'] = (int)$anio;
        }

        $sql .= " ORDER BY Fecha DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return [];
        }

        // Obtener todas las fechas únicas de los registros
        $fechas = array_column($rows, 'Fecha');
        $fechasUnicas = array_unique($fechas);

        // Consultar avance_diario: suma de despachos por fecha
        // q_total_despachada = EanUsadasDespInternos + EanUsadasDespExternos + EanComprasNuevasDespInternos
        $placeholders = implode(',', array_fill(0, count($fechasUnicas), '?'));
        $sqlAvance = "SELECT Fecha,
                             COALESCE(SUM(EanUsadasDespInternos), 0) +
                             COALESCE(SUM(EanUsadasDespExternos), 0) +
                             COALESCE(SUM(EanComprasNuevasDespInternos), 0) AS total_despachado
                      FROM avance_diario
                      WHERE Fecha IN ($placeholders) AND estado = 'activo'
                      GROUP BY Fecha";
        $stmtAvance = $this->db->prepare($sqlAvance);
        $stmtAvance->execute(array_values($fechasUnicas));
        $despachosPorFecha = [];
        while ($rowAvance = $stmtAvance->fetch(PDO::FETCH_ASSOC)) {
            $despachosPorFecha[$rowAvance['Fecha']] = (float)$rowAvance['total_despachado'];
        }

        // Asignar a cada registro
        foreach ($rows as &$row) {
            $fecha = $row['Fecha'];
            $row['q_total_despachada'] = $despachosPorFecha[$fecha] ?? 0;
            $row['q_pendiente_dia'] = 0;
            $row['q_pendiente_despacho_acumulado'] = 0;
            $row['codigo_planificacion'] = '';
            $row['semana'] = '';
        }

        return $rows;
    }

    /**
     * Obtener un registro por ID
     * @param int $id
     * @return array|null
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM plan_abastecimiento WHERE Id = ? AND estado = 'activo' LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener un registro por fecha
     * @param string $fecha Formato YYYY-MM-DD
     * @return array|null
     */
    public function getByFecha($fecha)
    {
        $sql = "SELECT * FROM plan_abastecimiento WHERE Fecha = ? AND estado = 'activo' LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$fecha]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Verificar si ya existe un registro con la misma fecha
     * @param string $fecha Fecha en formato YYYY-MM-DD
     * @param int|null $excluirId ID a excluir (para actualizaciones)
     * @return bool
     */
    public function existePorFecha($fecha, $excluirId = null)
    {
        $sql = "SELECT Id FROM plan_abastecimiento WHERE Fecha = ? AND estado = 'activo'";
        $params = [$fecha];
        if ($excluirId) {
            $sql .= " AND Id != ?";
            $params[] = $excluirId;
        }
        $sql .= " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    /**
     * Insertar nuevo registro
     * @param array $data
     * @return int ID insertado
     */
    public function insertar($data)
    {
        $sql = "INSERT INTO plan_abastecimiento (Fecha, QTotalPlanificada, creado_por) 
                VALUES (:fecha, :q_total_planificada, :creado_por)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':fecha' => $data['fecha'],
            ':q_total_planificada' => $data['q_total_planificada'] ?? 0,
            ':creado_por' => $data['creado_por'] ?? null
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * Actualizar registro existente e incrementar contador de modificaciones
     * @param int $id
     * @param array $data
     */
    public function actualizar($id, $data)
    {
        $sql = "UPDATE plan_abastecimiento SET
                    Fecha = :fecha,
                    QTotalPlanificada = :q_total_planificada,
                    modificado_por = :modificado_por,
                    modificado_en = NOW(),
                    modificaciones_count = modificaciones_count + 1
                WHERE Id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':fecha' => $data['fecha'],
            ':q_total_planificada' => $data['q_total_planificada'] ?? 0,
            ':modificado_por' => $data['modificado_por'] ?? null,
            ':id' => $id
        ]);
    }

    /**
     * Registrar en el historial quién hizo la modificación
     * (mismo patrón que KardexParihuela)
     */
    public function registrarModificacion($planId, $usuarioId, $ip = null)
    {
        $sql = "SELECT COALESCE(modificaciones_count, 0) as modificaciones_count FROM plan_abastecimiento WHERE Id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$planId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $nModificacion = $row ? (int)$row['modificaciones_count'] : 0;

        $sql = "INSERT INTO plan_abastecimiento_modificaciones (PlanAbastecimientoId, UsuarioId, NModificacion, IpAddress) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$planId, $usuarioId, $nModificacion, $ip]);
    }

    /**
     * Verificar si un registro puede ser modificado
     * Reutiliza la config de usuarios_limites_modificacion (igual que los otros 5 módulos)
     *
     * @param int $id ID del registro
     * @return array ['puede' => bool, 'modificaciones' => int, 'restantes' => int, 'mensaje' => string]
     */
    public function puedeModificar($id)
    {
        try {
            $sql = "SELECT COALESCE(modificaciones_count, 0) as modificaciones, creado_en
                    FROM plan_abastecimiento WHERE Id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$resultado) {
                return [
                    'puede' => false,
                    'modificaciones' => 0,
                    'restantes' => 0,
                    'mensaje' => 'Registro no encontrado'
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
                                     "El registro fue creado el {$fechaCreacion}. " .
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
                    : "Este registro ya alcanzo el limite de {$maxModificaciones} modificaciones."
            ];
        } catch (Exception $e) {
            error_log('Error en PlanAbastecimiento::puedeModificar: ' . $e->getMessage());
            return [
                'puede' => false,
                'modificaciones' => 0,
                'restantes' => 0,
                'mensaje' => 'Error al verificar el estado de modificaciones'
            ];
        }
    }
}
