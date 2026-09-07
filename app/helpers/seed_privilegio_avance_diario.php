<?php
/**
 * Script para crear el privilegio 'ver_avance_diario' si no existe.
 * Ejecutar: php app/helpers/seed_privilegio_avance_diario.php
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar si ya existe
    $stmt = $db->prepare("SELECT Id FROM privilegios WHERE Nombre = :nombre LIMIT 1");
    $stmt->execute(['nombre' => 'ver_avance_diario']);
    $existe = $stmt->fetch();
    
    if ($existe) {
        echo "El privilegio 'ver_avance_diario' ya existe (ID: {$existe['Id']}).\n";
    } else {
        $stmt = $db->prepare("INSERT INTO privilegios (Nombre, Descripcion) VALUES (:nombre, :descripcion)");
        $stmt->execute([
            'nombre' => 'ver_avance_diario',
            'descripcion' => 'Permite acceder al módulo Control de Stock de Racks y Parihuelas (Avance Diario)'
        ]);
        echo "Privilegio 'ver_avance_diario' creado exitosamente.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
