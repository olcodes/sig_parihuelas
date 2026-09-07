<?php

class Destino extends Model {
    public function createDestino($empresa, $ruc, $direccion) {
        $stmt = $this->db->prepare("INSERT INTO destino (Empresa, RUC, Direccion) VALUES (:empresa, :ruc, :direccion)");
        return $stmt->execute(['empresa' => $empresa, 'ruc' => $ruc, 'direccion' => $direccion]);
    }

    public function updateDestino($id, $empresa, $ruc, $direccion) {
        $stmt = $this->db->prepare("UPDATE destino SET Empresa = :empresa, RUC = :ruc, Direccion = :direccion WHERE Id = :id");
        return $stmt->execute(['empresa' => $empresa, 'ruc' => $ruc, 'direccion' => $direccion, 'id' => $id]);
    }

    public function deleteDestino($id) {
        $stmt = $this->db->prepare("DELETE FROM destino WHERE Id = :id");
        return $stmt->execute(['id' => $id]);
    }
    // Obtener destinos paginados y filtrados
    public function getPaginatedFiltered($limit, $offset, $busqueda = '')
    {
        $db = Database::getInstance()->getConnection();
        if ($busqueda !== '') {
            $like = '%' . $busqueda . '%';
            $stmt = $db->prepare('SELECT * FROM destino WHERE Empresa LIKE ? OR RUC LIKE ? OR Direccion LIKE ? ORDER BY Id DESC LIMIT ? OFFSET ?');
            $stmt->bindValue(1, $like, PDO::PARAM_STR);
            $stmt->bindValue(2, $like, PDO::PARAM_STR);
            $stmt->bindValue(3, $like, PDO::PARAM_STR);
            $stmt->bindValue(4, (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(5, (int)$offset, PDO::PARAM_INT);
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
            $stmt = $db->prepare('SELECT COUNT(*) FROM destino WHERE Empresa LIKE ? OR RUC LIKE ? OR Direccion LIKE ?');
            $stmt->execute([$like, $like, $like]);
            return (int)$stmt->fetchColumn();
        } else {
            return $this->countAll();
        }
    }

    public function getPaginated($limit, $offset)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT * FROM destino ORDER BY Id DESC LIMIT ? OFFSET ?');
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query('SELECT COUNT(*) FROM destino');
        return (int)$stmt->fetchColumn();
    }

    public function getById($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT * FROM destino WHERE Id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Intento flexible de obtener destino por Id.
     * Trata primero la tabla `destino`, y si falla intenta `destinos`.
     */
    public function getByIdFlexible($id)
    {
        try {
            return $this->getById($id);
        } catch (Exception $e) {
            // intentar tabla plural 'destinos' por compatibilidad con esquemas diferentes
            try {
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare('SELECT * FROM destinos WHERE Id = ?');
                $stmt->execute([$id]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $__) {
                return null;
            }
        }
    }

    public function create($data)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('INSERT INTO destino (Empresa, RUC, Direccion) VALUES (?, ?, ?)');
        return $stmt->execute([
            $data['Empresa'],
            $data['RUC'],
            $data['Direccion']
        ]);
    }

    public function update($id, $data)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('UPDATE destino SET Empresa = ?, RUC = ?, Direccion = ? WHERE Id = ?');
        return $stmt->execute([
            $data['Empresa'],
            $data['RUC'],
            $data['Direccion'],
            $id
        ]);
    }

    public function delete($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('DELETE FROM destino WHERE Id = ?');
        return $stmt->execute([$id]);
    }

    public function existsRUC($ruc, $excludeId = null)
    {
        $db = Database::getInstance()->getConnection();
        if ($excludeId) {
            $stmt = $db->prepare('SELECT COUNT(*) FROM destino WHERE RUC = ? AND Id != ?');
            $stmt->execute([$ruc, $excludeId]);
        } else {
            $stmt = $db->prepare('SELECT COUNT(*) FROM destino WHERE RUC = ?');
            $stmt->execute([$ruc]);
        }
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Verificar si el destino tiene movimientos registrados
     * @param int $id ID del destino
     * @return bool true si tiene movimientos, false si no
     */
    public function hasMovements($id)
    {
        $db = Database::getInstance()->getConnection();
        
        // Verificar en despachos externos
        $stmt = $db->prepare('SELECT COUNT(*) FROM despachos_externos WHERE Destino = ?');
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }
        
        return false;
    }

    /**
     * Obtener todos los destinos con detección flexible de tabla
     * @return array
     */
    public function getAll()
    {
        $db = Database::getInstance()->getConnection();
        
        // Intentar detectar la tabla correcta
        $tableNames = ['clientesexternos', 'clientes_externos', 'destino', 'destinos'];
        $detectedTable = null;
        
        foreach ($tableNames as $tableName) {
            try {
                $stmt = $db->prepare("SELECT TABLE_NAME FROM information_schema.TABLES 
                                     WHERE TABLE_SCHEMA = DATABASE() 
                                     AND TABLE_NAME = ?");
                $stmt->execute([$tableName]);
                if ($stmt->fetchColumn()) {
                    $detectedTable = $tableName;
                    break;
                }
            } catch (Exception $e) {
                continue;
            }
        }
        
        if (!$detectedTable) {
            error_log('[Destino::getAll] No se pudo detectar tabla de destinos');
            return [];
        }
        
        error_log('[Destino::getAll] Usando tabla: ' . $detectedTable);
        
        try {
            // Intentar con diferentes estructuras de columnas
            $stmt = $db->prepare("SELECT * FROM $detectedTable ORDER BY Id DESC");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log('[Destino::getAll] Retornando ' . count($results) . ' destinos');
            return $results;
        } catch (Exception $e) {
            error_log('[Destino::getAll] Error: ' . $e->getMessage());
            return [];
        }
    }
}
