<?php

class User extends Model
{
    public function getPaginatedFiltered($limit, $offset, $busqueda = '')
    {
        $sql = "SELECT u.*, r.Nombre as RolNombre FROM usuarios u LEFT JOIN roles r ON u.RoleId = r.Id";
        $params = [];
        if ($busqueda) {
            $sql .= " WHERE u.username LIKE :b OR u.DocIdentidad LIKE :b OR u.NombresApellidos LIKE :b OR r.Nombre LIKE :b";
            $params['b'] = "%$busqueda%";
        }
        $sql .= " ORDER BY u.Id DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_STR);
        }
        $stmt->bindValue('limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countFiltered($busqueda = '')
    {
        $sql = "SELECT COUNT(*) FROM usuarios u LEFT JOIN roles r ON u.RoleId = r.Id";
        $params = [];
        if ($busqueda) {
            $sql .= " WHERE u.username LIKE :b OR u.DocIdentidad LIKE :b OR u.NombresApellidos LIKE :b OR r.Nombre LIKE :b";
            $params['b'] = "%$busqueda%";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }
    public function findByUsername($username)
    {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE LOWER(username) = LOWER(:username) LIMIT 1");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();
        if ($user && isset($user['Id'])) {
            $user['id'] = $user['Id'];
        }
        return $user;
    }


    public function getAllWithRoles()
    {
        $stmt = $this->db->query("SELECT u.*, r.Nombre as RolNombre FROM usuarios u LEFT JOIN roles r ON u.RoleId = r.Id ORDER BY u.Id DESC");
        return $stmt->fetchAll();
    }

    public function getAll()
    {
        $stmt = $this->db->query("SELECT * FROM usuarios ORDER BY Id DESC");
        return $stmt->fetchAll();
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE Id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("INSERT INTO usuarios (username, password, DocIdentidad, NombresApellidos, RoleId) VALUES (:username, :password, :DocIdentidad, :NombresApellidos, :RoleId)");
        return $stmt->execute([
            'username' => $data['username'],
            'password' => $data['password'],
            'DocIdentidad' => $data['DocIdentidad'],
            'NombresApellidos' => $data['NombresApellidos'],
            'RoleId' => $data['RoleId']
        ]);
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare("UPDATE usuarios SET username = :username, password = :password, DocIdentidad = :DocIdentidad, NombresApellidos = :NombresApellidos, RoleId = :RoleId WHERE Id = :id");
        return $stmt->execute([
            'id' => $id,
            'username' => $data['username'],
            'password' => $data['password'],
            'DocIdentidad' => $data['DocIdentidad'],
            'NombresApellidos' => $data['NombresApellidos'],
            'RoleId' => $data['RoleId']
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM usuarios WHERE Id = :id");
        return $stmt->execute(['id' => $id]);
    }
}