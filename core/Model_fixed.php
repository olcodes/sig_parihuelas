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
}
?>