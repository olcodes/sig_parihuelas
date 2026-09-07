<?php

class LoginController extends Controller
{
    public function index()
    {
        // Sesión iniciada globalmente en public/index.php
        // Si ya está logueado, redirigir a home
        if (isset($_SESSION['user'])) {
            redirect('/');
        }
        $timeoutMsg = isset($_GET['timeout']) && $_GET['timeout'] === '1'
            ? 'Su sesión fue cerrada automáticamente por inactividad.'
            : null;
        $this->view('login', ['esLogin' => true, 'timeoutMsg' => $timeoutMsg]);
    }

    public function auth()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $error = '';

            if (empty($username) || empty($password)) {
                $error = 'Usuario y contraseña requeridos.';
            } else {
                $userModel = $this->model('User');
                $user = $userModel->findByUsername($username);

                // echo "<pre>"; var_dump($user); echo "</pre>"; die();

                if ($user && password_verify($password, $user['password'])) {
                    // Guardar todos los datos relevantes del usuario en la sesión
                    $_SESSION['user'] = [
                        'id' => $user['Id'],
                        'username' => $user['username'],
                        'RoleId' => $user['RoleId'],
                        'NombresApellidos' => $user['NombresApellidos'],
                        'DocIdentidad' => $user['DocIdentidad']
                    ];
                    // Cargar privilegios del usuario
                    $privilegioModel = $this->model('Privilegio');
                    $privilegios = $privilegioModel->getByRoleId($user['RoleId']);
                    $_SESSION['privilegios'] = array_column($privilegios, 'Nombre');
                    
                    // Redirección robusta a la página principal
                    redirect('/');
                } else {
                    $error = 'Usuario o contraseña incorrectos.';
                }
            }
            $this->view('login', ['error' => $error, 'username' => $username, 'esLogin' => true]);
        } else {
            redirect('/login');
        }
    }

    public function logout()
    {
        // Sesión iniciada globalmente en public/index.php
        session_unset();
        session_destroy();
        // Si se llamó con ?timeout=1 (desde el JS de inactividad),
        // redirigir con el parámetro para mostrar el mensaje en el login
        $params = isset($_GET['timeout']) ? '?timeout=1' : '';
        redirect('/login' . $params);
    }
}