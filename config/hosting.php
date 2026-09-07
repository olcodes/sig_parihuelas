<?php
/**
 * ARCHIVO DE CONFIGURACIÓN PARA HOSTING
 * 
 * Instrucciones para el hosting:
 * 1. Subir todos los archivos del proyecto a public_html o el directorio web principal
 * 2. Asegurarse de que el archivo .htaccess esté en la raíz
 * 3. Las URLs automáticamente funcionarán sin modificaciones
 * 
 * Este archivo es opcional - solo para hostings que requieran configuración manual
 */

// CONFIGURACIÓN MANUAL PARA HOSTINGS ESPECIALES (descomenta si es necesario)

// Para hostings con configuración de subdirectorio forzado:
// define('FORCE_SUBDIRECTORY', '/mi-proyecto');

// Para hostings con SSL forzado:
// define('FORCE_HTTPS', true);

// Para hostings con múltiples dominios:
// define('CUSTOM_DOMAIN', 'midominio.com');

// ============================================================
// TOKEN SECRETO PARA CRON JOBS DE BACKUP
// Mantener este valor privado. Usado en las URLs de cron:
//   /backup/cron?tipo=daily&token=ESTE_TOKEN
// Para regenerarlo: php -r "echo bin2hex(random_bytes(24));"
// ============================================================
define('BACKUP_CRON_TOKEN', '0f1607096da7a26081ceeee53332df50f800d551e79052fe');

?>