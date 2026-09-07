<?php
/**
 * Modelo AvanceDiario - Control de Stock de Racks y Parihuelas
 */
require_once __DIR__ . '/../../core/Model.php';

class AvanceDiario extends Model
{
    protected $table = 'avance_diario';
    protected $primaryKey = 'Id';

    public static function getHoras()
    {
        return [
            '07:00', '09:00', '11:00', '13:00', '15:00', '17:00', '19:00',
            '21:00', '23:00', '01:00', '03:00', '05:00', '06:30'
        ];
    }

    public function listarPorFecha($fecha)
    {
        $sql = "SELECT * FROM avance_diario WHERE Fecha = ? AND estado = 'activo' ORDER BY FIELD(HoraAvance, 
                '07:00','09:00','11:00','13:00','15:00','17:00','19:00',
                '21:00','23:00','01:00','03:00','05:00','06:30')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$fecha]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $mapaHoras = [];
        foreach ($rows as $r) {
            $mapaHoras[$r['HoraAvance']] = $r;
        }

        $horas = self::getHoras();
        $resultado = [];
        $item = 1;
        foreach ($horas as $hora) {
            if (isset($mapaHoras[$hora])) {
                $fila = $mapaHoras[$hora];
                $fila['_item'] = $item;
                $resultado[] = $fila;
            } else {
                $resultado[] = [
                    'Id' => null, 'Fecha' => $fecha, 'HoraAvance' => $hora, 'Turno' => '',
                    'Repliegues' => 0, 'RecepcionesExternas' => 0, 'ComprasUsadas' => 0,
                    'ComprasNuevas' => 0, 'TotalRecepcion' => 0,
                    'EanUsadasDespInternos' => 0, 'EanUsadasDespExternos' => 0,
                    'EanComprasUsadasDespInternos' => 0, 'EanComprasUsadasDespExternos' => 0,
                    'EanComprasNuevasDespInternos' => 0, 'EanComprasNuevasDespExternos' => 0,
                    'TotalDespacho' => 0, 'QPlanificada' => 0, 'PorcentajeCumplimiento' => 0,
                    'StockComprasNuevas' => 0, 'StockComprasUsadas' => 0, 'StockFlujoRegular' => 0,
                    'TotalStock' => 0, 'estado' => 'activo', 'creado_por' => null, 'creado_en' => null,
                    'modificado_por' => null, 'modificado_en' => null, 'modificaciones_count' => 0, '_item' => $item
                ];
            }
            $item++;
        }
        return $resultado;
    }

    public function getById($id)
    {
        $sql = "SELECT * FROM avance_diario WHERE Id = ? AND estado = 'activo' LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function existePorFechaHora($fecha, $horaAvance, $excluirId = null)
    {
        $sql = "SELECT Id FROM avance_diario WHERE Fecha = ? AND HoraAvance = ? AND estado = 'activo'";
        $params = [$fecha, $horaAvance];
        if ($excluirId) { $sql .= " AND Id != ?"; $params[] = $excluirId; }
        $sql .= " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    public function eliminarPorFecha($fecha)
    {
        $sql = "UPDATE avance_diario SET estado = 'inactivo' WHERE Fecha = ? AND estado = 'activo'";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$fecha]);
    }

    public function insertar($data)
    {
        $sql = "INSERT INTO avance_diario (
            Fecha, HoraAvance, Turno, Repliegues, RecepcionesExternas,
            ComprasUsadas, ComprasNuevas, TotalRecepcion,
            EanUsadasDespInternos, EanUsadasDespExternos,
            EanComprasUsadasDespInternos, EanComprasUsadasDespExternos,
            EanComprasNuevasDespInternos, EanComprasNuevasDespExternos,
            TotalDespacho, QPlanificada, PorcentajeCumplimiento,
            StockComprasNuevas, StockComprasUsadas, StockFlujoRegular, TotalStock, creado_por
        ) VALUES (
            :fecha, :hora_avance, :turno,
            :repliegues, :recepciones_externas,
            :compras_usadas, :compras_nuevas, :total_recepcion,
            :ean_usadas_desp_internos, :ean_usadas_desp_externos,
            :ean_compras_usadas_desp_internos, :ean_compras_usadas_desp_externos,
            :ean_compras_nuevas_desp_internos, :ean_compras_nuevas_desp_externos,
            :total_despacho, :q_planificada, :porcentaje_cumplimiento,
            :stock_compras_nuevas, :stock_compras_usadas, :stock_flujo_regular, :total_stock, :creado_por
        )";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':fecha' => $data['fecha'], ':hora_avance' => $data['hora_avance'], ':turno' => $data['turno'] ?? '',
            ':repliegues' => $data['repliegues'] ?? 0, ':recepciones_externas' => $data['recepciones_externas'] ?? 0,
            ':compras_usadas' => $data['compras_usadas'] ?? 0, ':compras_nuevas' => $data['compras_nuevas'] ?? 0,
            ':total_recepcion' => $data['total_recepcion'] ?? 0,
            ':ean_usadas_desp_internos' => $data['ean_usadas_desp_internos'] ?? 0,
            ':ean_usadas_desp_externos' => $data['ean_usadas_desp_externos'] ?? 0,
            ':ean_compras_usadas_desp_internos' => $data['ean_compras_usadas_desp_internos'] ?? 0,
            ':ean_compras_usadas_desp_externos' => $data['ean_compras_usadas_desp_externos'] ?? 0,
            ':ean_compras_nuevas_desp_internos' => $data['ean_compras_nuevas_desp_internos'] ?? 0,
            ':ean_compras_nuevas_desp_externos' => $data['ean_compras_nuevas_desp_externos'] ?? 0,
            ':total_despacho' => $data['total_despacho'] ?? 0,
            ':q_planificada' => $data['q_planificada'] ?? 0, ':porcentaje_cumplimiento' => $data['porcentaje_cumplimiento'] ?? 0,
            ':stock_compras_nuevas' => $data['stock_compras_nuevas'] ?? 0,
            ':stock_compras_usadas' => $data['stock_compras_usadas'] ?? 0,
            ':stock_flujo_regular' => $data['stock_flujo_regular'] ?? 0,
            ':total_stock' => $data['total_stock'] ?? 0, ':creado_por' => $data['creado_por'] ?? null
        ]);
        return $this->db->lastInsertId();
    }

    public function actualizar($id, $data)
    {
        $sql = "UPDATE avance_diario SET
            Turno=:turno, Repliegues=:repliegues, RecepcionesExternas=:recepciones_externas,
            ComprasUsadas=:compras_usadas, ComprasNuevas=:compras_nuevas, TotalRecepcion=:total_recepcion,
            EanUsadasDespInternos=:ean_usadas_desp_internos, EanUsadasDespExternos=:ean_usadas_desp_externos,
            EanComprasUsadasDespInternos=:ean_compras_usadas_desp_internos, EanComprasUsadasDespExternos=:ean_compras_usadas_desp_externos,
            EanComprasNuevasDespInternos=:ean_compras_nuevas_desp_internos, EanComprasNuevasDespExternos=:ean_compras_nuevas_desp_externos,
            TotalDespacho=:total_despacho, QPlanificada=:q_planificada, PorcentajeCumplimiento=:porcentaje_cumplimiento,
            StockComprasNuevas=:stock_compras_nuevas, StockComprasUsadas=:stock_compras_usadas,
            StockFlujoRegular=:stock_flujo_regular, TotalStock=:total_stock,
            modificado_por=:modificado_por, modificado_en=NOW(), modificaciones_count=modificaciones_count+1
            WHERE Id=:id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':turno' => $data['turno'] ?? '',
            ':repliegues' => $data['repliegues'] ?? 0, ':recepciones_externas' => $data['recepciones_externas'] ?? 0,
            ':compras_usadas' => $data['compras_usadas'] ?? 0, ':compras_nuevas' => $data['compras_nuevas'] ?? 0,
            ':total_recepcion' => $data['total_recepcion'] ?? 0,
            ':ean_usadas_desp_internos' => $data['ean_usadas_desp_internos'] ?? 0,
            ':ean_usadas_desp_externos' => $data['ean_usadas_desp_externos'] ?? 0,
            ':ean_compras_usadas_desp_internos' => $data['ean_compras_usadas_desp_internos'] ?? 0,
            ':ean_compras_usadas_desp_externos' => $data['ean_compras_usadas_desp_externos'] ?? 0,
            ':ean_compras_nuevas_desp_internos' => $data['ean_compras_nuevas_desp_internos'] ?? 0,
            ':ean_compras_nuevas_desp_externos' => $data['ean_compras_nuevas_desp_externos'] ?? 0,
            ':total_despacho' => $data['total_despacho'] ?? 0,
            ':q_planificada' => $data['q_planificada'] ?? 0, ':porcentaje_cumplimiento' => $data['porcentaje_cumplimiento'] ?? 0,
            ':stock_compras_nuevas' => $data['stock_compras_nuevas'] ?? 0,
            ':stock_compras_usadas' => $data['stock_compras_usadas'] ?? 0,
            ':stock_flujo_regular' => $data['stock_flujo_regular'] ?? 0,
            ':total_stock' => $data['total_stock'] ?? 0,
            ':modificado_por' => $data['modificado_por'] ?? null, ':id' => $id
        ]);
    }

    public function guardarFilas($fecha, $filas, $usuarioId)
    {
        try {
            $this->db->beginTransaction();
            $this->eliminarPorFecha($fecha);
            foreach ($filas as $fila) {
                $fila['fecha'] = $fecha;
                $fila['creado_por'] = $usuarioId;
                $this->insertar($fila);
            }
            $this->db->commit();
            return ['success' => true, 'message' => 'Registros guardados exitosamente'];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error guardando avance diario: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al guardar: ' . $e->getMessage()];
        }
    }

    public function getUltimoRegistro($fecha)
    {
        $sql = "SELECT * FROM avance_diario WHERE Fecha = ? AND estado = 'activo' 
                ORDER BY FIELD(HoraAvance, 
                '07:00','09:00','11:00','13:00','15:00','17:00','19:00',
                '21:00','23:00','01:00','03:00','05:00','06:30') DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$fecha]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // --- Helper para asignar hora más cercana hacia adelante ---
    private function acumularPorHoraAdelante(&$resultado, $horasGrilla, $rows)
    {
        // Ordenar horas cronológicamente para asignación correcta
        $horasOrd = $horasGrilla;
        usort($horasOrd, function($a, $b) {
            $ap = explode(':', $a);
            $bp = explode(':', $b);
            return (intval($ap[0]) * 60 + intval($ap[1] ?? 0))
                 - (intval($bp[0]) * 60 + intval($bp[1] ?? 0));
        });

        foreach ($rows as $row) {
            $hora = $row['Hora'] ?? '00:00:00';
            $total = floatval($row['Total'] ?? 0);
            // Turno: 1=MAÑANA, 2=TARDE, 3=NOCHE (numérico en BD)
            $turno = isset($row['Turno']) ? intval($row['Turno']) : 0;
            $hp = explode(':', $hora);
            $horaMin = intval($hp[0]) * 60 + intval($hp[1] ?? 0);

            // Items MAÑANA (Turno=1) antes de 09:00 (540 min) se redirigen a 09:00
            // Items NOCHE/TARDE van a su hora cronológica normal
            if ($turno === 1 && $horaMin < 540) {
                $resultado['09:00'] += $total;
                continue;
            }

            $destino = null;
            foreach ($horasOrd as $hG) {
                if ($hG === '07:00') continue; // INICIO CORTE se salta
                $hgp = explode(':', $hG);
                if ($horaMin <= intval($hgp[0]) * 60 + intval($hgp[1] ?? 0)) {
                    $destino = $hG; break;
                }
            }
            $resultado[$destino ?? '06:30'] += $total;
        }
    }

    // --- Helper para agrupar por cierre de turno ---
    private function acumularPorCierreTurno(&$resultado, $rows)
    {
        foreach ($rows as $row) {
            $hora = $row['Hora'] ?? '00:00:00';
            $total = floatval($row['Total'] ?? 0);
            $hp = explode(':', $hora);
            $min = intval($hp[0]) * 60 + intval($hp[1] ?? 0);
            if ($min > 390 && $min <= 900) $resultado['15:00'] += $total;
            elseif ($min > 900 && $min <= 1380) $resultado['23:00'] += $total;
            elseif ($min > 1380 || $min <= 390) $resultado['06:30'] += $total;
        }
    }

    // ============ REPLIEGUES (desde Recepciones Internas) ============
    public function getReplieguesDesdeRecepcionesInternas($fecha)
    {
        $resultado = ['15:00' => 0, '23:00' => 0, '06:30' => 0];
        try {
            $sql = "SELECT ri.Hora, SUM(rip.Cantidad) as Total
                    FROM recepciones_internas ri
                    INNER JOIN recepciones_internas_productos rip ON ri.Id = rip.DespachoId
                    WHERE ri.Fecha = ? AND rip.CodigoProducto = '19003730'
                    AND rip.DescripcionProducto NOT LIKE '%COMPRAS GLORIA%'
                    AND (rip.DescripcionProducto NOT LIKE '%NUEVAS ARCHIVO CENTRAL%' OR rip.DescripcionProducto IS NULL)
                    GROUP BY ri.Hora";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fecha]);
            $this->acumularPorCierreTurno($resultado, $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            error_log("Error getRepliegues: " . $e->getMessage());
        }
        return $resultado;
    }

    // ============ RECEPCIONES EXTERNAS (EAN Usadas, desde Recepciones Externas) ============
    public function getRecepcionesExternasDesdeModulo($fecha)
    {
        $horasGrilla = ['07:00','09:00','11:00','13:00','15:00','17:00','19:00',
                        '21:00','23:00','01:00','03:00','05:00','06:30'];
        $resultado = array_fill_keys($horasGrilla, 0);
        try {
            $sql = "SELECT re.Hora, re.Turno, COALESCE(SUM(rep.Total), 0) as Total
                    FROM recepciones_externas re
                    INNER JOIN recepciones_externas_guias reg ON re.Id = reg.RecepcionExternaId
                    INNER JOIN recepciones_externas_productos rep ON reg.Id = rep.GuiaId
                    WHERE re.Fecha = ? AND rep.CodigoProducto = '19003730'
                    AND (rep.DescripcionProducto NOT LIKE '%COMPRAS GLORIA%' OR rep.DescripcionProducto IS NULL)
                    AND (rep.DescripcionProducto NOT LIKE '%NUEVAS ARCHIVO CENTRAL%' OR rep.DescripcionProducto IS NULL)
                    GROUP BY re.Hora, re.Turno";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fecha]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->acumularPorHoraAdelante($resultado, $horasGrilla, $rows);
        } catch (Exception $e) {
            error_log("Error getRecepcionesExt: " . $e->getMessage());
        }
        return $resultado;
    }

    // ============ COMPRAS NUEVAS (desde Recepciones Internas + Externas, solo COMPRAS GLORIA) ============
    public function getComprasNuevasDesdeModulos($fecha)
    {
        $horasGrilla = ['07:00','09:00','11:00','13:00','15:00','17:00','19:00',
                        '21:00','23:00','01:00','03:00','05:00','06:30'];
        $resultado = array_fill_keys($horasGrilla, 0);
        try {
            $sqlRI = "SELECT ri.Hora, SUM(rip.Cantidad) as Total
                      FROM recepciones_internas ri
                      INNER JOIN recepciones_internas_productos rip ON ri.Id = rip.DespachoId
                      WHERE ri.Fecha = ? AND rip.CodigoProducto = '19003730'
                      AND rip.DescripcionProducto LIKE '%COMPRAS GLORIA%'
                      AND rip.DescripcionProducto NOT LIKE '%NUEVAS ARCHIVO CENTRAL%'
                      GROUP BY ri.Hora";
            $stmt = $this->db->prepare($sqlRI);
            $stmt->execute([$fecha]);
            $this->acumularPorHoraAdelante($resultado, $horasGrilla, $stmt->fetchAll(PDO::FETCH_ASSOC));

            $sqlRE = "SELECT re.Hora, SUM(rep.Total) as Total
                      FROM recepciones_externas re
                      INNER JOIN recepciones_externas_guias reg ON re.Id = reg.RecepcionExternaId
                      INNER JOIN recepciones_externas_productos rep ON reg.Id = rep.GuiaId
                      WHERE re.Fecha = ? AND rep.CodigoProducto = '19003730'
                      AND rep.DescripcionProducto LIKE '%COMPRAS GLORIA%'
                      AND rep.DescripcionProducto NOT LIKE '%NUEVAS ARCHIVO CENTRAL%'
                      GROUP BY re.Hora";
            $stmt = $this->db->prepare($sqlRE);
            $stmt->execute([$fecha]);
            $this->acumularPorHoraAdelante($resultado, $horasGrilla, $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            error_log("Error getComprasNuevas: " . $e->getMessage());
        }
        return $resultado;
    }

    // ============ COMPRAS USADAS (desde Recepciones Internas + Externas, solo NUEVAS ARCHIVO CENTRAL) ============
    public function getComprasUsadasDesdeModulos($fecha)
    {
        $horasGrilla = ['07:00','09:00','11:00','13:00','15:00','17:00','19:00',
                        '21:00','23:00','01:00','03:00','05:00','06:30'];
        $resultado = array_fill_keys($horasGrilla, 0);
        try {
            $sqlRI = "SELECT ri.Hora, SUM(rip.Cantidad) as Total
                      FROM recepciones_internas ri
                      INNER JOIN recepciones_internas_productos rip ON ri.Id = rip.DespachoId
                      WHERE ri.Fecha = ? AND rip.CodigoProducto = '19003730'
                      AND rip.DescripcionProducto LIKE '%NUEVAS ARCHIVO CENTRAL%'
                      GROUP BY ri.Hora";
            $stmt = $this->db->prepare($sqlRI);
            $stmt->execute([$fecha]);
            $this->acumularPorHoraAdelante($resultado, $horasGrilla, $stmt->fetchAll(PDO::FETCH_ASSOC));

            $sqlRE = "SELECT re.Hora, SUM(rep.Total) as Total
                      FROM recepciones_externas re
                      INNER JOIN recepciones_externas_guias reg ON re.Id = reg.RecepcionExternaId
                      INNER JOIN recepciones_externas_productos rep ON reg.Id = rep.GuiaId
                      WHERE re.Fecha = ? AND rep.CodigoProducto = '19003730'
                      AND rep.DescripcionProducto LIKE '%NUEVAS ARCHIVO CENTRAL%'
                      GROUP BY re.Hora";
            $stmt = $this->db->prepare($sqlRE);
            $stmt->execute([$fecha]);
            $this->acumularPorHoraAdelante($resultado, $horasGrilla, $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            error_log("Error getComprasUsadas: " . $e->getMessage());
        }
        return $resultado;
    }

    // ============ STOCK C. USADAS ACUMULADO (recálculo retroactivo) ============
    /**
     * Suma las recepciones (internas + externas) del producto 19003730
     * con descripción "NUEVAS ARCHIVO CENTRAL" hasta el día anterior a $fecha.
     *
     * Sin límite inferior de fecha: incluye TODOS los vales del producto, incluso
     * los anteriores al corte del 12/07/2026 (ej: vale 3639 del 09/07/2026), para
     * que el stock refleje la totalidad recibida. Así, las modificaciones de
     * cantidad en vales pasados se reflejan en el stock de cualquier fecha
     * posterior, sin depender del cierre guardado del día anterior en
     * avance_diario.
     *
     * NOTA: Se restan los despachos (internos + externos) de NUEVAS ARCHIVO
     * CENTRAL hasta el día anterior, para que el stock inicial refleje tanto
     * las recepciones como las salidas acumuladas del producto.
     */
    public function getStockComprasUsadasAcumulado($fecha)
    {
        $fechaFin = date('Y-m-d', strtotime($fecha . ' -1 day'));
        $total = 0;
        try {
            // Recepciones internas: todos los vales NUEVAS ARCHIVO CENTRAL hasta el día anterior
            $sqlRI = "SELECT COALESCE(SUM(rip.Cantidad), 0) as Total
                      FROM recepciones_internas ri
                      INNER JOIN recepciones_internas_productos rip ON ri.Id = rip.DespachoId
                      WHERE ri.Fecha <= ?
                      AND rip.CodigoProducto = '19003730'
                      AND rip.DescripcionProducto LIKE '%NUEVAS ARCHIVO CENTRAL%'";
            $stmt = $this->db->prepare($sqlRI);
            $stmt->execute([$fechaFin]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $total += floatval($row['Total'] ?? 0);

            // Recepciones externas
            $sqlRE = "SELECT COALESCE(SUM(rep.Total), 0) as Total
                      FROM recepciones_externas re
                      INNER JOIN recepciones_externas_guias reg ON re.Id = reg.RecepcionExternaId
                      INNER JOIN recepciones_externas_productos rep ON reg.Id = rep.GuiaId
                      WHERE re.Fecha <= ?
                      AND rep.CodigoProducto = '19003730'
                      AND rep.DescripcionProducto LIKE '%NUEVAS ARCHIVO CENTRAL%'";
            $stmt = $this->db->prepare($sqlRE);
            $stmt->execute([$fechaFin]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $total += floatval($row['Total'] ?? 0);

            // Despachos internos: restar vales NUEVAS ARCHIVO CENTRAL hasta el día anterior
            $sqlDInt = "SELECT COALESCE(SUM(dip.Cantidad), 0) as Total
                        FROM despachos_internos di
                        INNER JOIN despachos_internos_productos dip ON di.Id = dip.DespachoId
                        WHERE di.Fecha <= ?
                        AND dip.CodigoProducto = '19003730'
                        AND dip.DescripcionProducto LIKE '%NUEVAS ARCHIVO CENTRAL%'
                        AND di.estado = 'activo'";
            $stmt = $this->db->prepare($sqlDInt);
            $stmt->execute([$fechaFin]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $total -= floatval($row['Total'] ?? 0);

            // Despachos externos: restar vales NUEVAS ARCHIVO CENTRAL hasta el día anterior
            $sqlDExt = "SELECT COALESCE(SUM(dep.Cantidad), 0) as Total
                        FROM despachos_externos de
                        INNER JOIN despachos_externos_productos dep ON de.Id = dep.DespachoId
                        WHERE de.Fecha <= ?
                        AND dep.CodigoProducto = '19003730'
                        AND dep.DescripcionProducto LIKE '%NUEVAS ARCHIVO CENTRAL%'
                        AND de.estado = 'activo'";
            $stmt = $this->db->prepare($sqlDExt);
            $stmt->execute([$fechaFin]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $total -= floatval($row['Total'] ?? 0);
        } catch (Exception $e) {
            error_log("Error getStockComprasUsadasAcumulado: " . $e->getMessage());
        }
        return $total;
    }

    // ============ EAN USADAS DESPACHOS INTERNOS ============
    public function getEanUsadasDespInternos($fecha)
    {
        $resultado = ['15:00' => 0, '23:00' => 0, '06:30' => 0];
        try {
            $sql = "SELECT di.Hora, SUM(dip.Cantidad) as Total
                    FROM despachos_internos di
                    INNER JOIN despachos_internos_productos dip ON di.Id = dip.DespachoId
                    WHERE di.Fecha = ? AND dip.CodigoProducto = '19003730'
                    AND (dip.DescripcionProducto NOT LIKE '%COMPRAS GLORIA%' OR dip.DescripcionProducto IS NULL)
                    AND (dip.DescripcionProducto NOT LIKE '%NUEVAS ARCHIVO CENTRAL%' OR dip.DescripcionProducto IS NULL)
                    AND di.estado = 'activo'
                    GROUP BY di.Hora";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fecha]);
            $this->acumularPorCierreTurno($resultado, $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            error_log("Error getEanUsadasDespInt: " . $e->getMessage());
        }
        return $resultado;
    }

    // ============ EAN USADAS DESPACHOS EXTERNOS ============
    public function getEanUsadasDespExternos($fecha)
    {
        $horasGrilla = ['07:00','09:00','11:00','13:00','15:00','17:00','19:00',
                        '21:00','23:00','01:00','03:00','05:00','06:30'];
        $resultado = array_fill_keys($horasGrilla, 0);
        try {
            $sql = "SELECT de.Hora, SUM(dep.Cantidad) as Total
                    FROM despachos_externos de
                    INNER JOIN despachos_externos_productos dep ON de.Id = dep.DespachoId
                    WHERE de.Fecha = ? AND dep.CodigoProducto = '19003730'
                    AND (dep.DescripcionProducto NOT LIKE '%COMPRAS GLORIA%' OR dep.DescripcionProducto IS NULL)
                    AND (dep.DescripcionProducto NOT LIKE '%NUEVAS ARCHIVO CENTRAL%' OR dep.DescripcionProducto IS NULL)
                    AND de.estado = 'activo'
                    GROUP BY de.Hora";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fecha]);
            $this->acumularPorHoraAdelante($resultado, $horasGrilla, $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            error_log("Error getEanUsadasDespExt: " . $e->getMessage());
        }
        return $resultado;
    }

    // ============ EAN COMPRAS NUEVAS DESPACHOS INTERNOS ============
    public function getEanComprasNuevasDespInternos($fecha)
    {
        $resultado = ['15:00' => 0, '23:00' => 0, '06:30' => 0];
        try {
            $sql = "SELECT di.Hora, SUM(dip.Cantidad) as Total
                    FROM despachos_internos di
                    INNER JOIN despachos_internos_productos dip ON di.Id = dip.DespachoId
                    WHERE di.Fecha = ? AND dip.CodigoProducto = '19003730'
                    AND dip.DescripcionProducto LIKE '%COMPRAS GLORIA%'
                    AND (dip.DescripcionProducto NOT LIKE '%NUEVAS ARCHIVO CENTRAL%' OR dip.DescripcionProducto IS NULL)
                    AND di.estado = 'activo'
                    GROUP BY di.Hora";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fecha]);
            $this->acumularPorCierreTurno($resultado, $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            error_log("Error getEanCompNueDespInt: " . $e->getMessage());
        }
        return $resultado;
    }

    // ============ EAN COMPRAS NUEVAS DESPACHOS EXTERNOS ============
    public function getEanComprasNuevasDespExternos($fecha)
    {
        $horasGrilla = ['07:00','09:00','11:00','13:00','15:00','17:00','19:00',
                        '21:00','23:00','01:00','03:00','05:00','06:30'];
        $resultado = array_fill_keys($horasGrilla, 0);
        try {
            $sql = "SELECT de.Hora, SUM(dep.Cantidad) as Total
                    FROM despachos_externos de
                    INNER JOIN despachos_externos_productos dep ON de.Id = dep.DespachoId
                    WHERE de.Fecha = ? AND dep.CodigoProducto = '19003730'
                    AND dep.DescripcionProducto LIKE '%COMPRAS GLORIA%'
                    AND (dep.DescripcionProducto NOT LIKE '%NUEVAS ARCHIVO CENTRAL%' OR dep.DescripcionProducto IS NULL)
                    AND de.estado = 'activo'
                    GROUP BY de.Hora";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fecha]);
            $this->acumularPorHoraAdelante($resultado, $horasGrilla, $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            error_log("Error getEanCompNueDespExt: " . $e->getMessage());
        }
        return $resultado;
    }

    // ============ EAN COMPRAS USADAS DESPACHOS INTERNOS (NUEVAS ARCHIVO CENTRAL) ============
    public function getEanComprasUsadasDespInternos($fecha)
    {
        $resultado = ['15:00' => 0, '23:00' => 0, '06:30' => 0];
        try {
            $sql = "SELECT di.Hora, SUM(dip.Cantidad) as Total
                    FROM despachos_internos di
                    INNER JOIN despachos_internos_productos dip ON di.Id = dip.DespachoId
                    WHERE di.Fecha = ? AND dip.CodigoProducto = '19003730'
                    AND dip.DescripcionProducto LIKE '%NUEVAS ARCHIVO CENTRAL%'
                    AND di.estado = 'activo'
                    GROUP BY di.Hora";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fecha]);
            $this->acumularPorCierreTurno($resultado, $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            error_log("Error getEanCompUsaDespInt: " . $e->getMessage());
        }
        return $resultado;
    }

    // ============ EAN COMPRAS USADAS DESPACHOS EXTERNOS (NUEVAS ARCHIVO CENTRAL) ============
    public function getEanComprasUsadasDespExternos($fecha)
    {
        $horasGrilla = ['07:00','09:00','11:00','13:00','15:00','17:00','19:00',
                        '21:00','23:00','01:00','03:00','05:00','06:30'];
        $resultado = array_fill_keys($horasGrilla, 0);
        try {
            $sql = "SELECT de.Hora, SUM(dep.Cantidad) as Total
                    FROM despachos_externos de
                    INNER JOIN despachos_externos_productos dep ON de.Id = dep.DespachoId
                    WHERE de.Fecha = ? AND dep.CodigoProducto = '19003730'
                    AND dep.DescripcionProducto LIKE '%NUEVAS ARCHIVO CENTRAL%'
                    AND de.estado = 'activo'
                    GROUP BY de.Hora";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fecha]);
            $this->acumularPorHoraAdelante($resultado, $horasGrilla, $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            error_log("Error getEanCompUsaDespExt: " . $e->getMessage());
        }
        return $resultado;
    }

    // ============ LÍMITE DE MODIFICACIONES ============
    protected function obtenerConfigUsuarioModificacion($usuarioId)
    {
        $sql = "SELECT max_modificaciones, ventana_horas, permite_multiples 
                FROM usuarios_limites_modificacion 
                WHERE usuario_id = ? AND modulo = 'avance_diario' LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$usuarioId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function puedeModificar($id)
    {
        try {
            $sql = "SELECT COALESCE(modificaciones_count, 0) as modificaciones, creado_en FROM avance_diario WHERE Id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$resultado) {
                return ['puede' => false, 'modificaciones' => 0, 'restantes' => 0, 'mensaje' => 'Registro no encontrado'];
            }
            $modificaciones = (int)$resultado['modificaciones'];
            $creadoEn = $resultado['creado_en'] ?? null;
            $usuarioId = $_SESSION['user']['id'] ?? null;
            $config = $this->obtenerConfigUsuarioModificacion($usuarioId);

            if ($config && !empty($config['permite_multiples'])) {
                return ['puede' => true, 'modificaciones' => $modificaciones, 'restantes' => 999, 'mensaje' => 'Permiso especial'];
            }

            $maxModificaciones = ($config && $config['max_modificaciones'] !== null) ? (int)$config['max_modificaciones'] : 1;
            $ventanaHoras = ($config && $config['ventana_horas'] !== null) ? (int)$config['ventana_horas'] : null;

            if ($ventanaHoras !== null && $creadoEn) {
                $fechaLimite = date('Y-m-d H:i:s', strtotime($creadoEn . " + {$ventanaHoras} hours"));
                if (strtotime('now') > strtotime($fechaLimite)) {
                    return ['puede' => false, 'modificaciones' => $modificaciones, 'restantes' => 0, 'mensaje' => "Ventana de {$ventanaHoras}h expirada."];
                }
            }

            $puede = $modificaciones < $maxModificaciones;
            $restantes = max(0, $maxModificaciones - $modificaciones);
            return [
                'puede' => $puede, 'modificaciones' => $modificaciones, 'restantes' => $restantes,
                'mensaje' => $puede ? "Modificaciones: {$modificaciones}, restantes: {$restantes}" : "Límite de {$maxModificaciones} modificaciones alcanzado."
            ];
        } catch (Exception $e) {
            error_log('Error en AvanceDiario::puedeModificar: ' . $e->getMessage());
            return ['puede' => false, 'modificaciones' => 0, 'restantes' => 0, 'mensaje' => 'Error al verificar modificaciones'];
        }
    }

    public function registrarModificacion($planId, $usuarioId, $ip = null)
    {
        $sql = "SELECT COALESCE(modificaciones_count, 0) as cnt FROM avance_diario WHERE Id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$planId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $n = $row ? (int)$row['cnt'] : 0;
        $sql2 = "INSERT INTO avance_diario_modificaciones (AvanceDiarioId, UsuarioId, NModificacion, IpAddress) VALUES (?, ?, ?, ?)";
        $stmt2 = $this->db->prepare($sql2);
        return $stmt2->execute([$planId, $usuarioId, $n, $ip]);
    }

    // ================================================================
    // MÉTODOS DE DETALLE PARA POPOVER
    // ================================================================

    /**
     * Helper: filtrar registros por hora según el tipo de acumulación
     * @param array $rows Registros con campo 'Hora'
     * @param string|null $horaAvance Hora de la grilla (07:00, 09:00, ... 15:00, 23:00, 06:30)
     * @param string $tipo 'cierre' (3 buckets) o 'adelante' (13 buckets)
     * @return array Registros filtrados
     */
    private function filtrarPorHoraGrilla($rows, $horaAvance, $tipo = 'adelante')
    {
        if ($horaAvance === null || empty($rows)) {
            return $rows;
        }

        $horasGrilla = ['07:00','09:00','11:00','13:00','15:00','17:00','19:00',
                        '21:00','23:00','01:00','03:00','05:00','06:30'];

        if ($tipo === 'acumulado') {
            // Para el desglose de Stock Global: acumula los vales del día
            // hasta la hora de la fila, de modo que el total coincida con la celda.
            // INICIO CORTE (07:00): solo stock inicial, sin vales del día.
            if ($horaAvance === '07:00') return [];
            $hp = explode(':', $horaAvance);
            $horaAvanceMin = intval($hp[0]) * 60 + intval($hp[1] ?? 0);
            $resultado = [];
            foreach ($rows as $row) {
                $hp2 = explode(':', $row['Hora'] ?? '00:00:00');
                $min = intval($hp2[0]) * 60 + intval($hp2[1] ?? 0);
                if ($horaAvance === '06:30') {
                    // FIN CORTE: todos los movimientos del día
                    $resultado[] = $row;
                } elseif ($min > 0 && $min <= $horaAvanceMin) {
                    $resultado[] = $row;
                }
            }
            return $resultado;
        }

        if ($tipo === 'cierre') {
            // Buckets: Mañana→15:00, Tarde→23:00, Noche→06:30
            $rangos = [
                '15:00' => [['h' => 6.5, 'm' => 390], ['h' => 15, 'm' => 900]],    // 06:31 a 15:00
                '23:00' => [['h' => 15, 'm' => 900], ['h' => 23, 'm' => 1380]],      // 15:01 a 23:00
                '06:30' => [['h' => 23, 'm' => 1380], ['h' => 30.5, 'm' => 1830]]    // 23:01 a 06:30
            ];

            if (!isset($rangos[$horaAvance])) {
                return [];
            }

            $rango = $rangos[$horaAvance];
            $resultado = [];
            foreach ($rows as $row) {
                $hora = $row['Hora'] ?? '00:00:00';
                $hp = explode(':', $hora);
                $min = intval($hp[0]) * 60 + intval($hp[1] ?? 0);
                // 06:30 (NOCHE) debe usar OR porque incluye registros después de 23:00 Y antes de 06:30
                if ($horaAvance === '06:30') {
                    if ($min > $rango[0]['m'] || ($min > 0 && $min <= 390)) {
                        $resultado[] = $row;
                    }
                } else {
                    if ($min > $rango[0]['m'] && $min <= $rango[1]['m']) {
                        $resultado[] = $row;
                    }
                }
            }
            return $resultado;
        } else {
            // Adelante: asignar a la hora de grilla más cercana hacia adelante
            // Usar el MISMO orden que acumularPorHoraAdelante (sin 07:00)
            $horasFiltradas = array_values(array_filter($horasGrilla, function($h) {
                return $h !== '07:00';
            }));

            $hp = explode(':', $horaAvance);
            $horaAvanceMin = intval($hp[0]) * 60 + intval($hp[1] ?? 0);

            // Encontrar la hora anterior en el orden del array filtrado
            // para que 09:00 herede items desde 00:01 (no desde 06:30)
            $horaAnteriorMin = 0;
            $encontrado = false;
            foreach ($horasFiltradas as $hg) {
                $hgp = explode(':', $hg);
                $hgm = intval($hgp[0]) * 60 + intval($hgp[1] ?? 0);
                if ($hgm < $horaAvanceMin) {
                    $horaAnteriorMin = $hgm;
                } else {
                    break; // Detenerse al llegar a la hora actual
                }
            }

            $resultado = [];
            foreach ($rows as $row) {
                $hora = $row['Hora'] ?? '00:00:00';
                $hp = explode(':', $hora);
                $min = intval($hp[0]) * 60 + intval($hp[1] ?? 0);
                // Mayor que la hora anterior y menor o igual a la hora actual
                if ($min > $horaAnteriorMin && $min <= $horaAvanceMin) {
                    $resultado[] = $row;
                }
            }
            error_log("[DEBUG filtrarPorHoraGrilla] tipo={$tipo}, horaAvance={$horaAvance}, " .
                "horaAvanceMin={$horaAvanceMin}, horaAnteriorMin={$horaAnteriorMin}, " .
                "rows_in=" . count($rows) . ", rows_out=" . count($resultado));
            return $resultado;
        }
    }

    /**
     * Obtener detalle de Repliegues (Recepciones Internas, NO COMPRAS GLORIA)
     */
    public function getDetalleRepliegues($fecha, $hora = null, $tipoFiltro = 'cierre')
    {
        try {
            $sql = "SELECT ri.Hora,
                           CONCAT('VRI-', LPAD(ri.NVale, 6, '0')) as NValeFormateado,
                           COALESCE(s.Subarea, '--') as OrigenDestino,
                           rip.Cantidad
                    FROM recepciones_internas ri
                    JOIN recepciones_internas_productos rip ON ri.Id = rip.DespachoId
                    LEFT JOIN subareas s ON ri.Subarea = s.Id
                    WHERE ri.Fecha = ? AND rip.CodigoProducto = '19003730'
                    AND (rip.DescripcionProducto NOT LIKE '%COMPRAS GLORIA%' OR rip.DescripcionProducto IS NULL)
                    AND (rip.DescripcionProducto NOT LIKE '%NUEVAS ARCHIVO CENTRAL%' OR rip.DescripcionProducto IS NULL)
                    AND ri.estado = 'activo'
                    GROUP BY ri.Hora, ri.NVale, s.Subarea, rip.Cantidad, ri.Id
                    ORDER BY ri.Hora ASC, ri.NVale ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fecha]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $this->filtrarPorHoraGrilla($rows, $hora, $tipoFiltro);
        } catch (Exception $e) {
            error_log("Error getDetalleRepliegues: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener detalle de Recepciones Externas (NO COMPRAS GLORIA)
     */
    public function getDetalleRecepcionesExternas($fecha, $hora = null, $tipoFiltro = 'adelante')
    {
        try {
            // NORMALIZACIÓN DE SERIE: por carga masiva pueden existir guías cuyo prefijo
            // no incluya la letra "T" (ej: "081-4975571" en vez de "T081-4975571").
            // El segundo LEFT JOIN matchea la serie con el prefijo "T" agregado cuando
            // el match exacto falla, para agrupar bajo el CentroDistribucion correcto.
            $sql = "SELECT re.Hora, re.Turno,
                           CONCAT('VRE-', LPAD(re.NVale, 6, '0')) as NValeFormateado,
                           COALESCE(s.CentroDistribucion, s2.CentroDistribucion, o.Origen) as OrigenDestino,
                           rep.Cantidad,
                           rep.Total
                    FROM recepciones_externas re
                    JOIN recepciones_externas_guias reg ON re.Id = reg.RecepcionExternaId
                    JOIN recepciones_externas_productos rep ON reg.Id = rep.GuiaId
                    LEFT JOIN origen o ON re.Origen = o.Id
                    LEFT JOIN series s ON s.Serie = SUBSTRING_INDEX(reg.NumeroGuia, '-', 1)
                    LEFT JOIN series s2 ON s.Serie IS NULL AND s2.Serie = CONCAT('T', SUBSTRING_INDEX(reg.NumeroGuia, '-', 1))
                    WHERE re.Fecha = ? AND rep.CodigoProducto = '19003730'
                    AND (rep.DescripcionProducto NOT LIKE '%COMPRAS GLORIA%' OR rep.DescripcionProducto IS NULL)
                    AND (rep.DescripcionProducto NOT LIKE '%NUEVAS ARCHIVO CENTRAL%' OR rep.DescripcionProducto IS NULL)
                    AND re.estado = 'activo'
                    ORDER BY re.Hora ASC, re.NVale ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fecha]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($hora === null) return $rows;

            // Modo acumulado (desglose de Stock Global): vales hasta la hora de la fila
            if ($tipoFiltro === 'acumulado') {
                return $this->filtrarPorHoraGrilla($rows, $hora, 'acumulado');
            }

            // Filtrar por hora con la misma lógica que acumularPorHoraAdelante
            $horasGrilla = ['07:00','09:00','11:00','13:00','15:00','17:00','19:00',
                            '21:00','23:00','01:00','03:00','05:00','06:30'];
            $horasOrd = $horasGrilla;
            usort($horasOrd, function($a, $b) {
                $ap = explode(':', $a);
                $bp = explode(':', $b);
                return (intval($ap[0]) * 60 + intval($ap[1] ?? 0))
                     - (intval($bp[0]) * 60 + intval($bp[1] ?? 0));
            });

            $hp = explode(':', $hora);
            $horaAvanceMin = intval($hp[0]) * 60 + intval($hp[1] ?? 0);

            // Encontrar hora anterior cronológica (excluyendo 07:00)
            $horaAnteriorMin = 0;
            foreach ($horasOrd as $hg) {
                if ($hg === '07:00') continue;
                $hgp = explode(':', $hg);
                $hgm = intval($hgp[0]) * 60 + intval($hgp[1] ?? 0);
                if ($hgm < $horaAvanceMin) {
                    $horaAnteriorMin = $hgm;
                }
            }

            $resultado = [];
            foreach ($rows as $row) {
                $hp2 = explode(':', $row['Hora'] ?? '00:00:00');
                $min = intval($hp2[0]) * 60 + intval($hp2[1] ?? 0);
                // Turno: 1=MAÑANA, 2=TARDE, 3=NOCHE
                $turno = isset($row['Turno']) ? intval($row['Turno']) : 0;

                // Items MAÑANA (Turno=1) con hora < 540 van a 09:00 (misma lógica que acumular)
                if ($hora === '09:00' && $turno === 1 && $min < 540) {
                    $resultado[] = $row;
                } elseif ($min > $horaAnteriorMin && $min <= $horaAvanceMin) {
                    $resultado[] = $row;
                }
            }
            return $resultado;
        } catch (Exception $e) {
            error_log("Error getDetalleRecepcionesExternas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener detalle de Compras Usadas
     * (Actualmente siempre 0, pero se deja estructura preparada)
     */
    public function getDetalleComprasUsadas($fecha, $hora = null, $tipoFiltro = 'adelante')
    {
        try {
            $sql = "(SELECT ri.Hora,
                            CONCAT('VRI-', LPAD(ri.NVale, 6, '0')) as NValeFormateado,
                            'Recepc. Interna' as OrigenDestino,
                            rip.Cantidad
                     FROM recepciones_internas ri
                     JOIN recepciones_internas_productos rip ON ri.Id = rip.DespachoId
                     WHERE ri.Fecha = ? AND rip.CodigoProducto = '19003730'
                     AND rip.DescripcionProducto LIKE '%NUEVAS ARCHIVO CENTRAL%'
                     AND ri.estado = 'activo')
                    UNION ALL
                    (SELECT re.Hora,
                            CONCAT('VRE-', LPAD(re.NVale, 6, '0')) as NValeFormateado,
                            'Recepc. Externa' as OrigenDestino,
                            rep.Total as Cantidad
                     FROM recepciones_externas re
                     JOIN recepciones_externas_guias reg ON re.Id = reg.RecepcionExternaId
                     JOIN recepciones_externas_productos rep ON reg.Id = rep.GuiaId
                     WHERE re.Fecha = ? AND rep.CodigoProducto = '19003730'
                     AND rep.DescripcionProducto LIKE '%NUEVAS ARCHIVO CENTRAL%'
                     AND re.estado = 'activo')
                    ORDER BY Hora ASC, NValeFormateado ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fecha, $fecha]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $this->filtrarPorHoraGrilla($rows, $hora, $tipoFiltro);
        } catch (Exception $e) {
            error_log("Error getDetalleComprasUsadas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener detalle de Compras Nuevas (COMPRAS GLORIA, Recepciones Internas + Externas)
     */
    public function getDetalleComprasNuevas($fecha, $hora = null, $tipoFiltro = 'adelante')
    {
        try {
            $sql = "(SELECT ri.Hora,
                            CONCAT('VRI-', LPAD(ri.NVale, 6, '0')) as NValeFormateado,
                            'Recepc. Interna' as OrigenDestino,
                            rip.Cantidad
                     FROM recepciones_internas ri
                     JOIN recepciones_internas_productos rip ON ri.Id = rip.DespachoId
                     WHERE ri.Fecha = ? AND rip.CodigoProducto = '19003730'
                     AND rip.DescripcionProducto LIKE '%COMPRAS GLORIA%'
                     AND rip.DescripcionProducto NOT LIKE '%NUEVAS ARCHIVO CENTRAL%'
                     AND ri.estado = 'activo')
                    UNION ALL
                    (SELECT re.Hora,
                            CONCAT('VRE-', LPAD(re.NVale, 6, '0')) as NValeFormateado,
                            'Recepc. Externa' as OrigenDestino,
                            rep.Total as Cantidad
                     FROM recepciones_externas re
                     JOIN recepciones_externas_guias reg ON re.Id = reg.RecepcionExternaId
                     JOIN recepciones_externas_productos rep ON reg.Id = rep.GuiaId
                     WHERE re.Fecha = ? AND rep.CodigoProducto = '19003730'
                     AND rep.DescripcionProducto LIKE '%COMPRAS GLORIA%'
                     AND rep.DescripcionProducto NOT LIKE '%NUEVAS ARCHIVO CENTRAL%'
                     AND re.estado = 'activo')
                    ORDER BY Hora ASC, NValeFormateado ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fecha, $fecha]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $this->filtrarPorHoraGrilla($rows, $hora, $tipoFiltro);
        } catch (Exception $e) {
            error_log("Error getDetalleComprasNuevas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener detalle de EAN Usadas Despachos Internos (NO COMPRAS GLORIA)
     */
    public function getDetalleEanUsadasDespInternos($fecha, $hora = null, $tipoFiltro = 'cierre')
    {
        try {
            $sql = "SELECT di.Hora,
                           CONCAT('VDI-', LPAD(di.NVale, 6, '0')) as NValeFormateado,
                           COALESCE(s.Subarea, '--') as OrigenDestino,
                           dip.Cantidad
                    FROM despachos_internos di
                    JOIN despachos_internos_productos dip ON di.Id = dip.DespachoId
                    LEFT JOIN subareas s ON di.Subarea = s.Id
                    WHERE di.Fecha = ? AND dip.CodigoProducto = '19003730'
                    AND (dip.DescripcionProducto NOT LIKE '%COMPRAS GLORIA%' OR dip.DescripcionProducto IS NULL)
                    AND (dip.DescripcionProducto NOT LIKE '%NUEVAS ARCHIVO CENTRAL%' OR dip.DescripcionProducto IS NULL)
                    AND di.estado = 'activo'
                    GROUP BY di.Hora, di.NVale, s.Subarea, dip.Cantidad, di.Id, dip.Id
                    ORDER BY di.Hora ASC, di.NVale ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fecha]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $this->filtrarPorHoraGrilla($rows, $hora, $tipoFiltro);
        } catch (Exception $e) {
            error_log("Error getDetalleEanUsadasDespInternos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener detalle de EAN Usadas Despachos Externos (NO COMPRAS GLORIA)
     */
    public function getDetalleEanUsadasDespExternos($fecha, $hora = null, $tipoFiltro = 'adelante')
    {
        try {
            $sql = "SELECT de.Hora,
                           CONCAT('VDE-', LPAD(de.NVale, 6, '0')) as NValeFormateado,
                           COALESCE(d.Empresa, CAST(de.Destino AS CHAR)) as OrigenDestino,
                           dep.Cantidad
                    FROM despachos_externos de
                    JOIN despachos_externos_productos dep ON de.Id = dep.DespachoId
                    LEFT JOIN clientesexternos d ON de.Destino = d.Id
                    WHERE de.Fecha = ? AND dep.CodigoProducto = '19003730'
                    AND (dep.DescripcionProducto NOT LIKE '%COMPRAS GLORIA%' OR dep.DescripcionProducto IS NULL)
                    AND (dep.DescripcionProducto NOT LIKE '%NUEVAS ARCHIVO CENTRAL%' OR dep.DescripcionProducto IS NULL)
                    AND de.estado = 'activo'
                    GROUP BY de.Hora, de.NVale, OrigenDestino, dep.Cantidad, de.Id, dep.Id
                    ORDER BY de.Hora ASC, de.NVale ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fecha]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            error_log("[DEBUG getDetalleEanUsadasDespExternos] fecha={$fecha}, hora={$hora}, rows_count=" . count($rows));
            if (count($rows) > 0) {
                error_log("[DEBUG getDetalleEanUsadasDespExternos] primer_row=" . json_encode($rows[0]));
            }

            $filtered = $this->filtrarPorHoraGrilla($rows, $hora, $tipoFiltro);
            error_log("[DEBUG getDetalleEanUsadasDespExternos] after_filter=" . count($filtered));
            return $filtered;
        } catch (Exception $e) {
            error_log("Error getDetalleEanUsadasDespExternos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener detalle de EAN Compras Usadas Despachos Internos (COMPRAS GLORIA)
     */
    public function getDetalleEanComprasUsadasDespInternos($fecha, $hora = null, $tipoFiltro = 'cierre')
    {
        try {
            $sql = "SELECT di.Hora,
                           CONCAT('VDI-', LPAD(di.NVale, 6, '0')) as NValeFormateado,
                           COALESCE(s.Subarea, '--') as OrigenDestino,
                           dip.Cantidad
                    FROM despachos_internos di
                    JOIN despachos_internos_productos dip ON di.Id = dip.DespachoId
                    LEFT JOIN subareas s ON di.Subarea = s.Id
                    WHERE di.Fecha = ? AND dip.CodigoProducto = '19003730'
                    AND dip.DescripcionProducto LIKE '%NUEVAS ARCHIVO CENTRAL%'
                    AND di.estado = 'activo'
                    GROUP BY di.Hora, di.NVale, s.Subarea, dip.Cantidad, di.Id, dip.Id
                    ORDER BY di.Hora ASC, di.NVale ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fecha]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $this->filtrarPorHoraGrilla($rows, $hora, $tipoFiltro);
        } catch (Exception $e) {
            error_log("Error getDetalleEanComprasUsadasDespInternos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener detalle de EAN Compras Usadas Despachos Externos (COMPRAS GLORIA)
     */
    public function getDetalleEanComprasUsadasDespExternos($fecha, $hora = null, $tipoFiltro = 'adelante')
    {
        try {
            $sql = "SELECT de.Hora,
                           CONCAT('VDE-', LPAD(de.NVale, 6, '0')) as NValeFormateado,
                           COALESCE(d.Empresa, CAST(de.Destino AS CHAR)) as OrigenDestino,
                           dep.Cantidad
                    FROM despachos_externos de
                    JOIN despachos_externos_productos dep ON de.Id = dep.DespachoId
                    LEFT JOIN clientesexternos d ON de.Destino = d.Id
                    WHERE de.Fecha = ? AND dep.CodigoProducto = '19003730'
                    AND dep.DescripcionProducto LIKE '%NUEVAS ARCHIVO CENTRAL%'
                    AND de.estado = 'activo'
                    GROUP BY de.Hora, de.NVale, OrigenDestino, dep.Cantidad, de.Id, dep.Id
                    ORDER BY de.Hora ASC, de.NVale ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fecha]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $this->filtrarPorHoraGrilla($rows, $hora, $tipoFiltro);
        } catch (Exception $e) {
            error_log("Error getDetalleEanComprasUsadasDespExternos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener detalle de EAN Compras Nuevas Despachos Internos (COMPRAS GLORIA)
     */
    public function getDetalleEanComprasNuevasDespInternos($fecha, $hora = null, $tipoFiltro = 'cierre')
    {
        try {
            $sql = "SELECT di.Hora,
                           CONCAT('VDI-', LPAD(di.NVale, 6, '0')) as NValeFormateado,
                           COALESCE(s.Subarea, '--') as OrigenDestino,
                           dip.Cantidad
                    FROM despachos_internos di
                    JOIN despachos_internos_productos dip ON di.Id = dip.DespachoId
                    LEFT JOIN subareas s ON di.Subarea = s.Id
                    WHERE di.Fecha = ? AND dip.CodigoProducto = '19003730'
                    AND dip.DescripcionProducto LIKE '%COMPRAS GLORIA%'
                    AND di.estado = 'activo'
                    GROUP BY di.Hora, di.NVale, s.Subarea, dip.Cantidad, di.Id, dip.Id
                    ORDER BY di.Hora ASC, di.NVale ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fecha]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $this->filtrarPorHoraGrilla($rows, $hora, $tipoFiltro);
        } catch (Exception $e) {
            error_log("Error getDetalleEanComprasNuevasDespInternos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener detalle de EAN Compras Nuevas Despachos Externos (COMPRAS GLORIA)
     */
    public function getDetalleEanComprasNuevasDespExternos($fecha, $hora = null, $tipoFiltro = 'adelante')
    {
        try {
            $sql = "SELECT de.Hora,
                           CONCAT('VDE-', LPAD(de.NVale, 6, '0')) as NValeFormateado,
                           COALESCE(d.Empresa, CAST(de.Destino AS CHAR)) as OrigenDestino,
                           dep.Cantidad
                    FROM despachos_externos de
                    JOIN despachos_externos_productos dep ON de.Id = dep.DespachoId
                    LEFT JOIN clientesexternos d ON de.Destino = d.Id
                    WHERE de.Fecha = ? AND dep.CodigoProducto = '19003730'
                    AND dep.DescripcionProducto LIKE '%COMPRAS GLORIA%'
                    AND de.estado = 'activo'
                    GROUP BY de.Hora, de.NVale, OrigenDestino, dep.Cantidad, de.Id, dep.Id
                    ORDER BY de.Hora ASC, de.NVale ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fecha]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $this->filtrarPorHoraGrilla($rows, $hora, $tipoFiltro);
        } catch (Exception $e) {
            error_log("Error getDetalleEanComprasNuevasDespExternos: " . $e->getMessage());
            return [];
        }
    }

    // ================================================================
    // DETALLE DE STOCK GLOBAL (POPOVER)
    // Combinan entradas (recepciones) y salidas (despachos) por tipo
    // ================================================================

    /**
     * Calcula el stock inicial (arrastre del día anterior) para el detalle
     * de Stock Global. Replica la lógica de asignación de listar() para la
     * fila 07:00 (INICIO CORTE).
     *
     * @param string $fecha Fecha actual (YYYY-MM-DD)
     * @param string $tipo 'CN' | 'CU' | 'FR'
     * @return float
     */
    private function getStockInicialDetalle($fecha, $tipo)
    {
        $fechaCorte = '2026-07-12';
        $stockCorteCN = 10240;
        $stockCorteFR = 3829;

        if ($tipo === 'CU') {
            return $this->getStockComprasUsadasAcumulado($fecha);
        }

        $fechaAnterior = date('Y-m-d', strtotime($fecha . ' -1 day'));
        $regAnterior = $this->getUltimoRegistro($fechaAnterior);

        if ($tipo === 'CN') {
            $stockCN = floatval($regAnterior['StockComprasNuevas'] ?? 0);
            if ($stockCN > 0) return $stockCN;
            return ($fecha >= $fechaCorte) ? $stockCorteCN : 0;
        }

        // FR
        $stockFR = floatval($regAnterior['StockFlujoRegular'] ?? 0);
        if ($stockFR > 0) return $stockFR;
        return ($fecha >= $fechaCorte) ? $stockCorteFR : 0;
    }

    /**
     * Obtener detalle de Stock C. Nuevas.
     * ENTRADA: Recepciones (internas + externas) COMPRAS GLORIA
     * SALIDA : Despachos (internos + externos) COMPRAS GLORIA
     * Incluye una fila de STOCK INICIAL (arrastre del día anterior).
     */
    public function getDetalleStockComprasNuevas($fecha, $hora = null)
    {
        try {
            // STOCK INICIAL (arrastre del día anterior) para reconstruir el saldo
            $stockInicial = $this->getStockInicialDetalle($fecha, 'CN');
            $filaInicial = [];
            if ($stockInicial > 0) {
                $filaInicial[] = [
                    'Hora' => '00:00',
                    'NValeFormateado' => 'STOCK INICIAL',
                    'OrigenDestino' => 'Día anterior',
                    'Cantidad' => $stockInicial,
                    'Tipo' => 'ENTRADA'
                ];
            }

            $entradas = $this->getDetalleComprasNuevas($fecha, $hora, 'acumulado');
            foreach ($entradas as &$e) { $e['Tipo'] = 'ENTRADA'; }
            unset($e);

            $salidasDI = $this->getDetalleEanComprasNuevasDespInternos($fecha, $hora, 'acumulado');
            foreach ($salidasDI as &$s) { $s['Tipo'] = 'SALIDA'; }
            unset($s);

            $salidasDE = $this->getDetalleEanComprasNuevasDespExternos($fecha, $hora, 'acumulado');
            foreach ($salidasDE as &$s) { $s['Tipo'] = 'SALIDA'; }
            unset($s);

            return array_merge($filaInicial, $entradas, $salidasDI, $salidasDE);
        } catch (Exception $e) {
            error_log("Error getDetalleStockComprasNuevas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener detalle de Stock C. Usadas.
     * ENTRADA: Recepciones (internas + externas) NUEVAS ARCHIVO CENTRAL
     * SALIDA : Despachos (internos + externos) de compras usadas
     */
    public function getDetalleStockComprasUsadas($fecha, $hora = null)
    {
        try {
            // STOCK INICIAL (arrastre del día anterior) para reconstruir el saldo
            $stockInicial = $this->getStockInicialDetalle($fecha, 'CU');
            $filaInicial = [];
            if ($stockInicial > 0) {
                $filaInicial[] = [
                    'Hora' => '00:00',
                    'NValeFormateado' => 'STOCK INICIAL',
                    'OrigenDestino' => 'Día anterior',
                    'Cantidad' => $stockInicial,
                    'Tipo' => 'ENTRADA'
                ];
            }

            $entradas = $this->getDetalleComprasUsadas($fecha, $hora, 'acumulado');
            foreach ($entradas as &$e) { $e['Tipo'] = 'ENTRADA'; }
            unset($e);

            $salidasDI = $this->getDetalleEanComprasUsadasDespInternos($fecha, $hora, 'acumulado');
            foreach ($salidasDI as &$s) { $s['Tipo'] = 'SALIDA'; }
            unset($s);

            $salidasDE = $this->getDetalleEanComprasUsadasDespExternos($fecha, $hora, 'acumulado');
            foreach ($salidasDE as &$s) { $s['Tipo'] = 'SALIDA'; }
            unset($s);

            return array_merge($filaInicial, $entradas, $salidasDI, $salidasDE);
        } catch (Exception $e) {
            error_log("Error getDetalleStockComprasUsadas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener detalle de Stock F. Regular.
     * ENTRADA: Repliegues + Recepciones Externas (NO COMPRAS GLORIA)
     * SALIDA : Despachos (internos + externos) EAN usadas
     */
    public function getDetalleStockFlujoRegular($fecha, $hora = null)
    {
        try {
            // STOCK INICIAL (arrastre del día anterior) para reconstruir el saldo
            $stockInicial = $this->getStockInicialDetalle($fecha, 'FR');
            $filaInicial = [];
            if ($stockInicial > 0) {
                $filaInicial[] = [
                    'Hora' => '00:00',
                    'NValeFormateado' => 'STOCK INICIAL',
                    'OrigenDestino' => 'Día anterior',
                    'Cantidad' => $stockInicial,
                    'Tipo' => 'ENTRADA'
                ];
            }

            $entradasRepliegues = $this->getDetalleRepliegues($fecha, $hora, 'acumulado');
            foreach ($entradasRepliegues as &$e) { $e['Tipo'] = 'ENTRADA'; }
            unset($e);

            $entradasRecExt = $this->getDetalleRecepcionesExternas($fecha, $hora, 'acumulado');
            foreach ($entradasRecExt as &$e) {
                $e['Tipo'] = 'ENTRADA';
                // Las recepciones externas afectan el stock por su 'Total' (igual que la grilla)
                if (isset($e['Total'])) { $e['Cantidad'] = floatval($e['Total']); }
            }
            unset($e);

            $salidasDI = $this->getDetalleEanUsadasDespInternos($fecha, $hora, 'acumulado');
            foreach ($salidasDI as &$s) { $s['Tipo'] = 'SALIDA'; }
            unset($s);

            $salidasDE = $this->getDetalleEanUsadasDespExternos($fecha, $hora, 'acumulado');
            foreach ($salidasDE as &$s) { $s['Tipo'] = 'SALIDA'; }
            unset($s);

            return array_merge($filaInicial, $entradasRepliegues, $entradasRecExt, $salidasDI, $salidasDE);
        } catch (Exception $e) {
            error_log("Error getDetalleStockFlujoRegular: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener detalle de Total Stock (combina los tres stocks globales).
     */
    public function getDetalleTotalStock($fecha, $hora = null)
    {
        try {
            $detCN = $this->getDetalleStockComprasNuevas($fecha, $hora);
            $detCU = $this->getDetalleStockComprasUsadas($fecha, $hora);
            $detFR = $this->getDetalleStockFlujoRegular($fecha, $hora);
            return array_merge($detCN, $detCU, $detFR);
        } catch (Exception $e) {
            error_log("Error getDetalleTotalStock: " . $e->getMessage());
            return [];
        }
    }
}
