<?php
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $data = json_decode(file_get_contents('php://input'), true);
    } else {
        $data = $_POST;
    }

    $serie = isset($data['serie']) ? trim(strtoupper($data['serie'])) : '';
    $centroDistribucion = isset($data['centroDistribucion']) ? trim(strtoupper($data['centroDistribucion'])) : '';
    if ($serie === '') {
        echo json_encode(['success' => false, 'message' => 'Serie inválida']);
        exit;
    }

    // Validar: 4 caracteres alfanumericos (ej: T086, 2056, EG07)
    if (!preg_match('/^[A-Z0-9]{4}$/i', $serie)) {
        echo json_encode(['success' => false, 'message' => 'Use 4 caracteres.']);
        exit;
    }

    if ($centroDistribucion === '') {
        echo json_encode(['success' => false, 'message' => 'Centro de Distribución es obligatorio.']);
        exit;
    }

    $db = Database::getInstance()->getConnection();

    // Verificar existencia
    $stmt = $db->prepare('SELECT Id FROM series WHERE UPPER(Serie) = :serie LIMIT 1');
    $stmt->execute([':serie' => strtoupper($serie)]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($exists) {
        echo json_encode(['success' => false, 'message' => 'La serie ya existe', 'id' => $exists['Id'], 'serie' => $serie]);
        exit;
    }

    // Insertar
    $ins = $db->prepare('INSERT INTO series (Serie, CentroDistribucion) VALUES (:serie, :centroDistribucion)');
    $res = $ins->execute([':serie' => $serie, ':centroDistribucion' => $centroDistribucion]);
    if ($res) {
        $id = $db->lastInsertId();
        echo json_encode(['success' => true, 'id' => $id, 'serie' => $serie, 'centroDistribucion' => $centroDistribucion]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al insertar serie']);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
}
