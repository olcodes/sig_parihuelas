<?php
require_once __DIR__ . '/../../core/Model.php';

class RecepcionExternaGuia extends Model {
    
    /**
     * Registrar una guía asociada a una recepción externa
     */
    public function registrar($data) {
        try {
                    $sql = "INSERT INTO recepciones_externas_guias 
                        (RecepcionExternaId, NumeroGuia, NumeroDocRef, Observacion, CodigoProductoObs, CantidadObservada, Orden, TextoObservaciones) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $numeroGuiaVal = null;
            if (isset($data['NumeroGuia'])) $numeroGuiaVal = strtoupper($data['NumeroGuia']);
            elseif (isset($data['numeroGuia'])) $numeroGuiaVal = strtoupper($data['numeroGuia']);
            $resultado = $stmt->execute([
                $data['RecepcionExternaId'],
                $numeroGuiaVal,
                $data['NumeroDocRef'] ?? $data['numeroDocRef'] ?? null,
                $data['Observacion'] ?? null,
                $data['CodigoProductoObs'] ?? null,
                $data['CantidadObservada'] ?? 0,
                $data['Orden'] ?? 1,
                $data['TextoObservaciones'] ?? $data['textoObservaciones'] ?? null
            ]);
            
            if ($resultado) {
                return $this->db->lastInsertId();
            }
            
            return false;
        } catch (Exception $e) {
            error_log('Error registrando guía: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Eliminar todas las guías de una recepción externa
     * (CASCADE eliminará automáticamente los productos asociados)
     */
    public function eliminarPorRecepcion($recepcionId) {
        try {
            $sql = "DELETE FROM recepciones_externas_guias WHERE RecepcionExternaId = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$recepcionId]);
        } catch (Exception $e) {
            error_log('Error eliminando guías de recepción: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Obtener todas las guías de una recepción
     */
    public function obtenerPorRecepcion($recepcionId) {
        try {
            $sql = "SELECT g.*, obs.Observaciones as ObservacionTexto
                    FROM recepciones_externas_guias g
                    LEFT JOIN observaciones obs ON g.Observacion = obs.Id
                    WHERE g.RecepcionExternaId = ?
                    ORDER BY g.Orden ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$recepcionId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error obteniendo guías de recepción: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Actualizar el número de guía
     */
    public function actualizarNumeroGuia($guiaId, $numeroGuia) {
        try {
            $sql = "UPDATE recepciones_externas_guias 
                    SET NumeroGuia = ? 
                    WHERE Id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$numeroGuia, $guiaId]);
        } catch (Exception $e) {
            error_log('Error actualizando número de guía: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Actualizar una guía completa
     */
    public function actualizar($guiaId, $datos) {
        try {
            $sql = "UPDATE recepciones_externas_guias 
                    SET NumeroGuia = ?, 
                        NumeroDocRef = ?,
                        Observacion = ?, 
                        CodigoProductoObs = ?, 
                        CantidadObservada = ?,
                        TextoObservaciones = ? 
                    WHERE Id = ?";
            
            $stmt = $this->db->prepare($sql);
            $numeroGuiaUpd = null;
            if (isset($datos['numeroGuia'])) $numeroGuiaUpd = strtoupper($datos['numeroGuia']);
            elseif (isset($datos['NumeroGuia'])) $numeroGuiaUpd = strtoupper($datos['NumeroGuia']);
            return $stmt->execute([
                $numeroGuiaUpd,
                $datos['numeroDocRef'] ?? $datos['NumeroDocRef'] ?? null,
                $datos['observacion'] ?? null,
                $datos['codigoProductoObs'] ?? null,
                $datos['cantidadObservada'] ?? 0,
                $datos['textoObservaciones'] ?? $datos['TextoObservaciones'] ?? null,
                $guiaId
            ]);
        } catch (Exception $e) {
            error_log('Error actualizando guía: ' . $e->getMessage());
            throw $e;
        }
    }
}
