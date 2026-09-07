<?php
class SeriesController extends Controller {
    public function index() {
    // Sesión iniciada globalmente en public/index.php
    $this->requirePrivilegio('ver_areas');
    $model = $this->model('Serie');
    $search = $_GET['busqueda'] ?? '';
    $page = max(1, (int)($_GET['pagina'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $total = $model->countAll($search);
    $series = $model->getAll($search, $limit, $offset);
    $totalPaginas = ceil($total / $limit);
    $titulo = 'Mantenimiento de Series';
    $this->view('series/index', compact('series', 'search', 'page', 'totalPaginas', 'titulo'));
    }

    public function create() {
        // Sesión iniciada globalmente en public/index.php
        $errores = [];
        $data = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $serie = trim($data['Serie'] ?? '');
            $centroDistribucion = trim($data['CentroDistribucion'] ?? '');
            if ($serie === '') $errores[] = 'El campo Serie es obligatorio.';
            if (strlen($serie) !== 4) $errores[] = 'La serie debe tener exactamente 4 caracteres.';
            if ($centroDistribucion === '') $errores[] = 'El campo Centro de Distribución es obligatorio.';
            $model = $this->model('Serie');
            if ($model->findBySerie($serie)) $errores[] = 'Ya existe una serie con ese nombre.';
            if (!$errores) {
                $model->insertSerie($serie, $centroDistribucion);
                redirect('/series');
            }
        }
        $titulo = 'Agregar Serie';
        $this->view('series/create', compact('errores', 'data', 'titulo'));
    }

    public function edit($id) {
        // Sesión iniciada globalmente en public/index.php
        $model = $this->model('Serie');
        $serie = $model->find($id);
        if (!$serie) exit('No encontrado');
        $errores = [];
        $data = $serie;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['Serie'] ?? '');
            $centroDistribucion = trim($_POST['CentroDistribucion'] ?? '');
            if ($nombre === '') $errores[] = 'El campo Serie es obligatorio.';
            if (strlen($nombre) !== 4) $errores[] = 'La serie debe tener exactamente 4 caracteres.';
            if ($centroDistribucion === '') $errores[] = 'El campo Centro de Distribución es obligatorio.';
            $exist = $model->findBySerie($nombre);
            if ($exist && $exist['Id'] != $id) $errores[] = 'Ya existe una serie con ese nombre.';
            // Verificar si la serie tiene movimientos registrados
            if ($model->hasMovements($id)) {
                $errores[] = 'No se puede editar esta serie porque ya tiene movimientos registrados en el sistema.';
            }
            if (!$errores) {
                $model->updateSerie($id, $nombre, $centroDistribucion);
                redirect('/series');
            }
            $data = ['Id'=>$id, 'Serie'=>$nombre, 'CentroDistribucion'=>$centroDistribucion];
        }
        $titulo = 'Editar Serie';
        $this->view('series/edit', compact('errores', 'data', 'id', 'titulo'));
    }

    public function delete($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('SWLAVORO');
            session_start();
        }
        $model = $this->model('Serie');
        $model->deleteSerie($id);
        redirect('/series');
    }

    public function exportar() {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('SWLAVORO');
            session_start();
        }
        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
        $model = $this->model('Serie');
        $series = $model->getAll('', 10000, 0);
        // ordenar por Id ascendente
        usort($series, function($a, $b) {
            return ((int)$a['Id']) <=> ((int)$b['Id']);
        });
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        try { $sheet->setTitle('Series'); } catch (Exception $e) { /* ignore */ }
        $headers = ['Id', 'Serie', 'Centro de Distribución'];
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
        $sheet->getStyle('A1:C1')->applyFromArray($headerStyle);
        $sheet->getStyle('A1:C1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->freezePane('A2');
        $row = 2;
        foreach ($series as $s) {
            $sheet->fromArray([$s['Id'], $s['Serie'], $s['CentroDistribucion']], NULL, 'A'.$row);
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
        // Desactivar líneas de cuadricula para exportaciones (consistente con otras tablas)
        $sheet->setShowGridlines(false);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="series_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
