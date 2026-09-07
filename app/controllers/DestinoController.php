<?php

class DestinoController extends Controller
{
    // Listar destinos
    public function index()
    {
        // Sesión iniciada globalmente en public/index.php
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $this->requirePrivilegio('ver_destinos');
        $destinoModel = $this->model('Destino');
        $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
        $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
        $porPagina = 10;
        $offset = ($pagina - 1) * $porPagina;
        $destinos = $destinoModel->getPaginatedFiltered($porPagina, $offset, $busqueda);
        $total = $destinoModel->countFiltered($busqueda);
        $totalPaginas = ceil($total / $porPagina);
        $this->view('destino/index', [
            'destinos' => $destinos,
            'busqueda' => $busqueda,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'titulo' => 'Mantenimiento de Destinos'
        ]);
    }

    // Crear destino
    public function create()
    {
        // Sesión iniciada globalmente en public/index.php
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'Empresa' => trim($_POST['Empresa'] ?? ''),
                'RUC' => trim($_POST['RUC'] ?? ''),
                'Direccion' => trim($_POST['Direccion'] ?? '')
            ];
            $errores = [];
            if ($data['Empresa'] === '') {
                $errores[] = 'El nombre de la empresa es obligatorio.';
            }
            if ($data['RUC'] === '' || !is_numeric($data['RUC'])) {
                $errores[] = 'El RUC es obligatorio y debe ser numérico.';
            }
            if ($data['Direccion'] === '') {
                $errores[] = 'La dirección es obligatoria.';
            }
            $destinoModel = $this->model('Destino');
            if ($destinoModel->existsRUC($data['RUC'])) {
                $errores[] = 'El RUC ya existe.';
            }
            if (empty($errores)) {
                $ok = $destinoModel->createDestino($data['Empresa'], $data['RUC'], $data['Direccion']);
                if ($ok) {
                    redirect('/destino?msg=addok');
                } else {
                    $errores[] = 'Error al guardar el destino.';
                }
            }
            $this->view('destino/create', [
                'errores' => $errores,
                'data' => $data,
                'titulo' => 'Agregar Destino'
            ]);
        } else {
            $this->view('destino/create', [
                'errores' => [],
                'data' => ['Empresa' => '', 'RUC' => '', 'Direccion' => ''],
                'titulo' => 'Agregar Destino'
            ]);
        }
    }

    // Editar destino
    public function edit($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('SWLAVORO');
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $destinoModel = $this->model('Destino');
        $destino = $destinoModel->getById($id);
        if (!$destino) {
            redirect('/destino');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'Empresa' => trim($_POST['Empresa'] ?? ''),
                'RUC' => trim($_POST['RUC'] ?? ''),
                'Direccion' => trim($_POST['Direccion'] ?? '')
            ];
            $errores = [];
            if ($data['Empresa'] === '') {
                $errores[] = 'El nombre de la empresa es obligatorio.';
            }
            if ($data['RUC'] === '' || !is_numeric($data['RUC'])) {
                $errores[] = 'El RUC es obligatorio y debe ser numérico.';
            }
            if ($data['Direccion'] === '') {
                $errores[] = 'La dirección es obligatoria.';
            }
            if ($destinoModel->existsRUC($data['RUC'], $id)) {
                $errores[] = 'El RUC ya existe.';
            }
            // Verificar si el destino tiene movimientos registrados
            if ($destinoModel->hasMovements($id)) {
                $errores[] = 'No se puede editar este destino porque ya tiene movimientos registrados en el sistema.';
            }
            if (empty($errores)) {
                $ok = $destinoModel->updateDestino($id, $data['Empresa'], $data['RUC'], $data['Direccion']);
                if ($ok) {
                    redirect('/destino?msg=editok');
                } else {
                    $errores[] = 'Error al actualizar el destino.';
                }
            }
            $this->view('destino/edit', [
                'errores' => $errores,
                'data' => $data,
                'Id' => $id,
                'titulo' => 'Editar Destino'
            ]);
        } else {
            $this->view('destino/edit', [
                'errores' => [],
                'data' => $destino,
                'Id' => $id,
                'titulo' => 'Editar Destino'
            ]);
        }
    }

    // Eliminar destino
    public function delete($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('SWLAVORO');
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $destinoModel = $this->model('Destino');
        $ok = $destinoModel->deleteDestino($id);
        if ($ok) {
            redirect('/destino?msg=delok');
        } else {
            redirect('/destino?msg=delerror');
        }
    }

    // Exportar a Excel
    public function exportar()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('SWLAVORO');
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
        $destinoModel = $this->model('Destino');
        $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
        $destinos = $destinoModel->getPaginatedFiltered(10000, 0, $busqueda);
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = ['ID', 'Empresa', 'RUC', 'Dirección'];
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
        foreach ($destinos as $d) {
            $sheet->setCellValue('A' . $row, $d['Id']);
            $sheet->setCellValue('B' . $row, $d['Empresa']);
            $sheet->setCellValue('C' . $row, $d['RUC']);
            $sheet->setCellValue('D' . $row, $d['Direccion']);
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
        header('Content-Disposition: attachment; filename="destino_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
