<!-- Vista de Recepciones Externas -->
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-4 flex-nowrap" style="white-space:nowrap;">
            <i class="bi bi-box-arrow-in-right display-5 text-primary me-2"></i>
            <h2 class="fw-bold mb-0" style="color:#1a237e; letter-spacing:0.5px; font-size:1.6rem; white-space:nowrap;">Recepciones Externas</h2>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" id="btnNuevo" class="btn btn-action btn-new keep-enabled"><i class="bi bi-plus-circle me-1"></i> Nuevo</button>
            <button type="button" id="btnGuardar" class="btn btn-action btn-save keep-enabled" disabled><i class="bi bi-save me-1"></i> Guardar</button>
            <button type="button" id="btnModificar" class="btn btn-action btn-edit keep-enabled" disabled><i class="bi bi-pencil me-1"></i> Modificar</button>
            <button type="button" id="btnImprimir" class="btn btn-action btn-print keep-enabled" title="Imprimir vista previa">
                <i class="bi bi-printer"></i>
            </button>
            <div class="bg-warning bg-gradient rounded-4 shadow-sm px-3 py-1 text-center d-flex align-items-center justify-content-center flex-nowrap" style="min-width:120px; margin-left:80px; white-space:nowrap; overflow:hidden;">
                <span class="fw-bold me-1" style="font-size:0.85rem; color:#7c4700; letter-spacing:0.5px; vertical-align:middle; white-space:nowrap;">N° Vale:</span>
                <span id="correlativoVale" class="fw-bolder" style="font-size:0.95rem; color:#1a237e; letter-spacing:1px; vertical-align:middle; white-space:nowrap;">VRE-000001</span>
            </div>
        </div>
    </div>

    <!-- Diseño compacto de dos columnas: Formulario + Vista Previa -->
    <div class="row g-3" style="align-items:stretch; zoom:0.82; transform-origin:top left;">
        <!-- Columna Izquierda: Formulario de entrada de datos (38%) -->
        <div class="col-lg-4 d-flex" style="flex:0 0 38%; max-width:38%;">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header text-white fw-bold d-flex justify-content-between align-items-center" style="background-color:#374151; cursor:pointer;" id="headerDatosRecepcion">
                    <span><i class="bi bi-pencil-square me-2"></i>Datos de la Recepción</span>
                    <button type="button" class="btn btn-sm btn-link text-white p-0" id="btnToggleDatos" title="Mostrar/Ocultar datos">
                        <i class="bi bi-chevron-up" id="iconToggleDatos"></i>
                    </button>
                </div>
                <div class="card-body p-3 recepcion-datos" id="seccionDatosRecepcion" style="flex:1 1 auto; overflow:auto;">
                    <form id="formRecepcionExterna">
                        <div class="row g-2">
                            <!-- Primera fila compacta: Fecha, Hora, Turno, Origen -->
                            <div class="col-12">
                                <div class="d-flex flex-md-nowrap align-items-end gap-2">
                                    <div class="col-auto p-0" style="display:flex; flex-direction:column; align-items:flex-start;">
                                        <div id="badgeFechaNocturno" class="d-none" style="width:100%; margin-bottom:2px; font-size:0.65rem;" title="Turno nocturno activo - fecha ajustada automáticamente">
                                            <i class="bi bi-moon-stars"></i>
                                            <span>Fecha a registrar: <strong id="fechaEfectivaDisplay"></strong></span>
                                        </div>
                                        <label for="fecha" class="form-label small fw-bold">Fecha <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control form-control-sm" id="fecha" name="fecha" value="<?= htmlspecialchars($fechaHoy ?? date('Y-m-d')) ?>" max="<?= date('Y-m-d') ?>" required style="width:auto;">
                                    </div>

                                    <div class="col-auto p-0">
                                        <label for="hora" class="form-label small fw-bold">Hora <span class="text-danger">*</span></label>
                                        <input type="time" class="form-control form-control-sm" id="hora" name="hora" value="<?= htmlspecialchars($horaActual ?? date('H:i')) ?>" required disabled tabindex="-1" aria-disabled="true" data-permanent-disabled="true" style="width:auto; pointer-events:none;">
                                    </div>

                                    <div class="turno-wrap turno-container ms-3" style="flex: 0 0 120px;">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <label for="turno" class="form-label small fw-bold mb-0">Turno <span class="text-danger">*</span></label>
                                            <div class="form-check ms-2 mb-0" style="padding-bottom:0;">
                                                <input class="form-check-input align-middle" type="checkbox" id="chkTurno">
                                                <label class="form-check-label align-middle small mb-0" for="chkTurno">Manual</label>
                                            </div>
                                        </div>
                                        <select class="form-select form-select-sm custom-dropdown" id="turno" name="turno" disabled required style="width:100%; height:38px;">
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

                                    <!-- origen moved below fecha -->
                                </div>
                            </div>

                            <!-- Origen (bajado debajo de Fecha) - reducir ancho para nuevo campo a la derecha -->
                            <div class="col-12">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label for="origen" class="form-label small fw-bold">Origen <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm custom-dropdown" id="origen" name="origen" required>
                                            <option value="" selected>&nbsp;</option>
                                            <?php if (isset($origenes) && is_array($origenes)): ?>
                                                <?php foreach ($origenes as $org): ?>
                                                    <option value="<?= htmlspecialchars($org['Id'] ?? '') ?>">
                                                        <?= htmlspecialchars($org['Origen'] ?? '') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="asistente" class="form-label small fw-bold">Asistente</label>
                                        <div id="asistente" class="form-control form-control-sm" style="background:#eef2f6; pointer-events:none;">
                                            <?= htmlspecialchars($_SESSION['user']['NombresApellidos'] ?? '') ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Empresa (con RUC debajo) -->
                            <div class="col-12">
                                <div class="row g-2">
                                    <div class="col-12">
                                        <label for="empresa" class="form-label small fw-bold">Empresa <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm custom-dropdown" id="empresa" name="empresa" required>
                                            <option value="" selected>&nbsp;</option>
                                            <?php if (isset($transportistas) && is_array($transportistas)): ?>
                                                <?php foreach ($transportistas as $trans): ?>
                                                    <option value="<?= htmlspecialchars($trans['Id'] ?? '') ?>" data-ruc="<?= htmlspecialchars($trans['RUC'] ?? '') ?>">
                                                        <?= htmlspecialchars($trans['Empresa'] ?? '') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="row g-2">
                                    <div class="col-12">
                                        <label for="ruc" class="form-label small fw-bold">RUC <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm custom-dropdown" id="ruc" name="ruc" required>
                                            <option value="" selected>&nbsp;</option>
                                            <?php if (isset($transportistas) && is_array($transportistas)): ?>
                                                <?php foreach ($transportistas as $trans): ?>
                                                    <option value="<?= htmlspecialchars($trans['Id'] ?? '') ?>" data-empresa="<?= htmlspecialchars($trans['Empresa'] ?? '') ?>">
                                                        <?= htmlspecialchars($trans['RUC'] ?? '') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Chofer (con Brevete debajo) -->
                            <div class="col-12">
                                <div class="row g-2">
                                    <div class="col-12">
                                        <label for="chofer" class="form-label small fw-bold">Chofer <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm custom-dropdown" id="chofer" name="chofer" required>
                                            <option value="" selected>&nbsp;</option>
                                            <?php if (isset($choferes) && is_array($choferes)): ?>
                                                <?php foreach ($choferes as $chof): ?>
                                                    <option value="<?= htmlspecialchars($chof['Id'] ?? '') ?>" data-brevete="<?= htmlspecialchars($chof['Brevete'] ?? '') ?>">
                                                        <?= htmlspecialchars($chof['ApellidosNombres'] ?? '') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="row g-2">
                                    <div class="col-12">
                                        <label for="brevete" class="form-label small fw-bold">Brevete <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm custom-dropdown" id="brevete" name="brevete" required>
                                            <option value="" selected>&nbsp;</option>
                                            <?php if (isset($choferes) && is_array($choferes)): ?>
                                                <?php foreach ($choferes as $chof): ?>
                                                    <option value="<?= htmlspecialchars($chof['Id'] ?? '') ?>" 
                                                            data-chofer="<?= htmlspecialchars($chof['ApellidosNombres'] ?? '') ?>">
                                                        <?= htmlspecialchars($chof['Brevete'] ?? '') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Campo Observaciones -->
                            <div class="col-12">
                                <label for="observaciones" class="form-label small fw-bold">Observaciones</label>
                                <textarea id="observaciones" name="observaciones" class="form-control" rows="2" style="height:50px !important; min-height:40px; font-size:10px; line-height:1.2;" placeholder="Observaciones..."></textarea>
                            </div>

                            <!-- Cuarta fila: Comentarios adicionales -->
                            <div class="col-12">
                                <label for="comentarios" class="form-label small fw-bold">Comentarios adicionales</label>
                                <textarea id="comentarios" name="comentarios" class="form-control" rows="2" style="height:50px !important; min-height:40px; font-size:10px; line-height:1.2;" placeholder="Comentarios adicionales..."></textarea>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Grilla de Guías (62%) -->
        <div class="col-lg-8 d-flex" style="flex:0 0 62%; max-width:62%;">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header text-white fw-bold d-flex justify-content-between align-items-center" style="background-color:#1f2937;">
                    <span><i class="bi bi-list-ul me-2"></i>Grilla de Guías</span>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-success" id="btnAgregarFilaGuia" title="agregar Guia" style="display:none;">
                            <i class="bi bi-plus-circle me-1"></i>Guia
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" id="btnQuitarFilaGuia" title="quitar Guia" style="display:none;">
                            <i class="bi bi-dash-circle me-1"></i>Guia
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" id="btnAgregarColumnaProducto" title="agregar Producto" style="display:none;">
                            <i class="bi bi-plus-circle me-1"></i>Producto
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" id="btnEliminarColumnaProducto" title="quitar Producto" style="display:none;">
                            <i class="bi bi-dash-circle me-1"></i>Producto
                        </button>
                    </div>
                </div>
                <div class="card-body p-3 recepcion-grilla" style="background:#f8f9fa; flex:1 1 auto; overflow:auto;">
                    <form id="formGrillaGuias">
                        <div class="table-responsive" id="contenedorGrillaGuias" style="max-height:500px; overflow-y:auto; overflow-x:auto;">
                            <style>
                                /* Forzar mayúsculas en la grilla de guías (scoped a recepcion-grilla) */
                                .recepcion-grilla #tablaGrillaGuias, .recepcion-grilla #tablaGrillaGuias th, .recepcion-grilla #tablaGrillaGuias td { text-transform: uppercase; }
                                .recepcion-grilla #tablaGrillaGuias td input, .recepcion-grilla #tablaGrillaGuias td select, .recepcion-grilla #tablaGrillaGuias td textarea { text-transform: uppercase; }
                                /* Excluir subcolumnas (clase .subcol) para mostrar texto tal cual */
                                .recepcion-grilla #tablaGrillaGuias th.subcol { text-transform: none; }
                            </style>
                            <table class="table table-sm table-bordered" id="tablaGrillaGuias" style="font-size:0.7rem; white-space:nowrap; text-transform:uppercase;">
                                <thead class="table-light" style="position:sticky; top:0; z-index:10;">
                                    <tr id="headerGrillaGuiasPrincipales">
                                        <th colspan="2" style="min-width:140px; vertical-align:middle; text-align:center;">N° GUIA</th>
                                        <th rowspan="2" style="min-width:100px; vertical-align:middle; text-align:center;">N°<br>doc. ref.</th>
                                        <th colspan="3" style="vertical-align:middle; text-align:center; padding:4px; background:#e3f2fd;" class="col-producto col-producto-fija" data-producto-col="1" data-producto-fijo="EAN">
                                            <button type="button" class="btn-collapse-col" data-producto-col="1" title="Ocultar columna" style="cursor:pointer; border:none; background:transparent; font-size:0.7rem; padding:0 4px; margin-right:4px;">[-]</button>
                                            <span style="font-size:0.7rem; font-weight:600;">ean</span>
                                        </th>
                                        <th colspan="3" style="vertical-align:middle; text-align:center; padding:4px; background:#fce4ec;" class="col-producto col-producto-fija" data-producto-col="2" data-producto-fijo="EXI">
                                            <button type="button" class="btn-collapse-col" data-producto-col="2" title="Ocultar columna" style="cursor:pointer; border:none; background:transparent; font-size:0.7rem; padding:0 4px; margin-right:4px;">[-]</button>
                                            <span style="font-size:0.7rem; font-weight:600;">exi</span>
                                        </th>
                                        <th colspan="3" style="vertical-align:middle; text-align:center; padding:4px; background:#e8f5e9;" class="col-producto col-producto-fija" data-producto-col="3" data-producto-fijo="JAB">
                                            <button type="button" class="btn-collapse-col" data-producto-col="3" title="Ocultar columna" style="cursor:pointer; border:none; background:transparent; font-size:0.7rem; padding:0 4px; margin-right:4px;">[-]</button>
                                            <span style="font-size:0.7rem; font-weight:600;">jng</span>
                                        </th>
                                        <th colspan="3" style="vertical-align:middle; text-align:center; padding:4px; background:#fff3e0;" class="col-producto col-producto-fija" data-producto-col="4" data-producto-fijo="JAN">
                                            <button type="button" class="btn-collapse-col" data-producto-col="4" title="Ocultar columna" style="cursor:pointer; border:none; background:transparent; font-size:0.7rem; padding:0 4px; margin-right:4px;">[-]</button>
                                            <span style="font-size:0.7rem; font-weight:600;">jbl</span>
                                        </th>
                                        <th colspan="3" style="vertical-align:middle; text-align:center; padding:4px; background:#f3e5f5;" class="col-producto col-producto-fija" data-producto-col="5" data-producto-fijo="EAN">
                                            <button type="button" class="btn-collapse-col" data-producto-col="5" title="Ocultar columna" style="cursor:pointer; border:none; background:transparent; font-size:0.7rem; padding:0 4px; margin-right:4px;">[-]</button>
                                            <span style="font-size:0.7rem; font-weight:600;">EAN CN</span>
                                        </th>
                                        <th colspan="3" style="vertical-align:middle; text-align:center; padding:4px; background:#fffde7;" class="col-producto col-producto-fija" data-producto-col="6" data-producto-fijo="PLAZUL">
                                            <button type="button" class="btn-collapse-col" data-producto-col="6" title="Ocultar columna" style="cursor:pointer; border:none; background:transparent; font-size:0.7rem; padding:0 4px; margin-right:4px;">[-]</button>
                                            <span style="font-size:0.7rem; font-weight:600;">PL AZUL</span>
                                        </th>
                                        <th colspan="3" style="vertical-align:middle; text-align:center; padding:4px; background:#e8eaf6;" class="col-producto col-producto-fija" data-producto-col="7" data-producto-fijo="REV">
                                            <button type="button" class="btn-collapse-col" data-producto-col="7" title="Ocultar columna" style="cursor:pointer; border:none; background:transparent; font-size:0.7rem; padding:0 4px; margin-right:4px;">[-]</button>
                                            <span style="font-size:0.7rem; font-weight:600;">REV</span>
                                        </th>
                                        <th colspan="3" style="vertical-align:middle; text-align:center; padding:4px; background:#e0f7fa;" class="col-producto col-producto-fija" data-producto-col="8" data-producto-fijo="NWZ">
                                            <button type="button" class="btn-collapse-col" data-producto-col="8" title="Ocultar columna" style="cursor:pointer; border:none; background:transparent; font-size:0.7rem; padding:0 4px; margin-right:4px;">[-]</button>
                                            <span style="font-size:0.7rem; font-weight:600;">NWZ</span>
                                        </th>
                                    </tr>
                                    <tr id="headerGrillaGuiasSubcabeceras">
                                        <th class="subcol" style="width:60px; vertical-align:middle; text-align:center; padding:2px; font-size:0.5rem; background:#f5f5f5;">
                                            <div style="display:flex; align-items:center; justify-content:center; gap:6px;">
                                                <span>Serie</span>
                                                <button type="button" id="btnAddSerieHeader" class="btn btn-sm btn-outline-primary" style="padding:0 6px; height:18px; line-height:16px; font-size:0.65rem;">+</button>
                                            </div>
                                        </th>
                                        <th class="subcol" style="width:80px; vertical-align:middle; text-align:center; padding:2px; font-size:0.5rem; background:#f5f5f5;">Correlativo</th>
                                        <th class="subcol" data-producto-col="1" style="width:60px; vertical-align:middle; text-align:center; padding:2px; font-size:0.5rem; background:#e3f2fd;">Cant GR</th>
                                        <th class="subcol" data-producto-col="1" style="width:60px; vertical-align:middle; text-align:center; padding:2px; font-size:0.5rem; background:#e3f2fd;">Obs</th>
                                        <th class="subcol" data-producto-col="1" style="width:60px; vertical-align:middle; text-align:center; padding:2px; font-size:0.5rem; background:#e3f2fd;">Cant Obs</th>
                                        <th class="subcol" data-producto-col="2" style="width:60px; vertical-align:middle; text-align:center; padding:2px; font-size:0.5rem; background:#fce4ec;">Cant GR</th>
                                        <th class="subcol" data-producto-col="2" style="width:60px; vertical-align:middle; text-align:center; padding:2px; font-size:0.5rem; background:#fce4ec;">Obs</th>
                                        <th class="subcol" data-producto-col="2" style="width:60px; vertical-align:middle; text-align:center; padding:2px; font-size:0.5rem; background:#fce4ec;">Cant Obs</th>
                                        <th class="subcol" data-producto-col="3" style="width:60px; vertical-align:middle; text-align:center; padding:2px; font-size:0.5rem; background:#e8f5e9;">Cant GR</th>
                                        <th class="subcol" data-producto-col="3" style="width:60px; vertical-align:middle; text-align:center; padding:2px; font-size:0.5rem; background:#e8f5e9;">Obs</th>
                                        <th class="subcol" data-producto-col="3" style="width:60px; vertical-align:middle; text-align:center; padding:2px; font-size:0.5rem; background:#e8f5e9;">Cant Obs</th>
                                        <th class="subcol" data-producto-col="4" style="width:60px; vertical-align:middle; text-align:center; padding:2px; font-size:0.5rem; background:#fff3e0;">Cant GR</th>
                                        <th class="subcol" data-producto-col="4" style="width:60px; vertical-align:middle; text-align:center; padding:2px; font-size:0.5rem; background:#fff3e0;">Obs</th>
                                        <th class="subcol" data-producto-col="4" style="width:60px; vertical-align:middle; text-align:center; padding:2px; font-size:0.5rem; background:#fff3e0;">Cant Obs</th>
                                        <th class="subcol" data-producto-col="5" style="width:60px; vertical-align:middle; text-align:center; padding:2px; font-size:0.5rem; background:#f3e5f5;">Cant GR</th>
                                        <th class="subcol" data-producto-col="5" style="width:60px; vertical-align:middle; text-align:center; padding:2px; font-size:0.5rem; background:#f3e5f5;">Obs</th>
                                        <th class="subcol" data-producto-col="5" style="width:60px; vertical-align:middle; text-align:center; padding:2px; font-size:0.5rem; background:#f3e5f5;">Cant Obs</th>
                                        <th class="subcol" data-producto-col="6" style="width:60px; vertical-align:middle; text-align:center; padding:2px; font-size:0.5rem; background:#fffde7;">Cant GR</th>
                                        <th class="subcol" data-producto-col="6" style="width:60px; vertical-align:middle; text-align:center; padding:2px; font-size:0.5rem; background:#fffde7;">Obs</th>
                                        <th class="subcol" data-producto-col="6" style="width:60px; vertical-align:middle; text-align:center; padding:2px; font-size:0.5rem; background:#fffde7;">Cant Obs</th>
                                        <th class="subcol" data-producto-col="7" style="width:60px; vertical-align:middle; text-align:center; padding:2px; font-size:0.5rem; background:#e8eaf6;">Cant GR</th>
                                        <th class="subcol" data-producto-col="7" style="width:60px; vertical-align:middle; text-align:center; padding:2px; font-size:0.5rem; background:#e8eaf6;">Obs</th>
                                        <th class="subcol" data-producto-col="7" style="width:60px; vertical-align:middle; text-align:center; padding:2px; font-size:0.5rem; background:#e8eaf6;">Cant Obs</th>
                                        <th class="subcol" data-producto-col="8" style="width:60px; vertical-align:middle; text-align:center; padding:2px; font-size:0.5rem; background:#e0f7fa;">Cant GR</th>
                                        <th class="subcol" data-producto-col="8" style="width:60px; vertical-align:middle; text-align:center; padding:2px; font-size:0.5rem; background:#e0f7fa;">Obs</th>
                                        <th class="subcol" data-producto-col="8" style="width:60px; vertical-align:middle; text-align:center; padding:2px; font-size:0.5rem; background:#e0f7fa;">Cant Obs</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyGrillaGuias">
                                    <!-- Filas dinámicas -->
                                </tbody>
                                <tfoot class="table-secondary" style="position:sticky; bottom:0; z-index:10;">
                                    <tr id="footerTotales">
                                        <th colspan="2" style="text-align:center; font-weight:700;">TOTALES</th>
                                        <th style="text-align:center;"></th>
                                        <!-- Producto 1 -->
                                        <th class="total-producto-gr" data-producto-col="1" style="text-align:center; font-weight:700;">0</th>
                                        <th style="text-align:center;"></th>
                                        <th class="total-producto-obs" data-producto-col="1" style="text-align:center; font-weight:700;">0</th>
                                        <!-- Producto 2 -->
                                        <th class="total-producto-gr" data-producto-col="2" style="text-align:center; font-weight:700;">0</th>
                                        <th style="text-align:center;"></th>
                                        <th class="total-producto-obs" data-producto-col="2" style="text-align:center; font-weight:700;">0</th>
                                        <!-- Producto 3 -->
                                        <th class="total-producto-gr" data-producto-col="3" style="text-align:center; font-weight:700;">0</th>
                                        <th style="text-align:center;"></th>
                                        <th class="total-producto-obs" data-producto-col="3" style="text-align:center; font-weight:700;">0</th>
                                        <!-- Producto 4 -->
                                        <th class="total-producto-gr" data-producto-col="4" style="text-align:center; font-weight:700;">0</th>
                                        <th style="text-align:center;"></th>
                                        <th class="total-producto-obs" data-producto-col="4" style="text-align:center; font-weight:700;">0</th>
                                        <!-- Producto 5 -->
                                        <th class="total-producto-gr" data-producto-col="5" style="text-align:center; font-weight:700;">0</th>
                                        <th style="text-align:center;"></th>
                                        <th class="total-producto-obs" data-producto-col="5" style="text-align:center; font-weight:700;">0</th>
                                        <!-- Producto 6 -->
                                        <th class="total-producto-gr" data-producto-col="6" style="text-align:center; font-weight:700;">0</th>
                                        <th style="text-align:center;"></th>
                                        <th class="total-producto-obs" data-producto-col="6" style="text-align:center; font-weight:700;">0</th>
                                        <!-- Producto 7 -->
                                        <th class="total-producto-gr" data-producto-col="7" style="text-align:center; font-weight:700;">0</th>
                                        <th style="text-align:center;"></th>
                                        <th class="total-producto-obs" data-producto-col="7" style="text-align:center; font-weight:700;">0</th>
                                        <!-- Producto 8 -->
                                        <th class="total-producto-gr" data-producto-col="8" style="text-align:center; font-weight:700;">0</th>
                                        <th style="text-align:center;"></th>
                                        <th class="total-producto-obs" data-producto-col="8" style="text-align:center; font-weight:700;">0</th>
                                    </tr>
                                </tfoot>
                            </table>
                            <script>
                                window.seriesData = <?php echo json_encode($series ?? []); ?>;
                            </script>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Vista previa ahora en modal; mantener preview oculto para que JS lo actualice -->
        <div style="display:none;">
            <div id="valePreview" class="bg-white border rounded p-3" style="font-family:Arial,sans-serif; font-size:0.85rem;">
                <!-- Vista previa se actualiza dinámicamente vía JS (contenedor oculto) -->
            </div>
        </div>

        <!-- Modal de impresión: la vista previa se cargará aquí al hacer clic en Imprimir -->
        <div class="modal fade" id="modalValePreview" tabindex="-1" aria-labelledby="modalValeLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width:1200px;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="modalValeLabel">Vista previa de Vale de Recepción Externa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body" id="valePreviewContent" style="background:#fff; overflow:auto; min-width:1100px;">
                        <!-- Contenido del vale generado por JS -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-primary" id="btnValePrint"><i class="bi bi-printer"></i> Imprimir</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para crear nueva Serie (pequeño, usado desde header Serie) -->
