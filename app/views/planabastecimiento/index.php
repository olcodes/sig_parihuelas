<!-- Vista del Módulo Plan de Abastecimiento -->
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-3" style="flex-wrap:nowrap;">
        <div class="d-flex align-items-center gap-2 flex-nowrap" style="white-space:nowrap; max-width:65%;">
            <i class="bi bi-calendar-check text-primary me-1" style="font-size:1.5rem;"></i>
            <h5 class="fw-bold mb-0" style="color:#1a237e; letter-spacing:0.3px; font-size:1.05rem; line-height:1.2; white-space:normal;">
                PLAN DE ABASTECIMIENTO
            </h5>
        </div>
        <div class="d-flex align-items-center gap-1 flex-shrink-0">
            <!-- Filtro por mes y año -->
            <div class="d-flex gap-1 align-items-center me-2">
                <select id="selectMes" class="form-select form-select-sm" style="width:auto; font-size:0.75rem; height:30px;">
                    <option value="1">Enero</option>
                    <option value="2">Febrero</option>
                    <option value="3">Marzo</option>
                    <option value="4">Abril</option>
                    <option value="5">Mayo</option>
                    <option value="6">Junio</option>
                    <option value="7">Julio</option>
                    <option value="8">Agosto</option>
                    <option value="9">Setiembre</option>
                    <option value="10">Octubre</option>
                    <option value="11">Noviembre</option>
                    <option value="12">Diciembre</option>
                </select>
                <select id="selectAnio" class="form-select form-select-sm" style="width:auto; font-size:0.75rem; height:30px;">
                    <?php for ($a = date('Y') - 2; $a <= date('Y') + 1; $a++): ?>
                        <option value="<?= $a ?>" <?= $a == date('Y') ? 'selected' : '' ?>><?= $a ?></option>
                    <?php endfor; ?>
                </select>
                <button type="button" id="btnFiltrar" class="btn btn-sm btn-outline-secondary" style="height:30px; font-size:0.75rem; padding:2px 10px;">
                    <i class="bi bi-search"></i>
                </button>
            </div>
            <button type="button" id="btnNuevo" class="btn btn-action btn-new keep-enabled" style="padding:6px 14px;font-size:0.8rem;">Nuevo</button>
            <button type="button" id="btnGuardar" class="btn btn-action btn-save keep-enabled" style="padding:6px 14px;font-size:0.8rem;" disabled>Guardar</button>
            <button type="button" id="btnModificar" class="btn btn-action btn-edit keep-enabled" style="padding:6px 14px;font-size:0.8rem;" disabled>Modificar</button>
        </div>
    </div>

    <!-- Diseño compacto de dos columnas (sin gap) -->
    <div class="row g-0" style="align-items:stretch; zoom:0.82; transform-origin:top left;">
        
        <!-- Columna Izquierda: Registro (28%) -->
        <div class="d-flex" style="flex:0 0 28%; max-width:28%; padding-right:6px;">
            <div class="card shadow-sm border-0 w-100">
                <div class="card-header text-white fw-bold py-2" style="background-color:#374151;">
                    <span><i class="bi bi-pencil-square me-2"></i>Registro</span>
                </div>
                <div class="card-body p-3" style="flex:1 1 auto; overflow:auto;">
                    <form id="formPlanAbastecimiento">
                        <input type="hidden" id="registroId" name="id" value="">
                        
                        <!-- Fecha -->
                        <div class="mb-2">
                            <label for="fecha" class="form-label small fw-bold mb-1">Fecha <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" id="fecha" name="fecha" value="<?= htmlspecialchars($fechaHoy ?? date('Y-m-d')) ?>" required disabled>
                        </div>

                        <!-- Q Total Planificada -->
                        <div class="mb-2">
                            <label for="q_total_planificada" class="form-label small fw-bold mb-1">Q Total Planificada <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-sm" id="q_total_planificada" name="q_total_planificada" step="1" min="0" value="0" required disabled>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Resumen de Movimientos (72%) -->
        <div class="d-flex" style="flex:0 0 72%; max-width:72%; padding-left:6px;">
            <div class="card shadow-sm border-0 w-100">
                <div class="card-header text-white fw-bold py-2" style="background-color:#1f2937;">
                    <span><i class="bi bi-list-ul me-2"></i>Resumen de Movimientos</span>
                </div>
                <div class="card-body p-2" style="background:#f8f9fa; flex:1 1 auto; overflow:auto;">
                    <div class="table-responsive" style="max-height:500px; overflow-y:auto; overflow-x:auto;">
                        <table class="table table-sm table-bordered mb-0" id="tablaPlanAbastecimiento" style="font-size:0.7rem; white-space:nowrap;">
                            <thead class="table-light" style="position:sticky; top:0; z-index:5;">
                                <tr>
                                    <th style="width:30px; text-align:center;">#</th>
                                    <th style="min-width:85px; text-align:center;">Fecha</th>
                                    <th style="min-width:85px; text-align:center;">Q Total Planificada</th>
                                    <th style="min-width:85px; text-align:center;">Q Total Despachada</th>
                                    <th style="min-width:75px; text-align:center;">Q Pendiente Día</th>
                                    <th style="min-width:110px; text-align:center;">Q Pend. Desp. Acum.</th>
                                    <th style="min-width:100px; text-align:center;">Código Planificación</th>
                                    <th style="min-width:60px; text-align:center;">Semana</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyPlanAbastecimiento">
                                <!-- Filas generadas dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
    window.BASE_URL = "<?= BASE_URL ?>";
    window.APP_URL = "<?= APP_URL ?>";
    window.usuarioUsername = "<?= $_SESSION['user']['username'] ?? '' ?>";
    window.usuarioNombre = "<?= $_SESSION['user']['NombresApellidos'] ?? '' ?>";
</script>
<script src="<?= asset_url('js/planabastecimiento.js?v=' . time()) ?>"></script>

<style>
    /* Estilos compactos (mismos que Kardex Parihuelas) */
    .card-body .form-label {
        margin-bottom: 0.15rem;
        color: #374151;
        font-size: 0.7rem;
    }

    .card-body .form-control-sm {
        font-size: 0.75rem;
        padding: 0.2rem 0.4rem;
        height: 30px !important;
    }

    .card-body input:disabled.form-control,
    .card-body input[disabled],
    .card-body .form-control:disabled {
        background-color: #eef2f6 !important;
        color: #6b7280 !important;
        cursor: not-allowed !important;
        opacity: 1 !important;
    }

    .table td, .table th {
        padding: 0.2rem 0.3rem;
        vertical-align: middle;
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

    /* Fila seleccionada en la grilla */
    #tablaPlanAbastecimiento tbody tr.seleccionado {
        background: #bfdbfe !important;
        font-weight: 700;
        color: #1e3a5f;
        outline: 2px solid #3b82f6;
        outline-offset: -2px;
    }
    #tablaPlanAbastecimiento tbody tr.seleccionado td {
        border-bottom: 1px solid #93c5fd !important;
    }
    #tablaPlanAbastecimiento tbody tr:hover {
        background: #e8f0fe;
        cursor: pointer;
    }
</style>
