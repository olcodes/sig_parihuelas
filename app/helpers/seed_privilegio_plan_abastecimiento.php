<?php
/**
 * Script para crear el privilegio 'ver_plan_abastecimiento' si no existe.
 * Ejecutar: php app/helpers/seed_privilegio_plan_abastecimiento.php
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar si ya existe
    $stmt = $db->prepare("SELECT Id FROM privilegios WHERE Nombre = :nombre LIMIT 1");
    $stmt->execute(['nombre' => 'ver_plan_abastecimiento']);
    $existe = $stmt->fetch();
    
    if ($existe) {
        echo "El privilegio 'ver_plan_abastecimiento' ya existe (ID: {$existe['Id']}).\n";
    } else {
        $stmt = $db->prepare("INSERT INTO privilegios (Nombre, Descripcion) VALUES (:nombre, :descripcion)");
        $stmt->execute([
            'nombre' => 'ver_plan_abastecimiento',
            'descripcion' => 'Permite acceder al módulo Plan de Abastecimiento'
        ]);
        echo "Privilegio 'ver_plan_abastecimiento' creado exitosamente.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
