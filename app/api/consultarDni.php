<?php
// app/api/consultarDni.php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}
$input = json_decode(file_get_contents('php://input'), true);
$dni = trim($input['dni'] ?? '');
if ($dni === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'DNI vacío']);
    exit;
}
try {
    require_once __DIR__ . '/../../core/Model.php';
    require_once __DIR__ . '/../../core/Database.php';
} catch (Exception $e) {
    // ignore
}
try {
    if (class_exists('Database')) {
        $db = Database::getInstance()->getConnection();
    } else {
        $cfg = @include __DIR__ . '/../../config/database.php';
        if (is_array($cfg) && isset($cfg['default'])) {
            $c = $cfg['default'];
            $dsn = "mysql:host={$c['hostname']};dbname={$c['database']};charset=utf8";
            $db = new PDO($dsn, $c['username'], $c['password']);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } else {
            throw new Exception('No se pudo obtener conexión a BD');
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
    exit;
}
try {
    $stmt = $db->prepare('SELECT ApellidosPaterno, ApellidoMaterno, Nombres, ApellidosNombres, Brevete FROM choferes WHERE DocIdentidad = ? LIMIT 1');
    $stmt->execute([$dni]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $result = ['success' => true, 'apellidos_nombres' => $row['ApellidosNombres'] ?? trim(($row['ApellidosPaterno'] ?? '') . ' ' . ($row['ApellidoMaterno'] ?? '') . ' ' . ($row['Nombres'] ?? ''))];
        if (!empty($row['Brevete'])) $result['numero_brevete'] = $row['Brevete'];
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }
    // No hay en BD -> consultar Factiliza
    try {
        $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIzOTY3NiIsImh0dHA6Ly9zY2hlbWFzLm1pY3Jvc29mdC5jb20vd3MvMjAwOC8wNi9pZGVudGl0eS9jbGFpbXMvcm9sZSI6ImNvbnN1bHRvciJ9.vfxtjiaNq8g6QbsX7h2b_BYfhGcXMiUiOPetH-JKaZI';
        $url = 'https://api.factiliza.com/v1/dni/info/' . rawurlencode($dni);
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => "Authorization: Bearer $token\r\nAccept: application/json\r\nUser-Agent: swlavoro/1.0\r\n",
                'timeout' => 10
            ]
        ];
        $context = stream_context_create($opts);
        $res = @file_get_contents($url, false, $context);
        if ($res === false) {
            echo json_encode(['success' => false, 'message' => 'No se encontró información para este DNI.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $data = json_decode($res, true);
        if ($data) {
            $src = $data['data'] ?? $data;
            $nombre = $src['nombre_completo'] ?? $src['nombre'] ?? $src['nombres'] ?? null;
            $out = ['success' => true];
            if ($nombre) $out['apellidos_nombres'] = $nombre;
            // verificar si la respuesta contiene licencia/brevete
            if (!empty($src['licencia']['numero'])) $out['numero_brevete'] = $src['licencia']['numero'];
            echo json_encode($out, JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'No se encontró información para este DNI.'], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error consultando Factiliza: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en consulta: ' . $e->getMessage()]);
}
