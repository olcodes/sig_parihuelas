<?php

class BackupController extends Controller
{
    // ---------------------------------------------------------------
    //  index() — Vista principal: listado de backups por tipo
    // ---------------------------------------------------------------
    public function index()
    {
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $this->requirePrivilegio('ver_backups');

        require_once ROOT . '/app/helpers/BackupService.php';
        $service = new BackupService();

        $this->view('backups/index', [
            'titulo'   => 'Gestión de Backups',
            'daily'    => $service->listar('daily'),
            'monthly'  => $service->listar('monthly'),
            'critical' => $service->listar('critical'),
            'retention'=> BackupService::RETENTION,
        ]);
    }

    // ---------------------------------------------------------------
    //  crear($tipo) — Genera un backup manual
    // ---------------------------------------------------------------
    public function crear()
    {
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $this->requirePrivilegio('gestionar_backups');

        $tipo = $_GET['tipo'] ?? '';
        $tipos_validos = ['daily', 'monthly', 'critical'];
        if (!in_array($tipo, $tipos_validos, true)) {
            $_SESSION['backup_msg'] = ['tipo' => 'danger', 'texto' => 'Tipo de backup inválido.'];
            redirect('/backup');
        }

        require_once ROOT . '/app/helpers/BackupService.php';
        $service  = new BackupService();
        $resultado = $service->generar($tipo);

        if ($resultado['success']) {
            $size = BackupService::formatSize($resultado['size']);
            $_SESSION['backup_msg'] = [
                'tipo'  => 'success',
                'texto' => "Backup <strong>{$tipo}</strong> creado exitosamente: <code>{$resultado['filename']}</code> ({$size})"
            ];
        } else {
            $_SESSION['backup_msg'] = [
                'tipo'  => 'danger',
                'texto' => "Error al crear backup: " . htmlspecialchars($resultado['error'])
            ];
        }

        redirect('/backup');
    }

    // ---------------------------------------------------------------
    //  descargar() — Descarga segura autenticada
    // ---------------------------------------------------------------
    public function descargar()
    {
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $this->requirePrivilegio('ver_backups');

        require_once ROOT . '/app/helpers/BackupService.php';
        $service  = new BackupService();

        $tipo     = $_GET['tipo']     ?? '';
        $filename = $_GET['archivo']  ?? '';

        $ruta = $service->rutaDescarga($tipo, $filename);
        if (!$ruta) {
            http_response_code(404);
            echo 'Archivo no encontrado.';
            exit;
        }

        header('Content-Type: application/gzip');
        header('Content-Disposition: attachment; filename="' . basename($ruta) . '"');
        header('Content-Length: ' . filesize($ruta));
        header('Cache-Control: no-cache, must-revalidate');
        readfile($ruta);
        exit;
    }

    // ---------------------------------------------------------------
    //  eliminar() — Elimina un backup puntual
    // ---------------------------------------------------------------
    public function eliminar()
    {
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $this->requirePrivilegio('gestionar_backups');

        require_once ROOT . '/app/helpers/BackupService.php';
        $service  = new BackupService();

        $tipo     = $_GET['tipo']    ?? '';
        $filename = $_GET['archivo'] ?? '';

        if ($service->eliminar($tipo, $filename)) {
            $_SESSION['backup_msg'] = [
                'tipo'  => 'success',
                'texto' => "Backup eliminado: <code>" . htmlspecialchars($filename) . "</code>"
            ];
        } else {
            $_SESSION['backup_msg'] = [
                'tipo'  => 'danger',
                'texto' => 'No se pudo eliminar el archivo.'
            ];
        }

        redirect('/backup');
    }

    // ---------------------------------------------------------------
    //  cron($tipo/$token) — Endpoint para cron jobs automáticos
    //  URL: /backup/cron?tipo=daily&token=TOKEN_SECRETO
    // ---------------------------------------------------------------
    public function cron()
    {
        // Sin sesión — protegido solo por token
        $token        = $_GET['token'] ?? '';
        $tipo         = $_GET['tipo']  ?? '';
        $tokenValido  = defined('BACKUP_CRON_TOKEN') ? BACKUP_CRON_TOKEN : '';

        if (!$tokenValido || !hash_equals($tokenValido, $token)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Token inválido']);
            exit;
        }

        $tipos_validos = ['daily', 'monthly', 'critical'];
        if (!in_array($tipo, $tipos_validos, true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Tipo inválido']);
            exit;
        }

        require_once ROOT . '/app/helpers/BackupService.php';
        $service   = new BackupService();
        $resultado = $service->generar($tipo);

        echo json_encode($resultado);
        exit;
    }
}
