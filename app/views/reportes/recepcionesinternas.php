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
<!-- Estilos para el modal de vista previa (coincidente con registro) -->
<style>
    #printPreviewModal .modal-dialog { max-width: 620px !important; }
    #printPreviewModal .modal-body { padding: 8px !important; font-size: 0.75rem !important; }
    #printPreviewModal .modal-title { font-size: 0.9rem !important; }
    /* Ajustes internos del contenido de preview */
    #printPreviewModal #printPreviewModal_content { font-size: 0.75rem !important; line-height:1.08 !important; }
    #printPreviewModal #printPreviewModal_content table { font-size: 0.65rem !important; }
    #printPreviewModal #printPreviewModal_content .productos-header td { font-size:0.65rem !important; }
    #printPreviewModal #printPreviewModal_content img { max-height: 26px !important; }
    #printPreviewModal .modal-footer .btn { padding: 6px 12px; }
</style>
<style>
    /* Forzar borde del bloque de firmas dentro de cualquier modal */
    .modal .firma-block { border: 0.5pt solid #333 !important; border-radius: 10px !important; padding: 10px !important; background: transparent !important; }
    .modal .firma-block td { vertical-align: bottom !important; }
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
                    <h2 class="fw-bold mb-0" style="color:#1a237e; letter-spacing:0.5px; font-size:2rem;">Reporte Recepciones Internas</h2>
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
            <!-- Grilla plana tipo Excel con filtros en cabecera -->
            <div class="table-responsive" style="overflow-x:auto; overflow-y:visible; -webkit-overflow-scrolling:touch; width:100%; padding:4px 8px; box-sizing:border-box;">
                <table class="table table-hover table-bordered table-sm" id="grillaReporte" style="table-layout:auto; width:100%; border-collapse: collapse !important;">
                    <thead class="table-light">
                        <tr>
                            <th style="width:70px; min-width:70px;"></th>
                            <th data-filterable="true" data-field="NVale">N° Vale</th>
                            <th data-filterable="true" data-field="Fecha">Fecha</th>
                            <th data-filterable="true" data-field="Hora">Hora</th>
                            <th data-filterable="true" data-field="Turno">Turno</th>
                            <th data-filterable="true" data-field="Area">Área</th>
                            <th data-filterable="true" data-field="Subarea">Subárea</th>
                            <th data-filterable="true" data-field="Despachador">Despachador</th>
                            <th data-filterable="true" data-field="MedioTransporte">Medio Transporte</th>
                            <th data-filterable="true" data-field="Verificador">Verificador</th>
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
            <style>
                /* Asegurar que la tarjeta principal del reporte no se desplace fuera del viewport:
                   limitamos su ancho máximo y la centramos dentro del contenedor disponible. */
                .reporte-card { max-width: 1200px !important; margin-left: auto !important; margin-right: auto !important; }
                /* Padding cómodo para la tarjeta */
                .reporte-card .card-body { padding: 1rem !important; }
                /* Estilo general para toda la tabla: forzar una sola línea por registro
                   y permitir scroll horizontal dentro de .table-responsive */
                #grillaReporte {
                    border-collapse: collapse !important;
                    border-spacing: 0 !important;
                    /* Ancho mínimo ampliado para columnas de productos */
                    min-width: 2000px !important;
                    width: 100% !important;
                }
                /* Forzar que las celdas no hagan wrap y muestren ellipsis si se cortan */
                #grillaReporte th, #grillaReporte td {
                    white-space: nowrap !important;
                    overflow: hidden !important;
                    text-overflow: ellipsis !important;
                    border: 1px solid #dee2e6 !important;
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
                    font-size: 11px;
                    padding: 4px !important;
                }
                .choices__inner, .choices{ min-height:32px !important; height:32px !important; font-size:13px !important; }
                #grillaReporte td {
                    overflow: hidden;
                    text-overflow: ellipsis;
                    font-size: 11px !important;
                    padding: 2px 4px !important;
                    vertical-align: middle !important;
                    line-height: 1.2 !important;
                }
                /* Aplicar tamaño de texto ligeramente menor a la tabla */
                #grillaReporteBody {
                    font-size: 11px !important;
                }

                /* Evitar salto de línea en etiquetas de cabecera (span dentro de cada th) */
                #grillaReporte thead th .d-flex span { white-space: nowrap !important; }
                /* Permitir que las tablas de productos dentro de la grilla ajusten sus columnas al contenido
                   (este selector usa el id #grillaReporte para vencer reglas globales con mayor especificidad) */
                #grillaReporte .productos, #grillaReporte .productos th, #grillaReporte .productos td {
                    table-layout: auto !important;
                    white-space: normal !important;
                    word-break: break-word !important;
                    overflow-wrap: anywhere !important;
                    max-width: none !important;
                }
                /* Ajustes adicionales para mejor visualización */
                .table-sm th, .table-sm td {
                    padding: 2px 4px !important;
                }
                /* Asegurarse que las filas tengan un espaciado adecuado */
                #grillaReporte tr {
                    margin: 0 !important;
                    border-collapse: collapse !important;
                }
                /* Mejoras para responsividad en dispositivos móviles */
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
                .producto-row {
                    background: #f8f9fa;
                }
                .producto-row:hover {
                    background: #e9ecef;
                }
                /* Estilos para el botón de acciones y menú desplegable */
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
                    position: fixed !important; /* Forzar posición fija para evitar restricciones de contenedor */
                    z-index: 1050 !important; /* Asegurar que esté por encima de otros elementos */
                    min-width: 160px !important; /* Ancho mínimo para que las opciones sean legibles */
                    margin: 0 !important; /* Eliminar márgenes que podrían afectar el posicionamiento */
                    transform: none !important; /* Prevenir transformaciones que muevan el menú */
                    animation: fadeIn 0.2s ease-out !important; /* Añadir animación suave */
                }
                
                @keyframes fadeIn {
                    from { opacity: 0; transform: translateY(-10px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                
                /* Asegurarse de que las opciones del dropdown sean claramente visibles */
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
                
                /* Mejorar visibilidad al hacer hover */
                .dropdown-menu .dropdown-item:hover {
                    background-color: rgba(13, 110, 253, 0.08) !important;
                    color: #0d6efd !important;
                    border-left-color: #0d6efd !important;
                }
                
                /* Estilo para el botón de tres puntos cuando está activo */
                .action-btn:focus, .action-btn.show {
                    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
                }
                
                /* Añadir flecha visualmente conectando el menú con el botón */
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
                /* Estilos para la columna de acciones */
                #grillaReporte td:first-child {
                    padding: 6px 8px;
                }
                /* Asegurar que los botones tengan suficiente espacio */
                #grillaReporte .d-flex.flex-column {
                    width: 100%;
                }
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

                /* OCULTAR opciones de edición en el reporte: solo botones lápiz y opciones de editar */
                /* Mantener visible la columna de acciones (3 puntos) para otras operaciones */
                #grillaReporte .btn-editar-guia,
                #grillaReporte .btn-editar-producto,
                #grillaReporte .btn-lapiz-producto,
                #grillaReporte .btn-lapiz-RECEPCIÓN {
                    display: none !important;
                }
                /* También ocultar iconos lápiz si quedan en el DOM */
                #grillaReporte i.bi-pencil, #grillaReporte i.bi-pencil-fill {
                    display: none !important;
                }

                /* Forzar que las tablas de productos dentro de la fila expandida ajusten sus columnas al contenido */
                .producto-row table, .producto-row table th, .producto-row table td {
                    table-layout: auto !important;
                    white-space: normal !important;
                    word-break: break-word !important;
                    overflow-wrap: anywhere !important;
                    max-width: none !important;
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="<?= BASE_URL ?>/js/jspdf.plugin.autotable.min.js"></script>
<script src="<?= BASE_URL ?>/js/autocorrector.js?v=<?= time() ?>"></script>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/edicion-productos.css?v=<?= time() ?>">
<script src="<?= BASE_URL ?>/js/bloquear_fechas_futuras.js?v=<?= time() ?>"></script>
<script>
    // Pasar datos del usuario a JavaScript
    window.usuarioActual = "<?= $_SESSION['user']['NombresApellidos'] ?? '' ?>";
    window.usuarioUsername = "<?= $_SESSION['user']['username'] ?? '' ?>";
    window.BASE_URL = "<?= BASE_URL ?>";
    console.log("BASE_URL definida en JavaScript:", window.BASE_URL);
</script>
<!-- Scripts en orden correcto -->
<script src="<?= BASE_URL ?>/js/filtro_productos.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>/js/despachosinternos.js?v=4"></script>
<script src="<?= BASE_URL ?>/js/printPreview.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>/js/column-filters.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>/js/reportes_recepcionesinternas.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>/js/notificaciones.js?v=<?= time() ?>"></script>

<!-- Script para restringir fechas futuras -->
<script>
    // Asegurarse de que el campo de fecha no permita seleccionar fechas futuras
    document.addEventListener('DOMContentLoaded', function() {
        // Función para restringir fechas futuras
        function restringirFechasFuturas() {
            // Obtener la fecha actual en formato YYYY-MM-DD
            const hoy = new Date();
            const año = hoy.getFullYear();
            const mes = String(hoy.getMonth() + 1).padStart(2, '0');
            const dia = String(hoy.getDate()).padStart(2, '0');
            const fechaHoy = `${año}-${mes}-${dia}`;
            
            // Aplicar la restricción al campo de fecha
            const campoFecha = document.getElementById('filtroFecha');
            if (campoFecha) {
                campoFecha.setAttribute('max', fechaHoy);
                console.log('Fecha máxima establecida a:', fechaHoy);
                
                // Sobrescribir el comportamiento del campo de fecha para prevenir fechas futuras
                campoFecha.addEventListener('input', function() {
                    const fechaSeleccionada = new Date(this.value);
                    if (fechaSeleccionada > hoy) {
                        alert('No se permite seleccionar fechas futuras');
                        this.value = fechaHoy;
                    }
                });
            }
        }
        
        // Llamar a la función inmediatamente
        restringirFechasFuturas();
        
        // También llamarla después de un breve retraso para asegurarnos de que se aplique
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
