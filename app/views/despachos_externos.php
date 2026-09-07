<script>
	window.usuarioActual = "<?= $_SESSION['user']['NombresApellidos'] ?? '' ?>";
	window.usuarioUsername = "<?= $_SESSION['user']['username'] ?? '' ?>";
	window.turnosData = <?php echo json_encode($turnos); ?>;
	window.horaActual = "<?= $horaActual ?>";
	window.destinosData = <?php echo json_encode($destinos); ?>;
	window.placasData = <?php echo json_encode($placas); ?>;
	</script>
	<script>
	window.productosData = <?php echo json_encode($productos); ?>;
</script>
<script>
	window.choferesData = <?php echo json_encode($choferes); ?>;
	window.transportistasData = <?php echo json_encode($transportistas); ?>;
	</script>
<!-- Choices.js para combos con búsqueda -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<!-- Incluir CSS personalizado para Choices.js -->
<link rel="stylesheet" href="<?= asset_url('css/choices-custom.css?v=1') ?>">
<style>
	/* Forzar altura compacta en selects y Choices.js */
	.select-tall,
	.select-tall.form-select,
	.select-tall.custom-dropdown,
	.choices__inner,
	.choices[data-type*=select-one] .choices__inner {
		height: 32px !important;
		min-height: 32px !important;
		max-height: 36px !important;
		font-size: 1rem !important;
		padding-top: 2px !important;
		padding-bottom: 2px !important;
		line-height: 28px !important;
		box-sizing: border-box;
	}
	.choices__list--single {
		padding: 0 8px !important;
	}
    /* Forzar que los textareas relacionados tengan la misma altura que `guiaRemision` */
    /* Forzar que los textareas relacionados tengan la misma altura que `guiaRemision` */
    #guiaRemision, #despachador, #comentarios {
        height: 38px !important;
        min-height: 38px !important;
        max-height: 38px !important;
        padding-top: .375rem !important;
        padding-bottom: .375rem !important;
        line-height: 1.2 !important;
        resize: none !important;
        box-sizing: border-box !important;
        padding-top: .375rem !important;
        padding-bottom: .375rem !important;
        line-height: 1.2 !important;
        resize: none !important;
        box-sizing: border-box !important;
    }

    /* Transportista: forzar ellipsis para evitar que el texto largo expanda el control y oculte etiquetas inferiores */
    #transportista + .choices .choices__inner .choices__list--single,
    #transportista + .choices .choices__inner .choices__list--single .choices__item {
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        max-width: 100% !important;
        width: 100% !important;
        display: block !important;
    }

	/* Estilos para los botones de acción */
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
		color: #b45309;
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
<!-- Incluir y ejecutar el JS de despachos externos (versión consolidada v2) -->
<script src="<?= BASE_URL ?>/js/autocorrector.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>/js/diccionario_espanol_basico.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>/js/fecha-efectiva-nocturno.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>/js/despachosexternos.js?v=<?= time() ?>"></script>
	<script>
		window.destinosData = <?php echo json_encode($destinos); ?>;
	</script>

