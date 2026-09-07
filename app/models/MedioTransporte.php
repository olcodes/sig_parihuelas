<?php
class MedioTransporte extends Model {
    public function getAll($busqueda = '', $limit = 100, $offset = 0) {
        if ($busqueda !== '') {
            $like = '%' . $busqueda . '%';
            $stmt = $this->db->prepare('SELECT * FROM medio_transporte WHERE MedioTransporte LIKE ? ORDER BY Id ASC LIMIT ? OFFSET ?');
            $stmt->bindValue(1, $like, PDO::PARAM_STR);
            $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(3, (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $this->db->prepare('SELECT * FROM medio_transporte ORDER BY Id ASC LIMIT ? OFFSET ?');
            $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM medio_transporte WHERE Id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getAllForSelect() {
        $stmt = $this->db->prepare('SELECT Id, MedioTransporte FROM medio_transporte ORDER BY MedioTransporte ASC');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
