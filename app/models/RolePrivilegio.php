<?php

class RolePrivilegio extends Model
{
    public function getPrivilegiosByRoleId($roleId)
    {
        $stmt = $this->db->prepare("SELECT PrivilegioId FROM roles_privilegios WHERE RoleId = :roleId");
        $stmt->execute(['roleId' => $roleId]);
        return array_column($stmt->fetchAll(), 'PrivilegioId');
    }

    public function setPrivilegiosForRole($roleId, $privilegios)
    {
        // Eliminar todos los privilegios actuales
        $this->db->prepare("DELETE FROM roles_privilegios WHERE RoleId = :roleId")->execute(['roleId' => $roleId]);
        // Insertar los nuevos
        if (!empty($privilegios)) {
            $stmt = $this->db->prepare("INSERT INTO roles_privilegios (RoleId, PrivilegioId) VALUES (:roleId, :privilegioId)");
            foreach ($privilegios as $pid) {
                $stmt->execute(['roleId' => $roleId, 'privilegioId' => $pid]);
            }
        }
    }
}
