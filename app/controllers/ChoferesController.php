<?php

class ChoferesController extends Controller
{

    // Exportar choferes a Excel
    public function exportar()
    {
        // Sesión iniciada globalmente en public/index.php
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
        $choferModel = $this->model('Chofer');
        $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
        $choferes = $choferModel->getPaginatedFiltered(10000, 0, $busqueda); // Exporta hasta 10,000 choferes

        // Aseguramos orden ascendente por Id antes de exportar
        usort($choferes, function($a, $b) {
            return ((int)$a['Id']) <=> ((int)$b['Id']);
        });

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        // Nombre de la hoja
        try {
            $sheet->setTitle('Choferes');
        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
            // En caso de caracteres inválidos o error, ignoramos y seguimos con el título por defecto
        }
        // Encabezados
        $headers = ['ID', 'Apellido Paterno', 'Apellido Materno', 'Nombres', 'Apellidos y Nombres', 'Brevete'];
        foreach ($headers as $i => $header) {
            $col = chr(65 + $i); // A, B, C, ...
            $sheet->setCellValue($col . '1', $header);
        }
        // Estilo de bordes para cabecera
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
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);
        $sheet->getStyle('A1:F1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->freezePane('A2');
        // Datos
        $row = 2;
        foreach ($choferes as $c) {
            $sheet->setCellValue('A' . $row, $c['Id']);
            $sheet->setCellValue('B' . $row, $c['ApellidosPaterno']);
            $sheet->setCellValue('C' . $row, $c['ApellidoMaterno']);
            $sheet->setCellValue('D' . $row, $c['Nombres']);
            $sheet->setCellValue('E' . $row, $c['ApellidosNombres']);
            $sheet->setCellValue('F' . $row, $c['Brevete']);
            // Bordes para cada fila de datos
            $sheet->getStyle('A'.$row.':F'.$row)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ]);
            $row++;
        }
        // Ajustar ancho de columnas
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        // Quitar líneas de cuadrícula
        $sheet->setShowGridlines(false);
        // Descargar archivo
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="choferes_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
    // Eliminar chofer
    public function delete($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('SWLAVORO');
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $choferModel = $this->model('Chofer');
        $ok = $choferModel->deleteChofer($id);
        if ($ok) {
            redirect('/choferes?msg=delok');
        } else {
            redirect('/choferes?msg=delerror');
        }
    }
    // Mostrar formulario de edición
    public function edit($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('SWLAVORO');
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $choferModel = $this->model('Chofer');
        $chofer = $choferModel->getById($id);
        if (!$chofer) {
            redirect('/choferes');
        }
        $this->view('choferes/edit', [
            'titulo' => 'Editar Chofer',
            'Id' => $chofer['Id'],
            'ApellidosPaterno' => $chofer['ApellidosPaterno'],
            'ApellidoMaterno' => $chofer['ApellidoMaterno'],
            'Nombres' => $chofer['Nombres'],
            'ApellidosNombres' => $chofer['ApellidosNombres'],
            'Brevete' => $chofer['Brevete'],
            'DocIdentidad' => $chofer['DocIdentidad'] ?? '',
            'Nacionalidad' => $chofer['Nacionalidad'] ?? 'PERUANO',
            'errores' => []
        ]);
    }

    // Actualizar chofer
    public function update($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('SWLAVORO');
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $apellidosPaterno = trim($_POST['ApellidosPaterno'] ?? '');
            $apellidoMaterno = trim($_POST['ApellidoMaterno'] ?? '');
            $nombres = trim($_POST['Nombres'] ?? '');
            $brevete = trim($_POST['Brevete'] ?? '');
            // DocIdentidad puede venir como DocIdentidad, o como Dni/CarnetExtranjeria según la vista
            $docIdentidad = trim($_POST['DocIdentidad'] ?? $_POST['Dni'] ?? $_POST['CarnetExtranjeria'] ?? '');
            // Nacionalidad también puede venir desde el formulario
            $nacionalidad = trim($_POST['Nacionalidad'] ?? 'PERUANO');
            $errores = [];
            if ($apellidosPaterno === '') $errores[] = 'El campo Apellido Paterno es obligatorio.';
            if ($apellidoMaterno === '') $errores[] = 'El campo Apellido Materno es obligatorio.';
            if ($nombres === '') $errores[] = 'El campo Nombres es obligatorio.';
            if ($brevete === '') $errores[] = 'El campo Brevete es obligatorio.';
            $choferModel = $this->model('Chofer');
            if ($choferModel->existsBrevete($brevete, $id)) {
                $errores[] = 'El brevete ya está registrado para otro chofer.';
            }
            
            // Verificar si el chofer tiene movimientos registrados
            if ($choferModel->hasMovements($id)) {
                $errores[] = 'No se puede editar este chofer porque ya tiene movimientos registrados en el sistema.';
            }
            
            if (count($errores) === 0) {
                    $apellidosNombres = trim($apellidosPaterno . ' ' . $apellidoMaterno . ' ' . $nombres);
                    $ok = $choferModel->updateChofer($id, $nacionalidad, $docIdentidad, $apellidosPaterno, $apellidoMaterno, $nombres, $apellidosNombres, $brevete);
                if ($ok) {
                    redirect('/choferes?msg=editok');
                } else {
                    $errores[] = 'Error al actualizar el chofer.';
                }
            }
            $apellidosNombres = trim($apellidosPaterno . ' ' . $apellidoMaterno . ' ' . $nombres);
            // Aseguramos que $id siempre se pase correctamente a la vista
            $this->view('choferes/edit', [
                'titulo' => 'Editar Chofer',
                'Id' => $id,
                'ApellidosPaterno' => $apellidosPaterno,
                'ApellidoMaterno' => $apellidoMaterno,
                'Nombres' => $nombres,
                'ApellidosNombres' => $apellidosNombres,
                'Brevete' => $brevete,
                    'DocIdentidad' => $docIdentidad,
                    'Nacionalidad' => $nacionalidad,
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
        $this->requirePrivilegio('ver_choferes');
        $choferModel = $this->model('Chofer');
        $porPagina = 10;
        $pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) && $_GET['pagina'] > 0 ? (int)$_GET['pagina'] : 1;
        $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
        $totalRegistros = $choferModel->countFiltered($busqueda);
        $totalPaginas = max(1, ceil($totalRegistros / $porPagina));
        if ($pagina > $totalPaginas) $pagina = $totalPaginas;
        $offset = ($pagina - 1) * $porPagina;
        $choferes = $choferModel->getPaginatedFiltered($porPagina, $offset, $busqueda);
        $this->view('choferes/index', [
            'choferes' => $choferes,
            'titulo' => 'Mantenimiento de Choferes',
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'busqueda' => $busqueda
        ]);
    }

    // Mostrar formulario de alta
    public function create()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('SWLAVORO');
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $this->view('choferes/create', ['titulo' => 'Agregar Chofer']);
    }

    // Guardar chofer
    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('SWLAVORO');
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $apellidosPaterno = trim($_POST['ApellidosPaterno'] ?? '');
            $apellidoMaterno = trim($_POST['ApellidoMaterno'] ?? '');
            $nombres = trim($_POST['Nombres'] ?? '');
            $brevete = trim($_POST['Brevete'] ?? '');
            // Nacionalidad y DocIdentidad
            $nacionalidad = trim($_POST['Nacionalidad'] ?? 'PERUANO');
            $docIdentidad = trim($_POST['DocIdentidad'] ?? $_POST['Dni'] ?? $_POST['CarnetExtranjeria'] ?? '');
            $errores = [];
            if ($apellidosPaterno === '') $errores[] = 'El campo Apellido Paterno es obligatorio.';
            if ($apellidoMaterno === '') $errores[] = 'El campo Apellido Materno es obligatorio.';
            if ($nombres === '') $errores[] = 'El campo Nombres es obligatorio.';
            if ($brevete === '') $errores[] = 'El campo Brevete es obligatorio.';
            $choferModel = $this->model('Chofer');
            if ($choferModel->existsBrevete($brevete)) {
                $errores[] = 'El brevete ya está registrado para otro chofer.';
            }
            if (count($errores) === 0) {
                $apellidosNombres = trim($apellidosPaterno . ' ' . $apellidoMaterno . ' ' . $nombres);
                $ok = $choferModel->createChofer($nacionalidad, $docIdentidad, $apellidosPaterno, $apellidoMaterno, $nombres, $apellidosNombres, $brevete);
                if ($ok) {
                    redirect('/choferes?msg=addok');
                } else {
                    $errores[] = 'Error al agregar el chofer.';
                }
            }
            $apellidosNombres = trim($apellidosPaterno . ' ' . $apellidoMaterno . ' ' . $nombres);
            $this->view('choferes/create', [
                'titulo' => 'Agregar Chofer',
                'ApellidosPaterno' => $apellidosPaterno,
                'ApellidoMaterno' => $apellidoMaterno,
                'Nombres' => $nombres,
                'ApellidosNombres' => $apellidosNombres,
                'Brevete' => $brevete,
                'DocIdentidad' => $docIdentidad,
                'Nacionalidad' => $nacionalidad,
                'errores' => $errores
            ]);
        }
    }

    // Endpoint AJAX: comprobar si un brevete ya existe (opcionalmente excluyendo un id)
    public function checkBrevete()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('SWLAVORO');
            session_start();
        }
        // Permitir llamadas AJAX sin redirect
        $result = ['success' => false, 'exists' => false, 'message' => ''];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $brevete = trim($_POST['brevete'] ?? '');
            $id = isset($_POST['id']) && is_numeric($_POST['id']) ? (int)$_POST['id'] : null;
            if ($brevete === '') {
                $result['message'] = 'Brevete no proporcionado';
                header('Content-Type: application/json');
                echo json_encode($result);
                return;
            }
            $choferModel = $this->model('Chofer');
            $exists = $choferModel->existsBrevete($brevete, $id);
            $result['success'] = true;
            $result['exists'] = $exists;
            if ($exists) {
                $result['message'] = 'El brevete ya está registrado para otro chofer.';
            }
        }
        header('Content-Type: application/json');
        echo json_encode($result);
        return;
    }
}
