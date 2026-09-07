<?php

class OrigenController extends Controller
{
    // Listar origenes
    public function index()
    {
        // Sesión iniciada globalmente en public/index.php
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $this->requirePrivilegio('ver_origenes');
        $origenModel = $this->model('Origen');
        $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
        $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
        $porPagina = 10;
        $offset = ($pagina - 1) * $porPagina;
        $origenes = $origenModel->getPaginatedFiltered($porPagina, $offset, $busqueda);
        $total = $origenModel->countFiltered($busqueda);
        $totalPaginas = ceil($total / $porPagina);
        $this->view('origen/index', [
            'origenes' => $origenes,
            'busqueda' => $busqueda,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'titulo' => 'Mantenimiento de Origenes'
        ]);
    }

    // Crear origen
    public function create()
    {
        // Sesión iniciada globalmente en public/index.php
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'Origen' => trim($_POST['Origen'] ?? '')
            ];
            $errores = [];
            if ($data['Origen'] === '') {
                $errores[] = 'El nombre de origen es obligatorio.';
            }
            $origenModel = $this->model('Origen');
            if ($origenModel->existsOrigen($data['Origen'])) {
                $errores[] = 'El origen ya existe.';
            }
            if (empty($errores)) {
                $ok = $origenModel->createOrigen($data['Origen']);
                if ($ok) {
                    redirect('/origen?msg=addok');
                } else {
                    $errores[] = 'Error al guardar el origen.';
                }
            }
            $this->view('origen/create', [
                'errores' => $errores,
                'data' => $data,
                'titulo' => 'Agregar Origen'
            ]);
        } else {
            $this->view('origen/create', [
                'errores' => [],
                'data' => ['Origen' => ''],
                'titulo' => 'Agregar Origen'
            ]);
        }
    }

    // Editar origen
    public function edit($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('SWLAVORO');
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $origenModel = $this->model('Origen');
        $origen = $origenModel->getById($id);
        if (!$origen) {
            redirect('/origen');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'Origen' => trim($_POST['Origen'] ?? '')
            ];
            $errores = [];
            if ($data['Origen'] === '') {
                $errores[] = 'El nombre de origen es obligatorio.';
            }
            if ($origenModel->existsOrigen($data['Origen'], $id)) {
                $errores[] = 'El origen ya existe.';
            }
            // Verificar si el origen tiene movimientos registrados
            if ($origenModel->hasMovements($id)) {
                $errores[] = 'No se puede editar este origen porque ya tiene movimientos registrados en el sistema.';
            }
            if (empty($errores)) {
                $ok = $origenModel->updateOrigen($id, $data['Origen']);
                if ($ok) {
                    redirect('/origen?msg=editok');
                } else {
                    $errores[] = 'Error al actualizar el origen.';
                }
            }
            $this->view('origen/edit', [
                'errores' => $errores,
                'data' => $data,
                'Id' => $id,
                'titulo' => 'Editar Origen'
            ]);
        } else {
            $this->view('origen/edit', [
                'errores' => [],
                'data' => $origen,
                'Id' => $id,
                'titulo' => 'Editar Origen'
            ]);
        }
    }

    // Eliminar origen
    public function delete($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('SWLAVORO');
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $origenModel = $this->model('Origen');
        $ok = $origenModel->deleteOrigen($id);
        if ($ok) {
            redirect('/origen?msg=delok');
        } else {
            redirect('/origen?msg=delerror');
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
        $origenModel = $this->model('Origen');
        $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
        $origenes = $origenModel->getPaginatedFiltered(10000, 0, $busqueda);
        // ordenar por Id ascendente
        usort($origenes, function($a, $b) {
            return ((int)$a['Id']) <=> ((int)$b['Id']);
        });

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        try { $sheet->setTitle('Origen'); } catch (Exception $e) { /* ignore */ }
        $headers = ['ID', 'Origen'];
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
        $sheet->getStyle('A1:B1')->applyFromArray($headerStyle);
        $sheet->getStyle('A1:B1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->freezePane('A2');
        $row = 2;
        foreach ($origenes as $o) {
            $sheet->setCellValue('A' . $row, $o['Id']);
            $sheet->setCellValue('B' . $row, $o['Origen']);
            $sheet->getStyle('A'.$row.':B'.$row)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ]);
            $row++;
        }
        foreach (range('A', 'B') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->setShowGridlines(false);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="origen_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
