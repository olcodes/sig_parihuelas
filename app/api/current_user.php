<?php
// Devuelve información del usuario actual desde la sesión (JSON)
header('Content-Type: application/json; charset=utf-8');
if (session_status() == PHP_SESSION_NONE) {
    session_name('SWLAVORO');
    $sessionPath = dirname(__DIR__) . '/tmp';
    if (!is_dir($sessionPath)) {
        @mkdir($sessionPath, 0777, true);
    }
    session_save_path($sessionPath);
    session_start();
}
$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
if ($user) {
    echo json_encode(['success' => true, 'username' => ($user['username'] ?? ''), 'name' => ($user['NombresApellidos'] ?? ($user['name'] ?? ''))]);
} else {
    echo json_encode(['success' => false, 'message' => 'No auth']);
}

?>