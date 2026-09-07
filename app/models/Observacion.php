<?php
class Observacion extends Model {
    public function createObservacion($item, $observaciones) {
        $stmt = $this->db->prepare("INSERT INTO observaciones (Item, Observaciones) VALUES (:item, :observaciones)");
        return $stmt->execute(['item' => $item, 'observaciones' => $observaciones]);
    }

    public function updateObservacion($id, $item, $observaciones) {
        $stmt = $this->db->prepare("UPDATE observaciones SET Item = :item, Observaciones = :observaciones WHERE Id = :id");
        return $stmt->execute(['item' => $item, 'observaciones' => $observaciones, 'id' => $id]);
    }

    public function deleteObservacion($id) {
        $stmt = $this->db->prepare("DELETE FROM observaciones WHERE Id = :id");
        return $stmt->execute(['id' => $id]);
    }
    protected $table = 'observaciones';
    protected $primaryKey = 'Id';


    public function getAll($search = '', $limit = 10, $offset = 0) {
        $sql = "SELECT * FROM observaciones ";
        if ($search) {
            // use distinct placeholders to avoid driver issues with repeated named params
            $sql .= "WHERE Item LIKE :search1 OR Observaciones LIKE :search2 ";
        }
        $sql .= "ORDER BY Id DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        if ($search) {
            $stmt->bindValue(':search1', "%$search%", PDO::PARAM_STR);
            $stmt->bindValue(':search2', "%$search%", PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll($search = '') {
        $sql = "SELECT COUNT(*) FROM observaciones ";
        if ($search) {
            $sql .= "WHERE Item LIKE :search1 OR Observaciones LIKE :search2 ";
        }
        $stmt = $this->db->prepare($sql);
        if ($search) {
            $stmt->bindValue(':search1', "%$search%", PDO::PARAM_STR);
            $stmt->bindValue(':search2', "%$search%", PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function findByItem($item) {
        $stmt = $this->db->prepare("SELECT * FROM observaciones WHERE Item = :item");
        $stmt->execute(['item' => $item]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM observaciones WHERE Id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllForSelect() {
        $stmt = $this->db->prepare("SELECT Id, Item, Observaciones FROM observaciones ORDER BY Item ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function __construct() {
        parent::__construct();
    }
}
