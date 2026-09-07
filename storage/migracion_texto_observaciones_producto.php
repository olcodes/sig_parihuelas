<?php
/**
 * Migración: Agregar columna TextoObservaciones a recepciones_externas_productos
 * 
 * Ejecutar accediendo a este archivo desde el navegador o con PHP CLI.
 * Ruta: http://localhost/swlavoro/storage/migracion_texto_observaciones_producto.php
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');
echo '<h2>Migración: Agregar TextoObservaciones a recepciones_externas_productos</h2>';

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar si la columna ya existe
    $stmt = $db->query("SHOW COLUMNS FROM recepciones_externas_productos LIKE 'TextoObservaciones'");
    $existe = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existe) {
        echo '<p style="color:green;">✓ La columna <strong>TextoObservaciones</strong> ya existe.</p>';
    } else {
        $db->exec("ALTER TABLE recepciones_externas_productos 
                    ADD COLUMN TextoObservaciones TEXT DEFAULT NULL AFTER Observacion");
        echo '<p style="color:green;">✓ Columna <strong>TextoObservaciones</strong> agregada correctamente.</p>';
    }
    
    echo '<p style="color:green; font-weight:bold;">✓ Migración completada exitosamente.</p>';
    
} catch (Exception $e) {
    echo '<p style="color:red;">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
