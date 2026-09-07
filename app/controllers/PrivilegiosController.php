<?php

class PrivilegiosController extends Controller
{
    private $privilegioModel;

    public function __construct()
    {
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $this->privilegioModel = $this->model('Privilegio');
    }

    public function index()
    {
        $this->requirePrivilegio('gestionar_roles');
        $busqueda = $_GET['busqueda'] ?? '';
        $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
        $porPagina = 10;
        $offset = ($pagina - 1) * $porPagina;
        $total = $this->privilegioModel->countFiltered($busqueda);
        $totalPaginas = max(1, ceil($total / $porPagina));
        $privilegios = $this->privilegioModel->getPaginatedFiltered($busqueda, $offset, $porPagina);
        $this->view('privilegios/index', [
            'privilegios' => $privilegios,
            'busqueda' => $busqueda,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas
        ]);
    }

    public function exportar()
    {
        $this->requirePrivilegio('gestionar_roles');
        $busqueda = $_GET['busqueda'] ?? '';
        $privilegios = $this->privilegioModel->getPaginatedFiltered($busqueda, 0, 10000);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="privilegios.xls"');
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Descripción</th></tr>";
        foreach ($privilegios as $p) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($p['Id']) . "</td>";
            echo "<td>" . htmlspecialchars($p['Nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($p['Descripcion']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        exit;
    }

    public function create()
    {
        $this->requirePrivilegio('gestionar_roles');
        $this->view('privilegios/create');
    }

    public function store()
    {
        $this->requirePrivilegio('gestionar_roles');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'Nombre' => trim($_POST['Nombre'] ?? ''),
                'Descripcion' => trim($_POST['Descripcion'] ?? '')
            ];
            $this->privilegioModel->create($data);
            redirect('/privilegios');
        }
        $this->create();
    }

    public function edit($id)
    {
        $this->requirePrivilegio('gestionar_roles');
        $privilegio = $this->privilegioModel->getById($id);
        $this->view('privilegios/edit', ['privilegio' => $privilegio]);
    }

    public function update($id)
    {
        $this->requirePrivilegio('gestionar_roles');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'Nombre' => trim($_POST['Nombre'] ?? ''),
                'Descripcion' => trim($_POST['Descripcion'] ?? '')
            ];
            $this->privilegioModel->update($id, $data);
            redirect('/privilegios');
        }
        $this->edit($id);
    }

    public function delete($id)
    {
        $this->requirePrivilegio('gestionar_roles');
        $this->privilegioModel->delete($id);
        redirect('/privilegios');
    }
}
