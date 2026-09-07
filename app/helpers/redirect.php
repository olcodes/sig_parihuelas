<?php
/**
 * Helper para redirecciones universales
 * Funciona tanto en local (XAMPP) como en hosting (Linux)
 */

/**
 * Redirección robusta que funciona en cualquier entorno
 * @param string $url URL completa o relativa a donde redirigir
 * @param bool $exit Si debe terminar la ejecución después de redirigir
 */
function redirect($url, $exit = true) {
    // Si la URL no tiene protocolo, usar APP_URL en lugar de BASE_URL
    if (!preg_match('/^https?:\/\//', $url)) {
        if (strpos($url, '/') !== 0) {
            $url = '/' . $url;
        }
        // Usar APP_URL para redirecciones de aplicación
        $url = APP_URL . $url;
    }
    
    // Detectar si estamos en desarrollo local o hosting
    $isLocal = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') || 
               (isset($_SERVER['SERVER_NAME']) && in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1'])) ||
               (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false);
    
    // En local, intentar header normal primero
    if ($isLocal && !headers_sent()) {
        header('Location: ' . $url);
        if ($exit) exit;
        return;
    }
    
    // En hosting o si headers ya enviados, usar redirección JavaScript
    if (!headers_sent()) {
        // Intentar header si aún es posible
        header('Location: ' . $url);
        if ($exit) exit;
    } else {
        // Headers ya enviados, usar JavaScript
        echo '<script type="text/javascript">';
        echo 'window.location.href = "' . $url . '";';
        echo '</script>';
        echo '<noscript>';
        echo '<meta http-equiv="refresh" content="0;url=' . $url . '">';
        echo '</noscript>';
        if ($exit) exit;
    }
}

/**
 * Detectar si estamos en entorno local
 * @return bool
 */
function isLocalEnvironment() {
    return (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') || 
           (isset($_SERVER['SERVER_NAME']) && in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1'])) ||
           (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false);
}

/**
 * Detectar si estamos en hosting/producción
 * @return bool
 */
function isProductionEnvironment() {
    return !isLocalEnvironment();
}
?>