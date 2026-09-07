<?php
/**
 * Endpoint: keep_alive.php
 * Renueva la marca de tiempo de última actividad para evitar
 * el cierre de sesión automático por inactividad (BASC).
 */
session_name('SWLAVORO');
ini_set('session.cookie_path', '/');

// Usar el MISMO directorio de sesiones que el front controller
// para que el heartbeat realmente actualice la sesión del usuario
$sessionPath = __DIR__ . '/../../tmp';
if (!is_dir($sessionPath)) {
    @mkdir($sessionPath, 0777, true);
}
session_save_path($sessionPath);

session_start();

header('Content-Type: application/json; charset=utf-8');
// No cachear esta respuesta
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['status' => 'expired']);
    exit;
}

// Solo aceptar POST o GET con parámetro explícito para evitar accesos accidentales
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['ping'])) {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

$_SESSION['last_activity'] = time();
echo json_encode(['status' => 'ok', 'timestamp' => time()]);
