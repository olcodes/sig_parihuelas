<div class="container py-0" style="padding-top:0 !important; margin-top:0 !important; position:relative;">
    <div class="despacho-header d-flex align-items-center justify-content-start mb-0">
        <div class="d-flex align-items-center gap-4 flex-nowrap" style="white-space:nowrap;">
            <i class="bi bi-inbox display-5 text-primary me-2"></i>
            <h2 class="fw-bold mb-0" style="color:#1a237e; letter-spacing:0.5px; font-size:2rem; white-space:nowrap;">Recepciones Internas</h2>
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
                    corr.textContent = 'VRI-' + corr.textContent.padStart(6, '0');
                }
            });
            </script>
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
                            <?php if (!empty($turnos)) foreach ($turnos as $t): ?>
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
    <!-- Eliminado script embebido: lógica gestionada en recepcionesinternos.js -->
    <!-- Pasar datos de turnos y hora actual al JS -->
    <script>
        window.turnosData = <?php echo json_encode($turnos); ?>;
        window.horaActual = "<?= $horaActual ?>";
    </script>
    <!-- Choices.js para combos con búsqueda -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script>
        window.BASE_URL = '<?= BASE_URL ?>';
        window.usuarioActual = "<?= $_SESSION['user']['NombresApellidos'] ?? '' ?>";
        window.usuarioUsername = "<?= $_SESSION['user']['username'] ?? '' ?>";
        window.productosData = <?php echo json_encode($productos); ?>;
    </script>
    <script src="<?= BASE_URL ?>/js/autocorrector.js?v=<?= time() ?>"></script>
    <script src="<?= BASE_URL ?>/js/diccionario_espanol_basico.js?v=<?= time() ?>"></script>
    <script src="<?= BASE_URL ?>/js/fecha-efectiva-nocturno.js?v=<?= time() ?>"></script>
    <script src="<?= BASE_URL ?>/js/recepcionesinternas.js?v=<?= time() ?>"></script>
    <script src="<?= BASE_URL ?>/js/debug_filtros.js?v=<?= time() ?>"></script>
    <!-- ...restaurado a solo formulario y controles, sin grilla... -->
    <div class="card shadow-sm border-0 mb-3" style="max-width:1150px; margin:auto; padding-bottom:2px;">
        <div class="card-body py-3 px-3">
            <form autocomplete="off">
                <div class="row g-3 align-items-end">
                    <!-- Despachador -->
                    <div class="col-md-4">
                        <label for="despachador" class="form-label">Despachador</label>
                        <select class="form-select custom-dropdown" id="despachador" name="despachador" required>
                            <option value="">Seleccione</option>
                            <?php if (!empty($responsables)) foreach ($responsables as $r): ?>
                                <option value="<?= htmlspecialchars($r['Id']) ?>"><?= htmlspecialchars($r['NombresApellidos']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Medio Transporte -->
                    <div class="col-md-4">
                        <label for="medioTransporte" class="form-label">Medio Transporte</label>
                        <select class="form-select custom-dropdown" id="medioTransporte" name="medioTransporte" required>
                            <option value="">Seleccione</option>
                            <?php if (!empty($mediosTransporte)) foreach ($mediosTransporte as $mt): ?>
                                <option value="<?= htmlspecialchars($mt['Id']) ?>"><?= htmlspecialchars($mt['MedioTransporte']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Verificador -->
                    <div class="col-md-4">
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
                    <div class="col-md-2">
                        <label for="codigo" class="form-label">Código</label>
                        <input type="text" class="form-control" id="codigo" name="codigo" readonly disabled>
                    </div>
                    <!-- Cantidad -->
                    <div class="col-md-2">
                        <label for="cantidad" class="form-label">Cantidad</label>
                        <input type="number" class="form-control" id="cantidad" name="cantidad" min="1" required>
                    </div>
                    <!-- Comentarios -->
                    <div class="col-md-4">
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
    <!-- Grilla de productos a recibir -->
    <div class="card shadow-sm border-0 mb-2" style="max-width:1150px; margin:auto; margin-top:8px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="grillaRecepcion" style="table-layout:fixed; width:100%;">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px;">N°</th>
                            <th style="width:120px; text-align:center;">Código</th>
                            <th style="width:220px;">Producto</th>
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
                /* Alineación consistente: cabeceras y filas centradas por defecto */
                #grillaRecepcion thead th {
                    text-align: center !important;
                    vertical-align: middle !important;
                }
                #grillaRecepcion tbody td {
                    text-align: center !important;
                    vertical-align: middle !important;
                    word-break: break-word;
                }
                /* Columnas alineadas a la izquierda: Producto (3) y Comentarios (6) */
                #grillaRecepcion tbody td:nth-child(3),
                #grillaRecepcion tbody td:nth-child(6) {
                    text-align: left !important;
                }
                /* Columnas centradas explícitas: Código(2), UM(4), Cantidad(5) */
                #grillaRecepcion tbody td:nth-child(2),
                #grillaRecepcion tbody td:nth-child(4),
                #grillaRecepcion tbody td:nth-child(5) {
                    text-align: center !important;
                }
                </style>
            </div>
        </div>
    </div>
    <!-- Bloque de búsqueda de recepción eliminado completamente para evitar distorsión visual -->

    <!-- Mensaje de error global -->
    <div id="mensajeError" class="alert alert-warning d-none position-fixed top-0 start-50 translate-middle-x mt-3 shadow-lg" style="z-index:2000; min-width:320px; max-width:500px; font-size:1.08rem; font-weight:500; letter-spacing:0.5px; color:#7c4700; background:#fffbe6; border:2px solid #ffe082; border-radius:0.7rem; box-shadow:0 2px 12px #e5e7eb55; text-align:center;"></div>
    <!-- Modal de impresión de vale -->
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




