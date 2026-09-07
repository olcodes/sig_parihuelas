<?php

class RolesController extends Controller
{
    private $roleModel;

    public function __construct()
    {
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $this->roleModel = $this->model('Role');
    }

    public function index()
    {
        $this->requirePrivilegio('gestionar_roles');
        $busqueda = $_GET['busqueda'] ?? '';
        $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
        $porPagina = 10;
        $offset = ($pagina - 1) * $porPagina;
        $total = $this->roleModel->countFiltered($busqueda);
        $totalPaginas = max(1, ceil($total / $porPagina));
        $roles = $this->roleModel->getPaginatedFiltered($busqueda, $offset, $porPagina);
        $this->view('roles/index', [
            'roles' => $roles,
            'busqueda' => $busqueda,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas
        ]);
    }

    public function exportar()
    {
        $this->requirePrivilegio('gestionar_roles');
        $busqueda = $_GET['busqueda'] ?? '';
        $roles = $this->roleModel->getPaginatedFiltered($busqueda, 0, 10000);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="roles.xls"');
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Descripción</th></tr>";
        foreach ($roles as $r) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($r['Id']) . "</td>";
            echo "<td>" . htmlspecialchars($r['Nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($r['Descripcion']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        exit;
    }

    public function create()
    {
        $this->requirePrivilegio('gestionar_roles');
        $privilegioModel = $this->model('Privilegio');
        $privilegios = $privilegioModel->getAll();
        $this->view('roles/create', ['privilegios' => $privilegios]);
    }

    public function store()
    {
        $this->requirePrivilegio('gestionar_roles');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'Nombre' => trim($_POST['Nombre'] ?? ''),
                'Descripcion' => trim($_POST['Descripcion'] ?? '')
            ];
            $privilegios = isset($_POST['privilegios']) ? $_POST['privilegios'] : [];
            $roleId = $this->roleModel->create($data);
            if (!$roleId) {
                $roleId = $this->roleModel->getByName($data['Nombre'])['Id'] ?? null;
            }
            if ($roleId) {
                $rolePrivilegioModel = $this->model('RolePrivilegio');
                $rolePrivilegioModel->setPrivilegiosForRole($roleId, $privilegios);
            }
            redirect('/roles');
        }
        $this->create();
    }

    public function edit($id)
    {
        $this->requirePrivilegio('gestionar_roles');
        $rol = $this->roleModel->getById($id);
        $privilegioModel = $this->model('Privilegio');
        $rolePrivilegioModel = $this->model('RolePrivilegio');
        $privilegios = $privilegioModel->getAll();
        $privilegiosRol = $rolePrivilegioModel->getPrivilegiosByRoleId($id);
        $this->view('roles/edit', [
            'rol' => $rol,
            'privilegios' => $privilegios,
            'privilegiosRol' => $privilegiosRol
        ]);
    }

    public function update($id)
    {
        $this->requirePrivilegio('gestionar_roles');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'Nombre' => trim($_POST['Nombre'] ?? ''),
                'Descripcion' => trim($_POST['Descripcion'] ?? '')
            ];
            $privilegios = isset($_POST['privilegios']) ? $_POST['privilegios'] : [];
            $this->roleModel->update($id, $data);
            $rolePrivilegioModel = $this->model('RolePrivilegio');
            $rolePrivilegioModel->setPrivilegiosForRole($id, $privilegios);
            redirect('/roles');
        }
        $this->edit($id);
    }

    public function delete($id)
    {
        $this->requirePrivilegio('gestionar_roles');
        $this->roleModel->delete($id);
        redirect('/roles');
    }
}
