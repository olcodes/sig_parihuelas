<?php
// Añadir método para actualizar un producto específico

class DespachoInterno extends Model
{
    // ... Código existente ...
    
    public function actualizarProductoEspecifico($despachoId, $codigoProducto, $nombreProducto, $cantidad, $comentarios)
    {
        try {
            $this->db->beginTransaction();

            error_log("Actualizando producto específico: despachoId=$despachoId, codigo=$codigoProducto, cantidad=$cantidad");
            
            // Validaciones
            if (empty($despachoId) || empty($codigoProducto)) {
                error_log("Faltan datos importantes para actualizar el producto");
                throw new Exception("Datos insuficientes para actualizar el producto");
            }
            
            // Validar que el despacho exista
            $stmtCheckDespacho = $this->db->prepare("SELECT Id FROM DespachoInterno WHERE Id = :despachoId");
            $stmtCheckDespacho->bindParam(':despachoId', $despachoId);
            $stmtCheckDespacho->execute();
            
            if ($stmtCheckDespacho->rowCount() === 0) {
                error_log("Despacho con ID $despachoId no encontrado");
                throw new Exception("Despacho no encontrado");
            }
            
            // Validar que el producto exista en este despacho
            $stmtCheckProducto = $this->db->prepare(
                "SELECT Id FROM DespachoInternoProducto WHERE DespachoInternoId = :despachoId AND CodigoProducto = :codigoProducto"
            );
            $stmtCheckProducto->bindParam(':despachoId', $despachoId);
            $stmtCheckProducto->bindParam(':codigoProducto', $codigoProducto);
            $stmtCheckProducto->execute();
            
            if ($stmtCheckProducto->rowCount() === 0) {
                error_log("Producto con código $codigoProducto no encontrado en despacho $despachoId");
                throw new Exception("Producto no encontrado en este despacho");
            }
            
            $productoId = $stmtCheckProducto->fetchColumn();
            
            // Actualizar el producto
            $sql = "UPDATE DespachoInternoProducto 
                   SET Cantidad = :cantidad, Comentarios = :comentarios
                   WHERE Id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':cantidad', $cantidad);
            $stmt->bindParam(':comentarios', $comentarios);
            $stmt->bindParam(':id', $productoId);
            
            $resultado = $stmt->execute();
            
            if (!$resultado) {
                error_log("Error al actualizar el producto: " . implode(", ", $stmt->errorInfo()));
                throw new Exception("Error al actualizar el producto en la base de datos");
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error en actualizarProductoEspecifico: " . $e->getMessage());
            throw $e;
        }
    }
    
    // ... Resto del código ...
}