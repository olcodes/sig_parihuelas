<?php
class SubareasController extends Controller {
    public function index() {
    // Sesión iniciada globalmente en public/index.php
    $this->requirePrivilegio('ver_subareas');
    $model = $this->model('Subarea');
        $areaModel = $this->model('Area');
        $search = $_GET['busqueda'] ?? '';
        if (is_array($search)) {
            $search = '';
        }
        $page = max(1, (int)($_GET['pagina'] ?? 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $total = $model->countAll($search);
        $subareas = $model->getAll($search, $limit, $offset);
        $totalPaginas = ceil($total / $limit);
        $areas = $areaModel->getAll('', 1000, 0);
        $titulo = 'Mantenimiento de Subáreas';
        $this->view('subareas/index', compact('subareas', 'areas', 'search', 'page', 'totalPaginas', 'titulo'));
    }

    public function create() {
        // Sesión iniciada globalmente en public/index.php
        $areaModel = $this->model('Area');
        $areas = $areaModel->getAll('', 1000, 0);
        $errores = [];
        $data = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $subarea = trim($data['Subarea'] ?? '');
            $idArea = (int)($data['IdArea'] ?? 0);
            if ($subarea === '') $errores[] = 'El campo Subárea es obligatorio.';
            if ($idArea <= 0) $errores[] = 'Debe seleccionar un Área.';
            $model = $this->model('Subarea');
            if ($model->findBySubarea($subarea, $idArea)) $errores[] = 'Ya existe una subárea con ese nombre en el área seleccionada.';
            if (!$errores) {
                $model->insertSubarea($subarea, $idArea);
                redirect('/subareas');
            }
        }
        $titulo = 'Agregar Subárea';
        $this->view('subareas/create', compact('errores', 'data', 'areas', 'titulo'));
    }

    public function edit($id) {
        // Sesión iniciada globalmente en public/index.php
        $model = $this->model('Subarea');
        $areaModel = $this->model('Area');
        $sub = $model->find($id);
        if (!$sub) exit('No encontrado');
        $areas = $areaModel->getAll('', 1000, 0);
        $errores = [];
        $data = $sub;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $subarea = trim($_POST['Subarea'] ?? '');
            $idArea = (int)($_POST['IdArea'] ?? 0);
            if ($subarea === '') $errores[] = 'El campo Subárea es obligatorio.';
            if ($idArea <= 0) $errores[] = 'Debe seleccionar un Área.';
            $exist = $model->findBySubarea($subarea, $idArea);
            if ($exist && $exist['Id'] != $id) $errores[] = 'Ya existe una subárea con ese nombre en el área seleccionada.';
            // Verificar si la subárea tiene movimientos registrados
            if ($model->hasMovements($id)) {
                $errores[] = 'No se puede editar esta subárea porque ya tiene movimientos registrados en el sistema.';
            }
            if (!$errores) {
                $model->updateSubarea($id, $subarea, $idArea);
                redirect('/subareas');
            }
            $data = ['Id'=>$id, 'Subarea'=>$subarea, 'IdArea'=>$idArea];
        }
        $titulo = 'Editar Subárea';
        $this->view('subareas/edit', compact('errores', 'data', 'areas', 'id', 'titulo'));
    }

    public function delete($id) {
        // Sesión iniciada globalmente en public/index.php
    $model = $this->model('Subarea');
    $model->deleteSubarea($id);
    redirect('/subareas');
    }

    public function exportar() {
        // Sesión iniciada globalmente en public/index.php
        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
        $model = $this->model('Subarea');
        $subareas = $model->getAll('', 10000, 0);
        // ordenar por Id ascendente
        usort($subareas, function($a, $b) {
            return ((int)$a['Id']) <=> ((int)$b['Id']);
        });

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        // establecer título de la hoja
        try { $sheet->setTitle('Subareas'); } catch (Exception $e) { /* ignore */ }
        $headers = ['Id', 'Subárea', 'Área'];
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
        foreach ($subareas as $s) {
            $sheet->fromArray([$s['Id'], $s['Subarea'], $s['Area']], NULL, 'A'.$row);
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
        $sheet->setShowGridlines(false);
        // auto-ajustar ancho de columnas Subárea (B) y Área (C)
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="subareas_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}
