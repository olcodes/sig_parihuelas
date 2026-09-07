<?php
// Controlador para integraciones de consulta RUC y Brevete
class ApiController extends Controller
{
    // Método index para evitar errores cuando se llama /api sin método específico
    public function index()
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(200);
        echo json_encode([
            'success' => false,
            'message' => 'API endpoint. Especifique un método: consultarDni, consultarCarnet, consultarBrevete, consultarRuc',
            'available_methods' => ['consultarDni', 'consultarCarnet', 'consultarBrevete', 'consultarRuc']
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Consulta Carnet de Extranjería en Factiliza vía backend seguro
    public function consultarCarnet()
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $params = json_decode(file_get_contents('php://input'), true);
        $carnet = isset($params['carnet']) ? trim($params['carnet']) : '';
        if (!$carnet || strlen($carnet) < 8) {
            echo json_encode(['success' => false, 'message' => 'Carnet inválido'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        // Llamada a la API de Factiliza: probar varios endpoints conocidos (carnet y cee, con/sin prefijo /pe)
        $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIzOTY3NiIsImh0dHA6Ly9zY2hlbWFzLm1pY3Jvc29mdC5jb20vd3MvMjAwOC8wNi9pZGVudGl0eS9jbGFpbXMvcm9sZSI6ImNvbnN1bHRvciJ9.vfxtjiaNq8g6QbsX7h2b_BYfhGcXMiUiOPetH-JKaZI';
        $base = 'https://api.factiliza.com';
        $candidates = [
            "$base/v1/carnet/info/",
            "$base/pe/v1/carnet/info/",
            "$base/v1/cee/info/",
            "$base/cee/info/",
            "$base/pe/v1/cee/info/",
        ];
        $result = null;
        $data = null;
        $attempts = [];
        // preferir cURL para más control
        foreach ($candidates as $prefix) {
            $urlTry = $prefix . rawurlencode($carnet);
            $attempt = ['url' => $urlTry, 'http' => null, 'err' => null, 'resp' => null];
            if (!function_exists('curl_init')) {
                // fallback a file_get_contents si cURL no disponible
                $opts = ['http' => ['method' => 'GET', 'header' => "Authorization: Bearer $token\r\nAccept: application/json, text/plain, */*\r\n"]];
                $context = stream_context_create($opts);
                $resp = @file_get_contents($urlTry, false, $context);
                $attempt['resp'] = $resp;
                if (isset($http_response_header) && is_array($http_response_header)) {
                    foreach ($http_response_header as $h) {
                        if (preg_match('#HTTP/\d+\.\d+\s+(\d+)#', $h, $m)) { $attempt['http'] = (int)$m[1]; break; }
                    }
                }
                $attempts[] = $attempt;
                if ($resp !== false && $resp !== '') {
                    $result = $resp;
                    $data = json_decode($resp, true);
                    break;
                }
                continue;
            }
            $ch = curl_init($urlTry);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token", 'Accept: application/json, text/plain, */*', 'User-Agent: swlavoro/1.0', 'Origin: https://app.factiliza.com', 'Referer: https://app.factiliza.com/']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            $resp = @curl_exec($ch);
            $err = curl_error($ch);
            $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $attempt['resp'] = $resp;
            $attempt['err'] = $err;
            $attempt['http'] = $http;
            $attempts[] = $attempt;
            if ($resp !== false && $resp !== '') {
                $result = $resp;
                $data = json_decode($resp, true);
                break;
            }
        }

        // Si no obtuvimos resultado y el código fue 404, intentar endpoint alternativo (/pe/v1/)
        if ((empty($data) || $data === null) && $httpCode === 404) {
            $altUrl = 'https://api.factiliza.com/pe/v1/carnet/info/' . rawurlencode($carnet);
            if (function_exists('curl_init')) {
                $ch2 = curl_init($altUrl);
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch2, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token", 'Accept: application/json, text/plain, */*', 'User-Agent: swlavoro/1.0', 'Origin: https://app.factiliza.com', 'Referer: https://app.factiliza.com/', 'Connection: keep-alive']);
                curl_setopt($ch2, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch2, CURLOPT_CONNECTTIMEOUT, 5);
                curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
                $altResp = @curl_exec($ch2);
                $altErr = curl_error($ch2);
                $altHttp = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                curl_close($ch2);
                if ($altResp !== false && $altResp !== '') {
                    $data = json_decode($altResp, true);
                    $httpCode = $altHttp;
                }
                // Añadir info de diagnóstico en logs
                @file_put_contents(__DIR__ . '/../../logs/factiliza_carnet_debug.txt', "[".date('c')."] alt_http={$altHttp} alt_err=".str_replace("\n"," ", substr($altErr,0,1000))."\n", FILE_APPEND);
            }
        }
        if (isset($data['data']['nombre_completo'])) {
            echo json_encode([
                'success' => true,
                'apellidos_nombres' => $data['data']['nombre_completo']
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        // Compatibilidad con la estructura mostrada en la documentación (/cee/info/{cee}):
        // data: { "numero": "...", "nombres": "...", "apellido_paterno": "...", "apellido_materno": "..." }
        if (isset($data['data']) && is_array($data['data']) && (isset($data['data']['nombres']) || isset($data['data']['apellido_paterno']) || isset($data['data']['apellido_materno']))) {
            $nombres = trim((string)($data['data']['nombres'] ?? $data['data']['nombre'] ?? ''));
            $apP = trim((string)($data['data']['apellido_paterno'] ?? $data['data']['apellidoPaterno'] ?? ''));
            $apM = trim((string)($data['data']['apellido_materno'] ?? $data['data']['apellidoMaterno'] ?? ''));
            // Componer: Apellidos + Nombres (más legible en formularios)
            $composed = trim(($apP . ' ' . $apM . ' ' . $nombres));
            if ($composed === '') {
                // fallback a cualquier campo disponible
                $composed = trim(($data['data']['nombre_completo'] ?? $data['data']['apellidos_nombres'] ?? $nombres . ' ' . $apP . ' ' . $apM));
            }
            echo json_encode(['success' => true, 'apellidos_nombres' => $composed], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Si no se pudo extraer nombre, continuar con diagnóstico
        else {
            // Preparar información de diagnóstico para devolver en la respuesta
            $debugInfo = [
                'primary_http' => isset($httpCode) ? (int)$httpCode : null,
                'primary_result' => isset($result) ? substr($result, 0, 2000) : null,
                'primary_curl_err' => isset($curlErr) ? substr($curlErr, 0, 1000) : null,
                'alt_http' => isset($altHttp) ? (int)$altHttp : null,
                'alt_result' => isset($altResp) ? substr($altResp, 0, 2000) : null,
                'alt_curl_err' => isset($altErr) ? substr($altErr, 0, 1000) : null,
            ];
            // intentar guardar en logs (siempre que la ruta sea escribible)
            @file_put_contents(__DIR__ . '/../../logs/factiliza_carnet_debug.txt', json_encode(array_merge(['ts' => date('c'), 'carnet' => $carnet], $debugInfo), JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
            $out = ['success' => false, 'message' => 'No se encontró información para este carnet.', 'diagnostic' => $debugInfo];
            echo json_encode($out, JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    // Consulta Brevete en Factiliza vía backend seguro
    public function consultarBrevete()
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $params = json_decode(file_get_contents('php://input'), true);
        $dni = isset($params['brevete']) ? trim($params['brevete']) : '';
        if (!$dni || strlen($dni) < 8) {
            echo json_encode(['success' => false, 'message' => 'DNI inválido'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        // Llamada a la API de Factiliza usando el DNI (licencia)
        $cacheDir = dirname(__DIR__) . '/cache/factiliza_brevete';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        $cacheFile = $cacheDir . '/' . preg_replace('/[^0-9]/', '', $dni) . '.json';
        $cacheTTL = 24 * 3600; // 24 horas
        if (is_file($cacheFile)) {
            $cached = @json_decode(@file_get_contents($cacheFile), true);
            if ($cached && isset($cached['ts']) && (time() - $cached['ts']) < $cacheTTL && isset($cached['body'])) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($cached['body'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        $url = 'https://api.factiliza.com/v1/licencia/info/' . $dni;
        $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIzOTY3NiIsImh0dHA6Ly9zY2hlbWFzLm1pY3Jvc29mdC5jb20vd3MvMjAwOC8wNi9pZGVudGl0eS9jbGFpbXMvcm9sZSI6ImNvbnN1bHRvciJ9.vfxtjiaNq8g6QbsX7h2b_BYfhGcXMiUiOPetH-JKaZI';
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => "Authorization: Bearer $token\r\nAccept: application/json\r\n"
            ]
        ];
        $context = stream_context_create($opts);
        $result = @file_get_contents($url, false, $context);
        if ($result === false) {
            echo json_encode(['success' => false, 'message' => 'No se pudo consultar la licencia.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $data = json_decode($result, true);
        if (isset($data['data']['nombre_completo'])) {
            $out = [
                'success' => true,
                'apellidos_nombres' => $data['data']['nombre_completo'],
                'numero_brevete' => $data['data']['licencia']['numero'] ?? ''
            ];
            // guardar cache
            @file_put_contents($cacheFile, json_encode(['ts' => time(), 'body' => $out], JSON_UNESCAPED_UNICODE));
            echo json_encode($out, JSON_UNESCAPED_UNICODE);
            exit;
        }
        // Si la respuesta tiene message pero no data, intentar extraer nombre de otro campo (futuro-proof)
        if (isset($data['message']) && !empty($data['message'])) {
            $out = [
                'success' => false,
                'apellidos_nombres' => '',
                'message' => $data['message']
            ];
            echo json_encode($out, JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'No se encontró información para este brevete.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    // Consulta DNI: primero BD, luego Factiliza si no existe
    public function consultarDni()
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $dni = trim($input['dni'] ?? '');
        if ($dni === '') {
            echo json_encode(['success' => false, 'message' => 'DNI vacío'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        // Intentar BD primero
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare('SELECT ApellidosPaterno, ApellidoMaterno, Nombres, ApellidosNombres, Brevete FROM choferes WHERE DocIdentidad = ? LIMIT 1');
            $stmt->execute([$dni]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $result = ['success' => true, 'apellidos_nombres' => $row['ApellidosNombres'] ?? trim(($row['ApellidosPaterno'] ?? '') . ' ' . ($row['ApellidoMaterno'] ?? '') . ' ' . ($row['Nombres'] ?? ''))];
                if (!empty($row['Brevete'])) $result['numero_brevete'] = $row['Brevete'];
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                exit;
            }
        } catch (Exception $e) {
            // Continuar a Factiliza si falla BD
        }
        // No hay en BD -> consultar Factiliza
        $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIzOTY3NiIsImh0dHA6Ly9zY2hlbWFzLm1pY3Jvc29mdC5jb20vd3MvMjAwOC8wNi9pZGVudGl0eS9jbGFpbXMvcm9sZSI6ImNvbnN1bHRvciJ9.vfxtjiaNq8g6QbsX7h2b_BYfhGcXMiUiOPetH-JKaZI';
        $url = 'https://api.factiliza.com/v1/dni/info/' . rawurlencode($dni);
        
        if (!function_exists('curl_init')) {
            $opts = ['http' => ['method' => 'GET', 'header' => "Authorization: Bearer $token\r\nAccept: application/json\r\n", 'timeout' => 10]];
            $context = stream_context_create($opts);
            $res = @file_get_contents($url, false, $context);
        } else {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token", 'Accept: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            $res = @curl_exec($ch);
            curl_close($ch);
        }
        
        if ($res === false || $res === '') {
            echo json_encode(['success' => false, 'message' => 'No se encontró información para este DNI.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $data = json_decode($res, true);
        if ($data && isset($data['data'])) {
            $src = $data['data'];
            $nombre = $src['nombre_completo'] ?? $src['nombre'] ?? $src['nombres'] ?? null;
            $out = ['success' => true];
            if ($nombre) $out['apellidos_nombres'] = $nombre;
            if (!empty($src['licencia']['numero'])) $out['numero_brevete'] = $src['licencia']['numero'];
            echo json_encode($out, JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        echo json_encode(['success' => false, 'message' => 'No se encontró información para este DNI.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    // Consulta RUC en Factiliza vía backend seguro
    public function consultarRuc()
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $params = json_decode(file_get_contents('php://input'), true);
        $ruc = isset($params['ruc']) ? trim($params['ruc']) : '';
        if (!$ruc || strlen($ruc) !== 11 || !is_numeric($ruc)) {
            echo json_encode(['success' => false, 'message' => 'RUC inválido'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        // Llamada a la API de Factiliza
        $url = 'https://api.factiliza.com/v1/ruc/info/' . $ruc;
            $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIzOTY3NiIsImh0dHA6Ly9zY2hlbWFzLm1pY3Jvc29mdC5jb20vd3MvMjAwOC8wNi9pZGVudGl0eS9jbGFpbXMvcm9sZSI6ImNvbnN1bHRvciJ9.vfxtjiaNq8g6QbsX7h2b_BYfhGcXMiUiOPetH-JKaZI';
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => "Authorization: Bearer $token\r\nAccept: application/json\r\n"
            ]
        ];
        $context = stream_context_create($opts);
        $result = @file_get_contents($url, false, $context);
        if ($result === false) {
            echo json_encode(['success' => false, 'message' => 'No se pudo consultar el RUC.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $data = json_decode($result, true);
        if (isset($data['data']['nombre_o_razon_social'])) {
            echo json_encode([
                'success' => true,
                'razon_social' => $data['data']['nombre_o_razon_social'],
                'direccion' => $data['data']['direccion'] ?? ''
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'message' => 'El RUC ingresado no ha sido encontrado.'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}
