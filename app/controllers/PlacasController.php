<?php

class PlacasController extends Controller
{
    // Exportar placas a Excel
    public function exportar()
    {
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $this->requirePrivilegio('ver_placas');
        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
        $model = $this->model('Placa');
        $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
        $placas = $model->getPaginatedFiltered(10000, 0, $busqueda);
        // Aseguramos orden ascendente por Id
        usort($placas, function($a, $b) {
            return ((int)$a['Id']) <=> ((int)$b['Id']);
        });

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        // Nombre de la hoja
        try {
            $sheet->setTitle('Placas');
        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
            // ignorar si el título no es válido por alguna razón
        }
        $headers = ['ID', 'Placa', 'Tipo Placa', 'Constancia Inscripción'];
        foreach ($headers as $i => $header) {
            $col = chr(65 + $i);
            $sheet->setCellValue($col . '1', $header);
        }
        $headerStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFEFEFEF'],
            ],
        ];
        $sheet->getStyle('A1:D1')->applyFromArray($headerStyle);
        $sheet->getStyle('A1:D1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->freezePane('A2');
        $row = 2;
        foreach ($placas as $p) {
            $sheet->setCellValue('A' . $row, $p['Id']);
            $sheet->setCellValue('B' . $row, $p['Placa']);
            $sheet->setCellValue('C' . $row, $p['TipoPlaca'] ?? '');
            $sheet->setCellValue('D' . $row, $p['ConstanciaInscripcion']);
            $sheet->getStyle('A'.$row.':D'.$row)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ]);
            // Centrar contenido de Constancia Inscripción
            $sheet->getStyle('D'.$row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $row++;
        }
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->setShowGridlines(false);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="placas_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function delete($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('SWLAVORO');
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $this->requirePrivilegio('ver_placas');
        $model = $this->model('Placa');
        $ok = $model->delete($id);
        if ($ok) {
            redirect('/placas?msg=delok');
        } else {
            redirect('/placas?msg=delerror');
        }
    }

    public function edit($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('SWLAVORO');
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $this->requirePrivilegio('ver_placas');
        $model = $this->model('Placa');
        $p = $model->getById($id);
        if (!$p) {
            redirect('/placas');
        }
        $this->view('placas/edit', [
            'titulo' => 'Editar Placa',
            'Id' => $p['Id'],
            'Placa' => $p['Placa'],
            'TipoPlaca' => $p['TipoPlaca'] ?? '',
            'ConstanciaInscripcion' => $p['ConstanciaInscripcion'],
            'errores' => []
        ]);
    }

    public function update($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('SWLAVORO');
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $this->requirePrivilegio('ver_placas');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $placa = trim($_POST['Placa'] ?? '');
            $tipoPlaca = trim($_POST['TipoPlaca'] ?? '');
            $constanciaInscripcion = trim($_POST['ConstanciaInscripcion'] ?? '');
            $errores = [];
            if ($placa === '') $errores[] = 'El campo Placa es obligatorio.';
            if (count($errores) === 0) {
                $model = $this->model('Placa');
                // Verificar duplicados
                if ($model->exists($placa, $id)) {
                    $errores[] = 'La placa ya existe.';
                // Verificar si la placa tiene movimientos registrados
                } elseif ($model->hasMovements($id)) {
                    $errores[] = 'No se puede editar esta placa porque ya tiene movimientos registrados en el sistema.';
                } else {
                    $ok = $model->update($id, $placa, $tipoPlaca, $constanciaInscripcion);
                    if ($ok) {
                        redirect('/placas?msg=editok');
                    } else {
                        $errores[] = 'Error al actualizar la placa.';
                    }
                }
            }
            $this->view('placas/edit', [
                'titulo' => 'Editar Placa',
                'Id' => $id,
                'Placa' => $placa,
                'TipoPlaca' => $tipoPlaca,
                'ConstanciaInscripcion' => $constanciaInscripcion,
                'errores' => $errores
            ]);
            return;
        }
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('SWLAVORO');
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $this->requirePrivilegio('ver_placas');
        $model = $this->model('Placa');
        $porPagina = 10;
        $pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) && $_GET['pagina'] > 0 ? (int)$_GET['pagina'] : 1;
        $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
        $totalRegistros = $model->countFiltered($busqueda);
        $totalPaginas = max(1, ceil($totalRegistros / $porPagina));
        if ($pagina > $totalPaginas) $pagina = $totalPaginas;
        $offset = ($pagina - 1) * $porPagina;
        $placas = $model->getPaginatedFiltered($porPagina, $offset, $busqueda);
        $this->view('placas/index', [
            'placas' => $placas,
            'titulo' => 'Mantenimiento de Placas',
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'busqueda' => $busqueda
        ]);
    }

    public function create()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('SWLAVORO');
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $this->requirePrivilegio('ver_placas');
        $this->view('placas/create', ['titulo' => 'Agregar Placa']);
    }

    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('SWLAVORO');
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $this->requirePrivilegio('ver_placas');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $placa = trim($_POST['Placa'] ?? '');
            $tipoPlaca = trim($_POST['TipoPlaca'] ?? '');
            $constanciaInscripcion = trim($_POST['ConstanciaInscripcion'] ?? '');
            $errores = [];
            if ($placa === '') $errores[] = 'El campo Placa es obligatorio.';
            if (count($errores) === 0) {
                $model = $this->model('Placa');
                // Verificar duplicados
                if ($model->exists($placa, null)) {
                    $errores[] = 'La placa ya existe.';
                } else {
                    $ok = $model->create($placa, $tipoPlaca, $constanciaInscripcion);
                    if ($ok) {
                        redirect('/placas?msg=addok');
                    } else {
                        $errores[] = 'Error al agregar la placa.';
                    }
                }
            }
            $this->view('placas/create', [
                'titulo' => 'Agregar Placa',
                'Placa' => $placa,
                'TipoPlaca' => $tipoPlaca,
                'ConstanciaInscripcion' => $constanciaInscripcion,
                'errores' => $errores
            ]);
        }
    }
}
