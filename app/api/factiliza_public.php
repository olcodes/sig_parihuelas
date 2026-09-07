<?php
// public/api/factiliza_public.php
// Endpoint público que incluye el backend real de Factiliza (app/api/factiliza_dni.php)
// Uso: POST JSON { dni: '12345678', fetch_brevete: true }

// Forzar salida JSON y evitar que middleware de la app nos haga redirect
header('Content-Type: application/json; charset=utf-8');

// Evitar mostrar warnings/notices en salida JSON
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

$real = __DIR__ . '/../../app/api/factiliza_dni.php';
if (is_file($real)) {
    // Incluir el script original. No usamos include_once para permitir recarga en entornos con opcache.
    require $real;
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Script interno no encontrado']);
}
