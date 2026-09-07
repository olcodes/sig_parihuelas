<?php
// public/api/factiliza_public.php
// Endpoint público que incluye el backend real de Factiliza (app/api/factiliza_dni.php)
// Uso: POST JSON { dni: '12345678', fetch_brevete: true }

// Forzar salida JSON y evitar que middleware de la app nos haga redirect
header('Content-Type: application/json; charset=utf-8');

// En entornos distintos al hosting compartido, incluir puede generar warnings/notices
// Forzamos que no se muestren errores al cliente y capturamos la salida para validar JSON
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

$real = __DIR__ . '/../../app/api/factiliza_dni.php';
if (!is_file($real)) {
    http_response_code(500);
    echo json_encode(['error' => 'Script interno no encontrado']);
    exit;
}

// Ejecutar el script en un buffer para atrapar cualquier HTML/notice que rompa JSON
ob_start();
try {
    // Ejecutar el script real
    require $real;
} catch (Throwable $e) {
    // Capturar excepciones fatales e informar como JSON
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => 'exception_in_backend', 'message' => $e->getMessage()]);
    exit;
}
$output = ob_get_clean();

// Si el script ya envió cabeceras y salida JSON válida, devolvemos tal cual
if ($output !== null) {
    // Normalizar: recortar BOM/espacios al inicio
    $trimmed = preg_replace('/^\xEF\xBB\xBF/', '', ltrim($output));
    $decoded = @json_decode($trimmed, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        // Buen JSON: devolverlo
        echo $trimmed;
        exit;
    }

        // No es JSON: registrar el output crudo en logs para depuración local
        try {
            $logDir = __DIR__ . '/../../logs';
            if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
            $logFile = $logDir . '/factiliza_public_nonjson.log';
            $entry = "[" . date('Y-m-d H:i:s') . "] REQUEST_URI=" . ($_SERVER['REQUEST_URI'] ?? '') . "\n";
            $entry .= "REMOTE_ADDR=" . ($_SERVER['REMOTE_ADDR'] ?? 'cli') . "\n";
            $entry .= "OUTPUT_PREVIEW:\n" . $trimmed . "\n\n";
            @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
        } catch (Exception $ee) { /* no bloquear por logging */ }

        http_response_code(502);
        $preview = strip_tags($trimmed);
        $preview = mb_substr($preview, 0, 2000);
        echo json_encode([
            'error' => 'backend_returned_non_json',
            'preview' => $preview,
            'log' => basename($logFile)
        ], JSON_UNESCAPED_UNICODE);
        exit;
}
