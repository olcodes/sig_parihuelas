<?php
class Subarea extends Model {
    public function updateSubarea($id, $subarea, $idArea) {
        $stmt = $this->db->prepare("UPDATE subareas SET Subarea = :subarea, IdArea = :idArea WHERE Id = :id");
        return $stmt->execute(['subarea' => $subarea, 'idArea' => $idArea, 'id' => $id]);
    }

    public function deleteSubarea($id) {
        $stmt = $this->db->prepare("DELETE FROM subareas WHERE Id = :id");
        return $stmt->execute(['id' => $id]);
    }
    public function insertSubarea($subarea, $idArea) {
        $stmt = $this->db->prepare("INSERT INTO subareas (Subarea, IdArea) VALUES (:subarea, :idArea)");
        return $stmt->execute(['subarea' => $subarea, 'idArea' => $idArea]);
    }
    protected $table = 'subareas';
    protected $primaryKey = 'Id';


    public function getAll($search = '', $limit = 10, $offset = 0) {
        $sql = "SELECT s.*, a.Area FROM subareas s LEFT JOIN areas a ON s.IdArea = a.Id ";
        $params = [];
        $search = trim($search);
        if ($search !== '') {
            $sql .= "WHERE s.Subarea LIKE :search1 OR a.Area LIKE :search2 ";
            $params['search1'] = "%$search%";
            $params['search2'] = "%$search%";
        }
        $limit = (int)$limit;
        $offset = (int)$offset;
        $sql .= "ORDER BY s.Id DESC LIMIT $limit OFFSET $offset";
        $logFile = __DIR__ . '/../../logs/depurar_guardar.txt';
        file_put_contents($logFile, "\n--- getAll ---\n", FILE_APPEND);
        file_put_contents($logFile, "SQL: $sql\n", FILE_APPEND);
        file_put_contents($logFile, 'PARAMS: ' . print_r($params, true) . "\n", FILE_APPEND);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll($search = '') {
        $sql = "SELECT COUNT(*) FROM subareas s LEFT JOIN areas a ON s.IdArea = a.Id ";
        $params = [];
        $search = trim($search);
        if ($search !== '') {
            $sql .= "WHERE s.Subarea LIKE :search1 OR a.Area LIKE :search2 ";
            $params['search1'] = "%$search%";
            $params['search2'] = "%$search%";
        }
        $logFile = __DIR__ . '/../../logs/depurar_guardar.txt';
        file_put_contents($logFile, "\n--- countAll ---\n", FILE_APPEND);
        file_put_contents($logFile, "SQL: $sql\n", FILE_APPEND);
        file_put_contents($logFile, 'PARAMS: ' . print_r($params, true) . "\n", FILE_APPEND);
        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        return $stmt->fetchColumn();
    }

    public function findBySubarea($subarea, $idArea) {
        $stmt = $this->db->prepare("SELECT * FROM subareas WHERE Subarea = :subarea AND IdArea = :idArea");
        $stmt->execute(['subarea' => $subarea, 'idArea' => $idArea]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM subareas WHERE Id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function __construct() {
        parent::__construct();
    }

    public function getAllForSelect() {
        $stmt = $this->db->prepare("SELECT s.Id, s.Subarea, s.IdArea, a.Area FROM subareas s LEFT JOIN areas a ON s.IdArea = a.Id ORDER BY a.Area, s.Subarea");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verificar si la subárea tiene movimientos registrados
     * @param int $id ID de la subárea
     * @return bool true si tiene movimientos, false si no
     */
    public function hasMovements($id)
    {
        $db = Database::getInstance()->getConnection();
        
        // Verificar en despachos internos
        $stmt = $db->prepare('SELECT COUNT(*) FROM despachos_internos WHERE Subarea = ?');
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }
        
        // Verificar en recepciones internas
        $stmt = $db->prepare('SELECT COUNT(*) FROM recepciones_internas WHERE Subarea = ?');
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }
        
        return false;
    }
}
