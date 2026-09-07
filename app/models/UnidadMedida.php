<?php
class UnidadMedida extends Model {
    protected $table = 'unidades_medida';
    protected $primaryKey = 'Id';

    public function getAll() {
        $stmt = $this->db->prepare("SELECT Id, Abreviacion FROM unidades_medida ORDER BY Abreviacion ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
