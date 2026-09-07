<?php

class Chofer extends Model {
    // Obtener todos los choferes
    public function getAll() {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query('SELECT * FROM choferes ORDER BY Id DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function createChofer($nacionalidad, $docIdentidad, $apellidosPaterno, $apellidoMaterno, $nombres, $apellidosNombres, $brevete) {
        $stmt = $this->db->prepare("INSERT INTO choferes (Nacionalidad, DocIdentidad, ApellidosPaterno, ApellidoMaterno, Nombres, ApellidosNombres, Brevete) VALUES (:nacionalidad, :DocIdentidad, :apellidosPaterno, :apellidoMaterno, :nombres, :apellidosNombres, :brevete)");
        return $stmt->execute([
            'nacionalidad' => $nacionalidad,
            'DocIdentidad' => $docIdentidad,
            'apellidosPaterno' => $apellidosPaterno,
            'apellidoMaterno' => $apellidoMaterno,
            'nombres' => $nombres,
            'apellidosNombres' => $apellidosNombres,
            'brevete' => $brevete
        ]);
    }

    public function updateChofer($id, $nacionalidad, $docIdentidad, $apellidosPaterno, $apellidoMaterno, $nombres, $apellidosNombres, $brevete) {
        $stmt = $this->db->prepare("UPDATE choferes SET Nacionalidad = :nacionalidad, DocIdentidad = :DocIdentidad, ApellidosPaterno = :apellidosPaterno, ApellidoMaterno = :apellidoMaterno, Nombres = :nombres, ApellidosNombres = :apellidosNombres, Brevete = :brevete WHERE Id = :id");
        return $stmt->execute([
            'nacionalidad' => $nacionalidad,
            'DocIdentidad' => $docIdentidad,
            'apellidosPaterno' => $apellidosPaterno,
            'apellidoMaterno' => $apellidoMaterno,
            'nombres' => $nombres,
            'apellidosNombres' => $apellidosNombres,
            'brevete' => $brevete,
            'id' => $id
        ]);
    }

    public function deleteChofer($id) {
        $stmt = $this->db->prepare("DELETE FROM choferes WHERE Id = :id");
        return $stmt->execute(['id' => $id]);
    }
    // Obtener choferes paginados y filtrados
    public function getPaginatedFiltered($limit, $offset, $busqueda = '')
    {
        $db = Database::getInstance()->getConnection();
        if ($busqueda !== '') {
            $like = '%' . $busqueda . '%';
            $stmt = $db->prepare('SELECT * FROM choferes WHERE ApellidosPaterno LIKE ? OR ApellidoMaterno LIKE ? OR Nombres LIKE ? OR ApellidosNombres LIKE ? OR Brevete LIKE ? ORDER BY Id DESC LIMIT ? OFFSET ?');
            $stmt->bindValue(1, $like, \PDO::PARAM_STR);
            $stmt->bindValue(2, $like, \PDO::PARAM_STR);
            $stmt->bindValue(3, $like, \PDO::PARAM_STR);
            $stmt->bindValue(4, $like, \PDO::PARAM_STR);
            $stmt->bindValue(5, $like, \PDO::PARAM_STR);
            $stmt->bindValue(6, (int)$limit, \PDO::PARAM_INT);
            $stmt->bindValue(7, (int)$offset, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return $this->getPaginated($limit, $offset);
        }
    }

    // Contar total de choferes filtrados
    public function countFiltered($busqueda = '')
    {
        $db = Database::getInstance()->getConnection();
        if ($busqueda !== '') {
            $like = '%' . $busqueda . '%';
            $stmt = $db->prepare('SELECT COUNT(*) FROM choferes WHERE ApellidosPaterno LIKE ? OR ApellidoMaterno LIKE ? OR Nombres LIKE ? OR ApellidosNombres LIKE ? OR Brevete LIKE ?');
            $stmt->execute([$like, $like, $like, $like, $like]);
            return (int)$stmt->fetchColumn();
        } else {
            return $this->countAll();
        }
    }
    // Verificar si existe brevete (para alta)
    public function existsBrevete($brevete, $excludeId = null)
    {
        $db = Database::getInstance()->getConnection();
        if ($excludeId) {
            $stmt = $db->prepare('SELECT COUNT(*) FROM choferes WHERE Brevete = ? AND Id != ?');
            $stmt->execute([$brevete, $excludeId]);
        } else {
            $stmt = $db->prepare('SELECT COUNT(*) FROM choferes WHERE Brevete = ?');
            $stmt->execute([$brevete]);
        }
        return $stmt->fetchColumn() > 0;
    }
    // Obtener choferes paginados
    public function getPaginated($limit, $offset)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT * FROM choferes ORDER BY Id DESC LIMIT ? OFFSET ?');
        $stmt->bindValue(1, (int)$limit, \PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Contar total de choferes
    public function countAll()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query('SELECT COUNT(*) FROM choferes');
        return (int)$stmt->fetchColumn();
    }

    // Obtener un chofer por ID
    public function getById($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT * FROM choferes WHERE Id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Crear chofer
    public function create($data)
    {
        $db = Database::getInstance()->getConnection();
        $apellidosNombres = trim($data['ApellidosPaterno'] . ' ' . $data['ApellidoMaterno'] . ' ' . $data['Nombres']);
        $stmt = $db->prepare('INSERT INTO choferes (Nacionalidad, DocIdentidad, ApellidosPaterno, ApellidoMaterno, Nombres, ApellidosNombres, Brevete) VALUES (?, ?, ?, ?, ?, ?, ?)');
        return $stmt->execute([
            $data['Nacionalidad'] ?? 'PERUANO',
            $data['DocIdentidad'] ?? '',
            $data['ApellidosPaterno'],
            $data['ApellidoMaterno'],
            $data['Nombres'],
            $apellidosNombres,
            $data['Brevete']
        ]);
    }

    // Actualizar chofer
    public function update($id, $data)
    {
        $db = Database::getInstance()->getConnection();
        $apellidosNombres = trim($data['ApellidosPaterno'] . ' ' . $data['ApellidoMaterno'] . ' ' . $data['Nombres']);
        $stmt = $db->prepare('UPDATE choferes SET Nacionalidad = ?, DocIdentidad = ?, ApellidosPaterno = ?, ApellidoMaterno = ?, Nombres = ?, ApellidosNombres = ?, Brevete = ? WHERE Id = ?');
        return $stmt->execute([
            $data['Nacionalidad'] ?? 'PERUANO',
            $data['DocIdentidad'] ?? '',
            $data['ApellidosPaterno'],
            $data['ApellidoMaterno'],
            $data['Nombres'],
            $apellidosNombres,
            $data['Brevete'],
            $id
        ]);
    }

    // Eliminar chofer
    public function delete($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('DELETE FROM choferes WHERE Id = ?');
        return $stmt->execute([$id]);
    }

    public function getAllForSelect()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT Id, ApellidosNombres, Brevete FROM choferes ORDER BY ApellidosNombres ASC');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verificar si el chofer tiene movimientos registrados
     * @param int $id ID del chofer
     * @return bool true si tiene movimientos, false si no
     */
    public function hasMovements($id)
    {
        $db = Database::getInstance()->getConnection();
        
        // Verificar en despachos externos
        $stmt = $db->prepare('SELECT COUNT(*) FROM despachos_externos WHERE Chofer = ?');
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }
        
        // Verificar en recepciones externas
        $stmt = $db->prepare('SELECT COUNT(*) FROM recepciones_externas WHERE Chofer = ?');
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }
        
        return false;
    }
}
