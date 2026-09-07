<?php
// app/api/factiliza_dni.php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$dni = $data['dni'] ?? null;
// flags opcionales desde el cliente
$fetchBrevete = !empty($data['fetch_brevete']); // si true, intentar obtener brevete incluso si hay cache
$forceRefresh = !empty($data['force_refresh']); // si true, ignorar cache DNI y forzar reconsulta

if (!$dni || !preg_match('/^[0-9]{8}$/', $dni)) {
    http_response_code(400);
    echo json_encode(['error' => 'DNI inválido']);
    exit;
}

$token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIzOTY3NiIsImh0dHA6Ly9zY2hlbWFzLm1pY3Jvc29mdC5jb20vd3MvMjAwOC8wNi9pZGVudGl0eS9jbGFpbXMvcm9sZSI6ImNvbnN1bHRvciJ9.vfxtjiaNq8g6QbsX7h2b_BYfhGcXMiUiOPetH-JKaZI';
$url = 'https://api.factiliza.com/pe/v1/dni/info/' . $dni; // usar el endpoint exacto que usa la web UI

// Intentar devolver desde cache si existe y es reciente (evita llamadas a Factiliza)
$cacheDir = __DIR__ . '/../cache/factiliza_dni';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}
$cacheFile = $cacheDir . '/' . preg_replace('/[^0-9]/', '', $dni) . '.json';
$cacheTTL = 24 * 3600; // 24 horas
if (is_file($cacheFile) && !$forceRefresh) {
    $cached = @json_decode(@file_get_contents($cacheFile), true);
    if ($cached && isset($cached['ts']) && (time() - $cached['ts']) < $cacheTTL && isset($cached['body'])) {
        // usar cached body como punto de partida
        $body = $cached['body'];
        // si se solicita fetch de brevete y no existe en el body, intentarlo abajo
        if (!$fetchBrevete) {
            header('Content-Type: application/json');
            echo json_encode($body, JSON_UNESCAPED_UNICODE);
            exit;
        }
        // si fetchBrevete == true, continuamos y más abajo intentamos asegurar brevete
    }
}

