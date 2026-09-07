<?php
/**
 * Controlador AvanceDiario - Módulo Control de Stock de Racks y Parihuelas
 */
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../config/database.php';

class AvanceDiarioController extends Controller
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
            $this->requirePrivilegio('ver_avance_diario');

            // Forzar zona horaria Perú
            date_default_timezone_set('America/Lima');
            $fechaHoy = date('Y-m-d');

            $this->view('avancediario/index', [
                'titulo' => 'Control de Stock de Racks y Parihuelas',
                'fechaHoy' => $fechaHoy
            ]);
        } catch (Exception $e) {
            error_log("Error en AvanceDiarioController::index - " . $e->getMessage());
            echo "Error al cargar la página: " . htmlspecialchars($e->getMessage());
        }
    }

    /**
     * AJAX: Listar registros por fecha
     * GET /avancediario/listar?fecha=YYYY-MM-DD
     */
    public function listar()
    {
        header('Content-Type: application/json');
        try {
            $fecha = $_GET['fecha'] ?? date('Y-m-d');

            require_once __DIR__ . '/../models/AvanceDiario.php';
            $model = new AvanceDiario();
            $registros = $model->listarPorFecha($fecha);

            // Obtener QTotalPlanificada del módulo Plan de Abastecimiento
            $qPlanificada = 0;
            try {
                require_once __DIR__ . '/../models/PlanAbastecimiento.php';
                $planModel = new PlanAbastecimiento();
                $planReg = $planModel->getByFecha($fecha);
                if ($planReg) {
                    $qPlanificada = floatval($planReg['QTotalPlanificada'] ?? 0);
                }
            } catch (Exception $e) {
                error_log("Error obteniendo plan abastecimiento: " . $e->getMessage());
            }

            // Obtener stocks del día anterior (para INICIO CORTE)
            $stockInicialCN = 0;
            $stockInicialCU = 0;
            $stockInicialFR = 0;
            $regAnteriorTotal = 0;
            try {
                $fechaAnterior = date('Y-m-d', strtotime($fecha . ' -1 day'));
                $regAnterior = $model->getUltimoRegistro($fechaAnterior);
                if ($regAnterior) {
                    $stockInicialCN = floatval($regAnterior['StockComprasNuevas'] ?? 0);
                    $stockInicialCU = floatval($regAnterior['StockComprasUsadas'] ?? 0);
                    $stockInicialFR = floatval($regAnterior['StockFlujoRegular'] ?? 0);
                    $regAnteriorTotal = floatval($regAnterior['TotalStock'] ?? 0);
                }
                error_log("[AvanceDiario] Fecha={$fecha}, FechaAnterior={$fechaAnterior}, "
                    . "StockCN(ant)={$stockInicialCN}, StockCU(ant)={$stockInicialCU}, "
                    . "StockFR(ant)={$stockInicialFR}, TotalStock(ant)={$regAnteriorTotal}");
            } catch (Exception $e) {
                error_log("Error obteniendo stock anterior: " . $e->getMessage());
            }

            // ============================================================
            // CORTE MANUAL DE STOCK INICIAL - 12/07/2026
            // Motivo: Datos incorrectos por carga masiva previa
            // Valores proporcionados por el usuario:
            //   StockComprasNuevas = 10240
            //   StockComprasUsadas = 0
            //   StockFlujoRegular  = 3829
            // ============================================================
            $fechaCorte = '2026-07-12';
            $stockCorteCN = 10240;
            $stockCorteCU = 0;
            $stockCorteFR = 3829;

            // Obtener Repliegues desde Recepciones Internas
            $replieguesData = $model->getReplieguesDesdeRecepcionesInternas($fecha);
            // Obtener Recepciones Externas desde Recepciones Externas
            $recepcionesExtData = $model->getRecepcionesExternasDesdeModulo($fecha);
            $comprasNuevasData = $model->getComprasNuevasDesdeModulos($fecha);
            $comprasUsadasData = $model->getComprasUsadasDesdeModulos($fecha);
            $eanUsadasDespInternosData = $model->getEanUsadasDespInternos($fecha);
            $eanUsadasDespExternosData = $model->getEanUsadasDespExternos($fecha);
            $eanComprasNuevasDespInternosData = $model->getEanComprasNuevasDespInternos($fecha);
            $eanComprasNuevasDespExternosData = $model->getEanComprasNuevasDespExternos($fecha);
            $eanComprasUsadasDespInternosData = $model->getEanComprasUsadasDespInternos($fecha);
            $eanComprasUsadasDespExternosData = $model->getEanComprasUsadasDespExternos($fecha);

            // Stock C. Usadas acumulado (recálculo retroactivo)
            // Suma TODOS los vales "NUEVAS ARCHIVO CENTRAL" hasta el día anterior.
            $stockCUAcumulado = $model->getStockComprasUsadasAcumulado($fecha);
            error_log("[AvanceDiario] Fecha={$fecha}, StockCUAcumulado(hasta dia-anterior)={$stockCUAcumulado}");

            foreach ($registros as &$row) {
                $row['QPlanificada'] = $qPlanificada;
                if ($row['HoraAvance'] === '07:00') {
                    $savedCN = floatval($row['StockComprasNuevas'] ?? 0);
                    $savedCU = floatval($row['StockComprasUsadas'] ?? 0);
                    $savedFR = floatval($row['StockFlujoRegular'] ?? 0);
                    $savedTotal = floatval($row['TotalStock'] ?? 0);

                    // ✅ RECÁLCULO RETROACTIVO DE STOCK C. USADAS
                    // Para fechas posteriores al corte (12/07/2026), el Stock C. Usadas
                    // de la fila 07:00 se recalcula sumando los vales "NUEVAS ARCHIVO CENTRAL"
                    // (13-jul → día anterior) vía getStockComprasUsadasAcumulado().
                    // Así, las modificaciones de cantidad en vales pasados (ej: recepciones
                    // internas de julio) se reflejan en el stock de cualquier fecha posterior,
                    // sin depender del cierre guardado del día anterior en avance_diario.
                    $stockCU07 = ($fecha > $fechaCorte) ? $stockCUAcumulado : null;

                    // CORRECCIÓN: Arrastre correcto del stock inicial
                    //
                    // Lógica de selección de stock inicial para la fila 07:00:
                    //
                    // 1. Si el día anterior tiene datos VÁLIDOS (StockComprasNuevas > 0):
                    //    → Usar el cierre real del día anterior (getUltimoRegistro)
                    //      para CN y FR. Para CU (si fecha > corte) se usa el recálculo.
                    //
                    // 2. Si el día anterior NO tiene datos válidos (CN=0, datos corruptos)
                    //    pero la fecha es >= fecha de corte:
                    //    → Usar los valores hardcodeados del corte como base (CN/FR).
                    //      CU usa el recálculo retroactivo si fecha > corte.
                    //
                    // 3. Si no hay datos del día anterior ni corte:
                    //    → Usar valores guardados (o 0 si nunca se guardó).
                    if ($stockInicialCN > 0) {
                        // El día anterior tiene datos válidos → usar su cierre real (CN/FR)
                        $row['StockComprasNuevas'] = $stockInicialCN;
                        $row['StockComprasUsadas'] = ($stockCU07 !== null) ? $stockCU07 : $stockInicialCU;
                        $row['StockFlujoRegular'] = $stockInicialFR;
                        error_log("[AvanceDiario:07:00] Fecha={$fecha}. "
                            . "Guardado: CN={$savedCN}, CU={$savedCU}, FR={$savedFR}, Total={$savedTotal}. "
                            . "Asignado (cierre anterior válido): CN={$stockInicialCN}, CU=" . $row['StockComprasUsadas'] . ", "
                            . "FR={$stockInicialFR}, TotalAnterior={$regAnteriorTotal}");
                    } elseif ($fecha >= $fechaCorte) {
                        // Día anterior con datos corruptos → usar corte como base (CN/FR)
                        $row['StockComprasNuevas'] = $stockCorteCN;
                        $row['StockComprasUsadas'] = ($stockCU07 !== null) ? $stockCU07 : $stockCorteCU;
                        $row['StockFlujoRegular'] = $stockCorteFR;
                        error_log("[AvanceDiario:07:00] Fecha={$fecha} (fallback corte). "
                            . "Guardado: CN={$savedCN}, CU={$savedCU}, FR={$savedFR}, Total={$savedTotal}. "
                            . "Asignado (corte): CN={$stockCorteCN}, CU=" . $row['StockComprasUsadas'] . ", FR={$stockCorteFR}");
                    } else {
                        // Sin datos anteriores válidos ni corte → conservar guardado
                        error_log("[AvanceDiario:07:00] Fecha={$fecha} (sin referencia). "
                            . "Guardado: CN={$savedCN}, CU={$savedCU}, FR={$savedFR}, Total={$savedTotal}. "
                            . "Sin datos válidos anteriores.");
                    }
                }
                $hora = $row['HoraAvance'] ?? '';
                if (isset($replieguesData[$hora])) $row['Repliegues'] = $replieguesData[$hora];
                if (isset($recepcionesExtData[$hora])) $row['RecepcionesExternas'] = $recepcionesExtData[$hora];
                if (isset($comprasUsadasData[$hora])) $row['ComprasUsadas'] = $comprasUsadasData[$hora];
                if (isset($comprasNuevasData[$hora])) $row['ComprasNuevas'] = $comprasNuevasData[$hora];
                if (isset($eanUsadasDespInternosData[$hora])) $row['EanUsadasDespInternos'] = $eanUsadasDespInternosData[$hora];
                if (isset($eanUsadasDespExternosData[$hora])) $row['EanUsadasDespExternos'] = $eanUsadasDespExternosData[$hora];
                if (isset($eanComprasNuevasDespInternosData[$hora])) $row['EanComprasNuevasDespInternos'] = $eanComprasNuevasDespInternosData[$hora];
                if (isset($eanComprasNuevasDespExternosData[$hora])) $row['EanComprasNuevasDespExternos'] = $eanComprasNuevasDespExternosData[$hora];
                if (isset($eanComprasUsadasDespInternosData[$hora])) $row['EanComprasUsadasDespInternos'] = $eanComprasUsadasDespInternosData[$hora];
                if (isset($eanComprasUsadasDespExternosData[$hora])) $row['EanComprasUsadasDespExternos'] = $eanComprasUsadasDespExternosData[$hora];
            }

            echo json_encode([
                'success' => true,
                'data' => $registros,
                'fecha' => $fecha,
                'q_planificada' => $qPlanificada
            ]);
        } catch (Exception $e) {
            error_log("Error en listar avance diario: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * AJAX: Guardar todas las filas de una fecha
     * POST /avancediario/guardar
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

            $fecha = $data['fecha'] ?? '';
            $filas = $data['filas'] ?? [];

            if (empty($fecha)) {
                echo json_encode(['success' => false, 'error' => 'La fecha es requerida']);
                return;
            }

            if (empty($filas) || !is_array($filas)) {
                echo json_encode(['success' => false, 'error' => 'No hay filas para guardar']);
                return;
            }

            require_once __DIR__ . '/../models/AvanceDiario.php';
            $model = new AvanceDiario();

            $resultado = $model->guardarFilas($fecha, $filas, $_SESSION['user']['id']);

            echo json_encode([
                'success' => $resultado['success'],
                'message' => $resultado['message']
            ]);
        } catch (Exception $e) {
            error_log("Error guardando avance diario: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * AJAX: Obtener detalle de un campo específico para el Popover
     * GET /avancediario/detalleCampo?fecha=YYYY-MM-DD&campo=repliegues&hora=15:00
     */
    public function detalleCampo()
    {
        header('Content-Type: application/json');
        try {
            $fecha = $_GET['fecha'] ?? '';
            $campo = $_GET['campo'] ?? '';
            $hora = $_GET['hora'] ?? null;

            if (empty($fecha) || empty($campo)) {
                echo json_encode(['success' => false, 'error' => 'Parámetros requeridos: fecha y campo']);
                return;
            }

            require_once __DIR__ . '/../models/AvanceDiario.php';
            $model = new AvanceDiario();

            // Mapear nombre de campo a método del modelo
            $mapaMetodos = [
                'repliegues' => 'getDetalleRepliegues',
                'recepciones_externas' => 'getDetalleRecepcionesExternas',
                'compras_usadas' => 'getDetalleComprasUsadas',
                'compras_nuevas' => 'getDetalleComprasNuevas',
                'ean_usadas_desp_internos' => 'getDetalleEanUsadasDespInternos',
                'ean_usadas_desp_externos' => 'getDetalleEanUsadasDespExternos',
                'ean_compras_usadas_desp_internos' => 'getDetalleEanComprasUsadasDespInternos',
                'ean_compras_usadas_desp_externos' => 'getDetalleEanComprasUsadasDespExternos',
                'ean_compras_nuevas_desp_internos' => 'getDetalleEanComprasNuevasDespInternos',
                'ean_compras_nuevas_desp_externos' => 'getDetalleEanComprasNuevasDespExternos',
                // Campos de STOCK GLOBAL
                'stock_compras_nuevas' => 'getDetalleStockComprasNuevas',
                'stock_compras_usadas' => 'getDetalleStockComprasUsadas',
                'stock_flujo_regular' => 'getDetalleStockFlujoRegular',
                'total_stock' => 'getDetalleTotalStock'
            ];

            if (!isset($mapaMetodos[$campo])) {
                echo json_encode(['success' => false, 'error' => 'Campo no válido: ' . $campo]);
                return;
            }

            $metodo = $mapaMetodos[$campo];
            $detalle = $model->$metodo($fecha, $hora);
            // Usar 'Total' si existe (para recepciones externas), sino 'Cantidad'
            $campoTotal = (!empty($detalle) && isset($detalle[0]['Total'])) ? 'Total' : 'Cantidad';
            // Total neto: ENTRADA suma, SALIDA resta
            $total = 0;
            foreach ($detalle as $row) {
                $cant = floatval($row[$campoTotal] ?? 0);
                if (isset($row['Tipo']) && strtoupper($row['Tipo']) === 'SALIDA') {
                    $total -= $cant;
                } else {
                    $total += $cant;
                }
            }

            echo json_encode([
                'success' => true,
                'data' => $detalle,
                'total' => $total,
                'campo' => $campo,
                'hora' => $hora
            ]);
        } catch (Exception $e) {
            error_log("Error en detalleCampo: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
