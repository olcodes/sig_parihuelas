<?php
class ObservacionesController extends Controller {
    public function index() {
    // Sesión iniciada globalmente en public/index.php
    $this->requirePrivilegio('ver_observaciones');
    $model = $this->model('Observacion');
        $search = $_GET['search'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $total = $model->countAll($search);
        $observaciones = $model->getAll($search, $limit, $offset);
        $pages = ceil($total / $limit);
    $titulo = 'Mantenimiento de Observaciones';
    $this->view('observaciones/index', compact('observaciones', 'search', 'page', 'pages', 'total', 'titulo'));
    }

    public function create() {
        // Sesión iniciada globalmente en public/index.php
        $errores = [];
        $data = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $item = trim($data['Item'] ?? '');
            $obs = trim($data['Observaciones'] ?? '');
            if ($item === '') $errores[] = 'El campo Item es obligatorio.';
            if ($obs === '') $errores[] = 'El campo Observaciones es obligatorio.';
            $model = $this->model('Observacion');
            if ($model->findByItem($item)) $errores[] = 'Ya existe un registro con ese Item.';
            if (!$errores) {
                $model->createObservacion($item, $obs);
                redirect('/observaciones');
            }
        }
    $titulo = 'Agregar Observación';
    $this->view('observaciones/create', compact('errores', 'data', 'titulo'));
    }

    public function edit($id) {
        // Sesión iniciada globalmente en public/index.php
        $model = $this->model('Observacion');
        $obs = $model->find($id);
        if (!$obs) exit('No encontrado');
        $errores = [];
        $data = $obs;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $item = trim($_POST['Item'] ?? '');
            $observaciones = trim($_POST['Observaciones'] ?? '');
            if ($item === '') $errores[] = 'El campo Item es obligatorio.';
            if ($observaciones === '') $errores[] = 'El campo Observaciones es obligatorio.';
            $exist = $model->findByItem($item);
            if ($exist && $exist['Id'] != $id) $errores[] = 'Ya existe un registro con ese Item.';
            if (!$errores) {
                $model->updateObservacion($id, $item, $observaciones);
                redirect('/observaciones');
            }
            $data = ['Id'=>$id, 'Item'=>$item, 'Observaciones'=>$observaciones];
        }
    $titulo = 'Editar Observación';
    $this->view('observaciones/edit', compact('errores', 'data', 'id', 'titulo'));
    }

    public function delete($id) {
    $model = $this->model('Observacion');
    $model->deleteObservacion($id);
    redirect('/observaciones');
    }

    public function exportar() {
        $model = $this->model('Observacion');
        $observaciones = $model->getAll('', 10000, 0);
        // ordenar por Id ascendente
        usort($observaciones, function($a, $b) {
            return ((int)$a['Id']) <=> ((int)$b['Id']);
        });
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        try { $sheet->setTitle('Observaciones'); } catch (Exception $e) { /* ignore */ }
        $headers = ['Id', 'Item', 'Observaciones'];
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
        foreach ($observaciones as $o) {
            $sheet->fromArray([$o['Id'], $o['Item'], $o['Observaciones']], NULL, 'A'.$row);
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
        // auto-ajustar ancho columna Observaciones (C)
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="observaciones_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}
