<?php

class App
{
    protected $controller = 'HomeController';
    protected $method = 'index';
    protected $params = [];

    public function __construct()
    {
        $url = $this->parseUrl();

        // Mapeo explícito para compatibilidad total
        $controllerMap = [
            'despachosinternos' => 'DespachosInternosController',
            'despachosexternos' => 'DespachosExternosController',
            'reportesexternos' => 'ReportesExternosController',
            'clientesexternos' => 'ClientesExternosController',
            'responsables' => 'ResponsablesController',
            'recepcionistas' => 'RecepcionistasController',
            'kardexparihuelas' => 'KardexParihuelasController',
            'planabastecimiento' => 'PlanAbastecimientoController',
            'avancediario' => 'AvanceDiarioController',
        ];
        // Logging temporal de ruteo
        @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "\n--- App router ---\nURL: ".print_r($url, true)."\n", FILE_APPEND);

        if (isset($url[0])) {
            $urlController = strtolower($url[0]);
            if (isset($controllerMap[$urlController])) {
                $this->controller = $controllerMap[$urlController];
            } else {
                $possibleController = ucfirst($url[0]) . 'Controller';
                $controllerPath = __DIR__ . '/../app/controllers/';
                // Compatibilidad: en Windows file_exists es case-insensitive, en Linux no.
                // Buscamos el archivo de controlador ignorando mayúsculas/minúsculas si el nombre exacto no existe.
                if (file_exists($controllerPath . $possibleController . '.php')) {
                    $this->controller = $possibleController;
                } else {
                    // Escanear carpeta para encontrar coincidencia case-insensitive
                    $files = scandir($controllerPath);
                    foreach ($files as $f) {
                        if (stripos($f, '.php') !== false) {
                            if (strtolower($f) === strtolower($possibleController . '.php')) {
                                $this->controller = basename($f, '.php');
                                break;
                            }
                        }
                    }
                }
            }
            unset($url[0]);
        }
        // Guardar el nombre del controlador actual (sin 'Controller') en global para la vista
        $controllerName = strtolower(str_replace('Controller', '', $this->controller));
        $GLOBALS['currentController'] = $controllerName;
        @file_put_contents(__DIR__.'/../../logs/depurar_guardar.txt', "Cargando controlador: $this->controller\n", FILE_APPEND);
        require_once __DIR__ . '/../app/controllers/' . $this->controller . '.php';
        $this->controller = new $this->controller;

        // Método - después del unset, los índices se reorganizan
        $url = array_values($url); // Reorganizar índices después del unset
        if (isset($url[0]) && method_exists($this->controller, $url[0])) {
            $this->method = $url[0];
            unset($url[0]);
        }
        
        // Guardar el método/acción actual en global para la vista
        $GLOBALS['currentAction'] = $this->method;

        // Parámetros
        $this->params = $url ? array_values($url) : [];

        @file_put_contents(__DIR__.'/../logs/depurar_guardar.txt', "Llamando método: $this->method, params: ".print_r($this->params, true)."\n", FILE_APPEND);
        // Llamar al método del controlador con los parámetros
        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    private function parseUrl()
    {
        if (isset($_GET['url'])) {
            // Limpiar y normalizar la URL
            $url = $_GET['url'];
            
            // Remover espacios y caracteres especiales
            $url = trim($url);
            
            // Remover barras finales
            $url = rtrim($url, '/');
            
            // Filtrar la URL
            $url = filter_var($url, FILTER_SANITIZE_URL);
            
            // Si después de limpiar queda vacía, retornar array vacío
            if (empty($url)) {
                return [];
            }
            
            // Dividir por barras y filtrar elementos vacíos
            $parts = explode('/', $url);
            $parts = array_filter($parts, function($part) {
                return !empty(trim($part));
            });
            
            return array_values($parts); // Reindexar el array
        }
        return [];
    }

    // Método opcional para cargar un controlador por defecto
    public function loadController($controller)
    {
        require_once __DIR__ . '/../app/controllers/' . $controller . '.php';
        $this->controller = new $controller;
        $this->controller->index();
    }
}