<?php

class Privilegio extends Model
{
    public function getPaginatedFiltered($busqueda = '', $offset = 0, $limit = 10) {
        $sql = "SELECT * FROM privilegios ";
        $params = [];
        if ($busqueda) {
            $sql .= "WHERE Nombre LIKE :busqueda OR Descripcion LIKE :busqueda ";
            $params['busqueda'] = "%$busqueda%";
        }
        $sql .= "ORDER BY Nombre ASC LIMIT :offset, :limit";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(":$k", $v, PDO::PARAM_STR);
        }
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countFiltered($busqueda = '') {
        $sql = "SELECT COUNT(*) as total FROM privilegios ";
        $params = [];
        if ($busqueda) {
            $sql .= "WHERE Nombre LIKE :busqueda OR Descripcion LIKE :busqueda ";
            $params['busqueda'] = "%$busqueda%";
        }
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(":$k", $v, PDO::PARAM_STR);
        }
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ? (int)$row['total'] : 0;
    }
    public function create($data)
    {
        $stmt = $this->db->prepare("INSERT INTO privilegios (Nombre, Descripcion) VALUES (:nombre, :descripcion)");
        $stmt->execute([
            'nombre' => $data['Nombre'],
            'descripcion' => $data['Descripcion']
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare("UPDATE privilegios SET Nombre = :nombre, Descripcion = :descripcion WHERE Id = :id");
        $stmt->execute([
            'nombre' => $data['Nombre'],
            'descripcion' => $data['Descripcion'],
            'id' => $id
        ]);
        return $stmt->rowCount();
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM privilegios WHERE Id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount();
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM privilegios WHERE Id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function getByRoleId($roleId)
    {
        $stmt = $this->db->prepare("SELECT p.* FROM privilegios p INNER JOIN roles_privilegios rp ON p.Id = rp.PrivilegioId WHERE rp.RoleId = :roleId");
        $stmt->execute(['roleId' => $roleId]);
        return $stmt->fetchAll();
    }

    public function getAll()
    {
        $stmt = $this->db->query("SELECT * FROM privilegios ORDER BY Nombre ASC");
        return $stmt->fetchAll();
    }
}
