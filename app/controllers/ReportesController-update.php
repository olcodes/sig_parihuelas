<?php

class ReportesController extends Controller
{
    // ... Código existente ...

    public function actualizarProductoEspecifico()
    {
        header('Content-Type: application/json');

        try {
            $rawInput = file_get_contents('php://input');
            error_log("Datos recibidos en actualizarProductoEspecifico: " . $rawInput);
            
            $params = json_decode($rawInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Error de JSON: " . json_last_error_msg());
                echo json_encode(['success' => false, 'message' => 'Error en el formato de los datos: ' . json_last_error_msg()], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $despachoId = $params['despachoId'] ?? null;
            $producto = $params['producto'] ?? null;
            
            error_log("actualizarProductoEspecifico: despachoId=" . $despachoId . ", producto=" . json_encode($producto));

            if (!$despachoId || !$producto) {
                echo json_encode(['success' => false, 'message' => 'Datos incompletos'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Validar datos del producto
            if (!isset($producto['Codigo']) || !isset($producto['Cantidad'])) {
                echo json_encode(['success' => false, 'message' => 'Datos de producto incompletos'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Actualizar el producto en la base de datos
            $model = $this->model('DespachoInterno');
            
            try {
                $resultado = $model->actualizarProductoEspecifico(
                    $despachoId, 
                    $producto['Codigo'],
                    $producto['Producto'] ?? '',
                    $producto['Cantidad'],
                    $producto['Comentarios'] ?? ''
                );
                
                if ($resultado) {
                    echo json_encode(['success' => true, 'message' => 'Producto actualizado correctamente'], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el producto'], JSON_UNESCAPED_UNICODE);
                }
            } catch (Exception $e) {
                error_log("Error al actualizar producto: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
            
        } catch (Exception $e) {
            error_log("Error general en actualizarProductoEspecifico: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error del servidor'], JSON_UNESCAPED_UNICODE);
        }
    }
    
    // ... Resto del código ...
}