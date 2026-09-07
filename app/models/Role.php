<?php

class Role extends Model
{
    public function getPaginatedFiltered($busqueda = '', $offset = 0, $limit = 10) {
        $sql = "SELECT * FROM roles ";
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
        $sql = "SELECT COUNT(*) as total FROM roles ";
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
        $stmt = $this->db->prepare("INSERT INTO roles (Nombre, Descripcion) VALUES (:nombre, :descripcion)");
        $stmt->execute([
            'nombre' => $data['Nombre'],
            'descripcion' => $data['Descripcion']
        ]);
        return $this->db->lastInsertId();
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM roles WHERE Id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount();
    }
    public function update($id, $data)
    {
        $stmt = $this->db->prepare("UPDATE roles SET Nombre = :nombre, Descripcion = :descripcion WHERE Id = :id");
        $stmt->execute([
            'nombre' => $data['Nombre'],
            'descripcion' => $data['Descripcion'],
            'id' => $id
        ]);
        return $stmt->rowCount();
    }
    public function getAll()
    {
        $stmt = $this->db->query("SELECT * FROM roles ORDER BY Nombre ASC");
        return $stmt->fetchAll();
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM roles WHERE Id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function getByName($nombre)
    {
        $stmt = $this->db->prepare("SELECT * FROM roles WHERE Nombre = :nombre LIMIT 1");
        $stmt->execute(['nombre' => $nombre]);
        return $stmt->fetch();
    }
}
