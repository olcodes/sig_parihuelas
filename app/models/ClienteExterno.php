<?php

class ClienteExterno extends Model {
    public function createClienteExterno($ruc, $empresa, $direccion, $direccion2 = null, $direccion3 = null, $direccion4 = null, $direccion5 = null) {
        $stmt = $this->db->prepare("INSERT INTO clientesexternos (RUC, Empresa, Direccion, Direccion2, Direccion3, Direccion4, Direccion5) VALUES (:ruc, :empresa, :direccion, :d2, :d3, :d4, :d5)");
        try {
            $ok = $stmt->execute([
                'ruc' => $ruc,
                'empresa' => $empresa,
                'direccion' => $direccion,
                'd2' => $direccion2,
                'd3' => $direccion3,
                'd4' => $direccion4,
                'd5' => $direccion5
            ]);
            if (!$ok) {
                $err = $stmt->errorInfo();
                $serverInfo = ['REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? '', 'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? ''];
                $log = "[".date('Y-m-d H:i:s')."] createClienteExterno failed - RUC={$ruc} - err=".json_encode($err)." SERVER=".json_encode($serverInfo)."\n";
                @file_put_contents(dirname(__DIR__,2).'/logs/clientesexternos_error.log', $log, FILE_APPEND);
            }
            return $ok;
        } catch (\PDOException $e) {
            $err = $e->getMessage();
            $serverInfo = ['REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? '', 'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? ''];
            $log = "[".date('Y-m-d H:i:s')."] createClienteExterno EXCEPTION - RUC={$ruc} - msg=" . $err . " SERVER=".json_encode($serverInfo)."\n";
            @file_put_contents(dirname(__DIR__,2).'/logs/clientesexternos_error.log', $log, FILE_APPEND);
            return false;
        }
    }

    public function updateClienteExterno($id, $ruc, $empresa, $direccion, $direccion2 = null, $direccion3 = null, $direccion4 = null, $direccion5 = null) {
        $stmt = $this->db->prepare("UPDATE clientesexternos SET RUC = :ruc, Empresa = :empresa, Direccion = :direccion, Direccion2 = :d2, Direccion3 = :d3, Direccion4 = :d4, Direccion5 = :d5 WHERE Id = :id");
        try {
            $ok = $stmt->execute([
                'ruc' => $ruc,
                'empresa' => $empresa,
                'direccion' => $direccion,
                'd2' => $direccion2,
                'd3' => $direccion3,
                'd4' => $direccion4,
                'd5' => $direccion5,
                'id' => $id
            ]);
            if (!$ok) {
                $err = $stmt->errorInfo();
                $serverInfo = ['REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? '', 'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? ''];
                $log = "[".date('Y-m-d H:i:s')."] updateClienteExterno failed - Id={$id} RUC={$ruc} - err=".json_encode($err)." SERVER=".json_encode($serverInfo)."\n";
                @file_put_contents(dirname(__DIR__,2).'/logs/clientesexternos_error.log', $log, FILE_APPEND);
            }
            return $ok;
        } catch (\PDOException $e) {
            $err = $e->getMessage();
            $serverInfo = ['REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? '', 'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? ''];
            $log = "[".date('Y-m-d H:i:s')."] updateClienteExterno EXCEPTION - Id={$id} RUC={$ruc} - msg=" . $err . " SERVER=".json_encode($serverInfo)."\n";
            @file_put_contents(dirname(__DIR__,2).'/logs/clientesexternos_error.log', $log, FILE_APPEND);
            return false;
        }
    }

