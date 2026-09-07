<?php

class UsuarioLimiteModificacion extends Model
{
    protected $table = 'usuarios_limites_modificacion';

    /**
     * Obtener configuración de límites por ID de usuario
     */
    public function getByUsuarioId($usuarioId)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE UsuarioId = ?"
            );
            $stmt->execute([$usuarioId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log('Error en UsuarioLimiteModificacion::getByUsuarioId: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Guardar o actualizar configuración de límites para un usuario
     * @param array $data [UsuarioId, max_modificaciones, ventana_horas, permite_multiples]
     * @return bool
     */
    public function save($data)
    {
        try {
            $usuarioId = (int)$data['UsuarioId'];
            $maxModificaciones = isset($data['max_modificaciones']) && $data['max_modificaciones'] !== '' 
                ? (int)$data['max_modificaciones'] 
                : null;
            $ventanaHoras = isset($data['ventana_horas']) && $data['ventana_horas'] !== '' 
                ? (int)$data['ventana_horas'] 
                : null;
            $permiteMultiples = !empty($data['permite_multiples']) ? 1 : 0;

            // Verificar si ya existe un registro para este usuario
            $existing = $this->getByUsuarioId($usuarioId);

            if (!empty($existing)) {
                // Actualizar
                $stmt = $this->db->prepare(
                    "UPDATE {$this->table} 
                     SET max_modificaciones = ?, ventana_horas = ?, permite_multiples = ?
                     WHERE UsuarioId = ?"
                );
                return $stmt->execute([
                    $maxModificaciones,
                    $ventanaHoras,
                    $permiteMultiples,
                    $usuarioId
                ]);
            } else {
                // Insertar
                $stmt = $this->db->prepare(
                    "INSERT INTO {$this->table} 
                     (UsuarioId, max_modificaciones, ventana_horas, permite_multiples)
                     VALUES (?, ?, ?, ?)"
                );
                return $stmt->execute([
                    $usuarioId,
                    $maxModificaciones,
                    $ventanaHoras,
                    $permiteMultiples
                ]);
            }
        } catch (Exception $e) {
            error_log('Error en UsuarioLimiteModificacion::save: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar configuración de límites para un usuario
     */
    public function deleteByUsuarioId($usuarioId)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE UsuarioId = ?");
            return $stmt->execute([$usuarioId]);
        } catch (Exception $e) {
            error_log('Error en UsuarioLimiteModificacion::deleteByUsuarioId: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener todos los registros con información del usuario
     */
    public function getAllWithUsuarios($busqueda = '', $offset = 0, $limit = 50)
    {
        try {
            $sql = "SELECT ulm.*, u.username, u.NombresApellidos, u.DocIdentidad, r.Nombre as RolNombre
                    FROM {$this->table} ulm
                    INNER JOIN usuarios u ON ulm.UsuarioId = u.Id
                    LEFT JOIN roles r ON u.RoleId = r.Id";
            $params = [];

            if (!empty($busqueda)) {
                $sql .= " WHERE u.username LIKE :b OR u.NombresApellidos LIKE :b";
                $params['b'] = "%$busqueda%";
            }

            $sql .= " ORDER BY u.NombresApellidos ASC LIMIT :offset, :limit";
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $k => $v) {
                $stmt->bindValue(":$k", $v, PDO::PARAM_STR);
            }
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Error en UsuarioLimiteModificacion::getAllWithUsuarios: ' . $e->getMessage());
            return [];
        }
    }
}
