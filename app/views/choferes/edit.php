<div class="container-fluid d-flex justify-content-center align-items-center" style="min-height:80vh;">
    <div class="card shadow-sm border-0" style="width:100%; max-width:480px;">
        <div class="card-body p-4">
            <div class="mb-4 d-flex align-items-center">
                <i class="bi bi-person-lines-fill display-6 text-primary me-2"></i>
                <div class="d-flex flex-column">
                    <h3 class="mb-0 fw-bold" style="letter-spacing:0.5px; color:#1a237e;">Editar Chofer</h3>
                    <a href="https://slcp.mtc.gob.pe/" target="_blank" rel="noopener noreferrer" class="small text-decoration-none text-primary mt-1">Link de Búsqueda (SLCP)</a>
                </div>
                <div class="form-check form-switch ms-auto">
                    <input class="form-check-input" type="checkbox" id="manualToggleEdit" disabled>
                    <label class="form-check-label small text-muted ms-1" for="manualToggleEdit">Manual</label>
                </div>
            </div>
            <?php
 if (!empty($errores)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errores as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <form method="post" action="<?= app_url('choferes/update/' . urlencode($Id)) ?>" id="formChoferEdit" autocomplete="off">
                <div class="mb-3" style="max-width:480px;">
                    <label for="Nacionalidad" class="form-label">Nacionalidad</label>
                    <select id="Nacionalidad" name="Nacionalidad" class="form-select form-select-lg">
                        <option value="" disabled <?= empty($Nacionalidad) ? 'selected' : '' ?>></option>
                        <option value="PERUANO" <?= (isset($Nacionalidad) && $Nacionalidad === 'PERUANO') ? 'selected' : '' ?>>PERUANO</option>
                        <option value="EXTRANJERO" <?= (isset($Nacionalidad) && $Nacionalidad === 'EXTRANJERO') ? 'selected' : '' ?>>EXTRANJERO</option>
                    </select>
                </div>
                <!-- Campo documento con botón consultar (DNI/Carnet) -->
                <input type="hidden" id="DocIdentidad" name="DocIdentidad" value="<?= htmlspecialchars($DocIdentidad ?? '') ?>">
                <div id="documentoGroup" class="mb-3" style="max-width:480px;"></div>
                <div class="mb-3">
                    <label for="ApellidosPaterno" class="form-label">Apellido Paterno</label>
                    <input type="text" class="form-control form-control-lg mayusculas" id="ApellidosPaterno" name="ApellidosPaterno" required value="<?= htmlspecialchars($ApellidosPaterno ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="ApellidoMaterno" class="form-label">Apellido Materno</label>
                    <input type="text" class="form-control form-control-lg mayusculas" id="ApellidoMaterno" name="ApellidoMaterno" required value="<?= htmlspecialchars($ApellidoMaterno ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="Nombres" class="form-label">Nombres</label>
                    <input type="text" class="form-control form-control-lg mayusculas" id="Nombres" name="Nombres" required value="<?= htmlspecialchars($Nombres ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="ApellidosNombres" class="form-label">Apellidos y Nombres</label>
                    <input type="text" class="form-control form-control-lg bg-light mayusculas" id="ApellidosNombres" name="ApellidosNombres" value="<?= htmlspecialchars($ApellidosNombres ?? '') ?>" readonly>
                </div>
                <div class="mb-4">
                    <label for="Brevete" class="form-label">Brevete</label>
                    <input type="text" class="form-control form-control-lg mayusculas" id="Brevete" name="Brevete" required value="<?= htmlspecialchars($Brevete ?? '') ?>">
                </div>
                <div class="d-flex justify-content-between gap-2">
                    <button type="submit" class="btn btn-primary px-4 fw-bold"><i class="bi bi-save me-1"></i>Actualizar</button>
                    <a href="<?= app_url('choferes') ?>" class="btn btn-secondary px-4"><i class="bi bi-arrow-left me-1"></i>Cancelar</a>
                </div>
            </form>
        </div>
    </div>
<script>
function updateApellidosNombresEdit() {
    const ap = document.getElementById('ApellidosPaterno').value.trim();
    const am = document.getElementById('ApellidoMaterno').value.trim();
    const n = document.getElementById('Nombres').value.trim();
    document.getElementById('ApellidosNombres').value = (ap + ' ' + am + ' ' + n).trim();
}
// Bloquear campos solicitados al cargar el formulario de edición y preparar toggle Manual
    try {
    const apP = document.getElementById('ApellidosPaterno');
    const apM = document.getElementById('ApellidoMaterno');
    const n = document.getElementById('Nombres');
    const an = document.getElementById('ApellidosNombres');
    const brev = document.getElementById('Brevete');
    if (apP) apP.readOnly = true;
    if (apM) apM.readOnly = true;
    if (n) n.readOnly = true;
    if (an) an.readOnly = true; // mantener readonly
    if (brev) brev.readOnly = true;

    const manualToggleEdit = document.getElementById('manualToggleEdit');
    if (manualToggleEdit) {
        manualToggleEdit.checked = false;
        manualToggleEdit.addEventListener('change', function() {
            const enabled = manualToggleEdit.checked;
            if (apP) apP.readOnly = !enabled;
            if (apM) apM.readOnly = !enabled;
            if (n) n.readOnly = !enabled;
            if (brev) brev.readOnly = !enabled;
            // No tocar el select de Nacionalidad ni los inputs de documento aquí:
            // dejar que cambiar la nacionalidad muestre/oculte y permita edición como en crear.
            if (!enabled && typeof updateApellidosNombresEdit === 'function') {
                updateApellidosNombresEdit();
            }
        });
    }
} catch (e) { console.warn('No se pudo bloquear campos iniciales en edición', e); }

// Mantener funcionalidad de actualización del campo compuesto cuando se habilite manualmente
document.getElementById('ApellidosPaterno').addEventListener('input', updateApellidosNombresEdit);
document.getElementById('ApellidoMaterno').addEventListener('input', updateApellidosNombresEdit);
document.getElementById('Nombres').addEventListener('input', updateApellidosNombresEdit);

// Validar longitud exacta del documento antes de guardar
const formChoferEdit = document.getElementById('formChoferEdit');
if (formChoferEdit) {
    formChoferEdit.addEventListener('submit', function(e) {
        const nacionalidad = document.getElementById('Nacionalidad')?.value;
        const docIdentidad = document.getElementById('DocIdentidad')?.value.trim();
        const brevete = document.getElementById('Brevete')?.value.trim();
        
        if (nacionalidad === 'PERUANO') {
            // DNI debe tener exactamente 8 caracteres
            if (!docIdentidad || docIdentidad.length !== 8) {
                e.preventDefault();
                alert('El DNI debe tener exactamente 8 dígitos.');
                const dniInput = document.getElementById('Dni');
                if (dniInput) dniInput.focus();
                return false;
            }
            // Brevete no puede ser mayor a 9 caracteres para peruanos
            if (brevete && brevete.length > 9) {
                e.preventDefault();
                alert('El Brevete no puede tener más de 9 caracteres para un ciudadano peruano.');
                const breveteInput = document.getElementById('Brevete');
                if (breveteInput) breveteInput.focus();
                return false;
            }
            // Brevete debe contener al menos una letra
            if (brevete && !/[A-Za-z]/.test(brevete)) {
                e.preventDefault();
                alert('El Brevete debe contener al menos una letra.');
                const breveteInput = document.getElementById('Brevete');
                if (breveteInput) breveteInput.focus();
                return false;
            }
        } else if (nacionalidad === 'EXTRANJERO') {
            // Carnet debe tener exactamente 9 caracteres
            if (!docIdentidad || docIdentidad.length !== 9) {
                e.preventDefault();
                alert('El Carnet de Extranjería debe tener exactamente 9 caracteres.');
                const carnetInput = document.getElementById('CarnetExtranjeria');
                if (carnetInput) carnetInput.focus();
                return false;
            }
            // Brevete no puede ser mayor a 10 caracteres para extranjeros
            if (brevete && brevete.length > 10) {
                e.preventDefault();
                alert('El Brevete no puede tener más de 10 caracteres para un ciudadano extranjero.');
                const breveteInput = document.getElementById('Brevete');
                if (breveteInput) breveteInput.focus();
                return false;
            }
            // Brevete debe contener al menos una letra
            if (brevete && !/[A-Za-z]/.test(brevete)) {
                e.preventDefault();
                alert('El Brevete debe contener al menos una letra.');
                const breveteInput = document.getElementById('Brevete');
                if (breveteInput) breveteInput.focus();
                return false;
            }
        }
    });
}
</script>
</div>
<style>
    .card label.form-label {
        font-weight: 500;
        color: #374151;
    }
    .card .form-control-lg {
        font-size: 1.08rem;
        padding: 0.5rem 0.75rem;
        border-radius: 0.5rem;
    }
    .card .form-control-lg:focus {
        box-shadow: 0 0 0 0.15rem #b6d4fe;
        border-color: #1976d2;
    }
    .card .btn-primary, .card .btn-secondary {
        font-size: 1.05rem;
        border-radius: 0.45rem;
        box-shadow: 0 1px 2px rgba(60,60,60,0.04);
    }
</style>

<style>
/* Alinear apariencia de campos readonly/disabled en el formulario editar chofer */
#formChoferEdit input[readonly],
#formChoferEdit .form-control[readonly],
#formChoferEdit input:disabled,
#formChoferEdit .form-control:disabled {
    background-color: var(--bs-secondary-bg) !important;
    color: var(--bs-secondary-color) !important;
    border-color: var(--bs-light-border-subtle) !important;
    opacity: 1 !important;
}
</style>
</style>
<script src="<?= BASE_URL ?>/js/autocorrector.js?v=<?= time() ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const nacionalidadSelect = document.getElementById('Nacionalidad');
    const documentoGroup = document.getElementById('documentoGroup');
    const hiddenDoc = document.getElementById('DocIdentidad');
    const apP = document.getElementById('ApellidosPaterno');
    const apM = document.getElementById('ApellidoMaterno');
    const nombres = document.getElementById('Nombres');
    const apellidosNombresInput = document.getElementById('ApellidosNombres');
    const breveteInput = document.getElementById('Brevete');

    function setIndividualNameFields(apPVal, apMVal, nombresVal) {
        if (apP && typeof apPVal === 'string') apP.value = apPVal.trim().toUpperCase();
        if (apM && typeof apMVal === 'string') apM.value = apMVal.trim().toUpperCase();
        if (nombres && typeof nombresVal === 'string') nombres.value = nombresVal.trim().toUpperCase();
        if (apellidosNombresInput) {
            const parts = [apPVal || '', apMVal || '', nombresVal || ''].join(' ').trim();
            if (parts) apellidosNombresInput.value = parts.toUpperCase();
        }
    }

    function clearPersonFields() {
        try {
            if (apP) apP.value = '';
            if (apM) apM.value = '';
            if (nombres) nombres.value = '';
            if (apellidosNombresInput) apellidosNombresInput.value = '';
            if (breveteInput) { breveteInput.value = ''; breveteInput.readOnly = true; }
            if (hiddenDoc) hiddenDoc.value = '';
            const mt = document.getElementById('manualToggleEdit');
            if (mt) { mt.checked = false; mt.dispatchEvent(new Event('change')); }
        } catch(e) { console.warn('clearPersonFields edit error', e); }
    }

    function parseFullNameAndSet(fullName) {
        if (!fullName) return;
        // Limpiar comas que puedan venir de la API
        const cleaned = fullName.replace(/,/g, ' ').trim();
        const toks = cleaned.split(/\s+/).filter(t => t.length > 0);
        if (toks.length === 0) return;
        const apPVal = toks[0] || '';
        const apMVal = toks[1] || '';
        const nombresVal = toks.slice(2).join(' ') || '';
        setIndividualNameFields(apPVal, apMVal, nombresVal);
    }

    function renderDocumentoField() {
        if (!documentoGroup) return;
        documentoGroup.innerHTML = '';
        const current = hiddenDoc ? hiddenDoc.value.trim() : '';
        if (!nacionalidadSelect.value) {
            documentoGroup.innerHTML = '';
            return;
        }
        if (nacionalidadSelect.value === 'PERUANO') {
            documentoGroup.innerHTML = `
                <label for="Dni" class="form-label">DNI</label>
                <div class="input-group input-group-lg">
                    <input type="text" class="form-control" id="Dni" name="Dni" maxlength="8" style="text-transform:uppercase;" autocomplete="off" placeholder="Ingrese DNI">
                    <button type="button" class="btn btn-outline-info" id="btnConsultarDni" title="Consultar DNI" style="min-width:110px; font-weight:600; border-width:2px;">Consultar</button>
                </div>`;
        } else {
            documentoGroup.innerHTML = `
                <label for="CarnetExtranjeria" class="form-label">Carnet de Extranjería</label>
                <div class="input-group input-group-lg">
                    <input type="text" class="form-control" id="CarnetExtranjeria" name="CarnetExtranjeria" maxlength="9" style="text-transform:uppercase;" placeholder="Ingrese número de carnet" autocomplete="off">
                    <button type="button" class="btn btn-outline-info" id="btnConsultarCarnet" title="Consultar Carnet" style="min-width:110px; font-weight:600; border-width:2px;">Consultar</button>
                </div>`;
        }
        // Prefill visible input from hidden value
        setTimeout(() => {
            const dniInput = document.getElementById('Dni');
            const carnetInput = document.getElementById('CarnetExtranjeria');
            if (dniInput && current.length) dniInput.value = current;
            if (carnetInput && current.length) carnetInput.value = current;
            asignarEventosDocumento();
        }, 0);
    }

    function asignarEventosDocumento() {
        const dniInput = document.getElementById('Dni');
        const btnConsultarDni = document.getElementById('btnConsultarDni');
        const carnetInput = document.getElementById('CarnetExtranjeria');
        const btnConsultarCarnet = document.getElementById('btnConsultarCarnet');

        if (btnConsultarDni && dniInput) {
            // Copiado y adaptado de create.php: realiza consulta proxy + consulta a consultarBrevete
            window.consultarDniEdit = function() {
                try {
                    const dniVal = (document.getElementById('Dni')?.value || '').trim();
                    if (hiddenDoc) hiddenDoc.value = dniVal;
                    if (dniVal.length !== 8 || isNaN(dniVal)) {
                        mostrarMensajeDni('Ingrese un DNI válido de 8 dígitos.');
                        document.getElementById('Dni')?.focus();
                        return;
                    }
                    // limpiar campos antes de iniciar la búsqueda
                    try { if (apP) apP.value = ''; if (apM) apM.value = ''; if (typeof nombres !== 'undefined' && nombres) nombres.value = ''; if (apellidosNombresInput) apellidosNombresInput.value = ''; if (breveteInput) breveteInput.value = ''; } catch(e) { console.warn('No se pudo limpiar campos antes de consultar DNI (edit)', e); }

                    btnConsultarDni.disabled = true;
                    btnConsultarDni.textContent = 'Consultando...';

                    let foundInfo = false;
                    let initialHasName = false;
                    let initialHasBrevete = false;

                    fetch(window.APP_URL + '/api/consultarDni', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ dni: dniVal })
                    })
                    .then(r => r.text().then(text => {
                        try { return JSON.parse(text); } catch(err) { return { __raw: text }; }
                    }))
                    .then(data => {
                        if (data && data.__raw) {
                            try {
                                let raw = data.__raw.replace(/<[^>]*>/g, ' ');
                                raw = raw.replace(/\s+/g, ' ').trim();
                                const lines = raw.split(/\r?\n/).map(s => s.trim()).filter(Boolean);
                                let candidate = lines.length ? lines.reduce((a,b) => a.length >= b.length ? a : b, '') : raw;
                                candidate = candidate.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]/g, ' ').replace(/\s+/g, ' ').trim();
                                if (candidate.length >= 4 && candidate.split(/\s+/).length >= 2) {
                                    const blacklist = /fatal|uncaught|typeerror|call to|on line|stack trace|deprecated|warning|notice|php|index\(|<\/html>/i;
                                    if (!blacklist.test(candidate)) {
                                        const toksRaw = candidate.split(/\s+/);
                                        const apPR = toksRaw[toksRaw.length - 2] || '';
                                        const apMR = toksRaw[toksRaw.length - 1] || '';
                                        const nomsR = toksRaw.slice(0, toksRaw.length - 2).join(' ');
                                        setIndividualNameFields(apPR, apMR, nomsR || candidate);
                                        apellidosNombresInput.value = candidate.toUpperCase();
                                        foundInfo = true;
                                        initialHasName = true;
                                    }
                                }
                            } catch (e) { console.warn('No se pudo extraer nombre del raw response (edit)', e); }
                        }

                        if (data) {
                            const src = data.data || data;
                            const api_ap = (src.apellido_paterno || src.apellidoPaterno || '').trim();
                            const api_am = (src.apellido_materno || src.apellidoMaterno || '').trim();
                            const api_nombres = (src.nombres || src.nombre || src.nombre_completo || '').trim();
                            const apellidosNombres = (api_ap + ' ' + api_am + ' ' + api_nombres).trim();
                            if (apellidosNombres) { apellidosNombresInput.value = apellidosNombres.toUpperCase(); foundInfo = true; initialHasName = true; }
                            if (!initialHasName && data.apellidos_nombres) {
                                try { apellidosNombresInput.value = (data.apellidos_nombres || '').toUpperCase(); parseFullNameAndSet(data.apellidos_nombres); foundInfo = true; initialHasName = true; } catch(e) { console.warn('parseFullNameAndSet error edit (apellidos_nombres)', e); }
                            }
                            if (api_ap || api_am) { setIndividualNameFields(api_ap, api_am, api_nombres); foundInfo = true; initialHasName = true; }
                            else if (api_nombres) {
                                const toks = api_nombres.trim().split(/\s+/);
                                if (toks.length >= 2) {
                                    const apPVal = toks[toks.length - 2] || '';
                                    const apMVal = toks[toks.length - 1] || '';
                                    const nomsVal = toks.slice(0, toks.length - 2).join(' ');
                                    setIndividualNameFields(apPVal, apMVal, nomsVal || api_nombres);
                                    foundInfo = true; initialHasName = true;
                                } else { setIndividualNameFields('', '', api_nombres); foundInfo = true; initialHasName = true; }
                            }
                        }

                        if (hiddenDoc) hiddenDoc.value = dniVal;
                        if (data && data.numero_brevete) {
                            breveteInput.value = (data.numero_brevete || '').toUpperCase();
                            foundInfo = true; initialHasBrevete = true;
                            if (data.apellidos_nombres) { apellidosNombresInput.value = (data.apellidos_nombres || '').toUpperCase(); parseFullNameAndSet(data.apellidos_nombres); }
                            if (data.apellido_paterno || data.apellido_materno || data.nombres) {
                                const api_ap = (data.apellido_paterno || '').trim();
                                const api_am = (data.apellido_materno || '').trim();
                                const api_nombres = (data.nombres || '').trim();
                                setIndividualNameFields(api_ap, api_am, api_nombres);
                            }
                            checkBreveteEdit(breveteInput.value).then(cb => { if (cb.exists) mostrarMensajeDni(cb.message || 'El brevete ya está registrado para otro chofer.'); });
                            return null;
                        }
                        return fetch(window.APP_URL + '/api/consultarBrevete', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ brevete: dniVal }) }).then(r2 => r2.json());
                    })
                    .then(res => {
                        let breveteFound = false;
                        if (!res) {
                            if (initialHasBrevete) return;
                            if (initialHasName) {
                                mostrarMensajeDni('El documento no cuenta con brevete.');
                                try { if (manualToggleEdit) manualToggleEdit.checked = true; if (breveteInput) { breveteInput.readOnly = false; breveteInput.focus(); } } catch(e) { console.warn('No se pudo habilitar modo manual (edit) after no brevete', e); }
                            } else if (!foundInfo) {
                                mostrarMensajeDni('No se encontró información para el documento solicitado.');
                                try { if (manualToggleEdit) { manualToggleEdit.checked = true; manualToggleEdit.dispatchEvent(new Event('change')); } if (apP) apP.readOnly = false; if (apM) apM.readOnly = false; if (n) n.readOnly = false; if (breveteInput) { breveteInput.readOnly = false; breveteInput.focus(); } } catch(e) { console.warn('No se pudo forzar habilitación campos edit', e); }
                            }
                            return;
                        }
                        if (res) {
                            const api_ap = (res.apellido_paterno || res.apellidoPaterno || '').trim();
                            const api_am = (res.apellido_materno || res.apellidoMaterno || '').trim();
                            const api_nombres = (res.nombres || res.nombre || res.apellidos_nombres || '').trim();
                            if (res.numero_brevete) { breveteInput.value = (res.numero_brevete || '').toUpperCase(); breveteFound = true; foundInfo = true; checkBreveteEdit(breveteInput.value).then(cb => { if (cb.exists) mostrarMensajeDni(cb.message || 'El brevete ya está registrado para otro chofer.'); }); }
                            if (api_ap || api_am) { setIndividualNameFields(api_ap, api_am, api_nombres); foundInfo = true; }
                            else if (api_nombres) {
                                const toks2 = api_nombres.trim().split(/\s+/);
                                if (toks2.length >= 2) { const apP2 = toks2[toks2.length - 2] || ''; const apM2 = toks2[toks2.length - 1] || ''; const noms2 = toks2.slice(0, toks2.length - 2).join(' '); setIndividualNameFields(apP2, apM2, noms2 || api_nombres); foundInfo = true; } else { setIndividualNameFields('', '', api_nombres); foundInfo = true; }
                            } else if (res.apellidos_nombres) { apellidosNombresInput.value = (res.apellidos_nombres || '').toUpperCase(); parseFullNameAndSet(res.apellidos_nombres); foundInfo = true; }
                            if (initialHasName && !breveteFound) { try { breveteInput.value = ''; } catch(e) {} mostrarMensajeDni('El documento no cuenta con brevete.'); try { if (manualToggleEdit) manualToggleEdit.checked = true; if (breveteInput) { breveteInput.readOnly = false; breveteInput.focus(); } } catch(e) { console.warn('No se pudo habilitar modo manual (brevete missing edit)', e); } }
                        }
                    })
                    .catch(err => {
                        console.error('Error en consultas DNI/brevete (edit)', err);
                        if (!foundInfo) mostrarMensajeDni('No se pudo consultar el DNI o brevete.');
                        try { if (manualToggleEdit) { manualToggleEdit.checked = true; manualToggleEdit.dispatchEvent(new Event('change')); } if (apP) apP.readOnly = false; if (apM) apM.readOnly = false; if (n) n.readOnly = false; if (breveteInput) { breveteInput.readOnly = false; breveteInput.focus(); } } catch(e) { console.warn('No se pudo habilitar modo manual tras error DNI (edit)', e); }
                    })
                    .finally(() => {
                        if (!foundInfo) {
                            mostrarMensajeDni('No se encontró información para el documento solicitado.');
                            try { if (manualToggleEdit) { manualToggleEdit.checked = true; manualToggleEdit.dispatchEvent(new Event('change')); } if (apP) apP.readOnly = false; if (apM) apM.readOnly = false; if (n) n.readOnly = false; if (breveteInput) { breveteInput.readOnly = false; breveteInput.focus(); } } catch(e) { console.warn('No se pudo habilitar modo manual en finally DNI (edit)', e); }
                        }
                        btnConsultarDni.disabled = false;
                        btnConsultarDni.textContent = 'Consultar';
                    });
                } catch(e) { console.error('Error en window.consultarDniEdit', e); }
            };
            btnConsultarDni.addEventListener('click', function(ev) { try { ev.stopPropagation(); } catch(e) {} if (window._consultarDniBtnPendingEdit) return; window._consultarDniBtnPendingEdit = true; setTimeout(() => { window._consultarDniBtnPendingEdit = false; }, 300); if (typeof window.consultarDniEdit === 'function') window.consultarDniEdit(); });
            // Delegated fallback
            document.addEventListener('click', function(ev) { if (ev.target && ev.target.id === 'btnConsultarDni') { if (window._consultarDniPendingEdit) return; window._consultarDniPendingEdit = true; setTimeout(() => { window._consultarDniPendingEdit = false; }, 300); if (typeof window.consultarDniEdit === 'function') window.consultarDniEdit(); } });
            if (dniInput && hiddenDoc) { dniInput.addEventListener('input', function() { hiddenDoc.value = dniInput.value.trim(); }); }
        }

        if (btnConsultarCarnet && carnetInput) {
            btnConsultarCarnet.addEventListener('click', function() {
                const carnet = carnetInput.value.trim();
                if (hiddenDoc) hiddenDoc.value = carnet;
                if (carnet.length < 6) { mostrarMensajeDni('Ingrese un número de carnet válido.'); carnetInput.focus(); return; }
                btnConsultarCarnet.disabled = true;
                btnConsultarCarnet.textContent = 'Consultando...';
                fetch(window.APP_URL + '/api/consultarCarnet', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ carnet: carnet })
                })
                .then(async resp => { let data = null; try { data = await resp.json(); } catch(e){ mostrarMensajeDni('No se encontró información para este carnet.'); try { const mt = document.getElementById('manualToggleEdit'); if (mt) { mt.checked = true; mt.dispatchEvent(new Event('change')); } try { if (apP) apP.readOnly = false; if (apM) apM.readOnly = false; if (n) n.readOnly = false; if (brev) { brev.readOnly = false; brev.focus(); } } catch(innerErr) { console.warn('No se pudo forzar habilitación de campos edit (JSON error)', innerErr); } } catch(err) { console.warn('No se pudo activar modo manual tras error JSON carnet edit', err); } }
                    if (data && data.success && data.apellidos_nombres) {
                        if (apellidosNombresInput) apellidosNombresInput.value = data.apellidos_nombres.toUpperCase();
                        // separar en campos individuales (Apellido Paterno, Apellido Materno, Nombres)
                        try { parseFullNameAndSet(data.apellidos_nombres); } catch(e) { console.warn('parseFullNameAndSet error', e); }
                        // asignar brevete si viene
                        if (data.numero_brevete) {
                            if (breveteInput) breveteInput.value = (data.numero_brevete || '').toUpperCase();
                        } else {
                            if (breveteInput) breveteInput.value = '';
                            // Si hay nombres pero no brevete, solo habilitar el campo brevete
                            try {
                                const mt = document.getElementById('manualToggleEdit');
                                // Marcar toggle como checked PERO NO disparar el evento change
                                if (mt) mt.checked = true;
                                // Solo habilitar el campo brevete manualmente
                                if (breveteInput) { breveteInput.readOnly = false; breveteInput.focus(); }
                            } catch(e) { console.warn('No se pudo habilitar modo manual para carnet sin brevete', e); }
                        }
                        // Si el proxy devolvió apellidos_nombres pero no entró en el bloque anterior, asegurarlo
                        try {
                            if ((!apellidosNombresInput || !apellidosNombresInput.value) && data && data.apellidos_nombres) {
                                if (apellidosNombresInput) apellidosNombresInput.value = data.apellidos_nombres.toUpperCase();
                                try { parseFullNameAndSet(data.apellidos_nombres); } catch(e) { console.warn('parseFullNameAndSet error edit fallback', e); }
                            }
                        } catch(e) { console.warn('fallback apellidos_nombres edit error', e); }
                    } else {
                        // Limpiar brevete por si quedaba un valor anterior
                        if (breveteInput) breveteInput.value = '';
                        mostrarMensajeDni((data && data.message) || 'No se encontró información para este carnet.');
                        try {
                            const mt = document.getElementById('manualToggleEdit');
                            if (mt) { mt.checked = true; mt.dispatchEvent(new Event('change')); }
                            try { if (apP) apP.readOnly = false; if (apM) apM.readOnly = false; if (n) n.readOnly = false; if (brev) { brev.readOnly = false; brev.focus(); } } catch(innerErr) { console.warn('No se pudo forzar habilitación de campos edit (no info)', innerErr); }
                        } catch(err) { console.warn('No se pudo activar modo manual tras no encontrar info carnet edit', err); }
                    }
                    btnConsultarCarnet.disabled = false; btnConsultarCarnet.textContent = 'Consultar';
                })
                .catch(() => { mostrarMensajeDni('No se pudo consultar el carnet.'); btnConsultarCarnet.disabled = false; btnConsultarCarnet.textContent = 'Consultar'; });
            });
            carnetInput.addEventListener('input', function() { if (hiddenDoc) hiddenDoc.value = carnetInput.value.trim(); });
        }
    }

    function mostrarMensajeDni(mensaje) {
        const dniInput = document.getElementById('Dni');
        const carnetInput = document.getElementById('CarnetExtranjeria');
        const input = (dniInput && dniInput.offsetParent !== null) ? dniInput : carnetInput;
        if (!input) return;
        let div = document.getElementById('mensajeDni');
        if (!div) {
            div = document.createElement('div');
            div.id = 'mensajeDni';
            div.style.marginBottom = '10px';
            div.style.padding = '12px';
            div.style.background = '#fff3cd';
            div.style.color = '#856404';
            div.style.border = '1px solid #ffeeba';
            div.style.borderRadius = '5px';
            div.style.fontWeight = 'bold';
            div.style.fontSize = '1rem';
            div.style.textAlign = 'center';
            input.parentNode.parentNode.insertBefore(div, input.parentNode);
        }
        div.textContent = mensaje;
        setTimeout(() => { div.remove(); }, 4000);
    }

    // Helper: comprobar brevete duplicado vía AJAX al controlador (versión edit)
    function checkBreveteEdit(brevete) {
        return new Promise((resolve) => {
            if (!brevete) return resolve({ success: false, exists: false });
            const body = new URLSearchParams();
            body.append('brevete', brevete);
            fetch(window.APP_URL + '/choferes/checkBrevete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            })
            .then(r => r.json())
            .then(json => resolve(json))
            .catch(err => { console.error('Error comprobando brevete (edit)', err); resolve({ success: false, exists: false }); });
        });
    }

    renderDocumentoField();
    nacionalidadSelect.addEventListener('change', function(){ clearPersonFields(); renderDocumentoField(); });
});
</script>
