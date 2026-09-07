<?php
// public/app/api/factiliza_dni.php
// Wrapper público que incluye el script real en ../.. para evitar redirecciones a login
// Permite que solicitudes AJAX a /app/api/factiliza_dni.php lleguen al script sin pasar por el front controller.

// Seguridad: este archivo expone solo la funcionalidad existente en app/api/factiliza_dni.php
// No añadir lógica adicional aquí sin revisar.

$realPath = __DIR__ . '/../../../app/api/factiliza_dni.php';
if (is_file($realPath)) {
    require $realPath;
} else {
    header('Content-Type: application/json', true, 500);
    echo json_encode(['error' => 'Script interno no encontrado']);
}
