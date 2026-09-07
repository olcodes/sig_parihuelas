<?php
require_once __DIR__ . '/../config/database.php';

class Model {
    protected $db;

    public function __construct()
    {
        // Usamos la configuración de la base de datos
        $this->db = Database::getInstance()->getConnection();
    }

    // Método universal para ejecutar sentencias preparadas
    protected function query($sql, $params = [])
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        // Si la consulta es SELECT o CALL que retorna datos
        if (stripos($sql, 'SELECT') === 0 || stripos($sql, 'CALL') === 0) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $stmt;
    }

    // Obtener el último ID insertado
    public function lastInsertId() {
        return $this->db->lastInsertId();
    }

    /**
     * Obtener configuración de límites de modificación para un usuario
     * desde la tabla usuarios_limites_modificacion
     *
     * @param int|null $usuarioId ID del usuario
     * @return array|null Configuración o null si no existe
     */
    protected function obtenerConfigUsuarioModificacion($usuarioId)
    {
        if (!$usuarioId || !$this->db) return null;
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM usuarios_limites_modificacion WHERE UsuarioId = ?"
            );
            $stmt->execute([$usuarioId]);
            $config = $stmt->fetch(PDO::FETCH_ASSOC);
            return $config ?: null;
        } catch (Exception $e) {
            error_log('Error obteniendo config modificacion usuario: ' . $e->getMessage());
            return null;
        }
    }
}
?>