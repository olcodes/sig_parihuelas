<style>
/* Estilos para diferenciar las grillas jerárquicas */
tr.guia-row.cabecera-guias td {
    background-color: #b0bec5 !important;
    border-top: 2px solid #546e7a !important;
}

tr.guia-row.cabecera-guias {
    background-color: #b0bec5 !important;
    font-weight: bold !important;
}

tr.guia-row:not(.cabecera-guias) {
    background-color: #eceff1 !important;
}

tr.guia-row:not(.cabecera-guias) td {
    background-color: #eceff1 !important;
}

tr.producto-row.cabecera-productos td {
    background-color: #a5d6a7 !important;
    border-top: 2px solid #388e3c !important;
}

tr.producto-row.cabecera-productos {
    background-color: #a5d6a7 !important;
    font-weight: bold !important;
}

tr.producto-row:not(.cabecera-productos) {
    background-color: #e8f5e9 !important;
}

tr.producto-row:not(.cabecera-productos) td {
    background-color: #e8f5e9 !important;
}
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
                    <i class="bi bi-grid-3x3-gap-fill display-6 text-primary me-2"></i>
                    <h2 class="fw-bold mb-0" style="color:#2563eb; letter-spacing:0.5px; font-size:2rem;">Reporte Picking</h2>
                    <div class="d-flex align-items-center gap-2 ms-3 d-none" title="Filtros ocultos: Fecha/Turno (conservar IDs para reactivar lógica)">
                        <label class="form-label mb-0 fw-bold" style="font-size:0.9rem;">Fecha:</label>
                        <input type="date" class="form-control form-control-sm" id="filtroFecha" style="width:150px;">
                        <label class="form-label mb-0 fw-bold ms-2" style="font-size:0.9rem;">Turno:</label>
                        <select class="form-select form-select-sm" id="filtroTurno" style="width:120px;">
                            <option value="">Todos</option>
                            <option value="MAÑANA">MAÑANA</option>
                            <option value="TARDE">TARDE</option>
                            <option value="NOCHE">NOCHE</option>
                        </select>
                    </div>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <nav aria-label="Paginación de reporte" class="me-2">
                        <ul class="pagination pagination-sm mb-0" id="paginacionReporte">
                        </ul>
                    </nav>
                    <button type="button" class="btn btn-success fw-bold px-4" id="btnExportExcel"><i class="bi bi-file-earmark-excel me-2"></i> Exportar Excel</button>
                </div>
            </div>
            <div class="mb-3">
            <!-- Contenedor con altura automática y scroll solo si es necesario -->
            <div style="max-height: none; overflow-x: auto; border: 1px solid #dee2e6; border-radius: 4px;">
                <table class="table table-hover table-bordered table-sm mb-0" id="grillaReporte" style="table-layout:auto; width:100%; border-collapse: collapse !important;">
                    <thead class="table-light" style="position: sticky; top: 0; z-index: 10; background-color: #f8f9fa;">
                        <tr>
                            <th><div class="d-flex flex-column align-items-center"><span>Fecha</span><input type="date" class="form-control form-control-sm filtro-grilla mt-1" data-campo="Fecha" placeholder="Buscar"></div></th>
                            <th><div class="d-flex flex-column align-items-center"><span>N° Vale</span><input type="text" class="form-control form-control-sm filtro-grilla mt-1" data-campo="NVale" placeholder="Buscar"></div></th>
                            <th><div class="d-flex flex-column align-items-center"><span>N° Doc. Ref.</span><input type="text" class="form-control form-control-sm filtro-grilla mt-1" data-campo="NumeroDocRef" placeholder="Buscar"></div></th>
                            <th><div class="d-flex flex-column align-items-center"><span>N° Guía</span><input type="text" class="form-control form-control-sm filtro-grilla mt-1" data-campo="NumeroGuia" placeholder="Buscar"></div></th>
                            <th><div class="d-flex flex-column align-items-center"><span>Origen</span><input type="text" class="form-control form-control-sm filtro-grilla mt-1" data-campo="Origen" placeholder="Buscar"></div></th>
                            <th><div class="d-flex flex-column align-items-center"><span>Observaciones</span><input type="text" class="form-control form-control-sm filtro-grilla mt-1" data-campo="Observaciones" placeholder="Buscar"></div></th>
                            <th><div class="d-flex flex-column align-items-center"><span>Turno</span><input type="text" class="form-control form-control-sm filtro-grilla mt-1" data-campo="Turno" placeholder="Buscar"></div></th>
                        </tr>
                    </thead>
                    <tbody id="grillaReporteBody">
                        <!-- Aquí se cargan los datos dinámicamente -->
                    </tbody>
                </table>
            </div>
            <style>
                .reporte-card { 
                    max-width: 100% !important; 
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
                    min-width: 800px !important;
                    width: 100% !important;
                }
                /* Asegurar que el thead quede fijo al hacer scroll */
                #grillaReporte thead {
                    position: sticky !important;
                    top: 0 !important;
                    z-index: 100 !important;
                    background-color: #f8f9fa !important;
                }
                #grillaReporte th, #grillaReporte td {
                    white-space: nowrap !important;
                    overflow: hidden !important;
                    text-overflow: ellipsis !important;
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
                
                /* Cursor pointer para filas clicables */
                #grillaReporteBody tr[data-vale-id]:hover {
                    cursor: pointer;
                    background-color: #e3f2fd !important;
                }
                #grillaReporteBody tr.guia-row:hover {
                    cursor: pointer;
                    background-color: #bbdefb !important;
                }
            </style>
                </table>
            </div>
            <nav aria-label="Paginación de reporte" class="mt-3">
                <ul class="pagination justify-content-center" id="paginacionReporte">
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
<script src="<?= BASE_URL ?>/js/reportes-picking.js?v=<?= time() . rand(1000,9999) ?>"></script>
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
