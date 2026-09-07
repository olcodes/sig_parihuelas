<?php

class Placa extends Model
{
    public function getPaginatedFiltered($limit, $offset, $busqueda = '')
    {
        try {
            if (!empty($busqueda)) {
                $query = "SELECT Id, Placa, TipoPlaca, ConstanciaInscripcion FROM placas 
                          WHERE Placa LIKE :busqueda1 OR ConstanciaInscripcion LIKE :busqueda2 OR TipoPlaca LIKE :busqueda1
                          ORDER BY Id DESC LIMIT :limit OFFSET :offset";
                $stmt = $this->db->prepare($query);
                $searchTerm = '%' . $busqueda . '%';
                $stmt->bindValue(':busqueda1', $searchTerm, PDO::PARAM_STR);
                $stmt->bindValue(':busqueda2', $searchTerm, PDO::PARAM_STR);
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
            } else {
                $query = "SELECT Id, Placa, TipoPlaca, ConstanciaInscripcion FROM placas 
                          ORDER BY Id DESC LIMIT :limit OFFSET :offset";
                $stmt = $this->db->prepare($query);
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
            }
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Fallback si la columna TipoPlaca no existe: ejecutar consultas sin esa columna
            if (!empty($busqueda)) {
                $query = "SELECT Id, Placa, ConstanciaInscripcion FROM placas 
                          WHERE Placa LIKE :busqueda1 OR ConstanciaInscripcion LIKE :busqueda2
                          ORDER BY Id DESC LIMIT :limit OFFSET :offset";
                $stmt = $this->db->prepare($query);
                $searchTerm = '%' . $busqueda . '%';
                $stmt->bindValue(':busqueda1', $searchTerm, PDO::PARAM_STR);
                $stmt->bindValue(':busqueda2', $searchTerm, PDO::PARAM_STR);
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
            } else {
                $query = "SELECT Id, Placa, ConstanciaInscripcion FROM placas ORDER BY Id DESC LIMIT :limit OFFSET :offset";
                $stmt = $this->db->prepare($query);
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
            }
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Añadir campo TipoPlaca vacío para compatibilidad con el resto del código
            foreach ($rows as &$r) {
                if (!array_key_exists('TipoPlaca', $r)) {
                    $r['TipoPlaca'] = '';
                }
            }
            unset($r);
        }
        return $rows;
    }

    public function countFiltered($busqueda = '')
    {
        try {
            if (!empty($busqueda)) {
                $query = "SELECT COUNT(*) FROM placas 
                          WHERE Placa LIKE :busqueda1 OR ConstanciaInscripcion LIKE :busqueda2 OR TipoPlaca LIKE :busqueda1";
                $stmt = $this->db->prepare($query);
                $searchTerm = '%' . $busqueda . '%';
                $stmt->execute(['busqueda1' => $searchTerm, 'busqueda2' => $searchTerm]);
            } else {
                $query = "SELECT COUNT(*) FROM placas";
                $stmt = $this->db->query($query);
            }
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            // Fallback sin TipoPlaca
            if (!empty($busqueda)) {
                $query = "SELECT COUNT(*) FROM placas 
                          WHERE Placa LIKE :busqueda1 OR ConstanciaInscripcion LIKE :busqueda2";
                $stmt = $this->db->prepare($query);
                $searchTerm = '%' . $busqueda . '%';
                $stmt->execute(['busqueda1' => $searchTerm, 'busqueda2' => $searchTerm]);
                return (int) $stmt->fetchColumn();
            }
            $query = "SELECT COUNT(*) FROM placas";
            $stmt = $this->db->query($query);
            return (int) $stmt->fetchColumn();
        }
    }

    public function getAll()
    {
        try {
            $query = "SELECT Id, Placa, TipoPlaca, ConstanciaInscripcion FROM placas ORDER BY Placa ASC";
            $stmt = $this->db->query($query);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $query = "SELECT Id, Placa, ConstanciaInscripcion FROM placas ORDER BY Placa ASC";
            $stmt = $this->db->query($query);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$r) {
                if (!array_key_exists('TipoPlaca', $r)) {
                    $r['TipoPlaca'] = '';
                }
            }
            unset($r);
        }
        return $rows;
    }

    public function getAllForSelect()
    {
        $query = "SELECT Id, Placa FROM placas ORDER BY Placa ASC";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        try {
            $query = "SELECT Id, Placa, TipoPlaca, ConstanciaInscripcion FROM placas WHERE Id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $query = "SELECT Id, Placa, ConstanciaInscripcion FROM placas WHERE Id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row !== false && !array_key_exists('TipoPlaca', $row)) {
                $row['TipoPlaca'] = '';
            }
        }
        return $row;
    }

    public function create($placa, $tipoPlaca, $constanciaInscripcion)
    {
        try {
            $query = "INSERT INTO placas (Placa, TipoPlaca, ConstanciaInscripcion) VALUES (:placa, :tipo, :constancia)";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                'placa' => $placa,
                'tipo' => $tipoPlaca,
                'constancia' => $constanciaInscripcion
            ]);
        } catch (PDOException $e) {
            // Fallback si la columna TipoPlaca no existe: insertar sin esa columna
            $query = "INSERT INTO placas (Placa, ConstanciaInscripcion) VALUES (:placa, :constancia)";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                'placa' => $placa,
                'constancia' => $constanciaInscripcion
            ]);
        }
    }

    public function update($id, $placa, $tipoPlaca, $constanciaInscripcion)
    {
        try {
            $query = "UPDATE placas SET Placa = :placa, TipoPlaca = :tipo, ConstanciaInscripcion = :constancia WHERE Id = :id";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                'id' => $id,
                'placa' => $placa,
                'tipo' => $tipoPlaca,
                'constancia' => $constanciaInscripcion
            ]);
        } catch (PDOException $e) {
            // Fallback si la columna TipoPlaca no existe: actualizar sin esa columna
            $query = "UPDATE placas SET Placa = :placa, ConstanciaInscripcion = :constancia WHERE Id = :id";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                'id' => $id,
                'placa' => $placa,
                'constancia' => $constanciaInscripcion
            ]);
        }
    }

    public function delete($id)
    {
        $query = "DELETE FROM placas WHERE Id = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute(['id' => $id]);
    }

    public function exists($placa, $excludeId = null)
    {
        if ($excludeId) {
            $query = "SELECT COUNT(*) FROM placas WHERE Placa = :placa AND Id != :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute(['placa' => $placa, 'id' => $excludeId]);
        } else {
            $query = "SELECT COUNT(*) FROM placas WHERE Placa = :placa";
            $stmt = $this->db->prepare($query);
            $stmt->execute(['placa' => $placa]);
        }
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Verificar si la placa tiene movimientos registrados
     * @param int $id ID de la placa
     * @return bool true si tiene movimientos, false si no
     */
    public function hasMovements($id)
    {
        // Obtener el número de placa para buscar en las tablas
        $placaData = $this->getById($id);
        if (!$placaData) {
            return false;
        }
        $placaNumero = $placaData['Placa'];
        
        // Verificar en despachos externos (Placa_Tracto o Placa_Carreta)
        $query = "SELECT COUNT(*) FROM despachos_externos WHERE Placa_Tracto = :placa1 OR Placa_Carreta = :placa2";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['placa1' => $placaNumero, 'placa2' => $placaNumero]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }
        
        return false;
    }
}