<div class="container py-0" style="padding-top:0 !important; margin-top:0 !important; position:relative;">
	<div class="despacho-header d-flex align-items-center justify-content-start mb-0">
		<div class="d-flex align-items-center gap-4 flex-nowrap" style="white-space:nowrap;">
			<i class="bi bi-truck-flatbed display-5 text-primary me-2"></i>
			<h2 class="fw-bold mb-0" style="color:#1a237e; letter-spacing:0.5px; font-size:2rem; white-space:nowrap;">Despachos Externos</h2>
		</div>
		<div class="d-flex align-items-center gap-2 ms-4">
			<button type="button" id="btnNuevo" class="btn btn-action btn-new keep-enabled">Nuevo</button>
			<button type="button" id="btnGuardar" class="btn btn-action btn-save keep-enabled" disabled>Guardar</button>
			<button type="button" id="btnModificar" class="btn btn-action btn-edit keep-enabled" disabled>Modificar</button>
			<button type="button" id="btnImprimir" class="btn btn-action btn-print keep-enabled" title="Imprimir"><i class="bi bi-printer"></i></button>
			<div class="bg-warning bg-gradient rounded-4 shadow-sm px-3 py-1 text-center d-flex align-items-center justify-content-center flex-nowrap" style="min-width:120px; margin-left:80px; white-space:nowrap;">
				<span class="fw-bold me-1" style="font-size:1.05rem; color:#7c4700; letter-spacing:1px; vertical-align:middle; white-space:nowrap;">N° Vale:</span>
				<span id="correlativoVale" class="fw-bolder" style="font-size:1.25rem; color:#1a237e; letter-spacing:2px; vertical-align:middle; white-space:nowrap;">000001</span>
			</div>
		</div>
	</div>

	<div class="card shadow-sm border-0 mb-3" style="max-width:1150px; margin:auto; padding-bottom:2px;">
		<div class="card-body py-3 px-3">
			<form autocomplete="off">
				   <div class="row g-3 align-items-start">
					   <!-- Fecha -->
					   <div class="col-md-3">
					    <label for="fecha" class="form-label">Fecha</label>
					    <input type="date" class="form-control" id="fecha" name="fecha" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
					    <div id="badgeFechaNocturno" class="d-none" style="margin-top:4px;" title="Turno nocturno activo - fecha ajustada automáticamente">
					     <i class="bi bi-moon-stars"></i>
					     <span>Fecha a registrar: <strong id="fechaEfectivaDisplay"></strong></span>
					    </div>
					   </div>
					   <!-- Turno + Checkbox -->
					   <div class="col-md-3">
						   <div class="d-flex align-items-end justify-content-between mb-1">
							   <label for="turno" class="form-label mb-0">Turno</label>
							   <div class="form-check ms-2 mb-0" style="padding-bottom:0;">
								   <input class="form-check-input align-middle" type="checkbox" id="chkTurno" name="chkTurno" style="margin-bottom:0;">
								   <label class="form-check-label align-middle" for="chkTurno" style="font-size:0.97rem; margin-bottom:0;">Manual</label>
							   </div>
						   </div>
						<select class="form-select form-control-lg custom-dropdown align-middle" id="turno" name="turno" required style="vertical-align:middle; height:38px; min-height:38px;">
							   <option value="">Seleccione</option>
							   <?php if (!empty($turnos)) foreach ($turnos as $t): ?>
								   <option value="<?= htmlspecialchars($t['Id']) ?>"><?= htmlspecialchars($t['Turno']) ?></option>
							   <?php endforeach; ?>
						   </select>
					   </div>
					   <!-- Despachador (usuario logueado) -->
					   <div class="col-md-3">
						   <label for="despachador" class="form-label">Despachador</label>
						   <textarea class="form-control" id="despachador" name="despachador" rows="1" style="min-height:38px; max-width:100%; height:38px; resize:none;"><?php echo htmlspecialchars($usuarioLogueado['NombresApellidos'] ?? '') ?></textarea>
					   </div>
				   </div>
				   <div class="row g-3 align-items-end mt-1">
					   <!-- Destino -->
					   <div class="col-md-4">
						   <label for="destino" class="form-label">Destino</label>
						<select class="form-select form-control-lg custom-dropdown select-tall align-middle" id="destino" name="destino" required style="vertical-align:middle;">
							   <option value="">Seleccione</option>
							   <?php if (!empty($destinos)) foreach ($destinos as $d): ?>
								   <option value="<?= htmlspecialchars($d['Id']) ?>"><?= htmlspecialchars($d['Empresa']) ?></option>
							   <?php endforeach; ?>
						   </select>
					   </div>
					   <!-- RUC -->
					   <div class="col-md-4">
						   <label for="ruc" class="form-label">RUC</label>
						<select class="form-select form-control-lg custom-dropdown select-tall align-middle" id="ruc" name="ruc" required style="vertical-align:middle;">
							   <option value="">Seleccione</option>
							   <?php if (!empty($destinos)) foreach ($destinos as $d): ?>
								   <option value="<?= htmlspecialchars($d['RUC']) ?>" data-destino="<?= htmlspecialchars($d['Id']) ?>"><?= htmlspecialchars($d['RUC']) ?></option>
							   <?php endforeach; ?>
						   </select>
					   </div>
					   <!-- Dirección -->
					   <div class="col-md-4">
						   <label for="direccion" class="form-label">Dirección</label>
						<input type="text" class="form-control" id="direccion" name="direccion" maxlength="200" required>
					   </div>
				   </div>
			</form>
		</div>
	</div>
	<!-- Grupo de responsables -->
	<div class="card shadow-sm border-0 mb-3" style="max-width:1150px; margin:auto; padding-bottom:2px;">
		<div class="card-body py-3 px-3">
			<form autocomplete="off">
				<div class="row g-3 align-items-end">
					   <!-- Chofer -->
					   <div class="col-md-4">
						   <label for="chofer" class="form-label">Chofer</label>
						<select class="form-select form-control-lg custom-dropdown select-tall" id="chofer" name="chofer" required>
							   <option value="">Seleccione</option>
							   <?php if (!empty($choferes)) foreach ($choferes as $c): ?>
								   <option value="<?= htmlspecialchars($c['Id']) ?>"><?= htmlspecialchars($c['ApellidosNombres']) ?></option>
							   <?php endforeach; ?>
						   </select>
					   </div>
					   <!-- Brevete (short) -->
					   <div class="col-md-2">
						   <label for="brevete" class="form-label">Brevete</label>
						<select class="form-select form-control-lg custom-dropdown select-tall" id="brevete" name="brevete">
							<option value="">Seleccione</option>
							   <?php if (!empty($choferes)) foreach ($choferes as $c): ?>
								   <option value="<?= htmlspecialchars($c['Brevete']) ?>" data-chofer="<?= htmlspecialchars($c['Id']) ?>"><?= htmlspecialchars($c['Brevete']) ?></option>
							   <?php endforeach; ?>
						   </select>
					   </div>
					   <!-- Transportista -->
					   <div class="col-md-4">
						   <label for="transportista" class="form-label">Transportista</label>
						<select class="form-select form-control-lg custom-dropdown select-tall" id="transportista" name="transportista" required>
							   <option value="">Seleccione</option>
							   <?php if (!empty($transportistas)) foreach ($transportistas as $t): ?>
								   <option value="<?= htmlspecialchars($t['Id']) ?>"><?= htmlspecialchars($t['Empresa']) ?></option>
							   <?php endforeach; ?>
						   </select>
					   </div>
					   <!-- RUC transportista (short) -->
					   <div class="col-md-2">
						   <label for="ruc_transportista" class="form-label">RUC</label>
						<select class="form-select form-control-lg custom-dropdown select-tall" id="ruc_transportista" name="ruc_transportista">
							<option value="">Seleccione</option>
							   <?php if (!empty($transportistas)) foreach ($transportistas as $t): ?>
								   <option value="<?= htmlspecialchars($t['RUC']) ?>" data-transportista="<?= htmlspecialchars($t['Id']) ?>"><?= htmlspecialchars($t['RUC']) ?></option>
							   <?php endforeach; ?>
						   </select>
					   </div>
						   <!-- Placa Tracto -->
						   <div class="col-md-2">
							   <label for="placa_tracto" class="form-label">Placa Tracto</label>
							   <select class="form-select form-control-lg custom-dropdown select-tall" id="placa_tracto" name="Placa_Tracto">
							    <option value="">Seleccione</option>
							    <?php if (!empty($placas)) foreach ($placas as $pl): ?>
							     <?php if (isset($pl['TipoPlaca']) && strtoupper($pl['TipoPlaca']) === 'TRACTO'): ?>
							     <option value="<?= htmlspecialchars($pl['Placa'] ?? '') ?>" data-constancia="<?= htmlspecialchars($pl['ConstanciaInscripcion'] ?? '') ?>"><?= htmlspecialchars($pl['Placa'] ?? '') ?></option>
							     <?php endif; ?>
							    <?php endforeach; ?>
							   </select>
							  </div>
							  <!-- Constancia Insc. Tracto -->
							  <div class="col-md-2">
							   <label for="constancia_inscripcion" class="form-label">Constancia Insc. Tracto</label>
							   <select class="form-select form-control-lg custom-dropdown select-tall" id="constancia_inscripcion" name="Constancia_Inscripcion">
							    <option value="">Seleccione</option>
							    <?php if (!empty($placas)) foreach ($placas as $pl): ?>
							     <?php if (isset($pl['TipoPlaca']) && strtoupper($pl['TipoPlaca']) === 'TRACTO' && !empty($pl['ConstanciaInscripcion'] ?? '')): ?>
							     <option value="<?= htmlspecialchars($pl['ConstanciaInscripcion'] ?? '') ?>" data-placa="<?= htmlspecialchars($pl['Placa'] ?? '') ?>"><?= htmlspecialchars($pl['ConstanciaInscripcion'] ?? '') ?></option>
							     <?php endif; ?>
							    <?php endforeach; ?>
							   </select>
							  </div>
							  <!-- Placa Carreta -->
							  <div class="col-md-2">
							   <label for="placa_carreta" class="form-label">Placa Carreta</label>
							   <select class="form-select form-control-lg custom-dropdown select-tall" id="placa_carreta" name="Placa_Carreta">
							    <option value="">Seleccione</option>
							    <?php if (!empty($placas)) foreach ($placas as $pl): ?>
							     <?php if (isset($pl['TipoPlaca']) && strtoupper($pl['TipoPlaca']) === 'CARRETA'): ?>
							     <option value="<?= htmlspecialchars($pl['Placa'] ?? '') ?>" data-constancia="<?= htmlspecialchars($pl['ConstanciaInscripcion'] ?? '') ?>"><?= htmlspecialchars($pl['Placa'] ?? '') ?></option>
							     <?php endif; ?>
							    <?php endforeach; ?>
							   </select>
							  </div>
							  <!-- Constancia Insc. Carreta -->
							  <div class="col-md-2">
							   <label for="constancia_inscripcion_2" class="form-label">Constancia Insc. Carreta</label>
							   <select class="form-select form-control-lg custom-dropdown select-tall" id="constancia_inscripcion_2" name="Constancia_Inscripcion_2">
							    <option value="">Seleccione</option>
							    <?php if (!empty($placas)) foreach ($placas as $pl): ?>
							     <?php if (isset($pl['TipoPlaca']) && strtoupper($pl['TipoPlaca']) === 'CARRETA' && !empty($pl['ConstanciaInscripcion'] ?? '')): ?>
							     <option value="<?= htmlspecialchars($pl['ConstanciaInscripcion'] ?? '') ?>" data-placa="<?= htmlspecialchars($pl['Placa'] ?? '') ?>"><?= htmlspecialchars($pl['ConstanciaInscripcion'] ?? '') ?></option>
							     <?php endif; ?>
							    <?php endforeach; ?>
							   </select>
						   </div>
						   <!-- Guía Remisión (textarea) -->
						   <div class="col-md-3">
							   <label for="guiaRemision" class="form-label">Guía Remisión</label>
							   <textarea class="form-control" id="guiaRemision" name="guiaRemision" rows="1" style="min-height:38px; max-width:100%; height:38px; resize:none;" placeholder="Ingrese GR (opcional)"></textarea>
						   </div>
				</div>
			</form>
		</div>
	</div>
	<!-- Grupo de productos -->
	<div class="card shadow-sm border-0 mb-3" style="max-width:1150px; margin:auto; padding-bottom:2px;">
		<div class="card-body py-3 px-3">
			<form autocomplete="off">
				<div class="row g-3 align-items-end">
					<!-- Producto -->
					<div class="col-md-4">
						<label for="producto" class="form-label">Producto</label>
						<select class="form-select form-control-lg custom-dropdown select-tall" id="producto" name="producto">
							<option value="">Seleccione</option>
							<?php if (!empty($productos)) foreach ($productos as $p): ?>
								<option value="<?= htmlspecialchars($p['Id']) ?>" data-codigo="<?= htmlspecialchars($p['Codigo']) ?>" data-unidadmedida="<?= htmlspecialchars($p['UnidadMedida'] ?? '') ?>">
									<?= htmlspecialchars($p['Producto']) ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<!-- Código -->
					<div class="col-md-2">
						<label for="codigo" class="form-label">Código</label>
						<input type="text" class="form-control" id="codigo" name="codigo">
					</div>
					<!-- Cantidad -->
					<div class="col-md-2">
						<label for="cantidad" class="form-label">Cantidad</label>
						<input type="number" class="form-control" id="cantidad" name="cantidad" min="1" required>
					</div>
					<!-- Comentarios -->
					<div class="col-md-4">
						<label for="comentarios" class="form-label">Comentarios</label>
						<textarea class="form-control mayusculas" id="comentarios" name="comentarios" rows="1" style="min-height:38px; max-width:100%; height:38px; resize:none;"></textarea>
					</div>
				</div>
			</form>
		</div>
	</div>
	<!-- Botones de gestión de la grilla -->
	<div class="d-flex justify-content-end gap-2 mb-3" style="max-width:1150px; margin:auto;">
		<button type="button" class="btn btn-success" id="btnAgregar"><i class="bi bi-plus-circle me-1"></i> Agregar</button>
		<button type="button" class="btn btn-danger" id="btnQuitar"><i class="bi bi-trash me-1"></i> Quitar</button>
		<button type="button" class="btn btn-secondary" id="btnLimpiar"><i class="bi bi-x-circle me-1"></i> Limpiar</button>
	</div>
	<!-- Grilla de productos a despachar -->
	<div class="card shadow-sm border-0 mb-2" style="max-width:1150px; margin:auto; margin-top:8px;">
		<div class="card-body p-0">
			<div class="table-responsive">
				<table class="table table-hover align-middle mb-0" id="grillaDespacho" style="table-layout:fixed; width:100%;">
					<thead class="table-light">
						<tr>
							<th style="width:40px;">N°</th>
							<th style="width:80px; text-align:center;">Código</th>
							<th style="width:220px;">Producto</th>
							<th style="width:100px; text-align:center;">UM</th>
							<th style="width:60px; text-align:center;">Cantidad</th>
							<th style="width:170px;">Comentarios</th>
						</tr>
					</thead>
					<tbody>
						<!-- Aquí se agregarán dinámicamente las filas -->
					</tbody>
				</table>
				<style>
				/* Alineación consistente: cabeceras y filas centradas por defecto */
				#grillaDespacho thead th {
					text-align: center !important;
					vertical-align: middle !important;
				}
				#grillaDespacho tbody td {
					text-align: center !important;
					vertical-align: middle !important;
					word-break: break-word;
				}
				/* Columnas alineadas a la izquierda: Producto (3) y Comentarios (6) */
				#grillaDespacho tbody td:nth-child(3),
				#grillaDespacho tbody td:nth-child(6) {
					text-align: left !important;
				}
				/* Columnas centradas explícitas: Código(2), UM(4), Cantidad(5) */
				#grillaDespacho tbody td:nth-child(2),
				#grillaDespacho tbody td:nth-child(4),
				#grillaDespacho tbody td:nth-child(5) {
					text-align: center !important;
				}
				</style>
			</div>
		</div>
	</div>
	<!-- Mensaje de error global -->
	<div id="mensajeError" class="alert alert-danger d-none position-fixed top-0 start-50 translate-middle-x mt-3 shadow-lg" style="z-index:2000; min-width:320px; max-width:500px; font-size:1.08rem; font-weight:500; letter-spacing:0.5px; color:#7f1d1d; background:#fee2e2; border:2px solid #fca5a5; border-radius:0.7rem; box-shadow:0 2px 12px #e5e7eb55; text-align:center;"></div>
