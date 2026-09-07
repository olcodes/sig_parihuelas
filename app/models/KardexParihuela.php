<?php
/**
 * Modelo KardexParihuela - Gestión de Kardex de Inventario para Parihuelas Estándar
 * Producto: 19003730 (PARIHUELAS ESTÁNDAR)
 */
require_once __DIR__ . '/../../core/Model.php';

class KardexParihuela extends Model
{
    protected $table = 'kardex_parihuelas';
    protected $primaryKey = 'Id';
    const CODIGO_PRODUCTO = '19003730';
    
    /**
     * ╔═══════════════════════════════════════════════════════════╗
     * ║  FECHA DE INICIO DEL KARDEX                              ║
     * ║  Cambiar esta fecha cuando el sistema entre en producción ║
     * ║  Antes de esta fecha, stock inicial = 0                  ║
     * ╚═══════════════════════════════════════════════════════════╝
     */
    const FECHA_INICIO = '2026-06-27';

    /**
     * Obtener un kardex por Fecha + Turno
     */
    public function getByFechaTurno($fecha, $turno)
    {
        $sql = "SELECT * FROM kardex_parihuelas WHERE Fecha = ? AND Turno = ? AND estado = 'activo' LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$fecha, $turno]);
        $kardex = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$kardex) return null;

        // Cargar detalle de recepciones y despachos
        $kardex['recepciones'] = $this->getRecepcionesDetalle($kardex['Id']);
        $kardex['despachos'] = $this->getDespachosDetalle($kardex['Id']);

