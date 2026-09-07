<?php
class Responsable extends Model {
    public function createResponsable($apellidoPaterno, $nombres, $nombresApellidos) {
        $stmt = $this->db->prepare("INSERT INTO responsables (ApellidoPaterno, Nombres, NombresApellidos) VALUES (:apellidoPaterno, :nombres, :nombresApellidos)");
        return $stmt->execute([
            'apellidoPaterno' => $apellidoPaterno,
            'nombres' => $nombres,
            'nombresApellidos' => $nombresApellidos
        ]);
    }

    public function updateResponsable($id, $apellidoPaterno, $nombres, $nombresApellidos) {
        $stmt = $this->db->prepare("UPDATE responsables SET ApellidoPaterno = :apellidoPaterno, Nombres = :nombres, NombresApellidos = :nombresApellidos WHERE Id = :id");
        return $stmt->execute([
            'apellidoPaterno' => $apellidoPaterno,
            'nombres' => $nombres,
            'nombresApellidos' => $nombresApellidos,
            'id' => $id
        ]);
    }

    public function deleteResponsable($id) {
        $stmt = $this->db->prepare("DELETE FROM responsables WHERE Id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function getPaginatedFiltered($limit, $offset, $busqueda = '')
    {
        $db = Database::getInstance()->getConnection();
        if ($busqueda !== '') {
            $like = '%' . $busqueda . '%';
            $stmt = $db->prepare('SELECT * FROM responsables WHERE ApellidoPaterno LIKE ? OR Nombres LIKE ? OR NombresApellidos LIKE ? ORDER BY Id DESC LIMIT ? OFFSET ?');
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

    public function countFiltered($busqueda = '')
    {
        $db = Database::getInstance()->getConnection();
        if ($busqueda !== '') {
            $like = '%' . $busqueda . '%';
            $stmt = $db->prepare('SELECT COUNT(*) FROM responsables WHERE ApellidoPaterno LIKE ? OR Nombres LIKE ? OR NombresApellidos LIKE ?');
            $stmt->execute([$like, $like, $like]);
            return (int)$stmt->fetchColumn();
        } else {
            return $this->countAll();
        }
    }

    public function getPaginated($limit, $offset)
    {
        $stmt = $this->db->prepare('SELECT * FROM responsables ORDER BY Id DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll()
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM responsables');
        return (int)$stmt->fetchColumn();
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare('SELECT * FROM responsables WHERE Id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll() {
        $sql = "SELECT Id, NombresApellidos FROM responsables ORDER BY NombresApellidos ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllForSelect() {
        return $this->getAll();
    }

    /**
     * Comprueba si ya existe un registro con el mismo NombresApellidos.
     * Si se pasa $excludeId, se excluye ese Id (útil al actualizar).
     * Normaliza los espacios múltiples a uno solo antes de comparar.
     */
    public function existsByNombresApellidos($nombresApellidos, $excludeId = null)
    {
        // Normalizar: trim, convertir a mayúsculas y reducir espacios múltiples a uno solo
        $n = trim($nombresApellidos);
        if ($n === '') return false;
        $n = preg_replace('/\s+/', ' ', $n); // Normalizar espacios múltiples
        
        if ($excludeId) {
            // Para actualizar: excluir el ID actual
            $stmt = $this->db->prepare('SELECT Id, NombresApellidos FROM responsables WHERE Id != :id');
            $stmt->execute(['id' => $excludeId]);
        } else {
            // Para crear: revisar todos
            $stmt = $this->db->prepare('SELECT Id, NombresApellidos FROM responsables');
            $stmt->execute();
        }
        
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($registros as $reg) {
            $existing = preg_replace('/\s+/', ' ', trim($reg['NombresApellidos']));
            if (strcasecmp($n, $existing) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verificar si el responsable tiene movimientos registrados
     * @param int $id ID del responsable
     * @return bool true si tiene movimientos, false si no
     */
    public function hasMovements($id)
    {
        $db = Database::getInstance()->getConnection();
        
        // Verificar en despachos internos (Despachador o Verificador)
        $stmt = $db->prepare('SELECT COUNT(*) FROM despachos_internos WHERE Despachador = ? OR Verificador = ?');
        $stmt->execute([$id, $id]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }
        
        // Verificar en recepciones internas (Despachador o Verificador)
        $stmt = $db->prepare('SELECT COUNT(*) FROM recepciones_internas WHERE Despachador = ? OR Verificador = ?');
        $stmt->execute([$id, $id]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }
        
        return false;
    }
}
