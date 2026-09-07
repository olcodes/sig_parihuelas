<?php
class AreasController extends Controller {
    public function index() {
    // Sesión iniciada globalmente en public/index.php
    $this->requirePrivilegio('ver_areas');
    $model = $this->model('Area');
    $search = $_GET['busqueda'] ?? '';
    $page = max(1, (int)($_GET['pagina'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $total = $model->countAll($search);
    $areas = $model->getAll($search, $limit, $offset);
    $totalPaginas = ceil($total / $limit);
    $titulo = 'Mantenimiento de Áreas';
    $this->view('areas/index', compact('areas', 'search', 'page', 'totalPaginas', 'titulo'));
    }

    public function create() {
        // Sesión iniciada globalmente en public/index.php
        $errores = [];
        $data = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $area = trim($data['Area'] ?? '');
            if ($area === '') $errores[] = 'El campo Área es obligatorio.';
            $model = $this->model('Area');
            if ($model->findByArea($area)) $errores[] = 'Ya existe un área con ese nombre.';
            if (!$errores) {
                $model->insertArea($area);
                redirect('/areas');
            }
        }
        $titulo = 'Agregar Área';
        $this->view('areas/create', compact('errores', 'data', 'titulo'));
    }

    public function edit($id) {
        // Sesión iniciada globalmente en public/index.php
        $model = $this->model('Area');
        $area = $model->find($id);
        if (!$area) exit('No encontrado');
        $errores = [];
        $data = $area;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['Area'] ?? '');
            if ($nombre === '') $errores[] = 'El campo Área es obligatorio.';
            $exist = $model->findByArea($nombre);
            if ($exist && $exist['Id'] != $id) $errores[] = 'Ya existe un área con ese nombre.';
            // Verificar si el área tiene movimientos registrados
            if ($model->hasMovements($id)) {
                $errores[] = 'No se puede editar esta área porque ya tiene movimientos registrados en el sistema.';
            }
            if (!$errores) {
                $model->updateArea($id, $nombre);
                redirect('/areas');
            }
            $data = ['Id'=>$id, 'Area'=>$nombre];
        }
        $titulo = 'Editar Área';
        $this->view('areas/edit', compact('errores', 'data', 'id', 'titulo'));
    }

    public function delete($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('SWLAVORO');
            session_start();
        }
        $model = $this->model('Area');
        $model->deleteArea($id);
        redirect('/areas');
    }

    public function exportar() {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('SWLAVORO');
            session_start();
        }
        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
        $model = $this->model('Area');
        $areas = $model->getAll('', 10000, 0);
        // ordenar por Id ascendente
        usort($areas, function($a, $b) {
            return ((int)$a['Id']) <=> ((int)$b['Id']);
        });

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        // establecer título de la hoja
        try { $sheet->setTitle('Area'); } catch (Exception $e) { /*ignore*/ }
        $headers = ['Id', 'Área'];
        $sheet->fromArray($headers, NULL, 'A1');
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
        foreach ($areas as $a) {
            $sheet->fromArray([$a['Id'], $a['Area']], NULL, 'A'.$row);
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
        $sheet->setShowGridlines(false);
        // auto-ajustar ancho de la columna 'Area' (columna B)
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="areas_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}
