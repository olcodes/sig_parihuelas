<?php
/**
 * Helper para generar URLs del sistema
 * Maneja automáticamente las diferencias entre local y hosting
 */

/**
 * Genera URL para assets (CSS, JS, imágenes)
 * @param string $path Ruta del asset
 * @return string URL completa del asset
 */
function asset_url($path = '') {
    // Si BASE_URL está vacío, forzar a buscar en /public o /public_html
    if (defined('BASE_URL') && BASE_URL !== '') {
        return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
    }
    // Fallback: intentar /public primero, luego /public_html
    $publicPaths = ['/public', '/public_html'];
    foreach ($publicPaths as $pub) {
        if (file_exists(__DIR__ . '/../../' . $pub . '/' . ltrim($path, '/'))) {
            return $pub . '/' . ltrim($path, '/');
        }
    }
    // Último recurso: raíz
    return '/' . ltrim($path, '/');
}

/**
 * Genera URL para rutas de la aplicación (controladores)
 * @param string $path Ruta de la aplicación
 * @return string URL completa de la aplicación
 */
function app_url($path = '') {
    return APP_URL . '/' . ltrim($path, '/');
}

/**
 * Genera URL para formularios y AJAX
 * @param string $path Ruta del controlador/acción
 * @return string URL completa para formularios
 */
function form_url($path = '') {
    return app_url($path);
}

/**
 * Redirección con URL correcta según el entorno
 * @param string $path Ruta a redireccionar
 */
function redirect_to($path = '') {
    $url = app_url($path);
    header("Location: $url");
    exit;
}
?>