<div class="container py-0" style="padding-top:0 !important; margin-top:0 !important; position:relative;">
    <div id="mensajeError" class="alert alert-warning d-none position-fixed top-0 start-50 translate-middle-x mt-3 shadow-lg" style="z-index:2000; min-width:320px; max-width:500px; font-size:1.08rem; font-weight:500; letter-spacing:0.5px; color:#7c4700; background:#fffbe6; border:2px solid #ffe082; border-radius:0.7rem; box-shadow:0 2px 12px #e5e7eb55; text-align:center;"></div>
    <div class="despacho-header d-flex align-items-center justify-content-start mb-0">
        <div class="d-flex align-items-center gap-4 flex-nowrap" style="white-space:nowrap;">
            <i class="bi bi-inbox display-5 text-success me-2"></i>
            <h2 class="fw-bold mb-0" style="color:#1a237e; letter-spacing:0.5px; font-size:1.5rem; white-space:nowrap;">Edición de Vale - Recepciones Internas</h2>
        </div>
        <div class="d-flex align-items-center gap-2 ms-4">
            <button type="button" id="btnBuscarVale" class="btn btn-action btn-search keep-enabled">Buscar Vale</button>
            <button type="button" id="btnGuardar" class="btn btn-action btn-save keep-enabled" disabled>Guardar</button>
            <button type="button" id="btnModificar" class="btn btn-action btn-edit keep-enabled" disabled>Modificar</button>
            <button type="button" id="btnImprimir" class="btn btn-action btn-print keep-enabled" title="Imprimir"><i class="bi bi-printer"></i></button>
            <div id="bloqueNumeroVale" class="bg-warning bg-gradient rounded-4 shadow-sm px-3 py-1 text-center d-flex align-items-center justify-content-center flex-nowrap d-none" style="min-width:120px; margin-left:20px; white-space:nowrap;">
                <span class="fw-bold me-1" style="font-size:1.05rem; color:#7c4700; letter-spacing:1px; vertical-align:middle; white-space:nowrap;">N° Vale:</span>
                <span id="correlativoVale" class="fw-bolder" style="font-size:1.25rem; color:#1a237e; letter-spacing:2px; vertical-align:middle; white-space:nowrap;">VRI-000001</span>
            </div>
        </div>
    </div>

    <style>
        /* Estilos para los botones de acción */
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

        /* Uniformar ancho de botones */
        .btn-save, .btn-edit, .btn-search {
            width: 110px !important;
            min-width: 110px !important;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            white-space: nowrap !important;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Estandarizar altura de inputs y selects */
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

        /* Cuando el texto seleccionado es muy largo, permitir salto de línea
           y aumentar la altura del select (solo para el select de producto) */
        .choices.choices-wrap .choices__inner,
        .choices.choices-wrap .choices__list,
        .choices.choices-wrap .choices__list--single {
            height: auto !important;
            min-height: 38px !important;
            max-height: none !important;
            white-space: normal !important;
        }
        .choices.choices-wrap .choices__inner .choices__item,
        .choices.choices-wrap .choices__list .choices__item,
        .choices.choices-wrap .choices__list--single .choices__item {
            white-space: normal !important;
            word-break: break-word !important;
            display: block !important;
            padding-top: 0.25rem !important;
            padding-bottom: 0.25rem !important;
            line-height: 1.15 !important;
        }
        /* Ajuste visual del caret cuando el height crece */
        .choices.choices-wrap .choices__inner .choices__inner {
            align-items: flex-start !important;
        }

        /* Reglas específicas y más agresivas para el select de producto
           - Permitir salto de línea siempre para el elemento seleccionado
           - Aumentar el alto del .choices cuando el contenido ocupa más de una línea */
        #producto + .choices .choices__inner {
            height: auto !important;
            min-height: 38px !important;
            align-items: flex-start !important;
            padding-top: 0.25rem !important;
            padding-bottom: 0.25rem !important;
        }
        #producto + .choices .choices__list--single .choices__item {
            white-space: normal !important;
            word-break: break-word !important;
            display: block !important;
            line-height: 1.15 !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
        }
        /* Ajustar el ancho del caret/trigger para que no se desplace */
        #producto + .choices .choices__inner .choices__list--single {
            display: block !important;
        }

        /* Reglas agresivas: permitir overflow visible y wrapping completo */
        #producto + .choices {
            overflow: visible !important;
        }
        #producto + .choices .choices__inner,
        #producto + .choices .choices__list--single {
            overflow: visible !important;
            white-space: normal !important;
        }
        #producto + .choices .choices__list--single .choices__item {
            white-space: normal !important;
            overflow-wrap: anywhere !important;
            word-break: break-word !important;
        }

        /* Reglas aún más específicas para forzar wrapping en cualquier caso */
        .choices[data-type="select-one"] .choices__list--single .choices__item {
            white-space: normal !important;
            display: block !important;
            overflow: visible !important;
            overflow-wrap: anywhere !important;
            word-break: break-word !important;
            max-width: 100% !important;
        }
        .choices[data-type="select-one"] .choices__inner {
            display: block !important;
            align-items: flex-start !important;
            overflow: visible !important;
        }
        /* Forzar que el elemento contenedor permita múltiples líneas */
        .choices[data-type="select-one"] {
            overflow: visible !important;
        }

        /* Mostrar controles deshabilitados en negrita dentro del card de Fecha/Turno
           Esto incluye el select de 'turno' (Choices) para que aparezca igual que 'area' */
        #cardFechaTurno input:disabled.form-control,
        #cardFechaTurno select:disabled.form-select,
        #cardFechaTurno .choices__inner[aria-disabled="true"],
        #cardFechaTurno .choices__inner:disabled,
        #cardFechaTurno .choices__inner[disabled] {
            font-weight: 600 !important;
            color: inherit !important;
        }
        /* Específico para la instancia de Choices del turno: hacer el texto del item seleccionado más visible */
        #cardFechaTurno #turno + .choices .choices__list--single .choices__item {
            font-weight: 600 !important;
            line-height: 1.2 !important;
        }
        /* Estilo gris para Choices cuando el select original está disabled */
        #turno:disabled + .choices .choices__inner,
        #cardFechaTurno #turno:disabled + .choices .choices__inner {
            background-color: #eef2f6 !important;
            color: #6b7280 !important;
            cursor: not-allowed !important;
            opacity: 1 !important;
        }
        #turno:disabled + .choices .choices__list--single .choices__item,
        #cardFechaTurno #turno:disabled + .choices .choices__list--single .choices__item {
            color: #6b7280 !important;
        }
        /* Reglas adicionales por si Choices añade clases/atributos distintos al deshabilitar */
        #cardFechaTurno .choices.is-disabled .choices__inner,
        #cardFechaTurno .choices[aria-disabled="true"] .choices__inner,
        #cardFechaTurno .choices__inner[aria-disabled="true"] {
            background-color: #eef2f6 !important;
            color: #6b7280 !important;
            cursor: not-allowed !important;
            opacity: 1 !important;
        }
        #cardFechaTurno .choices.is-disabled .choices__list--single .choices__item,
        #cardFechaTurno .choices[aria-disabled="true"] .choices__list--single .choices__item,
        #cardFechaTurno .choices__list--single .choices__item[aria-disabled="true"] {
            color: #6b7280 !important;
        }

        /* Centrar columnas en tabla de productos */
        .campo-codigo, .campo-unidad, .campo-cantidad {
            text-align: center !important;
            vertical-align: middle !important;
        }

        #grillaRecepcion td.campo-codigo,
        #grillaRecepcion td.campo-unidad,
        #grillaRecepcion td.campo-cantidad {
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
            gap: 0.35rem;
        }

        #modalBuscarVale #tablaVales tbody td.actions .btn {
            padding: 0.25rem 0.45rem;
            line-height: 1;
            height: 32px;
        }

        #tablaVales tbody tr:hover {
            background-color: #f8f9fa;
            cursor: pointer;
        }

        .sticky-top {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        /* Alinear cabecera y celdas de la columna "Producto":
           - Cabecera centrada
           - Celdas de producto alineadas a la izquierda */
        #grillaRecepcion thead th:nth-child(3) {
            text-align: center !important;
        }
        #grillaRecepcion tbody td:nth-child(3) {
            text-align: left !important;
        }
        /* Cabecera centrada para Comentarios y celdas alineadas a la izquierda */
        #grillaRecepcion thead th:nth-child(6) {
            text-align: center !important;
        }
        #grillaRecepcion tbody td:nth-child(6) {
            text-align: left !important;
        }
        
        /* Animación para resaltar el botón Modificar */
        @keyframes pulseButton {
            0%, 100% { box-shadow: 0 2px 6px rgba(245,158,11,0.06); }
            50% { box-shadow: 0 2px 12px rgba(245,158,11,0.4); transform: scale(1.02); }
        }
        .pulse-animation {
            animation: pulseButton 1.5s ease-in-out infinite;
        }
    </style>

    <!-- Formulario principal dividido en dos cards -->
    <!-- Card 1: Fecha, Turno, Subárea, Área -->
    <div id="cardFechaTurno" class="card shadow-sm border-0 mb-3 d-none" style="max-width:1150px; margin-left:0; margin-right:auto;">
        <div class="card-body py-3 px-3">
            <form autocomplete="off" id="formRecepcionInterna">
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

                    <div class="col-md-3">
                        <label for="subarea" class="form-label">Subárea</label>
                        <select class="form-select custom-dropdown" id="subarea" name="subarea" required>
                            <option value="">Seleccione</option>
                            <?php if (!empty($subareas)) foreach ($subareas as $s): ?>
                                <option value="<?= htmlspecialchars($s['Id']) ?>" data-area="<?= htmlspecialchars($s['IdArea']) ?>" data-area-nombre="<?= htmlspecialchars($s['Area']) ?>"><?= htmlspecialchars($s['Subarea']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="area" class="form-label">Área</label>
                        <input type="text" class="form-control" id="area" name="area" maxlength="100" required disabled>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Card 2: Emisor, Despachador, Medio de Transporte, Verificador -->
    <div id="cardResponsables" class="card shadow-sm border-0 mb-3 d-none" style="max-width:1150px; margin-left:0; margin-right:auto;">
        <div class="card-body py-3 px-3">
            <form autocomplete="off">
                <div class="row g-3 align-items-start">
                    <div class="col-md-3 d-flex flex-column">
                        <label for="emisor" class="form-label">Emisor</label>
                        <select class="form-select custom-dropdown" id="emisor" name="emisor" required>
                            <option value="">Seleccione</option>
                            <?php if (!empty($responsables)) foreach ($responsables as $r): ?>
                                <option value="<?= htmlspecialchars($r['Id']) ?>"><?= htmlspecialchars($r['NombresApellidos']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="despachador" class="form-label">Despachador</label>
                        <select class="form-select custom-dropdown" id="despachador" name="despachador" required>
                            <option value="">Seleccione</option>
                            <?php if (!empty($responsables)) foreach ($responsables as $r): ?>
                                <option value="<?= htmlspecialchars($r['Id']) ?>"><?= htmlspecialchars($r['NombresApellidos']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="medioTransporte" class="form-label">Medio de Transporte</label>
                        <select class="form-select custom-dropdown" id="medioTransporte" name="medioTransporte" required>
                            <option value="">Seleccione</option>
                            <?php if (!empty($medios)) foreach ($medios as $m): ?>
                                <option value="<?= htmlspecialchars($m['Id']) ?>"><?= htmlspecialchars($m['MedioTransporte']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="verificador" class="form-label">Verificador</label>
                        <select class="form-select custom-dropdown" id="verificador" name="verificador" required>
                            <option value="">Seleccione</option>
                            <?php if (!empty($responsables)) foreach ($responsables as $r): ?>
                                <option value="<?= htmlspecialchars($r['Id']) ?>"><?= htmlspecialchars($r['NombresApellidos']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Card 3: Agregar productos -->
    <div id="cardProductos" class="card shadow-sm border-0 mb-3 d-none" style="max-width:1150px; margin-left:0; margin-right:auto;">
        <div class="card-body py-3 px-3">
            <form autocomplete="off">
                <div class="row g-3 align-items-start">
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

                    <div class="col-md-2 d-flex flex-column" style="align-self: flex-start !important;">
                        <label for="codigo" class="form-label">Código</label>
                        <input type="text" class="form-control" id="codigo" name="codigo" readonly disabled>
                    </div>

                    <div class="col-md-2 d-flex flex-column" style="align-self: flex-start !important;">
                        <label for="cantidad" class="form-label">Cantidad</label>
                        <input type="number" class="form-control" id="cantidad" name="cantidad" min="1" required>
                    </div>

                    <div class="col-md-4 d-flex flex-column" style="align-self: flex-start !important;">
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
                <table class="table table-hover align-middle mb-0" id="grillaRecepcion" style="table-layout:fixed; width:100%;">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px; text-align:center !important;">N°</th>
                            <th style="width:120px; text-align:center !important;">Código</th>
                            <th style="width:220px; text-align:center !important;">Producto</th>
                            <th style="width:100px; text-align:center !important;">UM</th>
                            <th style="width:60px; text-align:center !important;">Cantidad</th>
                            <th style="width:170px; text-align:center !important;">Comentarios</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Aquí se agregarán dinámicamente las filas -->
                    </tbody>
                </table>
                <style>
                    /* Centrar ciertos campos de la grilla: código, unidad y cantidad (forzado) */
                    #grillaRecepcion td.campo-codigo,
                    #grillaRecepcion td.campo-unidad,
                    #grillaRecepcion td.campo-cantidad {
                        text-align: center !important;
                    }
                </style>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Búsqueda de Vales -->
<div class="modal fade" id="modalBuscarVale" tabindex="-1" aria-labelledby="modalBuscarValeLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalBuscarValeLabel"><i class="bi bi-search me-2"></i>Buscar Vale de Recepción Interna</h5>
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
                                    <span class="input-group-text">VRI-</span>
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
                                <th style="width: 20%;">Área</th>
                                <th style="width: 25%;">Turno</th>
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

<!-- Modal de impresión (vista previa) -->
<div class="modal fade" id="modalValePreview" tabindex="-1" aria-labelledby="modalValeLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered" style="max-width:620px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalValeLabel" style="font-size:0.9rem;">Vista previa de Vale de Recepción Interna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="valePreviewContent" style="background:#fff; padding:8px; font-size:0.75rem;">
                <!-- Aquí se genera el contenido del vale -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btnValePrint"><i class="bi bi-printer"></i> Imprimir</button>
            </div>
        </div>
    </div>
</div>

<!-- Choices.js CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

<!-- Overwrite Choices default styles for producto (loaded after Choices.css) -->
<style>
    /* Ensure the visible single item can wrap and the inner container expands */
    #producto + .choices {
        display: inline-block !important;
        overflow: visible !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
    }
    #producto + .choices .choices__inner {
        height: auto !important;
        min-height: 38px !important;
        overflow: visible !important;
        display: flex !important;
        flex-wrap: wrap !important;
        align-items: flex-start !important;
        padding-top: 0.25rem !important;
        padding-bottom: 0.25rem !important;
        box-sizing: border-box !important;
    }
    #producto + .choices .choices__list--single {
        overflow: visible !important;
        display: flex !important;
        flex-wrap: wrap !important;
        align-items: flex-start !important;
        width: 100% !important;
    }
    #producto + .choices .choices__list--single .choices__item {
        white-space: normal !important;
        word-break: break-word !important;
        overflow-wrap: anywhere !important;
        display: inline-block !important;
        line-height: 1.15 !important;
        max-width: 100% !important;
        width: 100% !important;
    }
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

