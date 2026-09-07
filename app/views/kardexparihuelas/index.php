<!-- Vista del Módulo Kardex de Parihuelas Estándar -->
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-3" style="flex-wrap:nowrap;">
        <div class="d-flex align-items-center gap-2 flex-nowrap" style="white-space:nowrap; max-width:65%;">
            <i class="bi bi-clipboard-data text-primary me-1" style="font-size:1.5rem;"></i>
            <h5 class="fw-bold mb-0" style="color:#1a237e; letter-spacing:0.3px; font-size:1.05rem; line-height:1.2; white-space:normal;">
                REPORTE DE RECEPCIÓN, DESPACHOS<br>Y STOCKS DE PARIHUELAS ESTÁNDAR
            </h5>
            <span class="badge bg-secondary ms-2" style="font-size:0.6rem; padding:2px 8px; cursor:default;" title="Cambiar en app/models/KardexParihuela.php línea 18">
                📅 Inicio: <?= htmlspecialchars($fechaInicioKardex ?? '2026-06-25') ?>
            </span>
        </div>
        <div class="d-flex align-items-center gap-1 flex-shrink-0">
            <button type="button" id="btnNuevo" class="btn btn-action btn-new keep-enabled" style="padding:6px 14px;font-size:0.8rem;display:none;">Nuevo</button>
            <button type="button" id="btnGuardar" class="btn btn-action btn-save keep-enabled" style="padding:6px 14px;font-size:0.8rem;">Guardar</button>
            <button type="button" id="btnModificar" class="btn btn-action btn-edit keep-enabled" style="padding:6px 14px;font-size:0.8rem;">Modificar</button>
            <button type="button" id="btnImprimir" class="btn btn-action btn-print keep-enabled" title="Imprimir vista previa" style="padding:6px 10px;">
                <i class="bi bi-printer"></i>
            </button>
        </div>
    </div>

    <!-- Diseño compacto de dos columnas -->
    <div class="row g-3" style="align-items:stretch; zoom:0.82; transform-origin:top left;">
        
        <!-- Columna Izquierda: Datos del Kardex + Stock Inicial (38%) -->
        <div class="col-lg-4 d-flex" style="flex:0 0 38%; max-width:38%;">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header text-white fw-bold d-flex justify-content-between align-items-center" style="background-color:#374151; cursor:pointer;" id="headerDatosKardex">
                    <span><i class="bi bi-pencil-square me-2"></i>Datos Generales</span>
                    <button type="button" class="btn btn-sm btn-link text-white p-0" id="btnToggleDatos" title="Mostrar/Ocultar datos">
                        <i class="bi bi-chevron-up" id="iconToggleDatos"></i>
                    </button>
                </div>
                <div class="card-body p-3 kardex-datos" id="seccionDatosKardex" style="flex:1 1 auto; overflow:auto;">
                    <form id="formKardex">
                        <div class="row g-2">
                            <!-- Fecha y Turno -->
                            <div class="col-12">
                                <div class="d-flex flex-md-nowrap align-items-center gap-2">
                                    <div class="col-auto p-0">
                                        <label for="fecha" class="form-label small fw-bold">Fecha <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control form-control-sm" id="fecha" name="fecha" value="<?= htmlspecialchars($fechaHoy ?? date('Y-m-d')) ?>" max="<?= date('Y-m-d') ?>" required style="width:auto;">
                                    </div>

                                    <div class="turno-wrap turno-container ms-3" style="flex: 0 0 120px;">
                                        <div class="d-flex align-items-center mb-1">
                                            <label for="turno" class="form-label small fw-bold mb-0">Turno <span class="text-danger">*</span></label>
                                        </div>
                                        <select class="form-select form-select-sm custom-dropdown" id="turno" name="turno" required style="width:100%; height:30px; font-size:0.75rem;">
                                            <option value="" selected>&nbsp;</option>
                                            <?php if (isset($turnos) && is_array($turnos)): ?>
                                                <?php foreach ($turnos as $turno): ?>
                                                    <option value="<?= htmlspecialchars($turno['Id'] ?? $turno['id'] ?? '') ?>" 
                                                            data-inicio="<?= htmlspecialchars($turno['HoraInicio'] ?? '') ?>" 
                                                            data-fin="<?= htmlspecialchars($turno['HoraFin'] ?? '') ?>">
                                                        <?= htmlspecialchars($turno['Turno'] ?? $turno['turno'] ?? '') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Sección Stock Inicial -->
                    <hr class="my-2">
                    <div class="fw-bold small mb-1" style="color:#1a237e;">STOCK INICIAL</div>
                    <div class="row g-1">
                        <div class="col-6">
                            <label class="form-label small">Asperjadas</label>
                            <input type="number" class="form-control form-control-sm campo-si" id="si_asperjadas" step="1" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Aptas</label>
                            <input type="number" class="form-control form-control-sm campo-si" id="si_aptas" step="1" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Dañadas</label>
                            <input type="number" class="form-control form-control-sm campo-si" id="si_danadas" step="1" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Sucias</label>
                            <input type="number" class="form-control form-control-sm campo-si" id="si_sucias" step="1" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Por Seleccionar</label>
                            <input type="number" class="form-control form-control-sm campo-si" id="si_por_seleccionar" step="1" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Lavadas y Secadas</label>
                            <input type="number" class="form-control form-control-sm campo-si" id="si_lavadas_secadas" step="1" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Total</label>
                            <input type="number" class="form-control form-control-sm campo-si fw-bold" id="si_total" step="1" min="0" style="background:#e8f0fe;">
                        </div>
                    </div>

                    <!-- Stock Final -->
                    <hr class="my-2">
                    <div class="fw-bold small mb-1" style="color:#b71c1c;">STOCK FINAL</div>
                    <div class="row g-1">
                        <div class="col-6">
                            <label class="form-label small">Asperjadas</label>
                            <input type="number" class="form-control form-control-sm campo-sf" id="sf_asperjadas" step="1" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Aptas</label>
                            <input type="number" class="form-control form-control-sm campo-sf" id="sf_aptas" step="1" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Dañadas</label>
                            <input type="number" class="form-control form-control-sm campo-sf" id="sf_danadas" step="1" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Sucias</label>
                            <input type="number" class="form-control form-control-sm campo-sf" id="sf_sucias" step="1" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Por Seleccionar</label>
                            <input type="number" class="form-control form-control-sm campo-sf" id="sf_por_seleccionar" step="1" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Lavadas y Secadas</label>
                            <input type="number" class="form-control form-control-sm campo-sf" id="sf_lavadas_secadas" step="1" min="0">
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold">Total</label>
                            <input type="number" class="form-control form-control-sm campo-sf fw-bold" id="sf_total" step="1" min="0" style="background:#fce4ec;">
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold" style="color:#6b7280;">Ajuste Manual</label>
                            <input type="number" class="form-control form-control-sm campo-ajuste-manual" id="ajuste_manual_sf_total" step="1" min="0" style="background:#fff8e1; border-color:#f9a825;" title="Uso interno: si se ingresa un valor, este reemplazará al Total de Stock Final al guardar">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Grillas + Ajustes (62%) -->
        <div class="col-lg-8 d-flex" style="flex:0 0 62%; max-width:62%;">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header text-white fw-bold" style="background-color:#1f2937;">
                    <span><i class="bi bi-list-ul me-2"></i>Resumen de Movimientos</span>
                </div>
                <div class="card-body p-2 kardex-grilla" style="background:#f8f9fa; flex:1 1 auto; overflow:auto;">

                    <!-- Grilla de Recepciones -->
                    <div class="mb-2">
                        <div class="fw-bold small mb-1" style="color:#1a237e;">RESUMEN DE RECEPCIONES DE PARIHUELAS ESTÁNDAR</div>
                        <div class="table-responsive" style="max-height:250px; overflow-y:auto; overflow-x:auto;">
                            <table class="table table-sm table-bordered" id="tablaRecepciones" style="font-size:0.7rem; white-space:nowrap;">
                                <thead class="table-light" style="position:sticky; top:0; z-index:5;">
                                    <tr>
                                        <th style="width:35px; text-align:center;">Item</th>
                                        <th style="min-width:120px; text-align:center;">Área Origen</th>
                                        <th style="width:60px; text-align:center;background:#fff3cd;">Aptas</th>
                                        <th style="width:60px; text-align:center;background:#ffe0b2;">Dañadas</th>
                                        <th style="width:60px; text-align:center;background:#bbdefb;">Sucias</th>
                                        <th style="width:60px; text-align:center;background:#c8e6c9;">X Sel</th>
                                        <th style="width:80px; text-align:center;">Total Recepcionado</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyRecepciones">
                                    <!-- Filas generadas dinámicamente -->
                                </tbody>
                                <tfoot class="table-secondary" style="position:sticky; bottom:0; z-index:5;">
                                    <tr>
                                        <th colspan="2" style="text-align:center; font-weight:700;">TOTAL</th>
                                        <th id="totalRecAptas" style="text-align:center; font-weight:700;background:#fff3cd;">0</th>
                                        <th id="totalRecDanadas" style="text-align:center; font-weight:700;background:#ffe0b2;">0</th>
                                        <th id="totalRecSucias" style="text-align:center; font-weight:700;background:#bbdefb;">0</th>
                                        <th id="totalRecPorSel" style="text-align:center; font-weight:700;background:#c8e6c9;">0</th>
                                        <th id="totalRecepcionado" style="text-align:center; font-weight:700;">0</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Grilla de Despachos -->
                    <div class="mb-2">
                        <div class="fw-bold small mb-1" style="color:#1a237e;">RESUMEN DE DESPACHOS DE PARIHUELAS ESTÁNDAR</div>
                        <div class="table-responsive" style="max-height:250px; overflow-y:auto; overflow-x:auto;">
                            <table class="table table-sm table-bordered" id="tablaDespachos" style="font-size:0.7rem; white-space:nowrap;">
                                <thead class="table-light" style="position:sticky; top:0; z-index:5;">
                                    <tr>
                                        <th style="width:35px; text-align:center;">Item</th>
                                        <th style="min-width:120px; text-align:center;">Área Destino</th>
                                        <th style="width:80px; text-align:center;">Total Despachado</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyDespachos">
                                    <!-- Filas generadas dinámicamente -->
                                </tbody>
                                <tfoot class="table-secondary" style="position:sticky; bottom:0; z-index:5;">
                                    <tr>
                                        <th colspan="2" style="text-align:right; font-weight:700;">TOTAL</th>
                                        <th id="totalDespachado" style="text-align:center; font-weight:700;">0</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Nota -->
                    <div class="small text-muted fst-italic mb-2 p-1" style="border-left:3px solid #ffc107; background:#fffbe6;">
                        Importante: Toda recepción se selecciona de inmediato previo a su ingreso al almacén.
                    </div>

                    <!-- Sección Ajustes Manuales -->
                    <div class="row g-1 mb-2">
                        <!-- Total Parihuelas Lavadas -->
                        <div class="col-4">
                            <div class="card border-0 bg-white p-1">
                                <label class="form-label small fw-bold" style="font-size:0.65rem;">TOTAL PARIHUELAS LAVADAS</label>
                                <input type="number" class="form-control form-control-sm campo-ajuste" id="total_parihuelas_lavadas" step="1" min="0">
                            </div>
                        </div>
                    </div>

                    <div class="row g-1 mb-2">
                        <!-- Clasificación de Parihuelas Lavadas y Secadas -->
                        <div class="col-12">
                            <div class="card border-0 bg-white p-1">
                                <label class="form-label small fw-bold" style="font-size:0.65rem;">CLASIFICACIÓN DE PARIHUELAS LAVADAS Y SECADAS</label>
                                <div class="row g-1">
                                    <div class="col-3">
                                        <label class="form-label small" style="font-size:0.6rem;">Aptas</label>
                                        <input type="number" class="form-control form-control-sm campo-ajuste" id="clasif_aptas" step="1" min="0">
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small" style="font-size:0.6rem;">Dañadas</label>
                                        <input type="number" class="form-control form-control-sm campo-ajuste" id="clasif_danadas" step="1" min="0">
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small" style="font-size:0.6rem;">Relavado</label>
                                        <input type="number" class="form-control form-control-sm campo-ajuste" id="clasif_relavado" step="1" min="0">
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small fw-bold" style="font-size:0.6rem;">Total</label>
                                        <input type="number" class="form-control form-control-sm campo-ajuste fw-bold" id="clasif_total" step="1" min="0" style="background:#e8f0fe;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-1 mb-2">
                        <!-- Total Parihuelas Reparadas -->
                        <div class="col-6">
                            <div class="card border-0 bg-white p-1">
                                <label class="form-label small fw-bold" style="font-size:0.65rem;">TOTAL PARIHUELAS REPARADAS</label>
                                <div class="row g-1">
                                    <div class="col-4">
                                        <label class="form-label small" style="font-size:0.6rem;">Aptas</label>
                                        <input type="number" class="form-control form-control-sm campo-ajuste" id="reparadas_aptas" step="1" min="0">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small" style="font-size:0.6rem;">Sucias</label>
                                        <input type="number" class="form-control form-control-sm campo-ajuste" id="reparadas_sucias" step="1" min="0">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small fw-bold" style="font-size:0.6rem;">Total</label>
                                        <input type="number" class="form-control form-control-sm campo-ajuste fw-bold" id="reparadas_total" step="1" min="0" style="background:#e8f0fe;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Parihuelas Clasificadas (del Total por Seleccionar) -->
                        <div class="col-6">
                            <div class="card border-0 bg-white p-1">
                                <label class="form-label small fw-bold" style="font-size:0.65rem;">PARIHUELAS CLASIFICADAS (DEL TOTAL POR SELECCIONAR)</label>
                                <div class="row g-1">
                                    <div class="col-3">
                                        <label class="form-label small" style="font-size:0.6rem;">Aptas</label>
                                        <input type="number" class="form-control form-control-sm campo-ajuste" id="clasificadas_aptas" step="1" min="0">
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small" style="font-size:0.6rem;">Dañadas</label>
                                        <input type="number" class="form-control form-control-sm campo-ajuste" id="clasificadas_danadas" step="1" min="0">
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small" style="font-size:0.6rem;">Sucias</label>
                                        <input type="number" class="form-control form-control-sm campo-ajuste" id="clasificadas_sucias" step="1" min="0">
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small fw-bold" style="font-size:0.6rem;">Total</label>
                                        <input type="number" class="form-control form-control-sm campo-ajuste fw-bold" id="clasificadas_total" step="1" min="0" style="background:#e8f0fe;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-1 mb-2">
                        <div class="col-3">
                            <label class="form-label small fw-bold" style="font-size:0.65rem;">Reselección</label>
                            <input type="number" class="form-control form-control-sm campo-ajuste" id="reseleccion" step="1" min="0">
                        </div>
                        <div class="col-3">
                            <label class="form-label small fw-bold" style="font-size:0.65rem;">Reparación</label>
                            <input type="number" class="form-control form-control-sm campo-ajuste" id="reparacion" step="1" min="0">
                        </div>
                    </div>

                    <div class="row g-1 mb-2">
                        <div class="col-3">
                            <label class="form-label small fw-bold" style="font-size:0.65rem;">Asperjadas del Turno</label>
                            <input type="number" class="form-control form-control-sm campo-ajuste" id="asperjadas_turno" step="1" min="0">
                        </div>
                        <div class="col-3">
                            <label class="form-label small fw-bold" style="font-size:0.65rem;">Despacho Asperjadas</label>
                            <input type="number" class="form-control form-control-sm campo-ajuste" id="despacho_asperjadas" step="1" min="0">
                        </div>
                        <div class="col-3">
                            <label class="form-label small fw-bold" style="font-size:0.65rem;">Autoservicios</label>
                            <input type="number" class="form-control form-control-sm campo-ajuste" id="autoservicios" step="1" min="0">
                        </div>
                        <div class="col-3">
                            <label class="form-label small fw-bold" style="font-size:0.65rem;">Observadas</label>
                            <input type="number" class="form-control form-control-sm campo-ajuste" id="observadas" step="1" min="0">
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Impresión -->
    <div class="modal fade" id="modalKardexPreview" tabindex="-1" aria-labelledby="modalKardexLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width:1200px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalKardexLabel">Vista Previa - Kardex de Parihuelas Estándar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="kardexPreviewContent" style="background:#fff; overflow:auto; min-width:900px;">
                    <!-- Contenido generado por JS -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-success" id="btnKardexExcel"><i class="bi bi-file-earmark-excel"></i> Excel</button>
                    <button type="button" class="btn btn-primary" id="btnKardexPrint"><i class="bi bi-printer"></i> Imprimir</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

