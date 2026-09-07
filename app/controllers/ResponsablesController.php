<?php

class ResponsablesController extends Controller
{
    // Exportar responsables a Excel
    public function exportar()
    {
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $this->requirePrivilegio('ver_responsables');
        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
        $model = $this->model('Responsable');
        $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
        $responsables = $model->getPaginatedFiltered(10000, 0, $busqueda);
        // Aseguramos orden ascendente por Id antes de exportar
        usort($responsables, function($a, $b) {
            return ((int)$a['Id']) <=> ((int)$b['Id']);
        });

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        // Nombre de la hoja y orden de columnas: ID, Nombres, Apellido Paterno, Nombres y Apellidos
        try { $sheet->setTitle('Responsables'); } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {}
        $headers = ['ID', 'Nombres', 'Apellido Paterno', 'Nombres y Apellidos'];
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
        foreach ($responsables as $r) {
            $sheet->setCellValue('A' . $row, $r['Id']);
            $sheet->setCellValue('B' . $row, $r['Nombres']);
            $sheet->setCellValue('C' . $row, $r['ApellidoPaterno']);
            // Recalcular Nombres y Apellido para asegurar orden: Nombres + ApellidoPaterno
            $full = trim(($r['Nombres'] ?? '') . ' ' . ($r['ApellidoPaterno'] ?? ''));
            $sheet->setCellValue('D' . $row, $full);
            $sheet->getStyle('A'.$row.':D'.$row)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ]);
            $row++;
        }
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->setShowGridlines(false);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="responsables_' . date('Ymd_His') . '.xlsx"');
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
        $this->requirePrivilegio('ver_responsables');
        $model = $this->model('Responsable');
        $ok = $model->deleteResponsable($id);
        if ($ok) {
            redirect('/responsables?msg=delok');
        } else {
            redirect('/responsables?msg=delerror');
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
        $this->requirePrivilegio('ver_responsables');
        $model = $this->model('Responsable');
        $r = $model->getById($id);
        if (!$r) {
            redirect('/responsables');
        }
        $this->view('responsables/edit', [
            'titulo' => 'Editar Responsable',
            'Id' => $r['Id'],
            'ApellidoPaterno' => $r['ApellidoPaterno'],
            'Nombres' => $r['Nombres'],
            'NombresApellidos' => $r['NombresApellidos'],
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
        $this->requirePrivilegio('ver_responsables');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $apellidoPaterno = trim($_POST['ApellidoPaterno'] ?? '');
            $nombres = trim($_POST['Nombres'] ?? '');
            $errores = [];
            // El campo DNI ya no es obligatorio
            if ($apellidoPaterno === '') $errores[] = 'El campo Apellido Paterno es obligatorio.';
            if ($nombres === '') $errores[] = 'El campo Nombres es obligatorio.';
            // Validar que sólo contengan letras y espacios
            $pattern = '/^[\p{L}\s]+$/u';
            if ($apellidoPaterno !== '' && !preg_match($pattern, $apellidoPaterno)) {
                $errores[] = 'El campo Apellido Paterno sólo debe contener letras y espacios.';
            }
            if ($nombres !== '' && !preg_match($pattern, $nombres)) {
                $errores[] = 'El campo Nombres sólo debe contener letras y espacios.';
            }
            // Construir Nombres y Apellidos como: Nombres + ' ' + ApellidoPaterno
            $nombresApellidos = trim($nombres . ' ' . $apellidoPaterno);
            if (count($errores) === 0) {
                $model = $this->model('Responsable');
                // Validación de duplicado por NombresApellidos
                if ($model->existsByNombresApellidos($nombresApellidos, $id)) {
                    $errores[] = 'Ya existe un responsable con el mismo Nombre y Apellido.';
                // Verificar si el responsable tiene movimientos registrados
                } elseif ($model->hasMovements($id)) {
                    $errores[] = 'No se puede editar este responsable porque ya tiene movimientos registrados en el sistema.';
                } else {
                    $ok = $model->updateResponsable($id, $apellidoPaterno, $nombres, $nombresApellidos);
                    if ($ok) {
                        redirect('/responsables?msg=editok');
                    } else {
                        $errores[] = 'Error al actualizar el responsable.';
                    }
                }
            }
            $this->view('responsables/edit', [
                'titulo' => 'Editar Responsable',
                'Id' => $id,
                'ApellidoPaterno' => $apellidoPaterno,
                'Nombres' => $nombres,
                'NombresApellidos' => $nombresApellidos,
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
        $this->requirePrivilegio('ver_responsables');
        $model = $this->model('Responsable');
        $porPagina = 10;
        $pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) && $_GET['pagina'] > 0 ? (int)$_GET['pagina'] : 1;
        $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
        $totalRegistros = $model->countFiltered($busqueda);
        $totalPaginas = max(1, ceil($totalRegistros / $porPagina));
        if ($pagina > $totalPaginas) $pagina = $totalPaginas;
        $offset = ($pagina - 1) * $porPagina;
        $responsables = $model->getPaginatedFiltered($porPagina, $offset, $busqueda);
        $this->view('responsables/index', [
            'responsables' => $responsables,
            'titulo' => 'Mantenimiento de Responsables',
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
        $this->requirePrivilegio('ver_responsables');
        $this->view('responsables/create', ['titulo' => 'Agregar Responsable']);
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
        $this->requirePrivilegio('ver_responsables');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $apellidoPaterno = trim($_POST['ApellidoPaterno'] ?? '');
            $nombres = trim($_POST['Nombres'] ?? '');
            $errores = [];
            // El campo DNI ya no es obligatorio
            if ($apellidoPaterno === '') $errores[] = 'El campo Apellido Paterno es obligatorio.';
            if ($nombres === '') $errores[] = 'El campo Nombres es obligatorio.';
            // Validar que sólo contengan letras y espacios
            $pattern = '/^[\p{L}\s]+$/u';
            if ($apellidoPaterno !== '' && !preg_match($pattern, $apellidoPaterno)) {
                $errores[] = 'El campo Apellido Paterno sólo debe contener letras y espacios.';
            }
            if ($nombres !== '' && !preg_match($pattern, $nombres)) {
                $errores[] = 'El campo Nombres sólo debe contener letras y espacios.';
            }
            // Construir Nombres y Apellidos como: Nombres + ' ' + ApellidoPaterno
            $nombresApellidos = trim($nombres . ' ' . $apellidoPaterno);
            if (count($errores) === 0) {
                $model = $this->model('Responsable');
                // Validación de duplicado por NombresApellidos
                if ($model->existsByNombresApellidos($nombresApellidos)) {
                    $errores[] = 'Ya existe un responsable con el mismo Nombre y Apellido.';
                } else {
                    $ok = $model->createResponsable($apellidoPaterno, $nombres, $nombresApellidos);
                    if ($ok) {
                        redirect('/responsables?msg=addok');
                    } else {
                        $errores[] = 'Error al agregar el responsable.';
                    }
                }
            }
            $this->view('responsables/create', [
                'titulo' => 'Agregar Responsable',
                'ApellidoPaterno' => $apellidoPaterno,
                'Nombres' => $nombres,
                'NombresApellidos' => $nombresApellidos,
                'errores' => $errores
            ]);
        }
    }
}