</div>

<script>
// Sincronización de Placas y Constancias
(function(){
	const placaTractoEl = document.getElementById('placa_tracto');
	const constanciaTractoEl = document.getElementById('constancia_inscripcion');
	const placaCarretaEl = document.getElementById('placa_carreta');
	const constanciaCarretaEl = document.getElementById('constancia_inscripcion_2');

	// Sincronizar Placa Tracto -> Constancia Tracto
	if (placaTractoEl) {
		placaTractoEl.addEventListener('change', function() {
			const placa = this.value;
			if (!placa || !window.placasData) return;
			const found = window.placasData.find(p => p.Placa === placa);
			if (found && constanciaTractoEl) {
				constanciaTractoEl.value = found.ConstanciaInscripcion || '';
				// Si hay Choices.js, actualizar
				if (window.choicesInstances && window.choicesInstances.constancia_inscripcion) {
					try {
						window.choicesInstances.constancia_inscripcion.setChoiceByValue(found.ConstanciaInscripcion || '');
					} catch(e) {}
				}
			}
		});
	}

	// Sincronizar Constancia Tracto -> Placa Tracto
	if (constanciaTractoEl) {
		constanciaTractoEl.addEventListener('change', function() {
			const constancia = this.value;
			if (!constancia || !window.placasData) return;
			const found = window.placasData.find(p => p.ConstanciaInscripcion === constancia);
			if (found && placaTractoEl) {
				placaTractoEl.value = found.Placa || '';
				// Si hay Choices.js, actualizar
				if (window.choicesInstances && window.choicesInstances.placa_tracto) {
					try {
						window.choicesInstances.placa_tracto.setChoiceByValue(found.Placa || '');
					} catch(e) {}
				}
			}
		});
	}

	// Sincronizar Placa Carreta -> Constancia Carreta
	if (placaCarretaEl) {
		placaCarretaEl.addEventListener('change', function() {
			const placa = this.value;
			if (!placa || !window.placasData) return;
			const found = window.placasData.find(p => p.Placa === placa);
			if (found && constanciaCarretaEl) {
				constanciaCarretaEl.value = found.ConstanciaInscripcion || '';
				// Si hay Choices.js, actualizar
				if (window.choicesInstances && window.choicesInstances.constancia_inscripcion_2) {
					try {
						window.choicesInstances.constancia_inscripcion_2.setChoiceByValue(found.ConstanciaInscripcion || '');
					} catch(e) {}
				}
			}
		});
	}

	// Sincronizar Constancia Carreta -> Placa Carreta
	if (constanciaCarretaEl) {
		constanciaCarretaEl.addEventListener('change', function() {
			const constancia = this.value;
			if (!constancia || !window.placasData) return;
			const found = window.placasData.find(p => p.ConstanciaInscripcion === constancia);
			if (found && placaCarretaEl) {
				placaCarretaEl.value = found.Placa || '';
				// Si hay Choices.js, actualizar
				if (window.choicesInstances && window.choicesInstances.placa_carreta) {
					try {
						window.choicesInstances.placa_carreta.setChoiceByValue(found.Placa || '');
					} catch(e) {}
				}
			}
		});
	}
})();
</script>
