<?php

class Transportista extends Model {
    // Obtener todos los transportistas
    public function getAll() {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query('SELECT * FROM transportistas ORDER BY Id DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function createTransportista($ruc, $empresa) {
        $stmt = $this->db->prepare("INSERT INTO transportistas (RUC, Empresa) VALUES (:ruc, :empresa)");
        return $stmt->execute(['ruc' => $ruc, 'empresa' => $empresa]);
    }

    public function updateTransportista($id, $ruc, $empresa) {
        $stmt = $this->db->prepare("UPDATE transportistas SET RUC = :ruc, Empresa = :empresa WHERE Id = :id");
        return $stmt->execute(['ruc' => $ruc, 'empresa' => $empresa, 'id' => $id]);
    }

    public function deleteTransportista($id) {
        $stmt = $this->db->prepare("DELETE FROM transportistas WHERE Id = :id");
        return $stmt->execute(['id' => $id]);
    }
    // Obtener transportistas paginados y filtrados
    public function getPaginatedFiltered($limit, $offset, $busqueda = '')
    {
        $db = Database::getInstance()->getConnection();
        if ($busqueda !== '') {
            $like = '%' . $busqueda . '%';
            $stmt = $db->prepare('SELECT * FROM transportistas WHERE RUC LIKE ? OR Empresa LIKE ? ORDER BY Id DESC LIMIT ? OFFSET ?');
            $stmt->bindValue(1, $like, PDO::PARAM_STR);
            $stmt->bindValue(2, $like, PDO::PARAM_STR);
            $stmt->bindValue(3, (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(4, (int)$offset, PDO::PARAM_INT);
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
            $stmt = $db->prepare('SELECT COUNT(*) FROM transportistas WHERE RUC LIKE ? OR Empresa LIKE ?');
            $stmt->execute([$like, $like]);
            return (int)$stmt->fetchColumn();
        } else {
            return $this->countAll();
        }
    }

    public function getPaginated($limit, $offset)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT * FROM transportistas ORDER BY Id DESC LIMIT ? OFFSET ?');
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query('SELECT COUNT(*) FROM transportistas');
        return (int)$stmt->fetchColumn();
    }

    public function getById($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT * FROM transportistas WHERE Id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('INSERT INTO transportistas (RUC, Empresa) VALUES (?, ?)');
        return $stmt->execute([
            $data['RUC'],
            $data['Empresa']
        ]);
    }

    public function update($id, $data)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('UPDATE transportistas SET RUC = ?, Empresa = ? WHERE Id = ?');
        return $stmt->execute([
            $data['RUC'],
            $data['Empresa'],
            $id
        ]);
    }

    public function delete($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('DELETE FROM transportistas WHERE Id = ?');
        return $stmt->execute([$id]);
    }

    public function existsRUC($ruc, $excludeId = null)
    {
        $db = Database::getInstance()->getConnection();
        if ($excludeId) {
            $stmt = $db->prepare('SELECT COUNT(*) FROM transportistas WHERE RUC = ? AND Id != ?');
            $stmt->execute([$ruc, $excludeId]);
        } else {
            $stmt = $db->prepare('SELECT COUNT(*) FROM transportistas WHERE RUC = ?');
            $stmt->execute([$ruc]);
        }
        return $stmt->fetchColumn() > 0;
    }

    public function getAllForSelect()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT Id, Empresa, RUC FROM transportistas ORDER BY Empresa ASC');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verificar si el transportista tiene movimientos registrados
     * @param int $id ID del transportista
     * @return bool true si tiene movimientos, false si no
     */
    public function hasMovements($id)
    {
        $db = Database::getInstance()->getConnection();
        
        // Verificar en despachos externos
        $stmt = $db->prepare('SELECT COUNT(*) FROM despachos_externos WHERE Transportista = ?');
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }
        
        // Verificar en recepciones externas (por empresa/RUC)
        $stmt = $db->prepare('SELECT COUNT(*) FROM recepciones_externas WHERE Empresa = ?');
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }
        
        return false;
    }
}
