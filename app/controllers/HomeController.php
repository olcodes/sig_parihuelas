<?php

class HomeController extends Controller
{
    public function index()
    {
        // Sesión iniciada globalmente en public/index.php
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
        $data = [
            'titulo' => 'Bienvenido a Lavoro-ERP',
            'mensaje' => 'Gestión de información'
        ];
        $this->view('home', $data);
    }
}