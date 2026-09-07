<?php
/**
 * CONFIGURACIÓN UNIVERSAL DE URLs - COMPATIBLE CON CUALQUIER HOSTING
 * Soporta: Apache, Nginx, IIS, subdominios, subcarpetas, SSL, puerto personalizado
 * Escenarios: Local, Hosting con estructura mixta, Hosting con todo en raíz
 * 
 * @author Sistema Lavoro-ERP
 * @version 3.0 - Configuración Ultra-robusta Mejorada
 */

// 1. DETECCIÓN DE PROTOCOLO (Ultra-compatible)
function detectarProtocolo() {
    // Métodos múltiples para detectar HTTPS
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return 'https';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') return 'https';
    if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') return 'https';
    if (!empty($_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO']) && $_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO'] === 'https') return 'https';
    if (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) return 'https';
    return 'http';
}

// 2. DETECCIÓN DE HOST (Compatible con proxy/CDN/Cloudflare)
function detectarHost() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) return $_SERVER['HTTP_X_FORWARDED_HOST'];
    if (!empty($_SERVER['HTTP_HOST'])) return $_SERVER['HTTP_HOST'];
    if (!empty($_SERVER['SERVER_NAME'])) return $_SERVER['SERVER_NAME'];
    return 'localhost';
}

// 3. DETECCIÓN INTELIGENTE DE ESTRUCTURA (Nuevo algoritmo mejorado)
function detectarEstructura() {
    $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $documentRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    $currentDir = str_replace('\\', '/', __DIR__);
    
    // Detectar si estamos en estructura local o hosting
    $isLocal = (
        strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || 
        strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false ||
        strpos($_SERVER['HTTP_HOST'] ?? '', '192.168.') !== false ||
        strpos($_SERVER['HTTP_HOST'] ?? '', '10.0.') !== false ||
        strpos($_SERVER['HTTP_HOST'] ?? '', '.local') !== false
    );
    
    if ($isLocal) {
        // Detectar el nombre de la carpeta del proyecto dinámicamente
        $projectFolder = basename(dirname(dirname(__DIR__)));
        $projectPath = '/' . $projectFolder;
        $publicPos = strpos($scriptPath, '/public');
        if ($publicPos !== false) {
            return [
                'basePath' => substr($scriptPath, 0, $publicPos),
                'baseUrl' => substr($scriptPath, 0, $publicPos) . '/public',
                'appUrl' => $projectPath,
                'mode' => 'local_structure'
            ];
        }
        // Fallback local para cualquier carpeta
        if (strpos($scriptPath, $projectPath) !== false) {
            $projectPos = strpos($scriptPath, $projectPath);
            return [
                'basePath' => substr($scriptPath, 0, $projectPos) . $projectPath,
                'baseUrl' => substr($scriptPath, 0, $projectPos) . $projectPath . '/public',
                'appUrl' => substr($scriptPath, 0, $projectPos) . $projectPath,
                'mode' => 'local_fallback'
            ];
        }
    } else {
        // HOSTING: Detectar estructura automáticamente
        
        // ============================================================
        // DETECCIÓN DE ESTRUCTURA EN HOSTING cPanel
        // ============================================================
        // Escenario típico cPanel:
        //   RAÍZ: /home/user/public_html/  -> .htaccess, index.php, app/, config/, core/, etc.
        //   SUBCARPETA "public_html/":     -> css/, js/, img/, api/, index.php
        //
        // El script actual (base_url.php) está en: /home/user/public_html/app/helpers/
        // El documentRoot apunta a: /home/user/public_html/
        // ============================================================
        
        // --- Detectar si existe subcarpeta "public_html" con assets ---
        $publicHtmlSubdir = $documentRoot . '/public_html';
        $hasPublicHtmlSubdir = (
            is_dir($publicHtmlSubdir) &&
            (file_exists($publicHtmlSubdir . '/css') ||
             file_exists($publicHtmlSubdir . '/js') ||
             file_exists($publicHtmlSubdir . '/img'))
        );
        
        // Caso 1: Estructura con subcarpeta "public_html/" para assets
        // Core en RAÍZ, assets en /public_html/
        if ($hasPublicHtmlSubdir) {
            return [
                'basePath' => '',
                'baseUrl' => '/public_html',
                'appUrl' => '',
                'mode' => 'hosting_root_with_public_html'
            ];
        }
        
        // Caso 2: TODO en raíz (estructura plana)
        // Todos los archivos en public_html/ (sin subcarpeta public_html/)
        if (file_exists($documentRoot . '/app') ||
            file_exists($documentRoot . '/config')) {
            return [
                'basePath' => '',
                'baseUrl' => '',
                'appUrl' => '',
                'mode' => 'hosting_root'
            ];
        }
        
        // Caso 3: Estructura mixta (estructura actual)
        // Core en /public_html/swlavoro/, archivos públicos en /public_html/
        $publicPos = strpos($scriptPath, '/public');
        if ($publicPos !== false) {
            return [
                'basePath' => substr($scriptPath, 0, $publicPos),
                'baseUrl' => substr($scriptPath, 0, $publicPos) . '/public',
                'appUrl' => substr($scriptPath, 0, $publicPos),
                'mode' => 'hosting_mixed'
            ];
        }
        
        // Caso 4: Todo en subcarpeta
        if (!empty($scriptPath) && $scriptPath !== '/') {
            return [
                'basePath' => $scriptPath,
                'baseUrl' => $scriptPath,
                'appUrl' => $scriptPath,
                'mode' => 'hosting_subfolder'
            ];
        }
    }
    
    // Fallback universal
    return [
        'basePath' => '',
        'baseUrl' => '',
        'appUrl' => '',
        'mode' => 'fallback'
    ];
}

