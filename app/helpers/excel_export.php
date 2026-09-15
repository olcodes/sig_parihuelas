<?php
/**
 * Helper para exportaciones de Excel
 * Maneja correctamente los buffers y headers para evitar problemas
 */

/**
 * Preparar el entorno para exportación de archivos
 * Limpia todos los buffers y prepara los headers
 */
function prepareForFileExport() {
    // Limpiar TODOS los buffers de salida existentes
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Verificar que no se hayan enviado headers
    if (headers_sent($file, $line)) {
        throw new Exception("No se puede exportar: headers ya enviados en $file línea $line");
    }
    
    // Limpiar cualquier output que pueda haber
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
}

/**
 * Configurar headers para descarga de archivo Excel
 * @param string $fileName Nombre del archivo
 */
function setExcelHeaders($fileName) {
    // Asegurar que el nombre de archivo no tenga caracteres problemáticos
    $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');
}

/**
 * Exportar archivo Excel de manera segura
 * @param \PhpOffice\PhpSpreadsheet\Writer\Xlsx $writer
 * @param string $fileName
 */
function exportExcelFile($writer, $fileName) {
    try {
        prepareForFileExport();
        setExcelHeaders($fileName);
        
        // Guardar el archivo al output
        $writer->save('php://output');
        exit;
        
    } catch (Exception $e) {
        // Si hay error, limpiar y mostrar mensaje
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: text/html; charset=utf-8');
        echo "<h3>Error al exportar archivo:</h3>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<a href='javascript:history.back()'>Volver</a>";
        exit;
    }
}
?>