<?php

class RecepcionistasController extends Controller
{
    // Exportar recepcionistas a Excel
    public function exportar()
    {
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $this->requirePrivilegio('ver_recepcionistas');
        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
        $model = $this->model('Recepcionista');
        $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
        $recepcionistas = $model->getPaginatedFiltered(10000, 0, $busqueda);
        // Orden ascendente por Id
        usort($recepcionistas, function($a, $b) { return ((int)$a['Id']) <=> ((int)$b['Id']); });
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        // Nombre de la hoja y orden de columnas: ID, Nombres, Apellido Paterno, Nombres y Apellidos
        try { $sheet->setTitle('Recepcionistas'); } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {}
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
        foreach ($recepcionistas as $r) {
            $sheet->setCellValue('A' . $row, $r['Id']);
            $sheet->setCellValue('B' . $row, $r['Nombres']);
            $sheet->setCellValue('C' . $row, $r['ApellidoPaterno']);
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
        header('Content-Disposition: attachment; filename="recepcionistas_' . date('Ymd_His') . '.xlsx"');
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
        $this->requirePrivilegio('ver_recepcionistas');
        $model = $this->model('Recepcionista');
        $ok = $model->deleteRecepcionista($id);
        if ($ok) {
            redirect('/recepcionistas?msg=delok');
        } else {
            redirect('/recepcionistas?msg=delerror');
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
        $this->requirePrivilegio('ver_recepcionistas');
        $model = $this->model('Recepcionista');
        $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
        $porPagina = 20;
        $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
        $totalRegistros = $model->countFiltered($busqueda);
        $totalPaginas = max(1, ceil($totalRegistros / $porPagina));
        $offset = ($pagina - 1) * $porPagina;
        $recepcionistas = $model->getPaginatedFiltered($porPagina, $offset, $busqueda);
        $this->view('recepcionistas/index', [
            'recepcionistas' => $recepcionistas,
            'paginaActual' => $pagina,
            'totalPaginas' => $totalPaginas
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
        $this->requirePrivilegio('ver_recepcionistas');
        $this->view('recepcionistas/create');
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
        $this->requirePrivilegio('ver_recepcionistas');
        $errores = [];
        $apellidoPaterno = trim($_POST['ApellidoPaterno'] ?? '');
        $nombres = trim($_POST['Nombres'] ?? '');
        // Construir Nombres y Apellidos como: Nombres + ' ' + ApellidoPaterno
        $nombresApellidos = trim($nombres . ' ' . $apellidoPaterno);
        if ($apellidoPaterno === '') $errores[] = 'Apellido paterno requerido';
        if ($nombres === '') $errores[] = 'Nombres requeridos';
        // Validar que sólo contengan letras y espacios
        $pattern = '/^[\p{L}\s]+$/u';
        if ($apellidoPaterno !== '' && !preg_match($pattern, $apellidoPaterno)) {
            $errores[] = 'El campo Apellido Paterno sólo debe contener letras y espacios.';
        }
        if ($nombres !== '' && !preg_match($pattern, $nombres)) {
            $errores[] = 'El campo Nombres sólo debe contener letras y espacios.';
        }
        if (!$errores) {
            $model = $this->model('Recepcionista');
            // Validación de duplicado por NombresApellidos
            if ($model->existsByNombresApellidos($nombresApellidos)) {
                $errores[] = 'Ya existe una recepcionista con ese nombre y apellidos.';
            } else {
                $ok = $model->createRecepcionista($apellidoPaterno, $nombres, $nombresApellidos);
                if ($ok) {
                    redirect('/recepcionistas?msg=addok');
                } else {
                    $errores[] = 'Error al guardar en la base de datos';
                }
            }
        }
        $this->view('recepcionistas/create', compact('apellidoPaterno', 'nombres', 'nombresApellidos', 'errores'));
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
        $this->requirePrivilegio('ver_recepcionistas');
        $model = $this->model('Recepcionista');
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT * FROM recepcionistas WHERE Id = ?');
        $stmt->execute([$id]);
        $recepcionista = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$recepcionista) {
            redirect('/recepcionistas?msg=notfound');
        }
        $this->view('recepcionistas/edit', compact('recepcionista'));
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
        $this->requirePrivilegio('ver_recepcionistas');
        $errores = [];
        $apellidoPaterno = trim($_POST['ApellidoPaterno'] ?? '');
        $nombres = trim($_POST['Nombres'] ?? '');
        // Construir Nombres y Apellidos como: Nombres + ' ' + ApellidoPaterno
        $nombresApellidos = trim($nombres . ' ' . $apellidoPaterno);
        if ($apellidoPaterno === '') $errores[] = 'Apellido paterno requerido';
        if ($nombres === '') $errores[] = 'Nombres requeridos';
        // Validar que sólo contengan letras y espacios
        $pattern = '/^[\p{L}\s]+$/u';
        if ($apellidoPaterno !== '' && !preg_match($pattern, $apellidoPaterno)) {
            $errores[] = 'El campo Apellido Paterno sólo debe contener letras y espacios.';
        }
        if ($nombres !== '' && !preg_match($pattern, $nombres)) {
            $errores[] = 'El campo Nombres sólo debe contener letras y espacios.';
        }
        if (!$errores) {
            $model = $this->model('Recepcionista');
            // Validación de duplicado por NombresApellidos (excluir propio registro)
            if ($model->existsByNombresApellidos($nombresApellidos, $id)) {
                $errores[] = 'Ya existe otra recepcionista con ese nombre y apellidos.';
            // Verificar si el/la recepcionista tiene movimientos registrados
            } elseif ($model->hasMovements($id)) {
                $errores[] = 'No se puede editar este/a recepcionista porque ya tiene movimientos registrados en el sistema.';
            } else {
                $ok = $model->updateRecepcionista($id, $apellidoPaterno, $nombres, $nombresApellidos);
                if ($ok) {
                    redirect('/recepcionistas?msg=updok');
                } else {
                    $errores[] = 'Error al actualizar en la base de datos';
                }
            }
        }
        $recepcionista = [
            'Id' => $id,
            'ApellidoPaterno' => $apellidoPaterno,
            'Nombres' => $nombres,
            'NombresApellidos' => $nombresApellidos
        ];
        $this->view('recepcionistas/edit', compact('recepcionista', 'errores'));
    }
}
