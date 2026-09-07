<?php

class Controller
{
    // Verificar privilegio
    protected function requirePrivilegio($priv)
    {
        if (!isset($_SESSION['privilegios']) || !in_array($priv, $_SESSION['privilegios'])) {
            die('<div style="padding:2rem;font-family:sans-serif;color:#b71c1c;font-weight:bold;max-width:500px;margin:3rem auto;border-radius:1rem;background:#fff3f3;box-shadow:0 2px 12px #0001;">
                <div style="font-size:1.2rem;margin-bottom:1.5rem;">No tiene permiso para realizar esta acción.</div>
                <button onclick="window.history.back()" style="background:#b71c1c;color:#fff;border:none;padding:0.6rem 1.5rem;border-radius:0.5rem;font-size:1rem;cursor:pointer;">
                    ⬅️ Regresar
                </button>
            </div>');
        }
    }
    // Cargar un modelo
    public function model($model)
    {
        $ruta = __DIR__ . '/../app/models/' . $model . '.php';
        @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "LLAMANDO model: $model, ruta: $ruta\n", FILE_APPEND);
        if (!file_exists($ruta)) {
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "NO EXISTE EL ARCHIVO DEL MODELO: $ruta\n", FILE_APPEND);
            return null;
        }
        require_once $ruta;
        @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "INSTANCIANDO CLASE MODELO: $model\n", FILE_APPEND);
        return new $model();
    }

    // Cargar una vista con layout
    public function view($view, $data = [])
    {
        $viewFile = __DIR__ . '/../app/views/' . $view . '.php';
        // LOG temporal para depuración de vistas
        @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "\n--- view() ---\nIntentando cargar vista: $viewFile\nDatos: ".print_r($data, true)."\n", FILE_APPEND);
        if (file_exists($viewFile)) {
            extract($data);
            ob_start();
            require $viewFile;
            $contenido = ob_get_clean();
            require __DIR__ . '/../app/views/layouts/main.php';
        } else {
            @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "NO SE ENCONTRÓ LA VISTA: $viewFile\n", FILE_APPEND);
            echo "La vista $view no existe.";
        }
    }
}