<div class="modal fade" id="modalAddSerie" tabindex="-1" aria-labelledby="modalAddSerieLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAddSerieLabel">Crear Serie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label for="inputNuevaSerie" class="form-label small">Serie (4 caracteres)</label>
                    <input type="text" id="inputNuevaSerie" class="form-control form-control-sm" maxlength="4" placeholder="Ej: T086" style="text-transform:uppercase;">
                </div>
                <div class="mb-2">
                    <label for="inputNuevaSerieCentroDistribucion" class="form-label small">Centro de Distribución</label>
                    <input type="text" id="inputNuevaSerieCentroDistribucion" class="form-control form-control-sm" maxlength="100" placeholder="Ej: OQUENDO" style="text-transform:uppercase;">
                </div>
                <div id="addSerieFeedback" style="display:none; font-size:0.85rem; color:#b91c1c;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnSaveNuevaSerie" class="btn btn-sm btn-primary">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Choices.js CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

<!-- Script personalizado de Recepciones Externas -->
<script>
    // Definir rutas como variables globales para JavaScript
    window.BASE_URL = "<?= BASE_URL ?>"; // Para assets (CSS, JS, imágenes)
    window.APP_URL = "<?= APP_URL ?>";   // Para rutas de aplicación (AJAX, formularios)
    
    // Pasar datos de PHP a JavaScript
    window.turnosData = <?= json_encode($turnos ?? []) ?>;
    window.horaActual = "<?= htmlspecialchars($horaActual ?? date('H:i')) ?>";
    window.usuarioUsername = "<?= $_SESSION['user']['username'] ?? '' ?>";
