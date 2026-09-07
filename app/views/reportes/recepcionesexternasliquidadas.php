<!-- Contenedor global para mensajes -->
<div id="mensajes-container"></div>
<div class="container-fluid py-3" style="max-width:100%; width:100%; padding-left:12px; padding-right:12px; margin:auto;">
    <div class="card shadow-sm border-0 mb-4 reporte-card">
        <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-grid-3x3-gap-fill display-6 text-primary me-2"></i>
                    <h2 class="fw-bold mb-0" style="color:#2563eb; letter-spacing:0.5px; font-size:2rem;">Recepciones Externas Liquidadas</h2>
                    <div class="d-flex align-items-center gap-2 ms-3">
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
                <table class="table table-hover table-bordered mb-0" id="grillaReporte" style="table-layout:auto; width:100%; border-collapse: collapse !important;">
                    <thead class="table-light" style="position: sticky; top: 0; z-index: 10; background-color: #f8f9fa;">
                        <tr>
                            <th><div class="d-flex flex-column align-items-center"><span>N° Guía</span><input type="text" class="form-control form-control-sm filtro-grilla mt-1" data-campo="NumeroGuia" placeholder="Buscar"></div></th>
                            <th><div class="d-flex flex-column align-items-center"><span>Fecha</span><input type="date" class="form-control form-control-sm filtro-grilla mt-1" data-campo="Fecha" placeholder="Buscar"></div></th>
                            <th><div class="d-flex flex-column align-items-center"><span>Observaciones</span><input type="text" class="form-control form-control-sm filtro-grilla mt-1" data-campo="Observaciones" placeholder="Buscar"></div></th>
                            <th><div class="d-flex flex-column align-items-center"><span>Cantidad</span><input type="number" class="form-control form-control-sm filtro-grilla mt-1" data-campo="Cantidad" placeholder="Buscar"></div></th>
                            <th><div class="d-flex flex-column align-items-center"><span>Pendiente</span><input type="number" class="form-control form-control-sm filtro-grilla mt-1" data-campo="Pendiente" placeholder="Buscar"></div></th>
                            <th><div class="d-flex flex-column align-items-center"><span>Adicional</span><input type="number" class="form-control form-control-sm filtro-grilla mt-1" data-campo="Adicional" placeholder="Buscar"></div></th>
                            <th><div class="d-flex flex-column align-items-center"><span>Total</span><input type="number" class="form-control form-control-sm filtro-grilla mt-1" data-campo="Total" placeholder="Buscar"></div></th>
                        </tr>
                    </thead>
                    <tbody id="tbodyReporte">
                        <tr>
                            <td colspan="7" class="text-center py-5">
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
                /* ===== ESTILOS COMPACTOS DE GRILLA ===== */
                .reporte-card { max-width: 1200px !important; margin-left: auto !important; margin-right: auto !important; }
                .reporte-card .card-body { padding: 1rem !important; }

                #grillaReporte {
                    border-collapse: collapse !important;
                    border-spacing: 0 !important;
                    min-width: 1600px !important;
                    width: 100% !important;
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

                /* COMPACT: filas del tbody con padding mínimo */
                #grillaReporte tbody tr,
                #grillaReporte tbody td {
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

                .choices__inner, .choices { min-height: 32px !important; height: 32px !important; font-size: 13px !important; }

                /* COMPACT: celdas de datos */
                #grillaReporte td {
                    overflow: hidden;
                    text-overflow: ellipsis;
                    font-size: 12px !important;
                    padding: 2px 4px !important;
                    vertical-align: middle !important;
                    line-height: 1.2 !important;
                }

                #grillaReporteBody { font-size: 12px !important; }
                .table-sm th, .table-sm td { padding: 2px 4px !important; }
                #grillaReporte tr { margin: 0 !important; border-collapse: collapse !important; }

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
                .fila-edicion .btn { padding: 2px 5px; font-size: 11px; }
                .producto-row { background: #f8f9fa; }
                .producto-row:hover { background: #e9ecef; }

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
                .action-btn i { font-size: 1.1rem; }
                .action-btn::after { display: none !important; }
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
                    padding: 8px 12px !important;
                    font-weight: normal !important;
                    white-space: nowrap !important;
                    transition: all 0.15s ease-in-out !important;
                    border-left: 3px solid transparent !important;
                    font-size: 0.82rem !important;
                }
                .dropdown-menu .dropdown-item:hover {
                    background-color: rgba(13, 110, 253, 0.08) !important;
                    color: #0d6efd !important;
                    border-left-color: #0d6efd !important;
                }
            </style>
            </div>
        </div>
    </div>
</div>

<script>
    window.BASE_URL = '<?= BASE_URL ?>';
</script>
<script src="<?= BASE_URL ?>/js/reportes-recepcionesexternasliquidadas.js?v=<?= time() . rand(1000,9999) ?>"></script>
