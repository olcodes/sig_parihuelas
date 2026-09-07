<!-- Vista del Módulo Avance Diario - Control de Stock de Racks y Parihuelas -->
<style>
    body, html { overflow:hidden; height:100%; margin:0; }
    .d-flex[style*="min-height"] { height:calc(100vh - 70px) !important; min-height:calc(100vh - 70px) !important; overflow:hidden !important; }
    main { height:100% !important; min-height:100% !important; overflow:hidden !important; display:flex; flex-direction:column; }
    main > div { display:flex; flex-direction:column; flex:1; overflow:hidden; }
</style>
<div class="container-fluid px-0" style="display:flex; flex-direction:column; flex:1; overflow:hidden;">
    <div class="d-flex justify-content-between align-items-center mb-3" style="flex-wrap:nowrap; flex-shrink:0;">
        <div class="d-flex align-items-center gap-2 flex-nowrap" style="white-space:nowrap; max-width:65%;">
            <i class="bi bi-speedometer2 text-primary me-1" style="font-size:1.5rem;"></i>
            <h5 class="fw-bold mb-0" style="color:#1a237e; letter-spacing:0.3px; font-size:1.05rem; line-height:1.2; white-space:normal;">
                CONTROL DE STOCK DE RACKS Y PARIHUELAS
            </h5>
        </div>
        <div class="d-flex align-items-center gap-1 flex-shrink-0">
            <div class="d-flex gap-1 align-items-center me-2">
                <input type="date" id="inputFecha" class="form-control form-control-sm" style="width:auto; font-size:0.75rem; height:30px;" value="<?= htmlspecialchars($fechaHoy ?? date('Y-m-d')) ?>">
                <button type="button" id="btnFiltrar" class="btn btn-sm btn-outline-secondary" style="height:30px; font-size:0.75rem; padding:2px 10px;">
                    <i class="bi bi-search"></i>
                </button>
            </div>
            <button type="button" id="btnGuardar" class="btn btn-action btn-save keep-enabled" style="padding:6px 14px;font-size:0.8rem;">Guardar</button>
        </div>
    </div>

    <style>
        #tablaAvanceDiario tbody td span.ad-text { display:inline-block; font-size:0.7rem; text-align:center; padding:1px 2px; }
        th.rotado {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            height: 90px;
            white-space: nowrap;
            padding: 2px;
            font-size: 0.65rem;
            text-align: center;
            vertical-align: middle;
        }
        #tablaAvanceDiario tbody td input.ad-input[type=number]::-webkit-inner-spin-button,
        #tablaAvanceDiario tbody td input.ad-input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        #tablaAvanceDiario tbody td input.ad-input[type=number] {
            -moz-appearance: textfield;
        }
    </style>
    <div id="ad-wrapper" style="flex:1; min-height:0; overflow:auto; border:1px solid #dee2e6; border-radius:6px; background:#fff;">
        <table class="table table-sm table-bordered mb-0" id="tablaAvanceDiario" style="font-size:0.7rem; white-space:nowrap;">
            <thead>
                <tr>
                    <th rowspan="2" style="text-align:center; vertical-align:middle; background-color:#4caf50; color:#fff; border-color:#388e3c;">Item</th>
                    <th rowspan="2" style="text-align:center; vertical-align:middle; background-color:#4caf50; color:#fff; border-color:#388e3c;">Fecha</th>
                    <th rowspan="2" style="text-align:center; vertical-align:middle; background-color:#4caf50; color:#fff; border-color:#388e3c;">Hora<br>Avance</th>
                    <th rowspan="2" style="text-align:center; vertical-align:middle; background-color:#4caf50; color:#fff; border-color:#388e3c;">Turno</th>
                    <th colspan="2" style="text-align:center; vertical-align:middle; background-color:#ffd54f; color:#333; border-color:#f9a825; font-weight:700;">EAN USADAS</th>
                    <th colspan="3" style="text-align:center; vertical-align:middle; background-color:#ffd54f; color:#333; border-color:#f9a825; font-weight:700;">RECEPCIONES</th>
                    <th colspan="2" style="text-align:center; vertical-align:middle; background-color:#ef5350; color:#fff; border-color:#c62828; font-weight:700;">EAN USADAS</th>
                    <th colspan="2" style="text-align:center; vertical-align:middle; background-color:#ef5350; color:#fff; border-color:#c62828; font-weight:700;">EAN<br>COMPRAS<br>USADAS</th>
                    <th colspan="2" style="text-align:center; vertical-align:middle; background-color:#ef5350; color:#fff; border-color:#c62828; font-weight:700;">EAN<br>COMPRAS<br>NUEVAS</th>
                    <th rowspan="2" class="rotado" style="background-color:#ef5350; color:#fff; border-color:#c62828;">Total Despacho</th>
                    <th rowspan="2" class="rotado" style="background-color:#4fc3f7; color:#333; border-color:#0288d1;">Q Planificada</th>
                    <th rowspan="2" class="rotado" style="background-color:#4fc3f7; color:#333; border-color:#0288d1;">% Cumplimiento</th>
                    <th colspan="4" style="text-align:center; vertical-align:middle; background-color:#f48fb1; color:#333; border-color:#d81b60; font-weight:700;">STOCK GLOBAL</th>
                </tr>
                <tr>
                    <th class="rotado" style="background-color:#fff8e1; color:#333; border-color:#f9a825;">Repliegues</th>
                    <th class="rotado" style="background-color:#fff8e1; color:#333; border-color:#f9a825;">Recepc. Externas</th>
                    <th class="rotado" style="background-color:#fff8e1; color:#333; border-color:#f9a825;">Compras Usadas</th>
                    <th class="rotado" style="background-color:#fff8e1; color:#333; border-color:#f9a825;">Compras Nuevas</th>
                    <th class="rotado" style="background-color:#fff8e1; color:#333; border-color:#f9a825;">Total Recepción</th>
                    <th class="rotado" style="background-color:#ffebee; color:#b71c1c; border-color:#c62828;">Desp. Internos</th>
                    <th class="rotado" style="background-color:#ffebee; color:#b71c1c; border-color:#c62828;">Desp. Externos</th>
                    <th class="rotado" style="background-color:#ffebee; color:#b71c1c; border-color:#c62828;">Desp. Internos</th>
                    <th class="rotado" style="background-color:#ffebee; color:#b71c1c; border-color:#c62828;">Desp. Externos</th>
                    <th class="rotado" style="background-color:#ffebee; color:#b71c1c; border-color:#c62828;">Desp. Internos</th>
                    <th class="rotado" style="background-color:#ffebee; color:#b71c1c; border-color:#c62828;">Desp. Externos</th>
                    <th class="rotado" style="background-color:#fce4ec; color:#880e4f; border-color:#d81b60;">Stock C. Nuevas</th>
                    <th class="rotado" style="background-color:#fce4ec; color:#880e4f; border-color:#d81b60;">Stock C. Usadas</th>
                    <th class="rotado" style="background-color:#fce4ec; color:#880e4f; border-color:#d81b60;">Stock F. Regular</th>
                    <th class="rotado" style="background-color:#fce4ec; color:#880e4f; border-color:#d81b60;">Total Stock</th>
                </tr>
            </thead>
            <tbody id="tbodyAvanceDiario">
            </tbody>
        </table>
    </div>
