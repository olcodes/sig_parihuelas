<?php
/**
 * Migración: Agregar columna Total a recepciones_externas_productos
 * y poblar datos existentes con el cálculo correcto.
 * 
 * Fórmula: Total = Cantidad + Adicional - Pendiente
 * - Adicional: cuando la observación es 'A' (adicional)
 * - Pendiente: cuando la observación es 'P', 'DE', 'LE', 'R' (pendiente/deja/lleva/regulariza)
 * 
 * Ejecutar accediendo a este archivo desde el navegador o con PHP CLI.
 * Ruta: http://localhost/swlavoro/storage/migracion_total_producto.php
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');
echo '<h2>Migración: Agregar columna Total a recepciones_externas_productos</h2>';

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Verificar si la columna Total ya existe
    $stmt = $db->query("SHOW COLUMNS FROM recepciones_externas_productos LIKE 'Total'");
    $existe = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existe) {
        echo '<p style="color:green;">✓ La columna <strong>Total</strong> ya existe.</p>';
    } else {
        $db->exec("ALTER TABLE recepciones_externas_productos 
                    ADD COLUMN Total DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER CantidadObservada");
        echo '<p style="color:green;">✓ Columna <strong>Total</strong> agregada correctamente.</p>';
    }
    
    // 2. Poblar datos existentes con el cálculo correcto
    echo '<p>Actualizando registros existentes...</p>';
    
    $sql = "UPDATE recepciones_externas_productos p
            LEFT JOIN observaciones o ON p.Observacion = o.Id
            SET p.Total = p.Cantidad +
                CASE
                    WHEN UPPER(TRIM(o.Item)) IN ('A', 'ADICI', 'R', 'REGULAR') THEN p.CantidadObservada
                    WHEN UPPER(TRIM(o.Item)) IN ('P', 'DE', 'LE', 'PEND', 'DEJA', 'LLEVA') THEN -p.CantidadObservada
                    ELSE 0
                END";
    
    $affected = $db->exec($sql);
    echo '<p style="color:green;">✓ ' . $affected . ' registros actualizados con el Total correcto.</p>';
    
    // 3. Mostrar algunos ejemplos para verificar
    echo '<h3>Verificación (primeros 10 registros):</h3>';
    $stmt = $db->query("
        SELECT p.Id, p.Cantidad, p.CantidadObservada, 
               COALESCE(o.Item, 'SIN OBS') as TipoObs,
               p.Total,
               CONCAT('Cant=', p.Cantidad,
                      CASE
                          WHEN UPPER(TRIM(o.Item)) IN ('A', 'ADICI', 'R', 'REGULAR') THEN CONCAT(' + Adic(', p.CantidadObservada, ')')
                          WHEN UPPER(TRIM(o.Item)) IN ('P', 'DE', 'LE', 'PEND', 'DEJA', 'LLEVA') THEN CONCAT(' - Pend(', p.CantidadObservada, ')')
                          ELSE ''
                      END,
                      ' = ', p.Total) as Formula
        FROM recepciones_externas_productos p
        LEFT JOIN observaciones o ON p.Observacion = o.Id
        ORDER BY p.Id DESC
        LIMIT 10
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($rows) {
        echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse; font-family:monospace;">';
        echo '<tr style="background:#f0f0f0;">
                <th>ID</th><th>Cantidad</th><th>CantObs</th><th>Tipo Obs</th><th>Total</th><th>Fórmula</th>
              </tr>';
        foreach ($rows as $row) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['Id']) . '</td>';
            echo '<td>' . htmlspecialchars($row['Cantidad']) . '</td>';
            echo '<td>' . htmlspecialchars($row['CantidadObservada']) . '</td>';
            echo '<td>' . htmlspecialchars($row['TipoObs']) . '</td>';
            echo '<td><strong>' . htmlspecialchars($row['Total']) . '</strong></td>';
            echo '<td>' . htmlspecialchars($row['Formula']) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    
    echo '<p style="color:green; font-weight:bold; font-size:1.2em;">✓ Migración completada exitosamente.</p>';
    
} catch (Exception $e) {
    echo '<p style="color:red;">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
