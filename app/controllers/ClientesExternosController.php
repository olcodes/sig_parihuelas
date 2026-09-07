<?php
class ClientesExternosController extends Controller
{
    // Listar clientes externos
    public function index()
    {
        // Sesión iniciada globalmente en public/index.php
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $this->requirePrivilegio('ver_clientes_externos');
        $clienteModel = $this->model('ClienteExterno');
        $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
        $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
        $porPagina = 10;
        $offset = ($pagina - 1) * $porPagina;
        $clientes = $clienteModel->getPaginatedFiltered($porPagina, $offset, $busqueda);
        $total = $clienteModel->countFiltered($busqueda);
        $totalPaginas = ceil($total / $porPagina);
        $this->view('clientesexternos/index', [
            'clientes' => $clientes,
            'busqueda' => $busqueda,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'titulo' => 'Mantenimiento de Clientes Externos'
        ]);
    }

    // Crear cliente externo
    public function create()
    {
        // Log de entrada inmediata
        @file_put_contents(dirname(__DIR__,2).'/logs/debug_create_entry.log', "[" . date('Y-m-d H:i:s') . "] create() called - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . " METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'unknown') . "\n", FILE_APPEND);
        // Sesión iniciada globalmente en public/index.php
        if (!isset($_SESSION['user'])) {
            @file_put_contents(dirname(__DIR__,2).'/logs/debug_create_entry.log', "[" . date('Y-m-d H:i:s') . "] No session, redirecting to login\n", FILE_APPEND);
            redirect('/login');
        }
        @file_put_contents(dirname(__DIR__,2).'/logs/debug_create_entry.log', "[" . date('Y-m-d H:i:s') . "] Session OK, user: " . ($_SESSION['user']['nombre'] ?? 'unknown') . "\n", FILE_APPEND);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            @file_put_contents(dirname(__DIR__,2).'/logs/debug_create_entry.log', "[" . date('Y-m-d H:i:s') . "] POST request detected\n", FILE_APPEND);
            // Intento de escritura de prueba en logs para verificar permisos en hosting
            try {
                $testPath = dirname(__DIR__,2) . '/logs/write_test_create.log';
                $msg = "[".date('Y-m-d H:i:s')."] create reached from " . ($_SERVER['REMOTE_ADDR'] ?? '') . "\n";
                file_put_contents($testPath, $msg, FILE_APPEND | LOCK_EX);
                error_log("ClientesExternos create test write: " . $testPath . "\n");
            } catch (Exception $e) {
                // no bloquear flujo
            }
            @file_put_contents(dirname(__DIR__,2).'/logs/debug_create_entry.log', "[" . date('Y-m-d H:i:s') . "] About to read POST data\n", FILE_APPEND);
            $data = [
                'RUC' => trim($_POST['RUC'] ?? ''),
                'Empresa' => mb_strtoupper(trim($_POST['Empresa'] ?? ''), 'UTF-8'),
                'Direccion' => mb_strtoupper(trim($_POST['Direccion'] ?? ''), 'UTF-8'),
                'Direccion2' => mb_strtoupper(trim($_POST['Direccion2'] ?? ''), 'UTF-8'),
                'Direccion3' => mb_strtoupper(trim($_POST['Direccion3'] ?? ''), 'UTF-8'),
                'Direccion4' => mb_strtoupper(trim($_POST['Direccion4'] ?? ''), 'UTF-8'),
                'Direccion5' => mb_strtoupper(trim($_POST['Direccion5'] ?? ''), 'UTF-8'),
            ];
            @file_put_contents(dirname(__DIR__,2).'/logs/debug_create_entry.log', "[" . date('Y-m-d H:i:s') . "] POST data read - RUC: " . ($data['RUC'] ?? 'empty') . " Empresa: " . (isset($data['Empresa']) ? substr($data['Empresa'], 0, 20) : 'empty') . "\n", FILE_APPEND);
            $errores = [];
            if ($data['RUC'] === '' || !is_numeric($data['RUC'])) {
                $errores[] = 'El RUC es obligatorio y debe ser numérico.';
            }
            if ($data['Empresa'] === '') {
                $errores[] = 'El nombre de la empresa es obligatorio.';
            }
            // Validación de longitud para evitar errores de DB (Data too long)
            if (mb_strlen($data['Empresa'] ?? '', 'UTF-8') > 150) {
                $errores[] = 'El nombre de la empresa es demasiado largo (máx. 150 caracteres).';
            }
            if ($data['Direccion'] === '') {
                $errores[] = 'La dirección es obligatoria.';
            }
            $clienteModel = $this->model('ClienteExterno');
            @file_put_contents(dirname(__DIR__,2).'/logs/debug_create_entry.log', "[" . date('Y-m-d H:i:s') . "] Validation complete, checking RUC existence\n", FILE_APPEND);
            if ($clienteModel->existsRUC($data['RUC'])) {
                $errores[] = 'El RUC ya existe.';
            }
            @file_put_contents(dirname(__DIR__,2).'/logs/debug_create_entry.log', "[" . date('Y-m-d H:i:s') . "] Errores count: " . count($errores) . "\n", FILE_APPEND);
            if (empty($errores)) {
                @file_put_contents(dirname(__DIR__,2).'/logs/debug_create_entry.log', "[" . date('Y-m-d H:i:s') . "] About to call createClienteExterno\n", FILE_APPEND);
                $ok = $clienteModel->createClienteExterno(
                    $data['RUC'],
                    $data['Empresa'],
                    $data['Direccion'],
                    $data['Direccion2'] ?? null,
                    $data['Direccion3'] ?? null,
                    $data['Direccion4'] ?? null,
                    $data['Direccion5'] ?? null
                );
                if ($ok) {
                    redirect('/clientesexternos?msg=addok');
                } else {
                    $errores[] = 'Error al guardar el cliente externo.';
                    // Escritura de prueba adicional si falla la creación
                    try {
                        $testPath = dirname(__DIR__,2) . '/logs/write_test_create_fail.log';
                        $msg = "[".date('Y-m-d H:i:s')."] create failed for RUC={$data['RUC']} from " . ($_SERVER['REMOTE_ADDR'] ?? '') . "\n";
                        file_put_contents($testPath, $msg, FILE_APPEND | LOCK_EX);
                    } catch (Exception $e) {}
                }
            }
            $this->view('clientesexternos/create', [
                'errores' => $errores,
                'data' => $data,
                'titulo' => 'Agregar Cliente Externo'
            ]);
        } else {
            $this->view('clientesexternos/create', [
                'errores' => [],
                'data' => ['RUC' => '', 'Empresa' => '', 'Direccion' => '', 'Direccion2' => '', 'Direccion3' => '', 'Direccion4' => '', 'Direccion5' => ''],
                'titulo' => 'Agregar Cliente Externo'
            ]);
        }
    }

    // Editar cliente externo
    public function edit($id)
    {
        try {
        // Log de entrada inmediata
        @file_put_contents(dirname(__DIR__,2).'/logs/debug_edit_entry.log', "[" . date('Y-m-d H:i:s') . "] edit() called - ID: {$id} IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . " METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'unknown') . "\n", FILE_APPEND);
        if (session_status() === PHP_SESSION_NONE) {
            session_name('SWLAVORO');
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            @file_put_contents(dirname(__DIR__,2).'/logs/debug_edit_entry.log', "[" . date('Y-m-d H:i:s') . "] No session, redirecting to login\n", FILE_APPEND);
            redirect('/login');
        }
        $clienteModel = $this->model('ClienteExterno');
        $cliente = $clienteModel->getById($id);
        if (!$cliente) {
            redirect('/clientesexternos');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'RUC' => trim($_POST['RUC'] ?? ''),
                'Empresa' => mb_strtoupper(trim($_POST['Empresa'] ?? ''), 'UTF-8'),
                'Direccion' => mb_strtoupper(trim($_POST['Direccion'] ?? ''), 'UTF-8'),
                'Direccion2' => mb_strtoupper(trim($_POST['Direccion2'] ?? ''), 'UTF-8'),
                'Direccion3' => mb_strtoupper(trim($_POST['Direccion3'] ?? ''), 'UTF-8'),
                'Direccion4' => mb_strtoupper(trim($_POST['Direccion4'] ?? ''), 'UTF-8'),
                'Direccion5' => mb_strtoupper(trim($_POST['Direccion5'] ?? ''), 'UTF-8'),
            ];
            $errores = [];
            if ($data['RUC'] === '' || !is_numeric($data['RUC'])) {
                $errores[] = 'El RUC es obligatorio y debe ser numérico.';
            }
            if ($data['Empresa'] === '') {
                $errores[] = 'El nombre de la empresa es obligatorio.';
            }
            // Validación de longitud para evitar errores de DB (Data too long)
            if (mb_strlen($data['Empresa'] ?? '', 'UTF-8') > 150) {
                $errores[] = 'El nombre de la empresa es demasiado largo (máx. 150 caracteres).';
            }
            if ($data['Direccion'] === '') {
                $errores[] = 'La dirección es obligatoria.';
            }
            @file_put_contents(dirname(__DIR__,2).'/logs/debug_edit_entry.log', "[" . date('Y-m-d H:i:s') . "] POST data read for edit - RUC: " . ($data['RUC'] ?? 'empty') . "\n", FILE_APPEND);
            if ($clienteModel->existsRUC($data['RUC'], $id)) {
                $errores[] = 'El RUC ya existe.';
            }
            // Verificar si el cliente externo tiene movimientos registrados
            if ($clienteModel->hasMovements($id)) {
                $errores[] = 'No se puede editar este cliente externo porque ya tiene movimientos registrados en el sistema.';
            }
            @file_put_contents(dirname(__DIR__,2).'/logs/debug_edit_entry.log', "[" . date('Y-m-d H:i:s') . "] Errores count: " . count($errores) . "\n", FILE_APPEND);
            if (empty($errores)) {
                @file_put_contents(dirname(__DIR__,2).'/logs/debug_edit_entry.log', "[" . date('Y-m-d H:i:s') . "] About to call updateClienteExterno\n", FILE_APPEND);
                // Debug: snapshot POST, data and session before update
                try {
                    $snap = json_encode([
                        'POST' => $_POST,
                        'data' => $data,
                        'session_user' => isset($_SESSION['user']) ? array_intersect_key($_SESSION['user'], array_flip(['Id','nombre','rol'])) : null,
                        'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? ''
                    ], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
                } catch (\Throwable $_e) {
                    $snap = '{json_error}';
                }
                @file_put_contents(dirname(__DIR__,2).'/logs/debug_edit_entry.log', "[" . date('Y-m-d H:i:s') . "] PRE_UPDATE SNAPSHOT: " . $snap . "\n", FILE_APPEND);
                $ok = $clienteModel->updateClienteExterno(
                    $id,
                    $data['RUC'],
                    $data['Empresa'],
                    $data['Direccion'],
                    $data['Direccion2'] ?? null,
                    $data['Direccion3'] ?? null,
                    $data['Direccion4'] ?? null,
                    $data['Direccion5'] ?? null
                );
                @file_put_contents(dirname(__DIR__,2).'/logs/debug_edit_entry.log', "[" . date('Y-m-d H:i:s') . "] POST_UPDATE RESULT: " . ($ok ? '1' : '0') . "\n", FILE_APPEND);
                if ($ok) {
                    redirect('/clientesexternos?msg=editok');
                } else {
                    $errores[] = 'Error al actualizar el cliente externo.';
                    // Escritura de prueba adicional si falla la actualización
                    try {
                        $testPath = dirname(__DIR__,2) . '/logs/write_test_edit_fail.log';
                        $msg = "[".date('Y-m-d H:i:s')."] update failed for Id={$id} RUC={$data['RUC']} from " . ($_SERVER['REMOTE_ADDR'] ?? '') . "\n";
                        file_put_contents($testPath, $msg, FILE_APPEND | LOCK_EX);
                    } catch (Exception $e) {}
                }
            }
            $this->view('clientesexternos/edit', [
                'errores' => $errores,
                'data' => $data,
                'Id' => $id,
                'titulo' => 'Editar Cliente Externo'
            ]);
        } else {
            // Ensure data includes new optional fields when rendering form
            $cliente['Direccion2'] = $cliente['Direccion2'] ?? '';
            $cliente['Direccion3'] = $cliente['Direccion3'] ?? '';
            $cliente['Direccion4'] = $cliente['Direccion4'] ?? '';
            $cliente['Direccion5'] = $cliente['Direccion5'] ?? '';
            $this->view('clientesexternos/edit', [
                'errores' => [],
                'data' => $cliente,
                'Id' => $id,
                'titulo' => 'Editar Cliente Externo'
            ]);
        }
        } catch (\Throwable $e) {
            $serverInfo = ['REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? '', 'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? '', 'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'] ?? ''];
            $postSnapshot = [];
            try { $postSnapshot = @json_encode($_POST); } catch (\Throwable $_) { $postSnapshot = '{json_error}'; }
            $log = "[".date('Y-m-d H:i:s')."] Uncaught exception in ClientesExternosController::edit Id={$id} - msg=" . $e->getMessage() . " errfile=" . $e->getFile() . " errline=" . $e->getLine() . " TRACE=" . $e->getTraceAsString() . " POST=" . $postSnapshot . " SERVER=" . json_encode($serverInfo) . "\n";
            @file_put_contents(dirname(__DIR__,2).'/logs/clientesexternos_error.log', $log, FILE_APPEND);
            @file_put_contents(dirname(__DIR__,2).'/logs/debug_edit_entry.log', $log, FILE_APPEND);
            @file_put_contents(dirname(__DIR__,2).'/logs/write_test_edit_fail.log', "[".date('Y-m-d H:i:s')."] exception caught for Id={$id}\n", FILE_APPEND | LOCK_EX);
            if (!headers_sent()) {
                header("HTTP/1.1 500 Internal Server Error");
            }
            echo "Error interno al procesar la solicitud.";
            exit;
        }
    }

    // Eliminar cliente externo
    public function delete($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('SWLAVORO');
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $clienteModel = $this->model('ClienteExterno');
        $ok = $clienteModel->deleteClienteExterno($id);
        if ($ok) {
            redirect('/clientesexternos?msg=delok');
        } else {
            redirect('/clientesexternos?msg=delerror');
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
        $clienteModel = $this->model('ClienteExterno');
        $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
        $clientes = $clienteModel->getPaginatedFiltered(10000, 0, $busqueda);
        // ordenar por Id ascendente
        usort($clientes, function($a, $b) {
            return ((int)$a['Id']) <=> ((int)$b['Id']);
        });

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        try { $sheet->setTitle('Clientes_Externos'); } catch (Exception $e) { /* ignore */ }
            $headers = ['ID', 'RUC', 'Empresa', 'Dirección', 'Dirección2', 'Dirección3', 'Dirección4', 'Dirección5'];
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
            $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);
        $sheet->getStyle('A1:H1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->freezePane('A2');
        $row = 2;
        foreach ($clientes as $c) {
            $sheet->setCellValue('A' . $row, $c['Id']);
            $sheet->setCellValue('B' . $row, $c['RUC']);
            $sheet->setCellValue('C' . $row, $c['Empresa']);
            $sheet->setCellValue('D' . $row, $c['Direccion']);
                $sheet->setCellValue('E' . $row, $c['Direccion2'] ?? '');
                $sheet->setCellValue('F' . $row, $c['Direccion3'] ?? '');
                $sheet->setCellValue('G' . $row, $c['Direccion4'] ?? '');
                $sheet->setCellValue('H' . $row, $c['Direccion5'] ?? '');
            $sheet->getStyle('A'.$row.':H'.$row)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ]);
            $row++;
        }
            foreach (range('A', 'H') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->setShowGridlines(false);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="clientes_externos_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // Endpoint AJAX para verificar existencia de RUC
    public function checkRuc()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $ruc = isset($input['ruc']) ? trim($input['ruc']) : '';
        $excludeId = isset($input['excludeId']) ? (int)$input['excludeId'] : null;
        $clienteModel = $this->model('ClienteExterno');
        $exists = false;
        if ($ruc !== '') {
            $exists = $clienteModel->existsRUC($ruc, $excludeId ? $excludeId : null);
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'exists' => $exists]);
        exit;
    }
}
