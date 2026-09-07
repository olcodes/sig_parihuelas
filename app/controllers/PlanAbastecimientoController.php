<?php
/**
 * Controlador PlanAbastecimiento - Módulo Plan de Abastecimiento
 */
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../config/database.php';

class PlanAbastecimientoController extends Controller
{
    /**
     * Cargar vista principal
     */
    public function index()
    {
        try {
            if (!isset($_SESSION['user'])) {
                redirect('/login');
                return;
            }
            $this->requirePrivilegio('ver_plan_abastecimiento');

            $fechaHoy = date('Y-m-d');

            $this->view('planabastecimiento/index', [
                'titulo' => 'Plan de Abastecimiento',
                'fechaHoy' => $fechaHoy
            ]);
        } catch (Exception $e) {
            error_log("Error en PlanAbastecimientoController::index - " . $e->getMessage());
            echo "Error al cargar la página: " . htmlspecialchars($e->getMessage());
        }
    }

    /**
     * AJAX: Listar registros, opcionalmente filtrados por mes y año
     * GET /planabastecimiento/listar?mes=6&anio=2026
     */
    public function listar()
    {
        header('Content-Type: application/json');
        try {
            $mes = $_GET['mes'] ?? null;
            $anio = $_GET['anio'] ?? null;

            require_once __DIR__ . '/../models/PlanAbastecimiento.php';
            $model = new PlanAbastecimiento();
            $registros = $model->listarTodos($mes, $anio);

            echo json_encode([
                'success' => true,
                'data' => $registros,
                'mes' => $mes,
                'anio' => $anio
            ]);
        } catch (Exception $e) {
            error_log("Error en listar plan abastecimiento: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * AJAX: Guardar (insertar o actualizar) registro
     * POST /planabastecimiento/guardar
     */
    public function guardar()
    {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'error' => 'Método no permitido']);
                return;
            }

            if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
                echo json_encode(['success' => false, 'error' => 'Sesión expirada']);
                return;
            }

            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (stripos($contentType, 'application/json') !== false) {
                $data = json_decode(file_get_contents('php://input'), true);
            } else {
                $data = $_POST;
            }

            $id = $data['id'] ?? null;
            $data['creado_por'] = $_SESSION['user']['id'];
            $data['modificado_por'] = $_SESSION['user']['id'];

            // Validar campos requeridos
            if (empty($data['fecha'])) {
                echo json_encode(['success' => false, 'error' => 'La fecha es requerida']);
                return;
            }

            require_once __DIR__ . '/../models/PlanAbastecimiento.php';
            $model = new PlanAbastecimiento();

            // Validar que no exista duplicado de fecha
            if ($model->existePorFecha($data['fecha'], $id)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Ya existe un registro con la fecha ' . htmlspecialchars($data['fecha']) . '. No se permiten fechas duplicadas.'
                ]);
                return;
            }

            if ($id) {
                // Verificar límite de modificaciones antes de actualizar
                $validacion = $model->puedeModificar($id);
                if (!$validacion['puede']) {
                    echo json_encode([
                        'success' => false,
                        'error' => $validacion['mensaje'],
                        'limite_alcanzado' => true
                    ]);
                    return;
                }

                // Actualizar existente
                $model->actualizar($id, $data);
                // Registrar modificación en la bitácora
                $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                $model->registrarModificacion($id, $_SESSION['user']['id'], $ip);

                echo json_encode([
                    'success' => true,
                    'id' => $id,
                    'message' => 'Registro actualizado exitosamente'
                ]);
            } else {
                // Insertar nuevo
                $nuevoId = $model->insertar($data);
                echo json_encode([
                    'success' => true,
                    'id' => $nuevoId,
                    'message' => 'Registro guardado exitosamente'
                ]);
            }
        } catch (Exception $e) {
            error_log("Error guardando plan abastecimiento: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * AJAX: Obtener registro por ID
     * GET /planabastecimiento/obtener?id=N
     */
    public function obtener()
    {
        header('Content-Type: application/json');
        try {
            $id = $_GET['id'] ?? 0;
            if (empty($id)) {
                echo json_encode(['success' => false, 'error' => 'ID requerido']);
                return;
            }

            require_once __DIR__ . '/../models/PlanAbastecimiento.php';
            $model = new PlanAbastecimiento();
            $registro = $model->getById($id);

            if ($registro) {
                echo json_encode(['success' => true, 'data' => $registro]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Registro no encontrado']);
            }
        } catch (Exception $e) {
            error_log("Error obteniendo plan abastecimiento: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * AJAX: Verificar estado de modificaciones de un registro
     * GET /planabastecimiento/verificarModificaciones?id=N
     */
    public function verificarModificaciones()
    {
        header('Content-Type: application/json');
        try {
            $id = $_GET['id'] ?? 0;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID requerido']);
                return;
            }

            require_once __DIR__ . '/../models/PlanAbastecimiento.php';
            $model = new PlanAbastecimiento();
            $validacion = $model->puedeModificar($id);

            echo json_encode([
                'success' => true,
                'puede_modificar' => $validacion['puede'],
                'modificaciones' => $validacion['modificaciones'],
                'restantes' => $validacion['restantes'],
                'mensaje' => $validacion['mensaje']
            ]);
        } catch (Exception $e) {
            error_log("Error en verificarModificaciones: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
