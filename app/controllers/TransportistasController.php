<?php

class TransportistasController extends Controller
{
    // Listar transportistas
    public function index()
    {
        // Sesión iniciada globalmente en public/index.php
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $this->requirePrivilegio('ver_transportistas');
        $transportistaModel = $this->model('Transportista');
        $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
        $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
        $porPagina = 10;
        $offset = ($pagina - 1) * $porPagina;
        $transportistas = $transportistaModel->getPaginatedFiltered($porPagina, $offset, $busqueda);
        $total = $transportistaModel->countFiltered($busqueda);
        $totalPaginas = ceil($total / $porPagina);
        $this->view('transportistas/index', [
            'transportistas' => $transportistas,
            'busqueda' => $busqueda,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'titulo' => 'Mantenimiento de Transportistas'
        ]);
    }

    // Crear transportista
    public function create()
    {
        // Sesión iniciada globalmente en public/index.php
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'RUC' => trim($_POST['RUC'] ?? ''),
                'Empresa' => trim($_POST['Empresa'] ?? '')
            ];
            $errores = [];
            if ($data['RUC'] === '' || !is_numeric($data['RUC'])) {
                $errores[] = 'El RUC es obligatorio y debe ser numérico.';
            }
            if ($data['Empresa'] === '') {
                $errores[] = 'El nombre de la empresa es obligatorio.';
            }
            $transportistaModel = $this->model('Transportista');
            if ($transportistaModel->existsRUC($data['RUC'])) {
                $errores[] = 'El RUC ya existe.';
            }
            if (empty($errores)) {
                $ok = $transportistaModel->createTransportista($data['RUC'], $data['Empresa']);
                if ($ok) {
                    redirect('/transportistas?msg=addok');
                } else {
                    $errores[] = 'Error al guardar el transportista.';
                }
            }
            $this->view('transportistas/create', [
                'errores' => $errores,
                'data' => $data,
                'titulo' => 'Agregar Transportista'
            ]);
        } else {
            $this->view('transportistas/create', [
                'errores' => [],
                'data' => ['RUC' => '', 'Empresa' => ''],
                'titulo' => 'Agregar Transportista'
            ]);
        }
    }

    // Editar transportista
    public function edit($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('SWLAVORO');
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $transportistaModel = $this->model('Transportista');
        $transportista = $transportistaModel->getById($id);
        if (!$transportista) {
            redirect('/transportistas');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'RUC' => trim($_POST['RUC'] ?? ''),
                'Empresa' => trim($_POST['Empresa'] ?? '')
            ];
            $errores = [];
            if ($data['RUC'] === '' || !is_numeric($data['RUC'])) {
                $errores[] = 'El RUC es obligatorio y debe ser numérico.';
            }
            if ($data['Empresa'] === '') {
                $errores[] = 'El nombre de la empresa es obligatorio.';
            }
            if ($transportistaModel->existsRUC($data['RUC'], $id)) {
                $errores[] = 'El RUC ya existe.';
            }
            // Verificar si el transportista tiene movimientos registrados
            if ($transportistaModel->hasMovements($id)) {
                $errores[] = 'No se puede editar este transportista porque ya tiene movimientos registrados en el sistema.';
            }
            if (empty($errores)) {
                $ok = $transportistaModel->updateTransportista($id, $data['RUC'], $data['Empresa']);
                if ($ok) {
                    redirect('/transportistas?msg=editok');
                } else {
                    $errores[] = 'Error al actualizar el transportista.';
                }
            }
            $this->view('transportistas/edit', [
                'errores' => $errores,
                'data' => $data,
                'Id' => $id,
                'titulo' => 'Editar Transportista'
            ]);
        } else {
            $this->view('transportistas/edit', [
                'errores' => [],
                'data' => $transportista,
                'Id' => $id,
                'titulo' => 'Editar Transportista'
            ]);
        }
    }

    // Eliminar transportista
    public function delete($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('SWLAVORO');
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $transportistaModel = $this->model('Transportista');
        $ok = $transportistaModel->deleteTransportista($id);
        if ($ok) {
            redirect('/transportistas?msg=delok');
        } else {
            redirect('/transportistas?msg=delerror');
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
        $transportistaModel = $this->model('Transportista');
        $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
        $transportistas = $transportistaModel->getPaginatedFiltered(10000, 0, $busqueda);
        // ordenar por Id ascendente
        usort($transportistas, function($a, $b) {
            return ((int)$a['Id']) <=> ((int)$b['Id']);
        });

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        try { $sheet->setTitle('Transportistas'); } catch (Exception $e) { /* ignore */ }
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'RUC');
        $sheet->setCellValue('C1', 'Empresa');
        $headers = ['ID', 'RUC', 'Empresa'];
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
        $sheet->getStyle('A1:C1')->applyFromArray($headerStyle);
        $sheet->getStyle('A1:C1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->freezePane('A2');
        $row = 2;
        foreach ($transportistas as $t) {
            $sheet->setCellValue('A' . $row, $t['Id']);
            $sheet->setCellValue('B' . $row, $t['RUC']);
            $sheet->setCellValue('C' . $row, $t['Empresa']);
            $sheet->getStyle('A'.$row.':C'.$row)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ]);
            $row++;
        }
        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->setShowGridlines(false);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="transportistas_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
