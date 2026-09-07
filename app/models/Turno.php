<?php
class Turno extends Model {
    public function getAll() {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query('SELECT * FROM turnos ORDER BY Id ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAllForSelect() {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query('SELECT Id, Turno FROM turnos ORDER BY Id ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
