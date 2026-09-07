<?php

class AuditoriaController extends Controller {

    public function index() {
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $this->requirePrivilegio('ver_auditoria');

        $model    = $this->model('Auditoria');
        $usuarios = $model->getUsuarios();

        $filtros = [
            'modulo'      => $_GET['modulo']      ?? '',
            'accion'      => $_GET['accion']      ?? '',
            'usuario_id'  => $_GET['usuario_id']  ?? '',
            'fecha_desde' => $_GET['fecha_desde'] ?? '',
            'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
            'nvale'       => $_GET['nvale']        ?? '',
        ];

        $porPagina = 50;
        $pagina    = max(1, (int)($_GET['pagina'] ?? 1));
        $offset    = ($pagina - 1) * $porPagina;

        $total      = $model->countLog($filtros);
        $totalPags  = max(1, ceil($total / $porPagina));
        $registros  = $model->getLog($filtros, $porPagina, $offset);

        $this->view('auditoria/index', [
            'titulo'      => 'Bitácora de Auditoría',
            'registros'   => $registros,
            'usuarios'    => $usuarios,
            'filtros'     => $filtros,
            'pagina'      => $pagina,
            'totalPaginas'=> $totalPags,
            'total'       => $total,
        ]);
    }

    /**
     * AJAX: devuelve el historial de un registro específico (Nivel 2).
     * URL: /auditoria/historial?modulo=despacho_interno&id=123
     */
    public function historial() {
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'message' => 'Sin sesión']);
            exit;
        }

        $modulo = $_GET['modulo'] ?? '';
        $id     = (int)($_GET['id'] ?? 0);

        if (!$modulo || !$id) {
            echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
            exit;
        }

        $model   = $this->model('Auditoria');
        $historial = $model->getHistorialRegistro($modulo, $id);

        echo json_encode(['success' => true, 'historial' => $historial]);
        exit;
    }

    /**
     * Exporta el log filtrado a Excel usando PhpSpreadsheet.
     */
    public function exportar() {
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $this->requirePrivilegio('ver_auditoria');

        $model   = $this->model('Auditoria');
        $filtros = [
            'modulo'      => $_GET['modulo']      ?? '',
            'accion'      => $_GET['accion']      ?? '',
            'usuario_id'  => $_GET['usuario_id']  ?? '',
            'fecha_desde' => $_GET['fecha_desde'] ?? '',
            'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
            'nvale'       => $_GET['nvale']        ?? '',
        ];

        $registros = $model->getLog($filtros, 10000, 0);

        require_once __DIR__ . '/../../vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bitácora');

        // Encabezados
        $headers = ['#', 'Módulo', 'N° Vale', 'Acción', 'Usuario', 'Nombres y Apellidos', 'Fecha', 'Hora', 'IP', 'N° Modificación'];
        foreach ($headers as $col => $h) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . '1';
            $sheet->setCellValue($cell, $h);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF1A237E');
            $sheet->getStyle($cell)->getFont()->getColor()->setARGB('FFFFFFFF');
        }

        // Datos
        foreach ($registros as $i => $row) {
            $r = $i + 2;
            $fh = !empty($row['fecha_hora']) ? date_create($row['fecha_hora']) : false;
            $sheet->setCellValue("A{$r}", $i + 1);
            $sheet->setCellValue("B{$r}", $row['modulo']   ?? '');
            $sheet->setCellValue("C{$r}", $row['nvale']    ?? '');
            $sheet->setCellValue("D{$r}", $row['accion']   ?? '');
            $sheet->setCellValue("E{$r}", $row['username'] ?? '—');
            $sheet->setCellValue("F{$r}", $row['nombres']  ?? '—');
            $sheet->setCellValue("G{$r}", $fh ? $fh->format('d/m/Y') : '—');
            $sheet->setCellValue("H{$r}", $fh ? $fh->format('H:i:s') : '—');
            $sheet->setCellValue("I{$r}", $row['ip']       ?? '—');
            $sheet->setCellValue("J{$r}", isset($row['nmodificacion']) && $row['nmodificacion'] !== null ? $row['nmodificacion'] : '—');
        }

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'bitacora_auditoria_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
