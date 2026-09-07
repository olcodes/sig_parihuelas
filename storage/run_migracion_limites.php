<?php
/**
 * Script para ejecutar la migración de la tabla usuarios_limites_modificacion
 * Ejecutar: php storage/run_migracion_limites.php
 */

$host = '204.93.224.230';
$dbname = 'lavorope_dblavoro';
$user = 'lavorope_adm';
$pass = '7Jb4TcRpX120';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = file_get_contents(__DIR__ . '/migracion_limites_modificacion.sql');
    $pdo->exec($sql);
    
    echo "Migracion ejecutada exitosamente: tabla usuarios_limites_modificacion creada.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
