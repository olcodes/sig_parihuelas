<?php
// Front controller universal para local y hosting
date_default_timezone_set('America/Lima');
session_name('SWLAVORO');

// Usar directorio de sesiones dentro del proyecto para evitar problemas de permisos
$sessionPath = dirname(__FILE__) . '/tmp';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}
session_save_path($sessionPath);

session_start();

// Configurar errores según el entorno
$isLocal = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false);

if ($isLocal) {
    // Desarrollo local: mostrar errores
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    // Hosting: ocultar errores
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Definir ruta base
define('ROOT', dirname(__FILE__));

// Cargar autoloader si existe
if (file_exists(ROOT . '/vendor/autoload.php')) {
    require_once ROOT . '/vendor/autoload.php';
}

// Cargar helpers universales
require_once ROOT . '/app/helpers/base_url.php';
require_once ROOT . '/app/helpers/redirect.php';
require_once ROOT . '/app/helpers/url_helper.php';

// ── Cierre de sesión automático por inactividad (BASC) ─────────────────────
if (!defined('SESSION_INACTIVITY_TIMEOUT')) {
    define('SESSION_INACTIVITY_TIMEOUT', 1200); // segundos — 1 min para pruebas (cambiar a 1200 para 20 min)
}
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

// Cargar núcleo del sistema
require_once ROOT . '/core/App.php';
require_once ROOT . '/core/Controller.php';
require_once ROOT . '/core/Model.php';
require_once ROOT . '/config/database.php';
require_once ROOT . '/config/hosting.php';

// Inicializar aplicación
try {
    $app = new App();
} catch (Exception $e) {
    if ($isLocal) {
        echo "<h1>Error de Aplicación</h1>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "<p>Archivo: " . $e->getFile() . " línea " . $e->getLine() . "</p>";
    } else {
        echo "Error interno del servidor.";
    }
}