</script>
<script src="<?= asset_url('js/fecha-efectiva-nocturno.js?v=' . time()) ?>"></script>
<script src="<?= asset_url('js/recepcionesexternas.js?v=' . time()) ?>"></script>

<style>
    /* Estilos compactos para formulario */
    .form-label {
        margin-bottom: 0.25rem;
        color: #374151;
    }

    /* Estilo para inputs deshabilitados (aparecer en gris y no seleccionables) */
    #seccionDatosRecepcion input:disabled.form-control,
    #seccionDatosRecepcion input[disabled],
    #seccionDatosRecepcion .form-control:disabled,
    #seccionDatosRecepcion .form-select:disabled {
        background-color: #eef2f6 !important;
        color: #6b7280 !important;
        cursor: not-allowed !important;
        opacity: 1 !important;
    }
    
    .recepcion-datos .form-control-sm, .recepcion-datos .form-select-sm {
        font-size: 0.875rem;
        padding: 0.25rem 0.5rem;
    }
    
    /* Forzar tamaño de fuente pequeño en selects principales del formulario (scoped) */
    #seccionDatosRecepcion #turno, #seccionDatosRecepcion #origen, #seccionDatosRecepcion #empresa, #seccionDatosRecepcion #ruc, #seccionDatosRecepcion #chofer, #seccionDatosRecepcion #brevete {
        font-size: 0.7rem !important;
        line-height: 1.2 !important;
    }
    
    #seccionDatosRecepcion #turno option, #seccionDatosRecepcion #origen option, #seccionDatosRecepcion #empresa option, #seccionDatosRecepcion #ruc option, #seccionDatosRecepcion #chofer option, #seccionDatosRecepcion #brevete option {
        font-size: 0.7rem !important;
    }
    
    /* Forzar tamaño pequeño en contenedores de Choices.js para selects principales (solo datos) */
    #seccionDatosRecepcion .choices__inner,
    .recepcion-datos .choices__inner {
        font-size: 0.7rem !important;
        min-height: 28px !important;
        padding: 2px 6px !important;
    }
    
    .recepcion-datos .choices__list--single {
        padding: 2px 6px !important;
    }
    
    .recepcion-datos .choices__item {
        font-size: 0.7rem !important;
    }
    
    .recepcion-datos .choices__list--dropdown .choices__item {
        font-size: 0.7rem !important;
        padding: 6px 8px !important;
    }
    
    /* Forzar tamaño de fuente pequeño en tabla de productos de guía (scoped a la grilla) */
    .recepcion-grilla #tablaProductosGuia select.prod-select,
    .recepcion-grilla #tablaProductosGuia select.prod-observacion {
        font-size: 0.7rem !important;
        line-height: 1 !important;
    }
    
    .recepcion-grilla #tablaProductosGuia select.prod-select option,
    .recepcion-grilla #tablaProductosGuia select.prod-observacion option {
        font-size: 0.7rem !important;
    }
    
    .recepcion-grilla #tablaProductosGuia input {
        font-size: 0.7rem !important;
    }

    /* Forzar tamaño de fuente específico para textareas en esta sección */
    #seccionDatosRecepcion textarea#comentarios.form-control,
    #seccionDatosRecepcion textarea#observaciones.form-control {
        font-size: 11px !important;
        line-height: 1.2 !important;
        min-height: 40px !important;
        height: 50px !important;
    }

    /* Unificar tamaño de selects e inputs en la grilla de guías (scoped) */
    .recepcion-grilla .select-header-producto,
    .recepcion-grilla .select-serie-guia,
    .recepcion-grilla .select-obs,
    .recepcion-grilla .select-cod-obs,
    .recepcion-grilla .input-num-guia,
    .recepcion-grilla .input-serie-guia,
    .recepcion-grilla .input-correlativo-guia,
    .recepcion-grilla .input-cant-producto,
    .recepcion-grilla .input-cant-obs {
        font-size: 0.7rem !important;
        line-height: 1 !important;
        height: 28px !important;
        padding: 2px 6px !important;
    }

    /* Asegurar que el icono y texto del botón se mantengan compactos */
    .d-flex .btn-sm i {
        margin-right: 6px;
        font-size: 0.95rem;
    }

    /* Reglas específicas y con mayor prioridad para forzar controles compactos en la grilla (scoped) */
    .recepcion-grilla #tablaGrillaGuias .select-header-producto,
    .recepcion-grilla #tablaGrillaGuias .select-header-producto.form-select,
    .recepcion-grilla #tablaGrillaGuias .select-obs,
    .recepcion-grilla #tablaGrillaGuias .select-cod-obs,
    .recepcion-grilla #tablaGrillaGuias .select-obs.form-select {
        font-size: 0.75rem !important;
        height: 30px !important;
        min-height: 30px !important;
        padding: 4px 8px !important;
        box-sizing: border-box !important;
        line-height: 1 !important;
        vertical-align: middle !important;
    }

    #tablaGrillaGuias input.form-control,
    #tablaGrillaGuias input.input-num-guia,
    #tablaGrillaGuias input.input-serie-guia,
    #tablaGrillaGuias input.input-correlativo-guia,
    #tablaGrillaGuias input.input-cant-producto,
    #tablaGrillaGuias input.input-cant-obs {
        height: 30px !important;
        padding: 4px 8px !important;
        font-size: 0.75rem !important;
        box-sizing: border-box !important;
        vertical-align: middle !important;
    }

    /* Ajustar ancho/alto de Cant GR y Cant Obs para que coincidan con selects 'Obs' (40x30) */
    .recepcion-grilla #tablaGrillaGuias input.input-cant-producto,
    .recepcion-grilla #tablaGrillaGuias input.input-cant-obs,
    .recepcion-grilla #tablaGrillaGuias input.input-cant-obs-producto,
    .recepcion-grilla #tablaGrillaGuias input.input-cant-producto-producto {
        width: 40px !important;
        height: 30px !important;
        min-height: 30px !important;
        padding: 4px 8px !important;
        box-sizing: border-box !important;
    }

    /* Forzar ancho de las subcolumnas del header para mantener alineación visual */
    .recepcion-grilla #tablaGrillaGuias th.subcol {
        width: 40px !important;
        max-width: 40px !important;
        padding: 2px !important;
        text-align: center !important;
    }

    /* Forzar que las celdas/controles de 'Obs' tengan mismo ancho/alto que 'Cant GR' y 'Cant Obs' */
    .recepcion-grilla #tablaGrillaGuias td .select-obs,
    .recepcion-grilla #tablaGrillaGuias td .select-cod-obs,
    .recepcion-grilla #tablaGrillaGuias td textarea.obs-textarea,
    .recepcion-grilla #tablaGrillaGuias td input.input-obs {
        height: 56px !important;
        min-height: 56px !important;
        width: 56px !important;
        padding: 6px 8px !important;
        font-size: 0.75rem !important;
        box-sizing: border-box !important;
        vertical-align: middle !important;
        overflow: hidden !important;

    /* Clase visual para indicar corrección/valor inválido temporal en inputs */
    .recepcion-grilla #tablaGrillaGuias input.input-error {
        border-color: #ff5252 !important;
        box-shadow: 0 0 0 3px rgba(255,82,82,0.12) !important;
        transition: box-shadow 0.18s ease-in-out, border-color 0.18s ease-in-out;
    }
        line-height: 1 !important;
    }
    .recepcion-grilla #tablaGrillaGuias td textarea.obs-textarea { resize: none !important; }

    /* Mantener tamaño al hacer focus/active para evitar salto de altura */
    .recepcion-grilla #tablaGrillaGuias td .select-obs:focus,
    .recepcion-grilla #tablaGrillaGuias td .select-cod-obs:focus,
    .recepcion-grilla #tablaGrillaGuias td input.input-obs:focus,
    .recepcion-grilla #tablaGrillaGuias td textarea.obs-textarea:focus,
    .recepcion-grilla #tablaGrillaGuias td .select-obs:active,
    .recepcion-grilla #tablaGrillaGuias td .select-cod-obs:active,
    .recepcion-grilla #tablaGrillaGuias td input.input-obs:active,
    .recepcion-grilla #tablaGrillaGuias td textarea.obs-textarea:active {
        height: 56px !important;
        min-height: 56px !important;
        line-height: 1 !important;
        box-sizing: border-box !important;
    }

    /* Reglas específicas de mayor prioridad para sobrescribir Bootstrap y style.css
       y asegurar que los selects 'Obs' en la grilla respeten 56x56 */
    .recepcion-grilla #tablaGrillaGuias td select.select-obs-producto,
    .recepcion-grilla #tablaGrillaGuias td select.select-obs,
    .recepcion-grilla #tablaGrillaGuias td select.form-select.select-obs-producto {
        width: 40px !important;
        height: 30px !important;
        min-height: 30px !important;
        padding: 4px 8px !important;
        font-size: 0.75rem !important;
        box-sizing: border-box !important;
    }

    /* Quitar flecha nativa de los selects 'Obs' en la grilla */
    .recepcion-grilla #tablaGrillaGuias td select.select-obs-producto,
    .recepcion-grilla #tablaGrillaGuias td select.select-obs {
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        appearance: none !important;
        background-image: none !important;
        background-color: transparent !important;
        padding-right: 6px !important;
    }
    /* Mostrar selects 'Obs' deshabilitados en gris (igual que inputs deshabilitados) */
    .recepcion-grilla #tablaGrillaGuias td select.select-obs-producto:disabled,
    .recepcion-grilla #tablaGrillaGuias td select.select-obs:disabled {
        background-color: #e9ecef !important; /* Bootstrap input disabled bg */
        color: #6c757d !important; /* texto gris */
        cursor: not-allowed !important;
        border-color: #ced4da !important; /* borde similar a inputs */
        opacity: 1 !important;
    }
    /* Internet Explorer/Edge native arrow */
    .recepcion-grilla #tablaGrillaGuias td select.select-obs-producto::-ms-expand,
    .recepcion-grilla #tablaGrillaGuias td select.select-obs::-ms-expand {
        display: none !important;
    }
    /* Asegurar que no queden imágenes de fondo por Bootstrap */
    .recepcion-grilla #tablaGrillaGuias td select.select-obs-producto,
    .recepcion-grilla #tablaGrillaGuias td select.select-obs {
        background-repeat: no-repeat !important;
        background-position: center right !important;
    }

    /* Ajustes específicos para el select de Serie: quitar flecha nativa y igualar altura */
    .recepcion-grilla #tablaGrillaGuias td select.select-serie-guia {
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        appearance: none !important;
        background-image: none !important;
        background-color: transparent !important;
        padding-right: 6px !important;
        height: 30px !important;
        min-height: 30px !important;
        padding: 4px 8px !important;
        font-size: 0.75rem !important;
        box-sizing: border-box !important;
        line-height: 1 !important;
    }
    .recepcion-grilla #tablaGrillaGuias td select.select-serie-guia:disabled {
        background-color: #e9ecef !important;
        color: #6c757d !important;
        cursor: not-allowed !important;
        border-color: #ced4da !important;
        opacity: 1 !important;
    }
    /* Internet Explorer/Edge native arrow */
    .recepcion-grilla #tablaGrillaGuias td select.select-serie-guia::-ms-expand {
        display: none !important;
    }
    .recepcion-grilla #tablaGrillaGuias td select.select-serie-guia {
        background-repeat: no-repeat !important;
        background-position: center right !important;
    }
    /* Forzar fondo blanco en el select de Serie para que destaque sobre filas sombreadas */
    .recepcion-grilla #tablaGrillaGuias td select.select-serie-guia,
    .recepcion-grilla #tablaGrillaGuias td .choices__inner.select-serie-guia {
        background-color: #ffffff !important;
        background-image: none !important;
        color: inherit !important;
        border-color: #ced4da !important;
    }

    /* Mantener el sombreado por filas (zebra) en las celdas; solo los controles internos deben ser blancos */
    .recepcion-grilla #tablaGrillaGuias td.celda-producto-obs select,
    .recepcion-grilla #tablaGrillaGuias td.celda-producto-obs input,
    .recepcion-grilla #tablaGrillaGuias td.celda-producto-obs textarea,
    .recepcion-grilla #tablaGrillaGuias td.celda-producto-obs .choices {
        background-color: #ffffff !important;
        background-image: none !important;
        color: inherit !important;
    }
    .recepcion-grilla #tablaGrillaGuias td select.select-obs-producto:focus,
    .recepcion-grilla #tablaGrillaGuias td select.select-obs:focus,
    .recepcion-grilla #tablaGrillaGuias td select.select-obs-producto:active,
    .recepcion-grilla #tablaGrillaGuias td select.select-obs:active {
        height: 30px !important;
        min-height: 30px !important;
    }

    /* Asegurar que los dropdowns de Choices y selects aparezcan por encima de la grilla */
    .choices__list--dropdown,
    .choices__container--open .choices__list--dropdown,
    .choices__list--dropdown.is-active {
        z-index: 3000 !important;
    }

    /* Elevar selects nativos y elementos contenedores cuando estén abiertos/focus */
    .custom-dropdown,
    .select-header-producto,
    .select-obs {
        position: relative;
        z-index: 2100;
    }
    
    /* Asegurar que los selectores del formulario principal (origen, empresa, ruc, chofer, brevete) se vean por encima de la grilla */
    #seccionDatosRecepcion .custom-dropdown,
    #origen, #empresa, #ruc, #chofer, #brevete, #turno {
        position: relative !important;
    }
    
    /* Contenedores de los form-groups con selectores */
    #seccionDatosRecepcion .col-md-6,
    #seccionDatosRecepcion .col-md-7,
    #seccionDatosRecepcion .col-md-5 {
        position: relative;
        z-index: 1;
    }
    
    /* Cuando un contenedor tiene un select activo, elevar su z-index para que aparezca sobre otros campos */
    #seccionDatosRecepcion .col-md-6.select-active,
    #seccionDatosRecepcion .col-md-7.select-active,
    #seccionDatosRecepcion .col-md-5.select-active {
        z-index: 9999 !important;
    }
    
    /* Cuando un select está en focus o abierto, elevar su z-index */
    #seccionDatosRecepcion .custom-dropdown:focus,
    #seccionDatosRecepcion .custom-dropdown:focus-within,
    #origen:focus, #empresa:focus, #ruc:focus, #chofer:focus, #brevete:focus, #turno:focus {
        z-index: 3500 !important;
    }

    /* Si la tabla hace sticky y oculta elementos, dar prioridad a los selects del formulario */
    .table-responsive {
        overflow: auto;
    }

    /* Cuando un select del formulario esté abierto, permitir que la grilla se muestre por encima (evitar clipping) */
    body.select-open .table-responsive {
        overflow: visible !important;
        z-index: 0 !important;
    }
    
    /* IMPORTANTE: Forzar overflow visible en la card y card-body cuando hay select activo */
    body.select-open .card,
    body.select-open .card-body,
    body.select-open #seccionDatosRecepcion {
        overflow: visible !important;
    }
    
    /* Elevar dropdowns de Choices con máxima prioridad */
    .choices__list--dropdown,
    .choices__list[aria-expanded="true"],
    .choices[data-type*="select"] .choices__list--dropdown {
        z-index: 999999 !important;
    }
    
    /* CRITICO: El contenedor .choices debe tener position relative y z-index alto cuando está abierto */
    .choices.is-open,
    .choices.is-focused {
        position: relative !important;
        z-index: 999998 !important;
    }
    
    /* Cuando hay un select activo, elevar TODA la jerarquía de contenedores */
    body.select-open .col-lg-6,
    body.select-open .card,
    body.select-open .card-body,
    body.select-open #seccionDatosRecepcion,
    body.select-open form {
        position: relative;
        z-index: auto;
    }
    
    /* Elevar específicamente la fila (row) que contiene los selects activos */
    body.select-open #seccionDatosRecepcion .row {
        position: relative;
        z-index: 10000;
    }
    
    /* Vista previa responsiva */
    #valePreview {
        overflow-y: auto;
        max-height: 700px;
    }
    
    /* Ajustes para pantallas pequeñas */
    @media (max-width: 991px) {
        .col-lg-6 {
            margin-bottom: 1rem;
        }
    }

    /* Estilos para notificaciones flotantes (overlay) */
    #floatingMsgContainer {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        pointer-events: none; /* Contenedor no captura eventos; los mensajes sí */
    }
                                    #origen { min-width: 260px !important; max-width: 560px !important; white-space: nowrap !important; }
    .floating-msg {
        pointer-events: auto;
        box-sizing: border-box;
    }

    .floating-success { background: #ecfdf5; border: 1px solid #bbf7d0; }
    .floating-warning { background: #fff7ed; border: 1px solid #fed7aa; }
    .floating-error   { background: #fff1f2; border: 1px solid #fecaca; }

    /* Estilos para los botones de acción (coincidir con Recepciones Internas) */
    .btn-action {
        border-radius: 8px;
        padding: 10px 22px;
        font-weight: 700;
        color: #0f172a;
        background: transparent;
        border: 1px solid rgba(15,23,42,0.06);
        transition: all .12s ease-in-out;
        box-shadow: 0 1px 0 rgba(0,0,0,0.02);
        font-size: 0.95rem;
    }
    .btn-action:hover { transform: translateY(-1px); }
    .btn-action:disabled { opacity: 0.55; cursor: not-allowed; }

    /* Variantes con color tenue al cargar y más pronunciado al presionar/hover */
    .btn-new {
        color: #16a34a;
        border-color: rgba(16,185,129,0.28);
        background: rgba(16,185,129,0.06);
    }
    .btn-new:active, .btn-new:focus, .btn-new:hover {
        background: rgba(16,185,129,0.14);
        box-shadow: 0 2px 6px rgba(16,185,129,0.06);
    }

    .btn-save {
        color: #2563eb;
        border-color: rgba(59,130,246,0.22);
        background: rgba(59,130,246,0.06);
    }
    .btn-save:active, .btn-save:focus, .btn-save:hover {
        background: rgba(59,130,246,0.14);
        box-shadow: 0 2px 6px rgba(59,130,246,0.06);
    }

    .btn-edit {
        color: #b45309; /* un naranja más elegante */
        border-color: rgba(245,158,11,0.18);
        background: rgba(245,158,11,0.06);
    }
    .btn-edit:active, .btn-edit:focus, .btn-edit:hover {
        background: rgba(245,158,11,0.14);
        box-shadow: 0 2px 6px rgba(245,158,11,0.06);
    }

    .btn-print {
        color: #374151;
        border: 1px solid rgba(55,65,81,0.12);
        background: rgba(255,255,255,0.98);
        padding: 10px 12px;
    }
    .btn-print:hover { background: rgba(244,246,248,0.98); }

    /* Mantener el icono dentro del botón pequeño */
    .btn-print i { font-size: 1.05rem; }

    /* Badge tipo 'pill' para el correlativo (coincidir con diseño de otros módulos) */
    .correlativo-vale {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 8px 18px;
        border-radius: 999px;
        background: linear-gradient(180deg,#fef08a,#f59e0b);
        color: #0b1726;
        font-weight:700;
        box-shadow: 0 6px 18px rgba(245,158,11,0.12);
        border: 1px solid rgba(0,0,0,0.06);
        font-size: 0.95rem;
    }
    .correlativo-vale .code-vale {
        color: #0b47ff; /* azul para el código */
        margin-left: 6px;
        font-family: monospace;
    }
    
    /* Estilos para colapsar sección de datos */
    #seccionDatosRecepcion {
        transition: all 0.3s ease-in-out;
    }

    /* Ajustes específicos: alinear turno y prevenir wrap en origen */
    .d-flex.align-items-center .form-label { margin-bottom: 0.25rem; }
    #origen { min-width: 260px !important; max-width: 560px !important; white-space: nowrap !important; }
    #turno { display: inline-block; vertical-align: middle; }
    /* Bajar el contenedor del turno para que quede al mismo nivel que Fecha/Hora */
    .turno-wrap { align-self: flex-end; margin-top: 8px; transform: translateY(6px); position: relative; }
    .turno-container { flex: 0 0 160px; }
    .turno-wrap .form-check { margin-bottom: 0.15rem; margin-top: 0; display:flex; align-items:center; }
    .turno-wrap label.form-label { margin-bottom: 0.08rem; }
    /* Ajuste extra para navegadores/zoom donde la altura de inputs difiere */
    .turno-wrap.adjust-down { transform: translateY(8px) !important; }
    /* Asegurar que cuando el turno está activo su contenedor quede por encima de otros selects */
    .turno-wrap.select-active { z-index: 999999 !important; }
    #turno:focus, #turno:active { z-index: 1000000; position: relative; }
    /* Forzar prioridad del dropdown de Choices.js sin afectar el layout: el contenedor .choices es relativo
       y la lista .choices__list--dropdown se posiciona absolute encima (no empuja elementos). */
    .turno-wrap .choices { position: relative !important; z-index: 2000000 !important; }
    .turno-wrap select { position: relative !important; z-index: 2000000 !important; }
    .turno-wrap .choices__list--dropdown {
        position: absolute !important;
        top: calc(100% + 4px) !important;
        left: 0 !important;
        z-index: 3000000 !important;
        transform: none !important;
        box-shadow: 0 6px 18px rgba(15,23,42,0.08) !important;
    }
    
    /* Solo aplicar overflow hidden cuando está colapsado */
    #seccionDatosRecepcion.collapsed {
        overflow: hidden !important;
    }
    
    #headerDatosRecepcion {
        user-select: none;
    }
    
    #btnToggleDatos {
        transition: transform 0.3s ease;
    }
    
    #btnToggleDatos:hover {
        text-decoration: none !important;
        opacity: 0.8;
    }
    
    /* Evitar parpadeo en vista previa */
    #valePreview img {
        opacity: 1;
        transition: opacity 0.2s ease-in-out;
    }

    /* FIX: Sobrescribir reglas globales de Choices que fijaban altura a 38px
       y provocaban un scrollbar adicional en el contenedor. Aquí forzamos
       que el contenedor .choices y .choices__inner no limiten la altura,
       dejando el scroll sólo a .choices__list--dropdown (el dropdown). */
    /* Scopear fixes de Choices.js solo a la sección de datos para no afectar la grilla */
    #seccionDatosRecepcion .choices, #seccionDatosRecepcion .choices .choices__inner, #seccionDatosRecepcion .choices[data-type*="select-one"] .choices__inner {
        min-height: auto !important;
        height: auto !important;
        max-height: none !important;
        overflow: visible !important;
    }
    #seccionDatosRecepcion .choices__list--dropdown {
        max-height: 320px !important;
        overflow-y: auto !important;
    }
    /* Evitar doble scrollbar dentro de la sección de datos */
    #seccionDatosRecepcion .choices__list--dropdown .choices__list {
        overflow: visible !important;
        max-height: none !important;
        height: auto !important;
        position: static !important;
    }
    /* Asegurar que el input clonado no provoque barra adicional (solo datos) */
    #seccionDatosRecepcion .choices__input--cloned {
        display: block !important;
        box-sizing: border-box !important;
        width: 100% !important;
    }
    /* Ocultar cualquier select nativo que Choices marque como oculto o que quede dentro de .choices__inner
       para evitar que el navegador muestre su propio dropdown (doble scrollbar). */
    #seccionDatosRecepcion select[data-choices-hidden],
    #seccionDatosRecepcion select[hidden],
    #seccionDatosRecepcion .choices__inner > select {
        display: none !important;
        visibility: hidden !important;
        position: absolute !important;
        left: -9999px !important;
        top: -9999px !important;
        height: 0 !important;
        overflow: hidden !important;
    }
