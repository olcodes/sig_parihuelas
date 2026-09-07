<div class="container py-0" style="padding-top:0 !important; margin-top:0 !important; position:relative;">
    <div class="despacho-header d-flex align-items-center justify-content-start mb-0">
        <div class="d-flex align-items-center gap-4 flex-nowrap" style="white-space:nowrap;">
            <i class="bi bi-truck-flatbed display-5 text-primary me-2"></i>
            <h2 class="fw-bold mb-0" style="color:#1a237e; letter-spacing:0.5px; font-size:2rem; white-space:nowrap;">Despachos Internos</h2>
        </div>
        <div class="d-flex align-items-center gap-2 ms-4">
            <button type="button" id="btnNuevo" class="btn btn-action btn-new keep-enabled">Nuevo</button>
            <button type="button" id="btnGuardar" class="btn btn-action btn-save keep-enabled" disabled>Guardar</button>
            <button type="button" id="btnModificar" class="btn btn-action btn-edit keep-enabled" disabled>Modificar</button>
            <button type="button" id="btnImprimir" class="btn btn-action btn-print keep-enabled" title="Imprimir"><i class="bi bi-printer"></i></button>
            <div class="bg-warning bg-gradient rounded-4 shadow-sm px-3 py-1 text-center d-flex align-items-center justify-content-center flex-nowrap" style="min-width:120px; margin-left:80px; white-space:nowrap;">
                <span class="fw-bold me-1" style="font-size:1.05rem; color:#7c4700; letter-spacing:1px; vertical-align:middle; white-space:nowrap;">N° Vale:</span>
                <span id="correlativoVale" class="fw-bolder" style="font-size:1.25rem; color:#1a237e; letter-spacing:2px; vertical-align:middle; white-space:nowrap;">000001</span>
            </div>
            <script>
            // Formatear correlativo al cargar la página
            document.addEventListener('DOMContentLoaded', function() {
                var corr = document.getElementById('correlativoVale');
                if (corr && /^\d+$/.test(corr.textContent)) {
                    corr.textContent = 'VDI-' + corr.textContent.padStart(6, '0');
                }
            });
            </script>
        </div>
    </div>
    <style>
        /* Centrar Código, Unidad Medida y Cantidad en la grilla de registro */
        .campo-codigo, .campo-unidad, .campo-cantidad {
            text-align: center !important;
            vertical-align: middle !important;
        }
        /* Asegurar que se aplique dentro de la tabla de despacho */
        #grillaDespacho td.campo-codigo, #grillaDespacho td.campo-unidad, #grillaDespacho td.campo-cantidad {
            text-align: center !important;
        }
        
        /* Estandarizar altura de todos los inputs y select para que estén perfectamente alineados */
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
        
        /* Ajuste específico para textarea */
        textarea.form-control {
            height: auto !important;
            min-height: 38px !important;
        }
        
        /* Choices.js - alinear con inputs y evitar doble borde */
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
    /* Estilos para los botones de acción (coincidir con Recepciones Externas) */
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

    .btn-new {
        color: #16a34a;
        border-color: rgba(16,185,129,0.28);
        background: rgba(16,185,129,0.06);
    }
    .btn-new:active, .btn-new:focus, .btn-new:hover {
        background: rgba(16,185,129,0.14);
        box-shadow: 0 2px 6px rgba(16,185,129,0.06);
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
    </style>

    <div class="card shadow-sm border-0 mb-3" style="max-width:1150px; margin:auto; padding-bottom:2px;">
        <!-- Sin título para ganar espacio -->
        <div class="card-body py-3 px-3">
            <form autocomplete="off">
                <div class="row g-3 align-items-start">
                    <!-- Fecha -->
                    <div class="col-md-3">
                        <label for="fecha" class="form-label">Fecha</label>
                        <input type="date" class="form-control" id="fecha" name="fecha" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                        <div id="badgeFechaNocturno" class="d-none" style="margin-top:4px;" title="Turno nocturno activo - fecha ajustada automáticamente">
                            <i class="bi bi-moon-stars"></i>
                            <span>Fecha a registrar: <strong id="fechaEfectivaDisplay"></strong></span>
                        </div>
                    </div>
                    <!-- Turno + Checkbox -->
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
                            <?php
 if (!empty($turnos)) foreach ($turnos as $t): ?>
                                <option value="<?= htmlspecialchars($t['Id']) ?>"><?= htmlspecialchars($t['Turno']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Subárea -->
                    <div class="col-md-3">
                        <label for="subarea" class="form-label">Subárea</label>
                        <select class="form-select custom-dropdown" id="subarea" name="subarea" required>
                            <option value="">Seleccione</option>
                            <?php if (!empty($subareas)) foreach ($subareas as $s): ?>
                                <option value="<?= htmlspecialchars($s['Id']) ?>" data-area="<?= htmlspecialchars($s['IdArea']) ?>" data-area-nombre="<?= htmlspecialchars($s['Area']) ?>"><?= htmlspecialchars($s['Subarea']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Área -->
                    <div class="col-md-3">
                        <label for="area" class="form-label">Área</label>
                        <input type="text" class="form-control" id="area" name="area" maxlength="100" required disabled>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Mostrar área al elegir subárea -->
    <!-- Eliminado script embebido: lógica gestionada en despachosinternos.js -->
    <!-- Pasar datos de turnos y hora actual al JS -->
    <script>
        window.turnosData = <?php echo json_encode($turnos); ?>;
        window.horaActual = "<?= $horaActual ?>";
    </script>
    <!-- Choices.js para combos con búsqueda -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    
    <!-- Estilos para permitir wrapping de texto largo en select producto -->
    <style>
        /* Permitir wrapping en el select de producto cuando el texto es muy largo
           Usar flex + flex-wrap para que el contenedor expanda su altura correctamente */
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
        /* Cuando el select #turno está deshabilitado, estilizar su contenedor Choices para verse gris */
        #turno:disabled + .choices .choices__inner {
            background-color: #eef2f6 !important;
            color: #6b7280 !important;
            cursor: not-allowed !important;
            opacity: 1 !important;
        }
        #turno:disabled + .choices .choices__list--single .choices__item {
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
    <script>
        window.BASE_URL = '<?= BASE_URL ?>';
        window.usuarioActual = "<?= $_SESSION['user']['NombresApellidos'] ?? '' ?>";
        window.usuarioUsername = "<?= $_SESSION['user']['username'] ?? '' ?>";
        window.productosData = <?php echo json_encode($productos); ?>;        window.responsablesUsernameMap = <?= json_encode($responsablesUsernameMap ?? []) ?>;    </script>
    <script src="<?= BASE_URL ?>/js/autocorrector.js?v=<?= time() ?>"></script>
    <script src="<?= BASE_URL ?>/js/diccionario_espanol_basico.js?v=<?= time() ?>"></script>
    <script src="<?= BASE_URL ?>/js/fecha-efectiva-nocturno.js?v=<?= time() ?>"></script>
    <script src="<?= BASE_URL ?>/js/despachosinternos.js?v=8"></script>
    <script src="<?= BASE_URL ?>/js/debug_filtros.js?v=<?= time() ?>"></script>
    <!-- ...restaurado a solo formulario y controles, sin grilla... -->
    <div class="card shadow-sm border-0 mb-3" style="max-width:1150px; margin:auto; padding-bottom:2px;">
        <div class="card-body py-3 px-3">
            <form autocomplete="off">
                <div class="row g-3 align-items-end">
                    <!-- Despachador -->
                    <div class="col-md-4 d-flex flex-column">
                        <label for="despachador" class="form-label">Despachador</label>
                        <select class="form-select custom-dropdown" id="despachador" name="despachador" required>
                            <option value="">Seleccione</option>
                            <?php if (!empty($responsables)) foreach ($responsables as $r): ?>
                                <option value="<?= htmlspecialchars($r['Id']) ?>" data-username="<?= htmlspecialchars($responsablesUsernameMap[$r['Id']] ?? '') ?>"><?= htmlspecialchars($r['NombresApellidos']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Recepcionista -->
                    <div class="col-md-4">
                        <label for="recepcionista" class="form-label">Recepcionista</label>
                        <select class="form-select custom-dropdown" id="recepcionista" name="recepcionista" required>
                            <option value="">Seleccione</option>
                            <?php if (!empty($recepcionistas)) foreach ($recepcionistas as $r): ?>
                                <option value="<?= htmlspecialchars($r['Id']) ?>"><?= htmlspecialchars($r['NombresApellidos']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Verificador -->
                    <div class="col-md-4">
                        <label for="verificador" class="form-label">Verificador</label>
                        <select class="form-select custom-dropdown" id="verificador" name="verificador" required>
                            <option value="">Seleccione</option>
                            <?php if (!empty($responsables)) foreach ($responsables as $r): ?>
                                <option value="<?= htmlspecialchars($r['Id']) ?>" data-username="<?= htmlspecialchars($responsablesUsernameMap[$r['Id']] ?? '') ?>"><?= htmlspecialchars($r['NombresApellidos']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>
        </div>
    </div>
    <!-- Groupbox 3 -->
    <div class="card shadow-sm border-0 mb-3" style="max-width:1150px; margin:auto; padding-bottom:2px;">
        <!-- Sin título para ganar espacio -->
        <div class="card-body py-3 px-3">
            <form autocomplete="off">
                <div class="row g-3 align-items-start">
                    <!-- Producto -->
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
                    <!-- Código -->
                    <div class="col-md-2 d-flex flex-column" style="align-self: flex-start !important;">
                        <label for="codigo" class="form-label">Código</label>
                        <input type="text" class="form-control" id="codigo" name="codigo" readonly disabled>
                    </div>
                    <!-- Cantidad -->
                    <div class="col-md-2 d-flex flex-column" style="align-self: flex-start !important;">
                        <label for="cantidad" class="form-label">Cantidad</label>
                        <input type="number" class="form-control" id="cantidad" name="cantidad" min="1" required>
                    </div>
                    <!-- Comentarios -->
                    <div class="col-md-4 d-flex flex-column" style="align-self: flex-start !important;">
                        <label for="comentarios" class="form-label">Comentarios</label>
                        <textarea class="form-control" id="comentarios" name="comentarios" rows="2" style="min-height:40px; resize:vertical;"></textarea>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Botones de gestión de la grilla -->
    <div class="d-flex justify-content-end gap-2 mb-3" style="max-width:1150px; margin:auto;">
        <button type="button" class="btn btn-success" id="btnAgregar"><i class="bi bi-plus-circle me-1"></i> Agregar</button>
        <button type="button" class="btn btn-danger" id="btnQuitar"><i class="bi bi-trash me-1"></i> Quitar</button>
        <button type="button" class="btn btn-secondary" id="btnLimpiar"><i class="bi bi-x-circle me-1"></i> Limpiar</button>
    </div>
    <!-- Grilla de productos a despachar -->
    <div class="card shadow-sm border-0 mb-2" style="max-width:1150px; margin:auto; margin-top:8px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="grillaDespacho" style="table-layout:fixed; width:100%;">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px;">N°</th>
                            <th style="width:120px; text-align:center;">Código</th>
                            <th style="width:220px; text-align:center !important;">Producto</th>
                            <th style="width:100px; text-align:center;">UM</th>
                            <th style="width:60px; text-align:center;">Cantidad</th>
                            <th style="width:170px;">Comentarios</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Aquí se agregarán dinámicamente las filas -->
                    </tbody>
                </table>
                <style>
                /* Forzar cabeceras centradas y filas centradas por defecto */
                #grillaDespacho thead th {
                    text-align: center !important;
                    vertical-align: middle !important;
                }
                #grillaDespacho tbody td {
                    text-align: center !important;
                    vertical-align: middle !important;
                    word-break: break-word;
                }

                /* Columnas que deben quedar alineadas a la izquierda: Producto (3) y Comentarios (6) */
                #grillaDespacho tbody td:nth-child(3),
                #grillaDespacho tbody td:nth-child(6) {
                    text-align: left !important;
                }

                /* Columnas que requieren centrado explícito por diseño: Código(2), UM(4), Cantidad(5) */
                #grillaDespacho tbody td:nth-child(2),
                #grillaDespacho tbody td:nth-child(4),
                #grillaDespacho tbody td:nth-child(5) {
                    text-align: center !important;
                }
                </style>
            </div>
        </div>
    </div>
    <!-- Bloque de búsqueda de despacho eliminado completamente para evitar distorsión visual -->

    <!-- Mensaje de error global -->
            <div id="mensajeError" class="alert alert-danger d-none position-fixed top-0 start-50 translate-middle-x mt-3 shadow-lg" style="z-index:2000; min-width:320px; max-width:500px; font-size:1.08rem; font-weight:500; letter-spacing:0.5px; color:#991b1b; background:#fee; border:2px solid #fca5a5; border-radius:0.7rem; box-shadow:0 2px 12px #e5e7eb55; text-align:center;"></div>
    <!-- Modal de impresión de vale -->
        <div class="modal fade" id="modalValePreview" tabindex="-1" aria-labelledby="modalValeLabel" aria-hidden="true">
            <div class="modal-dialog modal-md modal-dialog-centered" style="max-width:620px;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold" id="modalValeLabel" style="font-size:0.9rem;">Vista previa de Vale de Despacho Interno</h5>
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
        <style>
            /* Forzar borde del bloque de firmas dentro de cualquier modal (evita que otros scripts lo quiten) */
            .modal .firma-block { border: 0.5pt solid #333 !important; border-radius: 10px !important; padding: 10px !important; background: transparent !important; }
            .modal .firma-block td { vertical-align: bottom !important; }
        </style>



