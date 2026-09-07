<!-- Contenedor global para mensajes -->
<div id="mensajes-container"></div>
<div class="container-fluid py-3" style="max-width:100%; width:100%; padding-left:12px; padding-right:12px; margin:auto; overflow:hidden;">
    <div class="card shadow-sm border-0 mb-4 reporte-card">
        <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-grid-3x3-gap-fill display-6 text-primary me-2"></i>
                    <h2 class="fw-bold mb-0" style="color:#2563eb; letter-spacing:0.5px; font-size:2rem;">Reporte Recepciones Ext.</h2>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <nav aria-label="Paginacion de reporte" class="me-2">
                        <ul class="pagination pagination-sm mb-0" id="paginacionReporte">
                        </ul>
                    </nav>
                    <button class="btn btn-success d-flex align-items-center" id="btnExportarExcel" style="gap:8px; padding:8px 16px; border-radius:8px;">
                        <i class="bi bi-file-earmark-excel-fill" style="font-size:1.1rem;"></i>
                        <span style="font-weight:600;">Exportar Excel</span>
                    </button>
                </div>
            </div>

            <div style="overflow-x:auto; max-height:calc(100vh - 280px); border:1px solid #dee2e6; border-radius:8px; width:100%;">
                <table class="table table-bordered table-hover mb-0" id="grillaReporte" style="table-layout:auto; border-collapse: collapse !important;">
                    <colgroup>
                        <col style="max-width:110px;">
                        <col style="max-width:70px;">
                        <col style="max-width:80px;">
                        <col style="max-width:100px;">
                        <col style="max-width:120px;">
                        <col style="max-width:130px;">
                        <col style="max-width:160px;">
                        <col style="max-width:160px;">
                        <col style="max-width:200px;">
                        <col style="max-width:80px;">
                        <col style="max-width:80px;">
                        <col style="max-width:80px;">
                        <col style="max-width:80px;">
                        <col style="max-width:90px;">
                    </colgroup>
                    <thead class="table-light" style="position:sticky; top:0; background:#f8f9fa; z-index:10;">
                        <tr style="background:#2563eb; color:#fff; font-weight:600;">
                            <th data-filterable="true" data-field="Fecha" style="text-align:center; vertical-align:middle;">Fecha</th>
                            <th data-filterable="true" data-field="Turno" style="text-align:center; vertical-align:middle;">Turno</th>
                            <th data-filterable="true" data-field="NVale" style="text-align:center; vertical-align:middle;">N° Vale</th>
                            <th data-filterable="true" data-field="NumeroGuia" style="text-align:center; vertical-align:middle;">N° Guia</th>
                            <th data-filterable="true" data-field="NumeroDocRef" style="text-align:center; vertical-align:middle;">N° Doc. Ref.</th>
                            <th data-filterable="true" data-field="Origen" style="text-align:center; vertical-align:middle;">Origen</th>
                            <th data-filterable="true" data-field="Transportista" style="text-align:center; vertical-align:middle;">Transportista</th>
                            <th data-filterable="true" data-field="Chofer" style="text-align:center; vertical-align:middle;">Chofer</th>
                            <th data-filterable="true" data-field="Observaciones" style="text-align:center; vertical-align:middle;">Observaciones</th>
                            <th data-filterable="true" data-field="Prod19003031" style="text-align:center; vertical-align:middle;">19003031</th>
                            <th data-filterable="true" data-field="Prod19002924" style="text-align:center; vertical-align:middle;">19002924</th>
                            <th data-filterable="true" data-field="Prod19003730" style="text-align:center; vertical-align:middle;">19003730</th>
                            <th data-filterable="true" data-field="Prod19003521" style="text-align:center; vertical-align:middle;">19003521</th>
                            <th data-filterable="true" data-field="TotalGeneral" style="text-align:center; vertical-align:middle;">Total Gral.</th>
                        </tr>
                    </thead>
                    <tbody id="grillaReporteBody">
                        <tr>
                            <td colspan="14" class="text-center py-4">
                                <div class="spinner-border text-primary" role="status" style="width:2rem; height:2rem;">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                                <p class="mt-2 mb-0 text-muted">Cargando datos...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
html, body {
    overflow: hidden !important;
    height: 100%;
    max-width: 100vw;
}
/* CSS exacto de Recepciones Externas - valores pequenos que sobrescriben todo */
#grillaReporte {
    border-collapse: collapse !important;
    border-spacing: 0 !important;
    min-width: 1200px !important;
    width: 100% !important;
    table-layout: auto !important;
}
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
    overflow: visible !important;
    font-size: 13px;
    padding: 4px !important;
}
#grillaReporte td {
    font-size: 12px !important;
    padding: 2px 4px !important;
    vertical-align: middle !important;
    line-height: 1.2 !important;
}
#grillaReporteBody {
    font-size: 12px !important;
}
/* Estilo para celdas de producto y total */
#grillaReporte td.celda-producto {
    text-align: center !important;
    font-weight: 500;
}
#grillaReporte td.celda-total {
    text-align: center !important;
    font-weight: 700;
    background-color: #e8f5e9;
}
/* Limitar el card a 1200px como recepcionesexternas */
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
</style>

<script>
    window.BASE_URL = '<?= BASE_URL ?>';
</script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="<?= BASE_URL ?>/js/column-filters.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>/js/reportes-recepcionesext.js?v=<?= time() . rand(1000,9999) ?>"></script>
<script src="<?= BASE_URL ?>/js/notificaciones.js?v=<?= time() ?>"></script>