</div>

<script>
    window.BASE_URL = "<?= BASE_URL ?>";
    window.APP_URL = "<?= APP_URL ?>";
    window.usuarioUsername = "<?= $_SESSION['user']['username'] ?? '' ?>";
    window.usuarioNombre = "<?= $_SESSION['user']['NombresApellidos'] ?? '' ?>";
</script>
<script src="<?= asset_url('js/avancediario.js?v=' . time()) ?>"></script>

<style>
    .table td, .table th {
        padding: 0.1rem 0.15rem;
        vertical-align: middle;
        text-align:center;
    }
    #tablaAvanceDiario tbody td span.ad-text {
        display:inline-block;
        min-width:50px;
        font-size:0.7rem;
        text-align:center;
        padding:2px 4px;
    }
    #tablaAvanceDiario tbody tr.fila-totales {
        font-weight: 700;
        background: #e8eaf6 !important;
        border-top: 2px solid #3f51b5;
    }
    #tablaAvanceDiario tbody tr.fila-totales td {
        border-bottom: 2px solid #3f51b5;
        padding: 0.25rem 0.3rem;
    }
    #tablaAvanceDiario tbody tr.fila-totales input.ad-input {
        font-weight: 700;
        background: #e8eaf6 !important;
        color: #1a237e;
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
    .btn-save { color: #2563eb; border-color: rgba(59,130,246,0.22); background: rgba(59,130,246,0.06); }
    .btn-save:active, .btn-save:focus, .btn-save:hover { background: rgba(59,130,246,0.14); }
    #tablaAvanceDiario tbody tr:hover {
        background: #e8f0fe;
    }

    /* Popover de detalle de cantidades - compacto */
    .popover {
        max-width: 380px !important;
        font-size: 11px !important;
    }
    .popover-header {
        font-size: 11px !important;
        padding: 4px 8px !important;
        background-color: #1a237e !important;
        color: #fff !important;
        font-weight: 700 !important;
        border-bottom: none !important;
    }
    .popover-body {
        padding: 6px 8px !important;
    }
    [data-clickable="true"]:hover {
        background-color: #e3f2fd !important;
        border-radius: 3px;
        outline: 1px solid #90caf9;
    }
</style>
