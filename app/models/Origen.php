<?php

class Origen extends Model {
    public function createOrigen($origen) {
        $stmt = $this->db->prepare("INSERT INTO origen (Origen) VALUES (:origen)");
        return $stmt->execute(['origen' => $origen]);
    }

    public function updateOrigen($id, $origen) {
        $stmt = $this->db->prepare("UPDATE origen SET Origen = :origen WHERE Id = :id");
        return $stmt->execute(['origen' => $origen, 'id' => $id]);
    }

    public function deleteOrigen($id) {
        $stmt = $this->db->prepare("DELETE FROM origen WHERE Id = :id");
        return $stmt->execute(['id' => $id]);
    }
    // Obtener origenes paginados y filtrados
    public function getPaginatedFiltered($limit, $offset, $busqueda = '')
    {
        $db = Database::getInstance()->getConnection();
        if ($busqueda !== '') {
            $like = '%' . $busqueda . '%';
            $stmt = $db->prepare('SELECT * FROM origen WHERE Origen LIKE ? ORDER BY Id DESC LIMIT ? OFFSET ?');
            $stmt->bindValue(1, $like, PDO::PARAM_STR);
            $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(3, (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return $this->getPaginated($limit, $offset);
        }
    }

    public function countFiltered($busqueda = '')
    {
        $db = Database::getInstance()->getConnection();
        if ($busqueda !== '') {
            $like = '%' . $busqueda . '%';
            $stmt = $db->prepare('SELECT COUNT(*) FROM origen WHERE Origen LIKE ?');
            $stmt->execute([$like]);
            return (int)$stmt->fetchColumn();
        } else {
            return $this->countAll();
        }
    }

    public function getPaginated($limit, $offset)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT * FROM origen ORDER BY Id DESC LIMIT ? OFFSET ?');
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query('SELECT COUNT(*) FROM origen');
        return (int)$stmt->fetchColumn();
    }

    public function getById($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT * FROM origen WHERE Id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('INSERT INTO origen (Origen) VALUES (?)');
        return $stmt->execute([
            $data['Origen']
        ]);
    }

    public function update($id, $data)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('UPDATE origen SET Origen = ? WHERE Id = ?');
        return $stmt->execute([
            $data['Origen'],
            $id
        ]);
    }

    public function delete($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('DELETE FROM origen WHERE Id = ?');
        return $stmt->execute([$id]);
    }

    public function existsOrigen($origen, $excludeId = null)
    {
        $db = Database::getInstance()->getConnection();
        if ($excludeId) {
            $stmt = $db->prepare('SELECT COUNT(*) FROM origen WHERE Origen = ? AND Id != ?');
            $stmt->execute([$origen, $excludeId]);
        } else {
            $stmt = $db->prepare('SELECT COUNT(*) FROM origen WHERE Origen = ?');
            $stmt->execute([$origen]);
        }
        return $stmt->fetchColumn() > 0;
    }

    public function getAllForSelect()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT Id, Origen FROM origen ORDER BY Origen ASC');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verificar si el origen tiene movimientos registrados
     * @param int $id ID del origen
     * @return bool true si tiene movimientos, false si no
     */
    public function hasMovements($id)
    {
        $db = Database::getInstance()->getConnection();
        
        // Verificar en recepciones externas
        $stmt = $db->prepare('SELECT COUNT(*) FROM recepciones_externas WHERE Origen = ?');
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }
        
        return false;
    }
}
