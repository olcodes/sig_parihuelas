<?php
// NO output antes de las redirecciones
date_default_timezone_set('America/Lima');

// Ruta base del proyecto (un nivel arriba de /public)
define('ROOT', __DIR__ . '/..');

require_once __DIR__ . '/../app/helpers/base_url.php';
require_once __DIR__ . '/../app/helpers/url_helper.php'; // Helper para URLs
require_once __DIR__ . '/../app/helpers/redirect.php'; // Helper universal para redirecciones

session_name('SWLAVORO');
ini_set('session.cookie_path', '/');

// Usar directorio de sesiones dentro del proyecto para evitar problemas de permisos
$sessionPath = __DIR__ . '/../tmp';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}
session_save_path($sessionPath);

session_start();

// ── Cierre de sesión automático por inactividad (BASC) ─────────────────────
define('SESSION_INACTIVITY_TIMEOUT', 1200); // segundos — 1 min para pruebas (cambiar a 1200 para 20 min)
if (isset($_SESSION['user'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_INACTIVITY_TIMEOUT) {
        session_unset();
        session_destroy();
        redirect('/login?timeout=1');
    }
    $_SESSION['last_activity'] = time();
} elseif (!isset($_SESSION['last_activity'])) {
    $_SESSION['last_activity'] = time();
}
// ───────────────────────────────────────────────────────────────────────────

// Configuración de errores para producción
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/hosting.php';

// Inicializar la aplicación
$app = new App();
?>