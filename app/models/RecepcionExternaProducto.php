<?php
require_once __DIR__ . '/../../core/Model.php';

class RecepcionExternaProducto extends Model {
    
    /**
     * Calcular el Total de un producto según el tipo de observación.
     *
     * Fórmula:
     * - Adicional (A, ADICI):     Total = Cantidad + CantidadObservada
     * - Pendiente/Deja/Lleva/PT
     *   (P, DE, LE, PT, ...):     Total = Cantidad - CantidadObservada
     * - Regulariza (R, REGULAR):  Total = CantidadObservada
     * - Sin observación:          Total = Cantidad
     *
     * @param float $cantidad
     * @param int|null $observacionId ID de la observación (tabla observaciones)
     * @param float $cantidadObservada
     * @return float
     */
    private function calcularTotal($cantidad, $observacionId, $cantidadObservada) {
        $cantidad = floatval($cantidad);
        $cantidadObservada = floatval($cantidadObservada);
        
        if ($cantidadObservada <= 0 || empty($observacionId)) {
            return $cantidad;
        }
        
        try {
            // Consultar el tipo de observación desde la tabla observaciones
            $sql = "SELECT UPPER(TRIM(Item)) as Item FROM observaciones WHERE Id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$observacionId]);
            $obs = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$obs) {
                return $cantidad;
            }
            
            $item = $obs['Item'];
            
            // Adicional (A, ADICI) → suma la cantidad observada
            if (in_array($item, ['A', 'ADICI'])) {
                return $cantidad + $cantidadObservada;
            }
            
            // Regulariza (R, REGULAR) → el total es la cantidad observada (cantidad regularizada)
            if (in_array($item, ['R', 'REGULAR'])) {
                return $cantidadObservada;
            }
            
            // Pendiente/Deja/Lleva/PT (P, DE, LE, PT, ...) → resta la cantidad observada
            if (in_array($item, ['P', 'DE', 'LE', 'PT', 'PEND', 'DEJA', 'LLEVA', 'PRODUCTO TERMINADO'])) {
                return $cantidad - $cantidadObservada;
            }
            
            return $cantidad;
        } catch (Exception $e) {
            error_log('Error calculando total producto: ' . $e->getMessage());
            return $cantidad;
        }
    }
    
    /**
     * Registrar un producto asociado a una guía
     */
    public function registrar($data) {
        try {
            // Calcular Total antes de guardar
            $total = $this->calcularTotal(
                $data['Cantidad'],
                $data['Observacion'] ?? null,
                $data['CantidadObservada'] ?? 0
            );
            
            $sql = "INSERT INTO recepciones_externas_productos
                    (GuiaId, CodigoProducto, DescripcionProducto, UnidadMedida, Cantidad, ColumnaProducto, Observacion, CantidadObservada, TextoObservaciones, Total)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['GuiaId'],
                $data['CodigoProducto'],
                $data['DescripcionProducto'],
                $data['UnidadMedida'],
                $data['Cantidad'],
                $data['ColumnaProducto'] ?? 1,
                $data['Observacion'] ?? null,
                $data['CantidadObservada'] ?? 0,
                $data['TextoObservaciones'] ?? null,
                $total
            ]);
        } catch (Exception $e) {
            error_log('Error registrando producto de guía: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Eliminar todos los productos de una guía específica
     */
    public function eliminarPorGuia($guiaId) {
        try {
            $sql = "DELETE FROM recepciones_externas_productos WHERE GuiaId = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$guiaId]);
        } catch (Exception $e) {
            error_log('Error eliminando productos de guía: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Obtener todos los productos de una guía
     */
    public function obtenerPorGuia($guiaId) {
        try {
            $sql = "SELECT * FROM recepciones_externas_productos 
                    WHERE GuiaId = ? 
                    ORDER BY ColumnaProducto ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$guiaId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error obteniendo productos de guía: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener todos los productos de una recepción externa (todos los productos de todas las guías)
     */
    public function obtenerPorRecepcion($recepcionId) {
        try {
            $sql = "SELECT p.*, g.NumeroGuia
                    FROM recepciones_externas_productos p
                    INNER JOIN recepciones_externas_guias g ON p.GuiaId = g.Id
                    WHERE g.RecepcionExternaId = ?
                    ORDER BY g.Orden ASC, p.ColumnaProducto ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$recepcionId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error obteniendo productos de recepción: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Actualizar un producto
     */
    public function actualizar($productoId, $datos) {
        try {
            // Calcular Total antes de actualizar
            $total = $this->calcularTotal(
                $datos['cantidad'],
                $datos['observacion'] ?? null,
                $datos['cantidadObservada'] ?? 0
            );
            
            $sql = "UPDATE recepciones_externas_productos
                    SET CodigoProducto = ?,
                        Cantidad = ?,
                        Observacion = ?,
                        CantidadObservada = ?,
                        Total = ?
                    WHERE Id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $datos['codigoProducto'],
                $datos['cantidad'],
                $datos['observacion'] ?? null,
                $datos['cantidadObservada'] ?? 0,
                $total,
                $productoId
            ]);
        } catch (Exception $e) {
            error_log('Error actualizando producto: ' . $e->getMessage());
            throw $e;
        }
    }
}
