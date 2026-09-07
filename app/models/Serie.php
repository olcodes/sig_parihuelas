<?php
class Serie extends Model {
    public function deleteSerie($id) {
        $stmt = $this->db->prepare("DELETE FROM series WHERE Id = :id");
        return $stmt->execute(['id' => $id]);
    }
    public function updateSerie($id, $serie, $centroDistribucion) {
        $stmt = $this->db->prepare("UPDATE series SET Serie = :serie, CentroDistribucion = :centroDistribucion WHERE Id = :id");
        return $stmt->execute(['serie' => $serie, 'centroDistribucion' => $centroDistribucion, 'id' => $id]);
    }
    protected $table = 'series';
    protected $primaryKey = 'Id';

    public function __construct() {
        parent::__construct();
    }

    public function getAll($search = '', $limit = 10, $offset = 0) {
        $sql = "SELECT * FROM series ";
        if ($search) {
            $sql .= "WHERE Serie LIKE :search ";
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
        $sql = "SELECT COUNT(*) FROM series ";
        if ($search) {
            $sql .= "WHERE Serie LIKE :search ";
        }
        $stmt = $this->db->prepare($sql);
        if ($search) $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function findBySerie($serie) {
        $stmt = $this->db->prepare("SELECT * FROM series WHERE Serie = :serie");
        $stmt->execute(['serie' => $serie]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM series WHERE Id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function insertSerie($serie, $centroDistribucion) {
        $stmt = $this->db->prepare("INSERT INTO series (Serie, CentroDistribucion) VALUES (:serie, :centroDistribucion)");
        return $stmt->execute(['serie' => $serie, 'centroDistribucion' => $centroDistribucion]);
    }

    public function getAllForSelect() {
        $stmt = $this->db->prepare("SELECT Id, Serie FROM series ORDER BY Serie");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verificar si la serie tiene movimientos registrados
     * @param int $id ID de la serie
     * @return bool true si tiene movimientos, false si no
     */
    public function hasMovements($id) {
        // Obtener el valor de la serie
        $serieData = $this->find($id);
        if (!$serieData) {
            return false;
        }
        $serieValor = $serieData['Serie'];
        
        // Verificar en recepciones externas (las guías pueden usar la serie)
        // Buscar guías que contengan la serie al inicio del NumeroGuia (ej: T086-000123)
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM recepciones_externas_guias WHERE NumeroGuia LIKE :serie');
        $stmt->execute(['serie' => $serieValor . '%']);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }
        
        // Verificar en recepciones internas (si existe tabla de guías similar)
        try {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM recepciones_internas_guias WHERE NumeroGuia LIKE :serie');
            $stmt->execute(['serie' => $serieValor . '%']);
            if ($stmt->fetchColumn() > 0) {
                return true;
            }
        } catch (PDOException $e) {
            // Si la tabla no existe, continuar con las demás verificaciones
        }
        
        return false;
    }
}