// Si $body ya está cargado desde cache y no se forzó refresh, no necesitamos llamar al endpoint DNI de Factiliza
$calledDni = false;
// contador de llamadas a Factiliza (DNI + licencia)
$factilizaCalls = 0;
if (empty($body) || $forceRefresh) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Accept: application/json, text/plain, */*',
        'User-Agent: swlavoro/1.0',
        'Origin: https://app.factiliza.com',
        'Referer: https://app.factiliza.com/',
        'Connection: keep-alive'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    // En entorno local puede fallar la verificación SSL si no hay CA instalada
    // Para desarrollo desactivamos la verificación; en producción use un CA bundle válido.
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    $response = curl_exec($ch);
    $factilizaCalls++;
    $errno = curl_errno($ch);
    $errstr = curl_error($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $calledDni = true;
} else {
    // si no llamamos al endpoint, aseguramos variables
    $errno = 0;
    $errstr = '';
    $httpcode = 200;
    $response = json_encode($body, JSON_UNESCAPED_UNICODE);
}

// si hubo error en la llamada DNI (solo aplicable si se llamó)
if ($calledDni && $errno) {
    http_response_code(502);
    echo json_encode(['error' => 'cURL error', 'code' => $errno, 'message' => $errstr]);
    exit;
}

if ($calledDni && ($httpcode < 200 || $httpcode >= 300)) {
    // No salir: intentar aún consultar la licencia (algunas APIs devuelven 404 en /dni pero la licencia puede existir)
    // Guardar la respuesta decodificada para debug
    $decoded = json_decode($response, true);
    $body = ['_dni_error' => true, 'dni_http_code' => $httpcode, 'dni_body' => $decoded];
    // no hacemos exit aquí: continuamos y luego intentamos sacar la mejor info posible (licencia)
}

// Si no teníamos $body (no vino desde cache), decodificar la respuesta DNI
if (empty($body)) {
    $body = json_decode($response, true);
}
header('Content-Type: application/json');
// Preparar cache de licencia
$licCacheDir = __DIR__ . '/../cache/factiliza_brevete';
if (!is_dir($licCacheDir)) {
    @mkdir($licCacheDir, 0755, true);
}
$licCacheFile = $licCacheDir . '/' . preg_replace('/[^0-9]/', '', $dni) . '.json';
$licCacheTTL = 24 * 3600;
// Si ya existe licencia cacheada, adjuntarla
if (is_file($licCacheFile)) {
    $cachedLic = @json_decode(@file_get_contents($licCacheFile), true);
    if ($cachedLic && isset($cachedLic['ts']) && (time() - $cachedLic['ts']) < $licCacheTTL && isset($cachedLic['body'])) {
        $licBody = $cachedLic['body'];
        // attach numero if present
        if (isset($licBody['numero'])) {
            $body['numero_brevete'] = $licBody['numero'];
            if (!isset($body['data'])) $body['data'] = [];
            $body['data']['licencia'] = ['numero' => $licBody['numero']];
        }
    }
} 
// Intentar obtener licencia UNA VEZ si aún no tenemos numero_brevete y
// (se pidió fetch_brevete o no existe cache de licencia)
$needLicFetch = empty($body['numero_brevete']) && ($fetchBrevete || !is_file($licCacheFile));
// Intentamos la consulta de licencia incluso si la consulta DNI devolvió non-2xx
if ($needLicFetch) {
    $licUrl = 'https://api.factiliza.com/v1/licencia/info/' . $dni;
    $ch2 = curl_init($licUrl);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Accept: application/json, text/plain, */*',
        'User-Agent: swlavoro/1.0',
        'Origin: https://app.factiliza.com',
        'Referer: https://app.factiliza.com/',
        'Connection: keep-alive'
    ]);
    curl_setopt($ch2, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch2, CURLOPT_CONNECTTIMEOUT, 4);
    curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
    $licResp = @curl_exec($ch2);
    $factilizaCalls++;
    $licErr = curl_errno($ch2);
    $licCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);
    if (!$licErr && $licCode >= 200 && $licCode < 300 && $licResp) {
        $licData = @json_decode($licResp, true);
        // Extraer número de licencia según la estructura
        $numero = $licData['data']['licencia']['numero'] ?? $licData['data']['numero'] ?? null;
        // Extraer nombre completo si viene en la estructura de licencia
        $nombreCompleto = $licData['data']['nombre_completo'] ?? $licData['data']['nombre'] ?? null;
        if ($numero) {
            $body['numero_brevete'] = $numero;
            if (!isset($body['data'])) $body['data'] = [];
            $body['data']['licencia'] = ['numero' => $numero];
            if ($nombreCompleto) {
                // normalizar nombre completo para front-end
                $body['apellidos_nombres'] = $nombreCompleto;
                $body['data']['nombre_completo'] = $nombreCompleto;
            }
            // Guardar cache de licencia
            @file_put_contents($licCacheFile, json_encode(['ts' => time(), 'body' => ['numero' => $numero]], JSON_UNESCAPED_UNICODE));
        }
        else {
            // adjuntar debug de respuesta de licencia si se solicitó desde el cliente
            if ($fetchBrevete) {
                $licDataDebug = @json_decode($licResp, true);
                if ($licDataDebug) {
                    $body['_debug_lic'] = $licDataDebug;
                } else {
                    $body['_debug_lic_raw'] = substr($licResp ?? '', 0, 1000);
                }
            }
        }
    }
}



// Asegurar que la respuesta al cliente sea 200 OK (la API externa puede devolver 404 pero queremos manejarlo)
http_response_code(200);
// Añadir conteo de llamadas en header para ayudar en debugging (visible en network)
header('X-Factiliza-Calls: ' . (int)$factilizaCalls);
echo json_encode($body, JSON_UNESCAPED_UNICODE);

// Guardar en cache simple por DNI para evitar consumir consultas repetidas
$cacheDir = __DIR__ . '/../cache/factiliza_dni';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}
// Cachear la respuesta enriquecida cuando tenga información útil (nombres o brevete)
$shouldCache = false;
if (!empty($body)) {
    if (!empty($body['numero_brevete']) || !empty($body['apellidos_nombres']) || (isset($body['data']) && !empty($body['data']))) {
        $shouldCache = true;
    }
}
if ($shouldCache) {
    $cacheFile = $cacheDir . '/' . preg_replace('/[^0-9]/', '', $dni) . '.json';
    @file_put_contents($cacheFile, json_encode([
        'ts' => time(),
        'http_code' => $httpcode ?? 200,
        'body' => $body
    ], JSON_UNESCAPED_UNICODE));
}
