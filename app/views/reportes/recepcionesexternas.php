<style>
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
/* Estilos compactos para la grilla plana - idéntico en los 4 reportes */
.reporte-card { max-width: 1200px !important; margin-left: auto !important; margin-right: auto !important; }
.reporte-card .card-body { padding: 1rem !important; }
#grillaReporte { font-size: 12px !important; border-collapse: collapse !important; border-spacing: 0 !important; min-width: 2200px !important; width: 100% !important; }
#grillaReporte thead th {
    padding: 3px 6px !important;
    font-size: 12px !important;
    white-space: nowrap !important;
    vertical-align: middle !important;
}
#grillaReporte tbody td {
    padding: 1px 4px !important;
    font-size: 11px !important;
    line-height: 1.2 !important;
    white-space: nowrap !important;
}
#grillaReporte tbody tr { height: auto !important; }
.col-filter-counter { font-size: 11px !important; margin-top: 2px !important; }
.action-btn { width: 28px !important; height: 28px !important; padding: 2px !important; }
.action-btn i { font-size: 0.9rem !important; }
</style>
<?php
?>
<!-- Contenedor global para mensajes -->
<div id="mensajes-container"></div>
<div class="container-fluid py-3" style="max-width:100%; width:100%; padding-left:12px; padding-right:12px; margin:auto;">
    <div class="card shadow-sm border-0 mb-4 reporte-card">
        <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-bar-chart-line display-6 text-primary me-2"></i>
                    <h2 class="fw-bold mb-0" style="color:#1a237e; letter-spacing:0.5px; font-size:2rem;">Reporte Recepciones Externas</h2>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <nav aria-label="Paginación de reporte" class="me-2">
                        <ul class="pagination pagination-sm mb-0" id="paginacionReporte">
                        </ul>
                    </nav>
                    <button type="button" class="btn btn-sm btn-outline-primary px-2 py-1" id="btnToggleColumns" style="font-size:13px; line-height:1.4;">
                        <i class="bi bi-layout-three-columns me-1"></i> Columnas
                    </button>
                    <button type="button" class="btn btn-sm btn-success fw-bold px-3 py-1" id="btnExportExcel" style="font-size:13px; line-height:1.4;"><i class="bi bi-file-earmark-excel me-1"></i> Exportar Excel</button>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 mb-2" id="navegacionLotes" style="font-size:13px;">
                <span class="fw-bold me-1">Lote de vales:</span>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnLotePrimero" disabled title="Ir al primer lote">
                    <i class="bi bi-chevron-double-left"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnLoteAnterior" disabled title="Lote anterior">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <span id="loteInfo" class="fw-bold px-2">1 / 1</span>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnLoteSiguiente" disabled title="Siguiente lote">
                    <i class="bi bi-chevron-right"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnLoteUltimo" disabled title="Ir al Ãºltimo lote">
                    <i class="bi bi-chevron-double-right"></i>
                </button>
                <span class="text-muted ms-2" id="loteDetalle" style="font-size:12px;">(500 vales por lote)</span>
            </div>
            <div class="mb-3">
            <!-- Contenedor con altura automática y scroll solo si es necesario -->
            <div style="max-height: none; overflow-x: auto; border: 1px solid #dee2e6; border-radius: 4px;">
                <table class="table table-hover table-bordered mb-0" id="grillaReporte" style="table-layout:auto; width:100%; border-collapse: collapse !important;">
                    <thead class="table-light" style="position: sticky; top: 0; z-index: 10; background-color: #f8f9fa;">
                        <tr>
                            <th style="width:80px; min-width:80px;"></th>
                            <th data-filterable="true" data-field="NVale">N° Vale</th>
                            <th data-filterable="true" data-field="Fecha">Fecha</th>
                            <th data-filterable="true" data-field="Hora">Hora</th>
                            <th data-filterable="true" data-field="Turno">Turno</th>
                            <th data-filterable="true" data-field="Origen">Origen</th>
                            <th data-filterable="true" data-field="Recepcionista">Recepcionista</th>
                            <th data-filterable="true" data-field="Empresa">Empresa</th>
                            <th data-filterable="true" data-field="RUC">RUC</th>
                            <th data-filterable="true" data-field="Chofer">Chofer</th>
                            <th data-filterable="true" data-field="Brevete">Brevete</th>
                            <th data-filterable="true" data-field="NumeroGuia">N° Guía</th>
                            <th data-filterable="true" data-field="NumeroDocRef">N° Doc Ref</th>
                            <th data-filterable="true" data-field="CodigoProducto">Código Prod</th>
                            <th data-filterable="true" data-field="DescripcionProducto">Producto</th>
                            <th data-filterable="true" data-field="CantidadProducto">Cantidad</th>
                            <th data-filterable="true" data-field="TextoObservaciones">Tipo Obs</th>
                            <th data-filterable="true" data-field="CantidadObsProducto">Cant Obs</th>
                            <th data-filterable="true" data-field="TotalProducto">Total</th>
                            <th data-filterable="true" data-field="TextoObservacionesProducto">Observación</th>
                            <th data-filterable="true" data-field="Comentarios">Comentarios</th>
                        </tr>
                    </thead>
                    <tbody id="grillaReporteBody">
                        <tr>
                            <td colspan="21" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                                <p class="mt-2 text-muted">Cargando datos...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <style>
                /* Estilos estandarizados de grilla - idénticos a despachos internos */
                .reporte-card { 
                    max-width: 1200px !important; 
                    margin-left: auto !important; 
                    margin-right: auto !important; 
                    overflow: visible !important;
                }
                .reporte-card .card-body { 
                    padding: 1rem !important; 
                    overflow: visible !important;
                }
                #grillaReporte {
                    border-collapse: collapse !important;
                    border-spacing: 0 !important;
                    min-width: 1200px !important;
                    width: 100% !important;
                }
                /* Asegurar que el thead quede fijo al hacer scroll */
                #grillaReporte thead {
                    position: sticky !important;
                    top: 0 !important;
                    z-index: 100 !important;
                    background-color: #f8f9fa !important;
                }
                /* === COLUMNA DE ACCIONES VISIBLE === */
                #grillaReporte th:first-child, #grillaReporte td:first-child {
                    display: table-cell !important;
                    visibility: visible !important;
                    width: 80px !important;
                    min-width: 80px !important;
                    max-width: 80px !important;
                }
                /* Botón de acciones en la grilla */
                #grillaReporte .acc-btn-trigger {
                    width: 30px !important;
                    height: 30px !important;
                    padding: 2px !important;
                    font-size: 13px !important;
                    line-height: 1 !important;
                    display: inline-flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    cursor: pointer !important;
                    border-radius: 4px !important;
                }
                #grillaReporte .acc-dropdown {
                    display: inline-block !important;
                    position: relative !important;
                }
                #grillaReporte .acc-cell {
                    text-align: center !important;
                    vertical-align: middle !important;
                    overflow: visible !important;
                }
                .acc-menu-items.show {
                    display: block !important;
                }
                .acc-menu-item:hover {
                    background-color: #f0f0f0 !important;
                }
                    padding: 0 !important;
                    margin: 0 !important;
                    border: 0 !important;
                }
                #grillaReporte .btn-editar-guia,
                #grillaReporte .btn-editar-producto,
                #grillaReporte .btn-lapiz-producto,
                #grillaReporte .btn-lapiz-RECEPCIÓN,
                #grillaReporte .btn-editar-RECEPCIÓN,
                #grillaReporte a.btn-editar-RECEPCIÓN {
                    display: none !important;
                }
                #grillaReporte i.bi-pencil, #grillaReporte i.bi-pencil-fill {
                    display: none !important;
                }
                #grillaReporte th, #grillaReporte td {
                    white-space: nowrap !important;
                    border: 1px solid #dee2e6 !important;
                }
                .campo-codigo,
                #grillaReporte td[data-campo="cantidad"],
                #grillaReporte td[data-campo="unidad"],
                .campo-editable[data-campo="cantidad"],
                .campo-editable[data-campo="unidad"] {
                    text-align: center !important;
                    vertical-align: middle !important;
                }
                #grillaReporte tbody tr, #grillaReporte tbody td {
                    padding-top: 2px !important;
                    padding-bottom: 2px !important;
                    height: auto !important;
                    min-height: 1em !important;
                    line-height: 1.2 !important;
                    margin: 0 !important;
                    border-width: 1px !important;
                }
                #grillaReporte th {
                    overflow: hidden;
                    text-overflow: ellipsis;
                    font-size: 13px;
                    padding: 4px !important;
                }
                .choices__inner, .choices{ min-height:32px !important; height:32px !important; font-size:13px !important; }
                #grillaReporte td {
                    overflow: hidden;
                    text-overflow: ellipsis;
                    font-size: 12px !important;
                    padding: 2px 4px !important;
                    vertical-align: middle !important;
                    line-height: 1.2 !important;
                }
                #grillaReporteBody {
                    font-size: 12px !important;
                }
                .table-sm th, .table-sm td {
                    padding: 2px 4px !important;
                }
                #grillaReporte tr {
                    margin: 0 !important;
                    border-collapse: collapse !important;
                }
                @media (max-width: 768px) {
                    #grillaReporte th, #grillaReporte td {
                        font-size: 0.75rem !important;
                        padding: 1px 2px !important;
                    }
                    #grillaReporte {
                        width: 100% !important;
                        overflow-x: auto !important;
                    }
                }
                .fila-edicion {
                    background: #e8f4fd !important;
                    border: 2px solid #0d6efd !important;
                }
                .fila-edicion input, .fila-edicion select {
                    border: 1px solid #0d6efd;
                    font-size: 0.85rem;
                    height: calc(1.5rem + 2px);
                    padding: 0.25rem 0.5rem;
                }
                .fila-edicion .btn {
                    padding: 2px 5px;
                    font-size: 11px;
                }
                .guia-row {
                    background: #f0f8ff;
                }
                .guia-row:hover {
                    background: #e6f3ff;
                }
                .producto-row {
                    background: #f8f9fa;
                }
                .producto-row:hover {
                    background: #e9ecef;
                }
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
                    display: none !important;
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
                    z-index: 1050 !important;
                    min-width: 160px !important;
                    animation: fadeIn 0.2s ease-out !important;
                }
                
                /* Forzar que el dropdown se renderice correctamente */
                .dropdown {
                    position: static !important;
                }
                
                /* Permitir que el dropdown se muestre fuera */
                #grillaReporteBody td:first-child {
                    position: static !important;
                    overflow: visible !important;
                }
                
                #grillaReporte td {
                    position: static !important;
                }
                
                /* Asegurar que el dropdown tenga z-index alto */
                .dropdown-menu {
                    position: fixed !important;
                    z-index: 9999 !important;
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
                
                .dropdown-item {
                    padding: 0.5rem 1rem;
                    color: #212529;
                    font-weight: 400;
                    transition: all 0.2s ease-in-out;
                }
                .dropdown-item:hover {
                    background-color: #f8f9fa;
                    color: #16181b;
                }
                .dropdown-item i {
                    width: 20px;
                    text-align: center;
                }
                .dropdown-divider {
                    margin: 0.25rem 0;
                }
                #grillaReporte td:first-child {
                    padding: 6px 8px;
                }
                #grillaReporte .d-flex.flex-column {
                    width: 100%;
                }
                .btn-guardar-recepcion {
                    background-color: #198754 !important;
                    border-color: #198754 !important;
                    color: white !important;
                    box-shadow: 0 1px 2px rgba(0,0,0,.1);
                }
                .btn-guardar-recepcion:hover {
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
                
                /* Estilos para cabeceras de guías y productos */
                .cabecera-guias {
                    background-color: #e3f2fd !important;
                    font-weight: bold !important;
                    font-size: 13px !important;
                }
                .cabecera-productos {
                    background-color: #f3e5f5 !important;
                    font-weight: bold !important;
                    font-size: 12px !important;
                }
                .guia-row {
                    background-color: #f8f9fa !important;
                }
                .producto-row {
                    background-color: #fafafa !important;
                }
            </style>
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
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
    // Pasar datos del usuario a JavaScript
    window.usuarioActual = "<?= $_SESSION['user']['NombresApellidos'] ?? '' ?>";
    window.usuarioUsername = "<?= $_SESSION['user']['username'] ?? '' ?>";
    window.BASE_URL = "<?= BASE_URL ?>";
    console.log("BASE_URL definida en JavaScript:", window.BASE_URL);
</script>
<!-- Scripts -->
<script src="<?= BASE_URL ?>/js/printPreview.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>/js/column-filters.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>/js/reportes-recepcionesexternas-v4.js?v=<?= time() . rand(1000,9999) ?>"></script>
<script src="<?= BASE_URL ?>/js/notificaciones.js?v=<?= time() ?>"></script>

<!-- Script para restringir fechas futuras -->
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
                console.log('Fecha máxima establecida a:', fechaHoy);
                
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
