<?php
// Proxy to app/api/current_user.php to allow AJAX from public folder
$real = __DIR__ . '/../..' . '/app/api/current_user.php';
if (file_exists($real)) {
    include $real;
} else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Not found']);
}
?>