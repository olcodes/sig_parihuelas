<?php
/**
 * Script para crear el privilegio 'ver_kardex_parihuelas' si no existe.
 * Ejecutar: php app/helpers/seed_privilegio_kardex.php
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar si ya existe
    $stmt = $db->prepare("SELECT Id FROM privilegios WHERE Nombre = :nombre LIMIT 1");
    $stmt->execute(['nombre' => 'ver_kardex_parihuelas']);
    $existe = $stmt->fetch();
    
    if ($existe) {
        echo "✅ El privilegio 'ver_kardex_parihuelas' ya existe (ID: {$existe['Id']}).\n";
    } else {
        $stmt = $db->prepare("INSERT INTO privilegios (Nombre, Descripcion) VALUES (:nombre, :descripcion)");
        $stmt->execute([
            'nombre' => 'ver_kardex_parihuelas',
            'descripcion' => 'Permite acceder al módulo Kardex de Parihuelas Estándar'
        ]);
        echo "✅ Privilegio 'ver_kardex_parihuelas' creado exitosamente.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
