<?php
// Este endpoint devuelve solo la unidad de medida de un producto
// Se usará como fuente autoritativa para obtener unidades de medida

class ObtencionUDM extends Controller
{
    public function index()
    {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Solicitud no válida'
        ]);
    }
    
    public function obtenerUDM($codigo = null)
    {
        header('Content-Type: application/json');
        
        if (!$codigo) {
            echo json_encode([
                'success' => false,
                'message' => 'Código de producto no proporcionado'
            ]);
            return;
        }
        
        try {
            // Intentar buscar el producto en la base de datos
            $modelProducto = $this->model('Producto');
            $producto = $modelProducto->buscarPorCodigo($codigo);
            
            if ($producto) {
                $unidadMedida = $producto['UnidadMedida'] ?? '';
                error_log("ObtencionUDM: Unidad de medida encontrada para código $codigo: $unidadMedida");
                
                echo json_encode([
                    'success' => true,
                    'codigo' => $codigo,
                    'unidadMedida' => $unidadMedida
                ]);
            } else {
                error_log("ObtencionUDM: No se encontró producto con código $codigo");
                echo json_encode([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ]);
            }
        } catch (Exception $e) {
            error_log("ObtencionUDM: Error al buscar unidad de medida para código $codigo: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error al buscar la unidad de medida'
            ]);
        }
    }
}