<script>
    window.BASE_URL = "<?= BASE_URL ?>";
    window.APP_URL = "<?= APP_URL ?>";
    window.turnosData = <?= json_encode($turnos ?? []) ?>;
    window.horaActual = "<?= htmlspecialchars($horaActual ?? date('H:i')) ?>";
    window.usuarioUsername = "<?= $_SESSION['user']['username'] ?? '' ?>";
    window.usuarioNombre = "<?= $_SESSION['user']['NombresApellidos'] ?? '' ?>";
    window.usuarioPrivilegios = <?= json_encode($_SESSION['privilegios'] ?? []) ?>;
    // Control inline de botones según privilegio (no cacheable)
    (function() {
        var privilegios = <?= json_encode($_SESSION['privilegios'] ?? []) ?>;
        var puedeGuardar = privilegios && privilegios.indexOf('guardar_kardex_parihuelas') !== -1;
        if (!puedeGuardar) {
            var guardarBtn = document.getElementById('btnGuardar');
            var modificarBtn = document.getElementById('btnModificar');
            if (guardarBtn) { guardarBtn.disabled = true; guardarBtn.style.display = 'none'; }
            if (modificarBtn) { modificarBtn.disabled = true; modificarBtn.style.display = 'none'; }
        }
    })();
</script>
<script src="<?= asset_url('js/kardexparihuelas.js?v=' . time()) ?>"></script>