    public function deleteClienteExterno($id) {
        $stmt = $this->db->prepare("DELETE FROM clientesexternos WHERE Id = :id");
        return $stmt->execute(['id' => $id]);
    }
    // Obtener clientes externos paginados y filtrados
    public function getPaginatedFiltered($limit, $offset, $busqueda = '')
    {
        $db = Database::getInstance()->getConnection();
        if ($busqueda !== '') {
            $like = '%' . $busqueda . '%';
            $stmt = $db->prepare('SELECT * FROM clientesexternos WHERE RUC LIKE ? OR Empresa LIKE ? OR Direccion LIKE ? OR Direccion2 LIKE ? OR Direccion3 LIKE ? ORDER BY Id DESC LIMIT ? OFFSET ?');
            $stmt->bindValue(1, $like, PDO::PARAM_STR);
            $stmt->bindValue(2, $like, PDO::PARAM_STR);
            $stmt->bindValue(3, $like, PDO::PARAM_STR);
            $stmt->bindValue(4, $like, PDO::PARAM_STR);
            $stmt->bindValue(5, $like, PDO::PARAM_STR);
            $stmt->bindValue(6, (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(7, (int)$offset, PDO::PARAM_INT);
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
            $stmt = $db->prepare('SELECT COUNT(*) FROM clientesexternos WHERE RUC LIKE ? OR Empresa LIKE ? OR Direccion LIKE ? OR Direccion2 LIKE ? OR Direccion3 LIKE ?');
            $stmt->execute([$like, $like, $like, $like, $like]);
            return (int)$stmt->fetchColumn();
        } else {
            return $this->countAll();
        }
    }

    public function getPaginated($limit, $offset)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT * FROM clientesexternos ORDER BY Id DESC LIMIT ? OFFSET ?');
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query('SELECT COUNT(*) FROM clientesexternos');
        return (int)$stmt->fetchColumn();
    }

    public function getById($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT * FROM clientesexternos WHERE Id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('INSERT INTO clientesexternos (RUC, Empresa, Direccion, Direccion2, Direccion3, Direccion4, Direccion5) VALUES (?, ?, ?, ?, ?, ?, ?)');
        return $stmt->execute([
            $data['RUC'],
            $data['Empresa'],
            $data['Direccion'],
            $data['Direccion2'] ?? null,
            $data['Direccion3'] ?? null,
            $data['Direccion4'] ?? null,
            $data['Direccion5'] ?? null
        ]);
    }

    public function update($id, $data)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('UPDATE clientesexternos SET RUC = ?, Empresa = ?, Direccion = ?, Direccion2 = ?, Direccion3 = ?, Direccion4 = ?, Direccion5 = ? WHERE Id = ?');
        return $stmt->execute([
            $data['RUC'],
            $data['Empresa'],
            $data['Direccion'],
            $data['Direccion2'] ?? null,
            $data['Direccion3'] ?? null,
            $data['Direccion4'] ?? null,
            $data['Direccion5'] ?? null,
            $id
        ]);
    }

    public function delete($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('DELETE FROM clientesexternos WHERE Id = ?');
        return $stmt->execute([$id]);
    }

    public function existsRUC($ruc, $excludeId = null)
    {
        $db = Database::getInstance()->getConnection();
        if ($excludeId) {
            $stmt = $db->prepare('SELECT COUNT(*) FROM clientesexternos WHERE RUC = ? AND Id != ?');
            $stmt->execute([$ruc, $excludeId]);
        } else {
            $stmt = $db->prepare('SELECT COUNT(*) FROM clientesexternos WHERE RUC = ?');
            $stmt->execute([$ruc]);
        }
        return $stmt->fetchColumn() > 0;
    }
    
    public function getAllForSelect() {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT Id, RUC, Empresa AS RazonSocial, Direccion FROM clientesexternos ORDER BY Empresa ASC');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verificar si el cliente externo tiene movimientos registrados
     * @param int $id ID del cliente externo
     * @return bool true si tiene movimientos, false si no
     */
    public function hasMovements($id)
    {
        $db = Database::getInstance()->getConnection();
        try {
            // Obtener RUC del cliente
            $stmt = $db->prepare('SELECT RUC FROM clientesexternos WHERE Id = ?');
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $ruc = $row['RUC'] ?? null;

            if ($ruc) {
                // Verificar en despachos_externos por RUC
                $stmt = $db->prepare('SELECT COUNT(*) FROM despachos_externos WHERE RUC = ?');
                $stmt->execute([$ruc]);
                if ($stmt->fetchColumn() > 0) {
                    return true;
                }

                // Verificar en recepciones_externas por RUC
                $stmt = $db->prepare('SELECT COUNT(*) FROM recepciones_externas WHERE RUC = ?');
                $stmt->execute([$ruc]);
                if ($stmt->fetchColumn() > 0) {
                    return true;
                }
            }
        } catch (Exception $e) {
            // En caso de error, registrar y asumir que tiene movimientos para prevenir edición insegura
            @file_put_contents(dirname(__DIR__,2).'/logs/clientesexternos_error.log', "[".date('Y-m-d H:i:s')."] hasMovements check failed for Id={$id} - " . $e->getMessage() . "\n", FILE_APPEND);
            return true;
        }

        return false;
    }
}
