<?php
class ProductosController extends Controller {
    public function index() {
    // Sesión iniciada globalmente en public/index.php
    $this->requirePrivilegio('ver_productos');
    $model = $this->model('Producto');
        $search = $_GET['busqueda'] ?? '';
        $page = max(1, (int)($_GET['pagina'] ?? 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $total = $model->countAll($search);
        $productos = $model->getAll($search, $limit, $offset);
        $totalPaginas = ceil($total / $limit);
        $titulo = 'Mantenimiento de Productos';
        $this->view('productos/index', compact('productos', 'search', 'page', 'totalPaginas', 'titulo'));
    }

    public function create() {
        // Sesión iniciada globalmente en public/index.php
        $errores = [];
        $data = [];
        $unidadModel = $this->model('UnidadMedida');
        $unidades = $unidadModel->getAll();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $codigo = mb_strtoupper(trim($data['Codigo'] ?? ''), 'UTF-8');
            $producto = mb_strtoupper(trim($data['Producto'] ?? ''), 'UTF-8');
            $abreviatura = trim($data['Abreviatura'] ?? '');
            $unidad = trim($data['UnidadMedida'] ?? '');
            if ($codigo === '') $errores[] = 'El campo Código es obligatorio.';
            if ($producto === '') $errores[] = 'El campo Producto es obligatorio.';
            if ($unidad === '') $errores[] = 'Debe seleccionar una Unidad de Medida.';
            $model = $this->model('Producto');
            // Validación: duplicado por descripción
            if ($model->existsByProducto($producto)) {
                $errores[] = 'Ya existe un producto con la misma descripción.';
            }
            // Validación: duplicado por Código + Descripción
            if ($model->existsByCodigoAndProducto($codigo, $producto)) {
                $errores[] = 'Ya existe un producto con el mismo Código y Descripción.';
            }
            if (!$errores) {
                $model->createProducto($codigo, $producto, $unidad, $abreviatura);
                redirect('/productos');
            }
        }
        $titulo = 'Agregar Producto';
        $this->view('productos/create', compact('errores', 'data', 'titulo', 'unidades'));
    }

    public function edit($id) {
        // Sesión iniciada globalmente en public/index.php
        $model = $this->model('Producto');
        $unidadModel = $this->model('UnidadMedida');
        $unidades = $unidadModel->getAll();
        $prod = $model->find($id);
        if (!$prod) exit('No encontrado');
        $errores = [];
        $data = $prod;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $codigo = mb_strtoupper(trim($_POST['Codigo'] ?? ''), 'UTF-8');
            $producto = mb_strtoupper(trim($_POST['Producto'] ?? ''), 'UTF-8');
            $abreviatura = trim($_POST['Abreviatura'] ?? '');
            $unidad = trim($_POST['UnidadMedida'] ?? '');
            if ($codigo === '') $errores[] = 'El campo Código es obligatorio.';
            if ($producto === '') $errores[] = 'El campo Producto es obligatorio.';
            if ($unidad === '') $errores[] = 'Debe seleccionar una Unidad de Medida.';
            // Validación en edición: evitar duplicados por descripción o por código+descripcion en otros registros
            if ($model->existsByProducto($producto, $id)) {
                $errores[] = 'Ya existe otro producto con la misma descripción.';
            }
            if ($model->existsByCodigoAndProducto($codigo, $producto, $id)) {
                $errores[] = 'Ya existe otro producto con el mismo Código y Descripción.';
            }
            // Verificar si el producto tiene movimientos registrados
            if ($model->hasMovements($id)) {
                $errores[] = 'No se puede editar este producto porque ya tiene movimientos registrados en el sistema.';
            }
            if (!$errores) {
                $model->updateProducto($id, $codigo, $producto, $unidad, $abreviatura);
                redirect('/productos');
            }
            $data = ['Id'=>$id, 'Codigo'=>$codigo, 'Producto'=>$producto, 'UnidadMedida'=>$unidad, 'Abreviatura'=>$abreviatura];
        }
        $titulo = 'Editar Producto';
        $this->view('productos/edit', compact('errores', 'data', 'id', 'titulo', 'unidades'));
    }

    public function delete($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('SWLAVORO');
            session_start();
        }
    $model = $this->model('Producto');
    $model->deleteProducto($id);
    redirect('/productos');
    }

    // Endpoint JSON: obtener un producto por id o por codigo (uso AJAX)
    public function getOne() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $id = $_GET['id'] ?? null;
            $codigo = $_GET['codigo'] ?? null;
            $model = $this->model('Producto');
            if ($id) {
                $prod = $model->find((int)$id);
                if ($prod) {
                    echo json_encode(['success' => true, 'data' => $prod], JSON_UNESCAPED_UNICODE);
                    return;
                }
                echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
                return;
            }
            if ($codigo) {
                $prod = $model->findByCodigo($codigo);
                if ($prod) {
                    echo json_encode(['success' => true, 'data' => $prod], JSON_UNESCAPED_UNICODE);
                    return;
                }
                echo json_encode(['success' => false, 'message' => 'Producto no encontrado por codigo']);
                return;
            }
            echo json_encode(['success' => false, 'message' => 'Parámetros insuficientes']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    public function exportar() {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('SWLAVORO');
            session_start();
        }
        $model = $this->model('Producto');
        $productos = $model->getAll('', 10000, 0);
        // ordenar por Id ascendente
        usort($productos, function($a, $b) {
            return ((int)$a['Id']) <=> ((int)$b['Id']);
        });
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        // título de la hoja
        try { $sheet->setTitle('Productos'); } catch (Exception $e) { /* ignore */ }
        $headers = ['Id', 'Código', 'Producto'];
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
        foreach ($productos as $p) {
            $sheet->fromArray([$p['Id'], $p['Codigo'], $p['Producto']], NULL, 'A'.$row);
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
        // auto-ajustar ancho de la columna Producto (C)
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="productos_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}
