<?php
class Area extends Model {
    public function deleteArea($id) {
        $stmt = $this->db->prepare("DELETE FROM areas WHERE Id = :id");
        return $stmt->execute(['id' => $id]);
    }
    public function updateArea($id, $area) {
        $stmt = $this->db->prepare("UPDATE areas SET Area = :area WHERE Id = :id");
        return $stmt->execute(['area' => $area, 'id' => $id]);
    }
    protected $table = 'areas';
    protected $primaryKey = 'Id';

    public function __construct() {
        parent::__construct();
    }

    public function getAll($search = '', $limit = 10, $offset = 0) {
        $sql = "SELECT * FROM areas ";
        if ($search) {
            $sql .= "WHERE Area LIKE :search ";
        }
        $sql .= "ORDER BY Id DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        if ($search) $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll($search = '') {
        $sql = "SELECT COUNT(*) FROM areas ";
        if ($search) {
            $sql .= "WHERE Area LIKE :search ";
        }
        $stmt = $this->db->prepare($sql);
        if ($search) $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function findByArea($area) {
        $stmt = $this->db->prepare("SELECT * FROM areas WHERE Area = :area");
        $stmt->execute(['area' => $area]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM areas WHERE Id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function insertArea($area) {
        $stmt = $this->db->prepare("INSERT INTO areas (Area) VALUES (:area)");
        return $stmt->execute(['area' => $area]);
    }

    public function getAllForSelect() {
        $stmt = $this->db->prepare("SELECT Id, Area FROM areas ORDER BY Area");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verificar si el área tiene movimientos registrados
     * @param int $id ID del área
     * @return bool true si tiene movimientos, false si no
     */
    public function hasMovements($id)
    {
        $db = Database::getInstance()->getConnection();
        
        // Verificar en despachos internos
        $stmt = $db->prepare('SELECT COUNT(*) FROM despachos_internos WHERE Area = ?');
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }
        
        // Verificar en recepciones internas
        $stmt = $db->prepare('SELECT COUNT(*) FROM recepciones_internas WHERE Area = ?');
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }
        
        return false;
    }
}
