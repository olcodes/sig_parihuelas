<?php
class RecepcionInternaProducto extends Model {
    // Eliminar todos los productos de una recepción
    public function eliminarPorRecepcion($recepcionId) {
        $sql = "DELETE FROM recepciones_internas_productos WHERE DespachoId = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$recepcionId]);
    }
    
    // Registrar un producto para una recepción
    public function registrar($data) {
        $sql = "INSERT INTO recepciones_internas_productos (DespachoId, CodigoProducto, DescripcionProducto, UnidadMedida, Cantidad, Comentarios) VALUES (?,?,?,?,?,?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['DespachoId'],
            $data['CodigoProducto'],
            $data['DescripcionProducto'],
            $data['UnidadMedida'],
            $data['Cantidad'],
            $data['Comentarios'] ?? null
        ]);
    }
}
