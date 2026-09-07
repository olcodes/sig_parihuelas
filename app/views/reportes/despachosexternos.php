<style>
/* Evitar scroll horizontal en toda la página sólo para esta vista: la tabla dentro de .table-responsive
    tendrá su propia barra horizontal. Se evita forzar overflow-x:hidden en html/body porque puede
    provocar que el bloque principal quede recortado a la derecha en vistas pequeñas. */
/* No forzar overflow en html/body aquí; dejamos que .table-responsive gestione el scroll horizontal. */

@media print {
    body * {
        visibility: hidden !important;
    }
    #print-only-container, #print-only-container * {
        visibility: visible !important;
    }
    #print-only-container {
        position: absolute !important;
        left: 0; top: 0; width: 100vw; height: 100vh;
        background: white !important;
        z-index: 99999 !important;
        display: block !important;
        padding: 0 !important;
        margin: 0 !important;
        box-sizing: border-box !important;
        overflow: visible !important;
    }
}
</style>
<!-- Estilos compactos para la grilla plana -->
<style>
    #grillaReporte { font-size: 12px !important; }
    #grillaReporte thead th {
        padding: 3px 6px !important;
        font-size: 12px !important;
        white-space: nowrap !important;
        vertical-align: middle !important;
    }
    #grillaReporte tbody td {
        padding: 1px 4px !important;
        font-size: 11px !important;
        line-height: 1.3 !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        max-width: 200px !important;
    }
    #grillaReporte tbody tr { height: auto !important; }
    .col-filter-counter { font-size: 11px !important; margin-top: 2px !important; }
    .action-btn { width: 28px !important; height: 28px !important; padding: 2px !important; }
    .action-btn i { font-size: 0.9rem !important; }
</style>
<!-- Estilos para el modal de vista previa (coincidente con despachos internos) -->
<style>
    #printPreviewModal .modal-dialog { max-width: 620px !important; }
    #printPreviewModal .modal-body { padding: 8px !important; font-size: 0.75rem !important; }
    #printPreviewModal .modal-title { font-size: 0.9rem !important; }
    /* Ajustes internos del contenido de preview */
    #printPreviewModal #printPreviewModal_content { font-size: 0.75rem !important; line-height:1.08 !important; }
    #printPreviewModal #printPreviewModal_content table { font-size: 0.70rem !important; }
    #printPreviewModal #printPreviewModal_content .productos-header td { font-size:0.70rem !important; }
    #printPreviewModal #printPreviewModal_content img { max-height: 26px !important; }
    #printPreviewModal .modal-footer .btn { padding: 6px 12px; }
</style>
<style>
/* Estilos para mostrar la cabecera de la grilla compacta desde el primer pintado */
/* Aumentamos ligeramente las fuentes de cabecera y cuerpo según pedido */
#grillaReporte thead th{ padding:4px; vertical-align:middle; font-size:13px; }
#grillaReporte thead th:first-child{ width:60px; }
#grillaReporte thead .filtro-grilla{ height:32px; padding:4px 8px; font-size:13px; }
#grillaReporte thead .form-control-sm{ height:32px; padding:4px 8px; font-size:13px; }
/* Ajustes para Choices.js placeholder antes y después de inicializar */
.choices__inner, .choices{ min-height:32px !important; height:32px !important; font-size:13px !important; }
.choices__list--single { line-height:32px !important; }
#grillaReporte tbody td{ font-size:12px !important; padding:2px 4px !important; vertical-align:middle !important; line-height:1.2 !important; }
/* Evitar ajuste de línea en celdas para que los registros se muestren en una sola línea
    y permitir scroll horizontal dentro de .table-responsive para ver todo el contenido */