<!-- Scripts -->
<script>
    window.BASE_URL = '<?= BASE_URL ?>';
    window.APP_URL = '<?= APP_URL ?? BASE_URL ?>';
    window.turnosData = <?= json_encode($turnos ?? []) ?>;
    window.horaActual = "<?= $horaActual ?? date('H:i:s') ?>";
    window.usuarioActual = "<?= $_SESSION['user']['NombresApellidos'] ?? '' ?>";
    window.usuarioUsername = "<?= $_SESSION['user']['username'] ?? '' ?>";
    window.productosData = <?= json_encode($productos ?? []) ?>;
    // Bandera para evitar que el listener de recepcionesinternas.js (main page)
    // ejecute su flujo de guardado en esta página de edición, ya que
    // recepcionesinternas_edicion.js maneja su propio listener.
    window._esPaginaEdicion = true;
</script>
<script src="<?= BASE_URL ?>/js/autocorrector.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>/js/diccionario_espanol_basico.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>/js/fecha-efectiva-nocturno.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>/js/recepcionesinternas.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>/js/recepcionesinternas_edicion.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>/js/auditoria_historial.js?v=<?= time() ?>"></script>
<script>
(function() {
    var params = new URLSearchParams(window.location.search);
    var autoId = params.get('autoLoadId');
    if (autoId) {
        var checkReady = setInterval(function() {
            if (typeof window.cargarVale === 'function') {
                clearInterval(checkReady);
                setTimeout(function() { window.cargarVale(autoId); }, 300);
            }
        }, 200);
        setTimeout(function() { clearInterval(checkReady); }, 10000);
    }
})();
</script>