// 4. CONSTRUCCIÓN INTELIGENTE DE URLs
if (isset($_SERVER['HTTP_HOST'])) {
    $protocol = detectarProtocolo();
    $host = detectarHost();
    $estructura = detectarEstructura();
    
    // Construir URLs según la estructura detectada
    define('BASE_URL', $protocol . '://' . $host . $estructura['baseUrl']);
    define('APP_URL', $protocol . '://' . $host . $estructura['appUrl']);
    define('DEPLOYMENT_MODE', $estructura['mode']);
    
} else {
    // Fallback para CLI o entornos especiales
    define('BASE_URL', '');
    define('APP_URL', '');
    define('DEPLOYMENT_MODE', 'cli');
}

// 5. DIAGNÓSTICO AVANZADO Y DEPURACIÓN
if (isset($_GET['debug_url']) || isset($_GET['debug_structure'])) {
    $estructura = detectarEstructura();
    echo "<div style='background:#f8f9fa;border:1px solid #dee2e6;padding:20px;margin:20px;border-radius:8px;font-family:monospace;font-size:13px;'>";
    echo "<h3 style='color:#495057;margin-top:0;'>🔧 DIAGNÓSTICO COMPLETO DE CONFIGURACIÓN</h3>";
    
    echo "<div style='background:#e3f2fd;padding:15px;border-radius:5px;margin:10px 0;'>";
    echo "<h4 style='margin:0;color:#1565c0;'>📊 Información del Servidor</h4>";
    echo "<table style='width:100%;margin-top:10px;'>";
    echo "<tr><td><strong>HTTP_HOST:</strong></td><td style='color:#1976d2;'>" . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "</td></tr>";
    echo "<tr><td><strong>SERVER_NAME:</strong></td><td style='color:#1976d2;'>" . ($_SERVER['SERVER_NAME'] ?? 'N/A') . "</td></tr>";
    echo "<tr><td><strong>SCRIPT_NAME:</strong></td><td style='color:#1976d2;'>" . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "</td></tr>";
    echo "<tr><td><strong>REQUEST_URI:</strong></td><td style='color:#1976d2;'>" . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "</td></tr>";
    echo "<tr><td><strong>DOCUMENT_ROOT:</strong></td><td style='color:#1976d2;'>" . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "</td></tr>";
    echo "<tr><td><strong>__FILE__:</strong></td><td style='color:#1976d2;'>" . __FILE__ . "</td></tr>";
    echo "</table>";
    echo "</div>";
    
    echo "<div style='background:#e8f5e8;padding:15px;border-radius:5px;margin:10px 0;'>";
    echo "<h4 style='margin:0;color:#2e7d32;'>🎯 Estructura Detectada</h4>";
    echo "<table style='width:100%;margin-top:10px;'>";
    echo "<tr><td><strong>Modo de Despliegue:</strong></td><td style='color:#388e3c;font-weight:bold;'>" . $estructura['mode'] . "</td></tr>";
    echo "<tr><td><strong>Base Path:</strong></td><td style='color:#388e3c;'>" . $estructura['basePath'] . "</td></tr>";
    echo "<tr><td><strong>Base URL Path:</strong></td><td style='color:#388e3c;'>" . $estructura['baseUrl'] . "</td></tr>";
    echo "<tr><td><strong>App URL Path:</strong></td><td style='color:#388e3c;'>" . $estructura['appUrl'] . "</td></tr>";
    echo "</table>";
    echo "</div>";
    
    echo "<div style='background:#fff3e0;padding:15px;border-radius:5px;margin:10px 0;'>";
    echo "<h4 style='margin:0;color:#ef6c00;'>🌐 URLs Finales</h4>";
    echo "<table style='width:100%;margin-top:10px;'>";
    echo "<tr><td><strong>BASE_URL (Assets):</strong></td><td style='color:#f57c00;font-weight:bold;'>" . BASE_URL . "</td></tr>";
    echo "<tr><td><strong>APP_URL (Routes):</strong></td><td style='color:#f57c00;font-weight:bold;'>" . APP_URL . "</td></tr>";
    echo "<tr><td><strong>Protocolo:</strong></td><td style='color:#f57c00;'>" . $protocol . "</td></tr>";
    echo "<tr><td><strong>Host:</strong></td><td style='color:#f57c00;'>" . $host . "</td></tr>";
    echo "</table>";
    echo "</div>";
    
    echo "<div style='background:#fce4ec;padding:15px;border-radius:5px;margin:10px 0;'>";
    echo "<h4 style='margin:0;color:#c2185b;'>🧪 Tests de Funciones</h4>";
    echo "<table style='width:100%;margin-top:10px;'>";
    echo "<tr><td><strong>app_url('login'):</strong></td><td style='color:#ad1457;'>" . (function_exists('app_url') ? app_url('login') : 'FUNCIÓN NO DISPONIBLE') . "</td></tr>";
    echo "<tr><td><strong>asset_url('css/style.css'):</strong></td><td style='color:#ad1457;'>" . (function_exists('asset_url') ? asset_url('css/style.css') : 'FUNCIÓN NO DISPONIBLE') . "</td></tr>";
    echo "</table>";
    echo "</div>";
    
    echo "<p style='margin-bottom:0;color:#6c757d;text-align:center;'>💡 <em>Accede con ?debug_url o ?debug_structure para ver esta información</em></p>";
    echo "</div>";
}

// 6. CONSTANTES ADICIONALES PARA MÁXIMA COMPATIBILIDAD
if (!defined('SITE_URL')) {
    define('SITE_URL', APP_URL);
}
if (!defined('ASSETS_URL')) {
    define('ASSETS_URL', BASE_URL);
}

// 7. CONFIGURACIÓN AUTOMÁTICA PARA HOSTING
if (DEPLOYMENT_MODE === 'hosting_root') {
    // En hosting con todo en raíz, asegurar que las rutas sean absolutas
    if (!defined('HOSTING_ROOT_MODE')) {
        define('HOSTING_ROOT_MODE', true);
    }
}
?>
