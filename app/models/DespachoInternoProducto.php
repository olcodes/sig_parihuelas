<?php
class DespachoInternoProducto extends Model {
    // Eliminar productos por despacho
    public function eliminarPorDespacho($despachoId) {
        $sql = "DELETE FROM despachos_internos_productos WHERE DespachoId = ?";
        try {
            return $this->query($sql, [$despachoId]);
        } catch (PDOException $e) {
            throw new Exception('Error MySQL: ' . $e->getMessage());
        }
    }
    // Registrar producto de despacho interno
    public function registrar($data) {
        $sql = "INSERT INTO despachos_internos_productos (DespachoId, CodigoProducto, DescripcionProducto, UnidadMedida, Cantidad, Comentarios) VALUES (?, ?, ?, ?, ?, ?)";
        try {
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "PRODUCTO DATA: " . print_r($data, true) . "\n", FILE_APPEND);
            $result = $this->query($sql, [
                $data['DespachoId'],
                $data['CodigoProducto'],
                $data['DescripcionProducto'],
                $data['UnidadMedida'] ?? '',
                $data['Cantidad'],
                $data['Comentarios'] ?? ''
            ]);
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "PRODUCTO OK\n", FILE_APPEND);
            return $result;
        } catch (PDOException $e) {
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "PRODUCTO ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
            throw new Exception('Error MySQL: ' . $e->getMessage());
        }
    }
}
