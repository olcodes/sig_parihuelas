<div class="container py-0" style="padding-top:0 !important; margin-top:0 !important; position:relative;">
    <div id="mensajeError" class="alert alert-danger d-none position-fixed top-0 start-50 translate-middle-x mt-3 shadow-lg" style="z-index:2000; min-width:320px; max-width:500px; font-size:1.08rem; font-weight:500; letter-spacing:0.5px; color:#7f1d1d; background:#fee2e2; border:2px solid #fca5a5; border-radius:0.7rem; box-shadow:0 2px 12px #e5e7eb55; text-align:center;"></div>
    <div class="despacho-header d-flex align-items-center justify-content-start mb-0">
        <div class="d-flex align-items-center gap-4 flex-nowrap" style="white-space:nowrap;">
            <i class="bi bi-truck-front display-5 me-2" style="color:#1a90ff;"></i>
            <h2 class="fw-bold mb-0" style="color:#1a237e; letter-spacing:0.5px; font-size:1.5rem; white-space:nowrap;">Edición de Vale - Despachos Externos</h2>
        </div>
        <div class="d-flex align-items-center gap-2 ms-4">
            <button type="button" id="btnBuscarVale" class="btn btn-action btn-search keep-enabled">Buscar Vale</button>
            <button type="button" id="btnGuardar" class="btn btn-action btn-save" disabled>Guardar</button>
            <button type="button" id="btnModificar" class="btn btn-action btn-edit" disabled>Modificar</button>
            <button type="button" id="btnImprimir" class="btn btn-action btn-print keep-enabled" title="Imprimir"><i class="bi bi-printer"></i></button>
            <div id="bloqueNumeroVale" class="bg-warning bg-gradient rounded-4 shadow-sm px-3 py-1 text-center d-flex align-items-center justify-content-center flex-nowrap d-none" style="min-width:120px; margin-left:20px; white-space:nowrap;">
                <span class="fw-bold me-1" style="font-size:1.05rem; color:#7c4700; letter-spacing:1px; vertical-align:middle; white-space:nowrap;">N° Vale:</span>
                <span id="correlativoVale" class="fw-bolder" style="font-size:1.25rem; color:#1a237e; letter-spacing:2px; vertical-align:middle; white-space:nowrap;">VDE-000001</span>
            </div>
        </div>
    </div>

    <style>
        /* Estilos para los botones de acción (coincidir con Nuevo Registro) */
        .btn-action {
            border-radius: 8px;
            padding: 10px 22px;
            font-weight: 700;
            color: #0f172a;
            background: transparent;
            border: 1px solid rgba(15,23,42,0.06);
            transition: all .12s ease-in-out;
            box-shadow: 0 1px 0 rgba(0,0,0,0.02);
            font-size: 0.95rem;
        }
        .btn-action:hover { transform: translateY(-1px); }
        .btn-action:disabled { opacity: 0.55; cursor: not-allowed; }

        .btn-search {
            color: #7c3aed;
            border-color: rgba(124, 58, 237, 0.22);
            background: rgba(124, 58, 237, 0.06);
        }
        .btn-search:active, .btn-search:focus, .btn-search:hover {
            background: rgba(124, 58, 237, 0.14);
            box-shadow: 0 2px 6px rgba(124, 58, 237, 0.06);
        }

        .btn-save {
            color: #2563eb;
            border-color: rgba(59,130,246,0.22);
            background: rgba(59,130,246,0.06);
        }
        .btn-save:active, .btn-save:focus, .btn-save:hover {
            background: rgba(59,130,246,0.14);
            box-shadow: 0 2px 6px rgba(59,130,246,0.06);
        }

        .btn-edit {
            color: #b45309;
            border-color: rgba(245,158,11,0.18);
            background: rgba(245,158,11,0.06);
        }
        .btn-edit:active, .btn-edit:focus, .btn-edit:hover {
            background: rgba(245,158,11,0.14);
            box-shadow: 0 2px 6px rgba(245,158,11,0.06);
        }

        .btn-print {
            color: #374151;
            border: 1px solid rgba(55,65,81,0.12);
            background: rgba(255,255,255,0.98);
            padding: 10px 12px;
        }
        .btn-print:hover { background: rgba(244,246,248,0.98); }

        /* Uniformar ancho de botones principales para que coincidan visualmente */
        .btn-save, .btn-edit, .btn-search {
            width: 110px !important;
            min-width: 110px !important;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap !important;
        }

        /* Estandarizar altura de todos los inputs y select */
        .form-control,
        input.form-control,
        textarea.form-control,
        select.form-select,
        select.custom-dropdown {
            height: 38px !important;
            min-height: 38px !important;
            padding: 0.375rem 0.75rem !important;
            font-size: 1rem !important;
            line-height: 1.5 !important;
            box-sizing: border-box !important;
        }

        textarea.form-control {
            height: auto !important;
            min-height: 38px !important;
        }

        /* Choices.js */
        .choices {
            margin-bottom: 0 !important;
            border: none !important;
        }

        .choices .choices__inner,
        .choices[data-type*=select-one] .choices__inner {
            min-height: 38px !important;
            height: 38px !important;
            max-height: 38px !important;
            padding: 0.375rem 0.75rem !important;
            font-size: 1rem !important;
            line-height: 1.5 !important;
            box-sizing: border-box !important;
            border: 1px solid #ced4da !important;
            border-radius: 0.25rem !important;
            background-color: #fff !important;
        }

        /* Transportista: forzar ellipsis para evitar que el texto largo expanda el control y oculte etiquetas inferiores */
        #transportista + .choices .choices__inner .choices__list--single,
        #transportista + .choices .choices__inner .choices__list--single .choices__item {
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            max-width: 100% !important;
            width: 100% !important;
            display: block !important;
        }

        /* Centrar columnas en tabla de productos */
        .campo-codigo, .campo-unidad, .campo-cantidad {
            text-align: center !important;
            vertical-align: middle !important;
        }

        /* Ajustar automáticamente el ancho de la columna Código según su contenido (no romper líneas) */
        .campo-codigo {
            white-space: nowrap !important;
            overflow: visible !important;
            width: auto !important;
            max-width: none !important;
            padding-right: 6px !important;
        }

        #grillaDespacho td.campo-codigo,
        #grillaDespacho td.campo-unidad,
        #grillaDespacho td.campo-cantidad {
            text-align: center !important;
        }

        /* Estilos para el modal de búsqueda */
        #modalBuscarVale #tablaVales tbody td {
            font-size: 0.86rem;
            padding-top: 0.45rem;
            padding-bottom: 0.45rem;
            vertical-align: middle;
        }

        #modalBuscarVale #tablaVales tbody td.actions {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.35rem !important;
        }

        #modalBuscarVale .table-hover tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.06) !important;
            cursor: pointer;
        }

        /* Alinear cabecera Producto centrada y celda Producto a la izquierda */
        #grillaDespacho thead th:nth-child(3) {
            text-align: center !important;
        }
        #grillaDespacho tbody td:nth-child(3) {
            text-align: left !important;
        }
    </style>
    <style>
        /* Estilos para notificaciones flotantes (overlay) - reutilizar diseño de Recepciones */
        #floatingMsgContainer {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            pointer-events: none; /* Contenedor no captura eventos; los mensajes sí */
        }
        .floating-msg { pointer-events: auto; box-sizing: border-box; }
        .floating-success { background: #ecfdf5; border: 1px solid #bbf7d0; }
        .floating-warning { background: #fff7ed; border: 1px solid #fed7aa; }
        .floating-error   { background: #fff1f2; border: 1px solid #fecaca; }
    </style>
    <style>
        /* Estilo para badge de fecha nocturna */
        #badgeFechaNocturno {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-left: 6px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffc107;
            white-space: nowrap;
            vertical-align: middle;
            cursor: default;
        }
        #badgeFechaNocturno i {
            font-size: 0.75rem;
        }
    </style>

    <!-- Panel Historial de Auditoría (Nivel 2) -->
    <div class="mt-4" id="panelHistorialAuditoria">
        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between py-2 px-3"
                 style="background:#e8eaf6; border-bottom:2px solid #c5cae9; cursor:pointer;"
                 onclick="toggleHistorialPanel()">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history" style="color:#1a237e; font-size:1.1rem;"></i>
                    <span class="fw-bold" style="color:#1a237e; font-size:0.97rem;">Historial de cambios de este vale</span>
                    <span id="historialBadge" class="badge rounded-pill" style="background:#1a237e; color:#fff; font-size:0.8rem; display:none;"></span>
                </div>
                <i class="bi bi-chevron-down" id="historialChevron" style="color:#1a237e;"></i>
            </div>
            <div id="historialBody" style="display:none;">
                <div class="card-body p-3" id="historialContenido">
                    <p class="text-muted mb-0 small"><i class="bi bi-info-circle me-1"></i>Cargue un vale para ver su historial de cambios.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario principal dividido en cards -->
    <div id="cardFechaTurno" class="card shadow-sm border-0 mb-3 d-none" style="max-width:1150px; margin-left:0; margin-right:auto;">
        <div class="card-body py-3 px-3">
            <form autocomplete="off" id="formDespachoExterno">
                <div class="row g-3 align-items-start">
                    <div class="col-md-3">
                        <label for="fecha" class="form-label">Fecha</label>
                        <input type="date" class="form-control" id="fecha" name="fecha" value="<?= htmlspecialchars($fechaHoy ?? date('Y-m-d')) ?>" max="<?= date('Y-m-d') ?>" required>
                        <div id="badgeFechaNocturno" class="d-none" style="margin-top:4px;" title="Turno nocturno activo - fecha ajustada automáticamente">
                            <i class="bi bi-moon-stars"></i>
                            <span>Fecha a registrar: <strong id="fechaEfectivaDisplay"></strong></span>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="d-flex align-items-end justify-content-between mb-1">
                            <label for="turno" class="form-label mb-0">Turno</label>
                            <div class="form-check ms-2 mb-0" style="padding-bottom:0;">
                                <input class="form-check-input align-middle" type="checkbox" id="chkTurno" name="chkTurno" style="margin-bottom:0;">
                                <label class="form-check-label align-middle" for="chkTurno" style="font-size:0.97rem; margin-bottom:0;">Manual</label>
                            </div>
                        </div>
                        <select class="form-select custom-dropdown" id="turno" name="turno" required>
                            <option value="">Seleccione</option>
                            <?php if (!empty($turnos)) foreach ($turnos as $t): ?>
                                <option value="<?= htmlspecialchars($t['Id']) ?>"><?= htmlspecialchars($t['Turno']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="despachador" class="form-label">Despachador</label>
                        <textarea class="form-control" id="despachador" name="despachador" rows="1" style="resize:none;"><?php echo htmlspecialchars($_SESSION['user']['NombresApellidos'] ?? '') ?></textarea>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="cardDestino" class="card shadow-sm border-0 mb-3 d-none" style="max-width:1150px; margin-left:0; margin-right:auto;">
        <div class="card-body py-3 px-3">
            <form autocomplete="off">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="destino" class="form-label">Destino</label>
                        <select class="form-select custom-dropdown" id="destino" name="destino" required>
                            <option value="">Seleccione</option>
                            <?php if (!empty($destinos)) foreach ($destinos as $d): ?>
                                <option value="<?= htmlspecialchars($d['Id']) ?>" data-ruc="<?= htmlspecialchars($d['RUC']) ?>" data-direccion="<?= htmlspecialchars($d['Direccion']) ?>"><?= htmlspecialchars($d['Empresa']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="ruc" class="form-label">RUC</label>
                        <input type="text" class="form-control" id="ruc" name="ruc" maxlength="20" required>
                    </div>

                    <div class="col-md-4">
                        <label for="direccion" class="form-label">Dirección</label>
                        <input type="text" class="form-control" id="direccion" name="direccion" maxlength="200" required>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="cardTransporte" class="card shadow-sm border-0 mb-3 d-none" style="max-width:1150px; margin-left:0; margin-right:auto;">
        <div class="card-body py-3 px-3">
            <form autocomplete="off">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="chofer" class="form-label">Chofer</label>
                        <select class="form-select custom-dropdown" id="chofer" name="chofer" required>
                            <option value="">Seleccione</option>
                            <?php if (!empty($choferes)) foreach ($choferes as $c): ?>
                                <option value="<?= htmlspecialchars($c['Id']) ?>" data-brevete="<?= htmlspecialchars($c['Brevete']) ?>"><?= htmlspecialchars($c['ApellidosNombres']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="brevete" class="form-label">Brevete</label>
                        <input type="text" class="form-control" id="brevete" name="brevete" maxlength="20">
                    </div>

                    <div class="col-md-4">
                        <label for="transportista" class="form-label">Transportista</label>
                        <select class="form-select custom-dropdown" id="transportista" name="transportista" required>
                            <option value="">Seleccione</option>
                            <?php if (!empty($transportistas)) foreach ($transportistas as $t): ?>
                                <option value="<?= htmlspecialchars($t['Id']) ?>" data-ruc="<?= htmlspecialchars($t['RUC']) ?>"><?= htmlspecialchars($t['Empresa']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="ruc_transportista" class="form-label">RUC Transportista</label>
                        <input type="text" class="form-control" id="ruc_transportista" name="ruc_transportista" maxlength="20">
                    </div>
                </div>

                <div class="row g-3 align-items-end mt-1">
                    <div class="col-md-2">
                        <label for="placa_tracto" class="form-label">Placa Tracto</label>
                        <select class="form-select custom-dropdown" id="placa_tracto" name="Placa_Tracto">
                            <option value="">Seleccione</option>
                            <?php if (!empty($placas)) foreach ($placas as $pl): ?>
                                <?php if (isset($pl['TipoPlaca']) && strtoupper($pl['TipoPlaca']) === 'TRACTO'): ?>
                                    <option value="<?= htmlspecialchars($pl['Placa'] ?? '') ?>" data-constancia="<?= htmlspecialchars($pl['ConstanciaInscripcion'] ?? '') ?>"><?= htmlspecialchars($pl['Placa'] ?? '') ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="constancia_inscripcion" class="form-label">Constancia Tracto</label>
                        <input type="text" class="form-control" id="constancia_inscripcion" name="Constancia_Inscripcion" maxlength="20">
                    </div>

                    <div class="col-md-2">
                        <label for="placa_carreta" class="form-label">Placa Carreta</label>
                        <select class="form-select custom-dropdown" id="placa_carreta" name="Placa_Carreta">
                            <option value="">Seleccione</option>
                            <?php if (!empty($placas)) foreach ($placas as $pl): ?>
                                <?php if (isset($pl['TipoPlaca']) && strtoupper($pl['TipoPlaca']) === 'CARRETA'): ?>
                                    <option value="<?= htmlspecialchars($pl['Placa'] ?? '') ?>" data-constancia="<?= htmlspecialchars($pl['ConstanciaInscripcion'] ?? '') ?>"><?= htmlspecialchars($pl['Placa'] ?? '') ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="constancia_inscripcion_2" class="form-label">Constancia Carreta</label>
                        <input type="text" class="form-control" id="constancia_inscripcion_2" name="Constancia_Inscripcion_2" maxlength="20">
                    </div>

                    <div class="col-md-4">
                        <label for="guiaRemision" class="form-label">Guía Remisión</label>
                        <textarea class="form-control" id="guiaRemision" name="guiaRemision" rows="1" style="resize:none;"></textarea>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="cardProductos" class="card shadow-sm border-0 mb-3 d-none" style="max-width:1150px; margin-left:0; margin-right:auto;">
        <div class="card-body py-3 px-3">
            <form autocomplete="off">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="producto" class="form-label">Producto</label>
                        <select class="form-select custom-dropdown" id="producto" name="producto">
                            <option value="">Seleccione</option>
                            <?php if (!empty($productos)) foreach ($productos as $p): ?>
                                <option value="<?= htmlspecialchars($p['Id']) ?>" data-codigo="<?= htmlspecialchars($p['Codigo']) ?>" data-unidadmedida="<?= htmlspecialchars($p['UnidadMedida'] ?? '') ?>">
                                    <?= htmlspecialchars($p['Codigo']) ?> - <?= htmlspecialchars($p['Producto']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="codigo" class="form-label">Código</label>
                        <input type="text" class="form-control" id="codigo" name="codigo" readonly disabled>
                    </div>

                    <div class="col-md-2">
                        <label for="cantidad" class="form-label">Cantidad</label>
                        <input type="number" class="form-control" id="cantidad" name="cantidad" min="1" required>
                    </div>

                    <div class="col-md-4">
                        <label for="comentarios" class="form-label">Comentarios</label>
                        <textarea class="form-control" id="comentarios" name="comentarios" rows="2" style="min-height:40px; resize:vertical;"></textarea>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Botones de gestión de la grilla -->
    <div id="botonesGrilla" class="d-flex justify-content-end gap-2 mb-3 d-none" style="max-width:1150px; margin-left:0; margin-right:auto;">
        <button type="button" class="btn btn-success" id="btnAgregar"><i class="bi bi-plus-circle me-1"></i> Agregar</button>
        <button type="button" class="btn btn-danger" id="btnQuitar"><i class="bi bi-trash me-1"></i> Quitar</button>
        <button type="button" class="btn btn-secondary" id="btnLimpiar"><i class="bi bi-x-circle me-1"></i> Limpiar</button>
    </div>

    <!-- Grilla de productos -->
    <div id="cardGrilla" class="card shadow-sm border-0 mb-2 d-none" style="max-width:1150px; margin-left:0; margin-right:auto;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="grillaDespacho" style="table-layout:fixed; width:100%;">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px; text-align:center;">N°</th>
                            <th style="width:100px; text-align:center;">Código</th>
                            <th style="width:240px; text-align:center;">Producto</th>
                            <th style="width:100px; text-align:center;">UM</th>
                            <th style="width:80px; text-align:center;">Cantidad</th>
                            <th style="width:200px; text-align:center;">Comentarios</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal de búsqueda de vales -->
<div class="modal fade" id="modalBuscarVale" tabindex="-1" aria-labelledby="modalBuscarValeLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalBuscarValeLabel"><i class="bi bi-search me-2"></i>Buscar Vale de Despacho Externo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <!-- Filtros de búsqueda -->
                <div class="card mb-3 border-0 bg-light">
                    <div class="card-body p-3">
                        <div class="row g-2">
                            <div class="col-md-10">
                                <label for="filtroNVale" class="form-label small fw-bold">N° Vale</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">VDE-</span>
                                    <input type="text" class="form-control form-control-sm" id="filtroNVale" placeholder="Ej: 19, 019, 0019" maxlength="6" inputmode="numeric" pattern="\d*" oninput="this.value=this.value.replace(/\D/g,'').slice(0,6)">
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="button" class="btn btn-primary btn-sm w-100" id="btnFiltrarVales"><i class="bi bi-search me-1"></i> Buscar</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabla de resultados -->
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover table-sm align-middle" id="tablaVales">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width: 15%;">N° Vale</th>
                                <th style="width: 12%;">Fecha</th>
                                <th style="width: 10%;">Hora</th>
                                <th style="width: 25%;">Destino</th>
                                <th style="width: 20%;">Chofer</th>
                                <th style="width: 13%; text-align: center;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-info-circle me-2"></i>Haga clic en "Buscar" para buscar vales
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div id="mensajeBusqueda" class="alert alert-info d-none mt-3" role="alert">
                    <i class="bi bi-info-circle me-2"></i><span id="textoBusqueda"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
        </div>
    </div>
</div>

<!-- Modal de impresión: creado dinámicamente por despachosexternos.js -->

<!-- Modal de confirmación para anular vale (igual que Recepciones Externas e Internos) -->
<div class="modal fade" id="modalAnularVale" tabindex="-1" aria-labelledby="modalAnularLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalAnularLabel"><i class="bi bi-exclamation-triangle me-2"></i>Anular Vale</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">¿Está seguro de anular el vale <strong id="valeAnularNumero"></strong>?</p>
                <div class="mb-3">
                    <label for="motivoAnulacion" class="form-label fw-bold">Motivo de anulación <span class="text-danger">*</span></label>
                    <textarea id="motivoAnulacion" class="form-control" rows="3" placeholder="Ingrese el motivo de la anulación..." required></textarea>
                </div>
                <div id="anularFeedback" class="alert d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnConfirmarAnular" class="btn btn-danger">Anular Vale</button>
            </div>
        </div>
    </div>
</div>

<!-- Panel Historial de Auditoría (Nivel 2) -->
<!-- Panel Historial de Auditoría (Nivel 2) moved to top for better layout -->

<!-- Choices.js CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

<!-- Scripts -->
<script>
    window.BASE_URL = '<?= BASE_URL ?>';
    window.APP_URL = '<?= APP_URL ?? BASE_URL ?>';
    window.turnosData = <?= json_encode($turnos ?? []) ?>;
    window.horaActual = "<?= $horaActual ?? date('H:i:s') ?>";
    window.usuarioActual = "<?= $_SESSION['user']['NombresApellidos'] ?? '' ?>";
    window.usuarioUsername = "<?= $_SESSION['user']['username'] ?? '' ?>";
    window.productosData = <?= json_encode($productos ?? []) ?>;
    window.destinosData = <?= json_encode($destinos ?? []) ?>;
    window.choferesData = <?= json_encode($choferes ?? []) ?>;
    window.transportistasData = <?= json_encode($transportistas ?? []) ?>;
    window.placasData = <?= json_encode($placas ?? []) ?>;
    // Bandera para evitar que el listener de despachosexternos.js (main page)
    // ejecute su flujo de guardado en esta página de edición, ya que
    // despachosexternos_edicion.js maneja su propio listener.
    window._esPaginaEdicion = true;
</script>
<script src="<?= BASE_URL ?>/js/autocorrector.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>/js/diccionario_espanol_basico.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>/js/fecha-efectiva-nocturno.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>/js/despachosexternos.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>/js/despachosexternos_edicion.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>/js/auditoria_historial.js?v=<?= time() ?>"></script>
<script>
// Auto-cargar vale si viene parámetro autoLoadId en la URL
(function() {
    var params = new URLSearchParams(window.location.search);
    var autoId = params.get('autoLoadId');
    if (autoId) {
        var checkReady = setInterval(function() {
            if (typeof window.cargarValeEdicion === 'function') {
                clearInterval(checkReady);
                setTimeout(function() {
                    window.cargarValeEdicion(autoId);
                }, 300);
            }
        }, 200);
        // Timeout de seguridad
        setTimeout(function() { clearInterval(checkReady); }, 10000);
    }
})();
</script>