#grillaReporte th, #grillaReporte td { white-space: nowrap; }
.table-sm th, .table-sm td{ padding:2px 4px !important; }
/* Estilos para el botón de acciones (paridad con despachos internos) */
.action-btn {
    background-color: #0d6efd !important;
    border-color: #0d6efd !important;
    color: #fff !important;
    width: 32px !important;
    height: 32px !important;
    padding: 4px !important;
    border-radius: 4px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    box-shadow: 0 1px 2px rgba(0,0,0,.1);
    margin: 0 auto;
}
.action-btn i {
    font-size: 1.1rem;
}
.action-btn::after {
    display: none !important; /* Ocultar la flecha del dropdown */
}
.dropdown-toggle.btn-primary:hover {
    background-color: #0b5ed7 !important;
    border-color: #0a58ca !important;
}
.dropdown-menu {
    border-radius: 0.5rem !important;
    border: 1px solid rgba(0,0,0,.1) !important;
    box-shadow: 0 5px 15px rgba(0,0,0,.1) !important;
    padding: 0.5rem 0 !important;
    position: fixed !important;
    z-index: 1050 !important;
    min-width: 160px !important;
    margin: 0 !important;
    transform: none !important;
    animation: fadeIn 0.2s ease-out !important;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.dropdown-menu .dropdown-item {
    background-color: white !important;
    padding: 10px 15px !important;
    font-weight: normal !important;
    white-space: nowrap !important;
    transition: all 0.15s ease-in-out !important;
    border-left: 3px solid transparent !important;
    font-size: 0.82rem !important;
    padding: 8px 12px !important;
}
.dropdown-menu .dropdown-item:hover {
    background-color: rgba(13, 110, 253, 0.08) !important;
    color: #0d6efd !important;
    border-left-color: #0d6efd !important;
}
.action-btn:focus, .action-btn.show {
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
}
.dropdown-menu::before {
    content: '';
    position: absolute;
    top: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 16px;
    height: 8px;
    clip-path: polygon(50% 0%, 0% 100%, 100% 100%);
    background-color: white;
    border-top: 1px solid rgba(0,0,0,0.1);
    border-left: 1px solid rgba(0,0,0,0.1);
    border-right: 1px solid rgba(0,0,0,0.1);
}
.dropdown-item i {
    width: 20px;
    text-align: center;
}
/* Asegurar la columna de acciones tenga padding similar; centrar solo cuando contiene el dropdown */
#grillaReporte td:first-child { padding: 2px 4px; }
#grillaReporte td:first-child > .dropdown { display:flex; align-items:center; justify-content:center; height:100%; }
#grillaReporte td:first-child > .dropdown .action-btn { margin: 0 !important; }
/* Estilos para la fila de productos (paridad con despachos internos) */
.producto-row { background: #f8f9fa; }
.producto-row:hover { background: #e9ecef; }
.producto-row td { padding: 2px 4px; }
.producto-row .vale-header { color: #1a237e !important; font-weight:700; margin: 6px 0 8px 0; font-size:1rem; }
.producto-table-container { padding-top:6px; }
.producto-table-container table { border-collapse: collapse; width:100%; }
.producto-table-container table, .producto-table-container table th, .producto-table-container table td { border: 1px solid #dee2e6 !important; }
.producto-table-container table thead th { font-weight:700; background:transparent; border-bottom: 2px solid #e9ecef; }
.producto-table-container table tbody td { vertical-align: middle; }

/* Forzar que las tablas de productos ajusten sus columnas al contenido (anulan el white-space:nowrap global) */
.producto-table-container table th, .producto-table-container table td {
    white-space: normal !important;
    word-break: break-word !important;
    overflow-wrap: anywhere !important;
    max-width: none !important;
}
/* Forzar fondo gris en la cabecera de la grilla (vista en pantalla).
   Cubrimos múltiples selectores: thead, tr.table-light, th, y .table-light aplicado en distintos niveles. */
#grillaReporte thead th,
#grillaReporte thead td,
#grillaReporte thead tr th,
#grillaReporte thead tr td,
#grillaReporte thead tr.table-light th,
#grillaReporte thead.table-light th,
#grillaReporte thead.table-light,
.table-responsive #grillaReporte thead th {
    background: #f2f2f2 !important;
    background-color: #f2f2f2 !important;
    background-image: none !important;
    color: #222 !important;
}
/* Asegurar que cualquier gradiente/imagen de fondo de Bootstrap se anule */
.table-responsive #grillaReporte thead th { background-image: none !important; }
/* Nota: evitamos selectores no estándar como :contains; si hace falta centrar
   una columna específica, podemos usar nth-child o añadir una clase al <th>. */
.btn-action-producto { width:34px; height:34px; padding:4px; border-radius:6px; display:inline-flex; align-items:center; justify-content:center; }
.btn-action-producto i { font-size:1rem; }

/* Estilos para modo edición (paridad con despachos internos) */
.fila-edicion {
    background: #e8f4fd !important;
    border: 2px solid #0d6efd !important;
}
.fila-edicion input, .fila-edicion select {
    border: 1px solid #0d6efd;
}
.fila-edicion .btn {
    padding: 2px 5px;
    font-size: 11px;
}
.fila-edicion input, .fila-edicion select {
    font-size: 0.85rem;
    height: calc(1.5rem + 2px);
    padding: 0.25rem 0.5rem;
}
.modal .firma-block { border: 0.5pt solid #333 !important; border-radius: 10px !important; padding: 10px !important; background: transparent !important; }
.modal .firma-block td { vertical-align: bottom !important; }
.btn-guardar-despacho {
    background-color: #198754 !important;
    border-color: #198754 !important;
    color: white !important;
    box-shadow: 0 1px 2px rgba(0,0,0,.1);
}
.btn-guardar-despacho:hover {
    background-color: #157347 !important;
    border-color: #146c43 !important;
}
.btn-cancelar-edicion {
    background-color: #6c757d !important;
    border-color: #6c757d !important;
    color: white !important;
    box-shadow: 0 1px 2px rgba(0,0,0,.1);
}
.btn-cancelar-edicion:hover {
    background-color: #5c636a !important;
    border-color: #565e64 !important;
}
/* Asegurar que los botones de guardar/cancelar en modo edición entren en su celda */
.fila-edicion td:first-child .btn {
    width: auto !important;
    height: auto !important;
    padding: 4px 8px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
}
.fila-edicion td { padding: 4px 6px !important; }
/* Forzar ancho mínimo de la columna de acciones para que los botones quepan */
#grillaReporte col:first-child, #grillaReporte th:first-child, #grillaReporte td:first-child {
    min-width: 60px !important;
    max-width: 80px !important;
    width: 60px !important;
}
/* Evitar que el contenido dentro de la primera celda se rompa en varias líneas */
#grillaReporte td:first-child { white-space: nowrap; overflow: hidden; }
/* Asegurar que los botones sean inline-flex y no ocupen todo el ancho */
.fila-edicion td:first-child .btn {
    display: inline-flex !important;
    width: auto !important;
    height: auto !important;
    padding: 2px 6px !important;
    font-size: 0.85rem !important;
}
.fila-edicion td:first-child .btn i { margin: 0 !important; }

/* Anchos prioritarios por columna para mejorar visibilidad y reducir scroll horizontal necesario */
#grillaReporte th:nth-child(6), #grillaReporte td:nth-child(6) { min-width: 220px !important; }
#grillaReporte th:nth-child(8), #grillaReporte td:nth-child(8) { min-width: 320px !important; }
#grillaReporte th:nth-child(9), #grillaReporte td:nth-child(9) { min-width: 160px !important; }
#grillaReporte th:nth-child(10), #grillaReporte td:nth-child(10) { min-width: 160px !important; }
#grillaReporte th:nth-child(12), #grillaReporte td:nth-child(12) { min-width: 180px !important; }

/* Columnas menos prioritarias pueden permitir wrap para ahorrar ancho */
#grillaReporte th:nth-child(2), #grillaReporte td:nth-child(2),
#grillaReporte th:nth-child(3), #grillaReporte td:nth-child(3),
#grillaReporte th:nth-child(4), #grillaReporte td:nth-child(4),
#grillaReporte th:nth-child(5), #grillaReporte td:nth-child(5),
#grillaReporte th:nth-child(7), #grillaReporte td:nth-child(7),
#grillaReporte th:nth-child(11), #grillaReporte td:nth-child(11),
#grillaReporte th:nth-child(13), #grillaReporte td:nth-child(13),
#grillaReporte th:nth-child(14), #grillaReporte td:nth-child(14),
#grillaReporte th:nth-child(15), #grillaReporte td:nth-child(15),
#grillaReporte th:nth-child(16), #grillaReporte td:nth-child(16) {
    white-space: normal;
}
/* Asegurar que la tarjeta principal del reporte no se desplace fuera del viewport:
    limitamos su ancho máximo y la centramos dentro del contenedor disponible. */
.reporte-card { max-width: 1200px !important; margin-left: auto !important; margin-right: auto !important; }
/* Restaurar padding interno de la tarjeta a un valor cómodo para la UI */
.reporte-card .card-body { padding: 1rem !important; }
/* Evitar que el contenido interno de la tarjeta provoque scroll en la página:
    la tarjeta limita su ancho, y la tabla dentro usa su propia barra horizontal
    dentro de .table-responsive. */
.reporte-card { overflow-x: hidden !important; }
.table-responsive { max-width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
/* No permitir salto de línea en celdas; dejar que la tabla calcule anchos según contenido
   y usar la barra horizontal del contenedor si es necesario. No forzar max-width. */
#grillaReporte thead th, #grillaReporte tbody td {
    white-space: nowrap !important;
    text-overflow: ellipsis; /* solo aplica si la celda queda limitada por el contenedor */
}
</style>
<?php
?>
<!-- Contenedor global para mensajes -->
<div id="mensajes-container"></div>
<div class="container-fluid py-3" style="max-width:none; width:100%; padding-left:12px; padding-right:12px; margin:auto;">
    <div class="card shadow-sm border-0 mb-4 reporte-card">
        <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-bar-chart-line display-6 text-primary me-2"></i>
                    <h2 class="fw-bold mb-0" style="color:#1a237e; letter-spacing:0.5px; font-size:2rem;">Reporte Despachos Externos</h2>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <!-- Contenedor de paginación (ubicado arriba a la izquierda del botón Exportar) -->
                    <nav aria-label="Paginación de reporte" class="me-2">
                        <ul class="pagination pagination-sm mb-0" id="paginacionReporte">
                            <!-- Paginación dinámica -->
                        </ul>
                    </nav>
                    <button type="button" class="btn btn-sm btn-primary px-2 py-1" id="btnToggleColumns" style="font-size:13px; line-height:1.4;">
                        <i class="bi bi-layout-three-columns me-1"></i> Columnas
                    </button>
                    <button type="button" class="btn btn-sm btn-success fw-bold px-3 py-1" id="btnExportExcel" style="font-size:13px; line-height:1.4;"><i class="bi bi-file-earmark-excel me-1"></i> Exportar Excel</button>
                </div>
            </div>
            <div class="mb-3">
            <!-- Filtros ahora en la cabecera de la grilla -->
            <!-- Añadimos padding-right para que el bloque no quede pegado al borde y
                 permitimos overflow-x auto para que la tabla cree su propia barra horizontal
                 cuando su min-width supere el ancho del contenedor. -->
            <div class="table-responsive" style="overflow-x:auto; overflow-y:visible; -webkit-overflow-scrolling:touch; width:100%; padding:4px 8px; box-sizing:border-box;">
                <table class="table table-hover table-bordered table-sm" id="grillaReporte" style="table-layout:auto; width:100%; border-collapse: collapse !important; min-width:0;">
                    <colgroup>
                        <!-- Usar ancho automático por contenido -->
                        <col style="width:auto">
                        <col style="width:auto">
                        <col style="width:auto">
                        <col style="width:auto">
                        <col style="width:auto">
                        <col style="width:auto">
                        <col style="width:auto">
                        <col style="width:auto">
                        <col style="width:auto">
                        <col style="width:auto">
                        <col style="width:auto">
                        <col style="width:auto">
                        <col style="width:auto">
                        <col style="width:auto">
                        <col style="width:auto">
                        <col style="width:auto">
                        <col style="width:auto">
                        <col style="width:auto">
                        <col style="width:auto">
                    </colgroup>
                    <thead class="table-light report-filters" style="background:#f2f2f2; color:#222; -webkit-print-color-adjust: exact; print-color-adjust: exact; color-adjust: exact;">
                        <tr>
                            <th style="width:70px; min-width:70px;"></th>
                            <th data-filterable="true" data-field="NVale">N° Vale</th>
                            <th data-filterable="true" data-field="Fecha">Fecha</th>
                            <th data-filterable="true" data-field="Turno">Turno</th>
                            <th data-filterable="true" data-field="Despachador">Despachador</th>
                            <th data-filterable="true" data-field="Destino">Destino</th>
                            <th data-filterable="true" data-field="RUC">RUC</th>
                            <th data-filterable="true" data-field="Direccion">Dirección</th>
                            <th data-filterable="true" data-field="Chofer">Chofer</th>
                            <th data-filterable="true" data-field="Brevete">Brevete</th>
                            <th data-filterable="true" data-field="Transportista">Transportista</th>
                            <th data-filterable="true" data-field="RUC_Transportista">RUC Transportista</th>
                            <th data-filterable="true" data-field="Placa_Tracto">Placa Tracto</th>
                            <th data-filterable="true" data-field="Constancia_Inscripcion">Const. Insc. Tracto</th>
                            <th data-filterable="true" data-field="Placa_Carreta">Placa Carreta</th>
                            <th data-filterable="true" data-field="Constancia_Inscripcion_2">Const. Insc. Carreta</th>
                            <th data-filterable="true" data-field="GR">Guía Remisión</th>
                            <th data-filterable="true" data-field="CodigoProducto">Código Prod.</th>
                            <th data-filterable="true" data-field="NombreProducto">Producto</th>
                            <th data-filterable="true" data-field="UnidadMedida">U.M.</th>
                            <th data-filterable="true" data-field="Cantidad">Cantidad</th>
                            <th data-filterable="true" data-field="ComentariosProducto">Comentarios</th>
                            <th data-filterable="true" data-field="Estado">Estado</th>
                        </tr>
                    </thead>
                    <tbody id="grillaReporteBody">
                        <!-- Aquí se cargan los datos dinámicamente -->
                    </tbody>
                </table>
            </div>
            <nav aria-label="Paginación de reporte" class="mt-3">
                <ul class="pagination justify-content-center" id="paginacionReporteBottom">
                    <!-- Paginación dinámica -->
                </ul>
            </nav>
        </div>
    </div>
</div>
<!-- Aquí continúa la lógica JS y PHP para cargar datos, exportar, imprimir, etc. -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="<?= BASE_URL ?>/js/jspdf.plugin.autotable.min.js"></script>
<script src="<?= BASE_URL ?>/js/autocorrector.js?v=<?= time() ?>"></script>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/edicion-productos.css?v=<?= time() ?>">
<script src="<?= BASE_URL ?>/js/bloquear_fechas_futuras.js?v=<?= time() ?>"></script>
<script>
    // Pasar datos del usuario a JavaScript y definir BASE_URL
    window.usuarioActual = "<?= $_SESSION['user']['NombresApellidos'] ?? '' ?>";
    window.usuarioUsername = "<?= $_SESSION['user']['username'] ?? '' ?>";
    window.BASE_URL = "<?= BASE_URL ?>";
    console.log("BASE_URL definida en JavaScript:", window.BASE_URL);
</script>
<!-- Scripts adicionales necesarios -->
<script src="<?= BASE_URL ?>/js/filtro_productos.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>/js/despachosexternos.js?v=<?= time() ?>"></script>
<!-- Módulo reutilizable de vista previa/print (modal two-up) -->
<script src="<?= BASE_URL ?>/js/printPreview.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>/js/column-filters.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>/js/reportes_despachosexternos.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>/js/notificaciones.js?v=<?= time() ?>"></script>

<!-- Inicializar restricción de fechas similares -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        function restringirFechasFuturas() {
            const hoy = new Date();
            const año = hoy.getFullYear();
            const mes = String(hoy.getMonth() + 1).padStart(2, '0');
            const dia = String(hoy.getDate()).padStart(2, '0');
            const fechaHoy = `${año}-${mes}-${dia}`;
            const campoFecha = document.getElementById('filtroFecha');
            if (campoFecha) {
                campoFecha.setAttribute('max', fechaHoy);
                campoFecha.addEventListener('input', function() {
                    const fechaSeleccionada = new Date(this.value);
                    if (fechaSeleccionada > hoy) {
                        alert('No se permite seleccionar fechas futuras');
                        this.value = fechaHoy;
                    }
                });
            }
        }
        restringirFechasFuturas();
        setTimeout(restringirFechasFuturas, 500);
    });
</script>
<!-- OVERRIDE COMPACTO -->
<style>
#grillaReporte tbody td { padding: 1px 4px !important; font-size: 11px !important; line-height: 1.2 !important; }
#grillaReporte thead th { padding: 3px 6px !important; font-size: 12px !important; }
.action-btn { width: 28px !important; height: 28px !important; padding: 2px !important; }
.action-btn i { font-size: 0.9rem !important; }
.col-filter-counter { font-size: 11px !important; margin-top: 2px !important; }
</style>
