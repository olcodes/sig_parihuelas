<?php

class UsuariosController extends Controller
{
    private $userModel;
    private $roleModel;

    public function __construct()
    {
        // Solo verificar que la sesión esté iniciada
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $this->userModel = $this->model('User');
        $this->roleModel = $this->model('Role');
    }

    protected function requirePrivilegio($priv)
    {
        if (!in_array($priv, $_SESSION['privilegios'])) {
            die('<div style="padding:2rem;font-family:sans-serif;color:#b71c1c;font-weight:bold;">No tiene permiso para realizar esta acción.</div>');
        }
    }

    private function isAdmin()
    {
        // El rol se guarda en sesión, si no, consultar a la BD
        if (isset($_SESSION['user']['RoleId'])) {
            return $_SESSION['user']['RoleId'] == $this->getAdminRoleId();
        }
        // Fallback: buscar en la BD
        $user = $this->userModel->getById($_SESSION['user']['id']);
        return $user && $user['RoleId'] == $this->getAdminRoleId();
    }

    private function getAdminRoleId()
    {
        $role = $this->roleModel->getByName('Administrador');
        return $role ? $role['Id'] : 1;
    }

    public function index()
    {
        $this->requirePrivilegio('ver_usuarios');
        $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
        $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
        $porPagina = 10;
        $offset = ($pagina - 1) * $porPagina;
        $usuarios = $this->userModel->getPaginatedFiltered($porPagina, $offset, $busqueda);
        $total = $this->userModel->countFiltered($busqueda);
        $totalPaginas = ceil($total / $porPagina);
        $this->view('usuarios/index', [
            'usuarios' => $usuarios,
            'busqueda' => $busqueda,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas
        ]);

    }

    // Exportar usuarios a Excel
    public function exportar()
    {
        $this->requirePrivilegio('ver_usuarios');
        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
        $userModel = $this->model('User');
        $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
        $usuarios = $userModel->getPaginatedFiltered(10000, 0, $busqueda);
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Usuario');
        $sheet->setCellValue('C1', 'Doc. Identidad');
        $sheet->setCellValue('D1', 'Nombre y Apellidos');
        $sheet->setCellValue('E1', 'Rol');
        $sheet->getStyle('A1:E1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->freezePane('A2');
        $row = 2;
        foreach ($usuarios as $u) {
            $sheet->setCellValue('A' . $row, $u['Id']);
            $sheet->setCellValue('B' . $row, $u['username']);
            $sheet->setCellValue('C' . $row, $u['DocIdentidad']);
            $sheet->setCellValue('D' . $row, $u['NombresApellidos']);
            $sheet->setCellValue('E' . $row, $u['RolNombre'] ?? '');
            $row++;
        }
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="usuarios.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    public function create()
    {
        $this->requirePrivilegio('crear_usuarios');
        $roles = $this->roleModel->getAll();
        $this->view('usuarios/create', ['roles' => $roles]);
    }

    /**
     * Valida que la contraseña cumpla la política BASC:
     * mínimo 8 caracteres, al menos una letra, un número y un carácter especial.
     * Retorna el mensaje de error o cadena vacía si es válida.
     */
    private function validarPassword(string $password): string
    {
        if (strlen($password) < 8) {
            return 'La contraseña debe tener al menos 8 caracteres.';
        }
        if (!preg_match('/[a-zA-Z]/', $password)) {
            return 'La contraseña debe contener al menos una letra.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            return 'La contraseña debe contener al menos un número.';
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            return 'La contraseña debe contener al menos un carácter especial (ej: @, #, $, !).';
        }
        return '';
    }

    public function store()
    {
        $this->requirePrivilegio('crear_usuarios');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $rawPassword = $_POST['password'] ?? '';
            $data = [
                'username'        => trim($_POST['username'] ?? ''),
                'password'        => password_hash($rawPassword, PASSWORD_DEFAULT),
                'DocIdentidad'    => trim($_POST['DocIdentidad'] ?? ''),
                'NombresApellidos'=> trim($_POST['NombresApellidos'] ?? ''),
                'RoleId'          => intval($_POST['RoleId'] ?? 0)
            ];
            $error = $this->validarPassword($rawPassword);
            if (!$error && $data['RoleId'] <= 0) {
                $error = 'Debe seleccionar un rol válido.';
            }
            if ($error) {
                $roles = $this->roleModel->getAll();
                $this->view('usuarios/create', ['roles' => $roles, 'error' => $error, 'data' => $data]);
                return;
            }
            $this->userModel->create($data);
            redirect('/usuarios');
        }
        $this->create();
    }

    public function edit($id)
    {
        $this->requirePrivilegio('editar_usuarios');
        $usuario = $this->userModel->getById($id);
        $roles = $this->roleModel->getAll();
        
        // Cargar límites de modificación del usuario
        $limiteModel = $this->model('UsuarioLimiteModificacion');
        $limiteUsuario = $limiteModel->getByUsuarioId($id);
        
        $this->view('usuarios/edit', [
            'usuario' => $usuario,
            'roles' => $roles,
            'limiteUsuario' => $limiteUsuario
        ]);
    }

    public function update($id)
    {
        $this->requirePrivilegio('editar_usuarios');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $rawPassword = $_POST['password'] ?? '';
            $data = [
                'username'        => trim($_POST['username'] ?? ''),
                'password'        => !empty($rawPassword) ? password_hash($rawPassword, PASSWORD_DEFAULT) : $_POST['password_actual'],
                'DocIdentidad'    => trim($_POST['DocIdentidad'] ?? ''),
                'NombresApellidos'=> trim($_POST['NombresApellidos'] ?? ''),
                'RoleId'          => intval($_POST['RoleId'] ?? 0)
            ];
            $error = '';
            if (!empty($rawPassword)) {
                $error = $this->validarPassword($rawPassword);
            }
            if (!$error && $data['RoleId'] <= 0) {
                $error = 'Debe seleccionar un rol válido.';
            }
            if ($error) {
                $roles = $this->roleModel->getAll();
                $usuario = $this->userModel->getById($id);
                $usuario = array_merge($usuario, $data);
                $this->view('usuarios/edit', ['usuario' => $usuario, 'roles' => $roles, 'error' => $error]);
                return;
            }
            $this->userModel->update($id, $data);
            
            // Guardar límites de modificación
            $limiteModel = $this->model('UsuarioLimiteModificacion');
            $limiteData = [
                'UsuarioId' => $id,
                'permite_multiples' => !empty($_POST['permite_multiples']) ? 1 : 0,
                'max_modificaciones' => (isset($_POST['max_modificaciones']) && $_POST['max_modificaciones'] !== '')
                    ? (int)$_POST['max_modificaciones']
                    : null,
                'ventana_horas' => (isset($_POST['ventana_horas']) && $_POST['ventana_horas'] !== '')
                    ? (int)$_POST['ventana_horas']
                    : null
            ];
            $limiteModel->save($limiteData);
            
            redirect('/usuarios');
        }
        $this->edit($id);
    }

    public function delete($id)
    {
        $this->requirePrivilegio('eliminar_usuarios');
        $this->userModel->delete($id);
        redirect('/usuarios');
    }

    public function show($id)
    {
        $this->requirePrivilegio('ver_usuarios');
        $usuario = $this->userModel->getById($id);
        $this->view('usuarios/show', ['usuario' => $usuario]);
    }
}
