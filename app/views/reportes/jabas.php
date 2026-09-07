<!-- Contenedor global para mensajes -->
<div id="mensajes-container"></div>
<div class="container-fluid py-3" style="max-width:100%; width:100%; padding-left:12px; padding-right:12px; margin:auto;">
    <div class="card shadow-sm border-0 mb-4 reporte-card">
        <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-grid-3x3-gap-fill display-6 text-success me-2"></i>
                    <h2 class="fw-bold mb-0" style="color:#16a34a; letter-spacing:0.5px; font-size:2rem;">
                        Reporte Jabas <?= isset($_GET['tipo']) && $_GET['tipo'] === 'blancas' ? 'Blancas' : 'Negras' ?>
                    </h2>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <div class="d-flex gap-2 align-items-center me-3">
                        <select id="selectMes" class="form-select form-select-sm" style="width:auto;">
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
                        <select id="selectAnio" class="form-select form-select-sm" style="width:auto;">
                            <?php for ($a = date('Y') - 2; $a <= date('Y') + 1; $a++): ?>
                                <option value="<?= $a ?>" <?= $a == date('Y') ? 'selected' : '' ?>><?= $a ?></option>
                            <?php endfor; ?>
                        </select>
                        <select id="selectTipo" class="form-select form-select-sm" style="width:auto;">
                            <option value="negras" <?= (isset($_GET['tipo']) && $_GET['tipo'] === 'negras') ? 'selected' : '' ?>>Jabas Negras</option>
                            <option value="blancas" <?= (isset($_GET['tipo']) && $_GET['tipo'] === 'blancas') ? 'selected' : '' ?>>Jabas Blancas</option>
                        </select>
                        <button class="btn btn-primary btn-sm" id="btnFiltrar">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                    </div>
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

            <div style="overflow:auto; max-height:calc(100vh - 280px); border:1px solid #dee2e6; border-radius:8px; width:100%;">
                <table class="table table-bordered table-hover mb-0" id="grillaReporte" style="table-layout:fixed; width:100%; border-collapse: collapse !important;">
                    <colgroup>
                        <col style="width:85px;">
                        <col style="width:70px;">
                        <col style="width:70px;">
                        <col style="width:70px;">
                        <col style="width:70px;">
                        <col style="width:75px;">
                        <col style="width:90px;">
                        <col style="width:70px;">
                        <col style="width:70px;">
                        <col style="width:70px;">
                        <col style="width:75px;">
                        <col style="width:75px;">
                        <col style="width:160px;">
                    </colgroup>
                    <thead class="table-light" style="position:sticky; top:0; background:#f8f9fa; z-index:10;">
                        <tr style="background:#16a34a; color:#fff; font-weight:600;">
                            <th data-filterable="true" data-field="fecha" style="text-align:center; vertical-align:middle; white-space:normal;">Fecha</th>
                            <th data-filterable="true" data-field="saldo_inicial" style="text-align:center; vertical-align:middle; white-space:normal;">Saldo<br>Inicial</th>
                            <th data-filterable="true" data-field="recep_maniana" style="text-align:center; vertical-align:middle; white-space:normal;">Recep.<br>Ma&ntilde;ana</th>
                            <th data-filterable="true" data-field="recep_tarde" style="text-align:center; vertical-align:middle; white-space:normal;">Recep.<br>Tarde</th>
                            <th data-filterable="true" data-field="recep_noche" style="text-align:center; vertical-align:middle; white-space:normal;">Recep.<br>Noche</th>
                            <th data-filterable="true" data-field="total_recepcion" style="text-align:center; vertical-align:middle; white-space:normal;">Total<br>Recep.</th>
                            <th data-filterable="true" data-field="total_saldo_inicial" style="text-align:center; vertical-align:middle; white-space:normal;">Total Saldo<br>Inicial</th>
                            <th data-filterable="true" data-field="desp_maniana" style="text-align:center; vertical-align:middle; white-space:normal;">Desp.<br>Ma&ntilde;ana</th>
                            <th data-filterable="true" data-field="desp_tarde" style="text-align:center; vertical-align:middle; white-space:normal;">Desp.<br>Tarde</th>
                            <th data-filterable="true" data-field="desp_noche" style="text-align:center; vertical-align:middle; white-space:normal;">Desp.<br>Noche</th>
                            <th data-filterable="true" data-field="total_despacho" style="text-align:center; vertical-align:middle; white-space:normal;">Total<br>Desp.</th>
                            <th data-filterable="true" data-field="saldo_final" style="text-align:center; vertical-align:middle; white-space:normal;">Saldo<br>Final</th>
                            <th data-filterable="true" data-field="observaciones" style="text-align:center; vertical-align:middle; white-space:normal;">Observaciones</th>
                        </tr>
                    </thead>
                    <tbody id="grillaReporteBody">
                        <tr>
                            <td colspan="13" class="text-center py-4">
                                <div class="spinner-border text-success" role="status" style="width:2rem; height:2rem;">
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
#grillaReporte {
    border-collapse: collapse !important;
    border-spacing: 0 !important;
    min-width: 1300px !important;
    width: 100% !important;
    table-layout: fixed !important;
}
#grillaReporte th, #grillaReporte td {
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    border: 1px solid #dee2e6 !important;
}
#grillaReporte th {
    white-space: normal !important;
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
    font-size: 12px;
    padding: 3px 2px !important;
}
#grillaReporte td {
    font-size: 12px !important;
    padding: 2px 3px !important;
    vertical-align: middle !important;
    line-height: 1.2 !important;
}
#grillaReporteBody {
    font-size: 12px !important;
}
#grillaReporte td.celda-total-recepcion {
    text-align: center !important;
    font-weight: 600;
    background-color: #e8f5e9;
}
#grillaReporte td.celda-total-despacho {
    text-align: center !important;
    font-weight: 600;
    background-color: #fff3e0;
}
#grillaReporte td.celda-saldo-final {
    text-align: center !important;
    font-weight: 700;
    background-color: #e3f2fd;
}
#grillaReporte td.celda-numero {
    text-align: center !important;
}
#grillaReporte td.celda-obs {
    text-align: left !important;
    font-size: 11px !important;
    max-width: 150px;
    white-space: normal !important;
}
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
    window.TIPO_JABA = '<?= isset($_GET['tipo']) ? htmlspecialchars($_GET['tipo']) : 'negras' ?>';
</script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="<?= BASE_URL ?>/js/column-filters.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>/js/reportes-jabas.js?v=<?= time() . rand(1000,9999) ?>"></script>
<script src="<?= BASE_URL ?>/js/notificaciones.js?v=<?= time() ?>"></script>

