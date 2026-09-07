<?php
/**
 * Script para crear el privilegio 'ver_documentacion' si no existe.
 * Ejecutar: php app/helpers/seed_privilegio_documentacion.php
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar si ya existe
    $stmt = $db->prepare("SELECT Id FROM privilegios WHERE Nombre = :nombre LIMIT 1");
    $stmt->execute(['nombre' => 'ver_documentacion']);
    $existe = $stmt->fetch();
    
    if ($existe) {
        echo "✅ El privilegio 'ver_documentacion' ya existe (ID: {$existe['Id']}).\n";
    } else {
        $stmt = $db->prepare("INSERT INTO privilegios (Nombre, Descripcion) VALUES (:nombre, :descripcion)");
        $stmt->execute([
            'nombre' => 'ver_documentacion',
            'descripcion' => 'Permite acceder a la sección de Documentación del Sistema'
        ]);
        echo "✅ Privilegio 'ver_documentacion' creado exitosamente.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
