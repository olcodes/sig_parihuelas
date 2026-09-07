<?php
class Recepcionista extends Model {
    public function createRecepcionista($apellidoPaterno, $nombres, $nombresApellidos) {
        $stmt = $this->db->prepare("INSERT INTO recepcionistas (ApellidoPaterno, Nombres, NombresApellidos) VALUES (:apellidoPaterno, :nombres, :nombresApellidos)");
        return $stmt->execute([
            'apellidoPaterno' => $apellidoPaterno,
            'nombres' => $nombres,
            'nombresApellidos' => $nombresApellidos
        ]);
    }

    public function updateRecepcionista($id, $apellidoPaterno, $nombres, $nombresApellidos) {
        $stmt = $this->db->prepare("UPDATE recepcionistas SET ApellidoPaterno = :apellidoPaterno, Nombres = :nombres, NombresApellidos = :nombresApellidos WHERE Id = :id");
        return $stmt->execute([
            'apellidoPaterno' => $apellidoPaterno,
            'nombres' => $nombres,
            'nombresApellidos' => $nombresApellidos,
            'id' => $id
        ]);
    }

    public function deleteRecepcionista($id) {
        $stmt = $this->db->prepare("DELETE FROM recepcionistas WHERE Id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function getPaginatedFiltered($limit, $offset, $busqueda = '')
    {
        $db = Database::getInstance()->getConnection();
        if ($busqueda !== '') {
            $like = '%' . $busqueda . '%';
            $stmt = $db->prepare('SELECT * FROM recepcionistas WHERE ApellidoPaterno LIKE ? OR Nombres LIKE ? OR NombresApellidos LIKE ? ORDER BY Id DESC LIMIT ? OFFSET ?');
            $stmt->bindValue(1, $like, \PDO::PARAM_STR);
            $stmt->bindValue(2, $like, \PDO::PARAM_STR);
            $stmt->bindValue(3, $like, \PDO::PARAM_STR);
            $stmt->bindValue(4, (int)$limit, \PDO::PARAM_INT);
            $stmt->bindValue(5, (int)$offset, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return $this->getPaginated($limit, $offset);
        }
    }

    public function getPaginated($limit, $offset)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT * FROM recepcionistas ORDER BY Id DESC LIMIT ? OFFSET ?');
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countFiltered($busqueda = '')
    {
        $db = Database::getInstance()->getConnection();
        if ($busqueda !== '') {
            $like = '%' . $busqueda . '%';
            $stmt = $db->prepare('SELECT COUNT(*) FROM recepcionistas WHERE ApellidoPaterno LIKE ? OR Nombres LIKE ? OR NombresApellidos LIKE ?');
            $stmt->execute([$like, $like, $like]);
            return (int)$stmt->fetchColumn();
        } else {
            return $this->countAll();
        }
    }

    public function countAll()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query('SELECT COUNT(*) FROM recepcionistas');
        return (int)$stmt->fetchColumn();
    }

    public function getAll() {
        $sql = "SELECT Id, NombresApellidos FROM recepcionistas ORDER BY NombresApellidos ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllForSelect() {
        return $this->getAll();
    }

    /**
     * Comprueba si ya existe un recepcionista con el mismo NombresApellidos.
     * Si se pasa $excludeId, se excluye ese Id (útil al actualizar).
     */
    public function existsByNombresApellidos($nombresApellidos, $excludeId = null)
    {
        $n = trim($nombresApellidos);
        if ($n === '') return false;
        if ($excludeId) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM recepcionistas WHERE LOWER(TRIM(NombresApellidos)) = LOWER(TRIM(:na)) AND Id != :id');
            $stmt->execute(['na' => $n, 'id' => $excludeId]);
        } else {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM recepcionistas WHERE LOWER(TRIM(NombresApellidos)) = LOWER(TRIM(:na))');
            $stmt->execute(['na' => $n]);
        }
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Verificar si el/la recepcionista tiene movimientos registrados
     * @param int $id ID del recepcionista
     * @return bool true si tiene movimientos, false si no
     */
    public function hasMovements($id)
    {
        $db = Database::getInstance()->getConnection();
        
        // Verificar en despachos internos
        $stmt = $db->prepare('SELECT COUNT(*) FROM despachos_internos WHERE Recepcionista = ?');
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }
        
        return false;
    }
}