        return $kardex;
    }

    /**
     * Obtener kardex por ID
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM kardex_parihuelas WHERE Id = ? AND estado = 'activo' LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $kardex = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$kardex) return null;

        $kardex['recepciones'] = $this->getRecepcionesDetalle($id);
        $kardex['despachos'] = $this->getDespachosDetalle($id);

        return $kardex;
    }

    /**
     * Obtener stock final del turno anterior
     */
    public function getStockFinalTurnoAnterior($fecha, $turno)
    {
        $fechaInicio = self::FECHA_INICIO;

        // Si la fecha es anterior a la fecha de inicio, no hay stock anterior
        // NOTA: Permitir el mismo día de inicio para soporte entre turnos (ej: Mañana→Tarde)
        if ($fecha < $fechaInicio) {
            return [
                'si_asperjadas' => 0,
                'si_aptas' => 0,
                'si_danadas' => 0,
                'si_sucias' => 0,
                'si_por_seleccionar' => 0,
                'si_lavadas_secadas' => 0,
                'si_secas' => 0,
                'si_total' => 0
            ];
        }

        $sql = "SELECT
                    sf_asperjadas, sf_aptas, sf_danadas, sf_sucias,
                    sf_por_seleccionar, sf_lavadas_secadas, sf_total
                FROM kardex_parihuelas
                WHERE (Fecha < ? OR (Fecha = ? AND Turno < ?))
                AND estado = 'activo'
                AND Fecha >= ?
                ORDER BY Fecha DESC, Turno DESC
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$fecha, $fecha, $turno, $fechaInicio]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return [
                'si_asperjadas' => 0,
                'si_aptas' => 0,
                'si_danadas' => 0,
                'si_sucias' => 0,
                'si_por_seleccionar' => 0,
                'si_lavadas_secadas' => 0,
                'si_secas' => 0,
                'si_total' => 0
            ];
        }

        return [
            'si_asperjadas' => $row['sf_asperjadas'] ?? 0,
            'si_aptas' => $row['sf_aptas'] ?? 0,
            'si_danadas' => $row['sf_danadas'] ?? 0,
            'si_sucias' => $row['sf_sucias'] ?? 0,
            'si_por_seleccionar' => $row['sf_por_seleccionar'] ?? 0,
            'si_lavadas_secadas' => $row['sf_lavadas_secadas'] ?? 0,
            'si_secas' => 0,
            'si_total' => $row['sf_total'] ?? 0
        ];
    }

    /**
     * Obtener recepciones (internas + externas) para producto 19003730
     * Agrupadas por subarea/origen
     */
    public function getRecepcionesParaKardex($fecha, $turno)
    {
        $fechaInicio = self::FECHA_INICIO;

        // Si la fecha es anterior a la fecha de inicio, no retornar datos
        if ($fecha < $fechaInicio) {
            return [];
        }

        // Recepciones Internas - agrupadas por Subarea
        $sqlInternas = "
            SELECT
                ri.Subarea as subarea_id,
                s.Subarea as subarea_nombre,
                SUM(rip.Cantidad) as total_recepcionado
            FROM recepciones_internas ri
            JOIN recepciones_internas_productos rip ON ri.Id = rip.DespachoId
            LEFT JOIN subareas s ON ri.Subarea = s.Id
            WHERE rip.CodigoProducto = ?
            AND ri.Fecha = ?
            AND ri.Turno = ?
            AND ri.estado = 'activo'
            AND ri.Fecha >= ?
            GROUP BY ri.Subarea, s.Subarea
            HAVING total_recepcionado > 0
        ";
        $stmt = $this->db->prepare($sqlInternas);
        $stmt->execute([self::CODIGO_PRODUCTO, $fecha, $turno, $fechaInicio]);
        $internas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Recepciones Externas - agrupadas por Origen/Serie
        // El área origen se determina desde la tabla series.CentroDistribucion
        // según el prefijo del NumeroGuia (parte antes del primer guion),
        // soportando series de 3 y 4 caracteres (ej: 081-4975491, T081-1234567).
        // EXCEPCIÓN: Tottus Fríos, Tottus Secos, Supesa, Supesa Acopio y Cencosud
        //            NO se agrupan bajo el CentroDistribucion (LOCALES), sino que
        //            muestran su nombre de origen real.
        //
        // NORMALIZACIÓN DE SERIE: por carga masiva pueden existir guías cuyo prefijo
        // no incluya la letra "T" (ej: "081-4975571" en vez de "T081-4975571").
        // El segundo LEFT JOIN intenta matchear la serie con el prefijo "T" agregado
        // (CONCAT('T', prefijo)) cuando el match exacto falla, para que esas guías
        // se agrupen bajo su CentroDistribucion correcto (ej: LOCALES) y no bajo
        // el nombre del origen real (ej: LURIN).
        $sqlExternas = "
            SELECT
                re.Origen as subarea_id,
                CASE
                    WHEN o.Origen IN ('Tottus Fríos', 'Tottus Secos', 'Supesa', 'Supesa Acopio', 'Cencosud')
                    THEN o.Origen
                    ELSE COALESCE(s.CentroDistribucion, s2.CentroDistribucion, o.Origen)
                END as subarea_nombre,
                SUM(rep.Total) as total_recepcionado
            FROM recepciones_externas re
            JOIN recepciones_externas_guias reg ON re.Id = reg.RecepcionExternaId
            JOIN recepciones_externas_productos rep ON reg.Id = rep.GuiaId
            LEFT JOIN origen o ON re.Origen = o.Id
            LEFT JOIN series s ON s.Serie = SUBSTRING_INDEX(reg.NumeroGuia, '-', 1)
            LEFT JOIN series s2 ON s.Serie IS NULL AND s2.Serie = CONCAT('T', SUBSTRING_INDEX(reg.NumeroGuia, '-', 1))
            WHERE rep.CodigoProducto = ?
            AND re.Fecha = ?
            AND re.Turno = ?
            AND re.estado = 'activo'
            AND re.Fecha >= ?
            GROUP BY re.Origen,
                CASE
                    WHEN o.Origen IN ('Tottus Fríos', 'Tottus Secos', 'Supesa', 'Supesa Acopio', 'Cencosud')
                    THEN o.Origen
                    ELSE COALESCE(s.CentroDistribucion, s2.CentroDistribucion, o.Origen)
                END
            HAVING total_recepcionado > 0
        ";
        $stmt = $this->db->prepare($sqlExternas);
        $stmt->execute([self::CODIGO_PRODUCTO, $fecha, $turno, $fechaInicio]);
        $externas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Unir resultados y agrupar por subarea_nombre para evitar duplicados
        // (una misma área puede aparecer tanto en internas como en externas)
        $recepcionesAgrupadas = [];
        foreach (array_merge($internas, $externas) as $r) {
            $key = $r['subarea_nombre'];
            if (isset($recepcionesAgrupadas[$key])) {
                $recepcionesAgrupadas[$key]['total_recepcionado'] += (int)$r['total_recepcionado'];
            } else {
                $recepcionesAgrupadas[$key] = [
                    'subarea_id' => $r['subarea_id'],
                    'subarea_nombre' => $r['subarea_nombre'],
                    'total_recepcionado' => (int)$r['total_recepcionado']
                ];
            }
        }

        // Asignar item y campos iniciales
        $item = 1;
        $recepciones = [];
        foreach ($recepcionesAgrupadas as $r) {
            $r['item'] = $item++;
            $r['aptas'] = 0;
            $r['danadas'] = 0;
            $r['sucias'] = 0;
            $r['por_seleccionar'] = 0;
            $recepciones[] = $r;
        }

        return $recepciones;
    }

    /**
     * Obtener despachos (internos + externos) para producto 19003730
     * Agrupados por subarea/destino
     */
    public function getDespachosParaKardex($fecha, $turno)
    {
        $fechaInicio = self::FECHA_INICIO;

        if ($fecha < $fechaInicio) {
            return [];
        }

        // Despachos Internos - agrupados por Subarea
        $sqlInternos = "
            SELECT
                di.Subarea as subarea_id,
                s.Subarea as subarea_nombre,
                SUM(dip.Cantidad) as total_despachado
            FROM despachos_internos di
            JOIN despachos_internos_productos dip ON di.Id = dip.DespachoId
            LEFT JOIN subareas s ON di.Subarea = s.Id
            WHERE dip.CodigoProducto = ?
            AND di.Fecha = ?
            AND di.Turno = ?
            AND di.estado = 'activo'
            AND di.Fecha >= ?
            GROUP BY di.Subarea, s.Subarea
            HAVING total_despachado > 0
        ";
        $stmt = $this->db->prepare($sqlInternos);
        $stmt->execute([self::CODIGO_PRODUCTO, $fecha, $turno, $fechaInicio]);
        $internos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Despachos Externos - no tienen Subarea, usamos Destino
        // NOTA: de.Destino puede ser un ID (numérico) referenciando la tabla destino,
        //       o un texto libre. Se intenta detectar la tabla de destinos disponible
        //       para resolver el nombre.
        // Las tablas de destino usan 'Empresa' como columna del nombre comercial.
        $tablaDestino = $this->detectarTablaDestino();
        $joinDestino = $tablaDestino
            ? "LEFT JOIN {$tablaDestino} d ON de.Destino = d.Id"
            : "";
        $selectDestino = $tablaDestino
            ? "COALESCE(d.Empresa, de.Destino) as subarea_nombre"
            : "de.Destino as subarea_nombre";
        $groupByDestino = $tablaDestino
            ? "COALESCE(d.Empresa, de.Destino)"
            : "de.Destino";

        $sqlExternos = "
            SELECT
                0 as subarea_id,
                {$selectDestino},
                SUM(dep.Cantidad) as total_despachado
            FROM despachos_externos de
            JOIN despachos_externos_productos dep ON de.Id = dep.DespachoId
            {$joinDestino}
            WHERE dep.CodigoProducto = ?
            AND de.Fecha = ?
            AND de.Turno = ?
            AND de.estado = 'activo'
            AND de.Fecha >= ?
            GROUP BY {$groupByDestino}
            HAVING total_despachado > 0
        ";
        $stmt = $this->db->prepare($sqlExternos);
        $stmt->execute([self::CODIGO_PRODUCTO, $fecha, $turno, $fechaInicio]);
        $externos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $despachos = array_merge($internos, $externos);

        $item = 1;
        foreach ($despachos as &$d) {
            $d['item'] = $item++;
            $d['tratadas'] = 0;
            $d['especiales'] = 0;
        }

        return $despachos;
    }

    /**
     * Obtener detalle de recepciones de un kardex
     */
    private function getRecepcionesDetalle($kardexId)
    {
        $sql = "SELECT * FROM kardex_parihuelas_recepciones WHERE KardexId = ? ORDER BY item ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$kardexId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener detalle de despachos de un kardex
     */
    private function getDespachosDetalle($kardexId)
    {
        $sql = "SELECT * FROM kardex_parihuelas_despachos WHERE KardexId = ? ORDER BY item ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$kardexId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Guardar o actualizar kardex completo con detalle
     */
    public function guardar($data, $recepciones, $despachos)
    {
        try {
            $this->db->beginTransaction();

            $fecha = $data['fecha'];
            $turno = $data['turno'];

            // Verificar si ya existe
            $existente = $this->getByFechaTurno($fecha, $turno);

            if ($existente) {
                $kardexId = $existente['Id'];

                // Verificar límite de modificaciones antes de actualizar
                $validacion = $this->puedeModificar($kardexId);
                if (!$validacion['puede']) {
                    $this->db->rollBack();
                    throw new Exception($validacion['mensaje']);
                }

                // Actualizar
                $this->actualizarKardex($kardexId, $data);
                // Eliminar detalle existente
                $this->db->prepare("DELETE FROM kardex_parihuelas_recepciones WHERE KardexId = ?")->execute([$kardexId]);
                $this->db->prepare("DELETE FROM kardex_parihuelas_despachos WHERE KardexId = ?")->execute([$kardexId]);

                // Registrar modificación en la bitácora
                $usuarioId = $_SESSION['user']['id'] ?? null;
                $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                $this->registrarModificacion($kardexId, $usuarioId, $ip);
            } else {
                // Insertar
                $kardexId = $this->insertarKardex($data);
            }

            // Insertar detalle de recepciones
            if (!empty($recepciones)) {
                $stmtRec = $this->db->prepare(
                    "INSERT INTO kardex_parihuelas_recepciones
                    (KardexId, item, subarea_id, subarea_nombre, aptas, danadas, sucias, por_seleccionar, total_recepcionado)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                foreach ($recepciones as $r) {
                    $stmtRec->execute([
                        $kardexId,
                        $r['item'],
                        $r['subarea_id'] ?? 0,
                        $r['subarea_nombre'] ?? '',
                        $r['aptas'] ?? 0,
                        $r['danadas'] ?? 0,
                        $r['sucias'] ?? 0,
                        $r['por_seleccionar'] ?? 0,
                        $r['total_recepcionado'] ?? 0
                    ]);
                }
            }

            // Insertar detalle de despachos
            if (!empty($despachos)) {
                $stmtDes = $this->db->prepare(
                    "INSERT INTO kardex_parihuelas_despachos
                    (KardexId, item, subarea_id, subarea_nombre, tratadas, especiales, total_despachado)
                    VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                foreach ($despachos as $d) {
                    $stmtDes->execute([
                        $kardexId,
                        $d['item'],
                        $d['subarea_id'] ?? 0,
                        $d['subarea_nombre'] ?? '',
                        $d['tratadas'] ?? 0,
                        $d['especiales'] ?? 0,
                        $d['total_despachado'] ?? 0
                    ]);
                }
            }

            $this->db->commit();
            return $kardexId;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error guardando kardex: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Insertar nuevo registro de kardex
     */
    private function insertarKardex($data)
    {
        $sql = "INSERT INTO kardex_parihuelas SET
            Fecha = :fecha,
            Turno = :turno,
            si_asperjadas = :si_asperjadas,
            si_aptas = :si_aptas,
            si_danadas = :si_danadas,
            si_sucias = :si_sucias,
            si_por_seleccionar = :si_por_seleccionar,
            si_lavadas = :si_lavadas,
            si_secas = :si_secas,
            si_total = :si_total,
            total_recepcionado = :total_recepcionado,
            total_despachado = :total_despachado,
            total_parihuelas_lavadas = :total_parihuelas_lavadas,
            clasif_aptas = :clasif_aptas,
            clasif_danadas = :clasif_danadas,
            clasif_relavado = :clasif_relavado,
            clasif_total = :clasif_total,
            reparadas_aptas = :reparadas_aptas,
            reparadas_sucias = :reparadas_sucias,
            reparadas_total = :reparadas_total,
            clasificadas_aptas = :clasificadas_aptas,
            clasificadas_danadas = :clasificadas_danadas,
            clasificadas_sucias = :clasificadas_sucias,
            clasificadas_total = :clasificadas_total,
            reseleccion = :reseleccion,
            reparacion = :reparacion,
            asperjadas_turno = :asperjadas_turno,
            despacho_asperjadas = :despacho_asperjadas,
            autoservicios = :autoservicios,
            observadas = :observadas,
            sf_asperjadas = :sf_asperjadas,
            sf_aptas = :sf_aptas,
            sf_danadas = :sf_danadas,
            sf_sucias = :sf_sucias,
            sf_por_seleccionar = :sf_por_seleccionar,
            sf_lavadas_secadas = :sf_lavadas_secadas,
            sf_total = :sf_total,
            ajuste_manual_sf_total = :ajuste_manual_sf_total,
            nota = :nota,
            creado_por = :creado_por";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':fecha' => $data['fecha'],
            ':turno' => $data['turno'],
            ':si_asperjadas' => $data['si_asperjadas'] ?? 0,
            ':si_aptas' => $data['si_aptas'] ?? 0,
            ':si_danadas' => $data['si_danadas'] ?? 0,
            ':si_sucias' => $data['si_sucias'] ?? 0,
            ':si_por_seleccionar' => $data['si_por_seleccionar'] ?? 0,
            ':si_lavadas' => $data['si_lavadas'] ?? 0,
            ':si_secas' => $data['si_secas'] ?? 0,
            ':si_total' => $data['si_total'] ?? 0,
            ':total_recepcionado' => $data['total_recepcionado'] ?? 0,
            ':total_despachado' => $data['total_despachado'] ?? 0,
            ':total_parihuelas_lavadas' => $data['total_parihuelas_lavadas'] ?? 0,
            ':clasif_aptas' => $data['clasif_aptas'] ?? 0,
            ':clasif_danadas' => $data['clasif_danadas'] ?? 0,
            ':clasif_relavado' => $data['clasif_relavado'] ?? 0,
            ':clasif_total' => $data['clasif_total'] ?? 0,
            ':reparadas_aptas' => $data['reparadas_aptas'] ?? 0,
            ':reparadas_sucias' => $data['reparadas_sucias'] ?? 0,
            ':reparadas_total' => $data['reparadas_total'] ?? 0,
            ':clasificadas_aptas' => $data['clasificadas_aptas'] ?? 0,
            ':clasificadas_danadas' => $data['clasificadas_danadas'] ?? 0,
            ':clasificadas_sucias' => $data['clasificadas_sucias'] ?? 0,
            ':clasificadas_total' => $data['clasificadas_total'] ?? 0,
            ':reseleccion' => $data['reseleccion'] ?? 0,
            ':reparacion' => $data['reparacion'] ?? 0,
            ':asperjadas_turno' => $data['asperjadas_turno'] ?? 0,
            ':despacho_asperjadas' => $data['despacho_asperjadas'] ?? 0,
            ':autoservicios' => $data['autoservicios'] ?? 0,
            ':observadas' => $data['observadas'] ?? 0,
            ':sf_asperjadas' => $data['sf_asperjadas'] ?? 0,
            ':sf_aptas' => $data['sf_aptas'] ?? 0,
            ':sf_danadas' => $data['sf_danadas'] ?? 0,
            ':sf_sucias' => $data['sf_sucias'] ?? 0,
            ':sf_por_seleccionar' => $data['sf_por_seleccionar'] ?? 0,
            ':sf_lavadas_secadas' => $data['sf_lavadas_secadas'] ?? 0,
            ':sf_total' => $data['sf_total'] ?? 0,
            ':ajuste_manual_sf_total' => $data['ajuste_manual_sf_total'] ?? 0,
            ':nota' => $data['nota'] ?? 'Importante: Toda recepción se selecciona de inmediato previo a su ingreso al almacén.',
            ':creado_por' => $data['creado_por'] ?? null
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Actualizar kardex existente
     */
    private function actualizarKardex($id, $data)
    {
        $sql = "UPDATE kardex_parihuelas SET
            si_asperjadas = :si_asperjadas,
            si_aptas = :si_aptas,
            si_danadas = :si_danadas,
            si_sucias = :si_sucias,
            si_por_seleccionar = :si_por_seleccionar,
            si_lavadas = :si_lavadas,
            si_secas = :si_secas,
            si_total = :si_total,
            total_recepcionado = :total_recepcionado,
            total_despachado = :total_despachado,
            total_parihuelas_lavadas = :total_parihuelas_lavadas,
            clasif_aptas = :clasif_aptas,
            clasif_danadas = :clasif_danadas,
            clasif_relavado = :clasif_relavado,
            clasif_total = :clasif_total,
            reparadas_aptas = :reparadas_aptas,
            reparadas_sucias = :reparadas_sucias,
            reparadas_total = :reparadas_total,
            clasificadas_aptas = :clasificadas_aptas,
            clasificadas_danadas = :clasificadas_danadas,
            clasificadas_sucias = :clasificadas_sucias,
            clasificadas_total = :clasificadas_total,
            reseleccion = :reseleccion,
            reparacion = :reparacion,
            asperjadas_turno = :asperjadas_turno,
            despacho_asperjadas = :despacho_asperjadas,
            autoservicios = :autoservicios,
            observadas = :observadas,
            sf_asperjadas = :sf_asperjadas,
            sf_aptas = :sf_aptas,
            sf_danadas = :sf_danadas,
            sf_sucias = :sf_sucias,
            sf_por_seleccionar = :sf_por_seleccionar,
            sf_lavadas_secadas = :sf_lavadas_secadas,
            sf_total = :sf_total,
            ajuste_manual_sf_total = :ajuste_manual_sf_total,
            nota = :nota,
            modificado_por = :modificado_por,
            modificado_en = NOW(),
            modificaciones_count = modificaciones_count + 1
            WHERE Id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':si_asperjadas' => $data['si_asperjadas'] ?? 0,
            ':si_aptas' => $data['si_aptas'] ?? 0,
            ':si_danadas' => $data['si_danadas'] ?? 0,
            ':si_sucias' => $data['si_sucias'] ?? 0,
            ':si_por_seleccionar' => $data['si_por_seleccionar'] ?? 0,
            ':si_lavadas' => $data['si_lavadas'] ?? 0,
            ':si_secas' => $data['si_secas'] ?? 0,
            ':si_total' => $data['si_total'] ?? 0,
            ':total_recepcionado' => $data['total_recepcionado'] ?? 0,
            ':total_despachado' => $data['total_despachado'] ?? 0,
            ':total_parihuelas_lavadas' => $data['total_parihuelas_lavadas'] ?? 0,
            ':clasif_aptas' => $data['clasif_aptas'] ?? 0,
            ':clasif_danadas' => $data['clasif_danadas'] ?? 0,
            ':clasif_relavado' => $data['clasif_relavado'] ?? 0,
            ':clasif_total' => $data['clasif_total'] ?? 0,
            ':reparadas_aptas' => $data['reparadas_aptas'] ?? 0,
            ':reparadas_sucias' => $data['reparadas_sucias'] ?? 0,
            ':reparadas_total' => $data['reparadas_total'] ?? 0,
            ':clasificadas_aptas' => $data['clasificadas_aptas'] ?? 0,
            ':clasificadas_danadas' => $data['clasificadas_danadas'] ?? 0,
            ':clasificadas_sucias' => $data['clasificadas_sucias'] ?? 0,
            ':clasificadas_total' => $data['clasificadas_total'] ?? 0,
            ':reseleccion' => $data['reseleccion'] ?? 0,
            ':reparacion' => $data['reparacion'] ?? 0,
            ':asperjadas_turno' => $data['asperjadas_turno'] ?? 0,
            ':despacho_asperjadas' => $data['despacho_asperjadas'] ?? 0,
            ':autoservicios' => $data['autoservicios'] ?? 0,
            ':observadas' => $data['observadas'] ?? 0,
            ':sf_asperjadas' => $data['sf_asperjadas'] ?? 0,
            ':sf_aptas' => $data['sf_aptas'] ?? 0,
            ':sf_danadas' => $data['sf_danadas'] ?? 0,
            ':sf_sucias' => $data['sf_sucias'] ?? 0,
            ':sf_por_seleccionar' => $data['sf_por_seleccionar'] ?? 0,
            ':sf_lavadas_secadas' => $data['sf_lavadas_secadas'] ?? 0,
            ':sf_total' => $data['sf_total'] ?? 0,
            ':ajuste_manual_sf_total' => $data['ajuste_manual_sf_total'] ?? 0,
            ':nota' => $data['nota'] ?? 'Importante: Toda recepción se selecciona de inmediato previo a su ingreso al almacén.',
            ':modificado_por' => $data['modificado_por'] ?? null,
            ':id' => $id
        ]);
    }

    /**
     * Registrar en el historial quién hizo la modificación
     * (mismo patrón que RecepcionExterna::registrarModificacion)
     */
    public function registrarModificacion($kardexId, $usuarioId, $ip = null)
    {
        $sql = "SELECT COALESCE(modificaciones_count, 0) as modificaciones_count FROM kardex_parihuelas WHERE Id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$kardexId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $nModificacion = $row ? (int)$row['modificaciones_count'] : 0;

        $sql = "INSERT INTO kardex_parihuelas_modificaciones (KardexId, UsuarioId, NModificacion, IpAddress) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$kardexId, $usuarioId, $nModificacion, $ip]);
    }

    /**
     * Verificar si un kardex puede ser modificado
     * Reutiliza la config de usuarios_limites_modificacion (igual que los otros 4 módulos)
     *
     * @param int $id ID del kardex
     * @return array ['puede' => bool, 'modificaciones' => int, 'restantes' => int, 'mensaje' => string]
     */
    public function puedeModificar($id)
    {
        try {
            $sql = "SELECT COALESCE(modificaciones_count, 0) as modificaciones, creado_en
                    FROM kardex_parihuelas WHERE Id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$resultado) {
                return [
                    'puede' => false,
                    'modificaciones' => 0,
                    'restantes' => 0,
                    'mensaje' => 'Kardex no encontrado'
                ];
            }

            $modificaciones = (int)$resultado['modificaciones'];
            $creadoEn = $resultado['creado_en'] ?? null;

            // Obtener configuración del usuario logueado
            $usuarioId = $_SESSION['user']['id'] ?? null;
            $config = $this->obtenerConfigUsuarioModificacion($usuarioId);

            // Si permite múltiples modificaciones, permitir sin restricción
            if ($config && !empty($config['permite_multiples'])) {
                return [
                    'puede' => true,
                    'modificaciones' => $modificaciones,
                    'restantes' => 999,
                    'mensaje' => 'Tiene permisos especiales para modificar sin restricciones'
                ];
            }

            // Determinar max_modificaciones
            $maxModificaciones = ($config && $config['max_modificaciones'] !== null)
                ? (int)$config['max_modificaciones']
                : 1;

            // Validar ventana de tiempo
            $ventanaHoras = ($config && $config['ventana_horas'] !== null)
                ? (int)$config['ventana_horas']
                : null;

            if ($ventanaHoras !== null && $creadoEn) {
                $fechaLimite = date('Y-m-d H:i:s', strtotime($creadoEn . " + {$ventanaHoras} hours"));
                if (strtotime('now') > strtotime($fechaLimite)) {
                    $fechaCreacion = date('d/m/Y H:i', strtotime($creadoEn));
                    return [
                        'puede' => false,
                        'modificaciones' => $modificaciones,
                        'restantes' => 0,
                        'mensaje' => "La ventana de modificacion de {$ventanaHoras}h ha expirado. " .
                                     "El kardex fue creado el {$fechaCreacion}. " .
                                     "Contacte a un administrador si necesita realizar cambios."
                    ];
                }
            }

            // Validar límite de modificaciones
            $puede = $modificaciones < $maxModificaciones;
            $restantes = max(0, $maxModificaciones - $modificaciones);

            return [
                'puede' => $puede,
                'modificaciones' => $modificaciones,
                'restantes' => $restantes,
                'mensaje' => $puede
                    ? "Puede modificar. Modificaciones realizadas: {$modificaciones}, restantes: {$restantes}"
                    : "Este kardex ya alcanzo el limite de {$maxModificaciones} modificaciones."
            ];
        } catch (Exception $e) {
            error_log('Error en KardexParihuela::puedeModificar: ' . $e->getMessage());
            return [
                'puede' => false,
                'modificaciones' => 0,
                'restantes' => 0,
                'mensaje' => 'Error al verificar el estado de modificaciones'
            ];
        }
    }

    /**
     * Actualizar solo los campos de stock inicial (si_*)
     * de un kardex existente, cuando el stock final del turno anterior
     * haya sido modificado con posterioridad.
     *
     * @param int $id ID del kardex a actualizar
     * @param array $stockInicial Datos con prefijo si_* (desde getStockFinalTurnoAnterior)
     * @return bool
     */
    public function actualizarStockInicial($id, $stockInicial)
    {
        try {
            $sql = "UPDATE kardex_parihuelas SET
                si_asperjadas = :si_asperjadas,
                si_aptas = :si_aptas,
                si_danadas = :si_danadas,
                si_sucias = :si_sucias,
                si_por_seleccionar = :si_por_seleccionar,
                si_lavadas = :si_lavadas,
                si_secas = :si_secas,
                si_total = :si_total
                WHERE Id = :id";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':si_asperjadas' => $stockInicial['si_asperjadas'] ?? 0,
                ':si_aptas' => $stockInicial['si_aptas'] ?? 0,
                ':si_danadas' => $stockInicial['si_danadas'] ?? 0,
                ':si_sucias' => $stockInicial['si_sucias'] ?? 0,
                ':si_por_seleccionar' => $stockInicial['si_por_seleccionar'] ?? 0,
                ':si_lavadas' => $stockInicial['si_lavadas_secadas'] ?? 0,
                ':si_secas' => $stockInicial['si_secas'] ?? 0,
                ':si_total' => $stockInicial['si_total'] ?? 0,
                ':id' => $id
            ]);
        } catch (Exception $e) {
            error_log("Error en KardexParihuela::actualizarStockInicial: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Detectar qué tabla de destinos está disponible en la BD.
     * Las posibles tablas son: clientesexternos, clientes_externos, destino, destinos.
     *
     * @return string|null Nombre de la tabla detectada, o null si no se encontró ninguna.
     */
    private function detectarTablaDestino()
    {
        $posibles = ['clientesexternos', 'clientes_externos', 'destino', 'destinos'];
        foreach ($posibles as $tabla) {
            try {
                $stmt = $this->db->prepare("SELECT 1 FROM {$tabla} LIMIT 1");
                $stmt->execute();
                return $tabla;
            } catch (Exception $e) {
                continue;
            }
        }
        return null;
    }
}