</style>
<style>
    /* Estilo para badge de fecha nocturna */
    #badgeFechaNocturno {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-left: 6px;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 600;
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffc107;
        white-space: nowrap;
        vertical-align: middle;
        cursor: default;
    }
    #badgeFechaNocturno i {
        font-size: 0.75rem;
    }
</style>
<script>
    // Evitar que los dropdowns de los selects del formulario queden por debajo de la grilla y otros campos
    (function(){
        const selectorIds = ['turno','origen','empresa','ruc','chofer','brevete'];
        const timeoutHide = 300;
        const contenedorGrilla = document.getElementById('contenedorGrillaGuias');
        let currentActiveColumn = null;

        function markOpen(selectElement) { 
            document.body.classList.add('select-open'); 
            
            // Forzar overflow visible en el contenedor de la grilla
            if (contenedorGrilla) {
                contenedorGrilla.style.overflow = 'visible';
            }
            
            // Elevar z-index del contenedor padre (columna) del select
            if (selectElement) {
                const column = selectElement.closest('.col-md-6, .col-md-7, .col-md-5');
                if (column) {
                    // Remover clase de columna anterior si existe
                    if (currentActiveColumn && currentActiveColumn !== column) {
                        currentActiveColumn.classList.remove('select-active');
                    }
                    column.classList.add('select-active');
                    currentActiveColumn = column;
                }
            }
        }
        
        function markCloseDelayed() { 
            setTimeout(()=>{
                document.body.classList.remove('select-open');
                
                // Restaurar overflow auto en el contenedor de la grilla
                if (contenedorGrilla) {
                    contenedorGrilla.style.overflow = 'auto';
                }
                
                // Remover clase select-active de la columna
                if (currentActiveColumn) {
                    currentActiveColumn.classList.remove('select-active');
                    currentActiveColumn = null;
                }
            }, timeoutHide); 
        }

        // Esperar a que el DOM esté listo
        function setupListeners() {
            selectorIds.forEach(id => {
                const el = document.getElementById(id);
                if (!el) return;
                
                // para selects nativos
                el.addEventListener('focus', () => markOpen(el));
                el.addEventListener('mousedown', () => markOpen(el));
                el.addEventListener('blur', markCloseDelayed);
                
                // también cubrir el caso de cambios por teclado
                el.addEventListener('keydown', (e)=>{ if(e.key === 'Escape') markCloseDelayed(); });
            });

            // Si usas Choices.js y convierte selects en componentes, detectar clicks en sus contenedores
            document.addEventListener('click', function(e){
                const choicesContainer = e.target.closest('.choices');
                if (choicesContainer) {
                    // Si el click está dentro de un choices relacionado a nuestros selects, marcar abierto
                    const related = choicesContainer.previousElementSibling;
                    if (related && selectorIds.includes(related.id)) {
                        markOpen(related);
                    }
                }
                // Si el click es fuera de los selects, cerrar
                if (!e.target.closest('.choices') && !e.target.closest('select')) {
                    markCloseDelayed();
                }
            }, true);

            // Mejora: algunos controles (como el turno) no están dentro de columnas con clase .col-md-*,
            // así que, al marcar abierto, buscamos un contenedor alternativo (.turno-wrap) si no se encuentra
            // una columna estándar. Esto asegura que el markOpen() eleve correctamente el z-index.
            const originalMarkOpen = markOpen;
            markOpen = function(selectElement){
                document.body.classList.add('select-open');
                if (contenedorGrilla) contenedorGrilla.style.overflow = 'visible';
                if (!selectElement) return;
                let column = selectElement.closest('.col-md-6, .col-md-7, .col-md-5');
                if (!column) {
                    column = selectElement.closest('.turno-wrap') || selectElement.parentElement || null;
                }
                if (column) {
                    if (currentActiveColumn && currentActiveColumn !== column) {
                        currentActiveColumn.classList.remove('select-active');
                    }
                    column.classList.add('select-active');
                    currentActiveColumn = column;
                }
            };
        }

        // Ejecutar cuando el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupListeners);
        } else {
            setupListeners();
        }
        // Listeners adicionales para asegurar que el contenedor .turno-wrap reciba la clase select-active
        (function(){
            const turno = document.getElementById('turno');
            if (!turno) return;
            const wrap = turno.closest('.turno-wrap');
            function addActive(){ if(wrap) wrap.classList.add('select-active'); }
            function removeActive(){ if(wrap) wrap.classList.remove('select-active'); }
            turno.addEventListener('focus', addActive);
            turno.addEventListener('mousedown', addActive);
            turno.addEventListener('click', addActive);
            turno.addEventListener('blur', removeActive);
            // Para Choices.js: si existe un container generado, también escuchar su apertura
            document.addEventListener('click', function(e){
                const choices = e.target.closest('.choices');
                if (choices && wrap && choices.previousElementSibling === turno) addActive();
            }, true);
        })();

        // Mejor manejo: al abrir turno, bajar z-index de Empresa y Chofer y mover dropdown de Choices.js a body
        (function(){
            const turnoEl = document.getElementById('turno');
            if (!turnoEl) return;
            const empresaEl = document.getElementById('empresa');
            const choferEl = document.getElementById('chofer');
            let movedDropdown = null;
            let originalParent = null;

            function lowerOthers(){
                [empresaEl, choferEl].forEach(el=>{
                    if (!el) return;
                    const col = el.closest('.col-md-7, .col-md-5, .col-md-6') || el.parentElement;
                    if (!col) return;
                    // store previous inline z-index
                    col.dataset._prevZ = col.style.zIndex || '';
                    col.dataset._prevPos = col.style.position || '';
                    col.style.position = 'relative';
                    col.style.zIndex = '1';
                });
            }

            function restoreOthers(){
                [empresaEl, choferEl].forEach(el=>{
                    if (!el) return;
                    const col = el.closest('.col-md-7, .col-md-5, .col-md-6') || el.parentElement;
                    if (!col) return;
                    if (col.dataset._prevZ !== undefined) col.style.zIndex = col.dataset._prevZ;
                    if (col.dataset._prevPos !== undefined) col.style.position = col.dataset._prevPos;
                    delete col.dataset._prevZ; delete col.dataset._prevPos;
                });
            }

            function moveChoicesDropdownToBody(){
                // buscar dropdown generado por Choices.js dentro del wrap
                const wrap = turnoEl.closest('.turno-wrap');
                if (!wrap) return;
                const dropdown = wrap.querySelector('.choices__list--dropdown');
                if (!dropdown) return null;
                // save original parent/nextSibling
                originalParent = dropdown.parentElement;
                dropdown.dataset._origNext = dropdown.nextSibling ? '1' : '0';
                // compute position relative to viewport
                const rect = turnoEl.getBoundingClientRect();
                dropdown.style.position = 'fixed';
                dropdown.style.left = rect.left + 'px';
                dropdown.style.top = (rect.bottom + 4) + 'px';
                dropdown.style.width = rect.width + 'px';
                dropdown.style.zIndex = '4000000';
                document.body.appendChild(dropdown);
                movedDropdown = dropdown;
                return dropdown;
            }

            function restoreChoicesDropdown(){
                if (!movedDropdown) return;
                // try to put back into original parent
                if (originalParent) originalParent.appendChild(movedDropdown);
                movedDropdown.style.position = '';
                movedDropdown.style.left = '';
                movedDropdown.style.top = '';
                movedDropdown.style.width = '';
                movedDropdown.style.zIndex = '';
                movedDropdown = null; originalParent = null;
            }

            function onOpen(){
                lowerOthers();
                // try move dropdown after short delay (Choices may render it)
                setTimeout(()=>{
                    moveChoicesDropdownToBody();
                }, 60);
            }

            function onClose(){
                restoreChoicesDropdown();
                restoreOthers();
            }

            turnoEl.addEventListener('focus', onOpen);
            turnoEl.addEventListener('click', onOpen);
            turnoEl.addEventListener('blur', onClose);
            // also catch clicks outside to restore
            document.addEventListener('click', function(e){
                if (!e.target.closest('.turno-wrap') && !e.target.closest('#turno')) onClose();
            }, true);
        })();
    })();
</script>