<style>
    /* Estilos compactos */
    .kardex-datos .form-label {
        margin-bottom: 0.15rem;
        color: #374151;
        font-size: 0.7rem;
    }

    .kardex-datos .form-control-sm, .kardex-datos .form-select-sm {
        font-size: 0.75rem;
        padding: 0.2rem 0.4rem;
        height: 30px !important;
    }

    .kardex-datos input:disabled.form-control,
    .kardex-datos input[disabled],
    .kardex-datos .form-control:disabled {
        background-color: #eef2f6 !important;
        color: #6b7280 !important;
        cursor: not-allowed !important;
        opacity: 1 !important;
    }

    .kardex-grilla .table td, .kardex-grilla .table th {
        padding: 0.2rem 0.3rem;
        vertical-align: middle;
    }

    /* =============================================
       VALIDACIÓN: resaltar filas donde la suma de campos
       manuales no coincide con el total recepcionado/despachado
    ============================================= */
    .kardex-grilla .tr-no-coincide {
        background-color: #fff0f0 !important;
        outline: 2px solid #dc3545;
        outline-offset: -1px;
    }
    .kardex-grilla .tr-no-coincide:hover {
        background-color: #ffe0e0 !important;
    }
    .kardex-grilla .td-no-coincide {
        color: #dc3545 !important;
        font-weight: 700 !important;
        position: relative;
    }
    .kardex-grilla .td-no-coincide::after {
        content: ' ⚠';
        font-size: 0.75rem;
        color: #dc3545;
        font-weight: 700;
    }
    .kardex-grilla .tr-no-coincide .form-control {
        border-color: #dc3545 !important;
        background-color: #fff5f5 !important;
    }

    .kardex-grilla input.form-control-sm {
        font-size: 0.7rem;
        padding: 0.15rem 0.3rem;
        height: 26px !important;
    }

    .campo-si, .campo-sf {
        font-size: 0.75rem !important;
        height: 28px !important;
    }

    .campo-ajuste {
        font-size: 0.7rem !important;
        height: 26px !important;
    }

    .btn-action {
        border-radius: 6px;
        padding: 6px 14px;
        font-weight: 600;
        color: #0f172a;
        background: transparent;
        border: 1px solid rgba(15,23,42,0.06);
        transition: all .12s ease-in-out;
        box-shadow: 0 1px 0 rgba(0,0,0,0.02);
        font-size: 0.8rem;
    }
    .btn-action:hover { transform: translateY(-1px); }
    .btn-action:disabled { opacity: 0.55; cursor: not-allowed; }

    .btn-new { color: #16a34a; border-color: rgba(16,185,129,0.28); background: rgba(16,185,129,0.06); }
    .btn-new:active, .btn-new:focus, .btn-new:hover { background: rgba(16,185,129,0.14); }

    .btn-save { color: #2563eb; border-color: rgba(59,130,246,0.22); background: rgba(59,130,246,0.06); }
    .btn-save:active, .btn-save:focus, .btn-save:hover { background: rgba(59,130,246,0.14); }

    .btn-edit { color: #b45309; border-color: rgba(245,158,11,0.18); background: rgba(245,158,11,0.06); }
    .btn-edit:active, .btn-edit:focus, .btn-edit:hover { background: rgba(245,158,11,0.14); }

    .btn-print { color: #374151; border: 1px solid rgba(55,65,81,0.12); background: rgba(255,255,255,0.98); padding: 6px 10px; }
    .btn-print:hover { background: rgba(244,246,248,0.98); }
    .btn-print i { font-size: 0.9rem; }

    /* Turno - compacto y alineado */
    .turno-wrap { align-self: flex-end; position: relative; }
    .turno-container { flex: 0 0 140px; }
    /* Choices.js compacto para turno */
    .turno-wrap .choices,
    .turno-wrap .choices__inner,
    .turno-wrap .choices[data-type*="select-one"] .choices__inner {
        min-height: 30px !important;
        height: 30px !important;
        max-height: 30px !important;
        padding: 0 4px !important;
        font-size: 0.75rem !important;
        border-radius: 4px !important;
        display: flex !important;
        align-items: center !important;
    }
    .turno-wrap .choices__list--single {
        padding: 0 4px !important;
    }

    /* Estilo para impresión */
    @media print {
        .btn-action, .btn-print, .card-header, .btn, #btnImprimir { display: none !important; }
        .container-fluid { zoom: 1; }
    }
</style>
